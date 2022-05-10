<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Change theme or width
if ($_GET['sa'] == 'width') {
	$_SESSION['site_width'] = $_GET['w'] == 'fluid' ? 'fluid' : 'fixed';
}

elseif ($_GET['sa'] == 'theme') {
	$desired_theme = trim($_POST['theme']);
	if (!ctype_digit($desired_theme) || !array_key_exists($desired_theme, $layout->themes))
		exit('Invalid theme');
	$ui->changeTheme($desired_theme);
}


redirect($_SERVER['HTTP_REFERER']);
