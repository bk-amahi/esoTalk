<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Conversation view - displays conversation header, pagination, posts, and reply box.

if (!defined("IN_ESOTALK")) exit;
?>
<?php
// If we're trying to start a new conversation but we can't, display an error message.
if (!$this->conversation["id"] and ($error = $this->canStartConversation()) !== true): echo $this->esoTalk->htmlMessage($error);
else:

// If we're starting a new conversation, we'll need a big form around the whole page.
if (!$this->conversation["id"]): ?><form action='<?php echo curLink(); ?>' method='post' enctype='multipart/form-data'><?php endif; ?>

<script type='text/javascript'>
// <![CDATA[
<?php if ($this->conversation["id"]): ?>
Conversation.id = <?php echo $this->conversation["id"]; ?>;
Conversation.postCount = <?php echo $this->conversation["postCount"]; ?>;
Conversation.startFrom = <?php echo $this->startFrom; ?>;
Conversation.lastActionTime = <?php echo $this->conversation["lastActionTime"]; ?>;
Conversation.lastRead = <?php echo ($this->esoTalk->user and $this->conversation["id"]) ? min($this->conversation["postCount"], $this->conversation["lastRead"]) : $this->conversation["postCount"]; ?>;
Conversation.autoReloadInterval = <?php
// Find the nearest power of 2 (4, 8, 16, 32, 64, ..., 512) to the square root of the most recent action.
// ex. if the most recent action is 81 seconds ago, the autoReloadInterval = closest power of 2 to sqrt(81) = 8.
echo max(4, min(pow(2, round(log(sqrt(time() - $this->conversation["lastActionTime"]), 2))), $config["autoReloadIntervalLimit"]));
?>;
<?php endif; ?>
Conversation.postsPerPage = <?php echo $config["postsPerPage"]; ?>;
// ]]>
</script>

<div id='cHdr'>

<div id='cInfo'>
<?php if ($this->conversation["id"]): ?><form action='<?php echo curLink(); ?>' method='post'><?php endif; ?>

<?php // Title (and star) ?>
<h2>
<?php echo $this->esoTalk->htmlStar($this->conversation["id"], $this->conversation["starred"]); ?> 
<?php if ($this->canEditTitle()): ?>
<input id='cTitle' name='cTitle' type='text' class='text<?php if ($this->conversation["id"]): ?> editable<?php endif; ?>' value='<?php echo $this->conversation["title"]; ?>' tabindex='10' maxlength='63'/>
<?php else: ?><span id='cTitle'><?php echo @$this->conversation["title"]; ?></span><?php endif; ?>
</h2>

<?php // Tags ?>
<dl>
<dt><?php echo $language["Tags"]; ?></dt>
<dd><?php if ($this->canEditTags()): ?>
<input id='cTags' name='cTags' type='text' class='text<?php if ($this->conversation["id"]): ?> editable<?php endif; ?>' value='<?php echo $this->conversation["tags"]; ?>' tabindex='20' maxlength='255'/>
<?php if ($this->conversation["id"]): ?>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>
<?php echo $this->esoTalk->skin->button(array("value" => $language["Save changes"], "id" => "saveTitleTags", "name" => "saveTitleTags")); ?>
<?php endif; ?>
<?php else: ?><span id='cTags'><?php echo $this->linkTags(@$this->conversation["tags"]); ?>&nbsp;</span><?php endif; ?>
<?php if (!$this->conversation["id"] and (empty($_POST["cTitle"]) or empty($_POST["cTags"]))): ?>
<script type='text/javascript'>
<?php if (empty($_POST["cTitle"])): ?>makePlaceholder($("cTitle"), $("cTitle").value);<?php endif; ?>
<?php if (empty($_POST["cTags"])): ?>makePlaceholder($("cTags"), $("cTags").value);<?php endif; ?>
</script>
<?php endif; ?>
</dd>

<?php // Labels ?>
<dt><?php echo $language["Labels"]; ?></dt>
<dd id='cLabels'><?php
foreach ($this->esoTalk->labels as $k => $v) echo "<span class='label $k'" . (!in_array($k, $this->conversation["labels"]) ? " style='display:none'" : "") . ">{$language["labels"][$k]}</span> "; ?></dd>
</dl>

