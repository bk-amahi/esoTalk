<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Search form include

if (!defined("IN_ESOTALK")) exit;
?>
<form id='search' action='<?php echo makeLink("search"); ?>' method='post' <?php if ($this->esoTalk->action == "search"): ?>class='withStartConversation'<?php endif; ?>>
<div>
<input id='searchText' name='search' type='text' class='text' value='<?php echo @$_SESSION["search"]; ?>'/>
<div class='fr'>
<a id='reset' href='<?php echo makeLink("search", ""); ?>'>x</a>
<?php echo $this->esoTalk->skin->button(array("id" => "submit", "name" => "submit", "value" => $language["Search"], "class" => "big")); ?>
<?php if ($this->esoTalk->action == "search"): ?>
<?php echo $this->esoTalk->skin->button(array("id" => "new", "name" => "new", "value" => $language["Start a conversation"], "class" => "big")); ?>
<?php endif; ?>
</div>
</div>
</form>