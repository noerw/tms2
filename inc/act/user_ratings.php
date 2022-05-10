<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

// User ID
$uid = $sql->prot($_GET['u']);

// Get username / check uid / get num ratings
// (only get ratings for completely existing maps, hence the joins)
$check = $sql->query("
	select
		mr.`username`,
		(
			select
				count(*)
			from
				`map_ratings` as r
				join `maps` as m on m.`id` = r.`mapid`
				join `members` as u on u.`id` = m.`user`
			where
				r.`userid` = mr.`id` and
				r.`rating` > 0

		) as num_ratings
	from
		`members` as mr
	where
		mr.`id` = '$uid'
	limit 1
");

// Well?
if ($sql->num($check) == 0)
	$layout->errorMsg('Invalid user id');

// Get info
list($username, $num_ratings) = $sql->fetch_row($check);
$sql->free($check);

// Any?
if ($num_ratings == 0)
	$layout->errorMsg('This user has yet to rate any maps');

$username = stringprep($username, true);

// Pagination
$pager = new Pagination;
$perpage = 50;
$pager->setPerPage($perpage);
$pager->setRows($num_ratings);
$pager->setBaseUrl($entry_point.'?action=user_ratings&amp;u='.$uid);
$pager->init();

// Deal with rating output
$rating_lister = new Threads;

// Get ratings
$get_ratings = $sql->query("
	select
		r.`date`,
		r.`comment`,
		r.`mapid`,
		r.`rating`,
		m.`title` as map_title,
		m.`user` as map_user_id,
		u.`username` as map_user
	from
		`map_ratings` as r
		join `maps` as m on m.`id` = r.`mapid`
		join `members` as u on u.`id` = m.`user`
	where
		r.`userid` = '$uid'
	order by
		r.`id` desc
	limit
	".$pager->getStartAt().", $perpage
");

// Stuff rating lister
while ($info = $sql->fetch_assoc($get_ratings)) {
	$rating_lister->items[] = array(
		'userid' => $uid,
		'username' => $username,
		'date' => $info['date'],
		'comment' => $info['comment'],
		'tr' => 
		($info['rating'] == 0 ? '[comment]' : $info['rating'].'/5')
		
		.' &mdash; <a href="'.$entry_point_sm.'?map='.$info['mapid'].'">'.stringprep($info['map_title']) . '</a> by <a href="'.$entry_point.'?action=view_profile&amp;u='.$info['map_user_id'].'">' . stringprep($info['map_user']) . '</a>'
	);
}

// Free ram
$sql->free($get_ratings);

// Start layout
$layout->head('Map ratings by '.$username.' ('.number_format($num_ratings).')');

// Show pagination
$pager->showPagination();

// Show rates
$rating_lister->show();

// End layout
$layout->foot();
