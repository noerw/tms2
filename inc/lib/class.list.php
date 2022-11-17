<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Deal with map lists
 */
class mapList {

	/*
	 * Hold sort filters and maps
	 */
	private
		$limit = array(0, 20),
		$author = false,
		$gametype = false,
		$year = false,
		$month = false,
		$sort = array('m.id', 'up'),
		$maps = array(),
		$downs = false,
		$motw = false,
		$scrots = true,
		$search_term = false,
		$search_descript = false,
		$csort = false,
		$num = 0;

	/*
	 * Change a boolean optional thing to get
	 */
	public function setBool($field, $opt) {
		if (is_bool($this->$field))
			$this->$field = (bool) $opt;
	}

	/*
	 * Choose author(s)
	 */
	public function setAuthor($author) {
		$this->author = is_array($author) ? array_unique($author) : $author;
	}

	/*
	 * Choose to search for something
	 */
	public function setSearch($term, $descript = false) {
		$this->search_term = $term;
		$this->search_descript = (bool) $descript;
	}

	/*
	 * Choose gametype
	 */
	public function setGametype($gametype) {
		if (is_numeric($gametype) && $gametype > 0)
			$this->gametype = $gametype;
	}

	/*
	 * Choose year
	 */
	public function setYear($year) {
		if (is_numeric($year) && $year > 0)
			$this->year = $year;
	}

	/*
	 * Choose month
	 */
	public function setMonth($month) {
		if (is_numeric($month) && $month > 0)
			$this->month = $month;
	}

	/*
	 * Decide limit / startat
	 */
	public function setRange($startat = 0, $limit = 20) {
		$this->limit = array($startat, $limit);
	}

	/*
	 * Choose what to sort by
	 */
	public function setSort($sort, $dir = 'up') {
		$this->sort = array($sort, $dir);
	}

	/*
	 * Something a bit more interesting
	 */
	public function setCSort($csort) {
		$this->csort = $csort;
	}

	/*
	 * Return number of maps with specified filters, if any
	 */
	public function getNum() {
		global $sql;

		// Query to only get number of rows returned
		$query = "
		select
			count(*)
		from
			maps as m
			".($this->downs == true ? "join map_downloads as d on d.mapid = m.id" : '')."
			".($this->motw == true ? "join mapoftheweek as motw on motw.mid = m.id" : '')."
		where
			".(is_numeric($this->author) ? "m.user = '{$this->author}' and" : '')."
			".(is_array($this->author) ? "find_in_set(m.user, '".implode(',', $this->author)."') and" : '')."
			".($this->gametype ? "m.gametype = '{$this->gametype}' and" : '')."
			".($this->year ? "year(from_unixtime(m.date)) = '{$this->year}' and" : '')."
			".($this->month ? "year(from_unixtime(m.date)) = '{$this->month}' and" : '')."
			".($this->search_term && !$this->search_descript ? "m.title like '{$this->search_term}' and" : '')."
			".($this->search_descript && $this->search_term ? "(m.info like '{$this->search_term}' or m.title like '{$this->search_term}') and" : '')."
			m.missing = '0'
		";

		// Run query
		$get_num = $sql->query($query);

		// Get number of maps
		list($num) = $sql->fetch_row($get_num);

		// Free query's ram
		$sql->free($get_num);

		// Localize
		$this->num = $num;

		// Return number of maps
		return $num;
	}

	/*
	 * Get all the maps we want into the $this->maps array
	 */
	public function fetchMaps() {
		global $sql;

		/*
		 * Query to get stuff
		 */
		$query = "
			select
				m.id,
				m.title,
				m.date,
				m.sc1,
				m.sc2,
				m.sc3,
				m.no_comments,
				(select avg(rating) from map_ratings where mapid = m.id and rating != 0) as rating,
				(select count(*) from map_ratings where mapid = m.id and rating != 0) as num_rats
				".($this->gametype == false ? ", m.gametype as gid, g.name as gn" : '')."
				".($this->downs ? ", d.file as fd, d.pic as od" : '')."
				".(!is_numeric($this->author) ? ", m.user as uid, u.username " : '')."
				".($this->motw == true ? ", motw.dl as motw_dl" : '')."
			from
				maps as m
				".($this->gametype == false ? " join gametypes as g on g.id = m.gametype" : '')."
				".(!is_numeric($this->author) ? "join members as u on u.id = m.user" : '')."
				".($this->downs == true ? "join map_downloads as d on d.mapid = m.id" : '')."
				".($this->motw == true ? "join mapoftheweek as motw on motw.mid = m.id" : '')."
			where
				".(is_numeric($this->author) ? "m.user = '{$this->author}' and" : '')."
				".(is_array($this->author) ? "find_in_set(m.user, '".implode(',', $this->author)."') and" : '')."
				".($this->gametype ? "m.gametype = '{$this->gametype}' and" : '')."
				".($this->year ? "year(from_unixtime(m.date)) = '{$this->year}' and" : '')."
				".($this->month ? "year(from_unixtime(m.date)) = '{$this->month}' and" : '')."
				".($this->search_term && !$this->search_descript ? "m.title like '{$this->search_term}' and" : '')."
				".($this->search_descript && $this->search_term ? "(m.info like '{$this->search_term}' or m.title like '{$this->search_term}') and" : '')."
				m.missing = '0'
			order by
				".(!$this->csort ? "{$this->sort[0]} ".($this->sort[1] == 'down' || $this->sort[1] == 'desc' ? 'desc' : 'asc') : $this->csort)."
			limit
			{$this->limit[0]}, {$this->limit[1]}
		";

		// Run query
		$get_maps = $sql->query_unbuff($query);

		/*
		 * Get maps
		 */
		while ($info = $sql->fetch_assoc($get_maps))
			$this->maps[] = $info;

		/*
		 * Free that
		 */
		$sql->free($get_maps);
	}

