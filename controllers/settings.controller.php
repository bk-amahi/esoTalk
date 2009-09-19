<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Settings controller: Handles the 'My settings' page. Changes avatar, color, and handles other forms.

if (!defined("IN_ESOTALK")) exit;

class settings extends Controller {
	
var $view = "settings.view.php";
var $messages = array();

// Initialize
function init()
{
	// If we're not logged in, go to the join page
	if (!$this->esoTalk->user) redirect("join");
	
	global $language;
	$this->title = $language["My settings"];	
	
	// Change the color?
	if (!empty($_GET["changeColor"]) and (int)$_GET["changeColor"]) $this->changeColor($_GET["changeColor"]);
	
	// Change the avatar?
	if (isset($_POST["changeAvatar"]) and $this->changeAvatar()) $this->esoTalk->message("changesSaved");
	
	// Change the user's password or email?
	if (isset($_POST["settingsPasswordEmail"]["submit"]) and $this->changePasswordEmail()) {
		$this->esoTalk->message("changesSaved");
		redirect("settings");
	}
	
	// Create a string of html language options
	$langOptions = "";
	$this->languages = array();
	if ($handle = opendir("languages")) {
	    while (false !== ($v = readdir($handle))) {
			if (!in_array($v, array(".", "..")) and substr($v, -4) == ".php" and $v[0] != ".") {
				$v = substr($v, 0, strrpos($v, "."));
				$langOptions .= "<option value='$v'" . ($this->esoTalk->user["language"] == $v ? " selected='selected'" : "") . ">$v</option>";
				$this->languages[] = $v;
			}
		}
	}
	
	// Create a string of html avatar alignment options
	$avatarAlignmentOptions = "";
	$align = array("alternate" => $language["on alternating sides"], "right" => $language["on the right"], "left" => $language["on the left"], "none" => $language["do not display avatars"]);
	foreach ($align as $k => $v) {
		$avatarAlignmentOptions .= "<option value='$k'" . ($this->esoTalk->user["avatarAlignment"] == $k ? " selected='selected'" : "") . ">$v</option>";
	}
	
	// Construct the form.
	$this->form = array(
		
		"settingsOther" => array(
			"legend" => $language["Other settings"],
			100 => array(
				"id" => "language",
				"html" => "<label>{$language["Forum language"]}</label> <select id='language' name='language'>$langOptions</select>",
				"databaseField" => "language",
				"required" => true,
				"validate" => array("Settings", "validateLanguage")
			),
			200 => array(
				"id" => "avatarAlignment",
				"html" => "<label>{$language["Display avatars"]}</label> <select id='avatarAlignment' name='avatarAlignment'>$avatarAlignmentOptions</select>",
				"databaseField" => "avatarAlignment",
				"validate" => array("Settings", "validateAvatarAlignment"),
				"required" => true
			),
			300 => array(
				"id" => "emailOnPrivateAdd",
				"html" => "<label for='emailOnPrivateAdd' class='checkbox'>{$language["emailOnPrivateAdd"]} <span class='label private'>{$language["labels"]["private"]}</span></label> <input id='emailOnPrivateAdd' type='checkbox' class='checkbox' name='emailOnPrivateAdd' value='1' " . ($this->esoTalk->user["emailOnPrivateAdd"] ? "checked='checked' " : "") . "/>",
				"databaseField" => "emailOnPrivateAdd",
				"checkbox" => true
			),
			400 => array(
				"id" => "emailOnStar",
				"html" => "<label for='emailOnStar' class='checkbox'>{$language["emailOnStar"]} <span class='star1 starInline'>*</span></label> <input id='emailOnStar' type='checkbox' class='checkbox' name='emailOnStar' value='1' " .  ($this->esoTalk->user["emailOnStar"] ? "checked='checked' " : "") . "/>",
				"databaseField" => "emailOnStar",
				"checkbox" => true
			),
			500 => array(
				"id" => "disableJSEffects",
				"html" => "<label for='disableJSEffects' class='checkbox'>{$language["disableJSEffects"]}</label> <input id='disableJSEffects' type='checkbox' class='checkbox' name='disableJSEffects' value='1' " .  (!empty($this->esoTalk->user["disableJSEffects"]) ? "checked='checked' " : "") . "/>",
				"databaseField" => "disableJSEffects",
				"checkbox" => true
			)
		)
	);
	
	$this->callHook("init");
	
	// Save settings if the big submit button was clicked.
	if (isset($_POST["submit"]) and $this->saveSettings()) {
		$this->esoTalk->message("changesSaved");
		redirect("settings");
	}	
}

// Save settings using the field arrays in the big form array.
function saveSettings()
{
	// Get the fields which we are saving into an array.
	$fields = array();
	foreach ($this->form as $k => $fieldset) {
		foreach ($fieldset as $j => $field) {
			if (!is_array($field)) continue;
			$this->form[$k][$j]["input"] = @$_POST[$field["id"]];
			$fields[] = &$this->form[$k][$j];
		}
	}
		
	// Go through the fields and validate them according to their "validate" and "required" values.
	foreach ($fields as $k => $field) {
		if ((!empty($field["required"]) or $field["input"])	and !empty($field["validate"]) and $msg = @call_user_func_array($field["validate"], array(&$fields[$k]["input"]))) {
			$validationError = true;
			$fields[$k]["message"] = $msg;
		}
	}
	
	if (!empty($validationError)) return false;
	
	// Save them to the database according to their "databaseField" value.
	$updateData = array();
	foreach ($fields as $field) {
		if (!empty($field["databaseField"])) $updateData[$field["databaseField"]] = @$field["checkbox"] ? ($field["input"] ? 1 : 0) : "'{$field["input"]}'";
	}
	$updateQuery = $this->esoTalk->db->constructUpdateQuery("members", $updateData, array("memberId" => $this->esoTalk->user["memberId"]));
	$this->esoTalk->db->query($updateQuery);
	
	// Update user session data according to the fields' "databaseField" values.
	foreach ($fields as $field) {
		if (!empty($field["databaseField"])) $_SESSION["user"][$field["databaseField"]] = $this->esoTalk->user[$field["databaseField"]] = $field["input"];
	}
	
	return true;
}

function changePasswordEmail()
{
	global $config;
	$updateData = array();
	
	// Are we setting a new password?
	if (!empty($_POST["settingsPasswordEmail"]["new"])) {
		// Make a copy of the password; the validatePassword() function will automatically format it into a hash.
		$hash = $_POST["settingsPasswordEmail"]["new"];
		if ($error = validatePassword($hash)) $this->messages["new"] = $error;
		// Do the passwords entered match?
		elseif ($_POST["settingsPasswordEmail"]["new"] != $_POST["settingsPasswordEmail"]["confirm"]) $this->messages["confirm"] = "passwordsDontMatch";
		// The password stuff is good. Add the password updating part to the query.
		else {
			$updateData["password"] = "'$hash'";
			$this->messages["confirm"] = "reenterInformation"; // Just in case we fail later on.
		}
		$this->messages["current"] = "reenterInformation";
	}
	
	// Are we setting a new email?
	if (!empty($_POST["settingsPasswordEmail"]["email"])) {
		// Validate the email address. If it's ok, add the updating part to the query.
		if ($error = validateEmail($_POST["settingsPasswordEmail"]["email"])) $this->messages["email"] = $error;
		else $updateData["email"] = "'{$_POST["settingsPasswordEmail"]["email"]}'";
		$this->messages["current"] = "reenterInformation";
	}
	
	// Check the user's old password.
	if (!$this->esoTalk->db->result("SELECT 1 FROM {$config["tablePrefix"]}members WHERE memberId={$this->esoTalk->user["memberId"]} AND password='" . md5($config["salt"] . $_POST["settingsPasswordEmail"]["current"]) . "'", 0)) $this->messages["current"] = "incorrectPassword";
	
	// Everything is ready to go! Run the query if necessary.
	elseif (count($updateData)) {
		$query = $this->esoTalk->db->constructUpdateQuery("members", $updateData, array("memberId" => $this->esoTalk->user["memberId"]));
		$this->esoTalk->db->query($query);
		$this->messages = array();
		return true;
	}
}

// Change the user's avatar - from a local upload or a remote url
function changeAvatar()
{
	if (empty($_POST["avatar"]["type"])) return false;
	global $config;
	
	$allowedTypes = array("image/jpeg", "image/png", "image/gif", "image/pjpeg", "image/x-png");
	$avatarFile = "avatars/{$this->esoTalk->user["memberId"]}";
		
	switch ($_POST["avatar"]["type"]) {
		
		// Upload an avatar from the user's computer
		case "upload":
			
			// Check for an error submitting the file and for a valid image file type.
			if ($_FILES["avatarUpload"]["error"] != 0
				or !in_array($_FILES["avatarUpload"]["type"], $allowedTypes)
				or !is_uploaded_file($_FILES["avatarUpload"]["tmp_name"])) {
				$this->esoTalk->message("avatarError");
				return false;
			}
			
			$type = $_FILES["avatarUpload"]["type"];
			$file = $_FILES["avatarUpload"]["tmp_name"];
			break;
		
		// Upload an avatar from a remote url
		case "url":
			
			// Make sure there is url_fopen
			if (!ini_get("allow_url_fopen")) return false;
			
			// Fix up the url
			$url = str_replace(" ", "%20", html_entity_decode($_POST["avatar"]["url"]));
			
			// Get/check the type of image
			$info = @getimagesize($url);
			$type = $info["mime"];
			$file = $avatarFile;
			
			// Check the type of the image, and open file read/write handlers.
			if (!in_array($type, $allowedTypes)
				or (($rh = fopen($url, "rb")) === false)
				or (($wh = fopen($file, "wb")) === false)) {
				$this->esoTalk->message("avatarError");
				return false;
			}
			
			// Transfer the image.
			while (!feof($rh)) {
				if (fwrite($wh, fread($rh, 1024)) === false) {
					$this->esoTalk->message("avatarError");
					return false;
				}
			}
			fclose($rh); fclose($wh);
			
			break;
			
		case "none":
			// Delete the avatar file.
			if (file_exists($avatarFile) and !@unlink($avatarFile)) {
				$this->esoTalk->message("avatarError");
				return false;
			}
			// Clear the avatarFormat.
			$this->esoTalk->db->query("UPDATE {$config["tablePrefix"]}members SET avatarFormat=NULL WHERE memberId={$this->esoTalk->user["memberId"]}");
			$this->esoTalk->user["avatarFormat"] = $_SESSION["user"]["avatarFormat"] = "";
			return true;
			
		default:
			return false;
	}
	
	// Phew, we got through all that. Now let's turn the image into a resource...
	switch ($type) {
		case "image/jpeg": case "image/pjpeg": $image = @imagecreatefromjpeg($file); break;
		case "image/x-png": case "image/png": $image = @imagecreatefrompng($file); break;
		case "image/gif": $image = @imagecreatefromgif($file);
	}
	if (!$image) {
		$this->esoTalk->message("avatarError");
		return false;
	}
	// ...and get its dimensions.
	list($curWidth, $curHeight) = getimagesize($file);
	
	// The dimensions we'll need are the normal avatar size and a thumbnail.
	$dimensions = array("" => array($config["avatarMaxWidth"], $config["avatarMaxHeight"]), "_thumb" => array($config["avatarThumbHeight"], $config["avatarThumbHeight"]));

	// Create new destination images according to the $dimensions.
	foreach ($dimensions as $suffix => $values) {

		// If the new max dimensions exist and are smaller than the current dimensions, we're gonna want to resize.
		$newWidth = $values[0];
		$newHeight = $values[1];
		if (($newWidth or $newHeight) and ($newWidth < $curWidth or $newHeight < $curHeight)) {
			// Work out the resize ratio and calculate the dimensions of the new image.
			$widthRatio = $newWidth / $curWidth;
			$heightRatio = $newHeight / $curHeight;
			$ratio = ($widthRatio and $widthRatio <= $heightRatio) ? $widthRatio : $heightRatio;
			$width = $ratio * $curWidth;
			$height = $ratio * $curHeight;
			$needsToBeResized = true;
		}
		// Otherwise just use the current dimensions.
		else {
			$width = $curWidth;
			$height = $curHeight;
			$needsToBeResized = false;
		}

		// Set the destination.
		$destination = $avatarFile . $suffix;
		
		// Delete their current avatar.
		if (file_exists("$destination.{$this->esoTalk->user["avatarFormat"]}"))
			unlink("$destination.{$this->esoTalk->user["avatarFormat"]}");

		// If it's a gif that doesn't need to be resized (and it's not a thumbnail), we move instead of resampling so as to preserve animation.
		if (!$needsToBeResized and $type == "image/gif" and $suffix != "_thumb") {
			
			$handle = fopen($file, "r"); 
			$contents = fread($handle, filesize($file)); 
			fclose($handle);
			
			// Filter the first 256 characters of the contents.
			$tags = array("!-", "a hre", "bgsound", "body", "br", "div", "embed", "frame", "head", "html", "iframe", "input", "img", "link", "meta", "object", "plaintext", "script", "style", "table");
			$re = array();
			foreach ($tags as $tag) {
				$part = "(?:<";
				$length = strlen($tag);
				for ($i = 0; $i < $length; $i++) {
					$part .= "\\x00*" . $tag[$i];
				}
				$re[] = $part . ")";
			}
			if (preg_match("/" . implode("|", $re) . "/", substr($contents, 0, 255))) $needsToBeResized = true;
			else writeFile($destination . ".gif", $contents);
		}

		if ($needsToBeResized or $type != "image/gif" or $suffix == "_thumb") {
			
			// -waves magic wand- Now, let's create the image!
			$newImage = imagecreatetruecolor($width, $height);
			// Preserve the alpha for pngs and gifs.
			if (in_array($type, array("image/png", "image/gif", "image/x-png"))) {
				imagecolortransparent($newImage, imagecolorallocate($newImage, 0, 0, 0));
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
			}
			// (Oh yeah, the reason we're doin' the whole imagecopyresampled() thing even for images that don't need to be resized is because it helps prevent a possible cross-site scripting attack in which the file has malicious data after the header.)
			imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $curWidth, $curHeight);
			
			// Save the image to the correct destination and format.
			switch ($type) {
				// jpeg
				case "image/jpeg": case "image/pjpeg":
					if (!imagejpeg($newImage, "$destination.jpg", 85)) $saveError = true;
					break;
				// png
				case "image/x-png": case "image/png":
					if (!imagepng($newImage, "$destination.png")) $saveError = true;
					break;
				case "image/gif":
					if (!imagepng($newImage, "$destination.gif")) $saveError = true;
			}
			if (!empty($saveError))  {
				$this->esoTalk->message("avatarError");
				return false;
			}

			// Clean up.
			imagedestroy($newImage);
		}
	}
	
