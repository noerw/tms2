<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Get user
$user = $sql->prot($_GET['u']);

// See if
$check = $sql->query("select `avatar_url` from `members` where `id` = '$user' limit 1");

// User id wrong?
if ($sql->num($check) == 0)
	exit('Invalid user ID '.$user);

// Get avatar url.
list($ave) = $sql->fetch_row($check);
$sql->free($check);

// Fix path to it
$ave = realpath( $sp['avatars'] . basename($ave) );

// It exist?
if (is_file($ave)) {
	output_image($ave, true);
	exit;
}

// Otherwise do generic avy
output_image($sp['local_theme'].'/default_ave.png');

