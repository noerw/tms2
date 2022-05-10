<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Am I logged in and already activated?
if ($ui->loggedIn() && $ui->userActivated())
	$layout->errorMsg('You\'re already logged in and activated.');

// Get user ID
$uid = $sql->prot($_GET['u']);

// Get code
$code = $sql->prot(trim($_GET['c']));

// See if this guy needs activation
$check = $sql->query("
	select
		`pending`
	from
		`members`
	where
		`id` = '$uid'
	limit 1
");

// User doesn't exist?
if ($sql->num($check) == 0)
	$layout->errorMsg('That user does not exist');

// It does, get activation status
list($activation_status) = $sql->fetch_row($check);
$sql->free($check);

// Already activated?
if ($activation_status == 0)
	$layout->errorMsg('This account is already activated');

// Does this code not match the code in the db?
if ($code != $activation_status || strlen($code) != 32)
	$layout->errorMsg('Invalid auth code');

// It does. Activate account and say so
$sql->query("update `members` set `pending` = '0' where `id` = '$uid' limit 1");
$layout->notifMsg('Account has been activated');
