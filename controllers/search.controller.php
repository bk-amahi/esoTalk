<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Search controller: Performs searches with gambits, and gets the tag cloud.

if (!defined("IN_ESOTALK")) exit;

class search extends Controller {

var $view = "search.view.php";
var $tagCloud = array();

// Initiate SQL query components
var $searchString;
var $search;
var $select = array();
var $where = array();
var $groupBy = array();
var $orderBy = array();
var $from;
var $limit;
var $reverse;
var $order;

// Gambit arrays
var $gambitCloud = array();
var $aliases = array();
var $gambits = array();

// Results
var $conversations = array();
var $numberOfConversations = 0;

function Search()
{
	if (isset($_POST["search"])) redirect("search", "?q2=" . urlencode(desanitize($_POST["search"])));
}

function init()
{
	global $language;
	
	// Define the gambits
	$this->gambits = array_merge($this->gambits, array(
		array(array($this, "gambitUnread"), 'return $v == strtolower($language["gambits"]["unread"]);'),
		array(array($this, "gambitPrivate"), 'return $v == strtolower($language["gambits"]["private"]);'),
		array(array($this, "gambitStarred"), 'return $v == strtolower($language["gambits"]["starred"]);'),
		array(array($this, "gambitTag"), 'return strpos($v, strtolower($language["gambits"]["tag:"])) === 0;'),
		array(array($this, "gambitActive"), 'return preg_match($language["gambits"]["gambitActive"], $v, $this->matches);'),
		array(array($this, "gambitAuthor"), 'return strpos($v, strtolower($language["gambits"]["author:"])) === 0;'),
		array(array($this, "gambitContributor"), 'return strpos($v, strtolower($language["gambits"]["contributor:"])) === 0;'),
		array(array($this, "gambitMoreResults"), 'return $v == strtolower($language["gambits"]["more results"]);'),
		array(array($this, "gambitDraft"), 'return $v == strtolower($language["gambits"]["draft"]);'),
		array(array($this, "gambitHasNPosts"), 'return preg_match($language["gambits"]["gambitHasNPosts"], $v, $this->matches);'),
		array(array($this, "gambitOrderByPosts"), 'return $v == strtolower($language["gambits"]["order by posts"]);'),
		array(array($this, "gambitOrderByNewest"), 'return $v == strtolower($language["gambits"]["order by newest"]);'),
		array(array($this, "gambitSticky"), 'return $v == strtolower($language["gambits"]["sticky"]);'),
		array(array($this, "gambitRandom"), 'return $v == strtolower($language["gambits"]["random"]);'),
		array(array($this, "gambitReverse"), 'return $v == strtolower($language["gambits"]["reverse"]);'),
		array(array($this, "gambitLocked"), 'return $v == strtolower($language["gambits"]["locked"]);'),
		array(array($this, "fulltext"), 'return $v;')
	));
	$this->gambitCloud += array(
		$language["gambits"]["active last ? hours"] => "s4",
		$language["gambits"]["active last ? days"] => "s5",
		$language["gambits"]["active today"] => "s2",
		$language["gambits"]["author:"] . $language["gambits"]["member"] => "s5",
		$language["gambits"]["contributor:"] . $language["gambits"]["member"] => "s5",
		$language["gambits"]["dead"] => "s4",
		$language["gambits"]["draft"] => "s1 draftText",
		$language["gambits"]["has replies"] => "s2",
		$language["gambits"]["has &gt;10 posts"] => "s4",
		$language["gambits"]["locked"] => "s4 lockedText",
		$language["gambits"]["more results"] => "s2",
		$language["gambits"]["order by newest"] => "s4",
		$language["gambits"]["order by posts"] => "s2",
		$language["gambits"]["private"] => "s1 privateText",
		$language["gambits"]["random"] => "s5",
		$language["gambits"]["reverse"] => "s4",
		$language["gambits"]["starred"] => "s1 starredText",
		$language["gambits"]["sticky"] => "s2 stickyText",
		$language["gambits"]["unread"] => "s1"
	);
	if ($this->esoTalk->user) {
		$this->gambitCloud += array(
			$language["gambits"]["contributor:"] . $language["gambits"]["myself"] => "s4",
			$language["gambits"]["author:"] . $language["gambits"]["myself"] => "s2"
		);
	}
	$this->aliases += array(
		$language["gambits"]["active today"] => $language["gambits"]["active 1 day"],
		$language["gambits"]["has replies"] => $language["gambits"]["has &gt; 1 post"],
		$language["gambits"]["has no replies"] => $language["gambits"]["has 0 posts"],
		$language["gambits"]["dead"] => $language["gambits"]["active &gt; 30 day"]
	);
	
	if (!$this->esoTalk->ajax and isset($_GET["markAsRead"]) and $this->esoTalk->user) $this->markAllConversationsAsRead();
	
	$markedAsRead = !empty($this->esoTalk->user["markedAsRead"]) ? $this->esoTalk->user["markedAsRead"] : "0";
	$this->select += array("c.conversationId AS id", "c.title AS title", "c.slug AS slug", "c.sticky AS sticky", "c.private AS private", "c.locked AS locked", "c.posts AS posts", "sm.name AS startMember", "c.startMember AS startMemberId", "sm.avatarFormat AS avatarFormat", "c.startTime AS startTime", "lpm.name AS lastPostMember", "c.lastPostMember AS lastPostMemberId", "c.lastPostTime AS lastPostTime", "GROUP_CONCAT(t.tag ORDER BY t.tag ASC SEPARATOR ', ') AS tags", "(IF(c.lastPostTime IS NOT NULL,c.lastPostTime,c.startTime)>$markedAsRead AND (s.lastRead IS NULL OR s.lastRead<c.posts)) AS unread",
	"s.starred AS starred");
	
	if (!$this->esoTalk->ajax) {
	
		// Assign the latest search to session
		if (isset($_GET["q"])) $_GET["q2"] = $_GET["q"];
		$this->searchString = $_SESSION["search"] = @$_GET["q2"];
		
		global $config, $language;

		$this->esoTalk->addLanguageToJS("Starred", "Unstarred", array("gambits", "member"), array("gambits", "tag:"), array("gambits", "more results"));
		$this->esoTalk->addVarToJS("updateCurrentResultsInterval", $config["updateCurrentResultsInterval"]);
		$this->esoTalk->addVarToJS("checkForNewResultsInterval", $config["checkForNewResultsInterval"]);
		
		$this->esoTalk->addToBar("right", "<a href='" . makeLink("feed") . "' id='rss'><span>{$language["RSS"]}</span></a>", 500);
		
		// Get the most common tags from the tags table and assign them a text-size class based upon their count
		$r = $this->esoTalk->db->query("SELECT tag, COUNT(tag) AS count FROM {$config["tablePrefix"]}tags GROUP BY tag ORDER BY count DESC LIMIT {$config["numberOfTagsInTagCloud"]}");
		$tags = array();
		if ($this->esoTalk->db->numRows($r)) {
			$step = 5 / $this->esoTalk->db->numRows($r);
			for ($i = 1; list($tag) = $this->esoTalk->db->fetchRow($r); $i++) {
				$this->tagCloud[$tag] = "s" . ceil($i * $step);
				if ($i < 10) $tags[] = $tag;
			}
		}
		
		$this->esoTalk->updateLastAction("");
		
		$this->esoTalk->addToHead("<meta name='keywords' content='" . implode(",", $tags) . "'/>");
		$this->esoTalk->addToHead("<meta name='description' content='" . sprintf($language["forumDescription"], $config["forumTitle"], implode(", ", $tags)) . "'/>");
		
		if (@$_GET["q1"] == "search") $this->esoTalk->addToHead("<meta name='robots' content='noindex, noarchive'/>");
		
		$this->doSearch();
		
		if ($this->esoTalk->user) $this->esoTalk->addToFooter("<a href='" . makeLink("?markAsRead") . "'>{$language["Mark all conversations as read"]}</a>");
	}
	
	$this->callHook("init");
}

function markAllConversationsAsRead()
{
	global $config;
	$this->esoTalk->db->query("UPDATE {$config["tablePrefix"]}members SET markedAsRead=" . time() . " WHERE memberId={$this->esoTalk->user["memberId"]}");
	$this->esoTalk->user["markedAsRead"] = $_SESSION["user"]["markedAsRead"] = time();
}

// Register a custom gambit:
// - $text is the text that will appear in the gambit cloud.
// - $class is the CSS className that will be applied to the text.
// - $function is the function to be called if the gambit is detected (called with call_user_func($function, $gambit, $negate))
// - $condition is the eval() code to be run to see if the gambit is in the search string. ex. 'return $v == "sticky";'
function registerGambit($text, $class, $function, $condition)
{
	$this->gambitCloud[$text] = $class;
	$this->gambits[] = array($function, $condition);
}

// Construct the search query and perform it
function getSearchQuery()
{
	global $config;
	
	// Initialize the 'from' part of the query
	$memberId = $this->esoTalk->user ? $this->esoTalk->user["memberId"] : 0;
	$this->from = array(
		"{$config["tablePrefix"]}conversations c",
		"LEFT JOIN {$config["tablePrefix"]}tags t USING (conversationId)",
		"LEFT JOIN {$config["tablePrefix"]}status s ON (s.conversationId=c.conversationId AND s.memberId=$memberId)",
		"INNER JOIN {$config["tablePrefix"]}members sm ON (c.startMember=sm.memberId)",
		"LEFT JOIN {$config["tablePrefix"]}members lpm ON (c.lastPostMember=lpm.memberId)"
	);
	
	// Add labels to the 'select' part of the query
	$labels = "CONCAT(";
	// Loop through the labels to check if they apply for this conversation
	foreach ($this->esoTalk->labels as $k => $v) $labels .= "$v,',',";
	$labels = substr($labels, 0, -5) . ") AS labels";
	$this->select[] = $labels;

	// Process the search string into gambits
	$search = $this->searchString ? explode("+", strtolower(str_replace("-", "+!", trim($this->searchString, " +-")))) : array();

	// Take each search term and attempt to execute it
	foreach ($search as $v) {

		// Are we dealing with a negative search term, ie. prefixed with a "!"?
		$v = trim($v);
		if ($negate = ($v[0] == "!")) $v = trim($v, "! ");

		// If the term is an alias, translate it into the appropriate gambit
		foreach ($this->aliases as $aliasK => $aliasV) {
			if ($v == $aliasK) {
				$v = $aliasV;
				break;
			}
		}

		// Execute gambits or fulltext search terms
		foreach ($this->gambits as $gambit) {
			list($function, $condition) = $gambit;
			global $language;
			if (eval($condition)) {
				call_user_func_array($function, array(&$this, $v, $negate));
				break;
			}
		}
	}

	if (!$this->esoTalk->user) $this->where[] = "c.posts>0 AND c.private=0";
	else $this->where[] = "c.startMember={$this->esoTalk->user["memberId"]} OR (c.posts>0 AND (c.private=0 OR s.allowed OR (SELECT allowed FROM {$config["tablePrefix"]}status WHERE conversationId=c.conversationId AND memberId='{$this->esoTalk->user["account"]}')))";

	// Append the default order by conditions
	$this->orderBy[] = "IF(s.lastRead IS NULL OR c.posts>s.lastRead, c.sticky, 0) DESC";
	$this->orderBy[] = "IF(c.lastPostTime IS NULL, c.startTime, c.lastPostTime) DESC";
	$this->groupBy[] = "c.conversationId";
	
	// Switch ASC and DESC if the reverse gambit was used!
	if ($this->reverse) $this->orderBy = str_replace(array("DESC", "ASC", "__D__"), array("__D__", "DESC", "ASC"), $this->orderBy);

	// Set the default limit (RESULTS + 1 is so that we can determine if there are more results to display)
	if (!$this->limit) $this->limit = $config["results"] + 1;

	// Build the query
	$components = array("select" => $this->select, "from" => $this->from, "where" => $this->where, "groupBy" => $this->groupBy, "orderBy" => $this->orderBy, "limit" => $this->limit);
	
	$this->callHook("beforeSearch", array(&$components));
	
	return $components;
}

function doSearch()
{
	global $config;
	
	// If they are searching for something, take some flood control measures.
	if ($this->searchString) {
	
		// If we have a record of their searches in the session, check how many searches they've performed in the last minute.
		if (!empty($_SESSION["searches"])) {
			// Clean anything older than 60 seconds out of the searches array.
			foreach ($_SESSION["searches"] as $k => $v) {
				if ($v < time() - 60) unset($_SESSION["searches"][$k]);
			}
			// Have they performed >= $config["searchesPerMinute"] searches in the last minute? If so, don't continue.
			if (count($_SESSION["searches"]) >= $config["searchesPerMinute"]) {
				$this->esoTalk->message("waitToSearch", true, array(60 - time() + min($_SESSION["searches"])));
				return;
			}
		}
		
		// However, if we don't have a record in the session, use the MySQL searches table.
		else {
			// Get the user's IP address.
			$ip = (int)ip2long($_SESSION["ip"]);
			// Have they performed >= $config["searchesPerMinute"] searches in the last minute?
			if ($this->esoTalk->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}searches WHERE ip=$ip AND searchTime>UNIX_TIMESTAMP()-60", 0) >= $config["searchesPerMinute"]) {
				$this->esoTalk->message("waitToSearch", true, 60);
				return;
			}
			// Log this search in the searches table.
			$this->esoTalk->db->query("INSERT INTO {$config["tablePrefix"]}searches (ip, searchTime) VALUES ($ip, UNIX_TIMESTAMP())");
			// Proactively clean the searches table of searches older than 60 seconds.
			$this->esoTalk->db->query("DELETE FROM {$config["tablePrefix"]}searches WHERE searchTime<UNIX_TIMESTAMP()-60");
		}
		
		// Log this search in the session array.
		if (!isset($_SESSION["searches"]) or !is_array($_SESSION["searches"])) $_SESSION["searches"] = array();
		$_SESSION["searches"][] = time();
		
	}
	
