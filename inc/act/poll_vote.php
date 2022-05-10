<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Vote in a current poll
 */

// I must be logged in
if (!$ui->loggedIn())
	$layout->errorMsg('Must be logged in');

// Get poll and option id
$pid = $sql->prot($_POST['pid']);
$oid = $sql->prot($_POST['oid']);

// Get info for poll
$get_poll = $sql->query("select `locked` from `poll_questions` where `poll_id` = '$pid' limit 1");

/*
 * Validate things involved
 */
// Doesn't exist?
if ($sql->num($get_poll) == 0)
	$layout->errorMsg('Poll does not exist');

// Is it locked?
list($locked) = $sql->fetch_row($get_poll);
$sql->free($get_poll);
if ($locked == 1)
	$layout->errorMsg('Poll is locked');

// Validate option (make sure it belongs to this poll)
$get_option = $sql->query("select `poll_id` from `poll_options` where `option_id` = '$oid' limit 1");
if ($sql->num($get_option) == 0)
	$layout->errorMsg('That option does not exist.');
list($option_poll_id) = $sql->fetch_row($get_option);
$sql->free($get_option);

if ($option_poll_id != $pid)
	$layout->errorMsg('That option does not belong to this poll.');

// Lastly, make sure I have not voted in this poll yet
$has_voted = $sql->num($sql->query("select null from `poll_votes` where `poll_id` = '$pid' and `user_id` = '".$ui->userID()."' limit 1")) == 1;
$sql->freelast();

if ($has_voted)
	$layout->errorMsg('You have already voted.');

/*
 * Prepare to vote
 */

// Insert vote
$sql->query("insert into `poll_votes` set `poll_id` = '$pid', `option_id` = '$oid', `user_id` = '".$ui->userID()."', `date` = UNIX_TIMESTAMP()");

// Update poll's number of votes
$sql->query("update `poll_questions` set `total_votes` = `total_votes` + 1 where `poll_id` = '$pid' limit 1");

// Updateo option's number of votes
$sql->query("update `poll_options` set `votes` = `votes` + 1 where `option_id` = '$oid' limit 1");

/*
 * Done. Go back.
 */

$location = $_SERVER['HTTP_REFERER'] == '' ? $entry_point : $_SERVER['HTTP_REFERER'];

redirect($location);
