<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Profile controller

if (!defined("IN_ESOTALK")) exit;

class profile extends Controller {

var $view = "profile.view.php";
var $member = array();
var $sections = array();

function init()
{
	if ($this->esoTalk->ajax) return;
	
	if (!empty($_GET["q2"])) $memberId = (int)$_GET["q2"];
	elseif ($this->esoTalk->user) $memberId = $this->esoTalk->user["memberId"];
	else $memberId = false;
	if (!$memberId or !($this->member = $this->getMember($memberId))) {
		$this->esoTalk->message("memberDoesntExist", false);
		redirect("");
	}
	
	$this->title = $this->member["name"];
	
	$this->callHook("init");
}

function ajax()
{
	$return = null;
	$this->callHook("ajax", array(&$return));
	return $return;
}

function addSection($section, $position = false)
{
	addToArray($this->sections, $section, $position);
}


function getMember($memberId)
{
	if (empty($memberId)) return false;
	
	global $config;

	// Construct the query components
	$select = array("m.memberId AS memberId", "m.name AS name", "IF(m.color>{$this->esoTalk->skin->numberOfColors},{$this->esoTalk->skin->numberOfColors},m.color) AS color", "m.account AS account", "m.lastSeen AS lastSeen", "m.lastAction AS lastAction", "m.avatarFormat AS avatarFormat",
		"(SELECT MIN(time) FROM {$config["tablePrefix"]}posts p WHERE p.memberId=m.memberId) AS firstPosted",
		"(SELECT COUNT(*) FROM {$config["tablePrefix"]}conversations c WHERE c.startMember=m.memberId) AS conversationsStarted",
		"(SELECT COUNT(DISTINCT conversationId) FROM {$config["tablePrefix"]}posts p WHERE p.memberId=m.memberId) AS conversationsParticipated",
		"(SELECT COUNT(*) FROM {$config["tablePrefix"]}posts p WHERE p.memberId=m.memberId) AS postCount"
	);
	$from = array("{$config["tablePrefix"]}members m");
	$where = array("m.memberId=$memberId");

	// Build the query
	$components = array("select" => $select, "from" => $from, "where" => $where);	
	$query = $this->esoTalk->db->constructSelectQuery($components);

	// Run the query.
	$result = $this->esoTalk->db->query($query);
	if (!$this->esoTalk->db->numRows($result)) return false; // How disappointing.

	// Get all the details from the query into an array.
	$member = $this->esoTalk->db->fetchAssoc($result);
	
	return $member;
}

}

?>