<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Skins controller: Handles the 'Skins' page. Installs and switches skins.

if (!defined("IN_ESOTALK")) exit;

class skins extends Controller {

var $view = "skins.view.php";
var $skins = array();

// Initialize; get a list of skins
function init()
{
	if (!$this->esoTalk->user["admin"]) redirect("");
	
	global $language, $config;
	$this->title = $language["Skins"];
	
	if (isset($_FILES["uploadSkin"])) $this->uploadSkin();
	
	// Get the skins
	if ($handle = opendir("skins")) {
	    while (false !== ($file = readdir($handle))) {
	        if ($file[0] != "." and is_dir("skins/$file") and file_exists("skins/$file/skin.php") and (include_once "skins/$file/skin.php") and class_exists($file)) {
	        	$skin = new $file;
				$this->skins[$file] = array(
					"selected" => $config["skin"] == $file,
					"name" => $skin->name,
					"version" => $skin->version,
					"author" => $skin->author,
				);
			}
	    }
	    closedir($handle);
	}
	ksort($this->skins);
	
	// Activate a skin
	if (!empty($_GET["q2"])) $this->changeSkin($_GET["q2"]);
}

// Change the skin
function changeSkin($skin)
{
	// Initial checks
	if (!array_key_exists($skin, $this->skins)) return false;
	
	// Write the skin file
	writeConfigFile("config/skin.php", '$config["skin"]', $skin);
	
	redirect("skins");
}

// Upload a new skin
function uploadSkin()
{
	// Check for upload error
	if ($_FILES["uploadSkin"]["error"]) {
		$this->esoTalk->message("invalidSkin");
		return false;
	}
	// Move the uploaded file
	move_uploaded_file($_FILES["uploadSkin"]["tmp_name"], "skins/{$_FILES["uploadSkin"]["name"]}");
	// Upzip it
	if (!($files = unzip("skins/{$_FILES["uploadSkin"]["name"]}", "skins/"))) $this->esoTalk->message("invalidSkin");
	else {
		$directories = 0; $infoFound = false; $skinFound = false;
		foreach ($files as $k => $file) {
			if (substr($file["name"], 0, 9) == "__MACOSX/" or substr($file["name"], -9) == ".DS_Store") {
				unset($files[$k]);
				continue;
			}
			if ($file["directory"]) $directories++;
			if (substr($file["name"], -8) == "skin.php") $skinFound = true;
		}
		// If we found a skin.php, info.php, and a base directory, write the files
		if ($skinFound and $directories == 1) {
			$error = false;
			foreach ($files as $k => $file) {
				if ($file["directory"] and !is_dir("skins/{$file["name"]}")) mkdir("skins/{$file["name"]}");
				elseif (!$file["directory"]) {
					if (file_exists("skins/{$file["name"]}") and !is_writeable("skins/{$file["name"]}")) {
						$this->esoTalk->message("notWritable", false, "skins/{$file["name"]}");
						$error = true;
						break;
					}
					$handle = fopen("skins/{$file["name"]}", "w");
					chmod("skins/{$file["name"]}", 0777);
					fwrite($handle, $file["content"]);
					fclose($handle);
				}
			}
			if (!$error) $this->esoTalk->message("skinAdded");
		} else $this->esoTalk->message("invalidSkin");
	}
	unlink("skins/{$_FILES["uploadSkin"]["name"]}");
}

}

?>