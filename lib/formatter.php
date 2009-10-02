<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Formatter class

if (!defined("IN_ESOTALK")) exit;

require_once "lexer.php";

class Formatter extends Hookable {

var $output = "";

var $modes = array();

var $allowedModes = array(
	
	// Modes in which inline-level formatting (bold, italic, links, etc.) can be applied.
	"inline" => array("text", "quote", "cite", "list", "heading", "italic", "bold", "strike", "link", "superscript", "subscript"),
	
	// Modes in which paragraph-level formatting (whitespace and images) can be applied.
	"whitespace" => array("text", "quote", "list", "italic", "bold", "strike"),
	
	// Modes in which block-level formatting (headings, lists, quotes, etc.) can be applied.
	"block" => array("text", "quote")
	
);

function Formatter()
{
	// Set up the lexer.
	$this->lexer = &new SimpleLexer($this, "text", true);
	
	// Define the modes.
	$this->modes = array(
		"bold" => new Formatter_Bold($this),
		"italic" => new Formatter_Italic($this),
		"heading" => new Formatter_Heading($this),
		"superscript" => new Formatter_Superscript($this),
		"strikethrough" => new Formatter_Strikethrough($this),
		"link" => new Formatter_Link($this),
		"image" => new Formatter_Image($this),
		"list" => new Formatter_List($this),
		"quote" => new Formatter_Quote($this),
		"fixedBlock" => new Formatter_Fixed_Block($this),
		"fixedInline" => new Formatter_Fixed_Inline($this),
		"horizontalRule" => new Formatter_Horizontal_Rule($this),
		"specialCharacters" => new Formatter_Special_Characters($this),
		"whitespace" => new Formatter_Whitespace($this)
	);
}

function addFormatter($name, $class)
{
	$this->modes[$name] = new $class($this);
}

function format($string, $formatters = false)
{
	$this->output = "";
	
	// Work out which formatters are going to be used.
	if (is_array($formatters)) $formatters = array_intersect(array_keys($this->modes), $formatters);
	else $formatters = array_keys($this->modes);
	
	// Clean up newline characters - make sure the only ones we are using are \n!
	$string = strtr($string, array("\r\n" => "\n", "\r" => "\n")) . "\n";
	
	// Set up the lexer with all of the different formatting modes.
	foreach ($formatters as $v) {
		if (method_exists($this->modes[$v], "format")) $this->modes[$v]->format();
	}
	
	// Run the lexer!
	$this->lexer->parse($string);
	
	// Run any post-formatting actions.
	foreach ($formatters as $v) {
		if (method_exists($this->modes[$v], "finish")) $this->output = $this->modes[$v]->finish($this->output);
	}

	return $this->output;
}

function revert($string, $formatters = false)
{
	// Work out which formatters are going to be used.
	if (is_array($formatters)) $formatters = array_intersect(array_keys($this->modes), $formatters);
	else $formatters = array_keys($this->modes);
	
	// Collect simple reversion patterns from each of the individual formatters.
	$translations = array();
	foreach ($formatters as $v) {
		if (isset($this->modes[$v]->revert) and is_array($this->modes[$v]->revert)) $translations += $this->modes[$v]->revert;
	}
	$string = strtr($string, $translations);

	// Run any more complex reversions.
	foreach ($formatters as $v) {
		if (method_exists($this->modes[$v], "revert")) $string = $this->modes[$v]->revert($string);
	}
	
	$string = rtrim($string);
	return $string;
}

function display($string, $formatters = false)
{
	// Work out which formatters are going to be used.
	if (is_array($formatters)) $formatters = array_intersect(array_keys($this->modes), $formatters);
	else $formatters = array_keys($this->modes);

	// Collect simple display patterns from each of the individual formatters.
	$translations = array();
	foreach ($formatters as $v) {
		if (isset($this->modes[$v]->display) and is_array($this->modes[$v]->display)) $translations += $this->modes[$v]->display;
	}
	$string = strtr($string, $translations);

	// Run any more complex display tasks.
	foreach ($formatters as $v) {
		if (method_exists($this->modes[$v], "display")) $string = $this->modes[$v]->display($string);
	}
	
	return $string;
}

function text($match, $state) {
 	$this->output .= $match;
 	return true;
}

function getModes($modes, $exclude = false)
{
	$newModes = array();
	foreach ($modes as $mode) {
		if ($mode == $exclude) continue;
		if (isset($this->modes[$mode])) $newModes = array_merge($newModes, $this->modes[$mode]->modes);
		else $newModes[] = $mode;
	}
	return $newModes;
}

}

