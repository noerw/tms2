<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

#exit('wip');

// Must be logged in
$ui->loggedIn() or
	$layout->errorMsg('Must be logged in');

// Must be activated
$ui->userActivated() or
	$layout->errorMsg('Account must be activated');

// Decide what to do
switch ($_GET['sa'] ?? null) {

	// Edit files for a map
	case 'edit':
		map_edit();
	break;

	// Show maps to edit
	default:
		show_my_maps();
	break;
}

// Defs for above

// List the maps available for me to edit
function show_my_maps() {
	global $ui, $sql, $layout, $entry_point_sm, $entry_point;

	// Get number of them
	list($num_maps) = $sql->fetch_row($sql->query("select count(*) from `maps` where `user` = '".$ui->userID()."' and `missing` = '0'"));
	$sql->freelast();

	// Start layout
	$layout->head('My Maps ('.$num_maps.')');

	// Maybe get some pagination for our maps?
	$perpage = MAPS_PER_PAGE;
	$pager = new Pagination;
	$pager->setPerPage($perpage);
	$pager->setRows($num_maps);
	$pager->setBaseUrl($entry_point.'?action='.SITE_ACTION);
	$pager->init();

	// Show it?
	$pager->showPagination();

	// Start off list table
	echo '
	<table>
		<tr>
			<th>Name</th>
			<th>Gametype</th>
			<th>Date Uploaded</th>
			<th>Last Revised</th>
			<th>Overview</th>
			<th>Download</th>
			<th>Screenshots</th>
		</tr>';

	// Get the stuff
	$get_maps = $sql->query("
		select
			m.`id`,
			m.`gametype`,
			m.`title`,
			m.`sc1`,
			m.`sc2`,
			m.`sc3`,
			m.`date`,
			m.`lastedit`,
			g.`name` as gametype_name,
			d.`file` as downs_map,
			d.`pic` as downs_ov
		from
			`maps` as m
			join `gametypes` as g on g.`id` = m.`gametype`
			left join `map_downloads` as d on d.`mapid` = m.`id`
		where
			m.`user` = '".$ui->userID()."' and
			m.`missing` = '0'
		order by
			m.`id` asc
		limit ".$pager->getStartAt().", $perpage
	");

	// Show them
	for ($alt = false; $map = $sql->fetch_assoc($get_maps); $alt = !$alt) {

		// Send out row
		echo '
		<tr',$alt ? ' class="alt"' : '','>
			<td><a href="'.$entry_point_sm.'?map='.$map['id'].'">'.stringprep($map['title']).'</a> | <a href="'.$entry_point.'?action='.SITE_ACTION.';sa=edit;mid='.$map['id'].'">Edit</a> | <a href="'.$entry_point.'?action=auto_bbc;map='.$map['id'].'">BBC</a></td>
			<td>'.$map['gametype_name'].'</td>
			<td class="sm_date">'.$ui->myDate(null, $map['date']).'</td>
			<td class="sm_date">',empty($map['lastedit']) ? '<em>never</em>' : $ui->myDate(null, $map['lastedit']) ,'</td>
			<td><a href="'.$entry_point_sm.'?overview='.$map['id'].'">Go</a> ['.$map['downs_ov'].']</td>
			<td><a href="'.$entry_point_sm.'?map='.$map['id'].'">Go</a> ['.$map['downs_map'].']</td>
			<td>';

			// Mention screenshots if they exist
			if ($map['sc1'] == '' && $map['sc2'] == '' && $map['sc3'] == '') {
				// They don't
				echo '<em>none</em>';
			}
			else {
				// At least one does
				foreach (range(1, 3) as $scn)
					if ($map['sc'.$scn] != '')
						echo '<a href="'.$entry_point.'?action=download&amp;map='.$map['id'].'&amp;sa=scr&amp;scn='.$scn.'">'.$scn.'</a>';
			}

			echo '</td>
		</tr>';
	}

	// Free ram
	$sql->free($get_maps);

	// End table
	echo '
	</table>
	';

	// End layout
	$layout->foot();
}

// Edit a map
function map_edit() {
	global $ui, $layout, $sql, $sp, $entry_point, $entry_point_sm, $allowed_map_exts, $allowed_picture_exts, $max_up_size;

	// Map id
	$mid = $sql->prot($_GET['mid']);

	// Get info
	$get_info = $sql->query("
		select
			m.`no_comments`,
			m.`rec_players_start`,
			m.`rec_players_end`,
			m.`user`,
			m.`missing`,
			m.`gametype`,
			m.`title`,
			m.`info`,
			m.`url`,
			m.`img`,
			m.`sc1`,
			m.`sc2`,
			m.`sc3`,
			m.`date`,
			m.`lastedit`,
			d.`file` as downs_map,
			d.`pic` as downs_ov
		from
			`maps` as m
			left join `map_downloads` as d on d.`mapid` = m.`id`
		where
			m.`id` = '$mid'
		limit 1
	");

	// Not a map?
	if ($sql->num($get_info) == 0)
		$layout->errorMsg('Invalid map id');

	// Get info
	$map_info = $sql->fetch_assoc($get_info);
	$sql->free($get_info);
	foreach(array('title', 'info') as $k)
		$map_info[$k] = stringprep($map_info[$k]);

	// Not my map?
	if (($map_info['user'] != $ui->userID()) && !$ui->isAdmin())
		$layout->errorMsg('This map is not yours');

	// Form not submitted?
	if (!isset($_POST['do_edit']) || $_POST['do_edit'] != 'yes')
	{

		// Start layout
		$layout->head('Edit  '.$map_info['title']);

		// Success message
		if (isset($_GET['updated']) && $_GET['updated'] == 1)
			echo '<p class="good">Updated successfully</p>';

		?>

	<p>
		<a href="<?=$entry_point?>?action=my_maps">&laquo; My Maps</a> |
		<a href="<?=$entry_point_sm?>?map=<?=$mid?>">Map Profile</a> |
		<a href="<?=$entry_point?>?action=auto_bbc;map=<?=$mid?>">Generate BB Code</a> |
		<a href="<?=$entry_point?>?action=clear_thumbnail_cache;map=<?=$mid?>">Clear thumbnail cache</a>
	</p>

	<form action="<?=$entry_point?>?action=my_maps;sa=edit;mid=<?=$mid?>" method="post">
		<fieldset>
			<legend>Map Information</legend>
			<div class="small_form">
				<div class="small_form_row">
					<label for="map_title">Map Title</label>
					<input type="text" id="map_title" name="map_title" value="<?=$map_info['title']?>" />
				</div>
				<div class="small_form_row alt">
					<label for="gametype">Primary Gametype:</label>
					<select id="gametype" name="gametype">
					<?php
						// Get gametypes
						$get_gt = $sql->query("select `id`, `name` from `gametypes` order by `name` asc");

						// Show them
						while (list($gt_id, $gt_name) = $sql->fetch_row($get_gt))
							echo '<option',$map_info['gametype'] == $gt_id ? ' selected="selected"' : '',' value="'.$gt_id.'">'.$gt_name.'</option>';

						// Free that
						$sql->free($get_gt);
					?>
					</select>
				</div>
				<div class="small_form_row">
					<label for="rc_f1">Best Number of Players:</label>
					<div class="small_form_vals">
						<input value="<?=($map_info['rec_players_start'] == 0 ? '' : $map_info['rec_players_start'])?>" size="2" maxlength="2" type="text" id="rc_f1" name="rc_f1" /> to
						<input value="<?=($map_info['rec_players_end'] == 0 ? '' :  $map_info['rec_players_end'])?>"  size="2" maxlength="2" type="text" id="rc_f2" name="rc_f2" />
					</div>
				</div>
				<div class="small_form_row alt">
					<label for="comments">Comments / Ratings:</label>
					<select id="comments" name="comments">
						<option<?=($map_info['no_comments'] == 0 ? ' selected="selected"' : '')?> value="yes">Allow Them</option>
						<option<?=($map_info['no_comments'] == 1 ? ' selected="selected"' : '')?> value="no">Disable Them</option>
					</select>
				</div>
				<div class="small_form_row">
					<label for="description">Description:</label>
					<div class="small_form_vals">
						<textarea class="still" cols="20" rows="5" id="description" name="description"><?=$map_info['info']?></textarea>
						<div class="descript">BB Code suppported</div>
					</div>
				</div>
				<div class="small_form_submit">
					<input type="hidden" name="area" value="info" />
					<input type="hidden" name="do_edit" value="yes" />
					<input type="submit" value="Save Map Information" />
				</div>
			</div>
		</fieldset>
	</form>
	<form action="<?=$entry_point?>?action=my_maps;sa=edit;mid=<?=$mid?>" method="post" enctype="multipart/form-data">
		<fieldset>
			<legend>Map Files</legend>
			<div class="small_form">
				<div class="small_form_row">
					<label for="map_file">Map File</label>
					<div class="small_form_vals">
						<input type="file" id="map_file" name="map_file" />
						<div class="descript">
						Allowed types: <?=implode('/', $allowed_map_exts)?> - Max file size: <?=round($max_up_size['map_file']/1048576, 2)?>MB</div>
					</div>
				</div>
				<div class="small_form_row alt">
					<label for="map_overview">Map Overview</label>
					<div class="small_form_vals">
						<input type="file" id="map_overview" name="map_overview" />
						<div class="descript">
						Allowed types: <?=implode('/', $allowed_picture_exts)?> - Max file size: <?=round($max_up_size['map_pic']/1048576, 2)?>MB</div>
					</div>
				</div>
				<div class="small_form_row">
					<label for="scrot_1">Screenshots</label>
					<div class="small_form_vals">
						<input type="file" id="scrot_1" name="scrot_1" /><br />
						<input type="file" id="scrot_2" name="scrot_2" /><br />
						<input type="file" id="scrot_3" name="scrot_3" /><br />
						<div class="descript">
						Allowed types: <?=implode('/', $allowed_picture_exts)?> - Max file size each: <?=round($max_up_size['map_pic']/1048576, 2)?>MB</div>
					</div>
				</div>
				<div class="small_form_submit alt">
					<input type="hidden" name="area" value="files" />
					<input type="hidden" name="do_edit" value="yes" />
					<input type="submit" value="Update Map Files" />
				</div>
			</div>
		</fieldset>
	</form>
		<?php

		// End layout
		$layout->foot();

		// Stop after form
		exit;

	}

	// Form submitted. Decide what to do
	switch ($_POST['area']) {

		// Edit some map info
		case 'info':

			// Get new info
			$new_title = $sql->prot(strip_tags(trim($_POST['map_title'])));
			$new_gametype = ctype_digit($_POST['gametype']) && !empty($_POST['gametype']) ? $_POST['gametype'] : $map_info['gametype'];
			$new_num_p = ctype_digit($_POST['rc_f1']) && ctype_digit($_POST['rc_f2']) && $_POST['rc_f1'] > 0 && $_POST['rc_f2'] > $_POST['rc_f1'] ?
				array($_POST['rc_f1'], $_POST['rc_f2']) : array(0, 0);
			$new_dis_comments = $_POST['comments'] == 'no' ? 1 : 0;
			$new_description = $sql->prot(trim(strip_tags($_POST['description'])));

			if (!preg_match('/^[a-z0-9\-\_\ ]+$/i', $new_title) || $new_title == '')
				$layout->errorMsg('Map title must exist, and exist of only letters, numbers, underscores, and possibly dashes and spaces.');

			// Deal with gametype if we're changing the gametype
			if ($new_gametype != $map_info['gametype']) {

				// Make sure this gametype exists
				if (!gametype_exists($new_gametype))
					$layout->errorMsg('Your new chosen gametype does not exist.');

				// Increment number of maps for the new gamemode
				$sql->query("update `gametypes` set `nummaps` = `nummaps` + 1 where `id` = '$new_gametype' limit 1");

				// Decrement number of maps for old gamemode
				$sql->query("update `gametypes` set `nummaps` = `nummaps` - 1 where `id` = '{$map_info['gametype']}' limit 1");
			}

			// Update info
			$sql->query("
			update `maps`
			set
				`title` = '$new_title',
				`gametype` = '$new_gametype',
				`info` = '$new_description',
				`rec_players_start` = '{$new_num_p[0]}',
				`rec_players_end` = '{$new_num_p[1]}',
				`no_comments` = '$new_dis_comments'
			where
				`id` = '$mid'
			limit 1");

			// Go back
			redirect($entry_point.'?action=my_maps;sa=edit;mid='.$mid.';updated=1');

		break;

		// Update the files
		case 'files':

			// Set paths to folder
			$local_path = dirname(realpath($sp['maps'].str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $map_info['url']))).'/';
			$db_path = 'maps/'.dirname(str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $map_info['url'])).'/';

			#exit("$local_path - $db_path");

			// Shown to user
			$updated_files = array();

			// Saved later
			$updated_paths = array();

			// Get file handles
			$handles['archive'] = array_key_exists('map_file', $_FILES) ? $_FILES['map_file'] : false;
			$handles['overview'] = array_key_exists('map_overview', $_FILES) ? $_FILES['map_overview'] : false;
			$handles['sc1'] = array_key_exists('scrot_1', $_FILES) ? $_FILES['scrot_1'] : false;
			$handles['sc2'] = array_key_exists('scrot_2', $_FILES) ? $_FILES['scrot_2'] : false;
			$handles['sc3'] = array_key_exists('scrot_3', $_FILES) ? $_FILES['scrot_3'] : false;

			// Localize upsizes
			$upsize['archive'] = $max_up_size['map_file'];
			$upsize['overview'] = $upsize['sc1'] = $upsize['sc2'] = $upsize['sc3'] = $max_up_size['map_pic'];

			// And extensions
			$al_ext['archive'] = $allowed_map_exts;
			$al_ext['overview'] = $al_ext['sc1'] = $al_ext['sc2'] = $al_ext['sc3'] = $allowed_picture_exts;

			// So far we don't want to save revision date
			$save_revision_date = false;

			// Go through each file
			foreach($handles as $type => $info) {

				// Nothing..?
				if (!is_array($info))
					continue;

				// First off, if it sucks, no way
				if ($info['error'] != UPLOAD_ERR_OK)
					continue;

				// Deal with size
				if ($info['size'] == 0 || $info['size'] > $upsize[$type])
					continue;

				// Deal with ext
				if (!in_array(get_file_extension($info['name']), (array)$al_ext[$type]))
					continue;

				// Seems okay, find how to deal with it:
				switch($type) {

					// Archive
					case 'archive':

						// kill current
						$current = $local_path.basename($map_info['url']);
						#exit($current);
						`/bin/chmod 755 $current`;
						`/bin/rm -f $current`;

						$fn = 'arc.'.get_file_extension($info['name']);
						$np = $local_path.$fn;

						// Attempt moving over..
						if (!move_uploaded_file($info['tmp_name'], $np))
							$layout->errorMsg('Error saving archive');

						`/bin/chmod 755 $np`;

						// Success!
						$updated_files[] = 'Map Archive';

						// Don't forget to update path..
						$update_paths[] = "`url`='".$sql->prot($db_path.$fn)."'";

						// And we do want to update the revision date
						$save_revision_date = true;

					break;

					// Overview
					case 'overview':

						// kill current
						$current = $local_path.basename($map_info['img']);
						`rm -f $current`;

						$fn = 'img.'.get_file_extension($info['name']);

						// Attempt moving over..
						if (!move_uploaded_file($info['tmp_name'], $local_path.$fn))
							$layout->errorMsg('Error saving overview');
						`chmod 755 $local_path$fn`;

						// Success!
						$updated_files[] = 'Overview';

						// Don't forget to update path..
						$update_paths[] = "`img`='".$sql->prot($db_path.$fn)."'";

						// Clear thumbs
						try {
							$cache = new OV_Thumbs($mid);
							$cache->remove(true);
						}
						catch (cacheException $e) {}

					break;

					// Screenshots
					case 'sc1':
					case 'sc2':
					case 'sc3':

						// Define screenshot number
						$scn = substr($type, -1);

						// kill current
						$current = $local_path.basename($map_info['sc'.$scn]);

						// Does that exist? Should we kill it?
						if ($map_info['sc'.$scn] != '' && is_file($current))
							`rm -f $current`;

						$fn = 'sc'.$scn.'.'.get_file_extension($info['name']);

						// Attempt moving over..
						if (!move_uploaded_file($info['tmp_name'], $local_path.$fn))
							$layout->errorMsg('Error saving overview');
						`chmod 755 $local_path$fn`;

						// Success!
						$updated_files[] = 'Screenshot #'.$scn;

						// Don't forget to update path..
						$update_paths[] = "`sc$scn`='".$sql->prot($db_path.$fn)."'";

						// Clear thumbs
						try {
							$cache = new SCN_Thumbs($mid, null, $scn);
							$cache->remove(true);
						}
						catch (cacheException $e) {
							#echo $e->getMessage();
						}
					break;

					// This should never happen
					default:
						continue 2;
					break;
				}
			}

			// Deal with it
			if (count($updated_files) == 0 || !is_array($update_paths))
				$layout->errorMsg('No files updated.');

			// Save revision date?
			if ($save_revision_date)
				$update_paths[] = '`lastedit` = UNIX_TIMESTAMP()';

			// We did some. Update paths and revision date, if need be
			$sql->query("update `maps` set ".implode(', ', $update_paths)."  where `id`  = '$mid' limit 1");

			// Show what did
			$layout->notifMsg('Successfully updated: '.implode(', ', $updated_files));

		break;

		// Dunno
		default:
			$layout->errorMsg('Unknown action');
		break;

	}
}


