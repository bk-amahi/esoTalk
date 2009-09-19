<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Plugins controller: Controls everything for the plugins view: toggling plugins, installing, settings, etc.

if (!defined("IN_ESOTALK")) exit;

class plugins extends Controller {
	
var $view = "plugins.view.php";
var $plugins = array();

// Get all the plugins into an array
function init()
{
	if (!$this->esoTalk->user["admin"]) redirect("");
	
	global $language, $config;
	$this->title = $language["Plugins"];
	
	if (isset($_FILES["uploadPlugin"])) $this->uploadPlugin();
	
	// Get the plugins and their details
	if ($handle = opendir("plugins")) {
	    while (false !== ($file = readdir($handle))) {
	        if ($file[0] != "." and is_dir("plugins/$file") and file_exists("plugins/$file/plugin.php") and (include_once "plugins/$file/plugin.php") and class_exists($file)) {
				$plugin = new $file;
				$plugin->esoTalk =& $this->esoTalk;
				$this->plugins[$plugin->id] = array(
					"loaded" => in_array($file, $config["loadedPlugins"]),
					"name" => $plugin->name,
					"version" => $plugin->version,
					"description" => $plugin->description,
					"author" => $plugin->author,
					"settings" => $plugin->settings()
				);
			}
	    }
	    closedir($handle);
	}
	ksort($this->plugins);
	
	// Toggle an plugin?
	if (!empty($_GET["toggle"])) {
		$plugins = array();
		// If the plugin is in the array, take it out
		$k = array_search($_GET["toggle"], $config["loadedPlugins"]);
		if ($k !== false) unset($config["loadedPlugins"][$k]);
		// If it's not in the array, add it in
		elseif ($k === false) $config["loadedPlugins"][] = $_GET["toggle"];
		if ($this->writeLoadedPlugins($config["loadedPlugins"])) redirect("plugins");
	}
}

// Ajax - toggle an plugin
function ajax()
{
	switch ($_POST["action"]) {
		
		// Toggle an plugin
		case "toggle":
			global $config;
			// If the plugin is in the array, take it out
			$k = array_search($_POST["id"], $config["loadedPlugins"]);
			if (!$_POST["enabled"] and $k !== false) unset($config["loadedPlugins"][$k]);
			// If it's not in the array, add it in
			elseif ($k === false) $config["loadedPlugins"][] = $_POST["id"];
			$this->writeLoadedPlugins($config["loadedPlugins"]);
			break;
		
	}
}

// Write the loaded plugins file
function writeLoadedPlugins($loadedPlugins)
{
	$loadedPlugins = array_unique($loadedPlugins);
	
	// Prepare the content
	$content = "<?php\n\$config[\"loadedPlugins\"] = array(";
	foreach ($loadedPlugins as $v) {
		if (!count($this->plugins) or array_key_exists($v, $this->plugins)) $content .= "\n\"$v\",";
	}
	$content .= "\n);\n?>";
	
	// Write the file.
	if (!writeFile("config/plugins.php", $content)) {
		$this->esoTalk->message("notWritable", false, "config/plugins.php");
		return false;
	}	
	return true;
}

// Upload an plugin
function uploadPlugin()
{
	if ($_FILES["uploadPlugin"]["error"]) {
		$this->esoTalk->message("invalidPlugin");
		return false;
	}
	if (!move_uploaded_file($_FILES["uploadPlugin"]["tmp_name"], "plugins/{$_FILES["uploadPlugin"]["name"]}")) {
		$this->esoTalk->message("notWritable", false, "plugins/");
		return false;
	}
	if (!($files = unzip("plugins/{$_FILES["uploadPlugin"]["name"]}", "plugins/"))) $this->esoTalk->message("invalidPlugin");
	else {
		$directories = 0; $settingsFound = false;
		foreach ($files as $k => $file) {
			// Strip out annoying Mac OS X files
			if (substr($file["name"], 0, 9) == "__MACOSX/" or substr($file["name"], -9) == ".DS_Store") {
				unset($files[$k]);
				continue;
			}
			// If the zip has more than one base directory, don't let it pass!
			if ($file["directory"] and substr_count($file["name"], "/") < 2) $directories++;
			// Make sure there's a settings file in there
			if (substr($file["name"], -10) == "plugin.php") $pluginFound = true;
		}
		if ($pluginFound and $directories == 1) {
			$error = false;
			// Loop through files and copy them over
			foreach ($files as $k => $file) {
				// Make a directory
				if ($file["directory"] and !is_dir("plugins/{$file["name"]}")) mkdir("plugins/{$file["name"]}");
				// Write a file
				elseif (!$file["directory"]) {
					if (!writeFile("plugins/{$file["name"]}", $file["content"])) {
						$this->esoTalk->message("notWritable", false, "plugins/{$file["name"]}");
						$error = true;
						break;
					}
				}
			}
			if (!$error) $this->esoTalk->message("pluginAdded");
		} else $this->esoTalk->message("invalidPlugin");
	}
	unlink("plugins/{$_FILES["uploadPlugin"]["name"]}");
}
	
}

?>