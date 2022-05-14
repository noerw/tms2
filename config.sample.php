<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

// maps per view page. default
define('MAPS_PER_PAGE', 20);

//mysql config
$dbi = array(
'server' => 'localhost',
'user' => '',
'pass' => '',
'db' => ''
);

//max file sizes (must be in bytes)
$max_up_size = array(
	'map_file' =>	20485760,	//10 megs
	'map_pic' =>	6145728,	//3 megs
	'avatar' =>	20480,		//20 kilobytes
	'resources' =>	786432,		//.75 meg
	'prefabs' =>	786432		//.75 meg
);

//allowed file extensions
$allowed_map_exts = array('zip','rar','pms', 'tgz','json');
$allowed_picture_exts = array('png','jpg','jpeg', 'gif');
$allowed_resource_exts = array('png','jpg','jpeg');
$allowed_prefab_exts = array('rar','zip','pfb');

//site admins
$site_admins = array(1);
//forum admins
$forum_admins = array(1);
//poll admins
$poll_admins = array(1);
//news admins
$news_admins = array(1);

//actions that do not require user, hits, or session management
$non_user_actions = array(
	'map_thumbnail',
	'screenshot_thumbnail',
	'querynumdebug',
	'sigpic',
	'image_download_count',
	'_act_issue'
);

//actions that do require user management, but not hits
$non_hits_actions = array(
	'download' //needed to prevent member from making their own map's download count higher
);

// Name of session
$session_name = 'tmssession';

// Login remember cookie
define('REMEMBER_COOKIE', '');

// Cookie encryption code. Generate a code using "echo hex2bin(sodium_crypto_secretbox_keygen());"
define('REMEMBER_COOKIE_KEY', '');

// actions forced to use fluid layout
$fluid_layout_actions = array('forum', 'tutorials', 'my_maps');

// usual date format
define('DATE_FORMAT', 'm/d/Y @ h:i A');

// Gifted peoples
define('SITE_ADMINS', serialize(array(1)));

// Discord oauth2
define('DISCORD_CLIENT_ID', '');
define('DISCORD_SECRET_KEY', '');
