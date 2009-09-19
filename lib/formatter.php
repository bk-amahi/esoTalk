<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Formatter class

if (!defined("IN_ESOTALK")) exit;

require_once "lexer.php";

class Formatter {

var $output = "";

var $modes = array();

var $allowedModes = array(
	
	// Modes in which inline-level formatting (bold, italic, links, etc.) can be applied.
	"inline" => array("text", "quote", "list", "heading", "italic", "bold", "strike", "link", "superscript", "subscript"),
	
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
		"bold" => new Formatter_Bold(),
		"italic" => new Formatter_Italic(),
		"heading" => new Formatter_Heading(),
		"superscript" => new Formatter_Superscript(),
		"strikethrough" => new Formatter_Strikethrough(),
		"link" => new Formatter_Link(),
		"image" => new Formatter_Image(),
		"list" => new Formatter_List(),
		"quote" => new Formatter_Quote(),
		"fixedBlock" => new Formatter_Fixed_Block(),
		"fixedInline" => new Formatter_Fixed_Inline(),
		"horizontalRule" => new Formatter_Horizontal_Rule(),
		"specialCharacters" => new Formatter_Special_Characters(),
	);
	foreach ($this->modes as $k => $v) $this->modes[$k]->init($this);
	
	// Whitespace.
	$allowedModes = $this->getModes($this->allowedModes["whitespace"]);
	foreach ($allowedModes as $mode) {
		$this->lexer->addSpecialPattern('\n(?=\n)', $mode, "paragraph");
		$this->lexer->addSpecialPattern('\n(?!\n)', $mode, "linebreak");
	}
}

function parse($string)
{
	// Convert all \r into \n.
	$string = strtr($string, array("\r\n" => "\n", "\r" => "\n"));
	
	$this->lexer->parse($string);
	
	// Strip empty paragraphs.
	$this->output = "<p>$this->output</p>";
	$this->output = preg_replace(array("/<p>\s*<\/p>/i", "/(?<=<p>)\s*(?:<br\/>)*/i", "/\s*(?:<br\/>)*\s*(?=<\/p>)/i"), "", $this->output);
	$this->output = str_replace("<p></p>", "", $this->output);
	
	return $this->output;
}

function text($match, $state) {
 	$this->output .= $match;
 	return true;
}

function paragraph($match, $state)
{
	$this->output .= "</p><p>";
	return true;
}

function linebreak($match, $state)
{
	$this->output .= "<br/>";
	return true;
}

function addFormatter() {}

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

class Formatter_Bold {

var $formatter;
var $modes = array("bold_tag_b", "bold_tag_strong", "bold_bbcode", "bold_wiki");

function bold($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "<b>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</b>"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	// Map the different forms to the same mode.
	$formatter->lexer->mapFunction("bold", array($this, "bold"));
	$formatter->lexer->mapHandler("bold_tag_b", "bold");
	$formatter->lexer->mapHandler("bold_tag_strong", "bold");
	$formatter->lexer->mapHandler("bold_bbcode", "bold");
	$formatter->lexer->mapHandler("bold_wiki", "bold");
	$allowedModes = $formatter->getModes($formatter->allowedModes["inline"], "bold");
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('&lt;b&gt;(?=.*&lt;\/b&gt;)', $mode, "bold_tag_b");
		$formatter->lexer->addEntryPattern('\[b](?=.*\[\/b])', $mode, "bold_bbcode");
		$formatter->lexer->addEntryPattern('&lt;strong&gt;(?=.*&lt;\/strong&gt;)', $mode, "bold_tag_strong");
		$formatter->lexer->addEntryPattern('&#39;&#39;&#39;(?=.*&#39;&#39;&#39;)', $mode, "bold_wiki");
	}
	$formatter->lexer->addExitPattern('&lt;\/b&gt;', "bold_tag_b");
	$formatter->lexer->addExitPattern('\[\/b]', "bold_bbcode");
	$formatter->lexer->addExitPattern('&lt;\/strong&gt;', "bold_tag_strong");
	$formatter->lexer->addExitPattern('&#39;&#39;&#39;', "bold_wiki");
}

}

