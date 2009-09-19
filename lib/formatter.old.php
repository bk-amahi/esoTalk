<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Formatter class

if (!defined("IN_ESOTALK")) exit;

class Formatter {
	
var $formatters = array();
	
var $defaultFormatters = array("blockCode", "inlineCode", "quote", "image", "links", "bold", "italic", "strikethrough", "header", "orderedList", "unorderedList", "whitespace");

function addFormatter($name, $formatForDisplayFunction, $formatForEditingFunction, $enabledByDefault = true)
{
	if ($enabledByDefault) $this->defaultFormatters[] = $name;
	$this->formatters[$name] = array(
		"formatForDisplay" => $formatForDisplayFunction,
		"formatForEditing" => $formatForEditingFunction
	);
}

function formatForEditing($string)
{
	foreach ($this->formatters as $k => $v) {
		call_user_func_array($v["formatForEditing"], array(&$string));
	}
	
	$translations = array(
		"<br/>" => "\n",
		"<p>" => "",
		"</p>" => "\n\n",
		"<pre>" => "[fixed]",
		"</pre>" => "[/fixed]\n\n",
		"<code>" => "[fixed]",
		"</code>" => "[/fixed]",
		"<b>" => "&#39;&#39;&#39;",
		"</b>" => "&#39;&#39;&#39;",
		"<i>" => "&#39;&#39;",
		"</i>" => "&#39;&#39;",
		"<h3>" => "===",
		"</h3>" => "===\n\n"
	);

	// Translate html to formatting characters
	$string = strtr($string, $translations);

	// Quotes
	while (preg_match("/<blockquote>.*?<\/blockquote>/s", $string))
		$string = preg_replace("/(.*?)<blockquote>(?:<cite>(.+?)<\/cite>)?\n*(.*?)\n*<\/blockquote>/ise", "'$1[quote' . ('$2' ? ':$2' : '') . ']$3[/quote]\n\n'", $string);

	// Strikethrough
	$string = preg_replace("/<del>(.*?)<\/del>/", "---$1---", $string);

	// Images
	$string = preg_replace("/<img src='(.*?)'.+?\/>/", "[image:$1]", $string);

	// Ordered lists
	$orderedList = create_function('$list', '
		$list = str_replace("</li>", "\n", $list);
		for ($i = 1; strpos($list, "<li>") !== false; $i++)
			$list = substr_replace($list, "$i. ", strpos($list, "<li>"), 4);
		return "$list\n";');
	$string = preg_replace("/<ol>(.*?)<\/ol>/es", "\$orderedList('$1')", $string);

	// Unordered lists
	$string = preg_replace("/<ul>(.*?)<\/ul>/es", "strtr('$1', array('</li>' => '\n', '<li>' => '- ')) . '\n'", $string);

	// Emails and links
	$string = preg_replace("/<a href='mailto:(.*?)'>\\1<\/a>/", "$1", $string);
	$string = preg_replace("`<a href='" . str_replace("?", "\?", makeLink("(post|conversation)", "(\d+)")) . "'[^>]*>(.*?)<\/a>`", "[$1:$2 $3]", $string);
	$string = preg_replace("/<a href='(.*?)'>(.*?)<\/a>/e",
		"('$1' == '$2' or preg_replace('/^(\w+:\/\/)/', '', '$1') == '$2') ? '$2' : '[$1 $2]'", $string);

	// Trim the right edge of whitespace
	$string = trim($string);

	return $string;
}

// Format a post for display
function formatForDisplay($string, $enabledFormatters = false)
{
	$formatters = is_array($enabledFormatters) ? $enabledFormatters : $this->defaultFormatters;
	
	// Convert all \r into \n
	$string = strtr($string, array("\r\n" => "\n", "\r" => "\n"));
	
	// Empty all [fixed] tags into an array so as to prevent any formatting being applied to them.
	// Block-level [fixed] tags become <pre>.
	if (in_array("blockCode", $formatters)) {
		$this->blockFixedContents = array();
		$hideFixed = create_function('&$formatter, $contents', '
			$formatter->blockFixedContents[] = $contents;
			return "</p><pre></pre><p>";');
		$regexp = "/(.*)^\[(fixed|code)\]\n?(.*?)\n?\[\/\\2]$/imse";
		while (preg_match($regexp, $string)) $string = preg_replace($regexp, "'$1' . \$hideFixed(\$this, '$3')", $string);
	}
	// Inline-level [fixed] tags become <code>.
	if (in_array("inlineCode", $formatters)) {
		$this->inlineFixedContents = array();
		$hideFixed = create_function('&$formatter, $contents', '
			$formatter->inlineFixedContents[] = $contents;
			return "<code></code>";');
		$string = preg_replace("/\[(fixed|code)\]\n?(.*?)\n?\[\/\\1]/ise", "\$hideFixed(\$this, '$2')", $string);
	}
	
	if (in_array("bold", $formatters)) {
		$string = preg_replace(array("/&lt;b&gt;(.*?)&lt;\/b&gt;/si", "/&lt;strong&gt;(.*?)&lt;\/strong&gt;/si", "/\[b\](.*?)\[\/b\]/si", "/(?:&#39;){3}(.*?)(?:&#39;){3}/s"), "<b>$1</b>", $string);
	}
	
	if (in_array("italic", $formatters)) {
		$string = preg_replace(array("/&lt;i&gt;(.*?)&lt;\/i&gt;/si", "/&lt;em&gt;(.*?)&lt;\/em&gt;/si", "/\[i\](.*?)\[\/i\]/si", "/(?:&#39;){2}(.*?)(?:&#39;){2}/s"), "<i>$1</i>", $string);
	}
	
	if (in_array("header", $formatters)) {
		$string = preg_replace(array("/&lt;h1&gt;(.*?)&lt;\/h1&gt;/si", "/&lt;h2&gt;(.*?)&lt;\/h2&gt;/si", "/\[h\](.*?)\[\/h\]/si", "/===(.*?)===/s"), "</p><h3>$1</h3><p>", $string);
	}
	
	if (in_array("strikethrough", $formatters)) {
		$string = preg_replace(array("/&lt;s&gt;(.*?)&lt;\/s&gt;/si", "/&lt;del&gt;(.*?)&lt;\/del&gt;/si", "/\[s\](.*?)\[\/s\]/si", "/---(.*?)---/s"), "<del>$1</del>", $string);
	}
	
	
	if (in_array("links", $formatters)) {
		
		// Post and conversation links - [post:816 text] or [conversation:123 text]
		$string = preg_replace("/\[(post|conversation|profile):(\d+) +(.+?)\]/ie", "'<a href=\'' . makeLink('$1', '$2') . '\' class=\'$1Link\'>$3</a>'", $string);

		// Links with display text - [x y]
		$string = preg_replace(array("/()\[url=(\w{2,6}:\/\/|mailto:)?([^\]]*?)\](.*?)\[\/url\]/ie", "/()\[(\w{2,6}:\/\/|mailto:)?([\w&()~%=+#?\-:\/]+?\.[^\s]+?|localhost[^\s]*?) +(.+?)\]/ie", "/&lt;a href=(&#39;|&quot;|)(\w{2,6}:\/\/|mailto:)?(.*?)\\1&gt;(.*?)&lt;\/a&gt;/ie"), "'<a href=\'' . ('$2' ? '$2' : 'http://') . '$3\'>$4</a>'", $string);

		// Email links
		$string = preg_replace("/(?<=\s|^|>)[\w-\.]+@([\w-]+\.)+[\w-]{2,4}/i", "<a href='mailto:$0'>$0</a>", $string);

		// Normal links - http://www.example.com, www.example.com
		$string = preg_replace(
				"/(?<=\s|^|>|\()(\w{2,6}:\/\/)?((?:[\w\-]+\.)+(?:ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|asia|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|je|jm|jo|jobs|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mil|mk|ml|mm|mn|mo|mobi|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tel|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)(\W[^\s<]*?)??)(?=[\s\.,?!><]*(?:[\s><]|$)|&#39;)/ie",
			"\$this->parseLink(('$1' ? '$1' : 'http://') . '$2', '$0')", $string);
						
	}
	
	if (in_array("image", $formatters)) {
		$string = preg_replace(array("/&lt;img +src=(&#39;|&quot;|)((?:\w+:\/\/)[^\s]+?)\\1(?:\s+alt=(&#39;|&quot;|)(.*?)\\3)?(?:\s+title=(&#39;|&quot;|)(.*?)\\5)?\s*\/?&gt;/ie", "/()\[(?:image|img)(?::|=)((?:\w+:\/\/)[^\s]+?)\]/ie", "/()\[img\]((?:\w+:\/\/)[^\s]+?)\[\/img\]/ie"), "'<img src=\'$2\' alt=\'' . ('$4' ? '$4' : '-image-') . '\'' . ('$6' ? ' title=\'$6\'' : '') . '/>'", $string);
	}
	
	if (in_array("quote", $formatters)) {
		$regexp = "/(.*?)\n?\[quote(?:(?::|=)(.*?))?\]\n?(.+?)\n?\[\/quote\]\n{0,2}/ise";
		while (preg_match($regexp, $string)) {
			$string = preg_replace($regexp,
				"'$1</p><blockquote>' . ('$2' ? '<p><cite>$2</cite></p>' : '') . '<p>$3</p></blockquote><p>'", $string);
		}
		
		$regexp = "/(.*?)\n?&lt;blockquote&gt;(?:\s*&lt;cite&gt;(.*?)&lt;\/cite&gt;)?\n?(.+?)\n?&lt;\/blockquote&gt;\n{0,2}/ise";
		while (preg_match($regexp, $string)) {
			$string = preg_replace($regexp,
				"'$1</p><blockquote>' . ('$2' ? '<p><cite>$2</cite></p>' : '') . '<p>$3</p></blockquote><p>'", $string);
		}
	}

	// Unordered lists
	if (in_array("unorderedList", $formatters)) {
		$string = $this->processLists($string);
	}
	
	if (in_array("orderedList", $formatters)) {
	//	$string = $this->processOrderedList($string, 0);
	}
	
	$string = str_replace("</li>\n", "</li>", $string);
	
	
	foreach ($this->formatters as $k => $v) {
		if (in_array($k, $formatters)) call_user_func_array($v["formatForDisplay"], array(&$string));
	}

	// Trim the edges of whitespace
	$string = trim($string);

	if (in_array("whitespace", $formatters)) {
		// Add paragraphs and breakspaces
		$string = "<p>" . strtr($string, array("\n\n" => "</p><p>", "\n" => "<br/>")) . "</p>";
		// Strip empty paragraphs
		$string = preg_replace(array("/<p>\s*<\/p>/i", "/(?<=<p>)\s*(?:<br\/>)*/i", "/\s*(?:<br\/>)*\s*(?=<\/p>)/i"), "", $string);
		$string = str_replace("<p></p>", "", $string);
	}
	
	if (in_array("inlineCode", $formatters)) {
		// Retrieve the contents of the inline <code> tags from the array in which they are stored.
		$string = preg_replace("/<code><\/code>/ie", "'<code>' . array_shift(\$this->inlineFixedContents) . '</code>'", $string);
	}
	if (in_array("blockCode", $formatters)) {
		// Retrieve the contents of the block <pre> tags from the array in which they are stored.
		$string = preg_replace("/<pre><\/pre>/ie", "'<pre>' . array_pop(\$this->blockFixedContents) . '</pre>'", $string);
	}

	//return sanitize($string);
	return $string;
}

// Convert a link (e.g. http://test.com) into a html link (<a href='http://test.com'>http://test.com</a>).
function parseLink($link, $text)
{
	$after = "";
	// If the last character is a ), and there are more ) than ( in the link, drop a ) off of the end.
	if ($link[strlen($link) - 1] == ")") {
		if (substr_count($link, "(") < substr_count($link, ")")) {
			$link = substr($link, 0, strlen($link) - 1);
			$text = substr($text, 0, strlen($text) - 1);
			$after = ")";
		}
	}
	return "<a href='$link'>$text</a>$after";
}

function test($list)
{
	while (preg_match("/^((?<=^|\n)( +)[-*] *[^\n]+(\n +[^-*\n]+)*(\n|$))+$/", $list)) {
		$list = preg_replace("/(?<=^|\n) ( *)(?=[-*])/", "$1", $list);
	}
	$list = preg_replace("/(?<=^|\n)[-*] *([^\n]+(\n +[^-*\n]+)*)(\n|$)/e", "'<li>' . (strpos('$1', '</p>') ? '<p>' : '') . preg_replace('/\n +\n/', '\n\n', '$1') . (strpos('$1', '</p>') ? '</p>' : '') . '</li>\n'", rtrim($list));
	$list = $this->processLists($list);
	return $list;
}

function processLists($string)
{
	$string = preg_replace(
		"/((?<=^|\n) *[-*] *[^\n]+(\n +[^-*\n]+)*(\n|$))+/e",
		"'</p><ul>' . \$this->test('$0') . '</ul><p>'",
		$string);
	$string = preg_replace(
		"/((?<=^|\n|<p>) *(?:[0-9][\.)]|#) *[^\n]+(\n +[^0-9#\n]+)*(\n|$))+/e",
		"'</p><ol>' . \$this->test2('$0') . '</ol><p>'",
		$string);
	return $string;
}

function test2($list)
{
	while (preg_match("/^((?<=^|\n)( +)(?:[0-9][\.)]|#) *[^\n]+(\n +[^0-9#\n]+)*(\n|$))+$/", $list)) {
		$list = preg_replace("/(?<=^|\n) ( *)(?=[0-9#])/", "$1", $list);
	}
	$list = preg_replace("/(?<=^|\n)(?:[0-9][\.)]|#) *([^\n]+(\n +[^0-9#\n]+)*)(\n|$)/e", "'<li>' . (strpos('$1', '</p>') ? '<p>' : '') . preg_replace('/\n +\n/', '\n\n', '$1') . (strpos('$1', '</p>') ? '</p>' : '') . '</li>\n'", rtrim($list));
	$list = $this->processLists($list);
	return $list;
}


}

?>