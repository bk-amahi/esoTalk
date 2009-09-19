<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Installer

define("IN_ESOTALK", 1);

// No timeout
@set_time_limit(0);

// Require essential files
require "../lib/functions.php";
require "../lib/database.php";

// Start a session if one does not already exist
if (!session_id()) session_start();

// Undo register globals
undoRegisterGlobals();

// Generate button html
function button($attributes)
{
	$class = $id = $style = ""; $attr = " type='submit'";
	foreach ($attributes as $k => $v) {
		if ($k == "class") $class = " $v";
		elseif ($k == "id") $id = " id='$v'";
		elseif ($k == "style") $style = " style='$v'";
		else $attr .= " $k='$v'";
	}
	return "<input class='button$class'$id$style$attr/>";
}

// If magic quotes is on, strip the slashes that it added
if (get_magic_quotes_gpc()) {
	$_GET = array_map("undoMagicQuotes", $_GET);
	$_POST = array_map("undoMagicQuotes", $_POST);
	$_COOKIE = array_map("undoMagicQuotes", $_COOKIE);
}

// Clean and sterilize the request data
$_POST = sanitize($_POST);
$_GET = sanitize($_GET);
$_COOKIE = sanitize($_COOKIE);

// Set up the Install controller
require "install.controller.php";
$install = new Install();
$install->init();