<?php if ($this->conversation["id"]): ?></form><?php endif; ?>
</div>

<?php // Members allowed list ?>
<dl id='allowed'>
<dt><?php echo $language["Members allowed to view this conversation"]; ?></dt>
<dd>
<span id='allowedList'><?php echo $this->htmlMembersAllowedList($this->conversation["membersAllowed"]); ?></span>
<?php if ($this->canEditMembersAllowed()): ?>
<?php if ($this->conversation["id"]): ?><form action='<?php echo curLink(); ?>' method='post'><div>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/><?php endif; ?>
<input id='addMember' name='member' type='text' class='text' tabindex='60' maxlength='31'/>
<?php echo $this->esoTalk->skin->button(array("id" => "addMemberSubmit", "name" => "addMember", "value" => $language["Add member"])); ?>
<?php if ($this->conversation["id"]): ?></div></form><?php endif; ?>
<?php endif; ?>
</dd>
</dl>

</div>

<?php $this->callHook("afterRenderHeader"); ?>

<div id='cContent'>

<div id='cBody'<?php if (empty($this->conversation["posts"])) echo " style='display:none'"; ?>>

<?php
// Generate the pagination bar!
// Work out pagination percentages.
if ($this->conversation["postCount"] > 0) {
	$curHandleWidth = max($config["postsPerPage"] / $this->conversation["postCount"], 0.2) * 100;
	if ($this->conversation["postCount"] <= $config["postsPerPage"]) $percentPerPost = 100 / $this->conversation["postCount"];
	else $percentPerPost = $this->conversation["postCount"] ? (100 - $curHandleWidth) / ($this->conversation["postCount"] - $config["postsPerPage"]) : 0;
	$showingPercent = max($percentPerPost * min($this->conversation["postCount"] - $this->startFrom, $config["postsPerPage"]), 20);
	$leftPercent = max(0, min(100 - $showingPercent, $this->startFrom * $percentPerPost));
	$rightPercent = 100 - ($showingPercent + $leftPercent);
	$unreadLeft = ($this->esoTalk->user and $this->conversation["id"]) ? min($this->conversation["postCount"], $this->conversation["lastRead"]) * (100 / $this->conversation["postCount"]) : 100;
	$unreadWidth = max(0, 100 - $unreadLeft);
} else $percentPerPost = $unreadLeft = $showingPercent = $leftPercent = $rightPercent = $unreadWidth = 0;

// Generate the buttons.
$previousButton = $this->startFrom <= 0
	? "<a class='previous disabled'>{$language["Previous"]}</a>"
	: "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?start=" . max(0, $this->startFrom - $config["postsPerPage"])) . "' class='previous'>{$language["Previous"]}</a>";
$firstButton = "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"]) . "' class='first'>{$language["First"]}</a>";
$lastButton = "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?start=last") . "' class='last'>{$language["Last"]}</a>";
$nextButton = $this->startFrom + min($config["postsPerPage"], $this->conversation["postCount"] - $this->startFrom) >= $this->conversation["postCount"]
	? "<a class='next disabled'>{$language["Next"]}</a>"
	: "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?start=" . min($this->conversation["postCount"], $this->startFrom + $config["postsPerPage"])) . "' class='next'>{$language["Next"]}</a>";

// Generate the viewing text: "11-30 of 41 posts".
$viewing = sprintf($language["viewingPosts"], "<span class='pgFrom'>" . ($this->startFrom + 1) . "</span>", "<span class='pgTo'>" . ($this->startFrom + min($config["postsPerPage"], $this->conversation["postCount"] - $this->startFrom)) . "</span>", "<span class='pgCount'>{$this->conversation["postCount"]}</span>");

// Put everything together.
$paginationHtml = "<ul id='pagination' class='pg'>
<li class='left'>$previousButton $firstButton</li>
<li class='middle'>
<div style='width:$showingPercent%; margin-left:$leftPercent%; margin-right:$rightPercent%;' class='viewing' title='" . strip_tags($viewing) . "'><div>$viewing</div></div>
<a href='" .  makeLink($this->conversation["id"], $this->conversation["slug"], "?start=unread") . "' style='width:$unreadWidth%; margin-left:$unreadLeft%;' class='unread'>{$language["unread"]}</a>
</li>
<li class='right'>$lastButton $nextButton</li>
</ul>";

