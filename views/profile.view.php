<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Profile view

if (!defined("IN_ESOTALK")) exit;
?>
<div class='p l c<?php echo $this->member["color"]; ?> profile'>
<div class='parts'>

<div>
<div class='hdr'>
<div class='pInfo'>
<h3><?php echo $this->member["name"]; ?></h3>
<span><?php echo $language[$this->member["account"]]; ?></span>
</div>
</div>
<div class='body'>
<ul class='form stats'>
<li><label><?php echo $language["Last active"]; ?></label> <div><?php echo relativeTime($this->member["lastSeen"]); ?></div></li>
<li><label><?php echo $language["First posted"]; ?></label> <div><?php echo relativeTime($this->member["firstPosted"]); ?></div></li>

<li><label><?php echo $language["Post count"]; ?></label> <div><?php echo number_format($this->member["postCount"]); ?>
<?php if ($this->member["postCount"] > 0): ?> <small>(<?php
$postsPerDay = round(min(max(0, $this->member["postCount"] / ((time() - $this->member["firstPosted"]) / 60 / 60 / 24)), $this->member["postCount"]));
if ($postsPerDay == 1) echo $language["post per day"];
else printf($language["posts per day"], $postsPerDay);
?>)</small><?php endif; ?></div></li>

<li><label><?php echo $language["Conversations started"]; ?></label> <div><?php echo number_format($this->member["conversationsStarted"]); ?>
<?php if ($this->member["conversationsStarted"] > 0): ?> <small>(<a href='<?php echo makeLink("search", "?q2=author:" . urlencode(desanitize($this->member["name"]))); ?>'><?php echo $language["check them out"]; ?></a>)</small><?php endif; ?></div></li>

<li><label><?php echo $language["Conversations participated in"]; ?></label> <div><?php echo $this->member["conversationsParticipated"]; ?>
<?php if ($this->member["conversationsParticipated"] > 0): ?> <small>(<a href='<?php echo makeLink("search", "?q2=contributor:" . urlencode(desanitize($this->member["name"]))); ?>'><?php echo $language["let's see"]; ?></a>)</small><?php endif; ?></div></li>

<?php if ($this->esoTalk->user and $this->member["memberId"] != $this->esoTalk->user["memberId"]): ?>
<li><label><?php echo $this->member["name"]; ?> &amp; <?php echo $this->esoTalk->user["name"]; ?><br/><span class='label private'><?php echo $language["labels"]["private"]; ?></span></label> <div><a href='<?php echo makeLink("search", "?q2=private+%2B+contributor:" . urlencode(desanitize($this->member["name"]))); ?>'><?php printf($language["See the private conversations I've had"], $this->member["name"]); ?></a><br/>
<a href='<?php echo makeLink("new", "?member=" . urlencode(desanitize($this->member["name"]))); ?>'><?php printf($language["Start a private conversation"], $this->member["name"]); ?></a></div></li>
<?php endif; ?>

</ul>
</div>
</div>

<?php
ksort($this->sections);
foreach ($this->sections as $section): ?>
<div><?php echo $section; ?></div>
<?php endforeach; ?>

</div>
<div class='avatar'><img src='<?php echo $this->esoTalk->getAvatar($this->member["memberId"], $this->member["avatarFormat"], "l"); ?>' alt=''/></div>
<div class='clear'></div>
</div>

