<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Casual English language pack

$language = array(

"charset" => "utf-8",

// Added in 1.0.0b1
/*
"fatalErrorMessage" => "<p>esoTalk has encountered an nasty error which is making it impossible to do whatever it is that you're doing. But don't feel down - <strong>here are a few things you can try</strong>:</p>
<ul>
<li>Go outside, walk the dog, have a coffee... then <strong><a href='javascript:window.location.reload()'>try again</a></strong>!</li>
<li>If you are the forum administrator, then you can <strong>get help on the <a href='http://forum.esotalk.com/search?q=%s'>esoTalk forum</a></strong>.</li>
<li>Try hitting the computer - that sometimes works for me.</li>
</ul>",
*/
// "Donate to esoTalk" => "Donate to esoTalk",
// "Mark all conversations as read" => "Mark all conversations as read",
// "accountNotYetVerified" => array("class" => "info", "message" => "You need to verify your account before you can log in with it! If you didn't receive the verification email, <a href='%s'>click here to get it sent again</a>."),
// "reenterInformation" => array("class" => "info", "message" => "Please reenter this information"),
// "newSearchResults" => array("class" => "info", "message" => "There has been new activity that has affected your search results. <a href='javascript:Search.showNewActivity()'>Show new activity</a>"),
// "waitToSearch" => array("class" => "warning", "message" => "Woah! Looks like you're trying to perform a few too many searches. Just wait a few seconds and try again."),
// "Username" => "Username",
// "day ago" => "Yesterday",
// "days ago" => "%d days ago",
// "hour ago" => "1 hour ago",
// "hours ago" => "%d hours ago",
// "minute ago" => "1 minute ago",
// "minutes ago" => "%d minutes ago",
// "month ago" => "1 month ago",
// "months ago" => "%d months ago",
// "second ago" => "1 second ago",
// "seconds ago" => "%d seconds ago",
// "week ago" => "Last week",
// "weeks ago" => "%d weeks ago",
// "year ago" => "Last year",
// "years ago" => "%d years ago",
// Deleted: "helpesoTalk"
// Deleted: "Name"
// Deleted: "ago"
// Deleted: "day"
// Deleted: "days"
// Deleted: "hour"
// Deleted: "hours"
// Deleted: "minute"
// Deleted: "minutes"
// Deleted: "month"
// Deleted: "months"
// Deleted: "second"
// Deleted: "seconds"
// Deleted: "week"
// Deleted: "weeks"
// Deleted: "year"
// Deleted: "years"
// Deleted: "1 day ago"
// Deleted: "1 hour ago"
// Deleted: "1 minute ago"
// Deleted: "1 month ago"
// Deleted: "1 second ago"
// Deleted: "1 week ago"
// Deleted: "1 year ago"
// Changed: "loginRequired" => array("class" => "warning", "message" => "You need to <a href='" . curLink() . "#' onclick='showLogin();return false'>log in</a> or <a href='" . makeLink("join") . "'>create an account</a> to do anything on this forum."),
// Changed: "ajaxDisconnected" => "Unable to communicate with the server. Wait a few seconds and <a href='javascript:Ajax.resumeAfterDisconnection()'>try again</a>, or <a href='' onclick='window.location.reload();return false'>refresh the page</a>.",
// Changed: "downloadPlugins" => array("class" => "info", "message" => "You can download more plugins from the <a href='%s'>esoTalk website</a>."),
// Changed: "downloadSkins" => array("class" => "info", "message" => "You can download more skins from the <a href='%s'>esoTalk website</a>."),
// Changed: "Fatal error" => "Uh oh! It's a fatal error...",
// Changed: "notWritable" => array("class" => "warning", "message" => "<code>%s</code> is not writeable. Try <code>chmod</code>ing it to <code>777</code>, or if it doesn't exist, <code>chmod</code> the folder it is contained within."),
// Changed key: "accountInformation" -> "Account information"
// Changed key: "settingsOther" -> "Other settings"
// Changed key: "settingsPasswordEmail" -> "Account information"
// Changed key: "accountInformation" -> "Account information"

// Added in 1.0.0a5
// "Never" => "Never",
// "RSS" => "RSS",
// "forumDescription" => "%s is a web-forum discussing %s.",
// Deleted: "Feed"
// Deleted: gambits["quoted:"]
// Deleted: "Powered by esoTalk"
// Changed: "viewMore" => array("class" => "info", "message" => "Your search found more than {$config["results"]} conversations. <a href='%s' onclick='Search.viewMore();return false'>View more</a>")
// Changed: "notWritable" => array("class" => "warning", "message" => "'%s' is not writeable. Please make sure it exists and chmod it to 777.")

"*" => "*",
"day ago" => "Yesterday",
"days ago" => "%d days ago",
"hour ago" => "1 hour ago",
"hours ago" => "%d hours ago",
"minute ago" => "1 minute ago",
"minutes ago" => "%d minutes ago",
"month ago" => "1 month ago",
"months ago" => "%d months ago",
"second ago" => "1 second ago",
"seconds ago" => "%d seconds ago",
"week ago" => "Last week",
"weeks ago" => "%d weeks ago",
"year ago" => "Last year",
"years ago" => "%d years ago",
"a private conversation" => "a private conversation",
"Account information" => "Account information",
"Add a new plugin" => "Add a new plugin",
"Add a new skin" => "Add a new skin",
"Add plugin" => "Add plugin",
"Add member" => "Add",
"Add skin" => "Add skin",
"Administrator" => "Administrator",
"Administrator-plural" => "Administrators",
"Appearance settings" => "Appearance settings",
"Bold" => "Bold",
"Cancel" => "Cancel",
"Change avatar" => "Change avatar",
"Change password" => "Change password",
"Check for updates" => "Check for updates",
"check them out" => "check them out!",
"Confirm password" => "Confirm password",
"Conversation" => "Conversation",
"conversations" => "conversations",
"Conversations participated in" => "Conversations participated in",
"Conversations started" => "Conversations started",
"delete" => "delete",
"Delete conversation" => "Delete conversation",
"deleted by" => "deleted by %s",
"disableJSEffects" => "Disable JavaScript effects and animations",
"Discard draft" => "Discard draft",
"Display avatars" => "Display avatars",
"do not display avatars" => "do not display avatars",
"Donate to esoTalk" => "Donate to esoTalk",
"edit" => "edit",
"edited by" => "edited by",
"Email" => "Email",
"emailOnPrivateAdd" => "Email me when I'm added to a private conversation",
"emailOnStar" => "Email me when someone posts in a conversation I have starred",
"Enter a conversation title" => "Enter a conversation title",
"Enter the web address of an avatar" => "Enter the web address of an avatar",
"Enter your email" => "Enter your email",
"Everyone" => "Everyone",
"exampleTags" => "ex. movies, winter olympics, cooking",
"Fatal error" => "Uh oh! It's a fatal error...",
"fatalErrorMessage" => "<p>esoTalk has encountered an nasty error which is making it impossible to do whatever it is that you're doing. But don't feel down - <strong>here are a few things you can try</strong>:</p>
<ul>
<li>Go outside, walk the dog, have a coffee... then <strong><a href='javascript:window.location.reload()'>try again</a></strong>!</li>
<li>If you are the forum administrator, then you can <strong>get help on the <a href='http://forum.esotalk.com/search?q=%s'>esoTalk forum</a></strong>.</li>
<li>Try hitting the computer - that sometimes works for me.</li>
</ul>",
"First" => "First",
"First posted" => "First posted",
"Fixed" => "Fixed",
"Forgot your password" => "Forgot your password?",
"Forum language" => "Forum language",
"forumDescription" => "%s is a web-forum discussing %s.",
"go to this post" => "go to this post",
"Header" => "Header",
"hide" => "hide",
"Home" => "Home",
"hour" => "hour",
"hours" => "hours",
"Image" => "Image",
"Installed plugins" => "Installed plugins",
"Installed skins" => "Installed skins",
"Italic" => "Italic",
"Join this forum" => "Join this forum!",
"Jump to last" => "Jump to last",
"Jump to unread" => "Jump to unread",
"Just now" => "Just now",
"labels" => array(
	"sticky" => "Sticky",
	"private" => "Private",
	"draft" => "Draft",
	"locked" => "Locked"
),
"Labels" => "Labels",
"Last" => "Last",
"Last active" => "Last active",
"Last reply" => "Last reply",
"let's see" => "let's see!",
"Link" => "Link",
"Loading" => "Loading...",
"Lock" => "Lock",
"Log in" => "Log in",
"Log out" => "Log out",
"Mark all conversations as read" => "Mark all conversations as read",
"Member" => "Member",
"member online" => "<a href='" . makeLink("online") . "'>member online</a>",
"Member-plural" => "Members",
"Members allowed to view this conversation" => "Members allowed to view this conversation",
"members online" => "<a href='" . makeLink("online") . "'>members online</a>",
"Moderator" => "Moderator",
"Moderator-plural" => "Moderators",
"My profile" => "My profile",
"My settings" => "My settings",
"Never" => "Never",
"New email" => "New email",
"New password" => "New password",
"Next" => "Next &#155;",
"No avatar" => "No avatar",
"No preview" => "No preview",
"on alternating sides" => "on alternating sides",
"on the left" => "on the left",
"on the right" => "on the right",
"online" => "online",
"optional" => "(optional)",
"Other settings" => "Other settings",
"Password" => "Password",
"Permalink to this post" => "Permalink to this post",
"Plugins" => "Plugins",
"Preview" => "Preview",
"Previous" => "&#139; Previous",
"Post a reply" => "Post a reply",
"Post count" => "Post count",
"posts" => "posts",
"Posts" => "Posts",
"post per day" => "that's about 1 post per day",
"posts per day" => "that's about %s posts per day",
"quote" => "quote",
"Quote" => "\"Quote\"",
"Recent posts" => "Recent posts",
"Recover password" => "Get me a new password!",
"Remember me" => "Remember me",
"restore" => "restore",
"RSS" => "RSS",
"Save changes" => "Save changes",
"Save draft" => "Save draft",
"Save post" => "Save post",
"Search" => "Search!",
"See the private conversations I've had" => "See the private conversations I've had with %s",
"settings" => "settings",
"Change your password or email" => "Change your password or email",
"show" => "show",
"Skin" => "Skin",
"Skins" => "Skins",
"Starred" => "Starred",
"Start a conversation" => "Start a conversation",
"Start a private conversation" => "Start a private conversation with %s",
"Started by" => "Started by",
"Starting a conversation" => "Starting a conversation",
"Sticky" => "Sticky",
"Strike" => "Strike",
"Submit post" => "Submit post!",
"Suspended" => "Suspended",
"Tags" => "Tags",
"Unlock" => "Unlock",
"unread" => "unread",
"Unstarred" => "Unstarred",
"Unsticky" => "Unsticky",
"Untitled conversation" => "Untitled conversation",
"Unvalidated" => "Unvalidated",
"Upload an avatar" => "Upload an avatar from your computer",
"Upload an plugin" => "Upload a plugin package",
"Upload a skin" => "Upload a skin package",
"Username" => "Username",
"Viewing" => "Viewing:",
"viewingPosts" => "<b>%s-%s</b> of %s posts",
"Who's online" => "Who's online?",
"Your current password" => "Your current password",

"emails" => array(

"forgotPassword" => array(
"subject" => "Did you forget your password, %s?",
"body" => "%s, some one (hopefully you!) has submitted a forgotten password request for your account on the forum '%s'. If you do not wish to change your password, just ignore this email and nothing will happen.\n\nHowever, if you did forget your password and wish to set a new one, visit the following link:\n%s"),

"join" => array(
"subject" => "%s, please validate your account",
"body" => "%s, someone (hopefully you!) has signed up to the forum '%s' with this email address.\n\nIf this was you, simply visit the following link and your account will be activated:\n%s"),

"privateAdd" => array(
"subject" => "%s, you have been added to a private conversation",
"body" => "%s, you have been added to a private conversation titled '%s'.\n\nTo view this conversation, check out the following link:\n%s"),

"newReply" => array(
"subject" => "%s, there is a new reply to '%s'",
"body" => "%s, %s has replied to a conversation which you starred: '%s'.\n\nTo view the new activity, check out the following link:\n%s")
),

"confirmLeave" => "Woah, you haven't saved the stuff you are editing! If you leave this page, you'll lose any changes you've made. Is this ok?",
"confirmDiscard" => "You have not saved your reply as a draft. Do you wish to discard it?",
"confirmDeleteConversation" => "Are you sure you want to delete this conversation? Seriously, you won't be able to get it back.",
"ajaxRequestPending" => "Hey! We're still processing some of your stuff! If you navigate away from this page you might lose any recent changes you've made, so wait a few seconds, ok?",
"ajaxDisconnected" => "Unable to communicate with the server. Wait a few seconds and <a href='javascript:Ajax.resumeAfterDisconnection()'>try again</a>, or <a href='' onclick='window.location.reload();return false'>refresh the page</a>.",
);

