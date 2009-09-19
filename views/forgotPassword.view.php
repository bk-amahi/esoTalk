<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Forgot password view

if (!defined("IN_ESOTALK")) exit;
?>
<?php if (!$this->setPassword):
echo $this->esoTalk->htmlMessage("forgotPassword"); ?>
<form action='<?php echo makeLink("forgot-password"); ?>' method='post'>
<ul class='form'>
<li>
<label><?php echo $language["Enter your email"]; ?></label>
<input type='text' value='' name='email' id='email' class='text'/>
</li>

<li><label></label> <?php echo $this->esoTalk->skin->button(array("value" => $language["Recover password"])); ?></li>

</ul>
</form>

<?php else:
echo $this->esoTalk->htmlMessage("setNewPassword"); ?>
<form action='<?php echo makeLink("forgot-password", @$_GET["q2"]); ?>' method='post'>
<ul class='form'>

<li>
<label><?php echo $language["New password"]; ?></label>
<input type='password' value='' name='password' id='password' class='text'/>
<?php if (isset($this->errors["password"])): echo $this->esoTalk->htmlMessage($this->errors["password"]); endif; ?>
</li>

<li>
<label><?php echo $language["Confirm password"]; ?></label>
<input type='password' value='' name='confirm' id='confirm' class='text'/>
<?php if (isset($this->errors["confirm"])): echo $this->esoTalk->htmlMessage($this->errors["confirm"]); endif; ?>
</li>

<li>
<label></label>
<?php echo $this->esoTalk->skin->button(array("name" => "changePassword", "value" => $language["Change password"])); ?>
</li>

</ul>
</form>

<?php endif; ?>