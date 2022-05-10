<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

function sizefix($s) {
	if (ctype_digit($s))
		return  (int)$s * (12/3). 'px';
	else
		return $s;
}
function urlfix($s) {
	return str_replace('"', '', $s);
}

// BBC code
function bbc($s, $min = false) {

	global $ui;

	//$s = utf8_encode($s);
	#$s = utf8_decode($s);

	// Hold what to replace
	$f = array();
	$r = array();

	// URL's
	//$f[] = '/(https?\:\/\/[^\t]+)/i';
	//$r[] = '<a href="$1">Link</a>';
	#$f[] = '/\[url=([^]]+)\](\s|.+?)\[\/url\]/i';
	#$r[] = '<a href="$1">$2</a>';
	#$f[] = '/\[url=([^\]]+)\]([^\[]+)\[\/url\]/ei';
	#$r[] = '"<a href=\"".urlfix("$1")."\">$2</a>"';

	$f[] = '/\[url\]"*(.+)"*\[\/url\]/';
	$r[] = '<a href="$1">Link</a>';

	// bold/italic/underline
	$f[] = '/\[b\](\s|.+?)\[\/b\]/is';
	$r[] = '<strong>$1</strong>';
	$f[] = '/\[i\](\s|.+?)\[\/i\]/is';
	$r[] = '<em>$1</em>';
	$f[] = '/\[u\](\s|.+?)\[\/u\]/is';
	$r[] = '<span style="text-decoration: underline;">$1</span>';

	// size
	#$f[] = '/\[size="*([a-z0-9]+)"*\](\s|.+?)\[\/size\]/ise';
	#$r[] = '\'<span style="font-size: \'.sizefix(\'$1\').\';">$2</span>\'';

	// color
	$f[] = '/\[color=\](\s|.+?)\[\/color\]/is';
	$r[] = '<span style="color: $1;">$2</span>';

	$f[] = '/\[color="([^"]+)"\](\s|.+?)\[\/color\]/is';
	$r[] = '<span style="color: $1;">$2</span>';


	// stuff that shouldn't be in, say, a shoutbox
	if ($min) {
		// Image with unspecified width/height
		$f[] = '/\[img\]"*(\s|.+?)"*\[\/img\]/i';
		$r[] = '<div class="pic"><img src="$1" alt="Image" /></div>';

		// Image with specified width/height
		$f[] = '/\[img width=(\d+) height=(\d+)\](\s|.+?)\[\/img\]/i';
		$r[] = '<div class="pic"><img width="$1" height="$2" src="$3" alt="Image"></div>';

		// Aligning stuff
		$f[] = '/\[align=(left|center|right)\](\s|.+?)\[\/align\]/is';
		$r[] = '<div style="text-align: $1;">$2</div>';

		// Centering
		$f[] = '/\[center\](\s|.+?)\[\/center\]/is';
		$r[] = '<div style="text-align: center;">$1</div>';

		// Quotes
		$f[] = '/\[quote\](\s|.+?)\[\/quote\]/is';
		$r[] = '<div class="quote border"><address class="border alt">Quote:</address><p>$1</p></div>';

		#$f[] = '/\[quote author=([^]]+) link=[^]]+ date=(\d+)\](\s|.+?)\[\/quote\]/ise';
		#$r[] = '\'<div class="quote border"><address class="border alt">Quote by $1 on \'.$ui->myDate(\'m/d/Y @ h:i A\', (int) $2).\':</address><p>$3</p></div>\'';
	}

	//foreach ($f as $k => $v)
	//$f[$k] .= 'u';

	// Return bbc'd string
	return preg_replace($f, $r, $s);
}

function smilies($s)
{
	global $sp;

	// Hold smilies
	$f = array();
	$r = array();

	// Smilies list
	$f[] = ':)';
	$r[] = '<img src="'.$sp['img'].'smilies/smiley.gif" alt=":)" />';
	$f[] = ';)';
	$r[] = '<img src="'.$sp['img'].'smilies/wink.gif" alt=";)" />';
	$f[] = ';D';
	$r[] = '<img src="'.$sp['img'].'smilies/grin.gif" alt=";D" />';
	$f[] = ':D';
	$r[] = '<img src="'.$sp['img'].'smilies/grin.gif" alt=";D" />';
	$f[] = 'xD';
	$r[] = '<img src="'.$sp['img'].'smilies/xd.gif" alt="xD" />';
	$f[] = ':(';
			$r[] = '<img src="'.$sp['img'].'smilies/sad.gif" alt=":(" />';
			$f[] = '8)';
	$r[] = '<img src="'.$sp['img'].'smilies/cool.gif" alt="8)" />';
	$f[] = ':P';
	$r[] = '<img src="'.$sp['img'].'smilies/tongue.gif" alt=":P" />';
	$f[] = ':love:';
	$r[] = '<img src="'.$sp['img'].'smilies/heart.gif" alt="Heart" />';
	$f[] = ':-/';
	$r[] = '<img src="'.$sp['img'].'smilies/undecided.gif" alt="Undecided" />';

	// Return smilied
	return str_ireplace($f, $r, $s);
}

