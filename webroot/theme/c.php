<?php

$file = trim($_GET['f']);

if ($file == '' || strpos($file, '/') !== false || preg_match('/^[^.][^\/][a-z0-9\-\_\.]+\.(css|js)$/i', $file, $m) != 1)
	exit('Invalid');

switch (strtolower($m[1])) {
	case 'js':
		$mime = 'application/x-javascript';
	break;

	case 'css':
		$mime = 'text/css';
	break;
}

$last_modified = filemtime($file);

// Last modified date
if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $last_modified)
{
	header('HTTP/1.1 304 Not Modified');
	exit;
}

header('Last-Modified: '.gmDate('D, d M Y H:i:s', $last_modified) . ' GMT');
header('Content-type: '.$mime);
ob_start('ob_gzhandler');
echo file_get_contents(dirname(__file__).'/'.$file);
ob_end_flush();
