<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Exceptions for class in this file
 */
class MapException extends Exception {}
class MapOvInfoException extends Exception {}
class MapArchInfoException extends Exception {}
class ScrnInfoException extends Exception {}

/*
 * Map info class
 */
class MapInfo {

	protected $mid, $uid, $uName, $info, $img_path, $arch_path, $a_info, $_sql;

	/*
	 * Start us off by localizing stuff
	 */
	function __construct($mid) {

		// Localize SQL
		$this->_sql = SQL::Fledging();

		// Validate map id
		if (!ctype_digit($mid) || $mid == 0)
			throw new MapException('Invalid map ID - '.$mid);

		// Localize map id
		$this->mid = $this->_sql->prot($mid);

	}

	/*
	 * Does it exist and isn't missing?
	 */
	function isReal() {
		$check = $this->_sql->query("select `missing` from `maps` where `id` = '{$this->mid}' limit 1");
		if ($this->_sql->num($check) == 0) {
			$this->_sql->free($check);
			return false;
		}
		list($missing) = $this->_sql->fetch_row($check);
		$this->_sql->free($check);
		return $missing == 0;
	}

	/*
	 * Convert object of info $info to array of info $a_info
	 */
	function infoArray() {
		foreach ($this->info as $k => $v)
			$this->a_info[$k] = $v;
	}