// Output the first pagination bar.
$this->callHook("beforeRenderPagination", array(&$paginationHtml));
echo $paginationHtml;
?>

<div id='cPosts'>

<script type='text/javascript'>
// <![CDATA[
<?php
$memberLink = "<a href='" . makeLink("profile", "%d") . "'>%s</a>";
$editedBy = "({$language["edited by"]} %s %s)";
$deletedBy = "({$language["deleted by"]})";
$quoteLink = "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?quotePost=%s", $this->startFrom ? "&start=$this->startFrom" : "") . "' onclick='Conversation.quotePost(%s);return false'>{$language["quote"]}</a>";
$editLink = "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?editPost=%s", $this->startFrom ? "&start=$this->startFrom" : "", "#p%s") . "' onclick='Conversation.editPost(%s);return false'>{$language["edit"]}</a>";
$deleteLink = "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?deletePost=%s", $this->startFrom ? "&start=$this->startFrom" : "", "&token=%t") . "' onclick='Conversation.deletePost(%s);return false'>{$language["delete"]}</a>";
$restoreLink = "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?restorePost=%s", $this->startFrom ? "&start=$this->startFrom" : "", "&token=%t") . "' onclick='Conversation.restorePost(%s);return false'>{$language["restore"]}</a>";
$showDeletedLink = "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?showDeletedPost=%s", $this->startFrom ? "&start=$this->startFrom" : "") . "' onclick='Conversation.showDeletedPost(%s);return false'>{$language["show"]}</a>";
$hideDeletedLink = "<a href='" . makeLink($this->conversation["id"], $this->conversation["slug"], "?start=$this->startFrom") . "' onclick='Conversation.hideDeletedPost(%s);return false'>{$language["hide"]}</a>";
$lastAction = "(<abbr title='%s'>{$language["online"]}</abbr>)";
$permalink = makeLink("post", "%s");
?>
function makeMemberLink(memberId, member) {return "<?php echo $memberLink; ?>".replace("%d", memberId).replace("%s", member);}
function makeEditedBy(member, time) {return "<?php echo $editedBy; ?>".replace("%s", member).replace("%s", time);}
function makeDeletedBy(member) {return "<?php echo $deletedBy; ?>".replace("%s", member);}
function makeQuoteLink(postId) {return "<?php echo $quoteLink; ?>".replace(/%s/g, postId);}
function makeEditLink(postId) {return "<?php echo $editLink; ?>".replace(/%s/g, postId).replace("%t", esoTalk.token);}
function makeDeleteLink(postId) {return "<?php echo $deleteLink; ?>".replace(/%s/g, postId).replace("%t", esoTalk.token);}
function makeRestoreLink(postId) {return "<?php echo $restoreLink; ?>".replace(/%s/g, postId);}
function makeShowDeletedLink(postId) {return "<?php echo $showDeletedLink; ?>".replace(/%s/g, postId);}
function makeHideDeletedLink(postId) {return "<?php echo $hideDeletedLink; ?>".replace(/%s/g, postId);}
function makeLastAction(lastAction) {return "<?php echo $lastAction; ?>".replace("%s", lastAction);}
function makePermalink(postId) {return "<?php echo $permalink; ?>".replace("%s", postId);}
// ]]>
</script>

<?php
// Loop through the posts and output them!
if (!empty($this->conversation["posts"])):
$side = false;
$alternateAvatars = false;
switch (!empty($this->esoTalk->user["avatarAlignment"]) ? $this->esoTalk->user["avatarAlignment"] : $_SESSION["avatarAlignment"]) {
	case "right": $side = "r"; break;
	case "left": $side = "l"; break;
	case "none": break;
	default: $side = $this->startFrom % 2 ? "l" : "r"; $alternateAvatars = true;
}
foreach ($this->conversation["posts"] as $k => $post):
$singlePost = false;

