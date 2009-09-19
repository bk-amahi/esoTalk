<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// Search view (wrapper, displays tag/gambit clouds and includes search form / results)

if (!defined("IN_ESOTALK")) exit;
?>
<div id='tagArea'>

<p id='tags'>
<?php
// Echo the most common tags
ksort($this->tagCloud);
foreach ($this->tagCloud as $k => $v) {
	echo "<a href='" . makeLink("search", "?q2=" . urlencode(desanitize((!empty($_SESSION["search"]) ? "{$_SESSION["search"]} + " : "") . "{$language["gambits"]["tag:"]}$k"))) . "' class='$v'>" . str_replace(" ", "&nbsp;", $k) . "</a> ";
}
?> 
</p>

<p id='gambits'>
<?php
// Echo the gambits alphabetically
ksort($this->gambitCloud);
foreach ($this->gambitCloud as $k => $v) {
	echo "<a href='" . makeLink("search", "?q2=" . urlencode(desanitize((!empty($_SESSION["search"]) ? "{$_SESSION["search"]} + " : "") . $k))) . "' class='$v'>" . str_replace(" ", "&nbsp;", $k) . "</a> ";
}
?> 
</p>

</div>

<?php include $this->esoTalk->skin->getView("searchForm.inc.php"); ?> 

<div id='searchResults'>
<?php include $this->esoTalk->skin->getView("searchResults.inc.php"); ?>
</div>

<script type='text/javascript'>
Search.currentSearch = '<?php if (isset($_SESSION["search"])) echo addslashes(desanitize($_SESSION["search"])); ?>';
Search.init();
</script>