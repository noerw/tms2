<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

// Allow URL get params to be seperated by ; and not just &
function get_freak(&$desired_action) {
	$action_semi_array = explode(';', $desired_action);
	if (count($action_semi_array) > 1 && preg_match('/^[a-zA-Z0-9\_\-]+$/', $action_semi_array[0]))
	{
		$desired_action = $action_semi_array[0];
		foreach ($action_semi_array as $val)
		{
      $parts = explode('=', $val, 2);
      if (count($parts) == 2) {
        list($k, $v) = $parts;
        $_GET[$k] = $v;
      }
		}
	}
}

// Allow for shorthand url's
function lazy_urls() {

	// Gotta be able to access and change this
	global $desired_action;

	// ?map=25 goes to map profile of 25
	if (!isset($_GET['act']) && !isset($_GET['action']) && isset($_GET['map']) && ctype_digit($_GET['map'])) {
		$desired_action = 'map_rate';
	}

	// ?download=25 downloads map 25
	elseif (!isset($_GET['act']) && !isset($_GET['action']) && isset($_GET['download']) && ctype_digit($_GET['download'])) {
		$desired_action = 'download';
		$_GET['map'] = $_GET['download'];
		$_GET['sa'] = 'file';
	}

	// ?overview=25 goes to overview of map 25
	elseif (!isset($_GET['act']) && !isset($_GET['action']) && isset($_GET['overview']) && ctype_digit($_GET['overview'])) {
		$desired_action = 'download';
		$_GET['map'] = $_GET['overview'];
		$_GET['sa'] = 'pic';
	}

	// ?user=25 shows you user #25's maps
	elseif (!isset($_GET['act']) && !isset($_GET['action']) && isset($_GET['user']) && ctype_digit($_GET['user'])) {
		$desired_action = 'user_maps';
		$_GET['u'] = $_GET['user'];
	}
}

// Load important site settings
function load_site_settings() {
	global $sql, $site_settings;

	// Get them
	$get_settings = $sql->query("select `setting`, `value` from `settings`");

	// Stuff array
	while (list($k, $v) = $sql->fetch_row($get_settings))
		$site_settings[$k] = stripslashes($v);

	// Free ram
	$sql->free($get_settings);

	// Done
	return true;
}