	/*
	 * List the maps
	 */
	public function showList($maps = false, $extra_fields = array()) {
		global $entry_point, $entry_point_sm, $sp, $ui;

		// Start list
		echo '
		<div class="flat_maps_list">';

		$alt = false;

		// What to list
		$show = is_array($maps) ? $maps : $this->maps;

		// Are there none?
		if (count($show) == 0) {
			echo '<p>No maps to show.</p></div>';
			return;
		}

		// List them
		foreach ($show as $info) {
			echo '
			<div class="gen_box_rh border',$alt ? ' alt' : '','">
				<div class="gen_box_rh_head border',$alt ? ' alt' : '','"><h4><a href="'.$entry_point_sm.'?map='.$info['id'].'">'.htmlspecialchars($info['title']).'</a>', !is_numeric($this->author) ? ' by '.userLink($info['uid'], $info['username']) : '','</h4></div>
				',$info['num_rats'] > 0 && $info['no_comments'] != '1' ? '<div class="gen_box_rh_head_r border'.($alt ? ' alt' : '').'">'.ceil($info['rating']).'/5</div>' : '','
				<div class="map_ov"><a href="'.$entry_point_sm.'?map='.$info['id'].'"><img alt="'.htmlspecialchars($info['title']).'" class="border" src="/t/'.$info['id'].':110x0" width="110" /></a></div>
				<div class="map_down">
					<a class="border" href="'.$entry_point_sm.'?map='.$info['id'].'">Map Profile</a>
					<a class="border" href="'.$entry_point_sm.'?download='.$info['id'].'">Download</a>
					<a class="border" href="'.$entry_point_sm.'?overview='.$info['id'].'">Overview</a>
				</div>
				<div class="gen_box_rh_content map_content border',$alt ? ' alt' : '','">
					<div class="map_info">
						<ul>
							',$this->gametype ? '' : '<li>Gametype: <a href="'.$entry_point.'?action=map_gt&amp;gametype='.$info['gid'].'">'.$info['gn'].'</a></li>','
							<li>Uploaded: '.$ui->myDate(DATE_FORMAT, $info['date']).'</li>';
						foreach ($extra_fields as $field)
							echo '<li>'.$field[0].': '.$info[$field[1]].'</li>';
						echo '
						</ul>
					</div>';

					// Show screenshots?
					if ($this->scrots == true)
					{
						// Are there some?
						if ($info['sc1'] != '' || $info['sc2'] != '' || $info['sc3'] != '') {
							// Do the ones that we do have
							$scrn = array();
							if ($info['sc1'] != '' && file_exists($sp['maps'] . str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $info['sc1'])))
								$scrn[] = 1;
							if ($info['sc2'] != '' && file_exists($sp['maps'] . str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $info['sc2'])))
								$scrn[] = 2;
							if ($info['sc3'] != '' && file_exists($sp['maps'] . str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $info['sc3'])))
								$scrn[] = 3;

							// Start list
							echo '
							<div class="map_sc">';

							// Go
							foreach ($scrn as $num) {
								echo '
								<a href="'.$entry_point.'?action=download&amp;sa=scr&amp;map='.$info['id'].'&amp;scn='.$num.'" title="Click to englarge">
									<img class="border" src="/t/'.$info['id'].':sc'.$num.':60x0" alt="Screenshot" width="60" />
								</a>';
							}

							// End list
							echo '
							</div>';
						}
					}
					echo '
				</div>
			</div>';

			// Alternate "row" bg's
			$alt = !$alt;
		}

		// End list
		echo '
		</div>';
	}
}
