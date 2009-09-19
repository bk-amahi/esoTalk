<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Join controller: Handles the 'Join this forum!' page. Defines form data, validates it, and adds the member to the database.

if (!defined("IN_ESOTALK")) exit;

class join extends Controller {

var $view = "join.view.php";
var $reservedNames = array("guest", "anonymous", "member", "members", "moderator", "moderators", "administrator", "administrators", "admin", "suspended", "esotalk", "name", "password", "everyone", "myself");

// Initialize; define the form contents, and check to see if form data was submitted
function init()
{
	// If we're already logged in, go to 'My settings'
	if ($this->esoTalk->user) redirect("settings");
	
	global $language, $config;
	$this->title = $language["Join this forum"];
	
	$this->esoTalk->addToHead("<meta name='robots' content='noindex, noarchive'/>");

	// Do we want to validate a member with a hash in the url?
	if (isset($_GET["q2"])) {
		if ($_GET["q2"] == "sendVerification") {
			$memberId = (int)@$_GET["q3"];
			if (list($email, $name, $password) = $this->esoTalk->db->fetchRow("SELECT email, name, password FROM {$config["tablePrefix"]}members WHERE memberId=$memberId AND account='Unvalidated'")) $this->sendVerificationEmail($email, $name, $memberId . $password);
			$this->esoTalk->message("verifyEmail", false);
			redirect("");
			return;
		}
		$this->validateMember($_GET["q2"]);
		return;
	}
		
	// The form contents
	$this->form = array(
		
		"accountInformation" => array(
			"legend" => $language["Account information"],
			100 => array(
				"id" => "name",
				"html" => @"<label>{$language["Username"]}</label> <input id='name' name='join[name]' type='text' class='text' value='{$_POST["join"]["name"]}' maxlength='31' tabindex='100'/>",
				"validate" => array($this, "validateName"),
				"required" => true,
				"databaseField" => "name",
				"ajax" => true
			),
			200 => array(
				"id" => "email",
				"html" => @"<label>{$language["Email"]}</label> <input id='email' name='join[email]' type='text' class='text' value='{$_POST["join"]["email"]}' maxlength='63' tabindex='200'/>",
				"validate" => "validateEmail",
				"required" => true,
				"databaseField" => "email",
				"message" => "emailInfo",
				"ajax" => true
			),
			300 => array(
				"id" => "password",
				"html" => @"<label>{$language["Password"]}</label> <input id='password' name='join[password]' type='password' class='text' value='{$_POST["join"]["password"]}' tabindex='300'/>",
				"validate" => "validatePassword",
				"required" => true,
				"databaseField" => "password",
				"message" => "passwordInfo",
				"ajax" => true
			),
			400 => array(
				"id" => "confirm",
				"html" => @"<label>{$language["Confirm password"]}</label> <input id='confirm' name='join[confirm]' type='password' class='text' value='{$_POST["join"]["confirm"]}' tabindex='400'/>",
				"required" => true,
				"validate" => array($this, "validateConfirmPassword"),
				"ajax" => true
			)
		)
	);
	
	$this->callHook("init");
	
	// Make an array of just fields (without the fieldsets) for easy access
	$this->fields = array();
	foreach ($this->form as $k => $fieldset) {
		foreach ($fieldset as $j => $field) {
			if (!is_array($field)) continue;
			$this->fields[$field["id"]] =& $this->form[$k][$j];
		}
	}
	
	// If there's input, validate the form and add the member into the database
	if (isset($_POST["join"]) and $this->addMember()) {
		$this->esoTalk->message("verifyEmail", false);
		redirect("");
	}
}

// Validate the form and add the member to the database
function addMember()
{
	global $config;
	
	$validationError = false;
	// Loop through the fields
	foreach ($this->fields as $k => $field) {
		if (!is_array($field)) continue;
		$this->fields[$k]["input"] = @$_POST["join"][$field["id"]];
		// If this field is required -or- data has been entered, validate it
		if ((@$field["required"] or $this->fields[$k]["input"]) and ($msg = @call_user_func_array($field["validate"], array(&$this->fields[$k]["input"])))) {
			$validationError = true;
			$this->fields[$k]["message"] = $msg;
			$this->fields[$k]["error"] = true;
		} else $this->fields[$k]["success"] = true;
	}
	
	$this->callHook("validateForm", array(&$validationError));
	if ($validationError) return false;
	
	// Construct the insert query
	$insertData = array();
	foreach ($this->fields as $field) {
		if (!is_array($field)) continue;
		if (@$field["databaseField"]) $insertData[$field["databaseField"]] = "'{$field["input"]}'";
	}
	$insertData["color"] = "FLOOR(1 + (RAND() * {$this->esoTalk->skin->numberOfColors}))";
	$insertData["language"] = "'" . addslashes($config["language"]) . "'";
	$insertData["avatarAlignment"] = "'{$_SESSION["avatarAlignment"]}'";
	
	$this->callHook("beforeAddMember", array(&$insertData));
	
	$insertQuery = $this->esoTalk->db->constructInsertQuery("members", $insertData);
	$insertQuery = "REPLACE" . substr($insertQuery, 6);
	
	// Add the member to the database
	$this->esoTalk->db->query($insertQuery);
	$memberId = $this->esoTalk->db->lastInsertId();
	
	$this->callHook("afterAddMember");
	
	// Email the member, asking them to validate
	$this->sendVerificationEmail($_POST["join"]["email"], $_POST["join"]["name"], $memberId . md5($config["salt"] . $_POST["join"]["password"]));
	
	return true;
}

function sendVerificationEmail($email, $name, $verifyHash)
{
	global $language, $config;
	sendEmail($email, sprintf($language["emails"]["join"]["subject"], $name), sprintf($language["emails"]["join"]["body"], $name, $config["forumTitle"], $config["baseURL"] . makeLink("join", $verifyHash)));
}


// Validate a member
function validateMember($hash)
{
	global $config;
	$memberId = (int)substr($hash, 0, strlen($hash) - 32);
	$password = addslashes(substr($hash, -32));
	if ($name = @$this->esoTalk->db->result($this->esoTalk->db->query("SELECT name FROM {$config["tablePrefix"]}members WHERE memberId=$memberId AND password='$password' AND account='Unvalidated'"), 0)) {
		$this->esoTalk->db->query("UPDATE {$config["tablePrefix"]}members SET account='Member' WHERE memberId=$memberId");
		$this->esoTalk->login($name, false, $password);
		$this->esoTalk->message("accountValidated", false);
	}
	redirect("");
}

// Ajax; validate a form field
function ajax()
{
	switch ($_POST["action"]) {
		case "validate":
			if ($msg = @call_user_func($this->fields[$_POST["field"]]["validate"], $_POST["value"]))
				return array("validated" => false, "message" => $this->esoTalk->htmlMessage($msg));
			else return array("validated" => true, "message" => "");
			break;
	}
	
	$return = null;
	$this->callHook("ajax", array(&$return));
	return $return;
}

// Add an element to the page's form.
function addToForm($fieldset, $field, $position = false)
{
	return addToArray($this->form[$fieldset], $field, $position);
}

// Add a fieldset to the form.
function addFieldset($fieldset, $legend, $position = false)
{
	return addToArrayString($this->form, $fieldset, array("legend" => $legend), $position);
}

// Validate the confirm password field (requires $_POST["join"]["password"] to be set)
function validateConfirmPassword($password) {if ($password != ($this->esoTalk->ajax ? $_POST["password"] : $_POST["join"]["password"])) return "passwordsDontMatch";}

// Validate the name field
function validateName(&$name)
{
	$name = substr($name, 0, 31);
	if (in_array(strtolower($name), $this->reservedNames)) return "nameTaken";
	if (!strlen($name)) return "nameEmpty";
	if (preg_match("/[" . preg_quote("!/%+-", "/") . "]/", $name)) return "invalidCharacters";
	global $config;
	if (@$this->esoTalk->db->result($this->esoTalk->db->query("SELECT 1 FROM {$config["tablePrefix"]}members WHERE name='" . addslashes($name) . "' AND account!='Unvalidated'"), 0))
		return "nameTaken";
}
	
}

?>