$language["gambits"] = array(

// Translating the gambit system can be quite complex, but we'll do our best to get you through it. :)
// Note: Don't use any html entities in these definitions, except for: &lt; &gt; &amp; &#39; 

// Simple gambits
// These gambits are pretty much evaluated as-they-are.
// tag:, author:, contributor:, and quoted: are combined with a value after the colon (:).
// For example: tag:video games, author:myself
"tag:" => "tag:",
"author:" => "author:",
"contributor:" => "contributor:",
"member" => "member",
"myself" => "myself",
"draft" => "draft",
"has attachments" => "has attachments",
"locked" => "locked",
"order by newest" => "order by newest",
"order by posts" => "order by posts",
"private" => "private",
"random" => "random",
"reverse" => "reverse",
"starred" => "starred",
"sticky" => "sticky",
"unread" => "unread",
"more results" => "more results",

// Aliases
// These are gambits which tell the gambit system to use another gambit.
// In other words, when you type "active today", the gambit system interprets it as if you typed "active 1 day".
// The first of each pair, the alias, can be anything you want.
// The second, however, must fit with the regular expression pattern defined below (more on that later.)
"active today" => "active today", // what appears in the gambit cloud
"active 1 day" => "active 1 day", // what it actually evaluates to

"has replies" => "has replies",
"has &gt; 1 post" => "has &gt; 1 post",

"has no replies" => "has no replies",
"has 0 posts" => "has 0 posts",

"dead" => "dead",
"active &gt; 30 day" => "active &gt; 30 day",

// Units of time
// These are used in the active gambit.
// ex. "[active] [>|<|>=|<=|last] 180 [second|minute|hour|day|week|month|year]"
"second" => "second",
"minute" => "minute",
"hour" => "hour",
"day" => "day",
"week" => "week",
"month" => "month",
"year" => "year",
"last" => "last", // as in "active last 180 days"
"active" => "active" // as in "active last 180 days"
);