	// Construct the search query.
	$components = $this->getSearchQuery();
	$query = $this->esoTalk->db->constructSelectQuery($components);
	
	// And execute it! Finished!
	$this->conversations = array();
	$result = $this->esoTalk->db->query($query);
	$this->numberOfConversations = $this->esoTalk->db->numRows($result);
	$conversationsToDisplay = $this->limit == ($config["results"] + 1) ? $config["results"] : $config["moreResults"];
	for ($i = 0; $i < $conversationsToDisplay and $conversation = $this->esoTalk->db->fetchAssoc($result); $i++)
		$this->conversations[] = $conversation;
}

// AJAX functions
function ajax()
{
	global $config, $language;
	
	switch ($_POST["action"]) {
		
		// Perform a search and return the results HTML.
		case "search":
			$this->view = "searchResults.inc.php";
			$this->searchString = $_SESSION["search"] = $_POST["query"];
			$this->doSearch();
			ob_start();
			$this->render();
			return ob_get_clean();
			break;
		
		// Update the current resultset details (unread, last post details, post count.)
		case "updateCurrentResults":
		
			// Work out which conversations we need to get details for (according to $_POST["conversationIds"].)
			$conversationIds = explode(",", $_POST["conversationIds"]);
			foreach ($conversationIds as $k => $v) if (!($conversationIds[$k] = (int)$v)) unset($conversationIds[$k]);
			$conversationIds = implode(",", array_unique($conversationIds));
			if (!$conversationIds) return;
			
			// We're going to run a query to get the details of all specified conversations.
			$markedAsRead = !empty($this->esoTalk->user["markedAsRead"]) ? $this->esoTalk->user["markedAsRead"] : "0";
			$memberId = $this->esoTalk->user ? $this->esoTalk->user["memberId"] : 0;
			$allowedPredicate = !$this->esoTalk->user ? "c.posts>0 AND c.private=0" : "c.startMember={$this->esoTalk->user["memberId"]} OR (c.posts>0 AND (c.private=0 OR s.allowed OR (SELECT allowed FROM {$config["tablePrefix"]}status WHERE conversationId=c.conversationId AND memberId='{$this->esoTalk->user["account"]}')))";
			$query = "SELECT c.conversationId, (IF(c.lastPostTime IS NOT NULL,c.lastPostTime,c.startTime)>$markedAsRead AND (s.lastRead IS NULL OR s.lastRead<c.posts)) AS unread, lpm.name AS lastPostMember, c.lastPostMember AS lastPostMemberId, c.lastPostTime AS lastPostTime, c.posts AS posts, s.starred AS starred
				FROM {$config["tablePrefix"]}conversations c
				LEFT JOIN {$config["tablePrefix"]}status s ON (s.conversationId=c.conversationId AND s.memberId=$memberId)
				LEFT JOIN {$config["tablePrefix"]}members lpm ON (c.lastPostMember=lpm.memberId)
				WHERE c.conversationId IN ($conversationIds) AND ($allowedPredicate)";
			$result = $this->esoTalk->db->query($query);
			
			// Loop through these conversations and construct an array of details to return in JSON format.
			$conversations = array();
			while (list($id, $unread, $lastPostMember, $lastPostMemberId, $lastPostTime, $postCount, $starred) = $this->esoTalk->db->fetchRow($result)) {
				$conversations[$id] = array(
					"unread" => !$this->esoTalk->user or $unread,
					"lastPostMember" => "<a href='" . makeLink("profile", $lastPostMemberId) . "'>$lastPostMember</a>",
					"lastPostTime" => relativeTime($lastPostTime),
					"postCount" => $postCount,
					"starred" => (int)$starred
				);
			}
			
			return array("conversations" => $conversations, "statistics" => $this->esoTalk->getStatistics());
			break;
		
		// Check for differing results to the current resultset (i.e. new conversations) and notify the user if there is new activity.
		case "checkForNewResults":
			
			$this->searchString = $_POST["query"];
			
			// If the "random" gambit is in the search string, then don't go any further (because the results will obviously differ!)
			$this->search = $this->searchString ? explode("+", strtolower(str_replace("-", "+!", trim($this->searchString, " +-")))) : array();
			foreach ($this->search as $v) {
				if (trim($v) == $language["gambits"]["random"]) return array("newActivity" => false);
			}
			
			// Search flood control - if the user has performed >= $config["searchesPerMinute"] searches in the last minute, don't bother checking for new results.
			// Check the session record of searches if it exists.
			if (!empty($_SESSION["searches"])) {
				foreach ($_SESSION["searches"] as $k => $v) {
					if ($v < time() - 60) unset($_SESSION["searches"][$k]);
				}
				if (count($_SESSION["searches"]) >= $config["searchesPerMinute"]) return;
			// Otherwise, check the database.
			} else {
				$ip = (int)ip2long($_SESSION["ip"]);
				if ($this->esoTalk->db->result("SELECT COUNT(*) FROM {$config["tablePrefix"]}searches WHERE ip=$ip AND searchTime>UNIX_TIMESTAMP()-60", 0) >= $config["searchesPerMinute"]) return;
			}
			
			// Get the search query components and simplify them a bit.
			$components = $this->getSearchQuery();
			$components["select"] = "c.conversationId AS id";
			$memberId = $this->esoTalk->user ? $this->esoTalk->user["memberId"] : 0;
			$components["from"] = array("{$config["tablePrefix"]}conversations c", "LEFT JOIN {$config["tablePrefix"]}status s ON (s.conversationId=c.conversationId AND s.memberId=$memberId)");
			unset($components["groupBy"]);
			if ($this->limit == $config["results"] + 1) $components["limit"] = $config["results"];
			
			// Perform a search using the query components and make an array of conversationId's.
			$query = $this->esoTalk->db->constructSelectQuery($components);
			$result = $this->esoTalk->db->query($query);
			$newConversationIds = array();
			while (list($conversationId) = $this->esoTalk->db->fetchRow($result)) $newConversationIds[] = $conversationId;			

			// Get an array of conversationId's are in the current resultset.
			$conversationIds = explode(",", $_POST["conversationIds"]);
			foreach ($conversationIds as $k => $v) if (!($conversationIds[$k] = (int)$v)) unset($conversationIds[$k]);
			$conversationIds = array_unique($conversationIds);

			// Get the difference of the two sets of conversationId's.
			$diff = array_diff($newConversationIds, $conversationIds);
			return array("newActivity" => count($diff));
	}
	
}

