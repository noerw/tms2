<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Usual and ever popular map profile page!
 */

/*
 * Get map ID
 */
$mid = $sql->prot($_GET['map']);

/*
 * Get info on map
 */
try {
	$map = new MapInfo($mid);
	$map_info = $map->getAllInfo();
}
catch (MapException $e){
	$layout->errorMsg($e->getMessage());
}

/*
 * Must be there.
 */

if ($map_info->MISSING)
	$layout->error_message('Map files are missing. Sorry.');

// We need a special stylesheet for this file
$layout->add_dep('css', 'map_profile.css');

// OG tags
$layout->add_meta('og:title', $map_info->MAP_NAME);
$layout->add_meta('og:url', $entry_point_sm.'?map='.$mid);
$layout->add_meta('og:image', $entry_point_sm.'t/'.$mid);
$layout->add_meta('og:updated_time', empty($map_info->EDIT_DATE) ? $map_info->UPLOAD_DATE : $map_info->EDIT_DATE);
$layout->add_meta('og:description', $map_info->MAP_NAME . ' by ' . $map_info->AUTHOR_USERNAME);

// Start out layout
$layout->head($map_info->MAP_NAME);

// is this map mine?
if ($ui->loggedIn() && $ui->userID() == $map_info->AUTHOR_ID) {
	echo '<p>
		This is your map;
		would you like to <a href="'.$entry_point.'?action=my_maps;sa=edit;mid='.$mid.'">edit it</a> or
		<a href="'.$entry_point.'?action=auto_bbc&amp;map='.$mid.'">generate bbc</a>?</p>';
}

// Am I an admin though?
elseif ($ui->loggedIn() && $ui->isAdmin()) {
	echo '<p>
		This is not your map; and I\'ll let you <a href="'.$entry_point.'?action=my_maps;sa=edit;mid='.$mid.'">edit it</a> anyway.
	</p>';
}