	/*
	 * Get a little bit of info
	 */
	function getMinInfo() {
		$get = $this->_sql->query("
		select
			m.user as AUTHOR_ID,
			a.username as AUTHOR_USERNAME,
			m.title as MAP_NAME,
			m.img as PICTURE_PATH,
			m.url as FILE_PATH,
			m.download_disabled as DOWNLOAD_DISABLED,
			m.sc1 as SC1_PATH,
			m.sc2 as SC2_PATH,
			m.sc3 as SC3_PATH,
			m.gametype as GAMETYPE_ID,
			m.info as INFO,
			m.missing
		from
			maps as m
			join members as a on a.id = m.user
		where
			m.id = '{$this->mid}'
		limit 1
		");

		if ($this->_sql->num($get) == 0)
			throw new MapException('No map with this ID');

		$this->info = $this->_sql->fetch_object($get);

		if ($this->info->missing == '1')
			throw new MapException('Map files are missing');

		$this->uid = $this->info->AUTHOR_ID;

		$this->uName = stringprep($this->info->AUTHOR_USERNAME);

		array_walk($this->info, create_function('&$v, $k', '$v = stringprep($v);'));

		return $this->info;
	}

	/*
	 * Get stats
	 * Simple. So omit some checks and additional useless info
	 */
	function getStatsInfo() {
		$get = $this->_sql->query("
		select
			d.file as DOWNLOADS_FILE,
			d.pic as DOWNLOADS_PICTURE,
			motw.dl as DOWNLOADS_WEEK,
			m.missing
		from
			maps as m
			join map_downloads as d on d.mapid = m.id
			left join mapoftheweek as motw on motw.mid = m.id
		where
			m.id = '{$this->mid}'
		limit 1
		");

		if ($this->_sql->num($get) == 0)
			throw new MapException('No map with this ID');

		$this->info = $this->_sql->fetch_object($get);

		return $this->info;
	}

	/*
	 * Get a little more info
	 */
	function getSomeInfo() {
		$get = $this->_sql->query("
		select
			m.user as AUTHOR_ID,
			a.username as AUTHOR_USERNAME,
			m.img as PICTURE_PATH,
			m.url as FILE_PATH,
			m.title as MAP_NAME,
			m.sc1 as SC1_PATH,
			m.sc2 as SC2_PATH,
			m.sc3 as SC3_PATH,
			m.missing
		from
			maps as m
			join members as a on a.id = m.user
		where
			m.id = '{$this->mid}'
		limit 1
		");

		if ($this->_sql->num($get) == 0)
			throw new MapException('No map with this ID');

		$this->info = $this->_sql->fetch_object($get);

		$this->uid = $this->info->AUTHOR_ID;

		$this->uName = stringprep($this->info->AUTHOR_USERNAME);

		array_walk($this->info, create_function('&$v, $k', '$v = stringprep($v);'));

		return $this->info;

	}

	/*
	 * Get a full dossier on this map
	 */
	function getAllInfo() {

		$get = $this->_sql->query("
		select
			m.user as AUTHOR_ID,
			a.username as AUTHOR_USERNAME,
			a.email as AUTHOR_EMAIL,
			(select count(*) from maps where user = m.user and missing = '0') as AUTHOR_NUM_MAPS,
			a.pending as AUTHOR_PENDING,
			a.mr_notif as AUTHOR_MR_NOTIF,
			m.img as PICTURE_PATH,
			m.url as FILE_PATH,
			m.title as MAP_NAME,
			m.date as UPLOAD_DATE,
			m.lastedit as EDIT_DATE,
			m.beta as IS_BETA,
			m.gametype as GAMETYPE_ID,
			g.name as GAMETYPE_NAME,
			m.info as INFO,
			m.sc1 as SC1_PATH,
			m.sc2 as SC2_PATH,
			m.sc3 as SC3_PATH,
			m.no_comments as NO_COMMENTS,
			m.missing as MISSING,
			m.download_disabled as DOWNLOAD_DISABLED,
			m.rec_players_start as REC_PLAYERS_START,
			m.rec_players_end as REC_PLAYERS_END,
			d.file as DOWNLOADS_FILE,
			d.pic as DOWNLOADS_PICTURE,
			motw.dl as DOWNLOADS_WEEK,
			(select avg(rating) from map_ratings where mapid = m.id and rating != '0') as RATING,
			(select count(*) from map_ratings where mapid = m.id) as NUMRATINGS
		from
			maps as m
			left join map_downloads as d on d.mapid = m.id
			join gametypes as g on g.id = m.gametype
			join members as a on a.id = m.user
			left join mapoftheweek as motw on motw.mid = m.id
		where
			m.id = '{$this->mid}'
		limit 1
		");

		if ($this->_sql->num($get) == 0)
			throw new MapException('No map with this ID');

		$this->info = $this->_sql->fetch_object($get);

		$this->uid = $this->info->AUTHOR_ID;

		$this->uName = stringprep($this->info->AUTHOR_USERNAME);

		array_walk($this->info, create_function('&$v, $k', '$v = stringprep($v);'));

		return $this->info;
	}

	/*
	 * Make sure map name has appropriate prefixes for gamemode
	 */
	function fixModePrefixes() {
		switch ($this->info->GAMETYPE_ID) {
			case 4:
				if (strtolower(substr($this->info->MAP_NAME, 0, 4))  != 'ctf_')
					$this->info->MAP_NAME = 'ctf_'.$this->info->MAP_NAME;
			break;
			case 3:
				if (strtolower(substr($this->info->MAP_NAME, 0, 4))  != 'inf_')
					$this->info->MAP_NAME = 'inf_'.$this->info->MAP_NAME;
			break;
			case 12:
				if (strtolower(substr($this->info->MAP_NAME, 0, 4))  != 'htf_')
					$this->info->MAP_NAME = 'htf_'.$this->info->MAP_NAME;
			break;
		}
	}

	/*
	 * Following are related to file downloading
	 */

	// Get stats on overview
	function overviewInfo() {
		global $sp;

		if (!isset($this->info->PICTURE_PATH))
			throw new MapOvInfoException('Do not know ov path');

		$this->img_path = $sp['maps'] . str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $this->info->PICTURE_PATH);

		// Doesn't exist?
		if (!is_file($this->img_path))
			throw new MapOvInfoException('Cannot find overview file');

		// Get info
		if (!($img_info = @getimagesize($this->img_path)))
			throw new MapOvInfoException('Cannot get info on overview file');

		// Give it
		return array(
			'width' => $img_info[0],
			'height' => $img_info[1],
			'size' => filesize($this->img_path),
			'type' => image_type_to_extension($img_info[2], false),
			'path' => $this->img_path
		);
	}

	// Get stats on main map archive!
	function archiveInfo() {
		global $sp;

		if (!isset($this->info->FILE_PATH))
			throw new MapArchInfoException('Do not know ov path');

		$this->arch_path = $sp['maps'] . str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $this->info->FILE_PATH);

		// Doesn't exist?
		if (!is_file($this->arch_path))
			throw new MapArchInfoException('Cannot find archive file: '.$this->arch_path);

		// Ex
		$ext = get_file_extension(basename($this->arch_path));

		// Give it
		return array(
			'size' => filesize($this->arch_path),
			'type' => $ext,
			'path' => $this->arch_path
		);
	}

	// Get info on one of the three screenshots
	function scrnInfo($scrn) {

		global $sp;

		// Make sure it's a number frome one to three
		if (!ctype_digit($scrn) || !in_array($scrn, range(1,3)))
			throw new ScrnInfoException('Invalid screenshot number - '.$scrn);

		// Need it
		$this->infoArray();

		// This should be it
		if (!isset($this->a_info['SC'.$scrn.'_PATH']))
			throw new ScrnInfoException('Do not know screenshot info.');

		// Does not exist?
		if (trim($this->a_info['SC'.$scrn.'_PATH']) == '')
			throw new ScrnInfoException('This screenshot probably does not exist.');

		// Get path
		$path = $sp['maps'] . str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $this->a_info['SC'.$scrn.'_PATH']);

		// Exist literally?
		if (!is_file($path))
			throw new ScrnInfoException('Cannot find screenshot file.');

		// Get info
		if (!($img_info = @getimagesize($path)))
			throw new MapOvInfoException('Cannot get info on screenshot file');

		// Ex
		$ext = get_file_extension(basename($path));

		// Give it
		return array(
			'width' => $img_info[0],
			'height' => $img_info[1],
			'size' => filesize($path),
			'type' => $ext,
			'path' => $path
		);
	}
}
