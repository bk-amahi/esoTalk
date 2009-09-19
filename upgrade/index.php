<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Upgrader: Upgrades esoTalk with every new release

define("IN_ESOTALK", 1);

// No timeout
@set_time_limit(0);

// Get the new version and the current version, and compare them. If we don't need to upgrade, home we go!
require "../config.default.php";
require "../config/versions.php";
if ($versions["esoTalk"] == ESOTALK_VERSION) {
	header("Location: ../index.php");
	exit;
}

// Require essential files.
require "../lib/functions.php";
require "../lib/database.php";
require "../config/config.php";
require "upgrade.controller.php";

// Start a session if one does not already exist
if (!session_id()) session_start();

// Undo register globals
undoRegisterGlobals();

// If magic quotes is on, strip the slashes that it added
if (get_magic_quotes_gpc()) {
	$_GET = array_map("undoMagicQuotes", $_GET);
	$_POST = array_map("undoMagicQuotes", $_POST);
	$_COOKIE = array_map("undoMagicQuotes", $_COOKIE);
}

// Clean and sterilize the request data
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);
$_COOKIE = sanitize($_COOKIE);

$upgrade = new Upgrade();
$upgrade->init();

?>