class Formatter_Italic {

var $formatter;
var $modes = array("italic_tag_i", "italic_tag_em", "italic_bbcode", "italic_wiki");

function italic($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "<i>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</i>"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	// Map the different forms to the same mode.
	$formatter->lexer->mapFunction("italic", array($this, "italic"));
	$formatter->lexer->mapHandler("italic_tag_i", "italic");
	$formatter->lexer->mapHandler("italic_tag_em", "italic");
	$formatter->lexer->mapHandler("italic_bbcode", "italic");
	$formatter->lexer->mapHandler("italic_wiki", "italic");
	$allowedModes = $formatter->getModes($formatter->allowedModes["inline"], "italic");
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('&lt;i&gt;(?=.*&lt;\/u&gt;)', $mode, "italic_tag_i");
		$formatter->lexer->addEntryPattern('\[i](?=.*\[\/i])', $mode, "italic_bbcode");
		$formatter->lexer->addEntryPattern('&lt;em&gt;(?=.*&lt;\/em&gt;)', $mode, "italic_tag_em");
		$formatter->lexer->addEntryPattern('&#39;&#39;(?=.*&#39;&#39;)', $mode, "italic_wiki");
	}
	$formatter->lexer->addExitPattern('&lt;\/i&gt;', "italic_tag_i");
	$formatter->lexer->addExitPattern('\[\/i]', "italic_bbcode");
	$formatter->lexer->addExitPattern('&lt;\/em&gt;', "italic_tag_em");
	$formatter->lexer->addExitPattern('&#39;&#39;', "italic_wiki");
}

}

class Formatter_Strikethrough {

var $formatter;
var $modes = array("strike_html", "strike_bbcode", "strike_wiki");

function strike($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "<del>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</del>"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	// Map the different forms to the same mode.
	$formatter->lexer->mapFunction("strike", array($this, "strike"));
	$formatter->lexer->mapHandler("strike_html", "strike");
	$formatter->lexer->mapHandler("strike_bbcode", "strike");
	$formatter->lexer->mapHandler("strike_wiki", "strike");
	$allowedModes = $formatter->getModes($formatter->allowedModes["inline"], "strike");
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('&lt;del&gt;(?=.*&lt;\/del&gt;)', $mode, "strike_html");
		$formatter->lexer->addEntryPattern('\[s](?=.*\[\/s])', $mode, "strike_bbcode");
		$formatter->lexer->addEntryPattern('-{3,}(?=.*---)', $mode, "strike_wiki");
	}
	$formatter->lexer->addExitPattern('&lt;\/del&gt;', "strike_html");
	$formatter->lexer->addExitPattern('\[\/s]', "strike_bbcode");
	$formatter->lexer->addExitPattern('-{3,}', "strike_wiki");
}

}

class Formatter_Superscript {

var $formatter;
var $modes = array("superscript", "subscript");

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

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	// Map the different forms to the same mode.
	$formatter->lexer->mapFunction("superscript", array($this, "superscript"));
	$formatter->lexer->mapFunction("subscript", array($this, "subscript"));
	$allowedModes = $formatter->getModes($formatter->allowedModes["inline"], "superscript");
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('&lt;sup&gt;(?=.*&lt;\/sup&gt;)', $mode, "superscript");
		$formatter->lexer->addEntryPattern('&lt;sub&gt;(?=.*&lt;\/sub&gt;)', $mode, "subscript");
	}
	$formatter->lexer->addExitPattern('&lt;\/sup&gt;', "superscript");
	$formatter->lexer->addExitPattern('&lt;\/sub&gt;', "subscript");
}

}

