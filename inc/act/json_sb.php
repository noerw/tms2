<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Send out JSON shouts.
 */
// Get shouts
$get_shouts = $sql->query("select sb.`date`, sb.`user`, sb.`msg`, u.`username` from `shoutbox` as sb join `members` as u on u.`id` = sb.`user` order by sb.`id` desc limit 10");

// Hold them here
$shouts = array();

// Stuff it
for ($alt = false; $shout = $sql->fetch_row($get_shouts); $alt = !$alt) {
	$shouts[] = array(
		'date' => $shout[0],
		'name' => stringprep($shout[3]),
		'uid' => $shout[1],
		'msg' => stringprep($shout[2]),
		'alt' => $alt
	);
}

// Free
$sql->free($get_shouts);

ob_start('ob_gzhandler');

echo json_encode($shouts);

ob_end_flush();
