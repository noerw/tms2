<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Make sure we can track what we download
 */
$_SESSION['maps_downloaded'] = is_array($_SESSION['maps_downloaded']) ? $_SESSION['maps_downloaded'] : array();
$_SESSION['ovs_downloaded'] = is_array($_SESSION['ovs_downloaded']) ? $_SESSION['ovs_downloaded'] : array();


/*
 * Get map id
 */
$mid = $sql->prot($_GET['map']);

/*
 * Get map info. Might not work.
 */
try {
	$map = new MapInfo($mid);
	$map_info = $map->getMinInfo();
}
catch (MapException $e) {
	exit($e->getMessage());
}

/*
 * Fix game mode prefixes.
 */
$map->fixModePrefixes();

/*
 * What're we doing?
 */
switch ($_GET['sa']) {
	
	/*
	 * Download a file
	 */
	case 'file':

		// File download disabled?
		if ($map_info->DOWNLOAD_DISABLED == '1')
			exit('Sorry, downloading this map is disabled. [on request of the author, ['.$map_info->AUTHOR_USERNAME.']');
		
		// No duplicate consecutive downloads from same ip
		$get_last_map = $sql->query("select l.map from map_download_log as l where l.ip = '".$_SERVER['REMOTE_ADDR']."' order by l.when desc limit 1");
		if ($sql->num($get_last_map) == 1)
			list($last_map_dl) = $sql->fetch_row($get_last_map);
		else
			$last_map_dl = null;
		$sql->free($get_last_map);


		// Inc download stat if we haven't download already and if we're not a bot and if last map downloaded from this ip isn't it if this map isn't mine
		if (!in_array($mid, $_SESSION['maps_downloaded']) && $last_map_dl != $mid && !$ui->isBot() && (!is_object($ui) || $ui->userID() != $map_info->AUTHOR_ID)) {
			
			// Inc the stat
			$sql->query("update `map_downloads` set `file` = `file` + 1 where `mapid` = '$mid' limit 1");
		
			// This week
			$week = date('oW');

			// Kill last week's
			$sql->query("delete from `mapoftheweek` where `wk` < '$week'");
			
			// Map of the week
			$sql->query("
			insert into `mapoftheweek`
			set
				`wk` = '$week',
				`mid` = '$mid',
				`dl` = '1'
			on duplicate key
				update `dl` = `dl` + 1
			");
			
			// Log download
			$sql->query("
				insert into `map_download_log` set 
					`when` = unix_timestamp(),
					`map` = '$mid',
					`who` = '".($ui->loggedIn() ? $ui->userID() : 0)."',
					`from` = '".$sql->prot($_SERVER['HTTP_REFERER'])."',
					`agent` = '".$sql->prot($_SERVER['HTTP_USER_AGENT'])."',
					`ip` = '".$_SERVER['REMOTE_ADDR']."'
			");
			
			// Don't do it again
			$_SESSION['maps_downloaded'][] = $mid;

		}

	
		// Get info on file
		try {
			$file_info = $map->archiveInfo();
		}
		catch (MapArchInfoException $e) {
			exit($e->getMessage());
		}
		
		// Uppercase PMS
		$ext = strtolower(get_file_extension($file_info['path']));
		$ext = $ext == 'pms' ? 'PMS' : $ext;

		// Fix filename
		$fn = str_replace(' ', '_', $map_info->MAP_NAME) . '.' . $ext;

		
		// Give it
		file_download($file_info['path'], null, $fn);
		
	break;
	
	/*
	 * View an image
	 */
	case 'pic':
		
		// Inc view stat if we haven' viewed already
		if (!in_array($mid, $_SESSION['ovs_downloaded']) && !$ui->isBot()) {
			
			// Inc the stat
			$sql->query("update `map_downloads` set `pic` = `pic` + 1 where `mapid` = '$mid' limit 1");
			
			// Don't do it again
			$_SESSION['ovs_downloaded'][] = $mid;

		}
	
		// Get info on pic
		try {
			$file_info = $map->overviewInfo();
		}
		catch (MapOvInfoException $e) {
			exit($e->getMessage());
		}

		// Output image
		output_image($file_info['path'], $map_info->MAP_NAME);

	break;
	
	/*
	 * One of the screenshots
	 */
	case 'scr':
	
		// Get info on pic
		try {
			$file_info = $map->scrnInfo($_GET['scn']);
		}
		catch (ScrnInfoException $e) {
			exit($e->getMessage());
		}

		// Output image
		output_image($file_info['path'], 'sc_'.$map_info->MAP_NAME);
	
	break;
	
	/*
	 * Dunno
	 */
	default:
		exit('Not sure what you want.');
	break;
}
