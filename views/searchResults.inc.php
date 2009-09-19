<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Search results

if (!defined("IN_ESOTALK")) exit;
?>
<table cellspacing='0' cellpadding='2' class='c'>
<thead><tr><th>&nbsp;</th><th><?php echo $language["Conversation"]; ?></th><th class='posts'><?php echo $language["Posts"]; ?></th><th><?php echo $language["Started by"]; ?></th><th><?php echo $language["Last reply"]; ?></th></tr>
<tr id='newResults' style='display:none'><td colspan='5'><?php echo $this->esoTalk->htmlMessage("newSearchResults"); ?></td></tr>
</thead>
<tbody id='conversations'>

<?php foreach ($this->conversations as $conversation): ?>
<tr id='c<?php echo $conversation["id"]; ?>'<?php if ($conversation["starred"]): ?> class='starred'<?php endif; ?>>
<td class='star'><?php echo $this->esoTalk->htmlStar($conversation["id"], $conversation["starred"]); ?></td>
<td>
<?php if (!empty($config["showAvatarThumbnails"])) echo "<div class='avatar'><img src='" . $this->esoTalk->getAvatar($conversation["startMemberId"], $conversation["avatarFormat"], "thumb") . "' alt='' class='thumb'/></div> "; ?>
<?php
$labels = explode(",", $conversation["labels"]); $i = 0; $labelsHtml = "";
foreach ($this->esoTalk->labels as $k => $v) {
	if (@$labels[$i]) $labelsHtml .= "<span class='label $k'>{$language["labels"][$k]}</span> ";
	$i++;
}
if ($labelsHtml) echo "<span class='labels'>$labelsHtml</span>";
?><strong<?php if ($this->esoTalk->user and !$conversation["unread"]): ?> class='read'<?php endif; ?>><a href='<?php echo makeLink($conversation["id"], $conversation["slug"]); ?>'><?php echo $conversation["title"]; ?></a></strong> <?php if ($this->esoTalk->user["name"] and $conversation["unread"]): ?><small><a href='<?php echo makeLink($conversation["id"], $conversation["slug"], "?start=unread"); ?>'><?php echo $language["Jump to unread"]; ?></a></small><?php else: ?><small><a href='<?php echo makeLink($conversation["id"], $conversation["slug"], "?start=last"); ?>'><?php echo $language["Jump to last"]; ?></a></small><?php endif; ?><br/><small><?php echo $conversation["tags"]; ?></small></td>
<td class='posts p<?php echo ($conversation["posts"] > 50) ? "1" : (($conversation["posts"] > 10) ? "2" : "3"); ?>'><?php echo $conversation["posts"]; ?></td>
<td class='author'><a href='<?php echo makeLink("profile", $conversation["startMemberId"]); ?>'><?php echo $conversation["startMember"]; ?></a><br/><small><?php echo relativeTime($conversation["startTime"]); ?></small></td>
<td class='lastPost'><span class='lastPostMember'><?php if ($conversation["posts"] > 1): ?><a href='<?php echo makeLink("profile", $conversation["lastPostMemberId"]); ?>'><?php echo $conversation["lastPostMember"]; ?></a><?php endif; ?></span><br/><small class='lastPostTime'><?php if ($conversation["posts"] > 1): ?><?php echo relativeTime($conversation["lastPostTime"]); ?><?php endif; ?></small></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php if (!$this->numberOfConversations): ?>
<?php echo $this->esoTalk->htmlMessage("noSearchResults"); ?>
<?php elseif ($this->limit == $config["results"] + 1 and $this->numberOfConversations > $config["results"]): ?>
<div id='more'>
<?php echo $this->esoTalk->htmlMessage("viewMore", array(makeLink("search", urlencode(@$_SESSION["search"] . (@$_SESSION["search"] ? " + " : "") . "more results")))); ?>
</div>
<?php endif; ?>