// Gambit functions
function gambitUnread(&$search, $v, $negate) {
	$markedAsRead = !empty($this->esoTalk->user["markedAsRead"]) ? $this->esoTalk->user["markedAsRead"] : "NULL";
	$search->where[] = $negate
		? "IF(c.lastPostTime,c.lastPostTime,c.startTime)<=$markedAsRead OR (s.lastRead IS NOT NULL AND s.lastRead>=c.posts)"
		: "IF(c.lastPostTime,c.lastPostTime,c.startTime)>$markedAsRead AND (s.lastRead IS NULL OR s.lastRead<c.posts)";
}

function gambitPrivate(&$search, $v, $negate) {$search->where[] = $negate ? "c.private=0" : "c.private=1";}

function gambitStarred(&$search, $v, $negate) {$search->where[] = $negate ? "s.starred=0" : "s.starred=1";}

function gambitTag(&$search, $v, $negate) {
	global $language, $config;
	$v = str_replace(array("&#39;", "&quot;"), null, trim(substr($v, strlen($language["gambits"]["tag:"]))));
	$search->where[] = ($negate ? "NOT " : "") . "EXISTS (SELECT 1 FROM {$config["tablePrefix"]}tags WHERE tag='$v' and conversationId=c.conversationId)";
}

function gambitActive(&$search, $v, $negate) {
	global $language;
	switch ($search->matches["c"]) {
		case $language["gambits"]["minute"]: $search->matches["b"] *= 60; break;
		case $language["gambits"]["hour"]: $search->matches["b"] *= 3600; break;
		case $language["gambits"]["day"]: $search->matches["b"] *= 86400; break;
		case $language["gambits"]["week"]: $search->matches["b"] *= 604800; break;
		case $language["gambits"]["month"]: $search->matches["b"] *= 2626560; break;
		case $language["gambits"]["year"]: $search->matches["b"] *= 31536000;
	}
	$search->matches["a"] = (!$search->matches["a"] or $search->matches["a"] == $language["gambits"]["last"]) ? "<=" : htmlspecialchars_decode($search->matches["a"]);
	if ($negate) {
		switch ($search->matches["a"]) {
			case "<": $search->matches["a"] = ">="; break;
			case "<=": $search->matches["a"] = ">"; break;
			case ">": $search->matches["a"] = "<="; break;
			case ">=": $search->matches["a"] = "<";
		}
	}
	$search->where[] = "UNIX_TIMESTAMP() - {$search->matches["b"]} {$search->matches["a"]} c.lastPostTime";
}

