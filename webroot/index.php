<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

// Time us
$time_start = microtime(true);

// Anti hack. (allow files to be included)
define('in_tms', true);

// Get libraries from composer
require __DIR__ . '/../vendor/autoload.php';

// Determine paths
$local_path = dirname(__FILE__) . '/../';
$web_url = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$web_url .= substr($web_url, -1) != '/' ? '/' : ''; // ensure trailing slash
$root_url = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$root_url .= substr($root_url, -1) != '/' ? '/' : ''; // again, ensure trailing slash
$entry_point = $web_url . 'index.php';
$entry_point_sm = $web_url;

$sp = array(
	'acts' => $local_path.'inc/act/',
	'libs' => $local_path.'inc/lib/',
	'img' => $web_url.'/images/',
	'local_theme' => dirname(__FILE__) .'/theme/',
	'web_theme' => $web_url.'theme/',
	'avatars' => $local_path.'avatars/',
	'maps' => $local_path.'maps/',
	'textures' => $local_path.'resources/textures/',
	'sceneries' => $local_path.'resources/scenery/',
	'prefabs' => $local_path.'resources/prefabs/',
	'thumbs' => $local_path.'thumbs/',
	'scrn_thumbs' => $local_path.'screenshot_cache/',
	'tmp' => '/tmp/',
);

define('LIB', $sp['libs']);

// Load config file
require_once $local_path . 'config.php';

// Load resources
require_once LIB . 'class.mysql.php';	// Database handling
require_once LIB . 'class.user.php';	// User login management
require_once LIB . 'class.layout.php';	// HTML output
require_once LIB . 'class.pagination.php';	// Handle pagination
require_once LIB . 'class.map.php';	// A map
require_once LIB . 'class.list.php';	// List some stuff
require_once LIB . 'class.thumbs.php';	// Caching auto generated thumbnails
require_once LIB . 'class.threads.php';	// Stuff like forum posts, news articles, etc
require_once LIB . 'misc.php';		// Misc useful stuff
require_once LIB . 'maintenance.php';	// Functions for maintaining DB and such
require_once LIB . 'check_email.php';	// Email address validator
require_once LIB . 'image.php';		// Image caching/outputting/etc functions
require_once LIB . 'init.res.php';	// Functions the init script needs
require_once LIB . 'encryption.php';	// Helper functions around libsodium's secretbox

// Deal with current action, and initialize when ready
$desired_action = isset($_GET['action']) ? $_GET['action'] : (isset($_GET['act']) ? $_GET['act'] : 'home');

// oauth2 callback?
if (isset($_GET['state']) && strpos($_GET['state'], 'DISCORDAUTH_') === 0) {
  $desired_action = 'auth_discord';
}

// Allow for ; to take the place of & in url's
get_freak($desired_action);

// Default to home if it is funky
$desired_action = preg_match('/^[a-z0-9\-\_\;]+$/i', $desired_action) == 1 ? $desired_action : 'home';

// Allow for lazy (but really useful) url's, like ?map=69/?overview=69/?download=69 and so on
lazy_urls();

// If it exists, load it and set the current action for later reference
if (file_exists($sp['acts'] . $desired_action . '.php') && is_readable($sp['acts'] . $desired_action . '.php')) {
		define('SITE_ACTION', $desired_action);
		require_once $local_path . 'init.php';
		require_once $sp['acts'] . $desired_action . '.php';
}

// Otherwise load homepage, if it exists
elseif (file_exists($sp['acts'] . 'home.php') && is_readable($sp['acts'] . 'home.php')) {
		define('SITE_ACTION', 'home');
		require_once $local_path . 'init.php';
		require_once $sp['acts'] . 'home.php';
}

// Otherwise... :(
else {
	define('SITE_ACTION', '_act_issue');
	require_once $local_path . 'init.php';
	$layout->errorMsgMinimal('Cannot find a working default action. ');
}