$language["gambits"] += array(

// Now the hard bit. This is a regular expression to test for the "active" gambit.
// The group (?<a> ... ) is the comparison operator (>, <, >=, <=, or last).
// The group (?<b> ... ) is the number (ex. 24).
// The group (?<c> ... ) is the unit of time.
// The languages of "last" and the units of time are defined above.
// However, if you need to reorder the groups, do so carefully, and make sure spaces are written as " *".
"gambitActive" => "/^{$language["gambits"]["active"]} *(?<a>&gt;|&lt;|&gt;=|&lt;=|{$language["gambits"]["last"]})? *(?<b>\d+) *(?<c>{$language["gambits"]["second"]}|{$language["gambits"]["minute"]}|{$language["gambits"]["hour"]}|{$language["gambits"]["day"]}|{$language["gambits"]["week"]}|{$language["gambits"]["month"]}|{$language["gambits"]["year"]})/",

// These appear in the tag cloud. They must fit the regular expression pattern where the ? is a number.
// If the regular expression pattern has been reordered, these gambits must also be reordered (as well as the ones in aliases.)
"active last ? hours" => "{$language["gambits"]["active"]} {$language["gambits"]["last"]} ? {$language["gambits"]["hour"]}s",
"active last ? days" => "{$language["gambits"]["active"]} {$language["gambits"]["last"]} ? {$language["gambits"]["day"]}s",

// This is similar to the regular expression for the active gambit, but for the "has n post(s)" gambit.
// Usually you just need to change the "has" and "post".
"gambitHasNPosts" => "/^has *(?<a>&gt;|&lt;|&gt;=|&lt;=)? *(?<b>\d+) *post/",

// This goes by the same rules as "active last ? hours" and "active last ? days".
"has &gt;10 posts" => "has &gt;10 posts"

);

