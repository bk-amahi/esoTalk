<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Online view - displays a list of members currently online.

if (!defined("IN_ESOTALK")) exit;
?>
<h2><?php echo $language["Who's online"]; ?></h2>

<?php if ($this->numberOnline): ?>
<div id='membersOnline'>
<?php while (list($memberId, $name, $avatarFormat, $color, $account, $lastSeen, $lastAction) = $this->esoTalk->db->fetchRow($this->online)): ?>
<div class='p c<?php echo $color; ?>'><div class='hdr'>
<?php if ($this->numberOnline < 20): ?><img src='<?php echo $this->esoTalk->getAvatar($memberId, $avatarFormat, "thumb"); ?>' alt='' class='avatar'/><?php endif; ?>
<h3><a href='<?php echo makeLink("profile", $memberId); ?>'><?php echo $name; ?></a></h3>
<span><?php echo $lastAction; ?> (<?php echo relativeTime($lastSeen); ?>)</span>
</div></div>
<?php endwhile; ?>
</div>

<?php else: ?>
<?php echo $this->esoTalk->htmlMessage("noMembersOnline"); ?>
<?php endif; ?>