class Formatter_Whitespace {

var $formatter;
var $revert = array("<br/>" => "\n", "<p>" => "", "</p>" => "\n\n");

function Formatter_Whitespace(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	// Map the different forms to the same mode.
	$this->formatter->lexer->mapFunction("paragraph", array($this, "paragraph"));
	$this->formatter->lexer->mapFunction("linebreak", array($this, "linebreak"));
	// Whitespace.
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["whitespace"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addSpecialPattern('\n(?=\n)', $mode, "paragraph");
		$this->formatter->lexer->addSpecialPattern('\n(?!\n)', $mode, "linebreak");
	}
}

function finish($output)
{
	// Strip empty paragraphs.
	$output = "<p>$output</p>";
	$output = preg_replace(array("/<p>\s*<\/p>/i", "/(?<=<p>)\s*(?:<br\/>)*/i", "/\s*(?:<br\/>)*\s*(?=<\/p>)/i"), "", $output);
	$output = str_replace("<p></p>", "", $output);
	
	return $output;
}

function paragraph($match, $state)
{
	$this->formatter->output .= "</p><p>";
	return true;
}

function linebreak($match, $state)
{
	$this->formatter->output .= "<br/>";
	return true;
}

}

class Formatter_Bold {

var $modes = array("bold_tag_b", "bold_tag_strong", "bold_bbcode", "bold_wiki");
var $revert = array("<b>" => "&lt;b&gt;", "</b>" => "&lt;/b&gt;");

function Formatter_Bold(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	// Map the different forms to the same mode.
	$this->formatter->lexer->mapFunction("bold", array($this, "bold"));
	$this->formatter->lexer->mapHandler("bold_tag_b", "bold");
	$this->formatter->lexer->mapHandler("bold_tag_strong", "bold");
	$this->formatter->lexer->mapHandler("bold_bbcode", "bold");
	$this->formatter->lexer->mapHandler("bold_wiki", "bold");
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"], "bold");
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('&lt;b&gt;(?=.*&lt;\/b&gt;)', $mode, "bold_tag_b");
		$this->formatter->lexer->addEntryPattern('\[b](?=.*\[\/b])', $mode, "bold_bbcode");
		$this->formatter->lexer->addEntryPattern('&lt;strong&gt;(?=.*&lt;\/strong&gt;)', $mode, "bold_tag_strong");
		$this->formatter->lexer->addEntryPattern('&#39;&#39;&#39;(?=.*&#39;&#39;&#39;)', $mode, "bold_wiki");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/b&gt;', "bold_tag_b");
	$this->formatter->lexer->addExitPattern('\[\/b]', "bold_bbcode");
	$this->formatter->lexer->addExitPattern('&lt;\/strong&gt;', "bold_tag_strong");
	$this->formatter->lexer->addExitPattern('&#39;&#39;&#39;', "bold_wiki");
}

function bold($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "<b>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</b>"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

}

class Formatter_Italic {

var $formatter;
var $modes = array("italic_tag_i", "italic_tag_em", "italic_bbcode", "italic_wiki");
var $revert = array("<i>" => "&lt;i&gt;", "</i>" => "&lt;/i&gt;");

function Formatter_Italic(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	// Map the different forms to the same mode.
	$this->formatter->lexer->mapFunction("italic", array($this, "italic"));
	$this->formatter->lexer->mapHandler("italic_tag_i", "italic");
	$this->formatter->lexer->mapHandler("italic_tag_em", "italic");
	$this->formatter->lexer->mapHandler("italic_bbcode", "italic");
	$this->formatter->lexer->mapHandler("italic_wiki", "italic");
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"], "italic");
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('&lt;i&gt;(?=.*&lt;\/i&gt;)', $mode, "italic_tag_i");
		$this->formatter->lexer->addEntryPattern('\[i](?=.*\[\/i])', $mode, "italic_bbcode");
		$this->formatter->lexer->addEntryPattern('&lt;em&gt;(?=.*&lt;\/em&gt;)', $mode, "italic_tag_em");
		$this->formatter->lexer->addEntryPattern('&#39;&#39;(?=.*&#39;&#39;)', $mode, "italic_wiki");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/i&gt;', "italic_tag_i");
	$this->formatter->lexer->addExitPattern('\[\/i]', "italic_bbcode");
	$this->formatter->lexer->addExitPattern('&lt;\/em&gt;', "italic_tag_em");
	$this->formatter->lexer->addExitPattern('&#39;&#39;', "italic_wiki");
}

function italic($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "<i>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</i>"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

}

class Formatter_Strikethrough {

var $formatter;
var $modes = array("strike_html", "strike_bbcode", "strike_wiki");
var $revert = array("<del>" => "&lt;s&gt;", "</del>" => "&lt;/s&gt;");

function Formatter_Strikethrough(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	// Map the different forms to the same mode.
	$this->formatter->lexer->mapFunction("strike", array($this, "strike"));
	$this->formatter->lexer->mapHandler("strike_html", "strike");
	$this->formatter->lexer->mapHandler("strike_bbcode", "strike");
	$this->formatter->lexer->mapHandler("strike_wiki", "strike");
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"], "strike");
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('&lt;s&gt;(?=.*&lt;\/s&gt;)', $mode, "strike_html");
		$this->formatter->lexer->addEntryPattern('\[s](?=.*\[\/s])', $mode, "strike_bbcode");
		$this->formatter->lexer->addEntryPattern('-{3,}(?=.*---)', $mode, "strike_wiki");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/s&gt;', "strike_html");
	$this->formatter->lexer->addExitPattern('\[\/s]', "strike_bbcode");
	$this->formatter->lexer->addExitPattern('-{3,}', "strike_wiki");
}

function strike($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "<del>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</del>"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

}

class Formatter_Superscript {

var $formatter;
var $modes = array("superscript", "subscript");
var $revert = array("<sup>" => "&lt;sup&gt;", "</sup>" => "&lt;/sup&gt;", "<sub>" => "&lt;s&gt;", "</sub>" => "&lt;/s&gt;");

function Formatter_Superscript(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	// Map the different forms to the same mode.
	$this->formatter->lexer->mapFunction("superscript", array($this, "superscript"));
	$this->formatter->lexer->mapFunction("subscript", array($this, "subscript"));
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"], "superscript");
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('&lt;sup&gt;(?=.*&lt;\/sup&gt;)', $mode, "superscript");
		$this->formatter->lexer->addEntryPattern('&lt;sub&gt;(?=.*&lt;\/sub&gt;)', $mode, "subscript");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/sup&gt;', "superscript");
	$this->formatter->lexer->addExitPattern('&lt;\/sub&gt;', "subscript");
}

function superscript($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "<sup>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</sup>"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

function subscript($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "<sub>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</sub>"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

}

class Formatter_Heading {

var $formatter;
var $modes = array("heading_html", "heading_bbcode", "heading_wiki");
var $revert = array("<h3>" => "&lt;h1&gt;", "</h3>" => "&lt;/h1&gt;\n\n");

function Formatter_Heading(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{
	// Map the different forms to the same mode.
	$this->formatter->lexer->mapFunction("heading", array($this, "heading"));
	$this->formatter->lexer->mapHandler("heading_html", "heading");
	$this->formatter->lexer->mapHandler("heading_bbcode", "heading");
	$this->formatter->lexer->mapHandler("heading_wiki", "heading");
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('&lt;h1&gt;(?=.*&lt;\/h1&gt;)', $mode, "heading_html");
		$this->formatter->lexer->addEntryPattern('\[h](?=.*\[\/h])', $mode, "heading_bbcode");
		$this->formatter->lexer->addEntryPattern('={3,}(?=.*===)', $mode, "heading_wiki");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/h1&gt;', "heading_html");
	$this->formatter->lexer->addExitPattern('\[\/h]', "heading_bbcode");
	$this->formatter->lexer->addExitPattern('={3,}', "heading_wiki");
}

function heading($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "</p><h3>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</h3><p>\n"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

}

class Formatter_Quote {

var $formatter;
var $modes = array("quote_html", "quote_bbcode");
var $revert = array("<blockquote>" => "&lt;blockquote&gt;", "</blockquote>" => "&lt;/blockquote&gt;\n\n", "<cite>" => "&lt;cite&gt;", "</cite>" => "&lt;/cite&gt;");

function Formatter_Quote(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	$this->formatter->lexer->mapFunction("quote", array($this, "quote"));
	$this->formatter->lexer->mapFunction("cite", array($this, "cite"));
	$this->formatter->lexer->mapHandler("quote_html", "quote");
	$this->formatter->lexer->mapHandler("quote_bbcode", "quote");
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('&lt;blockquote&gt;(?=.*&lt;\/blockquote&gt;)', $mode, "quote_html");
		$this->formatter->lexer->addEntryPattern('\[quote(?:[:=](?:.*?))?\](?=.*\[\/quote])', $mode, "quote_bbcode");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/blockquote&gt;', "quote_html");
	$this->formatter->lexer->addExitPattern('\[\/quote\]', "quote_bbcode");
	
	$this->formatter->lexer->addEntryPattern('&lt;cite&gt;(?=.*&lt;\/cite&gt;)', "quote_html", "cite");
	$this->formatter->lexer->addExitPattern('&lt;\/cite&gt;', "cite");
}

function cite($match, $state)
{
	switch ($state) {
		case LEXER_ENTER:
			$this->formatter->output .= "</p><p><cite>";
			break;
		case LEXER_EXIT:
			$this->formatter->output .= "</cite></p><p>";
			break;
		case LEXER_UNMATCHED:
			$this->formatter->output .= $match;
			break;
	}
	return true;
}

function quote($match, $state)
{
	switch ($state) {
		case LEXER_ENTER:
			$this->formatter->output .= "</p><blockquote><p>";
			break;
		case LEXER_EXIT:
			$this->formatter->output .= "</p></blockquote><p>\n";
			break;
		case LEXER_UNMATCHED:
			$this->formatter->output .= $match;
			break;
	}
	return true;
}

}

class Formatter_Fixed_Block {

var $formatter;
var $modes = array("pre_html_block", "code_html_block", "code_bbcode_block");
var $revert = array("<pre>" => "&lt;pre&gt;", "</pre>" => "&lt;/pre&gt;\n\n");

function Formatter_Fixed_Block(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	$this->formatter->lexer->mapFunction("fixedBlock", array($this, "fixedBlock"));
	$this->formatter->lexer->mapHandler("pre_html_block", "fixedBlock");
	$this->formatter->lexer->mapHandler("code_html_block", "fixedBlock");
	$this->formatter->lexer->mapHandler("code_bbcode_block", "fixedBlock");
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('\n&lt;pre&gt;(?=.*&lt;\/pre&gt;)', $mode, "pre_html_block");
		$this->formatter->lexer->addEntryPattern('\n&lt;code&gt;(?=.*&lt;\/code&gt;)', $mode, "code_html_block");
		$this->formatter->lexer->addEntryPattern('\n\[code\](?=.*\[\/code])', $mode, "code_bbcode_block");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/pre&gt;', "pre_html_block");
	$this->formatter->lexer->addExitPattern('&lt;\/code&gt;', "code_html_block");
	$this->formatter->lexer->addExitPattern('\[\/code]', "code_bbcode_block");
}

function fixedBlock($match, $state)
{
	switch ($state) {
		case LEXER_ENTER:
			$this->formatter->output .= "</p><pre>";
			break;
		case LEXER_EXIT:
			$this->formatter->output .= "</pre><p>\n";
			break;
		case LEXER_UNMATCHED:
			$this->formatter->output .= $match;
			break;
	}
	return true;
}

}

class Formatter_Fixed_Inline {

var $formatter;
var $modes = array("pre_html_inline", "code_html_inline", "code_bbcode_inline");
var $revert = array("<code>" => "&lt;pre&gt;", "</code>" => "&lt;/pre&gt;");

function Formatter_Fixed_Inline(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	$this->formatter->lexer->mapFunction("fixedInline", array($this, "fixedInline"));
	$this->formatter->lexer->mapHandler("pre_html_inline", "fixedInline");
	$this->formatter->lexer->mapHandler("code_html_inline", "fixedInline");
	$this->formatter->lexer->mapHandler("code_bbcode_inline", "fixedInline");
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('&lt;pre&gt;(?=.*&lt;\/pre&gt;)', $mode, "pre_html_inline");
		$this->formatter->lexer->addEntryPattern('&lt;code&gt;(?=.*&lt;\/code&gt;)', $mode, "code_html_inline");
		$this->formatter->lexer->addEntryPattern('\[code\](?=.*\[\/code])', $mode, "code_bbcode_inline");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/pre&gt;', "pre_html_inline");
	$this->formatter->lexer->addExitPattern('&lt;\/code&gt;', "code_html_inline");
	$this->formatter->lexer->addExitPattern('\[\/code]', "code_bbcode_inline");
}

function fixedInline($match, $state)
{
	switch ($state) {
		case LEXER_ENTER:
			$this->formatter->output .= "<code>";
			break;
		case LEXER_EXIT:
			$this->formatter->output .= "</code>";
			break;
		case LEXER_UNMATCHED:
			$this->formatter->output .= $match;
			break;
	}
	return true;
}

}

class Formatter_Link {

var $formatter;
var $modes = array("link_html", "link_bbcode", "link_wiki", "postLink");

function Formatter_Link(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{
	$this->formatter->lexer->mapFunction("link", array($this, "link"));
	$this->formatter->lexer->mapFunction("url", array($this, "url"));
	$this->formatter->lexer->mapFunction("email", array($this, "email"));
	$this->formatter->lexer->mapFunction("postLink", array($this, "postLink"));
	$this->formatter->lexer->mapHandler("link_html", "link");
	$this->formatter->lexer->mapHandler("link_bbcode", "link");
	$this->formatter->lexer->mapHandler("link_wiki", "link");
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"], "link");
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addSpecialPattern('(?<=[\s>(]|^)(?:(?:https?|ftp|feed):\/\/)?(?:[\w\-]+\.)+(?:ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|asia|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|je|jm|jo|jobs|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mil|mk|ml|mm|mn|mo|mobi|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tel|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)(?:[^\w\s]\S*?)??(?=[\.,?!]*(?:\s|$)|&#39;|&lt;|\[)', $mode, "url");
		$this->formatter->lexer->addEntryPattern('&lt;a.+?&gt;(?=.*&lt;\/a&gt;)', $mode, "link_html");
		$this->formatter->lexer->addEntryPattern('\[url=(?:(?:https?|ftp|feed):\/\/|mailto:|).+?](?=.*\[\/url])', $mode, "link_bbcode");
		$this->formatter->lexer->addEntryPattern('\[(?:(?:https?|ftp|feed):\/\/|mailto:)\S+\s+(?=.*])', $mode, "link_wiki");
		$this->formatter->lexer->addEntryPattern('\[post:\d+\s+(?=.*])', $mode, "postLink");
		$this->formatter->lexer->addSpecialPattern('(?<=[\s>(]|^)[\w-\.]+@(?:[\w-]+\.)+[\w-]{2,4}', $mode, "email");
	}
	$this->formatter->lexer->addExitPattern('&lt;\/a&gt;', "link_html");
	$this->formatter->lexer->addExitPattern('\[\/url]', "link_bbcode");
	$this->formatter->lexer->addExitPattern(']', "link_wiki");
	$this->formatter->lexer->addExitPattern(']', "postLink");
}

function email($match, $state)
{
	$this->formatter->output .= "<a href='mailto:$match'>$match</a>";
	return true;
}

function postLink($match, $state)
{
	switch ($state) {
		case LEXER_ENTER:
			$postId = rtrim(substr($match, 6));
			$this->formatter->output .= "<a href='" . makeLink("post", (int)$postId) . "'>";
			break;
		case LEXER_EXIT:
			$this->formatter->output .= "</a>";
			break;
		case LEXER_UNMATCHED:
			$this->formatter->output .= $match;
			break;
	}
	return true;
}

function url($match, $state)
{
	$protocol = "";
	if (!preg_match("`^((?:https?|file|ftp|feed)://)`i", $match)) $protocol = "http://";
	$after = "";
	// If the last character is a ), and there are more ) than ( in the link, drop a ) off of the end.
	if ($match[strlen($match) - 1] == ")") {
		if (substr_count($match, "(") < substr_count($match, ")")) {
			$match = substr($match, 0, strlen($match) - 1);
			$after = ")";
		}
	}
	$this->formatter->output .= "<a href='$protocol$match'>$match</a>$after";
	return true;
}

function link($match, $state)
{
	switch ($state) {
		case LEXER_ENTER:
			if (substr($match, 0, 5) == "&lt;a") {
				preg_match("`href=(&#39;|&quot;)((?:(?:https?|ftp|feed):\/\/|mailto:|).+?)\\1`", $match, $href);
				$link = $href[2];
				if (preg_match("`title=(&#39;|&quot;)(.+?)\\1`", $match, $titleMatch)) $title = $titleMatch[2];
				if (!empty($title)) $quote = strpos($title, "&#39;") === false ? "'" : '"';
			} elseif (substr($match, 0, 5) == "[url=") $link = substr($match, 5, -1);
			else $link = rtrim(substr($match, 1));
			$protocol = "";
			if (!preg_match("`^((?:https?|ftp|feed)://|mailto:)`i", $link)) $protocol = "http://";
			$this->formatter->output .= "<a href='$protocol$link'" . (isset($title) ? " title=$quote$title$quote" : "") . ">";
			break;
		case LEXER_EXIT:
			$this->formatter->output .= "</a>";
			break;
		case LEXER_UNMATCHED:
			$this->formatter->output .= $match;
			break;
	}
	return true;
}

function revert($string)
{
	// Emails and links
	$string = preg_replace("/<a href='mailto:(.*?)'>\\1<\/a>/", "$1", $string);
	$string = preg_replace("`<a href='" . str_replace("?", "\?", makeLink("post", "(\d+)")) . "'[^>]*>(.*?)<\/a>`", "[post:$1 $2]", $string);
	$string = preg_replace("`<a href='" . str_replace("?", "\?", makeLink("(\d+)")) . "'[^>]*>(.*?)<\/a>`", "[conversation:$1 $2]", $string);
	$string = preg_replace("/<a href='(?:\w+:\/\/)?(.*?)'>\\1<\/a>/", "$1", $string);
	$string = preg_replace("/<a(.*?)>(.*?)<\/a>/", "&lt;a$1&gt;$2&lt;/a&gt;", $string);
		
	return $string;
}

}

class Formatter_Image {

var $formatter;
var $modes = array("image_html", "image_bbcode1", "image_bbcode2");

function Formatter_Image(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{	
	$this->formatter->lexer->mapFunction("image_html", array($this, "image_html"));
	$this->formatter->lexer->mapFunction("image_bbcode1", array($this, "image_bbcode1"));
	$this->formatter->lexer->mapFunction("image_bbcode2", array($this, "image_bbcode2"));
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["whitespace"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addSpecialPattern('&lt;img.+?\/?&gt;', $mode, "image_html");
		$this->formatter->lexer->addSpecialPattern('\[img]https?:\/\/[^\s]+?\[\/img]', $mode, "image_bbcode1");
		$this->formatter->lexer->addSpecialPattern('\[(?:img|image)[:=]https?:\/\/[^\s]+?]', $mode, "image_bbcode2");
	}
}

function image_html($match, $state)
{
	if (preg_match("`src=(&#39;|&quot;)(https?:\/\/[^\s]+?)\\1`", $match, $src)) $src = $src[2];
	else {
		$this->formatter->output .= $match;
		return true;
	}
	$alt = $title = "";
	if (preg_match("`alt=(&#39;|&quot;)(.+?)\\1`", $match, $alt)) $alt = $alt[2];
	if (preg_match("`title=(&#39;|&quot;)(.+?)\\1`", $match, $title)) $title = $title[2];
	$this->image($src, $alt, $title);
	return true;
}

function image_bbcode1($match, $state)
{
	$match = substr($match, 5, -6);
	$this->image($match);
	return true;
}

function image_bbcode2($match, $state)
{
	$match = substr(strpbrk($match, ":="), 1, -5);
	$this->image($match);
	return true;
}

function image($src, $alt = "", $title = "")
{
	if (!empty($alt)) $altQuote = strpos($alt, "&#39;") === false ? "'" : '"';
	if (!empty($title)) $titleQuote = strpos($title, "&#39;") === false ? "'" : '"';
	$this->formatter->output .= "<img src='$src'" . (!empty($alt) ? " alt=$altQuote$alt$altQuote" : "") . (!empty($title) ? " title=$titleQuote$title$titleQuote" : "") . "/>";
}

function revert($string)
{
	$string = preg_replace("/<img(.*?)\/>/", "&lt;img$1&gt;", $string);
	return $string;
}

}

class Formatter_List {

var $formatter;
var $modes = array("blockList");

var $listStack = array();
var $initialDepth = 0;

function Formatter_List(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{
	$this->formatter->lexer->mapFunction("blockList", array($this, "blockList"));
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addEntryPattern('(?:\n|^) *(?:1[\.\)]|[-*#]) +', $mode, "blockList");
	}
	$this->formatter->lexer->addPattern('\n *(?:[0-9][\.\)]|[-*#]) +', "blockList");
	$this->formatter->lexer->addExitPattern('\n(?! )', "blockList");
}

function blockList($match, $state) {
	switch ($state) {
		case LEXER_ENTER:
			$depth = $this->interpretSyntax($match, $listType);

			$this->initialDepth = $depth;
			$this->listStack[] = array($listType, $depth);

			$this->formatter->output .= "</p><{$listType}l><li>";

		break;
		case LEXER_EXIT:
			while ( $list = array_pop($this->listStack) ) {
				$this->formatter->output .= "</li></{$list[0]}l>";
			}
			$this->formatter->output .= "<p>";
		break;
		case LEXER_MATCHED:
			$depth = $this->interpretSyntax($match, $listType);
			$end = end($this->listStack);

			// Not allowed to be shallower than initialDepth
			if ( $depth < $this->initialDepth ) {
				$depth = $this->initialDepth;
			}

			//------------------------------------------------------------------------
			if ( $depth == $end[1] ) {

				// Just another item in the list...
				if ( $listType == $end[0] ) {
					$this->formatter->output .= "</li><li>";

				// Switched list type...
				} else {
					$this->formatter->output .= "</li></{$end[0]}l><{$listType}l><li>";

					array_pop($this->listStack);
					$this->listStack[] = array($listType, $depth);
				}

			//------------------------------------------------------------------------
			// Getting deeper...
			} else if ( $depth > $end[1] ) {
				$this->formatter->output .= "<{$listType}l><li>";
				$this->listStack[] = array($listType, $depth);

			//------------------------------------------------------------------------
			// Getting shallower ( $depth < $end[1] )
			} else {
				$this->formatter->output .= "</li></{$end[0]}l>";

				// Throw away the end - done
				array_pop($this->listStack);

				while (1) {
					$end = end($this->listStack);

					if ( $end[1] <= $depth ) {

						// Normalize depths
						$depth = $end[1];

						$this->formatter->output .= "</li>";

						if ( $end[0] == $listType ) {
							$this->formatter->output .= "<li>";

						} else {
							// Switching list type...
							$this->formatter->output .= "</{$end[0]}l><{$listType}l><li>";

							array_pop($this->listStack);
							$this->listStack[] = array($listType, $depth);
						}

						break;

					// Haven't dropped down far enough yet.... ( $end[1] > $depth )
					} else {

						$this->formatter->output .= "</li></{$end[0]}l>";

						array_pop($this->listStack);

					}

				}

			}
		break;
		case LEXER_UNMATCHED:
			$this->formatter->output .= $match;
		break;
	}
	return true;
}

function interpretSyntax($match, & $type) {
	$match = rtrim($match);
	if ( substr($match,-1) == '*' or substr($match,-1) == '-' ) {
		$type = 'u';
	} else {
		$type = 'o';
	}
	return count(explode(' ',str_replace("\t",' ',$match)));
}

var $output;
function revert($string)
{
	$this->lexer = &new SimpleLexer($this, "text", true);
	$allowedModes = array("text", "unorderedList", "orderedList");
	foreach ($allowedModes as $mode) {
		$this->lexer->addEntryPattern('<ul>', $mode, "unorderedList");
		$this->lexer->addEntryPattern('<ol>', $mode, "orderedList");
	}
	$this->lexer->addSpecialPattern('<li>', "unorderedList", "uli");
	$this->lexer->addSpecialPattern('<li>', "orderedList", "oli");
	$this->lexer->addExitPattern('<\/ul>', "unorderedList");
	$this->lexer->addExitPattern('<\/ol>', "orderedList");
	
	$this->lexer->parse($string);
	
	$string = str_replace("</li>", "", $this->output);
	
	return $string;
}

var $listLevel = -1;
var $listNumbers = array();
var $firstItem = true;

function uli($match, $state)
{
	$this->output .= ($this->firstItem ? "" : "\n") . str_repeat(" ", $this->listLevel) . "- ";
	$this->firstItem = false;
	return true;
}
function oli($match, $state)
{
	$this->listNumbers[$this->listLevel]++;
	$this->output .= ($this->firstItem ? "" : "\n") . str_repeat(" ", $this->listLevel) . $this->listNumbers[$this->listLevel] . ". ";
	$this->firstItem = false;
	return true;
}
function unorderedList($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->listLevel++; break;
		case LEXER_EXIT: $this->listLevel--; if ($this->listLevel == -1) {$this->output .= "\n\n"; $this->firstItem = true;} break;
		case LEXER_UNMATCHED: $this->output .= $match; break;
	}
	return true;
}

function orderedList($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->listLevel++; $this->listNumbers[$this->listLevel] = 0; break;
		case LEXER_EXIT: $this->listLevel--; if ($this->listLevel == -1) {$this->output .= "\n\n"; $this->firstItem = true;} break;
		case LEXER_UNMATCHED: $this->output .= $match; break;
	}
	return true;
}

function text($match, $state) {
 	$this->output .= $match;
 	return true;
}

}

class Formatter_Horizontal_Rule {

var $formatter;
var $revert = array("<hr/>" => "-----\n\n");

function Formatter_Horizontal_Rule(&$formatter)
{
	$this->formatter =& $formatter;
}

function format()
{
	$this->formatter->lexer->mapFunction("horizontalRule", array($this, "horizontalRule"));
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addSpecialPattern('(?:\n|^)-{5,}(?=\n|$)', $mode, "horizontalRule");
	}
}

function horizontalRule($match, $state)
{
	$this->formatter->output .= "<hr/>";
	return true;
}

}

class Formatter_Special_Characters {

var $formatter;
var $characters = array(
	"&lt;-&gt;" => "↔",
	"-&gt;" => "→",
	"&lt;-" => "←",
	"&lt;=&gt;" => "⇔",
	"=&gt;" => "⇒",
	"&lt;=" => "⇐",
	"&gt;&gt;" => "»",
	"&lt;&lt;" => "«",
	"(c)" => "©",
	"(tm)" => "™",
	"(r)" => "®",
	"--" => "–",
	"..." => "…"
);

function Formatter_Special_Characters(&$formatter)
{
	$this->formatter =& $formatter;
	$this->revert = array_flip($this->characters);
}

function format()
{
	$this->formatter->lexer->mapFunction("entity", array($this, "entity"));
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"]);
    
	$pattern = array();
	foreach ($this->characters as $k => $v) $pattern[] = preg_quote($k);
	$pattern = implode("|", $pattern);
	
	foreach ($allowedModes as $mode) $this->formatter->lexer->addSpecialPattern($pattern, $mode, "entity");
}

function entity($match, $state)
{
	if (array_key_exists($match, $this->characters)) $this->formatter->output .= $this->characters[$match];
	return true;
}

}

?>