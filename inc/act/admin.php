<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Must be logged in
$ui->loggedIn() or 
	$layout->errorMsg('Must be logged in');

// Must be admin or ELSE
$ui->isAdmin() or 
	$layout->errorMsg('Must be admin');

// Actions. Obviously inspired by SMF
$admin_actions = array(
	'home' => array('admin.php', 'home'),
	'map_downloads' => array('admin.php', 'map_downloads'),
	'poll' => array('admin.php', 'poll')
);

// Deal with current action
if (array_key_exists('section', $_GET) && preg_match('/^[a-z0-9\-\_]+$/i', $_GET['section']) == 1 && array_key_exists($_GET['section'], $admin_actions)) {
	require_once LIB . $admin_actions[$_GET][0];
	call_user_func($admin_actions[$_GET][1]);
}
else {
	require_once LIB . $admin_actions['home'][0];
	call_user_func($admin_actions['home'][1]);
}