// If this post is deleted...
if (!empty($post["deleteMember"])): ?>
<hr/><div class='p deleted' id='p<?php echo $post["id"]; ?>'><div class='hdr'>
<div class='pInfo'>
<h3><?php echo $post["name"]; ?></h3>
<span title='<?php echo $post["date"]; ?>'><a href='<?php echo str_replace("%s", $post["id"], $permalink); ?>'><?php echo $post["relativeTime"]; ?></a></span>
<span><?php printf($deletedBy, $post["deleteMember"]); ?></span>
</div>
<div class='controls'>
<?php if ($post["canEdit"]): ?><span><?php echo str_replace("%s", $post["id"], $this->showingDeletedPost == $post["id"] ? $hideDeletedLink : $showDeletedLink); ?></span> <span><?php echo str_replace("%s", $post["id"], $restoreLink); ?></span><?php endif; ?> 
</div>
</div>
<?php if ($this->showingDeletedPost == $post["id"]): ?><div class='body'><?php echo $post["body"]; ?></div><?php endif; ?>
</div>
<?php continue; endif;

// If the post before this one is by a different member to this one, start a new post 'group'.
if (!isset($this->conversation["posts"][$k - 1]["memberId"]) or $this->conversation["posts"][$k - 1]["memberId"] != $post["memberId"] or !empty($this->conversation["posts"][$k - 1]["deleteMember"])): ?>
<hr/><div class='p <?php if ($side) echo $side; ?> c<?php echo $post["color"]; ?>'<?php
	if (!isset($this->conversation["posts"][$k + 1]["memberId"]) or $this->conversation["posts"][$k + 1]["memberId"] != $post["memberId"] or !empty($this->conversation["posts"][$k + 1]["deleteMember"])): $singlePost = true; ?> id='p<?php echo $post["id"]; ?>'<?php
	endif; ?>>
<div class='parts'>
<?php endif; 

// Regardless of post 'groups', output this individual post. ?>
<div<?php if (!$singlePost): ?> id='p<?php echo $post["id"]; ?>'<?php endif;?>>
<div class='hdr'>
<div class='pInfo'>
<h3><?php echo str_replace(array("%d", "%s"), array($post["memberId"], $post["name"]), $memberLink); ?></h3>
<span title='<?php echo $post["date"]; ?>'><a href='<?php echo str_replace("%s", $post["id"], $permalink); ?>'><?php echo $post["relativeTime"]; ?></a></span>
<?php if ($post["editTime"]): ?><span><?php printf($editedBy, $post["editMember"], $post["editTime"]); ?></span>
<?php endif; if (!empty($post["accounts"])): ?><form action='<?php echo curLink(); ?>' method='post'><div style='display:inline'><select onchange='Conversation.changeMemberGroup(<?php echo $post["memberId"]; ?>,this.value)' name='group'>
	<?php foreach ($post["accounts"] as $group): ?><option value='<?php echo $group; ?>'<?php if ($group == $post["account"]) echo " selected='selected'"; ?>><?php echo $language[$group]; ?></option><?php endforeach; ?></select></div> <noscript><div style='display:inline'><input name='saveGroup' type='submit' value='Save' class='save'/><input type='hidden' name='member' value='<?php echo $post["memberId"]; ?>'/></div></noscript></form>
<?php elseif ($post["account"] != "Member"): ?><span><?php echo $language[$post["account"]]; ?></span>
<?php endif; if ($post["lastAction"]): ?><span><?php printf($lastAction, $post["lastAction"]); ?></span>
<?php endif; foreach ((array)$post["info"] as $info) echo "<span>$info</span>\n"; ?>
</div>
<div class='controls'>
<?php if ($this->editingPost == $post["id"]): echo implode(" ", $this->getEditControls("p" . $post["id"])); 
else: ?>
<?php if ($this->canReply() === true) echo str_replace("%s", $post["id"], $quoteLink), " "; ?>
<?php if ($post["canEdit"]) echo str_replace(array("%s", "%t"), array($post["id"], $_SESSION["token"]), $editLink), " ", str_replace(array("%s", "%t"), array($post["id"], $_SESSION["token"]), $deleteLink), " "; ?>
<?php foreach ((array)$post["controls"] as $control) echo $control, " "; ?>
<?php endif; ?>
</div>
</div>
<div class='body<?php if ($this->editingPost == $post["id"]): ?> edit<?php endif; ?>'>
<?php echo $this->editingPost == $post["id"] ? $this->getEditArea($post["id"], $this->formatForEditing($post["body"])) : $post["body"]; ?> 
</div>
</div>
<?php

