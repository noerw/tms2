<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Must be logged in
if (!$ui->loggedIn())
	$layout->errorMsg('Must be logged in');

// Get map ID
$mid = $sql->prot($_POST['mid']);

// Make sure map is real, not missing, and doesn't have comments disabled, and get authors pref
$check_map = $sql->query("
select
	m.`user`,
	m.`title`,
	m.`missing`,
	m.`no_comments`,
	u.`mr_notif`
from
	`maps` as m
	join `members` as u on u.`id` = m.`user`
where
	m.`id` = '$mid'
limit 1
");

// Not a real map?
if ($sql->num($check_map) == 0)
	$layout->errorMsg('Map not found');

// Get info
list($uid, $title, $missing, $disabled, $email) = $sql->fetch_row($check_map);

if ($missing == '1')
	$layout->errorMsg('I am not letting you comment on a missing map');

if ($disabled == '1')
	$layout->errorMsg('Author disabled comments/ratings');

// mk, get working..

// Have I rated this map already?
if ($uid != $ui->userID()) {
	$rated = $sql->num($sql->query("select null from `map_ratings` where `mapid` = '$mid' and`userid` = '".$ui->userID()."' and `rating` > 0 limit 1")) == 1;
	$sql->freelast();

	// Snag rating
	$rating = $sql->prot($_POST['rating']);

	// If not, make sure I'm doin' it right
	if (!$rated && (!is_numeric($rating) || $rating < 1 || $rating > 5))
		$layout->errorMsg('You must choose a rating.');
}

// Make sure I supplied a commment
$comment = $sql->prot(trim($_POST['comment']));

// Not there?
if ($comment == '')
	$layout->errorMsg('You must fill in the comment field.');

// Okay, reply
$sql->query("
	insert into `map_ratings`
	set
		`mapid` = '$mid',
		`userid` = '".$ui->userID()."',
		`comment` = '$comment',
		`date` = UNIX_TIMESTAMP(),
		`ip` = '".$_SERVER['REMOTE_ADDR']."',
		`rating` = '".($rated || $uid != $ui->userID() ? '0' : $rating)."'
");

// Notify author of this rating/comment, if I am not the author
if ($email == '1' && $uid != $ui->userID()) {
	email_user(
		$uid,
		$title.' was commented upon',
		$ui->userName()." has posted a comment to your map, $title\n$entry_point?map=$mid"
	);
}

// Go back
redirect($entry_point_sm.'?map='.$mid);