class Formatter_Heading {

var $formatter;
var $modes = array("heading_html", "heading_bbcode", "heading_wiki");

function heading($match, $state)
{
	switch ($state) {
		case LEXER_ENTER: $this->formatter->output .= "</p><h3>"; break;
		case LEXER_EXIT: $this->formatter->output .= "</h3><p>\n"; break;
		case LEXER_UNMATCHED: $this->formatter->output .= $match; break;
	}
	return true;
}

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	// Map the different forms to the same mode.
	$formatter->lexer->mapFunction("heading", array($this, "heading"));
	$formatter->lexer->mapHandler("heading_html", "heading");
	$formatter->lexer->mapHandler("heading_bbcode", "heading");
	$formatter->lexer->mapHandler("heading_wiki", "heading");
	$allowedModes = $formatter->getModes($formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('&lt;h1&gt;(?=.*&lt;\/h1&gt;)', $mode, "heading_html");
		$formatter->lexer->addEntryPattern('\[h](?=.*\[\/h])', $mode, "heading_bbcode");
		$formatter->lexer->addEntryPattern('={3,}(?=.*===)', $mode, "heading_wiki");
	}
	$formatter->lexer->addExitPattern('&lt;\/h1&gt;', "heading_html");
	$formatter->lexer->addExitPattern('\[\/h]', "heading_bbcode");
	$formatter->lexer->addExitPattern('={3,}', "heading_wiki");
}

}

class Formatter_Quote {

var $formatter;
var $modes = array("quote_html", "quote_bbcode");

function quote($match, $state)
{
	switch ($state) {
		case LEXER_ENTER:
			if (preg_match("`&lt;cite&gt;(.*?)&lt;/cite&gt;|\[quote[:=](.*?)\]`", $match, $matches)) $cite = trim(end($matches));
			$this->formatter->output .= "</p><blockquote>" . (!empty($cite) ? "<p><cite>$cite</cite></p>" : "") . "<p>";
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

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	$formatter->lexer->mapFunction("quote", array($this, "quote"));
	$formatter->lexer->mapHandler("quote_html", "quote");
	$formatter->lexer->mapHandler("quote_bbcode", "quote");
	$allowedModes = $formatter->getModes($formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('&lt;blockquote&gt;(?:\s*&lt;cite&gt;(?:.*?)&lt;\/cite&gt;)?(?=.*&lt;\/blockquote&gt;)', $mode, "quote_html");
		$formatter->lexer->addEntryPattern('\[quote(?:[:=](?:.*?))?\](?=.*\[\/quote])', $mode, "quote_bbcode");
	}
	$formatter->lexer->addExitPattern('&lt;\/blockquote&gt;', "quote_html");
	$formatter->lexer->addExitPattern('\[\/quote\]', "quote_bbcode");
}

}

class Formatter_Fixed_Block {

var $formatter;
var $modes = array("pre_html_block", "code_html_block", "code_bbcode_block");

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

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	$formatter->lexer->mapFunction("fixedBlock", array($this, "fixedBlock"));
	$formatter->lexer->mapHandler("pre_html_block", "fixedBlock");
	$formatter->lexer->mapHandler("code_html_block", "fixedBlock");
	$formatter->lexer->mapHandler("code_bbcode_block", "fixedBlock");
	$allowedModes = $formatter->getModes($formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('\n&lt;pre&gt;(?=.*&lt;\/pre&gt;)', $mode, "pre_html_block");
		$formatter->lexer->addEntryPattern('\n&lt;code&gt;(?=.*&lt;\/code&gt;)', $mode, "code_html_block");
		$formatter->lexer->addEntryPattern('\n\[code\](?=.*\[\/code])', $mode, "code_bbcode_block");
	}
	$formatter->lexer->addExitPattern('&lt;\/pre&gt;', "pre_html_block");
	$formatter->lexer->addExitPattern('&lt;\/code&gt;', "code_html_block");
	$formatter->lexer->addExitPattern('\[\/code]', "code_bbcode_block");
}

}