	// Clean up temporary stuff.
	imagedestroy($image);
	@unlink($file);
	
	// Depending on the type of image that was uploaded, update the user's avatarFormat.
	switch ($type) {
		case "image/jpeg": case "image/pjpeg": $avatarFormat = "jpg"; break;
		case "image/x-png": case "image/png": $avatarFormat = "png"; break;
		case "image/gif": $avatarFormat = "gif";
	}
	$this->esoTalk->db->query("UPDATE {$config["tablePrefix"]}members SET avatarFormat='$avatarFormat' WHERE memberId={$this->esoTalk->user["memberId"]}");
	$this->esoTalk->user["avatarFormat"] = $_SESSION["user"]["avatarFormat"] = $avatarFormat;
	
	return true;
}

// Change the user's color
function changeColor($color)
{
	global $config;
	$color = max(0, min((int)$color, $this->esoTalk->skin->numberOfColors));

	$this->esoTalk->db->query("UPDATE {$config["tablePrefix"]}members SET color=$color WHERE memberId={$this->esoTalk->user["memberId"]}");
	$this->esoTalk->user["color"] = $_SESSION["user"]["color"] = $color;
}

// Ajax functions: change color
function ajax()
{
	switch ($_POST["action"]) {
		case "changeColor":
			$this->changeColor(@$_POST["color"]);
			break;
	}
	
	$return = null;
	$this->callHook("ajax", array(&$return));
	return $return;
}

// Add an element to the page's form.
function addToForm($fieldset, $field, $position = false)
{
	return addToArray($this->form[$fieldset], $field, $position);
}

// Add a fieldset to the form.
function addFieldset($fieldset, $legend, $position = false)
{
	return addToArrayString($this->form, $fieldset, array("legend" => $legend), $position);
}

// Validate the avatar alignment field; it must be "alternate", "right", "left", or "none".
function validateAvatarAlignment(&$alignment)
{
	if (!in_array($alignment, array("alternate", "right", "left", "none"))) $alignment = "alternate";
}

// Validate the language field; make sure the selected language actually exists.
function validateLanguage(&$language)
{
	global $config;
	if (!in_array($language, $this->languages)) $language = $config["language"];
}

}

?>