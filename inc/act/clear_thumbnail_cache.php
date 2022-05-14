<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// gotta be logged in
$ui->loggedIn() or
	$layout->errorMsg('Must be logged in');

// Get map ID
$mid = $sql->prot($_GET['map']);

// Get info for it
$get_info = $sql->query("
select
	`user`,
	`missing`
from
	`maps`
where
	`id` = '$mid'
limit 1
");

// Not existant?
if ($sql->num($get_info) == 0)
	$layout->errorMsg('Invalid map id');

// Get info
list($author_id, $is_missing) = $sql->fetch_row($get_info);
$sql->free($get_info);

// Not my map, or I'm not an admin
if (($author_id != $ui->userID()) && !$ui->isAdmin())
	$layout->errorMsg('This map is not yours');

// Is missing
if ($is_missing == 1)
	$layout->errorMsg('Map is missing');

// Do a confirmation
$layout->prompt('Really wipe thumbnail cache for this map?');

// Kill screenshot thumbs
try {
	// All 3
	for ($i = 1; $i <=3; $i++) {
		$cache = new SCN_Thumbs($mid, null, $i);
		$cache->remove(true);
	}

}
catch (cacheException $e) {}

// Kill overview thumb(s)
try {
	$cache = new OV_Thumbs($mid);
	$cache->remove(true);

}
catch (cacheException $e) {}

// Sucess?
$layout->errorMsg('Thumbs cleared. Regenerated upon next request.');
