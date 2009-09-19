<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Online controller - fetches a list of members currently online, ready to be displayed in the view.

if (!defined("IN_ESOTALK")) exit;

class online extends Controller {
	
var $view = "online.view.php";

function init()
{
	global $language, $config;
	$this->title = $language["Who's online"];
	
	// Get the members online table
	$this->online = $this->esoTalk->db->query("SELECT memberId, name, avatarFormat, IF(color>{$this->esoTalk->skin->numberOfColors},{$this->esoTalk->skin->numberOfColors},color), account, lastSeen, lastAction FROM {$config["tablePrefix"]}members WHERE UNIX_TIMESTAMP()-{$config["userOnlineExpire"]}<lastSeen ORDER BY lastSeen DESC");
	$this->numberOnline = $this->esoTalk->db->numRows($this->online);
	
	$this->esoTalk->addToHead("<meta name='robots' content='noindex, noarchive'/>");
}
	
}

?>