<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// esoTalk controller: Handles global actions such as logging in/out, preparing the bar, and collecting messages.

if (!defined("IN_ESOTALK")) exit;

class esoTalk extends Controller {

var $db;
var $user;
var $action;
var $controller;
var $language;
var $skin;
var $ajax = false;
var $scripts = array();
var $styleSheets = array();
var $head = "";
var $labels = array(
	"sticky" => "IF(sticky=1,1,0)",
	"private" => "IF(private=1,1,0)",
	"locked" => "IF(locked=1,1,0)",
	"draft" => "IF(s.draft IS NOT NULL,1,0)"
);
var $memberGroups = array("Administrator", "Moderator", "Member", "Suspended");
var $bar = array("left" => array(), "right" => array());
var $footer = array();
var $plugins = array();
var $jsLanguage = array();
var $jsVars = array();
var $allowedActions = array("conversation", "feed", "forgot-password", "join", "online", "plugins", "post", "profile", "search", "settings", "skins");

// Connect to the database. Redirect if the 'Start a conversation' button was pressed
function esoTalk()
{
	if (isset($_POST["new"])) redirect("conversation", "new");
	
	// Connect to the database
	global $config;
	$this->db = new Database();
	$this->db->esoTalk =& $this;
	if (!$this->db->connect($config["mysqlHost"], $config["mysqlUser"], $config["mysqlPass"], $config["mysqlDB"]))
		$this->fatalError($config["verboseFatalErrors"] ? $this->db->error() : "");
	
	if (!isset($_SESSION["messages"]) or !is_array($_SESSION["messages"])) $_SESSION["messages"] = array();
	
	$this->formatter = new Formatter();
}

// Initialize; connect to the db, log in, prepare page
function init()
{
	global $language, $config;

	// Logout if the user's asking for it
	if (@$_GET["q1"] == "logout") $this->logout();
		
	// Attempt to log in, and assign data to the user array
	if ($this->login(@$_POST["login"]["name"], @$_POST["login"]["password"])) {
		$this->user = $_SESSION["user"] + array(
			"admin" => $_SESSION["user"]["account"] == "Administrator",
			"moderator" => $_SESSION["user"]["account"] == "Moderator" or $_SESSION["user"]["account"] == "Administrator",
			"member" => $_SESSION["user"]["account"] == "Member",
			"suspended" => $_SESSION["user"]["account"] == "Suspended" ? true : null
		);
		$this->user["color"] = min($this->user["color"], $this->skin->numberOfColors);
	}
		
	// Set the default avatarAlignment for logged out users
	if (!isset($_SESSION["avatarAlignment"])) $_SESSION["avatarAlignment"] = $config["avatarAlignment"];
	
	// Check for updates - only for the root admin and on non-ajax requests.
	if ($this->user["memberId"] == $config["rootAdmin"] and !$this->ajax) {
		// How long ago was the last update check? If it was any more than 1 day ago, check again now.
		if (file_exists("config/lastUpdateCheck.php")) include "config/lastUpdateCheck.php";
		if (!isset($lastUpdateCheck) or time() - $lastUpdateCheck >= 86400) $this->checkForUpdates();
	}
	
	// Star a conversation if necessary
	if (isset($_GET["star"])) $this->star($_GET["star"]);
	
	// Set the wrapper view
	if (filesize("config/custom.css") > 0) $this->addCSS("config/custom.css");
	$this->view = "wrapper.php";
	
	if (!$this->ajax) {
	
		// If the user is not logged in, prepare the bar to display the login form / join link
		if (!$this->user) {
			$this->addToBar("left", "<form action='" . curLink() . "' method='post' id='login'><div>
<input id='loginName' name='login[name]' type='text' class='text' value='" . (!empty($_POST["login"]["name"]) ? $_POST["login"]["name"] : $language["Username"]) . "'/>
<input id='loginPassword' name='login[password]' type='password' class='text' value='********'/>
<input id='rememberMe' name='login[rememberMe]' type='checkbox' class='checkbox'/> <label for='rememberMe'>{$language["Remember me"]}</label>
" . $this->skin->button(array("value" => $language["Log in"])) . "
</div></form>
<script type='text/javascript'>" .
(empty($_POST["login"]["name"]) ? "makePlaceholder($('loginName'), '{$language["Username"]}');" : "") . "
makePlaceholder($('loginPassword'), '********');" . 
(!empty($_POST["login"]["name"]) ? "$('loginPassword').focus()" : "") . "
</script>", 100);
			$this->addToBar("left", "<a href='" . makeLink("join") . "'>{$language["Join this forum"]}</a>", 200);
			$this->addToBar("right", "<a href='" . makeLink("forgot-password") . "'>{$language["Forgot your password"]}</a>", 100);
		}
		
		// If the user is logged in, we want to display their name and some links 
		else {
			$this->addToBar("left", "<strong><a href='" . makeLink("profile") . "'>{$this->user["name"]}</a>:</strong>", 100);
			$this->addToBar("left", "<a href='" . makeLink("") . "'>{$language["Home"]}</a>", 200);
			$this->addToBar("left", "<a href='" . makeLink("conversation", "new") . "'>{$language["Start a conversation"]}</a>", 300);
			$this->addToBar("left", "<a href='" . makeLink("settings") . "'>{$language["My settings"]}</a>", 400);
			$this->addToBar("left", "<a href='" . makeLink("logout") . "'>{$language["Log out"]}</a>", 1000);
			if ($this->user["admin"]) {
				$this->addToBar("left", "<a href='" . makeLink("skins") . "'>{$language["Skins"]}</a>", 700);
				$this->addToBar("left", "<a href='" . makeLink("plugins") . "'>{$language["Plugins"]}</a>", 800);
			}
		}
		
		$this->addToFooter("<a href='http://esotalk.com'>{$language["Donate to esoTalk"]}</a>");
		
		// Add the default scripts
		$this->addScript("js/esotalk.js", 100);
		
		$this->addLanguageToJS("ajaxRequestPending", "ajaxDisconnected");
				
	}
	
	$this->callHook("init");
}

// Attempt to login with form data, with a cookie, or with a password hash
function login($name = false, $password = false, $hash = false)
{
	// Are we already logged in?
	if (isset($_SESSION["user"])) return true;

	global $config;
	
	// If a raw password was passed, convert it into a hash
	if ($name and $password) $hash = md5($config["salt"] . $password);
	// Otherwise attempt to get the name and password hash from a cookie
	elseif ($hash === false) {
		$cookie = @$_COOKIE[$config["cookieName"]];
		$memberId = substr($cookie, 0, strlen($cookie) - 32);
		$hash = substr($cookie, -32);
	}
	
	// If we successfully have a name and a hash then we attempt to login
	if (($name or $memberId = (int)$memberId) and $hash !== false) {
		
		$components = array(
			"select" => array("*"),
			"from" => array("{$config["tablePrefix"]}members"),
			"where" => array($name ? "name='$name'" : "memberId=$memberId", "password='$hash'")
		);
		$ip = (int)ip2long($_SESSION["ip"]);
		if (isset($cookie)) $components["where"][] = "cookieIP=" . ($ip ? $ip : "0");
		
		$this->callHook("beforeLogin", array(&$components));

		// Check the username and password against the database
		$result = $this->db->query($this->db->constructSelectQuery($components));
		if ($this->db->numRows($result) and ($data = $this->db->fetchAssoc($result))) {
			if ($data["account"] == "Unvalidated") {
				$this->message("accountNotYetVerified", false, makeLink("join", "sendVerification", $data["memberId"]));
				return false;
			}
			$_SESSION["user"] = $this->user = $data;
			session_regenerate_id();
			regenerateToken();
			if (@$_POST["login"]["rememberMe"]) {
				$ip = (int)ip2long($_SESSION["ip"]);
				$this->esoTalk->db->query("UPDATE {$config["tablePrefix"]}members SET cookieIP=$ip WHERE memberId={$_SESSION["user"]["memberId"]}");
				setcookie($config["cookieName"], $_SESSION["user"]["memberId"] . sanitizeForHTTP($hash), time() + $config["cookieExpire"], "/");
			}
			if (!$this->ajax) refresh();
			return true;
		}

		// Incorrect login details - throw an error at the user
		if (!isset($cookie)) $this->message("incorrectLogin", false);

	}
	
	// Didn't completely fill out the login form? Return an error
	elseif ($name or $password)	$this->message("incorrectLogin", false);
		
	return false;
}

// Logout - destroy all session data
function logout()
{
	global $config;
	
	// Destroy session data
	unset($_SESSION["user"]);
	regenerateToken();

	// Eat the cookie. OM NOM NOM
	if (isset($_COOKIE[$config["cookieName"]])) setcookie($config["cookieName"], "", -1, "/");
	
	$this->callHook("logout");

	// Redirect to the home page
	redirect("");
}

function validateToken($token)
{
	if ($token != $_SESSION["token"]) {
		$this->message("noPermission");
		return false;
	} else return true;
}

// Fetch forum statistics
function getStatistics()
{
	global $config, $language;
	$result = $this->db->query("SELECT (SELECT COUNT(*) FROM {$config["tablePrefix"]}posts),
		(SELECT COUNT(*) FROM {$config["tablePrefix"]}conversations),
		(SELECT COUNT(*) FROM {$config["tablePrefix"]}members WHERE UNIX_TIMESTAMP()-{$config["userOnlineExpire"]}<lastSeen)");
	list($posts, $conversations, $membersOnline) = $this->db->fetchRow($result);
	$result = array(
		"posts" => number_format($posts) . " {$language["posts"]}",
		"conversations" => number_format($conversations) . " {$language["conversations"]}",
		"membersOnline" => number_format($membersOnline) . " " . $language[$membersOnline == 1 ? "member online" : "members online"]
	);
	$this->callHook("getStatistics", array(&$result));
	
	return $result;
}

// Check for updates to the esoTalk software
function checkForUpdates()
{
	if ($this->ajax) return;
	writeConfigFile("config/lastUpdateCheck.php", '$lastUpdateCheck', time());
	
	if (($handle = @fopen("http://get.esotalk.com/latestVersion.txt", "r")) === false) return;
	$latestVersion = fread($handle, 8192);
	fclose($handle);
		
	if (version_compare(ESOTALK_VERSION, $latestVersion) == -1) $this->message("updatesAvailable", false, $latestVersion);
}

// Register a controller and set it up if need be
function registerController($name, $file)
{
	if (@$_GET["q1"] == $name) {
		require_once $file;
		$this->action = $name;
		$this->controller = new $name;
		$this->controller->esoTalk =& $this;
	}
}

// Add a message to the language
function addMessage($key, $class, $message)
{
	global $messages;
	if (!isset($messages[$key])) $messages[$key] = array("class" => $class, "message" => $message);
	return $key;
}

// Add a definition to the language (no overwrite!)
function addLanguage($key, $value)
{
	global $language;
	$definition =& $language;
	foreach ((array)$key as $k) $definition =& $definition[$k];
	if (isset($definition)) return false;
	$definition = $value;
}

// Add a string to the bar
function addToBar($side, $html, $position = false)
{
	addToArray($this->bar[$side], $html, $position);
}

function addToHead($string)
{
	$this->head .= "\n$string";
}

function addToFooter($html, $position = false)
{
	addToArray($this->footer, $html, $position);
}

// Add a script to the array
function addScript($script, $position = false)
{
	addToArray($this->scripts, $script, $position);
}

function addLanguageToJS()
{
	global $language;
	$args = func_get_args();
	foreach ($args as $key) {
		$definition =& $language;
		foreach ((array)$key as $k) $definition =& $definition[$k];
		$this->jsLanguage[$k] =& $definition;
	}
}

function addVarToJS($key, $val)
{
	$this->jsVars[$key] = $val;
}

function head()
{
	global $config, $language;
	
	$head = "<!-- This page was generated by esoTalk (http://esotalk.com) -->\n";
	
	// Base URL and RSS Feeds
	$head .= "<base href='{$config["baseURL"]}'/>\n";
	$head .= "<link href='{$config["baseURL"]}" . makeLink("feed") . "' rel='alternate' type='application/rss+xml' title='{$language["Recent posts"]}'/>\n";
	if ($this->action == "conversation" and !empty($this->controller->conversation["id"]))
		$head .= "<link href='{$config["baseURL"]}" . makeLink("feed", "conversation", $this->controller->conversation["id"]) . "' rel='alternate' type='application/rss+xml' title='\"{$this->controller->conversation["title"]}\"'/>";

	// Stylesheets
	ksort($this->styleSheets);
	foreach ($this->styleSheets as $styleSheet) {
		// If media is ie6 or ie7, use conditional comments.
		if ($styleSheet["media"] == "ie6" or $styleSheet["media"] == "ie7")
			$head .= "<!--[if " . ($styleSheet["media"] == "ie6" ? "lte IE 6" : "IE 7") . "]><link rel='stylesheet' href='{$styleSheet["href"]}' type='text/css'/><![endif]-->\n";
		// If not, use media as an attribute for the link tag.
		else $head .= "<link rel='stylesheet' href='{$styleSheet["href"]}' type='text/css'" . (!empty($styleSheet["media"]) ? " media='{$styleSheet["media"]}'" : "") . "/>\n";
	}

	// JavaScript: output all necessary config variables and language definitions.
	$esoTalkJS = array(
		"baseURL" => $config["baseURL"],
		"user" => $this->user ? $this->user["name"] : false,
		"skin" => $config["skin"],
		"disableAnimation" => !empty($this->esoTalk->user["disableJSEffects"]),
		"avatarAlignment" => !empty($this->esoTalk->user["avatarAlignment"]) ? $this->esoTalk->user["avatarAlignment"] : $_SESSION["avatarAlignment"],
		"messageDisplayTime" => $config["messageDisplayTime"],
		"language" => $this->jsLanguage,
		"token" => $_SESSION["token"]
	) + $this->jsVars;
	$head .= "<script type='text/javascript'>// <![CDATA[
var esoTalk=" . json($esoTalkJS) . ",isIE6,isIE7// ]]></script>\n";
	
	// Add the scripts collected in the $this->scripts array (via $this->addScript()).
	ksort($this->scripts);
	foreach ($this->scripts as $script) $head .= "<script type='text/javascript' src='$script'></script>\n";
	
	// Conditional browser comments to detect IE.
	$head .= "<!--[if lte IE 6]><script type='text/javascript' src='js/ie6TransparentPNG.js'></script><script type='text/javascript'>var isIE6=true</script><![endif]-->\n<!--[if IE 7]><script type='text/javascript'>var isIE7=true</script><![endif]-->";
	
	$head .= $this->head;

	return $head;
}

// Add a style sheet to the array
function addCSS($styleSheet, $media = false) 
{
	addToArray($this->styleSheets, array("href" => $styleSheet, "media" => $media));
}

// Add a message to the messages array
function message($key, $disappear = true, $arguments = false)
{
	$_SESSION["messages"][] = array("message" => $key, "arguments" => $arguments, "disappear" => $disappear);
}

// Returns html of a single message
function htmlMessage($key, $arguments = false)
{
	global $messages;
	$m = $messages[$key];
	if (!empty($arguments)) $m["message"] = is_array($arguments) ? vsprintf($m["message"], $arguments) : sprintf($m["message"], $arguments);
	return "<div class='msg {$m["class"]}'>{$m["message"]}</div>";
}

function getMessages()
{
	global $messages;
	$html = "<div id='messages'>";
	foreach ($_SESSION["messages"] as $m) $html .= $this->htmlMessage($m["message"], $m["arguments"]) . "\n";
	$html .= "</div>
<script type='text/javascript'>
Messages.init();";
	foreach ($_SESSION["messages"] as $m) {
		if (!empty($m["arguments"])) $text = is_array($m["arguments"]) ? vsprintf($messages[$m["message"]]["message"], $m["arguments"]) : sprintf($messages[$m["message"]]["message"], $m["arguments"]);
		else $text = $messages[$m["message"]]["message"];
		$html .= "Messages.showMessage(\"{$m["message"]}\", \"{$messages[$m["message"]]["class"]}\", \"" . escapeDoubleQuotes($text) . "\", " . ($m["disappear"] ? "true" : "false") . ");\n";
	}
	$html .= "</script>";
	return $html;
}

// Throw a fatal error
function fatalError($message)
{
	global $language, $config;
	if ($this->ajax) {
		header("HTTP/1.0 500 Internal Server Error");
		echo strip_tags("{$language["Fatal error"]} - $message");
	} else {
		$messageTitle = isset($language["Fatal error"]) ? $language["Fatal error"] : "Fatal error";
		$messageBody = $language["fatalErrorMessage"] . ($message ? "<div>$message</div>" : "");
		include "views/message.php";
	}
	exit;
}

// Ajax: star a conversation
function ajax()
{
	global $config;
	if (empty($_POST["action"])) return;
	switch ($_POST["action"]) {
		case "star":
			$conversationId = (int)$_POST["conversationId"];
			$this->star($conversationId);
			break;
	}
	
	$return = null;
	$this->callHook("ajax", array(&$return));
	return $return;
}

// Star a conversation
function star($conversationId)
{
	if (!$this->user) return false;

	global $config;	
	$conversationId = (int)$conversationId;
	// Star in the session (new conversation)
	if (!$conversationId) $_SESSION["starred"] = !@$_SESSION["starred"];
	else {
		// Star in the database
		$query = "INSERT INTO {$config["tablePrefix"]}status (conversationId, memberId, starred) VALUES ($conversationId, {$this->user["memberId"]}, 1) ON DUPLICATE KEY UPDATE starred=IF(starred=1,0,1)";
		$this->db->query($query);
		
		$this->callHook("star", array($conversationId));
	}
}

// Get a the html for a star
function htmlStar($conversationId, $starred)
{
	global $language;
	if (!$this->user) return "<span class='star0'>{$language["*"]}</span>";
	else {
		$conversationId = (int)$conversationId;
		return "<a href='" . makeLink(@$_GET["q1"], @$_GET["q2"], @$_GET["q3"], "?star=$conversationId") . "' onclick='toggleStar($conversationId, this);return false' class='star" . ($starred ? "1" : "0") . "'>{$language["*"]}<span> " . ($starred ? $language["Starred"] : $language["Unstarred"]) . "</span></a>";
	}
}

// Does a user have an avatar?
function getAvatar($memberId, $avatarFormat, $type = false)
{
	if ($avatarFormat == "gif" and $type != "thumb" and file_exists("avatars/$memberId.gif")) {
	 	return "avatars/gif.php?id=$memberId";
	} elseif ($avatarFormat) {
		$file = "avatars/$memberId" . ($type == "thumb" ? "_thumb" : "") . ".$avatarFormat";
		if (file_exists($file)) return $file;
	}
	if (!$avatarFormat) {
		global $config;
		switch ($type) {
			case "l": return "skins/{$config["skin"]}/avatarLeft.png";
			case "r": return "skins/{$config["skin"]}/avatarRight.png";
			case "thumb": return "skins/{$config["skin"]}/avatarThumb.png";
		}
	}
}

// Update the user's last action
function updateLastAction($action)
{
	if (!$this->user) return false;
	
	global $config;
	$action = addslashes(substr($action, 0, 255));
	$query = "UPDATE {$config["tablePrefix"]}members SET lastAction='$action', lastSeen=" . time() . " WHERE memberId={$this->user["memberId"]}";
	$this->user["lastSeen"] = $_SESSION["user"]["lastSeen"] = time();
	$this->user["lastAction"] = $_SESSION["user"]["lastAction"] = $action;
	$this->db->query($query);
}

// To change $member's group $this->user must be an admin and $member != rootAdmin and $member != $this->user.
// If $this->user is a moderator and $member's $group is member or suspended, the group can be changed between member/suspended.
// This function will return an array of groups $member can be changed to.
function canChangeGroup($memberId, $group)
{
	global $config;
	if (!$this->user or !$this->user["moderator"] or $memberId == $this->user["memberId"] or $memberId == $config["rootAdmin"]) return false;
	if ($this->user["admin"]) return $this->memberGroups;
	if ($this->user["moderator"] and ($group == "Member" or $group == "Suspended")) return array("Member", "Suspended");
}

// Change a member's group
function changeMemberGroup($memberId, $newGroup, $currentGroup = false)
{
	global $config;
	$memberId = (int)$memberId;
	if (!$currentGroup) $currentGroup = $this->db->result($this->db->query("SELECT account FROM {$config["tablePrefix"]}members WHERE memberId=$memberId"), 0);
	if (!($possibleGroups = $this->canChangeGroup($memberId, $currentGroup)) or !in_array($newGroup, $possibleGroups)) return false;
	
	$this->callHook("changeMemberGroup", array(&$newGroup));
	
	global $config;
	$this->db->query("UPDATE {$config["tablePrefix"]}members SET account='$newGroup' WHERE memberId=$memberId");
}

// Returns if the user is suspended
function isSuspended()
{
	global $config;
	if (!$this->user) return false;
	if ($this->user["suspended"] !== true and $this->user["suspended"] !== false) {
		$account = $this->db->result("SELECT account FROM {$config["tablePrefix"]}members WHERE memberId={$this->user["memberId"]}", 0);
		$this->user["account"] = $_SESSION["user"]["account"] = $account;
		$this->user["suspended"] = $account == "Suspended";
	}
	return $this->user["suspended"];
}

}

?>