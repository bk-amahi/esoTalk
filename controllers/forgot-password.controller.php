<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Forgot password controller: Controls everything for the forgot password view: sending password emails, changing passwords

if (!defined("IN_ESOTALK")) exit;

class forgotpassword extends Controller {

var $view = "forgotPassword.view.php";
var $title = "";
var $errors = array();
var $setPassword = false;

// Work out what step we're doing, and do it!
function init()
{
	if ($this->esoTalk->user) redirect("");
	
	global $language, $messages, $config;
	$this->title = $language["Forgot your password"];
	$this->esoTalk->addToHead("<meta name='robots' content='noindex, noarchive'/>");
	
	// If we're on the second step (they've clicked the link in their email)
	if ($hash = @$_GET["q2"]) {
		
		// Get the user with this recover password hash
		$result = $this->esoTalk->db->query("SELECT memberId FROM {$config["tablePrefix"]}members WHERE resetPassword='$hash'");
		if (!$this->esoTalk->db->numRows($result)) redirect("forgotPassword");
		list($memberId) = $this->esoTalk->db->fetchRow($result);
		
		$this->setPassword = true;
		
		// Validate the form if it was submitted
		if (isset($_POST["changePassword"])) {
			$password = @$_POST["password"];
			$confirm = @$_POST["confirm"];
			if ($error = validatePassword(@$_POST["password"])) $this->errors["password"] = $error;
			if ($password != $confirm) $this->errors["confirm"] = "passwordsDontMatch";
			
			if (!count($this->errors)) {
				$passwordHash = md5($config["salt"] . $password);
				$this->esoTalk->db->query("UPDATE {$config["tablePrefix"]}members SET resetPassword=NULL, password='$passwordHash' WHERE memberId=$memberId");
				$this->esoTalk->message("passwordChanged", false);
				redirect("");
			}
		}
	}
	
	// If they've submitted their email for a password link, email them!
	if (isset($_POST["email"])) {
		// Find the member with this email
		$result = $this->esoTalk->db->query("SELECT memberId, name, email FROM {$config["tablePrefix"]}members WHERE email='{$_POST["email"]}'");
		if (!$this->esoTalk->db->numRows($result)) {
			$this->esoTalk->message("emailDoesntExist");
			return;
		}
		list($memberId, $name, $email) = $this->esoTalk->db->fetchRow($result);
		
		// Set a special 'forgot password' hash
		$hash = md5(rand());
		$this->esoTalk->db->query("UPDATE {$config["tablePrefix"]}members SET resetPassword='$hash' WHERE memberId=$memberId");
		
		// Send the email
		if (sendEmail($email, sprintf($language["emails"]["forgotPassword"]["subject"], $name), sprintf($language["emails"]["forgotPassword"]["body"], $name, $config["forumTitle"], $config["baseURL"] . makeLink("forgot-password", $hash)))) {
			$this->esoTalk->message("passwordEmailSent", false);
			redirect("");
		}
	}
}


}