?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<!-- This page was generated by esoTalk (http://esotalk.com) -->
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
<head>
<title>esoTalk Installer</title>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<script type='text/javascript' src='../js/esotalk.js'></script>
<style type='text/css'>
body {background:#fff; font-size:75%}
body, input, select, textarea, table {font-family:arial, helvetica, sans-serif; margin:0}
input, select, textarea {font-size:100%}
#container {margin:50px auto 0 auto; width:55em; background:#f5f5ff; padding:20px; font-size:120%}
#container h1 {margin:0 0 20px 0; font-size:160%; font-weight:normal}
#container h1 img {vertical-align:middle; margin-right:15px}
a {text-decoration:none; color:#00f}
a:hover {text-decoration:underline; color:#000}
hr {color:#bbf; border:solid #bbf; border-width:1px 0 0 0; height:1px}
#footer {text-align:right; font-size:90%}
pre {overflow:auto; padding-bottom:5px}

/* Inputs, buttons, and other form elements */
form {margin:0}
input.text, textarea {border:1px solid #aaa; background:#fff; padding:2px}
input.text:focus, textarea.text:focus {border-color:#555}
#cInfo .editable:focus {background:#fff}
#reset {color:#aaa; background:#fff; border-color:#aaa}
#reset:hover {color:#000; border-color:#aaa}
.placeholder {color:#aaa}
input.checkbox, input.radio {padding:0; margin:0 2px 0 5px; vertical-align:-2px}
label.checkbox, label.radio {cursor:pointer}
fieldset {border:1px solid #ccc; margin:10px 0 20px 0; padding:0 15px 15px}
legend {font-size:140%; padding:5px 10px 10px; font-weight:bold; color:#000}

/* Structured forms */
.form label {width:12em; float:left; text-align:right; margin:2px 1em 0 0}
.form div label {float:none; width:auto; text-align:left; margin:0}
.form label.long {width:100%; text-align:left}
.form label.radio {text-align:left; cursor:pointer}
.form input.text, .form select {width:20em; margin:0; float:left}
.form div {float:left}
.form div input.text, .form div select {float:none}
.form {margin:0; padding:0}
.form li {margin-bottom:4px; overflow:hidden; list-style:none; display:block; zoom:1}
.form .msg {font-size:80%; padding:3px 5px; margin:0 0 0 5px; float:left; width:25em}

#footer .button {margin-left:5px}

.big {font-size:140%}

li {margin-bottom:1em}

.msg {padding:5px 10px; background:#ddd; margin:0 0 1em 0; line-height:1.4}
.info {background:#fad163}
.success {background:#cf0}
.warning {background:#c00; color:#fff}
.warning a, .warning a:hover {color:#fff; text-decoration:underline}
</style>
</head>

<body>
	
<form action='' method='post'>
<div id='container'>

<?php

switch ($install->step) {

// Fatal checks
case "fatalChecks": ?>
<h1><img src='logo.gif' alt=''/> Cannot install esoTalk</h1>
<p>The following errors were found with your esoTalk setup. They must be resolved before you can continue the installation.</p>
<hr/>
<ul>
<?php foreach ($install->errors as $error) echo "<li>$error</li>"; ?>
</ul>
<p>If you run into any other problems or just want some help with the installation, feel free to ask for assistance at the <a href='http://forum.esotalk.com/'>esoTalk support forum</a> where a bunch of friendly people will be happy to help you out.</p>
<hr/>
<p id='footer'><?php echo button(array("type" => "submit", "class" => "big", "value" => "Try again")); ?></p>
<?php break;

// Warning checks
case "warningChecks": ?>
<h1><img src='logo.gif' alt=''/> Warning!</h1>
<p>The following errors were found with your esoTalk setup. You can continue the esoTalk install without resolving them, but some esoTalk functionality may be limited.</p>
<hr/>
<ul>
<?php foreach ($install->errors as $error) echo "<li>$error</li>"; ?>
</ul>
<p>If you run into any other problems or just want some help with the installation, feel free to ask for assistance at the <a href='http://forum.esotalk.com/'>esoTalk support forum</a> where a bunch of friendly people will be happy to help you out.</p>
<hr/>
<p id='footer'><?php echo button(array("type" => "submit", "name" => "next", "class" => "big", "value" => "Next step &#155;")); ?></p>
<?php break;

// Specify setup information
case "info": ?>
<h1><img src='logo.gif' alt=''/> Specify setup information</h1>
<p>Please specify the following information about your esoTalk setup.</p>
<hr/>
<div>

<ul class='form'>
<li><label>Forum title</label> <input name='forumTitle' type='text' class='text' value='<?php echo @$_POST["forumTitle"]; ?>'/>
<?php if (isset($install->errors["forumTitle"])): ?><div class='warning msg'><?php echo $install->errors["forumTitle"]; ?></div><?php endif; ?></li>
</ul>

<br/>

<div class='form' style='overflow:hidden; zoom:1'>
<ul class='form' style='float:left'>
<li><label>MySQL host address</label> <input name='mysqlHost' type='text' class='text' value='<?php echo isset($_POST["mysqlHost"]) ? $_POST["mysqlHost"] : "localhost"; ?>'/></li>
<li><label>MySQL username</label> <input name='mysqlUser' type='text' class='text' value='<?php echo @$_POST["mysqlUser"]; ?>'/></li>
<li><label>MySQL password</label> <input name='mysqlPass' type='password' class='text' value='<?php echo @$_POST["mysqlPass"]; ?>'/></li>
<li><label>MySQL database</label> <input name='mysqlDB' type='text' class='text' value='<?php echo @$_POST["mysqlDB"]; ?>'/></li>
</ul>
<?php if (isset($install->errors["mysql"])): ?><span class='warning msg' style='float:left'><?php echo $install->errors["mysql"]; ?></span><?php endif; ?>
</div>

<br/>

<ul class='form'>
<li><label>Administrator username</label> <input name='adminUser' type='text' class='text' value='<?php echo @$_POST["adminUser"]; ?>'/>
<?php if (isset($install->errors["adminUser"])): ?><span class='warning msg'><?php echo $install->errors["adminUser"]; ?></span><?php endif; ?></li>

<li><label>Administrator email</label> <input name='adminEmail' type='text' class='text' value='<?php echo @$_POST["adminEmail"]; ?>'/>
<?php if (isset($install->errors["adminEmail"])): ?><span class='warning msg'><?php echo $install->errors["adminEmail"]; ?></span><?php endif; ?></li>

<li><label>Administrator password</label> <input name='adminPass' type='password' class='text' value='<?php echo @$_POST["adminPass"]; ?>'/>
<?php if (isset($install->errors["adminPass"])): ?><span class='warning msg'><?php echo $install->errors["adminPass"]; ?></span><?php endif; ?></li>

<li><label>Confirm password</label> <input name='adminConfirm' type='password' class='text' value='<?php echo @$_POST["adminConfirm"]; ?>'/>
<?php if (isset($install->errors["adminConfirm"])): ?><span class='warning msg'><?php echo $install->errors["adminConfirm"]; ?></span><?php endif; ?></li>
</ul>

<br/>

<div><a href='javascript:toggleAdvanced()' title='What, you&#39;re too cool for the normal settings?'>Advanced options</a></div>

<div id='advanced' style='overflow:hidden; margin:0'>
<hr/>

<?php if (isset($install->errors["tablePrefix"])): ?><p class='warning msg'><?php echo $install->errors["tablePrefix"]; ?></p><?php endif; ?>

<ul class='form'>
<li><label>MySQL table prefix</label> <input name='tablePrefix' type='text' class='text' value='<?php echo isset($_POST["tablePrefix"]) ? $_POST["tablePrefix"] : "et_"; ?>'/></li>

<li><label>Base URL</label> <input name='baseURL' type='text' class='text' value='<?php echo isset($_POST["baseURL"]) ? $_POST["baseURL"] : $install->suggestBaseUrl(); ?>'/></li>

<li><label>Use friendly URLs</label> <input name='friendlyURLs' type='checkbox' class='checkbox' value='1' checked='<?php echo (!empty($_POST["friendlyURLs"]) or $install->suggestFriendlyUrls()) ? "checked" : ""; ?>'/></li>
</ul>

<input type='hidden' name='showAdvanced' id='showAdvanced' value=''/>
<script type='text/javascript'>
function toggleAdvanced() {
	$("advanced").style.display = $("advanced").style.display == "none" ? "" : "none";
	$("showAdvanced").value = $("advanced").style.display == "none" ? "" : "1";
}
<?php if (empty($_POST["showAdvanced"])): ?>toggleAdvanced();<?php endif; ?>
</script>
</div>

<hr/>
<p id='footer'><?php echo button(array("type" => "submit", "class" => "big", "value" => "Next step &#155;")); ?></p>

<?php break;

// Show an installation error
case "install": ?>
<h1><img src='logo.gif' alt=''/> Uh oh! It's a fatal error...</h1>
<p class='warning msg'>The esoTalk installer encountered an error.</p>
<p>The installer has encountered a nasty error which is making it impossible to install esoTalk on your server. But don't feel down - <strong>here are a few things you can try</strong>:</p>
<ul>
<li><strong>Try again.</strong> Everyone makes mistakes - maybe the computer made one this time!</li>
<li><strong>Go back and check your settings.</strong> In particular, make sure your database information is correct.</li>
<li><strong>Get help.</strong> Go on the <a href='http://forum.esotalk.com' title='Don&#039;t worry, we&#039;re friendly!'>esoTalk support forum</a> and <a href='http://forum.esotalk.com/search/tag:installation'>search</a> to see if anyone else is having the same problem as you are. If not, start a new conversation about your problem, including the error details below.</li>
</ul>
<div><a href='javascript:toggleError()'>Show error information</a></div>
<div id='error'>
<hr/>
<?php echo $install->errors[1]; ?>
</div>
<script type='text/javascript'>
function toggleError() {
	$("error").style.display = $("error").style.display == "none" ? "" : "none";
}
toggleError();
</script>
<hr/>
<form action='' method='post'>
<p id='footer'>
<?php echo button(array("type" => "submit", "class" => "big", "value" => "&#139; Go back", "name" => "back")); ?>
<?php echo button(array("type" => "submit", "class" => "big", "value" => "Try again")); ?>
</p>
</form>

<?php break;

// Register
case "register": ?>
<h1><img src='logo.gif' alt=''/> Register your forum</h1>
<p>Registration is quick and painless, and it kinda helps us to get an idea of how many people are using esoTalk. ^_^</p>
<p><strong>If you choose to register, the following information will be sent to us:</strong></p>
<ul class='form'>
<li><label>Forum URL</label> <div><?php echo $_SESSION["install"]["baseURL"]; ?></div></li>
<li><label>PHP version</label> <div><?php echo $install->phpVersion; ?></div></li>
<li><label>Webserver</label> <div><?php echo $install->serverSoftware; ?></div></li>
<li><label>MySQL version</label> <div><?php echo $install->mysqlVersion; ?></div></li>
</ul>
<hr/>
<p>
<input type='radio' name='register' id='yes' value='1' checked='checked'/> <label for='yes' class='radio'><strong>Sure, why not? I'll register!</strong></label><br/>
<input type='radio' name='register' id='no' value='0'/> <label for='no' class='radio'><strong>No thanks.</strong></label>
</p>
<hr/>
<p id='footer'><?php echo button(array("type" => "submit", "class" => "big", "value" => "Next step &#155;")); ?></p>
<?php break;

// Finish!
case "finish": ?>
<h1><img src='logo.gif' alt=''/> Congratulations!</h1>
<p>esoTalk has been installed, and your forum should be up and ready to go.</p>
<p>It's highly recommended that you <strong>remove the <code>install</code> folder</strong> to prevent anyone from hacking your forum.</p>
<p><a href='javascript:toggleAdvanced()'>Show advanced information</a></p>
<div id='advanced'>
<hr/>
<p><strong>Queries run</strong></p>
<pre>
<?php if (isset($_SESSION["queries"]) and is_array($_SESSION["queries"]))
	foreach ($_SESSION["queries"] as $query) echo sanitize($query) . ";<br/><br/>"; ?>
</pre>
</div>
<script type='text/javascript'>
function toggleAdvanced() {
	$("advanced").style.display = $("advanced").style.display == "none" ? "" : "none";
}
toggleAdvanced();
</script>
<hr/>
<p style='text-align:center;' id='footer'><?php echo button(array("type" => "submit", "class" => "big", "value" => "Take me to my forum!", "name" => "finish")); ?></p>
<?php break;

}

?>
</div>

</div>
</form>

</body>

</html>