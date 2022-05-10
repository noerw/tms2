<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Out a local (possibly outside of web root) image to browser
function output_image($path, $name = false) {

	// Need these
	global $allowed_picture_exts;

	// Gotta exist
	if (!is_file($path)) {
		echo 'not found';
		return false;
	}

	// Make sure it's an image
	$image_info = @getimagesize($path);
	if (!$image_info || !is_array($image_info)) {
		echo 'not image';
		return false;
	}

	// Okay, if this is a bitmap, stop right here and try making it a jpeg
	if ($image_info[2] == IMAGETYPE_BMP) {
		$img = @imagecreatefrombmp($path);
		if ($img) {
			header('Content-type: image/jpeg');
			imagejpeg($img);
			exit;
		}

	}

	// Deal with extension and mime type
	$mime = image_type_to_mime_type($image_info[2]);
	$ext = image_type_to_extension($image_info[2], false);
	$ext = $ext == 'jpeg' ? 'jpg' : $ext;

	// Get unix timestamp of when last modified
	$last_modified = filemtime($path);

	// Grab md5 hash, wrapped in quotes
	$file_hash = md5_file($path);

	// Show it? if the person already has it cached, not showing it'll save loads of bandwidth

	// Last modified date
	if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $last_modified)
	{
		header('HTTP/1.1 304 Not Modified');
		exit;
	}

	if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && strpos($_SERVER['HTTP_IF_NONE_MATCH'], $file_hash) !== false)
	{
			header('HTTP/1.1 304 Not Modified');
			exit;
	}

	// Give it
	header('Content-type: '.$mime);
	header('Cache-Control: max-age=' . (525600 * 60) . ', private');
	header('Expires: ' . gmDate('D, d M Y H:i:s', time() + 525600 * 60) . ' GMT');
	header('Content-Length: ' . filesize($path));
	header('Last-Modified: '.gmDate('D, d M Y H:i:s', $last_modified) . ' GMT');
	header('ETag: "' . $file_hash . '"');

	//filename too?
	$name && header('Content-Disposition: inline; filename='.$name.'.'.$ext);

	// Send it
	readfile($path);
}