// If the post after this one is by a different member to this one, end the post 'group'.
if (!isset($this->conversation["posts"][$k + 1]["memberId"]) or $this->conversation["posts"][$k + 1]["memberId"] != $post["memberId"] or !empty($this->conversation["posts"][$k + 1]["deleteMember"])): ?>
</div>
<?php if ($side): ?><div class='avatar'><?php echo str_replace(array("%d", "%s"), array($post["memberId"], "<img src='" . ($post["avatar"] ? $post["avatar"] : ("skins/{$config["skin"]}/avatar" . ($side == "l" ? "Left" : "Right") . ".png")) . "' alt=''/>"), $memberLink); ?></div><?php endif; ?>
<div class='clear'></div>
</div>

<?php
// Switch sides now that we're at the end of the group.
if ($alternateAvatars and isset($this->conversation["posts"][$k + 1]["memberId"]) and empty($this->conversation["posts"][$k + 1]["deleteMember"])) $side = $side == "r" ? "l" : "r";
endif;
endforeach;
endif;
?>

</div>

<hr/>

<?php
// Output the second pagination bar.
echo str_replace("id='pagination'", "id='paginationBottom'", $paginationHtml);
?>

</div>

<?php
// If the user can't reply, inform them with an error message.
if (($error = $this->canReply()) !== true):
echo $this->esoTalk->htmlMessage($error);

// If they can reply, show the reply area.
else:
?>
<?php if ($this->conversation["locked"]) echo $this->esoTalk->htmlMessage("lockedButCanReply"); ?>
<hr/><div id='reply' class='p <?php if ($this->esoTalk->user["avatarAlignment"] != "none") echo $this->esoTalk->user["avatarAlignment"] == "left" ? "l" : "r"; ?> c<?php echo $this->esoTalk->user["color"]; ?>'>
<div class='parts'>

<div class='hdr'>
<div class='pInfo'><h3><?php echo !$this->conversation["id"] ? $language["Start a conversation"] : $language["Post a reply"]; ?></h3></div>
<div class='controls'><?php echo implode(" ", $this->getEditControls("reply")); ?></div>
</div>
<div class='body edit'>
<?php if ($this->conversation["id"]): ?><form action='<?php echo curLink(); ?>' method='post' enctype='multipart/form-data'><div><?php endif; ?>
<input type='hidden' name='token' value='<?php echo $_SESSION["token"]; ?>'/>
<textarea cols='200' rows='20' id='reply-textarea' name='content' tabindex='30'><?php echo $this->conversation["draft"]; ?></textarea>
<div id='reply-preview'></div>
<div class='editButtons'><?php 
echo $this->esoTalk->skin->button(array("id" => "saveDraft", "name" => "saveDraft", "class" => "fl", "value" => $language["Save draft"], "tabindex" => 50)), " ",
	$this->esoTalk->skin->button(array("id" => "discardDraft", "name" => "discardDraft", "class" => "fl", "value" => $language["Discard draft"])), " ",
	$this->esoTalk->skin->button(array("id" => "postReply", "name" => "postReply", "class" => "big submit fr", "value" => $language["Submit post"], "tabindex" => 40));
?></div>
<?php $this->callHook("renderReplyArea"); ?>
<?php if ($this->conversation["id"]): ?></div></form><?php endif; ?>
</div>

</div>
<?php if ($this->esoTalk->user["avatarAlignment"] != "none"): ?><div class='avatar'><img src='<?php echo $this->esoTalk->getAvatar($this->esoTalk->user["memberId"], $this->esoTalk->user["avatarFormat"], $this->esoTalk->user["avatarAlignment"] == "left" ? "l" : "r"); ?>' alt=''/></div><?php endif; ?><div class='clear'></div>
</div>

<?php endif; ?>
</div>

<script type='text/javascript'>Conversation.init();</script>

<?php if (!$this->conversation["id"]): ?></form><?php endif; ?>
<?php endif; ?>