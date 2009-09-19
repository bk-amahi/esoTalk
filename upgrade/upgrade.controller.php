<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Upgrade controller

class Upgrade extends Database {

function init()
{
	// Make sure the versions file is writable.
	if (!is_writeable("../config/versions.php"))
		$this->fatalError("<code>config/versions.php</code> is not writeable. Try <code>chmod</code>ing it to <code>777</code>, or if it doesn't exist, <code>chmod</code> the folder it is contained within.");
	
	// Connect to the database.
	global $config;
	if (!$this->connect($config["mysqlHost"], $config["mysqlUser"], $config["mysqlPass"], $config["mysqlDB"]))
		$this->fatalError($this->error());
	
	// Perform the upgrade, depending on what version the user is currently at.
	global $versions;
	
	// 1.0.0 alpha 5 -> 1.0.0 beta 1
	if ($versions["esoTalk"] == "1.0.0a5") {
		$this->upgrade_100b1();
		$versions["esoTalk"] = "1.0.0b1";
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}
	
	// Finish the upgrade
	if ($versions["esoTalk"] != ESOTALK_VERSION) {
		$versions["esoTalk"] = ESOTALK_VERSION;
		writeConfigFile("../config/versions.php", '$versions', $versions);
	}
	
	// Display a success message.
	$messageTitle = "You're good to go!";
	$messageBody = "<p>esoTalk has successfully been upgraded. Here's some stuff you should do now:</p>
	<ul>
	<li><strong>Delete the <code>upgrade</code> directory</strong> to prevent your forum from being hacked!</li>
	<li><a href='{$config["baseURL"]}'>Visit your forum</a> and make sure everything is working - if not, get help at the <a href='http://forum.esotalk.com'>esoTalk support forum</a>.</li>
	<li>If you're interested, <a href='javascript:toggleAdvanced()'>see advanced information</a> about what happened during the upgrade process.</li>
	</ul>
	<div id='advanced'>";
	
	// Advanced information...
	// Warnings.
	if (isset($_SESSION["warnings"]) and is_array($_SESSION["warnings"])) {
		$messageBody .= "<strong>Warnings</strong><ul>";
		foreach ($_SESSION["warnings"] as $msg) $messageBody .= "<li>$msg</li>";
		$_SESSION["warnings"] = array();
		$messageBody .= "</ul>";
	}
	
	// Queries run.
	if (isset($_SESSION["queries"]) and is_array($_SESSION["queries"])) {
		$messageBody .= "<strong>Queries run</strong><pre style='overflow:auto'>";
		foreach ($_SESSION["queries"] as $query) $messageBody .= sanitize($query) . ";<br/><br/>";
		$_SESSION["queries"] = array();
		$messageBody .= "</pre>";
	}
	
	$messageBody .= "</div>
	<script type='text/javascript'>
	function toggleAdvanced() {
		document.getElementById(\"advanced\").style.display = document.getElementById(\"advanced\").style.display == \"none\" ? \"\" : \"none\";
	}
	toggleAdvanced();
	</script>";
	include "../views/message.php";
	exit;
}

// Perform a mysql query.
function query($query)
{
	// Perform the query.
	if (!isset($_SESSION["queries"]) or !is_array($_SESSION["queries"])) $_SESSION["queries"] = array();
	$_SESSION["queries"][] = $query;
	$result = mysql_query($query, $this->link);
	if ($result) return $result;
	// Otherwise, show a fatal error.
	else $this->fatalError($this->error() . "<p style='font:100% monospace; overflow:auto'>" . $this->highlightQueryErrors($query, $this->error()) . "</p>");
}

// Display a fatal error with a 'Try again' link.
function fatalError($message)
{
	$messageTitle = "Uh oh! It's a fatal error...";
	$messageBody = "<p>esoTalk has encountered a nasty error which is making it impossible to upgrade your esoTalk installation. But don't feel down - <strong>here are a few things you can try</strong>:</p><ul>
	<li><strong><a href=''>Try again</a></strong>. Everyone makes mistakes - maybe the computer made one this time!</li>
	<li><strong>Get help.</strong> Go on the <a href='http://forum.esotalk.com' title='Don&#039;t worry, we&#039;re friendly!'>esoTalk support forum</a> and <a href='http://forum.esotalk.com/search/tag:upgrade'>search</a> to see if anyone else is having the same problem as you are. If not, start a new conversation about your problem, including the error details below.</li>
	<li>Try hitting the computer - that sometimes works for me.</li>
	</ul>
	<div>$message</div>";
	include "../views/message.php";
	exit;
}

// Write a file - on failure, trigger a fatal error.
function writeFile($file, $contents)
{
	writeFile($file, $contents) or $this->fatalError("<code>$file</code> is not writeable. Try <code>chmod</code>ing it to <code>777</code>, or if it doesn't exist, <code>chmod</code> the folder it is contained within.");
}

// Store a warning message for display in the advanced information section at the end of the upgrade.
function warning($msg)
{
	if (!isset($_SESSION["warnings"]) or !is_array($_SESSION["warnings"])) $_SESSION["warnings"] = array();
	$_SESSION["warnings"][] = $msg;	
}

// 1.0.0 alpha 5 -> 1.0.0 beta 1
function upgrade_100b1()
{
	global $config;
	
	// Add the markedAsRead field
	if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'markedAsRead'"))
		$this->query("ALTER TABLE {$config["tablePrefix"]}members ADD COLUMN markedAsRead int unsigned default NULL AFTER disableJSEffects");

	// Add the cookieIP field
	if (!$this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'cookieIP'"))
		$this->query("ALTER TABLE {$config["tablePrefix"]}members ADD COLUMN cookieIP int default NULL AFTER resetPassword");	
		
	// Fix avatarFormat
	if ($this->numRows("SHOW COLUMNS FROM {$config["tablePrefix"]}members LIKE 'avatarExtension'"))
		$this->query("ALTER TABLE {$config["tablePrefix"]}members CHANGE COLUMN avatarExtension avatarFormat enum('jpg','png','gif') default NULL");
	
	// Add a unique constraint to email in members
	if (!$this->numRows("SHOW INDEX FROM {$config["tablePrefix"]}members WHERE Key_name='members_email'"))
		$this->query("CREATE UNIQUE INDEX members_email ON {$config["tablePrefix"]}members (email)");
		
	// Fix NOT NULL's from a4 (oops!)
	$this->query("ALTER TABLE {$config["tablePrefix"]}conversations MODIFY lastPostMember int unsigned default NULL");
	$this->query("ALTER TABLE {$config["tablePrefix"]}posts MODIFY editMember int unsigned default NULL, MODIFY deleteMember int unsigned default NULL");
	
	// Create the searches table.
	if (!$this->numRows("SHOW TABLES LIKE '{$config["tablePrefix"]}searches'"))
		$this->query("CREATE TABLE {$config["tablePrefix"]}searches (
			ip int unsigned NOT NULL,
			searchTime int unsigned NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	
	// Add an index to the sticky column.
	if (!$this->numRows("SHOW INDEX FROM {$config["tablePrefix"]}conversations WHERE Key_name='conversations_sticky'"))		
		$this->query("CREATE INDEX conversations_sticky ON {$config["tablePrefix"]}conversations (sticky)");
	
	// Delete init.php, classes.php, database.php, formatter.php, and functions.php from the root directory.
	$filesToDelete = array("init.php", "classes.php", "database.php", "formatter.php", "functions.php");
	foreach ($filesToDelete as $file) {
		@unlink("../$file") or $this->warning("esoTalk could not delete <code>/$file</code>. Please delete it manually.");
	}
}

}

?>