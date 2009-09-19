<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Install controller

if (!defined("IN_ESOTALK")) exit;


class Install extends Database {

var $step;
var $errors = array();
var $config;
var $queries = array();

// Initialize the installer
function init()
{
	// Work out which step we're on
	if ($this->errors = $this->fatalChecks()) $this->step = "fatalChecks";
	elseif (@$_GET["step"]) $this->step = $_GET["step"];
	else $this->step = "warningChecks";
	
	switch ($this->step) {
		case "warningChecks":
			if (!($this->errors = $this->warningChecks()) or isset($_POST["next"])) $this->step("info");
			break;
			
		case "info":
			if (isset($_POST["forumTitle"])) {
				if ($this->errors = $this->validateInfo()) return;
				// Put all the POST data into the session and proceed to the install step
				$_SESSION["install"] = array(
					"forumTitle" => $_POST["forumTitle"],
					"mysqlHost" => $_POST["mysqlHost"],
					"mysqlUser" => $_POST["mysqlUser"],
					"mysqlPass" => $_POST["mysqlPass"],
					"mysqlDB" => $_POST["mysqlDB"],
					"adminUser" => $_POST["adminUser"],
					"adminEmail" => $_POST["adminEmail"],
					"adminPass" => $_POST["adminPass"],
					"adminConfirm" => $_POST["adminConfirm"],
					"tablePrefix" => $_POST["tablePrefix"],
					"baseURL" => $_POST["baseURL"],
					"friendlyURLs" => $_POST["friendlyURLs"]
				);
				$this->step("install");
			} else if (isset($_SESSION["install"])) $_POST = $_SESSION["install"];
			break;
			
		case "install":
			if (isset($_POST["back"]) or empty($_SESSION["install"])) $this->step("info");
			if ($this->errors = $this->doInstall()) return;
			$_SESSION["queries"] = $this->queries;
			$this->step("register");
			break;
			
		case "register":
			if (empty($_SESSION["install"])) $this->step("info");
			// Connect to the database so we can get the MySQL version.
			include "../config/config.php";
			$this->connect($config["mysqlHost"], $config["mysqlUser"], $config["mysqlPass"], $config["mysqlDB"]);
			$this->phpVersion = phpversion();
			$this->serverSoftware = $_SERVER["SERVER_SOFTWARE"];
			$this->mysqlVersion = mysql_get_server_info($this->link);
			if (isset($_POST["register"])) {
				if (!empty($_POST["register"])) {
					@ini_set("default_socket_timeout", 15);
					if ($handle = @fopen("http://esotalk.com/register.php?url=" . urlencode($_SESSION["install"]["baseURL"]) . "&php=" . urlencode($this->phpVersion) . "&server=" . urlencode($this->serverSoftware) . "&mysql=" . urlencode($this->mysqlVersion), "r")) fclose($handle);
				}
				$this->step("finish");
			}
			break;
			
		case "finish":
			if (isset($_POST["finish"])) {
				include "../config/config.php";
				$user = $_SESSION["user"];
				// Log in the admin
				session_destroy();
				session_name("{$config["cookieName"]}_Session");
				session_start();
				$_SESSION["user"] = $user;
				header("Location: ../");
				exit;
			}
			// Lock the installer
			if (($handle = fopen("lock", "w")) === false) $this->errors[1] = "esoTalk can't seem to lock the installer. Please manually delete the install folder, otherwise your forum's security will be vulnerable.";
			else fclose($handle);
	}

}

function suggestBaseUrl()
{
	$dir = substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], "/"));
	$dir = substr($dir, 0, strrpos($dir, "/"));
	$baseURL = "http://{$_SERVER["HTTP_HOST"]}{$dir}/";
	return $baseURL;
}

function suggestFriendlyUrls()
{
	return !empty($_SERVER["REQUEST_URI"]);
}

// Perform a mysql query.
function query($query)
{	
	$result = mysql_query($query, $this->link);
	$this->queries[] = $query;
	return $result;
}

