<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Ajax page: Fires events required for an ajax page to load and display.
// Initializes controllers and plugins, works out what to display, and displays it.

define("IN_ESOTALK", 1);
define("AJAX_REQUEST", 1);

// Basic page initialization: include configuration settings, check the version, require essential files,
// start a session, fix magic quotes/register_globals, sanitize request data, include the esoTalk controller,
// skin file, language file, and load plugins.
require "lib/init.php";

// This is an ajax request!
$esoTalk->ajax = true;

// Set up the action controller.
if (isset($_GET["controller"])) {
	$esoTalk->action = strtolower($_GET["controller"]);
	
	// Does this controller exist?
	if (!in_array($esoTalk->action, $esoTalk->allowedActions) or !file_exists(dirname(__FILE__) . "/controllers/$esoTalk->action.controller.php")) exit;
	
	// Require and set it up.
	require_once "controllers/$esoTalk->action.controller.php";
	$esoTalk->controller = new $esoTalk->action;
	$esoTalk->controller->esoTalk =& $esoTalk;
}

// If none was specificed, use the esoTalk controller.
else {
	$esoTalk->controller =& $esoTalk;
	$esoTalk->action = "esoTalk";
}

// Include the custom.php file.
if (file_exists("config/custom.php")) include "config/custom.php";

// Run plugin init() functions. These will hook onto controllers and add things like language definitions.
foreach ($esoTalk->plugins as $plugin) $plugin->init();

// Initialize esoTalk (connect to the db, login, stylesheets, user bar, etc.)
// Save the token to see if it has been regenerated later on.
$token = $_SESSION["token"];
$esoTalk->init();

// Now we're going to collect the result from the controller's ajax() function...
$controllerResult = null;

// Are we still logged in? If not, display a "been logged out" message/form.
if (!empty($_POST["loggedInAs"]) and empty($esoTalk->user["name"])) {
	$esoTalk->message("beenLoggedOut", false, array($_POST["loggedInAs"], "<input id='loginMsgPassword' type='password' class='text' onkeypress='if(event.keyCode==13)$(\"loginMsgSubmit\").click()'/> 
<input type='submit' value='{$language["Log in"]}' onclick='Ajax.login($(\"loginMsgPassword\").value);return false' id='loginMsgSubmit'/> <input type='button' value='{$language["Cancel"]}' onclick='Ajax.dismissLoggedOut()'/>"));
}

// Everything's fine; we're still logged in. Proceed with normal page actions.
else {
	// Initialize the controller (define variable page content, process forms, etc.)
	$esoTalk->controller->init();
	// Run the controller's ajax function. Collect the result in a variable.
	$controllerResult = $esoTalk->controller->ajax();
}

// $result is the variable that we will pass through json() and output for our Javascript to parse.
$result = array("messages" => array(), "result" => $controllerResult);

// If the token has been regenrated, include it in the result.
if ($token != $_SESSION["token"]) $result["token"] = $_SESSION["token"];

$esoTalk->callHook("ajaxFinish", array(&$result));

// Format and collect all messages from the session into the $result["messages"] array.
if (count($_SESSION["messages"])) {
	foreach ($_SESSION["messages"] as $k => $v) {
		if (!isset($messages[$v["message"]])) continue;
		$m = $messages[$v["message"]];
		if (!empty($v["arguments"])) $m["message"] = is_array($v["arguments"]) ? vsprintf($m["message"], $v["arguments"]) : sprintf($m["message"], $v["arguments"]);
		$result["messages"][$v["message"]] = array($m["class"], $m["message"], $v["disappear"]);
	}
}

// Alright, we're good to go! Output the JSON array to the page.
header("Content-type: text/plain; charset={$language["charset"]}");
echo json($result);

// Clear the messages array now that we have returned the messages.
$_SESSION["messages"] = array();

?>