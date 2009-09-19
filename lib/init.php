<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Basic page initialization: include configuration settings, check the version, require essential files,
// start a session, fix magic quotes/register_globals, sanitize request data, include the esoTalk controller,
// skin file, language file, and load plugins.

if (!defined("IN_ESOTALK")) exit;

// Include out config files.
require "config.default.php";
@include "config/config.php";
// If $config isn't set, esoTalk hasn't been installed. Redirect to the installer.
if (!isset($config)) {
	if (!defined("AJAX_REQUEST")) header("Location: install/index.php");
	exit;
}
// Combine config.default.php and config/config.php into $config (the latter will overwrite the former.)
$config = array_merge($defaultConfig, $config);

// Compare the hardcoded version of esoTalk (ESOTALK_VERSION) to the installed one ($versions["esoTalk"]).
// If they're out-of-date, redirect to the upgrader.
require "config/versions.php";
if ($versions["esoTalk"] != ESOTALK_VERSION) {
	if (!defined("AJAX_REQUEST")) header("Location: upgrade/index.php");
	exit;
}

// Require essential files.
require "functions.php";
require "database.php";
require "classes.php";
require "formatter.php";

// Start a session if one does not already exist.
if (!session_id()) {
	session_name("{$config["cookieName"]}_Session");
	session_start();
	$_SESSION["ip"] = $_SERVER["REMOTE_ADDR"];
	if (empty($_SESSION["token"])) regenerateToken();
}

// Prevent session highjacking - check the current IP address against the one that initiated the session.
if ($_SERVER["REMOTE_ADDR"] != $_SESSION["ip"]) session_destroy();

// Undo register_globals.
undoRegisterGlobals();

// If magic quotes is on, strip the slashes that it added.
if (get_magic_quotes_gpc()) {
	$_GET = array_map("undoMagicQuotes", $_GET);
	$_POST = array_map("undoMagicQuotes", $_POST);
	$_COOKIE = array_map("undoMagicQuotes", $_COOKIE);
}

// Replace GET values with ones from the request URI. (ex. index.php/test/123 -> ?q1=test&q2=123)
if (!empty($config["useFriendlyURLs"]) and isset($_SERVER["REQUEST_URI"])) {
	$parts = processRequestURI($_SERVER["REQUEST_URI"]);
	for ($i = 1, $count = count($parts); $i <= $count; $i++) $_GET["q$i"] = $parts[$i - 1];
}

// Sanitize the request data. This is pretty much the same as using htmlentities. 
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);
$_COOKIE = sanitize($_COOKIE);

// Include and set up the esoTalk controller.
require "controllers/esoTalk.controller.php";
$esoTalk = new esoTalk();
$esoTalk->esoTalk =& $esoTalk;

// Include the language file.
$esoTalk->language = sanitizeFileName((isset($_SESSION["user"]["language"]) and file_exists("languages/{$_SESSION["user"]["language"]}.php")) ? $_SESSION["user"]["language"] : $config["language"]);
if (file_exists("languages/$esoTalk->language.php")) include "languages/$esoTalk->language.php";
// If we haven't got a working language, show an error!
if (empty($language)) $esoTalk->fatalError("esoTalk can't find a language file to use. Please make sure <code>languages/$esoTalk->language.php</code> exists or change the default language by adding <code>\"language\" => \"YourLanguage\",</code> to <code>config/config.php</code>.");

// Include the skin file.
require "config/skin.php";
if (file_exists("skins/{$config["skin"]}/skin.php")) include_once "skins/{$config["skin"]}/skin.php";
if (class_exists($config["skin"])) {
	$esoTalk->skin = new $config["skin"];
	$esoTalk->skin->esoTalk =& $esoTalk;
	$esoTalk->skin->init();
}
// If we haven't got a working skin, show an error!
if (empty($esoTalk->skin)) $esoTalk->fatalError("esoTalk can't find a skin file to use. Please make sure <code>skins/{$config["skin"]}/skin.php</code> exists or change the default skin in <code>config/skin.php</code>.");

// Load plugins, which will hook on to controllers.
require "config/plugins.php";
foreach ($config["loadedPlugins"] as $v) {
	$v = sanitizeFileName($v);
	if (file_exists("plugins/$v/plugin.php")) include_once "plugins/$v/plugin.php";
	if (class_exists($v)) {
		$esoTalk->plugins[$v] = new $v;
		$esoTalk->plugins[$v]->esoTalk =& $esoTalk;
	}
}

?>