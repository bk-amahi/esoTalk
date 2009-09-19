<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Classes: This file contains all base classes which are extended throughout esoTalk...

// Hookable - a class in which code can be hooked on to.
// Controller (extends Hookable) - a class which defines a view and handles input.
// Plugin (extends Hookable) - a class which defines a plugin.
// Skin - defines a skin.

if (!defined("IN_ESOTALK")) exit;

// Hookable - a class in which code can be hooked on to.
// Extend this class and then use $this->callHook("uniqueMarker") in the class code
// to call any code which has been hooked via $classInstance->addHook("uniqueMarker", "function").
class Hookable {

var $hookedFunctions = array();

// Run all collective hooked functions for the specified point
function callHook($marker, $parameters = array(), $return = false)
{
	if (isset($this->hookedFunctions[$marker]) and count($this->hookedFunctions[$marker])) {
		// Can't use array_unshift because call-time pass-by-reference has been deprecated
		$parameters = array_merge(array(&$this), $parameters);
		foreach ($this->hookedFunctions[$marker] as $function) {
			// If this hook requires a return value and the function we're running returns something, return that.
			if (($returned = call_user_func_array($function, $parameters)) and $return) return $returned;
		}
	}
}

// Hook a function.
function addHook($hook, $function)
{
	$this->hookedFunctions[$hook][] = $function;
}

}


// Controller (extends Hookable) - a class which defines a view and handles input.
// Extend this class and then use $esoTalk->registerController() to register your new controller.
class Controller extends Hookable {

var $action;
var $view;
var $title;
var $esoTalk;

function init() {}
function ajax() {}

// Render the page according to the controller's $view.
function render()
{
	global $language, $messages, $config;
	include $this->esoTalk->skin->getView($this->view);
}

}


// Plugin (extends Hookable) - a class which defines a plugin.
// Extend this class to make a plugin. See the plugin documentation for more information.
class Plugin extends Hookable {

var $id;
var $name;
var $version;
var $author;
var $description;

// Constructor - include the config file / write the default config if it doesn't exist
function Plugin()
{
	if (!empty($this->defaultConfig)) {
		global $config;
		$filename = sanitizeFileName($this->id);
		if (!file_exists("config/$filename.php")) writeConfigFile("config/$filename.php", '$config["' . escapeDoubleQuotes($this->id) . '"]', $this->defaultConfig);
		include "config/$filename.php";
	}
}

function init()
{
	// Compare the version of the code ($this->version) to the installed one (config/versions.php)
	// If it's different, run the upgrade() function, and write the new version to config/versions.php
	global $versions;
	if (!isset($versions[$this->id]) or $versions[$this->id] != $this->version) {
		$this->upgrade(@$versions[$this->id]);
		$versions[$this->id] = $this->version;
		writeConfigFile("config/versions.php", '$versions', $versions);	
	}
}

function settings() {}
function upgrade() {}

}


// Skin - defines a skin.
// Extend this class to make a skin.
class Skin {

var $name;
var $version;
var $author;
var $views;

function init() {}

// Generate button HTML.
function button($attributes)
{
	$attr = " type='submit'";
	foreach ($attributes as $k => $v) $attr .= " $k='$v'";
	return "<input$attr/>";
}

// Register a custom view.
// Whenever a controller attempts to include $view, this new $file associated with $view will be included instead.
function registerView($view, $file)
{
	$this->views[$view] = $file;
}

function getView($view)
{
	return empty($this->views[$view]) ? "views/$view" : $this->views[$view];
}

}

?>