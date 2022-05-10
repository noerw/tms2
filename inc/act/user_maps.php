<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Get user ID
$uid = $sql->prot($_GET['u']);

// Check it / get username
$check_user = $sql->query("
select
	u.`username`,
	(select count(*) from `maps` as m where m.`missing` = '0' and m.`user` = u.`id`) as num_maps
from
	`members` as u
where
	u.`id` = '$uid'
limit 1");

// Non-existant?
if ($sql->num($check_user) == 0)
	$layout->errorMsg('Invalid user ID');

// Get username and num
list($username, $num_maps) = $sql->fetch_row($check_user);
$sql->free($check_user);

// Pagination
$perpage = MAPS_PER_PAGE;
$pager = new Pagination;
$pager->setPerPage($perpage);
$pager->setRows($num_maps);
$pager->setBaseUrl($entry_point_sm.'?user='.$uid);
$pager->init();

// Get our map lister
$list = new mapList;

// Make pagination sync with it
$list->setRange($pager->getStartAt(), $perpage);

// Want them by a certain user
$list->setAuthor($uid);

// Get them stored
$list->fetchMaps();

// Start layout
$layout->head('Maps by '.$username.' ('.$num_maps.')');

// Show pagination
$pager->showPagination();

// List them coolishly
$list->showList();

// End layout
$layout->foot();