class Formatter_Fixed_Inline {

var $formatter;
var $modes = array("pre_html_inline", "code_html_inline", "code_bbcode_inline");

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

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	$formatter->lexer->mapFunction("fixedInline", array($this, "fixedInline"));
	$formatter->lexer->mapHandler("pre_html_inline", "fixedInline");
	$formatter->lexer->mapHandler("code_html_inline", "fixedInline");
	$formatter->lexer->mapHandler("code_bbcode_inline", "fixedInline");
	$allowedModes = $formatter->getModes($formatter->allowedModes["inline"]);
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('&lt;pre&gt;(?=.*&lt;\/pre&gt;)', $mode, "pre_html_inline");
		$formatter->lexer->addEntryPattern('&lt;code&gt;(?=.*&lt;\/code&gt;)', $mode, "code_html_inline");
		$formatter->lexer->addEntryPattern('\[code\](?=.*\[\/code])', $mode, "code_bbcode_inline");
	}
	$formatter->lexer->addExitPattern('&lt;\/pre&gt;', "pre_html_inline");
	$formatter->lexer->addExitPattern('&lt;\/code&gt;', "code_html_inline");
	$formatter->lexer->addExitPattern('\[\/code]', "code_bbcode_inline");
}

}

class Formatter_Link {

var $formatter;
var $modes = array("link_html", "link_bbcode", "link_wiki");

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
			if (!preg_match('`&lt;a +href=(?:&#39;|&quot;)(.+?)(?:&#39;|&quot;)(?: +title=(?:&#39;|&quot;)(.+?)(?:&#39;|&quot;))?&gt;`', $match, $matches)
				and !preg_match('`\[url=(.+)]`', $match, $matches)
				and !preg_match('`\[(.+)`', $match, $matches)) return true;
			$link = $matches[1];
			$protocol = "";
			if (!preg_match("`^((?:https?|file|ftp|feed)://|mailto:)`i", $link)) $protocol = "http://";
			$this->formatter->output .= "<a href='$protocol$link'" . (isset($matches[2]) ? " title='{$matches[2]}'" : "") . ">";
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

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	$formatter->lexer->mapFunction("link", array($this, "link"));
	$formatter->lexer->mapFunction("url", array($this, "url"));
	$formatter->lexer->mapHandler("link_html", "link");
	$formatter->lexer->mapHandler("link_bbcode", "link");
	$formatter->lexer->mapHandler("link_wiki", "link");
	$allowedModes = $formatter->getModes($formatter->allowedModes["inline"], "link");
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addSpecialPattern('(?<=[\s>(]|^)(?:(?:https?|file|ftp|feed):\/\/)?(?:[\w\-]+\.)+(?:ac|ad|ae|aero|af|ag|ai|al|am|an|ao|aq|ar|arpa|as|asia|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|biz|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cat|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|com|coop|cr|cu|cv|cx|cy|cz|de|dj|dk|dm|do|dz|ec|edu|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gov|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|info|int|io|iq|ir|is|it|je|jm|jo|jobs|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mil|mk|ml|mm|mn|mo|mobi|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|name|nc|ne|net|nf|ng|ni|nl|no|np|nr|nu|nz|om|org|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|pro|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tel|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|travel|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)(?:[^\w\s]\S*?)?(?=[\.,?!]*(?:\s|$)|&#39;|&lt;)', $mode, "url");
		$formatter->lexer->addEntryPattern('&lt;a +href=(?:&#39;|&quot;)(?:(?:https?|file|ftp|feed):\/\/|mailto:|).+?(?:&#39;|&quot;)(?: +title=(?:&#39;|&quot;).+?(?:&#39;|&quot;))?&gt;(?=.*&lt;\/a&gt;)', $mode, "link_html");
		$formatter->lexer->addEntryPattern('\[url=(?:(?:https?|file|ftp|feed):\/\/|mailto:|).+?](?=.*\[\/url])', $mode, "link_bbcode");
		$formatter->lexer->addEntryPattern('\[(?:(?:https?|file|ftp|feed):\/\/|mailto:)\S+(?=.*])', $mode, "link_wiki");
	}
	$formatter->lexer->addExitPattern('&lt;\/a&gt;', "link_html");
	$formatter->lexer->addExitPattern('\[\/url]', "link_bbcode");
	$formatter->lexer->addExitPattern(']', "link_wiki");
}

}

