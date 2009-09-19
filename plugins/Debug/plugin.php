<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Debug plugin: Shows programming debug information for administrators

if (!defined("IN_ESOTALK")) exit;

class Debug extends Plugin {

var $id = "Debug";
var $name = "Debug";
var $version = "1.0.0";
var $description = "Shows programming debug information for administrators";
var $author = "esoTalk team";

var $start;

function Debug()
{
	$this->start = $this->microtimeFloat();
	if (empty($_SESSION["queries"]) or !is_array($_SESSION["queries"])) $_SESSION["queries"] = array();
}

function init()
{
	parent::init();
	
	$this->esoTalk->addHook("beforeDatabaseQuery", array($this, "addQuery"));

	if ($this->esoTalk->ajax) {
		$this->esoTalk->addHook("ajaxFinish", array($this, "addInformationToAjaxResult"));
		return;
	}
	
	// Language definitions
	$this->esoTalk->addLanguage("Debug information", "Debug information");
	$this->esoTalk->addLanguage("Page loaded in", "Page loaded in just over");
	$this->esoTalk->addLanguage("MySQL queries", "MySQL queries");
	$this->esoTalk->addLanguage("POST + GET + FILES information", "POST + GET + FILES information");
	$this->esoTalk->addLanguage("SESSION + COOKIE information", "SESSION + COOKIE information");
	$this->esoTalk->addLanguage("seconds", "seconds");
	
	$this->esoTalk->addScript("plugins/Debug/debug.js", 1000);
	
	$this->esoTalk->addHook("footer", array($this, "renderDebug"));
}

function addInformationToAjaxResult($esoTalk, &$result)
{
	//if (empty($esoTalk->user["admin"])) return;
	$result["queries"] = "";
	foreach ($_SESSION["queries"] as $query) $result["queries"] .= "<li style='margin-bottom:1em'>" . sanitize($query) . "</li>";
	$end = $this->microtimeFloat();
	$time = round($end - $this->start, 4);
	$result["loadTime"] = $time;
	$result["debugPost"] = sanitize(print_r($_POST, true));
	$result["debugGet"] = sanitize(print_r($_GET, true));
	$result["debugFiles"] = sanitize(print_r($_FILES, true));
	$result["debugSession"] = sanitize(print_r($_SESSION, true));
	$result["debugCookie"] = sanitize(print_r($_COOKIE, true));
	$_SESSION["queries"] = array();
}

function microtimeFloat()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function addQuery($esoTalk, $query) {$_SESSION["queries"][] = $query;}

// Render the debug info box
function renderDebug($esoTalk)
{
	//if (empty($esoTalk->user["admin"])) return;

	global $language;
		
	echo "<div id='debug' class='msg' style='margin:25px; padding:10px'>
<h2 style='margin-top:0'>{$language["Debug information"]}</h2>";

	// Page loading timer
	$end = $this->microtimeFloat();
	$time = round($end - $this->start, 4);
	echo "{$language["Page loaded in"]} <strong><span id='loadTime'>$time</span> {$language["seconds"]}</strong>";
	
	// MySQL queries
	echo "<h3>{$language["MySQL queries"]}</h3><ul id='queries' class='fixed'>";
	if (!count($_SESSION["queries"])) echo "<li></li>";
	else foreach ($_SESSION["queries"] as $query) echo "<li style='margin-bottom:1em'>" . sanitize($query) . "</li>";
	$_SESSION["queries"] = array();
	
	// POST + GET + FILES information
	echo "</ul><h3>{$language["POST + GET + FILES information"]}</h3><p style='white-space:pre' class='fixed' id='debugPost'>\$_POST = ";
	echo sanitize(print_r($_POST, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugGet'>\$_GET = ";
	echo sanitize(print_r($_GET, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugFiles'>\$_FILES = ";
	echo sanitize(print_r($_FILES, true));
	echo "</p>";
	
	// SESSION + COOKIE information
	echo "<h3>{$language["SESSION + COOKIE information"]}</h3><p style='white-space:pre' class='fixed' id='debugSession'>\$_SESSION = ";
	echo sanitize(print_r($_SESSION, true));
	echo "</p><p style='white-space:pre' class='fixed' id='debugCookie'>\$_COOKIE = ";
	echo sanitize(print_r($_COOKIE, true));
	echo "</p></div>";
}

}

?>