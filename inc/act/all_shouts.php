<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Hmm. Userish?
if (isset($_GET['user']) && ctype_digit($_GET['user']) && ($username = uid2username($_GET['user']))) {
	$do_user = true;
	$user_id = (int) $_GET['user'];
}
else
	$do_user = false;

// Get number of shouts
list($num_shouts) = $sql->fetch_row($sql->query("select count(*) from `shoutbox`".($do_user ? " where `user` = '$user_id'" : '')));
$sql->freelast();

// Deal with pagination
$perpage = 50;
$pager = new Pagination;
$pager->setPerPage($perpage);
$pager->setRows($num_shouts);
$pager->setBaseUrl($entry_point.'?action='.SITE_ACTION.($do_user ? "&amp;user=$user_id" : ''));
$pager->init();

// Lister
$lister = new Threads;

// Get shouts
$get_shouts = $sql->query("
	select
		sb.`id`,
		sb.`user` as userid,
		sb.`date`,
		sb.`msg` as comment,
		u.`username`
	from
		`shoutbox` as sb
		join `members` as u on u.`id` = sb.`user`
	".($do_user ? " where sb.`user` = '$user_id' " : '')."
	order by
		sb.`id`
	desc
	limit ".$pager->getStartAt().", $perpage
");

// Stuff it
while ($info = $sql->fetch_assoc($get_shouts))
	$lister->items[] = $info;

$sql->free($get_shouts);

// Start layout
$layout->head('All '.number_format($num_shouts).' Shouts'.($do_user ? " by $username" : ''));

// Show pagination
$pager->showPagination();

// Show shouts
$lister->show();

// End layout
$layout->foot();
