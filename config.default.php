<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Default configuration: This file will get overwritten with every esoTalk update, so do not edit it.
// If you wish the change a config setting, copy it into config/config.php and change it there.

if (!defined("IN_ESOTALK")) exit;

define("ESOTALK_VERSION", "1.0.0b1");

$defaultConfig = array(
// This following block is filled out by the installer in config/config.php.
"mysqlHost" => "",
"mysqlUser" => "",
"mysqlPass" => "",
"mysqlDB" => "",
"tablePrefix" => "",
"forumTitle" => "",
"baseURL" => "",
"rootAdmin" => 1,
"salt" => "",
"emailFrom" => "",
"cookieName" => "",

"language" => "English (casual)", // The default language.
"forumLogo" => false, // Path to an image file to replace the esoTalk logo (don't make it too big or it'll stretch the header!)
"sitemapCacheTime" => 3600, // Keep sitemaps for at least 1 hour.
"verboseFatalErrors" => false, // Show MySQL error information in fatal errors. Enable this if you need to debug a situation.
"basePath" => "", // The base path to use when including or writing to any files.

"useFriendlyURLs" => false, // ex. example.com/index.php/conversation/1
"useModRewrite" => false, // ex. example.com/conversation/1 (requires mod_rewrite and a .htaccess file!)
"skin" => "Plastic", // The default skin (overridden by config/skin.php.)
"minPasswordLength" => 6,
"cookieExpire" => 2592000, // 30 days
"userOnlineExpire" => 300, // Number of seconds a user's 'last seen time' is before the user 'goes offline'.
"messageDisplayTime" => 20, // Number of seconds before most messages up the top of the screen disappear.

"results" => 20, // Number of conversations to list for a normal search.
"moreResults" => 100, // Total number of conversations to list when 'more results' is clicked.
"numberOfTagsInTagCloud" => 40, // Number of tags to show in the tag cloud.
"showAvatarThumbnails" => true, // Whether or not to show avatar thumbnails next to each conversation.
"updateCurrentResultsInterval" => 30, // Number of seconds at which to automatically update the unread status, post count, and last post information for currently listed conversations in a search.
"checkForNewResultsInterval" => 60, // Number of seconds at which to automatically check for new conversations in a search and notify the user so they can reperform their search.
"searchesPerMinute" => 6, // Users are limited to this many searches every minute. 

"postsPerPage" => 20, // The maximum number of posts to display on each page of a conversation.
"timeBetweenPosts" => 10, // Posting flood control, in seconds.
"maxCharsPerPost" => 50000,
"autoReloadIntervalStart" => 4, // The initial number of seconds before checking for new posts on the conversation view.
"autoReloadIntervalMultiplier" => 1.5, // Each time we check for new posts and there are none, multiply the number of seconds by this.
// ex. after 4 seconds, check for new posts. If there are none: after 4*1.5 = 6 seconds check for new posts. If there are none: after 6*1.5 = 9 seconds check for new posts...
"autoReloadIntervalLimit" => 512, // The maximum number of seconds between checking for new posts. 

// Avatar dimensions (in pixels)
"avatarMaxWidth" => 100,
"avatarMaxHeight" => 100,
"avatarThumbWidth" => 32,
"avatarThumbHeight" => 32,
"avatarAlignment" => "alternate", // alternate, left, right, or none (individual users can override this setting)
);

?>