<?php

/*
 * Allow us to set cookies with these things
 */
$allowed_areas = array('poll_content','shoutbox_content','last_downloaded_maps_content','theme_changer_content', 'powersearch_form_content');
$allowed_values = array(1,2);

/*
 * Set the cookie
 */
function change($key, $value) {
	// First kill possibly old one
	setcookie('pref_'.$key, '', time() - 40000);

	// Set new
	setcookie('pref_'.$key, $value, time() + 40000, '/');
}

/*
 * Are we going to change something?
 */
if (isset($_GET['key']) && in_array($_GET['key'], $allowed_areas) && isset($_GET['val']) && in_array($_GET['val'], $allowed_values))
{
	change($_GET['key'], $_GET['val']);
	exit('ok?');
}
else
	exit('Invalid params');
