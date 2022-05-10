<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Get them
$get_maps = $sql->query("
select
	m.`title`,
	m.`id`,
	u.`username`,
	m.`user`
from
	`maps` as m
	join `members` as u on u.`id` = m.`user`
where
	m.`missing` = '0'
");

$maps = array();

while ($m = $sql->fetch_assoc($get_maps)) {
//	$maps[] = stringprep_recursive($m);
	$maps[] = $m;
}

$sql->free($get_maps);

#header('Content-type: application/json');
header('Content-type: application/x-javascript');
ob_start('ob_gzhandler');
echo json_encode(array('maps' => $maps));
ob_end_flush();
