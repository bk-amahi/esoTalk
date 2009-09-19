<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// RSS Controller

if (!defined("IN_ESOTALK")) exit;

class feed extends Controller {

var $items = array();
var $pubDate = "";
var $title = "";
var $description = "";
var $link = "";

function init()
{
	global $language, $config, $messages;
	
	$this->esoTalk->view = "feed.view.php";
	
	header("Content-type: text/xml; charset={$language["charset"]}");
	
	// Work out what type of feed we're doing:
	// conversation/[id] -> fetch the posts in conversation [id]
	// default -> fetch the most recent posts over the whole forum
	
	switch (@$_GET["q2"]) {
	
		case "conversation":
		
			// Get the conversation.
			$conversationId = (int)$_GET["q3"];
			if (!$conversationId or !($conversation = $this->esoTalk->db->fetchAssoc("SELECT c.conversationId AS id, c.title AS title, c.slug AS slug, c.private AS private, c.posts AS posts, c.startMember AS startMember, c.lastActionTime AS lastActionTime, GROUP_CONCAT(t.tag ORDER BY t.tag ASC SEPARATOR ', ') AS tags FROM {$config["tablePrefix"]}conversations c LEFT JOIN {$config["tablePrefix"]}tags t USING (conversationId) WHERE c.conversationId=$conversationId GROUP BY c.conversationId")))
				$this->esoTalk->fatalError($messages["cannotViewConversation"]["message"]);
			
			// Do we need authentication?
			if ($conversation["private"] or $conversation["posts"] == 0) {
				
				// Try to login with provided credentials.
				if (isset($_SERVER["PHP_AUTH_USER"])) $this->esoTalk->login($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]);
				
				// Still not logged in? Ask them again!
				if (!$this->esoTalk->user) {
					header('WWW-Authenticate: Basic realm="esoTalk RSS feed"');
				    header('HTTP/1.0 401 Unauthorized');
					$this->esoTalk->fatalError($messages["cannotViewConversation"]["message"]);
				}
				
				// We're logged in now. So, is this member allowed in this conversation?
				if (!($conversation["startMember"] == $this->esoTalk->user["memberId"]
					or ($conversation["posts"] > 0 and (!$conversation["private"] or $this->esoTalk->db->result("SELECT allowed FROM {$config["tablePrefix"]}status WHERE conversationId=$conversationId AND (memberId={$this->esoTalk->user["memberId"]} OR memberId='{$this->esoTalk->user["account"]}')", 0))))) {
					// Nuh-uh. Get OUT!!!
					$this->esoTalk->fatalError($messages["cannotViewConversation"]["message"]);
				}
			}
			
			// Past this point, the user is allowed to view the conversation.
			// Set the title, link, description, etc.
			$this->title = "{$conversation["title"]} - {$config["forumTitle"]}";
			$this->link = $config["baseURL"] . makeLink($conversation["id"], $conversation["slug"]);
			$this->description = $conversation["tags"];
			$this->pubDate = date("D, d M Y H:i:s O", $conversation["lastActionTime"]);
			
			// Get posts
			$result = $this->esoTalk->db->query("SELECT postId, name, content, time FROM {$config["tablePrefix"]}posts INNER JOIN {$config["tablePrefix"]}members USING (memberId) WHERE conversationId={$conversation["id"]} AND p.deleteMember IS NULL ORDER BY time DESC LIMIT 20");
			while (list($id, $member, $content, $time) = $this->esoTalk->db->fetchRow($result)) {
				$this->items[] = array(
					"title" => $member,
					"description" => sanitize($this->absoluteURLs($content)),
					"link" => $config["baseURL"] . makeLink("post", $id),
					"date" => date("D, d M Y H:i:s O", $time)
				);
			}
		
			break;
		
		default:
			// It doesn't matter whether we're logged in or not - just get the posts!
			$result = $this->esoTalk->db->query("SELECT p.postId, c.title, m.name, p.content, p.time FROM {$config["tablePrefix"]}posts p LEFT JOIN {$config["tablePrefix"]}conversations c USING (conversationId) INNER JOIN {$config["tablePrefix"]}members m ON (m.memberId=p.memberId) WHERE c.private=0 AND c.posts>0 AND p.deleteMember IS NULL ORDER BY p.time DESC LIMIT 20");
			while (list($postId, $title, $member, $content, $time) = $this->esoTalk->db->fetchRow($result)) {
				$this->items[] = array(
					"title" => "$member - $title",
					"description" => sanitize($this->absoluteURLs($content)),
					"link" => $config["baseURL"] . makeLink("post", $postId),
					"date" => date("D, d M Y H:i:s O", $time)
				);
			}
			
			// Set the title, link, description, etc.
			$this->title = "{$language["Recent posts"]} - {$config["forumTitle"]}";
			$this->link = $config["baseURL"];
			$this->pubDate = !empty($this->items[0]) ? $this->items[0]["date"] : "";
	}
}

// Convert relative URLs to absolute URLs
function absoluteURLs($text)
{
	global $config;
	$text = preg_replace("/<a([^>]*) href='(?!http|ftp|mailto)([^']*)'/i", "<a$1 href='{$config["baseURL"]}$2'", $text);
	$text = preg_replace("/<img([^>]*) src='(?!http|ftp|mailto)([^']*)'/i", "<img$1 src='{$config["baseURL"]}$2'", $text);
	return $text;
}

}

?>