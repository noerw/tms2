<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Start layout
$layout->head('News Archive');

/*
 * Show news
 */

$get_news = $sql->query("
select
	n.`date`,
	n.`news` as comment,
	u.`username`,
	n.`user` as userid
from
	`news` as n
	join `members` as u on u.`id` = n.`user`
order
	by n.`id`
");

// Load threader
$news_lister = new Threads;

// Stuff it
while ($info = $sql->fetch_assoc($get_news))
	$news_lister->items[] = $info;

// Save that ram
$sql->free($get_news);

// Show them
$news_lister->show();

// End layout
$layout->foot();
