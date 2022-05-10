<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

// Deal with sessions
if (!in_array(SITE_ACTION, $non_user_actions)) {

	// Obligatory custom name
	session_name($session_name);

	// Start it
	session_start();

	// For easy insertion into things like url's, ajax calls, etc
	define('SID', session_name() . '=' . session_id());
}

// Initiate HTML output
$layout = new Layout;

// Load up MySQL
$sql = SQL::Fledging(array($dbi['server'], $dbi['user'], $dbi['pass'], $dbi['db'], false));

// Get site settings
$site_settings = array();
load_site_settings();

// User management class, if we need it.
if (!in_array(SITE_ACTION, $non_user_actions)) {

	// Load/create the class
	$ui = Ui::Fledging();

	// As well as a few more things, should we need them:
	if (!$ui->loggedIn()) {
		$layout->js[] = 'login.js';
		$layout->css[] = 'login.css';
	}
}

// Deal with stats?
if (!in_array(SITE_ACTION, $non_user_actions) && !in_array(SITE_ACTION, $non_hits_actions) && !$ui->isBot()) {
	$ui->manageOnline();
}

// Decide what theme to use
$layout->determineTheme();