$messages = array(
"incorrectLogin" => array("class" => "warning", "message" => "Your login details were incorrect. <a href='" . makeLink("forgot-password") . "'>Have you forgotten your password?</a>"),
"beenLoggedOut" => array("class" => "warning", "message" => "Oops! You seem to have been <strong>logged out</strong> since you loaded this page. Please reenter your password below or press <strong>cancel</strong> to ignore this message.<br/><br/>
Enter the password for <strong>%s</strong>: %s"),
"accountNotYetVerified" => array("class" => "info", "message" => "You need to verify your account before you can log in with it! If you didn't receive the verification email, <a href='%s'>click here to get it sent again</a>."),
"changesSaved" => array("class" => "success", "message" => "Your changes were saved."),
"memberDoesntExist" => array("class" => "warning", "message" => "No member with that name exists."),
"conversationDeleted" => array("class" => "success", "message" => "The conversation was deleted."),
"emptyPost" => array("class" => "warning", "message" => "Yeah... uh, you should probably type something in your post."),
"noPermission" => array("class" => "warning", "message" => "Bad user! You do not have permisssion to perform this action."),
"emptyTitle" => array("class" => "warning", "message" => "The title of your conversation can't be blank. I mean, how can anyone click on a blank title? Think about it."),
"verifyEmail" => array("class" => "success", "message" => "Before you can start using your newly-created account, you'll need to verify your email address. Within the next minute or two you should receive an email from us containing a link to activate your account. <strong>Check your spam folder</strong> if you don't receive this email shortly!"),
"loginRequired" => array("class" => "warning", "message" => "You need to <a href='" . curLink() . "#' onclick='showLogin();return false'>log in</a> or <a href='" . makeLink("join") . "'>create an account</a> to do anything on this forum."),
"postTooLong" => array("class" => "warning", "message" => "Your post is really, really long! Too long! The maximum number of characters allowed is " . number_format($config["maxCharsPerPost"]) . ". That's really long!"),
"waitToReply" => array("class" => "warning", "message" => "You must wait at least {$config["timeBetweenPosts"]} seconds between replying to conversations. Take a deep breath and try again."),
"waitToSearch" => array("class" => "warning", "message" => "Woah! Looks like you're trying to perform a few too many searches. Wait %s seconds and try again."),
"suspended" => array("class" => "warning", "message" => "Ouch! A forum moderator has <strong>suspended</strong> your account. It sucks, but until the suspension is lifted you won't be able to do much around here. Hey, screw them!"),
"locked" => array("class" => "warning", "message" => "Hm, looks like this conversation is <strong>locked</strong>, so you can't reply to it."),
"cannotViewConversation" => array("class" => "warning", "message" => "For some reason this conversation cannot be viewed. Maybe it's been deleted? Or maybe it's a private conversation, in which case you might not be logged in or you might not be invited. Oh man, I hope they're not talking about you behind your back!"),
"emailDoesntExist" => array("class" => "warning", "message" => "That email address doesn't match any members in the database. Did you make a typo?"),
"passwordEmailSent" => array("class" => "success", "message" => "Ok, we've sent you an email containing a link to reset your password. Check your spam folder if you don't receive it within the next minute or two. Yeah, some times we get put through to spam - can you believe it?!"),
"passwordChanged" => array("class" => "success", "message" => "Your password has been changed. You may now log in with your new password."),
"reenterInformation" => array("class" => "info", "message" => "Please reenter this information"),
"accountValidated" => array("class" => "success", "message" => "Cool! Your account has been validated and you may now start participating in conversations. Why not <a href='" . makeLink("new") . "'>start one</a> yourself?"),
"avatarError" => array("class" => "warning", "message" => "There was a problem uploading your avatar. Make sure you're using a valid image type (like .jpg, .png, or .gif) and the file isn't really really huge."),
"forgotPassword" => array("class" => "info", "message" => "If you've forgotten your password, we'll send you a link to a page where you can choose a new one. Just enter your email address (and check your spam folder if the email doesn't arrive within the next minute or two)."),
"setNewPassword" => array("class" => "info", "message" => "Well done! What do you want your new password to be?"),
"passwordTooShort" => array("class" => "warning", "message" => "Your password must be at least {$config["minPasswordLength"]} characters"),
"invalidEmail" => array("class" => "warning", "message" => "Seems this email address isn't valid..."),
"emailTaken" => array("class" => "warning", "message" => "Curses, there is already a member with this email!"),
"noSearchResults" => array("class" => "warning", "message" => "No conversations matching your search were found."),
"viewMore" => array("class" => "info", "message" => "Your search found more than {$config["results"]} conversations. <a href='%s' onclick='Search.viewMore();return false'>View more</a>"),
"newSearchResults" => array("class" => "info", "message" => "There has been new activity that has affected your search results. <a href='javascript:Search.showNewActivity()'>Show new activity</a>"),
"passwordsDontMatch" => array("class" => "warning", "message" => "Your passwords do not match"),
"emailInfo" => array("class" => "info", "message" => "Used to verify your account and subscribe to conversations"),
"passwordInfo" => array("class" => "info", "message" => "Choose a secure password of at least {$config["minPasswordLength"]} characters"),
"nameTaken" => array("class" => "warning", "message" => "The name you have entered is taken or is a reserved word"),
"nameEmpty" => array("class" => "warning", "message" => "You must enter a name!"),
"invalidCharacters" => array("class" => "warning", "message" => "You can't use any of these characters in your name: ! / % + -"),
"incorrectPassword" => array("class" => "warning", "message" => "Your current password is incorrect"),
"notWritable" => array("class" => "warning", "message" => "<code>%s</code> is not writeable. Try <code>chmod</code>ing it to <code>777</code>, or if it doesn't exist, <code>chmod</code> the folder it is contained within."),
"lockedButCanReply" => array("class" => "info", "message" => "This conversation is <strong>locked</strong>, but you can still reply because you are <strong>awesome</strong>. (And also because you are a moderator or administrator.)"),
"noPluginsInstalled" => array("class" => "warning", "message" => "No plugins are currently installed."),
"invalidPlugin" => array("class" => "warning", "message" => "The plugin you uploaded is not valid."),
"pluginAdded" => array("class" => "success", "message" => "The plugin was successfully added!"),
"noSkinsInstalled" => array("class" => "warning", "message" => "No skins are currently installed."),
"invalidSkin" => array("class" => "warning", "message" => "The skin you uploaded is not valid."),
"skinAdded" => array("class" => "success", "message" => "The skin was successfully added!"),
"downloadPlugins" => array("class" => "info", "message" => "You can download more plugins from the <a href='%s'>esoTalk website</a>."),
"downloadSkins" => array("class" => "info", "message" => "You can download more skins from the <a href='%s'>esoTalk website</a>."),
"updatesAvailable" => array("class" => "info", "message" => "A new version of esoTalk (<strong>%s</strong>) is available for download. You have version {$versions["esoTalk"]}. <strong><a href='http://get.esotalk.com/'>Get it now</a></strong>!"),
"noMembersOnline" => array("class" => "warning", "message" => "No members are currently online.")
);

?>