class Formatter_Image {

var $formatter;
var $modes = array("image_html", "image_bbcode1", "image_bbcode2");

function image($match, $state)
{
	$matches = preg_match("`&lt;img +src=(?:&#39;|&quot;)(?P<src>.+?)(?:&#39;|&quot;) ");
	return true;
}

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	$formatter->lexer->mapFunction("image", array($this, "image"));
	$formatter->lexer->mapHandler("image_html", "image");
	$formatter->lexer->mapHandler("image_bbcode1", "image");
	$formatter->lexer->mapHandler("image_bbcode2", "image");
	$allowedModes = $formatter->getModes($formatter->allowedModes["whitespace"]);
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addSpecialPattern('&lt;img \/?&gt;', $mode, "image_html");
	}
}

}

class Formatter_List {

var $formatter;
var $modes = array("blockList");

var $listStack = array();
var $initialDepth = 0;

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
				$this->formatter->output .= "</li></{$list[0]}l><p>";
			}
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

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	$formatter->lexer->mapFunction("blockList", array($this, "blockList"));
	$allowedModes = $formatter->getModes($formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addEntryPattern('\n *(?:1[\.\)]|[-*#]) +', $mode, "blockList");
	}
	$formatter->lexer->addPattern('\n *(?:[0-9][\.\)]|[-*#]) +', "blockList");
	$formatter->lexer->addExitPattern('\n(?! )', "blockList");
}

}

class Formatter_Horizontal_Rule {

var $formatter;

function horizontalRule($match, $state)
{
	$this->formatter->output .= "<hr/>";
	return true;
}

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	$formatter->lexer->mapFunction("horizontalRule", array($this, "horizontalRule"));
	$allowedModes = $formatter->getModes($formatter->allowedModes["block"]);
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addSpecialPattern('\n-{5,}(?=\n)', $mode, "horizontalRule");
	}

}

}

class Formatter_Special_Characters {

var $formatter;

function entity($match, $state)
{
	switch ($match) {
		case "-&gt;": $this->formatter->output .= "→"; break;
		case "&lt;-": $this->formatter->output .= "←"; break;
		case "&lt;-&gt;": $this->formatter->output .= "↔"; break;
		case "=&gt;": $this->formatter->output .= "⇒"; break;
		case "&lt;=": $this->formatter->output .= "⇐"; break;
		case "&lt;=&gt;": $this->formatter->output .= "⇔"; break;
		case "&gt;&gt;": $this->formatter->output .= "»"; break;
		case "&lt;&lt;": $this->formatter->output .= "«"; break;
		case "(c)": $this->formatter->output .= "©"; break;
		case "(tm)": $this->formatter->output .= "™"; break;
		case "(r)": $this->formatter->output .= "®"; break;
		case "--": $this->formatter->output .= "–"; break;
		case "...": $this->formatter->output .= "…"; break;
	}
	return true;
}

function init(&$formatter)
{
	$this->formatter =& $formatter;
	
	$formatter->lexer->mapFunction("entity", array($this, "entity"));
	$allowedModes = $formatter->getModes($formatter->allowedModes["inline"]);
    
	foreach ($allowedModes as $mode) {
		$formatter->lexer->addSpecialPattern('&lt;-&gt;|-&gt;|&lt;-|&lt;=&gt;|=&gt;|&lt;=|&gt;&gt;|&lt;&lt;|\(c\)|\(tm\)|\(r\)|--(?!-)|\.\.\.', $mode, "entity");
	}

}

}

?>