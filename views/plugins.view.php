<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Plugins view

if (!defined("IN_ESOTALK")) exit;
?>
<?php if (count($this->plugins)) : ?>
<fieldset id='installed'>
<legend><?php echo $language["Installed plugins"]; ?></legend>

<script type='text/javascript'>
// <![CDATA[
function toggleEnabled(id, enabled) {
	Ajax.request({
		"url": esoTalk.baseURL + "ajax.php?controller=plugins",
		"post": "action=toggle&id=" + encodeURIComponent(id) + "&enabled=" + (enabled ? "1" : "0"),
		"success": function() {document.getElementById("ext-" + id).className = enabled ? "enabled" : "";}
	});
}
function toggleSettings(id) {
	for (var i in plugins) if (plugins[i] != id && $("ext-" + plugins[i] + "-settings") && $("ext-" + plugins[i] + "-settings").showing) animateToggle($("ext-" + plugins[i] + "-settings"), 0);
	animateToggle($("ext-" + id + "-settings"), !$("ext-" + id + "-settings").showing);
}

function animateToggle(settings, showing)
{
	settings.showing = showing;
	settings.style.display = "block";
	var overflowDiv = Conversation.createOverflowDiv(settings);
	if (!overflowDiv.style.display) overflowDiv.style.display = "none";
	var initHeight = overflowDiv.offsetHeight;
	if (!overflowDiv.style.opacity) overflowDiv.style.opacity = 0;
	var initOpacity = parseFloat(overflowDiv.style.opacity);
	settings.style.position = "relative";
	overflowDiv.style.display = "block";
	overflowDiv.style.overflow = "hidden";
	var finalHeight = settings.offsetHeight;
	if (overflowDiv.animation) overflowDiv.animation.stop();
	overflowDiv.animation = new Animation(function(values, final) {
		overflowDiv.style.height = Math.round(values[0]) + "px";
		//settings.style.top = Math.round(values[0] - finalHeight) + "px";
		overflowDiv.style.opacity = values[1];
		if (final && values[0] == 0) {
			overflowDiv.style.display = "none";
			overflowDiv.style.height = "";
			settings.style.display = "none";
			settings.style.top = "";
		}
	}, {begin: [initHeight, initOpacity], end: [showing ? finalHeight : 0, showing ? 1 : 0]});
	overflowDiv.animation.start();
}
var plugins = [];
// ]]>
</script>

<ul>
<?php foreach ($this->plugins as $k => $plugin): ?>
<li id='ext-<?php echo $k; ?>'<?php if ($plugin["loaded"]): ?> class='enabled'<?php endif; ?>>
<div class='controls'>
<?php if (!empty($plugin["settings"])): ?><a href='javascript:toggleSettings("<?php echo $k; ?>");void(0)'><?php echo $language["settings"]; ?></a><?php endif; ?>
</div>
<input type='checkbox' class='checkbox'<?php if ($plugin["loaded"]): ?> checked='checked'<?php endif; ?> id='ext-<?php echo $k; ?>-checkbox' name='plugins[<?php echo $k; ?>]' value='1' onclick='toggleEnabled("<?php echo $k; ?>", this.checked);'/>
<noscript><div style='display:inline'><a href='<?php echo makeLink("plugins", "?toggle=$k"); ?>'><?php echo $plugin["loaded"] ? "Deactivate" : "Activate"; ?></a></div></noscript>	
<label for='ext-<?php echo $k; ?>-checkbox' class='checkbox'><big><strong><?php echo $plugin["name"]; ?></strong> <?php echo $plugin["version"]; ?></big></label> &nbsp; <?php echo $plugin["author"] ?> <small><?php echo $plugin["description"]; ?></small>

<?php if (!empty($plugin["settings"])): ?>
<div id='ext-<?php echo $k; ?>-settings' class='settings'>
<?php echo $plugin["settings"]; ?>
</div>
<?php endif; ?>
<script type='text/javascript'>plugins.push("<?php echo $k; ?>");<?php if (!isset($_POST[$k])): ?>hide($("ext-<?php echo $k; ?>-settings"))<?php else: ?>$("ext-<?php echo $k; ?>-settings").showing=1<?php endif; ?></script>
</li>
<?php endforeach; ?>
</ul>
</fieldset>

<?php else: ?>
<?php echo $this->esoTalk->htmlMessage("noPluginsInstalled"); ?>
<?php endif; ?>

<fieldset id='addPlugin'>
<legend><?php echo $language["Add a new plugin"]; ?></legend>
<?php echo $this->esoTalk->htmlMessage("downloadPlugins", "http://esotalk.com/plugins"); ?>
<form action='<?php echo makeLink("plugins"); ?>' method='post' enctype='multipart/form-data'>
<ul class='form'>
<li><label><?php echo $language["Upload an plugin"]; ?></label> <input name='uploadPlugin' type='file' class='text' size='20'/></li>
<li><label></label> <?php echo $this->esoTalk->skin->button(array("value" => $language["Add plugin"])); ?></li>
</ul>
</form>
</fieldset>