// Create tables, write the config file, etc.
function doInstall()
{
	// Make sure the base url has a trailing slash.
	if (substr($_SESSION["install"]["baseURL"], -1) != "/") $_SESSION["install"]["baseURL"] .= "/";
	
	global $config;
	
	// Prepare the config settings
	$config = array(
		"mysqlHost" => desanitize($_SESSION["install"]["mysqlHost"]),
		"mysqlUser" => desanitize($_SESSION["install"]["mysqlUser"]),
		"mysqlPass" => desanitize($_SESSION["install"]["mysqlPass"]),
		"mysqlDB" => desanitize($_SESSION["install"]["mysqlDB"]),
		"tablePrefix" => desanitize($_SESSION["install"]["tablePrefix"]),
		"forumTitle" => $_SESSION["install"]["forumTitle"],
		"baseURL" => $_SESSION["install"]["baseURL"],
		"salt" => generateRandomString(rand(32, 64)),
		"emailFrom" => "do_not_reply@{$_SERVER["HTTP_HOST"]}",
		"cookieName" => preg_replace(array("/\s+/", "/[^\w]/"), array("_", ""), desanitize($_SESSION["install"]["forumTitle"])),
		"useFriendlyURLs" => !empty($_SESSION["install"]["friendlyURLs"]),
		"useModRewrite" => !empty($_SESSION["install"]["friendlyURLs"]) and function_exists("apache_get_modules") and in_array("mod_rewrite", apache_get_modules())
	);
	
	$this->connect($config["mysqlHost"], $config["mysqlUser"], $config["mysqlPass"], $config["mysqlDB"]);
	
	// Get the list of queries that we need to run and run them
	include "queries.php";
	foreach ($queries as $query) {
		if (!$this->query($query)) return array(1 => "<code>" . sanitize($this->error()) . "</code><p><strong>The query that caused this error was</strong></p><pre>" . sanitize($query) . "</pre>");
	}
	
	// Write the config file
	writeConfigFile("../config/config.php", '$config', $config);
	
	// Write the versions.php file
	include "../config.default.php";
	writeConfigFile("../config/versions.php", '$versions', array("esoTalk" => ESOTALK_VERSION));
	
	// Write a .htaccess file
	if ($config["useModRewrite"]) {
		$handle = fopen("../.htaccess", "w");
		fwrite($handle, "# Generated by esoTalk
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [QSA,L]
</IfModule>");
		fclose($handle);
	}
	
	// Write a robots.txt file
	$handle = fopen("../robots.txt", "w");
	fwrite($handle, "User-agent: *
Disallow: /search/
Disallow: /online/
Disallow: /join/
Disallow: /forgotPassword/
Disallow: /conversation/new/
Sitemap: {$config["baseURL"]}sitemap.php");
	fclose($handle);
	
	// Prepare to log in the administrator
	// This won't actually log them in due to different session names. But we do that later.
	$_SESSION["user"] = array(
		"memberId" => 1,
		"name" => $_SESSION["install"]["adminUser"],
		"account" => "Administrator",
		"color" => $color,
		"emailOnPrivateAdd" => false,
		"emailOnStar" => false,
		"language" => "English",
		"avatarAlignment" => "alternate",
		"avatarFormat" => "",
		"disableJSEffects" => false
	);
}

// Validate the information entered in the install form
function validateInfo()
{
	$errors = array();

	// Forum title
	if (!strlen($_POST["forumTitle"])) $errors["forumTitle"] = "Your forum title must consist of at least one character";
	
	// Username
	if (in_array(strtolower($_POST["adminUser"]), array("guest", "member", "members", "moderator", "moderators", "administrator", "administrators", "suspended", "everyone", "myself"))) $errors["adminUser"] = "The name you have entered is reserved and cannot be used";
	if (!strlen($_POST["adminUser"])) $errors["adminUser"] = "You must enter a name";
	if (preg_match("/[" . preg_quote("!/%+-", "/") . "]/", $_POST["adminUser"])) $errors["adminUser"] = "You can't use any of these characters in your name: ! / % + -";
	
	// Email
	if (!preg_match("/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $_POST["adminEmail"])) $errors["adminEmail"] = "You must enter a valid email address";
	
	// Password
	if (strlen($_POST["adminPass"]) < 6) $errors["adminPass"] = "Your password must be at least 6 characters";
	
	// Confirm password
	if ($_POST["adminPass"] != $_POST["adminConfirm"]) $errors["adminConfirm"] = "Your passwords do not match";
	
	// Try and connect to the database
	if (!$this->connect($_POST["mysqlHost"], $_POST["mysqlUser"], $_POST["mysqlPass"], $_POST["mysqlDB"])) $errors["mysql"] = "esoTalk could not connect to the MySQL server. The error returned was:<br/> " . $this->error();
	
	// Check to see if there are any conflicting tables already in the database
	elseif ($_POST["tablePrefix"] != @$_POST["confirmTablePrefix"] and !count($errors)) {
		$result = $this->query("SHOW TABLES");
		$theirTables = array();
		while (list($table) = $this->fetchRow($result)) $theirTables[] = $table;
		$ourTables = array("{$_POST["tablePrefix"]}conversations", "{$_POST["tablePrefix"]}posts", "{$_POST["tablePrefix"]}status", "{$_POST["tablePrefix"]}members", "{$_POST["tablePrefix"]}tags", "{$_POST["tablePrefix"]}attachments");
		$conflictingTables = array_intersect($ourTables, $theirTables);
		if (count($conflictingTables)) {
			$_POST["showAdvanced"] = true;
			$errors["tablePrefix"] = "The installer has detected that there is another installation of esoTalk in the same MySQL database with the same table prefix. The conflicting tables are: <code>" . implode(", ", $conflictingTables) . "</code>.<br/><br/>To overwrite this installation of esoTalk, click 'Next step' again. <strong>All data will be lost.</strong><br/><br/>If you wish to create another esoTalk installation alongside the existing one, <strong>change the table prefix</strong>.<input type='hidden' name='confirmTablePrefix' value='{$_POST["tablePrefix"]}'/>";
		}
	} 
	
	if (count($errors)) return $errors;
}

// Redirect to a specific step
function step($step) {header("Location: index.php?step=$step"); exit;}

// Check for fatal errors
function fatalChecks()
{
	$errors = array();
	
	// Make sure the installer is not locked
	if (@$_GET["step"] != "finish" and file_exists("lock")) $errors[] = "<strong>esoTalk is already installed.</strong><br/><small>To reinstall esoTalk, you must remove <strong>install/lock</strong>.</small>";
	
	// Check the PHP version
	if (!version_compare(PHP_VERSION, "4.3.0", ">=")) $errors[] = "Your server must have <strong>PHP 4.3.0 or greater</strong> installed to run esoTalk.<br/><small>Please upgrade your PHP installation (preferably to version 5) or request that your host or administrator upgrade the server.</small>";
	
	// Check for the MySQL extension
	if (!extension_loaded("mysql")) $errors[] = "You must have <strong>MySQL 4 or greater</strong> installed and the <a href='http://php.net/manual/en/mysql.installation.php' target='_blank'>MySQL extension enabled in PHP</a>.<br/><small>Please install/upgrade both of these requirements or request that your host or administrator install them.</small>";
	
	// Check for file permissions
	$fileErrors = array();
	$filesToCheck = array("", "avatars/", "plugins/", "skins/", "config/", "install/", "upgrade/");
	foreach ($filesToCheck as $file) {
		if (!is_writable("../$file") and !@chmod("../$file", 0777)) {
			$realPath = realpath("../$file");
			$fileErrors[] = $file ? $file : substr($realPath, strrpos($realPath, "/") + 1) . "/";
		}
	}
	if (count($fileErrors)) $errors[] = "esoTalk cannot write to the following files/folders: <strong>" . implode("</strong>, <strong>", $fileErrors) . "</strong>.<br/><small>To resolve this, you must navigate to these files/folders in your FTP client and <strong>chmod</strong> them to <strong>777</strong>.</small>";
	
	// Check for PCRE UTF-8 support
	if (!@preg_match("//u", "")) $errors[] = "<strong>PCRE UTF-8 support</strong> is not enabled.<br/><small>Please ensure that your PHP installation has PCRE UTF-8 support compiled into it.</small>";
	
	// Check for the gd extension
	if (!extension_loaded("gd") and !extension_loaded("gd2")) $errors[] = "The <strong>GD extension</strong> is not enabled.<br/><small>This is required to save avatars and generate captcha images. Get your host or administrator to install/enable it.</small>";
	
	if (count($errors)) return $errors;
}

// Perform checks that will throw a warning
function warningChecks()
{
	$errors = array();
	
	// Register globals
	if (ini_get("register_globals")) $errors[] = "PHP's <strong>register_globals</strong> setting is enabled.<br/><small>While esoTalk can run with this setting on, it is recommended that it be turned off to increase security and to prevent esoTalk from having problems.</small>";
	
	// Check that we can open remote urls as files
	if (!ini_get("allow_url_fopen")) $errors[] = "The PHP setting <strong>allow_url_fopen</strong> is not on.<br/><small>Without this, avatars cannot be uploaded directly from remote websites.</small>";
	
	// Check for safe mode
	if (ini_get("safe_mode")) $errors[] = "<strong>Safe mode</strong> is enabled.<br/><small>This could potentially cause problems with esoTalk, but you can still proceed if you cannot turn it off.</small>";
	
	if (count($errors)) return $errors;
}

}

?>