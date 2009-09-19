<?php
// Copyright 2009 Simon Zerner, Toby Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

// GIF avatar loader: displays an unresized gif avatar with secure headers.

$memberId = (int)$_GET["id"];
$filename = "$memberId.gif";
if (!file_exists($filename)) exit;
header("Content-Type: image/gif");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=\"$filename\""); // Filename.
header("Content-Transfer-Encoding: binary");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");
header("Content-Length: " . filesize($filename));
set_time_limit(0);
ob_clean();
flush();
readfile($filename);
exit;

?>