function gambitAuthor(&$search, $v, $negate) {
	global $language, $config;
	$v = str_replace(array("&#39;", "&quot;"), null, trim(substr($v, strlen($language["gambits"]["author:"]))));
	if ($v == $language["gambits"]["myself"]) $v = $search->esoTalk->user["name"];
	$search->where[] = "c.startMember" . ($negate ? "!" : "") . "=(SELECT memberId FROM {$config["tablePrefix"]}members WHERE name='$v')";
}

function gambitContributor(&$search, $v, $negate) {
	global $language;
	$v = str_replace(array("&#39;", "&quot;"), null, trim(substr($v, strlen($language["gambits"]["contributor:"]))));
	if ($v == $language["gambits"]["myself"]) $v = $search->esoTalk->user["name"];
	global $config;
	$search->where[] = ($negate ? "NOT " : "") . "EXISTS (SELECT 1 FROM {$config["tablePrefix"]}posts INNER JOIN {$config["tablePrefix"]}members USING (memberId) WHERE name='$v' AND conversationId=c.conversationId)";
}

function gambitMoreResults(&$search, $v, $negate)
{
	global $config;
	if (!$negate) $search->limit = $config["moreResults"];
}

function gambitDraft(&$search, $v, $negate) {
	global $config;
	$search->where[] = "s.draft IS NOT NULL";
}

