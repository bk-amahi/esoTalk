<?php

class Emoticons extends Plugin {

var $id = "Emoticons";
var $name = "Emoticons";
var $version = "1.0.0";
var $description = "Converts emoticon text entities into graphic emoticons";
var $author = "esoTalk team";

var $emoticonDir = "plugins/Emoticons/";
var $emoticons = array();

function init()
{
	parent::init();
	
	$this->esoTalk->addToHead("<style type='text/css'>.emoticon {width:16px; height:16px; background:url({$this->emoticonDir}emoticons.png); background-repeat:no-repeat}</style>");
	
	// Add the formatter
	$this->esoTalk->formatter->addFormatter("emoticons", "Formatter_Emoticons");
	
}

}

class Formatter_Emoticons {
	
var $formatter;
var $emoticons = array();
	
function Formatter_Emoticons(&$formatter)
{
	$this->formatter = &$formatter;
	
	// Define emoticons
	$this->emoticons[":)"] = "<img src='js/x.gif' style='background-position:0 0' alt=':)' class='emoticon'/>";
	$this->emoticons["=)"] = "<img src='js/x.gif' style='background-position:0 0' alt='=)' class='emoticon'/>";
	$this->emoticons[":D"] = "<img src='js/x.gif' style='background-position:0 -20px' alt=':D' class='emoticon'/>";
	$this->emoticons["=D"] = "<img src='js/x.gif' style='background-position:0 -20px' alt='=D' class='emoticon'/>";
	$this->emoticons["^_^"] = "<img src='js/x.gif' style='background-position:0 -40px' alt='^_^' class='emoticon'/>";
	$this->emoticons["^^"] = "<img src='js/x.gif' style='background-position:0 -40px' alt='^^' class='emoticon'/>";
	$this->emoticons[":("] = "<img src='js/x.gif' style='background-position:0 -60px' alt=':(' class='emoticon'/>";
	$this->emoticons["=("] = "<img src='js/x.gif' style='background-position:0 -60px' alt='=(' class='emoticon'/>";
	$this->emoticons["-_-"] = "<img src='js/x.gif' style='background-position:0 -80px' alt='-_-' class='emoticon'/>";
	$this->emoticons[";)"] = "<img src='js/x.gif' style='background-position:0 -100px' alt=';)' class='emoticon'/>";
	$this->emoticons["^_-"] = "<img src='js/x.gif' style='background-position:0 -100px' alt='^_-' class='emoticon'/>";
	$this->emoticons["~_-"] = "<img src='js/x.gif' style='background-position:0 -100px' alt='~_-' class='emoticon'/>";
	$this->emoticons["-_^"] = "<img src='js/x.gif' style='background-position:0 -100px' alt='-_^' class='emoticon'/>";
	$this->emoticons["-_~"] = "<img src='js/x.gif' style='background-position:0 -100px' alt='-_~' class='emoticon'/>";
	$this->emoticons["^_^;"] = "<img src='js/x.gif' style='background-position:0 -120px; width:18px' alt='^_^;' class='emoticon'/>";
	$this->emoticons["^^;"] = "<img src='js/x.gif' style='background-position:0 -120px; width:18px' alt='^^;' class='emoticon'/>";
	$this->emoticons[">_<"] = "<img src='js/x.gif' style='background-position:0 -140px' alt='&gt;_&lt;' class='emoticon'/>";
	$this->emoticons[":/"] = "<img src='js/x.gif' style='background-position:0 -160px' alt=':/' class='emoticon'/>";
	$this->emoticons["=/"] = "<img src='js/x.gif' style='background-position:0 -160px' alt='=/' class='emoticon'/>";
	$this->emoticons[":\\"] = "<img src='js/x.gif' style='background-position:0 -160px' alt=':&#92;' class='emoticon'/>";
	$this->emoticons["=\\"] = "<img src='js/x.gif' style='background-position:0 -160px' alt='=&#92;' class='emoticon'/>";
	$this->emoticons[":S"] = "<img src='js/x.gif' style='background-position:0 -160px' alt=':S' class='emoticon'/>";
	$this->emoticons["=S"] = "<img src='js/x.gif' style='background-position:0 -160px' alt='=S' class='emoticon'/>";
	$this->emoticons[":x"] = "<img src='js/x.gif' style='background-position:0 -180px' alt=':x' class='emoticon'/>";
	$this->emoticons["=x"] = "<img src='js/x.gif' style='background-position:0 -180px' alt='=x' class='emoticon'/>";
	$this->emoticons[":|"] = "<img src='js/x.gif' style='background-position:0 -180px' alt=':|' class='emoticon'/>";
	$this->emoticons["=|"] = "<img src='js/x.gif' style='background-position:0 -180px' alt='=|' class='emoticon'/>";
	$this->emoticons["'_'"] = "<img src='js/x.gif' style='background-position:0 -180px' alt='&#39;_&#39;' class='emoticon'/>";
	$this->emoticons["<_<"] = "<img src='js/x.gif' style='background-position:0 -200px' alt='&lt;_&lt;' class='emoticon'/>";
	$this->emoticons[">_>"] = "<img src='js/x.gif' style='background-position:0 -220px' alt='&gt;_&gt;' class='emoticon'/>";
	$this->emoticons["x_x"] = "<img src='js/x.gif' style='background-position:0 -240px' alt='x_x' class='emoticon'/>";
	$this->emoticons["o_O"] = "<img src='js/x.gif' style='background-position:0 -260px' alt='o_O' class='emoticon'/>";
	$this->emoticons["O_o"] = "<img src='js/x.gif' style='background-position:0 -260px' alt='O_o' class='emoticon'/>";
	$this->emoticons["o_0"] = "<img src='js/x.gif' style='background-position:0 -260px' alt='o_0' class='emoticon'/>";
	$this->emoticons["0_o"] = "<img src='js/x.gif' style='background-position:0 -260px' alt='0_o' class='emoticon'/>";
	$this->emoticons[";_;"] = "<img src='js/x.gif' style='background-position:0 -280px' alt=';_;' class='emoticon'/>";
	$this->emoticons[":'("] = "<img src='js/x.gif' style='background-position:0 -280px' alt=':&#39;(' class='emoticon'/>";
	$this->emoticons[":O"] = "<img src='js/x.gif' style='background-position:0 -300px' alt=':O' class='emoticon'/>";
	$this->emoticons["=O"] = "<img src='js/x.gif' style='background-position:0 -300px' alt='=O' class='emoticon'/>";
	$this->emoticons[":o"] = "<img src='js/x.gif' style='background-position:0 -300px' alt=':o' class='emoticon'/>";
	$this->emoticons["=o"] = "<img src='js/x.gif' style='background-position:0 -300px' alt='=o' class='emoticon'/>";
	$this->emoticons[":P"] = "<img src='js/x.gif' style='background-position:0 -320px' alt=':P' class='emoticon'/>";
	$this->emoticons["=P"] = "<img src='js/x.gif' style='background-position:0 -320px' alt='=P' class='emoticon'/>";
	$this->emoticons[";P"] = "<img src='js/x.gif' style='background-position:0 -320px' alt=';P' class='emoticon'/>";
	$this->emoticons[":["] = "<img src='js/x.gif' style='background-position:0 -340px' alt=':[' class='emoticon'/>";
	$this->emoticons["=["] = "<img src='js/x.gif' style='background-position:0 -340px' alt='=[' class='emoticon'/>";
	$this->emoticons[":3"] = "<img src='js/x.gif' style='background-position:0 -360px' alt=':3' class='emoticon'/>";
	$this->emoticons["=3"] = "<img src='js/x.gif' style='background-position:0 -360px' alt='=3' class='emoticon'/>";
	$this->emoticons["._.;"] = "<img src='js/x.gif' style='background-position:0 -380px; width:18px' alt='._.;' class='emoticon'/>";
	$this->emoticons["<(^.^)>"] = "<img src='js/x.gif' style='background-position:0 -400px; width:19px' alt='&lt;(^.^)&gt;' class='emoticon'/>";
	$this->emoticons["(>'.')>"] = "<img src='js/x.gif' style='background-position:0 -400px; width:19px' alt='(&gt;&#39;.&#39;)&gt;' class='emoticon'/>";
	$this->emoticons["(>^.^)>"] = "<img src='js/x.gif' style='background-position:0 -400px; width:19px' alt='(&gt;^.^)&gt;' class='emoticon'/>";
	$this->emoticons["-_-;"] = "<img src='js/x.gif' style='background-position:0 -420px; width:18px' alt='-_-;' class='emoticon'/>";
	$this->emoticons["(o^_^o)"] = "<img src='js/x.gif' style='background-position:0 -440px' alt='(o^_^o)' class='emoticon'/>";
	$this->emoticons["(^_^)/"] = "<img src='js/x.gif' style='background-position:0 -460px; width:19px' alt='(^_^)/' class='emoticon'/>";
	$this->emoticons[">:("] = "<img src='js/x.gif' style='background-position:0 -480px' alt='>:(' class='emoticon'/>";
	$this->emoticons[">:["] = "<img src='js/x.gif' style='background-position:0 -480px' alt='>:[' class='emoticon'/>";
	$this->emoticons["._."] = "<img src='js/x.gif' style='background-position:0 -500px' alt='._.' class='emoticon'/>";
	$this->emoticons["T_T"] = "<img src='js/x.gif' style='background-position:0 -520px' alt='T_T' class='emoticon'/>";
	$this->emoticons["XD"] = "<img src='js/x.gif' style='background-position:0 -540px' alt='XD' class='emoticon'/>";
	$this->emoticons["('<"] = "<img src='js/x.gif' style='background-position:0 -560px' alt='(&#39;&lt;' class='emoticon'/>";
	$this->emoticons["B)"] = "<img src='js/x.gif' style='background-position:0 -580px' alt='B)' class='emoticon'/>";
	$this->emoticons["XP"] = "<img src='js/x.gif' style='background-position:0 -600px' alt='XP' class='emoticon'/>";
}

function emoticon($match, $state)
{
	$this->formatter->output .= $this->emoticons[desanitize($match)];
	return true;
}

function format()
{
	$patterns = array();
	foreach ($this->emoticons as $k => $v) $patterns[] = preg_quote(sanitize($k), "/");
	
	$this->formatter->lexer->mapFunction("emoticon", array($this, "emoticon"));
	$allowedModes = $this->formatter->getModes($this->formatter->allowedModes["inline"]);
	foreach ($allowedModes as $mode) {
		$this->formatter->lexer->addSpecialPattern('(?<=^|[\s.,!<>])(?:' . implode("|", $patterns) . ')(?=[\s.,!<>)]|$)', $mode, "emoticon");
	}
}

function revert($string)
{
	return strtr($string, array_flip($this->emoticons));
}

}

?>