// Deal with an ugly string
function stringprep($s, $htmlentities = false, $smilies = false, $bbc = false, $nlbr = false) {
	$s = stripslashes(trim(strip_tags($s)));
	$s = $htmlentities ? htmlspecialchars($s, ENT_NOQUOTES) : $s;
	$s = $bbc === true ? bbc($s) : $s;
	$s = $bbc === 2 ? bbc($s, true) : $s;
	$s = $smilies ? smilies($s) : $s;
	$s = $nlbr ? nl2br($s) : $s;
	return $s;
}

// Recursively deal with an ungly string
	function stringprep_recursive(&$ar) {
		foreach ($ar as $k => $v)
			$ar[$k] = stringprep($v);
		return $ar;
	}

// Go somewhere
function redirect($place) {

	// Go
	header('Location: '.$place);

	// Stop
	exit;
}

// get a file's file extension
function get_file_extension($s)
{
	// run regular expression on it
	if (preg_match('/^.+\.(.+)$/', $s, $m) == 0)
		return ''; // no extension?

	// trimmed lowercase extension
	$ext =  strtolower(trim($m[1]));

	// jp'e'g? meh
	$ext = $ext == 'jpeg' ? 'jpg' : $ext;

	// return it
	return $ext;
}

// Make a number of bytes look better than a number of bytes
	function pretty_size($b) {
		if ($b < 1048576)
			return round($b / 1024, 2) . 'KB';
		elseif ($b < 134217728)
			return round($b / 1048576, 2) . 'MB';
		else
			return $b . ' bytes';
	}

// Email a user something
function email_user($user, $subject = '(no subject)', $msg, $additional_headers = array())
{
	global $sql;

	// Deal with email headers
	$headers = array('Content-type: text/plain', 'From: The Soldat Mapping Showcase <noreply@jrgp.org>');
	array_merge($headers, $additional_headers);

	// Validate user / get email address and username
	$get_user = $sql->query("select `username`, `email` from `members` where `id` = '$user' limit 1");

	// Well?
	if ($sql->num($get_user) == 0)
		return false;

	// Get info
	list($username, $email) = $sql->fetch_row($get_user);
	$sql->free($get_user);

	$username = stringprep($username);
	$email = stringprep($email);
	$msg = trim($msg);

	// Validate email
	$email_validator = new EmailAddressValidator;
	if (!$email_validator->check_email_address($email))
		return false;

	// Message
	$message = "Dear $username,\n\n$msg\n\nRegards, The Soldat Mapping Showcase";

	// Lines can't be more than 70 characters long
	$message = wordwrap($message, 70);

	// Attempt the email
	if (!@mail($email, $subject, $message, implode("\r\n", $headers)))
		return false;

	// Done
	return true;
}

// Does this gametype id exist?
function gametype_exists($gid) {
	global $sql;
	$check = $sql->query("select count(*) from `gametypes` where `id` = '$gid' limit 1");
	list($rows) = $sql->fetch_row($check);
	$sql->free($check);
	return $rows == 1;
}

// Log a message to a file
function tmsLog($msg) {

	// Make the file in my home directory
	$u = @posix_getpwuid(@posix_getuid());
	$file = $u['dir'].'/tms_log.log';

	// Prepend timestamp and append newline
	$msg = '(' . date(DATE_FORMAT) . ") $msg\n";

	// Do it
	@file_put_contents($file, $msg, FILE_APPEND);
}

// From my USURP code
function file_download($path, $type = '', $name = '') {

	$path = realpath($path);

	// make sure it exists
	if (!is_file($path))
		return false;

	// dont allow for that which is evil.. (outputting a file under /etc)
	if (current((array) explode('/', $path, 2)) === '/etc')
		return false;

	// deal with absense of type
	if ($type == '' || empty($type))
		$type = get_file_extension($path);

	// deal with absense of name
	if ($name == '')
		$name = basename($path);

	$name = str_replace(array('/', '\\',' '), '_', $name);

	// determine how to handle it
	switch (strtolower($type)) {

		// images
		case 'png':
			$mime = 'image/png';
			$disposition = 'inline';
			break;
		case 'jpg':
		case 'jpeg':
			$mime = 'image/jpg';
			$disposition = 'inline';
			break;
		case 'gif':
			$mime = 'image/gif';
			$disposition = 'inline';
			break;

		// archives
		case 'zip':
			$mime = 'application/zip';
			$disposition = 'attachment';
			break;
		case 'rar':
			$mime = 'application/x-rar-compressed';
			$disposition = 'attachment';
			break;

		// text like
		case 'txt':
		case 'log':
		case 'pas':
		case 'ini':
		case 'json':
			$mime = 'text/plain';
			$disposition = 'attachment';
			#$disposition = 'inline';
		break;

		// something...else? just prompt to save it
		default:
			$mime = 'application/octet-stream';
			$disposition = 'attachment';
		break;
	}

	// date of last modification
	$last_modified = filemtime($path);

	// if person already has it downloaded then don't give it again
	// this should put a stop to lamers
	// (the $_SERVER['HTTP_IF_MODIFIED_SINCE'] var does not exist until we specify Last-Modified)
	if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $last_modified)
	{
		header('HTTP/1.1 304 Not Modified');
		exit;
	}

	// get on with it
	header('Content-type: '.$mime);
	header('Content-Disposition: '.$disposition.'; filename="'.$name.'"');
	header('Content-length: '.filesize($path));
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified) . ' GMT');

	// stuff it
	@readfile($path) or print(file_get_contents($path));

	// no farther must we go..
	exit;
}
