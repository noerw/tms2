<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Must be logged in 
if (!$ui->loggedIn())
	$layout->errorMsg('Not logged in');

// Are we shouting?
if (@$_POST['sb_do_shout'] != 'yes')
	$layout->errorMsg('Not shouting?');

// Make sure we're verified
if ($_POST[$ui->verifKey()] != $ui->verifVal())
	$layout->errorMsg('Invalid verification key');

// Get date of last post
list($last_post, $db_time) = $sql->fetch_row($sql->query("
	select
		`date`,
		UNIX_TIMESTAMP()
	from
		`shoutbox`
	where
		`user` = '".$ui->userID()."'
	order by `id` desc limit 1"));
$sql->freelast();

// Spam protection. Gotta be two minutes between shouts by the same guy
if (is_numeric($last_Post) && ($last_post > 0) && (($db_time - $last_post) < 120))
	$layout->errorMsg('You cannot shout more than once in two minutes.');

// Get post
$post = $sql->prot(trim($_POST['sb_main_msg']));

// Can't be nothing
if ($post == '')
	$layout->errorMsg('You didn\'t say anything');

// Insert shout
$sql->query("insert into `shoutbox` set `date` = UNIX_TIMESTAMP(), `msg` = '$post', `user` = '".$ui->userID()."'");

// Where to go
$go = trim($_SERVER['HTTP_REFERER']) == '' ? $entry_point : $_SERVER['HTTP_REFERER'];

// Go there
redirect($go);
