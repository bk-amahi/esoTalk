<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Shouts plugin: Allows members to leave short comments on other member profiles.

if (!defined("IN_ESOTALK")) exit;

class Shouts extends Plugin {
	
var $id = "Shouts";
var $name = "Shouts";
var $version = "1.0.0";
var $description = "Allows members to leave short comments on other member profiles.";
var $author = "esoTalk team";

var $limit = 50;

function init()
{
	parent::init();
	
	// Language definitions.
	$this->esoTalk->addLanguage("Shouts", "Shouts");
	$this->esoTalk->addLanguage("Shout it", "Shout it!");
	$this->esoTalk->addLanguage("Type a shout here", "Type a shout here...");
	$this->esoTalk->addLanguage("View more shouts", "View more shouts");
	$this->esoTalk->addLanguage("emailOnNewShout", "Email me when someone adds a shout to my profile");
	$this->esoTalk->addLanguage(array("emails", "newShout", "subject"), "%s, someone shouted on your profile");
	$this->esoTalk->addLanguage(array("emails", "newShout", "body"), "%s, %s has added a shout to your profile!\n\nTo view the new activity, check out the following link:\n%s");
	
	// If we're on the profile view, initiate all the shout stuff.
	if ($this->esoTalk->action == "profile") {
		$this->esoTalk->controller->addHook("init", array($this, "addShoutsSection"));
		$this->esoTalk->controller->addHook("ajax", array($this, "ajax"));
		$this->esoTalk->addScript("plugins/Shouts/shouts.js");
		$this->esoTalk->addCSS("plugins/Shouts/shouts.css");
	}
	
	if ($this->esoTalk->action == "settings") {
		$this->esoTalk->controller->addHook("init", array($this, "addShoutsSettings"));
	}
}

function addShoutsSettings(&$settings)
{
	global $language;
	if (!isset($this->esoTalk->user["emailOnNewShout"])) $_SESSION["emailOnNewShout"] = $this->esoTalk->user["emailOnNewShout"] = 0;
	$settings->addToForm("settingsOther", array(
		"id" => "emailOnNewShout",
		"html" => "<label for='emailOnNewShout' class='checkbox'>{$language["emailOnNewShout"]}</label> <input id='emailOnNewShout' type='checkbox' class='checkbox' name='emailOnNewShout' value='1' " . ($this->esoTalk->user["emailOnNewShout"] ? "checked='checked' " : "") . "/>",
		"databaseField" => "emailOnNewShout",
		"checkbox" => true,
		"required" => true
	), 400);
}

function addShoutsSection(&$controller)
{
	global $language;
	$this->member =& $controller->member;

	// Add or delete a shout
	if (isset($_POST["shoutSubmit"])) $this->addShout($_POST["shoutContent"]);
	if (isset($_GET["deleteShout"]) and $shoutId = (int)$_GET["deleteShout"]) $this->deleteShout($shoutId);

	// Get the shouts and generate the shout html
	if (!empty($_GET["limit"])) $this->limit = (int)$_GET["limit"];
	$controller->shouts = $this->getShouts($controller->member["memberId"], $this->limit);
	$section = "
<div class='hdr'><h3>{$language["Shouts"]}</h3></div>
<div class='body shouts'>";
	
	// If the user is not logged in, they can't send a shout! Otherwise, show the send shout form.
	if (!$this->esoTalk->user) $section .= $this->esoTalk->htmlMessage("loginRequired");
	else $section .= "<form action='" . curLink() . "' method='post' id='shoutForm'><div>
<input type='text' class='text' id='shoutContent' name='shoutContent'/> " . $this->esoTalk->skin->button(array("value" => $language["Shout it"], "id" => "shoutSubmit", "name" => "shoutSubmit")) . "
<script type='text/javascript'>makePlaceholder($(\"shoutContent\"), \"{$language["Type a shout here"]}\");</script>
</div></form>";

	// Loop through the shouts and output them.
	$section .= "<div id='shouts'>";
	foreach ($controller->shouts as $shout) {
		$section .= "<div id='shout{$shout["shoutId"]}'>" . $this->htmlShout($shout) . "</div>";
	}
	$section .= "</div>";
	
	// If there are more shouts, show a 'view more' link.
	if ($this->showViewMore) $section .= "<div><a href='" . makeLink("profile", $this->member["memberId"], "?limit=" . ($this->limit + 50)) . "'>{$language["View more shouts"]}</a></div>";
	$section .= "</div>";

	// Initialize the shout javascript.
	if ($this->esoTalk->user) $section .= "<script type='text/javascript'>Shouts.member={$controller->member["memberId"]};Shouts.init();</script>";
	
	// Add the section!
	$controller->addSection($section);
}

// Shout ajax
function ajax(&$controller, &$return)
{
	global $config;
	switch ($_POST["action"]) {
	
		// Add a new shout.
		case "shout":
			// Does this member exist?
			if (!($this->member = $controller->getMember($_POST["memberTo"]))) {
				$this->esoTalk->message("memberDoesntExist");
				return;
			}
			// Return the shout html and the shout id if we successfully add the shout.
			if ($shout = $this->addShout($_POST["content"])) {
				$return = array(
					"html" => $this->htmlShout($shout + array("name" => $this->esoTalk->user["name"], "color" => $this->esoTalk->user["color"], "avatarFormat" => $this->esoTalk->user["avatarFormat"])),
					"shoutId" => $shout["shoutId"]
				);
			}
			break;
		
		// Delete a shout. Easy!
		case "deleteShout":
			$this->deleteShout($_POST["shoutId"]);
	}
}

// Fetch the shouts from the database. 
function getShouts($memberId, $limit = 50)
{
	global $config;
	
	$shouts = array();
	$memberId = (int)$memberId;
	$result = $this->esoTalk->db->query("SELECT shoutId, memberFrom, name, color, avatarFormat, time, content FROM {$config["tablePrefix"]}shouts LEFT JOIN {$config["tablePrefix"]}members ON (memberId=memberFrom) WHERE memberTo=$memberId ORDER BY time DESC, shoutId DESC LIMIT " . ($limit + 1));
	
	// We selected $limit + 1 results; if there is that +1 result, there are more results to display.
	$this->showViewMore = $this->esoTalk->db->numRows($result) > $limit;
	
	// Put the results into an array.
	for ($i = 0; $i < $limit and $shout = $this->esoTalk->db->fetchAssoc($result); $i++) $shouts[] = $shout;
	return $shouts;
}

// Generate the html for an individual shout.
function htmlShout($shout)
{
	global $language;
	
	// Generate the shout wrapper, avatar, name, and time.
	$output = "<div class='p c{$shout["color"]}'><div class='hdr'>
<img src='" . $this->esoTalk->getAvatar($shout["memberFrom"], $shout["avatarFormat"], "thumb") . "' alt='' class='avatar thumb'/>
<div class='pInfo'><h4><a href='" . makeLink("profile", $shout["memberFrom"]) . "'>{$shout["name"]}</a></h4><br/><span>" . relativeTime($shout["time"]) . "</span></div>";

	// If the user can delete this shout, show the delete link.
	if ($this->canDeleteShout($shout["memberFrom"], $this->member["memberId"]) === true)
		$output .= "<div class='controls'><a href='" . makeLink("profile", $this->member["memberId"], "?deleteShout={$shout["shoutId"]}") . "' onclick='Shouts.deleteShout({$shout["shoutId"]});return false'>{$language["delete"]}</a></div>";
		
	// Finally, the shout content.
	$output .= "<p>" . $this->esoTalk->formatter->display($shout["content"], array("emoticons")) . "</p>
</div></div>";
	return $output;
}

// Add a shout to the database, and notify the member via email.
function addShout($content)
{
	global $config, $language;
	
	// Does the shout have content? Is this user allowed to send a shout?
	if (($error = !$content ? "emptyPost" : false) or ($error = $this->canAddShout()) !== true) {
		$this->esoTalk->message($error);
		return;
	}
	
	// Prepare and add the shout to the database.
	$shout = array(
		"memberTo" => $this->member["memberId"],
		"memberFrom" => $this->esoTalk->user["memberId"],
		"time" => time(),
		"content" => $this->esoTalk->formatter->format($content, array("bold", "italic", "strikethrough", "superscript", "link", "fixedInline", "specialCharacters"))
	);
		
	$this->esoTalk->db->query("INSERT INTO {$config["tablePrefix"]}shouts (memberTo, memberFrom, time, content) VALUES ({$shout["memberTo"]}, {$shout["memberFrom"]}, {$shout["time"]}, '" . addslashes($shout["content"]) . "')");
	$shout["shoutId"] = $this->esoTalk->db->lastInsertId();
	
	// Notify the member via email.
	if ($shout["memberTo"] != $shout["memberFrom"]) {
		list($emailOnNewShout, $email, $name) = $this->esoTalk->db->fetchRow("SELECT emailOnNewShout, email, name FROM {$config["tablePrefix"]}members WHERE memberId={$shout["memberTo"]}");
		if ($emailOnNewShout) sendEmail($email, sprintf($language["emails"]["newShout"]["subject"], $name), sprintf($language["emails"]["newShout"]["body"], $name, $this->esoTalk->user["name"], $config["baseURL"] . makeLink("profile", $shout["memberTo"])));
	}
	
	return $shout;
}

// Delete a shout from the database.
function deleteShout($shoutId)
{
	global $config;
	$shoutId = (int)$shoutId;
	
	// Can we find the shout we're trying to delete? Are we allowed to delete it?
	if (!(list($memberFrom, $memberTo) = $this->esoTalk->db->fetchRow("SELECT memberFrom, memberTo FROM {$config["tablePrefix"]}shouts WHERE shoutId=$shoutId"))) return;
	if (($error = $this->canDeleteShout($memberFrom, $memberTo)) !== true) {
		$this->esoTalk->message($error);
		return;
	}
	
	// Goodbye!
	$this->esoTalk->db->query("DELETE FROM {$config["tablePrefix"]}shouts WHERE shoutId=$shoutId");
}

// Does the current user have permission to add a shout? They must be logged in, not suspended, and can't have shouted recently.
function canAddShout()
{
	if (!$this->esoTalk->user) return "noPermission";
	if ($this->esoTalk->isSuspended()) return "suspended";
	global $config;
	if ($this->esoTalk->db->result("SELECT 1 FROM {$config["tablePrefix"]}shouts WHERE memberFrom={$this->esoTalk->user["memberId"]} AND time>UNIX_TIMESTAMP()-{$config["timeBetweenPosts"]}", 0)) return "waitToReply";
	return true;
}

// Does the current user have permission to delete a shout? They must be the receiver or the sender of the shout.
function canDeleteShout($memberFrom, $memberTo)
{
	if (!$this->esoTalk->user["moderator"] and $this->esoTalk->user["memberId"] != $memberFrom and $this->esoTalk->user["memberId"] != $memberTo) return "noPermission";
	return true;
}

// Add the table to the database.
function upgrade($oldVersion)
{
	global $config;
	
	if (!$this->esoTalk->db->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'emailOnNewShout'")) {
		$this->esoTalk->db->query("ALTER TABLE {$config["tablePrefix"]}members ADD COLUMN emailOnNewShout tinyint(1) NOT NULL default '0'");
	}
	
	if (!$oldVersion) {
		if ($this->esoTalk->db->numRows("SHOW TABLES LIKE '{$config["tablePrefix"]}shouts'")) return;
		$this->esoTalk->db->query("DROP TABLE IF EXISTS {$config["tablePrefix"]}shouts");
		$this->esoTalk->db->query("CREATE TABLE {$config["tablePrefix"]}shouts (
			shoutId int unsigned NOT NULL auto_increment,
			memberTo int unsigned NOT NULL,
			memberFrom int unsigned NOT NULL,
			time int unsigned NOT NULL,
			content text NOT NULL,
			PRIMARY KEY  (shoutId)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	}

}

}

?>