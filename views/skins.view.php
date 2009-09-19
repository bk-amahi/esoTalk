<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Skins view

if (!defined("IN_ESOTALK")) exit;
?>
<?php if (count($this->skins)): ?>
<fieldset id='skins'>
<legend><?php echo $language["Installed skins"]; ?></legend>
<ul>
<?php foreach ($this->skins as $k => $skin): ?>
<li<?php if ($skin["selected"]): ?> class='enabled'<?php endif; ?>>
<a href='<?php echo makeLink("skins", $k); ?>'>
<span class='preview'>
<?php if (file_exists("skins/$k/preview.png")): ?><img src='skins/<?php echo $k; ?>/preview.png' alt='<?php echo $skin["name"]; ?>'/>
<?php else: ?><span><?php echo $language["No preview"]; ?></span><?php endif; ?>
</span>
<big><strong><?php echo $skin["name"]; ?></strong> <?php echo $skin["version"]; ?></big> <?php echo $skin["author"]; ?>
</a>
</li>
<?php endforeach; ?>
</ul>
</fieldset>

<?php else: ?>
<?php echo $this->esoTalk->htmlMessage("noSkinsInstalled"); ?>
<?php endif; ?>

<fieldset id='addSkin'>
<legend><?php echo $language["Add a new skin"]; ?></legend>
<?php echo $this->esoTalk->htmlMessage("downloadSkins", "http://esotalk.com/skins"); ?>
<form action='<?php echo makeLink("skins"); ?>' method='post' enctype='multipart/form-data'>
<ul class='form'>
<li><label><?php echo $language["Upload a skin"]; ?></label> <input name='uploadSkin' type='file' class='text' size='20'/></li>
<li><label></label> <?php echo $this->esoTalk->skin->button(array("value" => $language["Add skin"])); ?></li>
</ul>
</form>
</fieldset>