function gambitHasNPosts(&$search, $v, $negate) {
	$search->matches["a"] = (!$search->matches["a"]) ? "=" : htmlspecialchars_decode($this->matches["a"]);
	if ($negate) {
		switch ($search->matches["a"]) {
			case "<": $search->matches["a"] = ">="; break;
			case "<=": $search->matches["a"] = ">"; break;
			case ">": $search->matches["a"] = "<="; break;
			case ">=": $search->matches["a"] = "<"; break;
			case "=": $search->matches["a"] = "!=";
		}
	}
	$search->where[] = "posts {$this->matches["a"]} {$this->matches["b"]}";
}

function gambitOrderByPosts(&$search, $v, $negate) {$search->orderBy[] = "c.posts " . ($negate ? "ASC" : "DESC");}

function gambitOrderByNewest(&$search, $v, $negate) {$search->orderBy[] = "c.startTime " . ($negate ? "ASC" : "DESC");}

function gambitSticky(&$search, $v, $negate) {$search->where[] = "c.sticky=" . ($negate ? "0" : "1");}

function gambitRandom(&$search, $v, $negate) {if (!$negate) $search->orderBy[] = "RAND()";}

function gambitReverse(&$search, $v, $negate) {if (!$negate) $search->reverse = true;}

function gambitLocked(&$search, $v, $negate) {$search->where[] = "c.locked=" . ($negate ? "0" : "1");}

function fulltext(&$search, $v, $negate) {
	global $config;
	$v = str_replace("&quot;", '"', $v);
	$search->where[] = ($negate ? "NOT " : "") . "EXISTS (SELECT 1 FROM {$config["tablePrefix"]}posts WHERE MATCH (title, content) AGAINST ('$v' IN BOOLEAN MODE) AND conversationId=c.conversationId)";
}

}

?>