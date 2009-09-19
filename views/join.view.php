<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Join view

if (!defined("IN_ESOTALK")) exit;
?>
<form action='<?php echo makeLink("join"); ?>' method='post' id='join'>
	
<?php
foreach ($this->form as $id => $fieldset):
	if (is_array($fieldset)):
		echo "<fieldset id='$id'><legend>{$fieldset["legend"]}</legend><ul class='form'>";
		ksort($fieldset);
		foreach ($fieldset as $k => $field):
			if ($k === "legend") continue;
			if (is_array($field)):
				echo "<li>{$field["html"]} <div id='{$field["id"]}-message'>";
				if (@$field["message"]) echo $this->esoTalk->htmlMessage($field["message"]);
				echo "</div></li>";
 			else: echo $field; endif;
		endforeach;
		echo "</ul></fieldset>";
	else: echo $fieldset; endif;
endforeach;
?>

<p><?php echo $this->esoTalk->skin->button(array("id" => "joinSubmit", "name" => "searchSubmit", "value" => $language["Join this forum"], "class" => "big", "tabindex" => 1000)); ?></p>

<script type='text/javascript'>
// An array of the fields and if they're validated
Join.fieldsValidated = {<?php
$array = array();
foreach ($this->fields as $field) if (!empty($field["ajax"]))
	$array[] = "'{$field["id"]}':" . ((@$field["required"] and !@$field["success"]) ? "false" : "true");
echo implode(",", $array);
?>};
Join.init();
</script>

</form>