// Show a little author profile?
echo '
<div class="map_author border">
	<div class="map_author_name border">
		by '.userLink($map_info->AUTHOR_ID, $map_info->AUTHOR_USERNAME).'
	</div>
	<div class="ov_hidden">
		<div style="background-image: url(\''.$entry_point.'?action=view_avatar;u='.$map_info->AUTHOR_ID.'\');" class="map_author_avatar border"></div>
		<div class="map_author_more_txt">
			<a href="'.$entry_point_sm.'?user='.$map_info->AUTHOR_ID.'">More by this artist ('.$map_info->AUTHOR_NUM_MAPS.'):</a>
		</div>
		<div class="map_author_more">';

		// Get more maps by this user
		// If the user has more than 7 maps, don't include current map in list
		$get_more = $sql->query("
		select
			`id`,
			`title`
		from
			`maps`
		where
			`user` = '".$map_info->AUTHOR_ID."' and
			`missing` = '0'
			".($map_info->AUTHOR_NUM_MAPS > 7 ? " and `id` != '$mid'"  : '')."
		order by
			rand()
		limit 7");

		// Show thumbnail links to them
		while (list($tmid, $tname) = $sql->fetch_row($get_more)) {
			$tname = stringprep($tname, true);
			echo '
			<a href="'.$entry_point_sm.'?map='.$tmid.'" title="'.$tname.'"><img class="border" src="/t/'.$tmid.':45x45" alt="'.$tname.'" /></a>';
		}

		// Free ram it used
		$sql->free($get_more);

		echo '
		</div>
		<div class="map_author_actions border">
			<ul>
				<li><a href="'.$entry_point.'?action=view_profile&amp;u='.$map_info->AUTHOR_ID.'">Profile</a></li>
				<li><a href="'.$entry_point.'?user='.$map_info->AUTHOR_ID.'">All Maps</a></li>
				<li><a href="'.$entry_point.'?action=pm&amp;sa=compose&amp;u='.$map_info->AUTHOR_ID.'">Send PM</a></li>
			</ul>
		</div>
	</div>
</div>';

// Show womping overview pic
echo '
<div class="gen_box_rh border">
	<div class="gen_box_rh_head border">
		<h4>Overview</h4>
	</div>
	<div class="gen_box_rh_content overview center">
		<a href="'.$entry_point.'?overview='.$mid.'" title="Click to enlarge">
			<img class="border border4" width="530" src="'.$entry_point.'?overview='.$mid.'" alt="" title="Click to enlarge" />
		</a>
		<div class="sub">';

		// Getting info on ov pic can be problematic
		try {
			$ovinfo = $map->overviewInfo();
			if ($ovinfo['type'] == 'bmp')
				echo 'Variable length Converted jpg';
			else
				echo $ovinfo['type'].' - '.$ovinfo['width'].'x'.$ovinfo['height'].' - '.pretty_size($ovinfo['size']);
		}
		catch (MapOvInfoException $e) {
			echo 'Error: '.$e->getMessage();
		}

	echo '
		</div>
	</div>
</div>';

// Screenshot time!
echo '
<div class="gen_box_rh border">
	<div class="gen_box_rh_head border">
		<h4>Screenshots</h4>
	</div>
	<div class="gen_box_rh_content screenshots">';

	// Are there none?
	if ($map_info->SC1_PATH == '' && $map_info->SC2_PATH == ''&& $map_info->SC3_PATH == '')
		echo 'None.';

	// We have at least one:
	else {
		// Do the ones that we do have
		$scrn = array();
		if ($map_info->SC1_PATH != '')
			$scrn[] = 1;
		if ($map_info->SC2_PATH != '')
			$scrn[] = 2;
		if ($map_info->SC3_PATH != '')
			$scrn[] = 3;

		// Go
		foreach ($scrn as $num) {
			echo '
			<a href="'.$entry_point.'?action=download&amp;sa=scr&amp;map='.$mid.'&amp;scn='.$num.'" title="Click to englarge">
				<img class="border border4" src="/t/'.$mid.':sc'.$num.':150x112" alt="Screenshot" width="150" height="112" />
			</a>';
		}
	}
	echo '
	</div>
</div>';

// Download button
echo '
<div class="gen_box_rh border">
	<div class="gen_box_rh_content download">
		<a class="border" href="'.$entry_point.'?download='.$mid.'" id="btn">Download</a>
		<div class="sub">';
		// Getting info on archive can be problematic
		try {
			$archinfo = $map->archiveInfo();
			echo strtoupper($archinfo['type']).' - '.pretty_size($archinfo['size']);
		}
		catch (MapArchInfoException $e) {
			echo 'Error: '.$e->getMessage();
		}
		echo '
		</div>
	</div>
</div>';

// Decription
echo '
<div class="gen_box_rh border">
	<div class="gen_box_rh_head border">
		<h4>Author\'s Description</h4>
	</div>
	<div class="gen_box_rh_content">
		<p>',$map_info->INFO == '' ? '<em>none</em>' : stringprep($map_info->INFO, true, true, true, true),'</p>
	</div>
</div>';


// Details?
echo '
<div class="gen_box_rh border">
	<div class="gen_box_rh_head border">
		<h4>Details</h4>
	</div>
	<div class="gen_box_rh_content">
		<ul>
			<li>Date uploaded: '.$ui->myDate('m/d/y @ h:i A', $map_info->UPLOAD_DATE).'</li>
			<li>Date revised: ',empty($map_info->EDIT_DATE) ? '<em>never</em>' : $ui->myDate('m/d/y @ h:i A', $map_info->EDIT_DATE),'</li>
			<li>Gametype: <a href="'.$entry_point.'?action=map_gt&amp;gametype='.$map_info->GAMETYPE_ID.'">'.$map_info->GAMETYPE_NAME.'</a></li>
			<li>Average Rating: ', $map_info->RATING ? ceil($map_info->RATING) . '/5 Stars' : '<em>not yet rated</em>','</li>
			<li>Overview Link: <a href="'.$entry_point_sm.'?overview='.$mid.'">'.$entry_point_sm.'?overview='.$mid.'</a></li>
			<li>Download Link: <a href="'.$entry_point_sm.'?download='.$mid.'">'.$entry_point_sm.'?download='.$mid.'</a></li>
		</ul>
	</div>
</div>';


// Stats?
echo '
<div class="gen_box_rh border">
	<div class="gen_box_rh_head border">
		<h4>Statistics</h4>
	</div>
	<div class="gen_box_rh_content">
		<ul>
			<li>Total downloads: '.number_format($map_info->DOWNLOADS_FILE).'</li>
			<li>This week: ',ctype_digit($map_info->DOWNLOADS_WEEK) ? number_format($map_info->DOWNLOADS_WEEK) : 0,'</li>
			<li>Overview hits: '.number_format($map_info->DOWNLOADS_PICTURE).'</li>
		</ul>
	</div>
</div>';

// Ratings

// Not disabled?
if ($map_info->NO_COMMENTS != '1') {

	echo '
	<h2 id="comments"><span>Ratings / Comments ('.$map_info->NUMRATINGS.')</span></h2>
	';

	// Get them
	$get_ratings = $sql->query("
	select
		u.`username`,
		c.`userid`,
		c.`date`,
		c.`comment`,
		c.`rating`
	from
		`map_ratings` as c
		join `members` as u on u.`id` = c.`userid`
	where
		`mapid` = '$mid'
	order by c.`id` asc
	");

	// None?
	if ($sql->num($get_ratings) == 0) {
		echo '<p>None to speak of.</p>';
	}
	else {

		// Snag threads class
		$rating_lister = new Threads;

		// Stuff it
		while ($info = $sql->fetch_row($get_ratings))
			$rating_lister->items[] = array(
				'username' => $info[0],
				'userid' => $info[1],
				'date' => $info[2],
				'comment' => $info[3],
				'tr' => ($info[4] > 0 ? $info[4].'/5 stars' : false)
			);

		// Free ram
		$sql->free($get_ratings);

		// Show ratings
		$rating_lister->show();
	}

	// Yourself
	echo '
	<h2><span>Rate / Comment</span></h2>';

	// Show map/rate comment form stuff if logged in
	if ($ui->loggedIn()) {

		// Have I rated?
		$rated = $sql->num($sql->query("select null from `map_ratings` where `mapid` = '$mid' and`userid` = '".$ui->userID()."' and `rating` > 0 limit 1")) == 1;
		$sql->freelast();

		// Start form
		echo '
		<form action="'.$entry_point.'?action=map_rate_reply" method="post">
			<div id="map_rate_form">';

				// Show rating field if we haven't yet.
				// And if this map is not mine. If it is, only allow commenting
				if (!$rated && $map_info->AUTHOR_ID != $ui->userID()) {
					echo '
					<label for="rating">Rating:</label>
					<select id="rating" name="rating">
						<option value="">Choose</option>';

						// Show 1-5
						for ($i = 1; $i <= 5; $i++)
							echo '<option value="'.$i.'">'.$i.'/5</option>';

					echo '
					</select><br />';
				}

			// Continue with form
			echo '
				<label for="map_rate_comment">Your comment:</label><br />
				<textarea id="map_rate_comment" name="comment" rows="5" cols="10"></textarea><br />
				<input type="hidden" id="mid" name="mid" value="'.$mid.'" />
				<input type="submit" value="',$rated ? 'Post' : 'Rate','" />
			</div>
		</form>
		';
	}

	// If not logged in, say we need to be to rate/comment the map
	else {
		echo '
		<p>Must be logged in.</p>
		';
	}
}
/*else {
	echo '<p>Ratings/comments for this map are are disabled. [at author\'s request]</p>';
}*/

// End layout
$layout->foot();
