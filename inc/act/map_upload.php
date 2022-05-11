<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Must be logged in
$ui->loggedIn() or
	$layout->errorMsg('Not logged in');

// Must be activated
$ui->userActivated() or
	$layout->errorMsg('Account must be activated.');

// Make sure our mapdir exists
if (!is_dir($ui->mapDir()) && !mkdir($ui->mapDir(), 0755))
	$layout->errorMsg('Error making sure your map dir exists');

// If form has not been submitted, show it
if (!isset($_POST['do_upload' ]) || $_POST['do_upload' ] != 'yes' || !$ui->isVerified())
{
	// Need extra css and js
	$layout->add_dep('css', 'map_upload.css');
	$layout->add_dep('js', 'map_upload.js');

	// Start layout
	$layout->head('Map Upload');

	// Classic witicisms
	$upload_msgs = array(
		'Upload'
	);

?>
<p> Note: only upload maps that are your own and that you have made yourself. Uploading others' work is considered plagiarism and might get the map deleted. The only exception to this is if what you're uploading is a map pack with work of yours in it. </p>
<p><span style="color: maroon;">*</span> denotes a required field.</p>
<form action="<?=$entry_point?>?action=map_upload" method="post" enctype="multipart/form-data" onsubmit="return handle_map_upload(this);">
	<div id="map_upload">
		<input type="hidden" name="<?=$ui->verifKey()?>" value="<?=$ui->verifVal()?>" />
		<input type="hidden" name="do_upload" value="yes" />
		<fieldset>
			<legend>Map Information</legend>
			<div class="small_form">
				<div class="small_form_row">
					<label for="title">Map Title:</label>
					<span class="floating_asterisk">*</span>
					<div class="small_form_vals">
						<input type="text" class="still" id="title" name="title" />
						<div class="descript">eg: ctf_Run or Arena. May only contain letters, numbers, and underscores.</div>
					</div>
				</div>
				<div class="small_form_row alt">
					<label for="gametype">Primary Gametype:</label>
					<span class="floating_asterisk">*</span>
					<select id="gametype" name="gametype">
						<option value="">Pick</option>
						<?php
							// Get gametypes
							$get_gt = $sql->query("select `id`, `name` from `gametypes` order by `name` asc");

							// Show them
							while (list($gt_id, $gt_name) = $sql->fetch_row($get_gt))
								echo '<option value="'.$gt_id.'">'.$gt_name.'</option>';

							// Free that
							$sql->free($get_gt);
						?>
					</select>
				</div>
				<div class="small_form_row">
					<label for="rc_f1">Best Number of Players:</label>
					<div class="small_form_vals">
						<input size="2" maxlength="2" type="text" id="rc_f1" name="rc_f1" /> to
						<input size="2" maxlength="2" type="text" id="rc_f2" name="rc_f2" />
					</div>
				</div>
				<div class="small_form_row alt">
					<label for="comments">Comments / Ratings:</label>
					<select id="comments" name="comments">
						<option value="yes">Allow Them</option>
						<option value="no">Disable Them</option>
					</select>
				</div>
				<div class="small_form_row" style="margin-bottom: 0;">
					<label for="description">Description:</label>
					<div class="small_form_vals">
						<textarea class="still" cols="20" rows="5" id="description" name="description"></textarea>
						<div class="descript">BB Code suppported</div>
					</div>
				</div>

			</div>
		</fieldset>
		<fieldset>
			<legend>Map Files</legend>
			<div class="small_form">
				<div class="small_form_row">
					<label for="map_file">Map File</label>
					<span class="floating_asterisk">*</span>
					<div class="small_form_vals">
						<input type="file" id="map_file" name="map_file" />
						<div class="descript">Allowed types: <?=implode('/', $allowed_map_exts)?> - Max file size: <?=round($max_up_size['map_file']/1048576, 2)?>MB</div>
					</div>
				</div>
				<div class="small_form_row alt">
					<label for="map_overview">Map Overview</label>
					<span class="floating_asterisk">*</span>
					<div class="small_form_vals">
						<input type="file" id="map_overview" name="map_overview" />
						<div class="descript">Allowed types: <?=implode('/', $allowed_picture_exts)?> - Max file size: <?=round($max_up_size['map_pic']/1048576, 2)?>MB</div>
					</div>
				</div>
				<div class="small_form_row">
					<label for="scrot_1">Screenshots</label>
					<div class="small_form_vals">
						<input type="file" id="scrot_1" name="scrot_1" /><br />
						<input type="file" id="scrot_2" name="scrot_2" /><br />
						<input type="file" id="scrot_3" name="scrot_3" /><br />
						<div class="descript">Allowed types: <?=implode('/', $allowed_picture_exts)?> - Max file size each: <?=round($max_up_size['map_pic']/1048576, 2)?>MB</div>
					</div>
				</div>
			</div>
		</fieldset>
		<input title="Click to upload" class="btn" type="submit" value="<?=($upload_msgs[rand(0, count($upload_msgs) -1)])?>" />
	</div>
</form>
<?php
	// End layout
	$layout->foot();

	// Stop
	exit;
}

/*
 *
 * Form Submitted
 *
 */

// Anti hack.
$ui->isVerified(true) or
	$layout->errorMsg('Hacker?');

// Get meta values
$title = $sql->prot(trim(strip_tags(str_replace(' ', '_', $_POST['title']))));
$gametype = (int) $_POST['gametype'];
$num_p = ctype_digit($_POST['rc_f1']) && ctype_digit($_POST['rc_f2']) && $_POST['rc_f1'] > 0 && $_POST['rc_f2'] > $_POST['rc_f1'] ?
	array($_POST['rc_f1'], $_POST['rc_f2']) : array(0, 0);
$waypoints = $_POST['waypoints'] == 'yes' ? 1 : 0;
$dis_comments = $_POST['comments'] == 'no' ? 1 : 0;
$description = $sql->prot(trim(strip_tags($_POST['description'])));

// Get file values
$arc_file = $_FILES['map_file'];
$ovi_file = $_FILES['map_overview'];
$sc_files = array(1 => $_FILES['scrot_1'], 2 => $_FILES['scrot_2'], 3 => $_FILES['scrot_3']);
$sc_do = array();
$sc_paths = array();

// Validate meta stuffs
if ($title == '' || !preg_match('/^[a-z0-9\-\_]+$/i', $title))
	$layout->errorMsg('Invalid map title');

/*
 * Base really important path
 */
$si_map_folder = md5(microtime(true).$ui->userID().rand(0,200)).'/';


/*
 * Deal with validating files
 */

// Validate archive file
/*if ($arc_file['error'] ==  UPLOAD_ERR_NO_FILE)
	$layout->errorMsg('You did not provide the archive file?');
#if ($arc_file['size'] == 0)
#	$layout->errorMsg('Empty archive file?');
if ($arc_file['error'] ==  UPLOAD_ERR_INI_SIZE)
	$layout->errorMsg('Archive file definitely too big');
if ($arc_file['size'] > $max_up_size['map_file'])
	$layout->errorMsg('Archive file too big');
*/
if ($arc_file['error'] !=  UPLOAD_ERR_OK)
	$layout->errorMsg('Error uploading archive file: '.$arc_file['error']);
if (!in_array(get_file_extension($arc_file['name']), $allowed_map_exts))
	$layout->errorMsg('Invalid archive file extension');

// Validate image file

/*if ($ovi_file['error'] ==  UPLOAD_ERR_NO_FILE)
	$layout->errorMsg('You did not provide the overview file?');
if ($ovi_file['size'] == 0)
	$layout->errorMsg('Empty overview file?');
if ($ovi_file['error'] ==  UPLOAD_ERR_INI_SIZE)
	$layout->errorMsg('Overview file definitely too big');
if ($ovi_file['size'] > $max_up_size['map_pic'])
	$layout->errorMsg('Overview file too big');*/

if ($ovi_file['error'] !=  UPLOAD_ERR_OK)
	$layout->errorMsg('Error uploading overview file: '.$ovi_file['error']);
if (!in_array(get_file_extension($ovi_file['name']), $allowed_picture_exts))
	$layout->errorMsg('Invalid overview file extension');

// Deal with screenshots..
foreach ($sc_files as $sc => $scf) {
	if ($scf['error'] == UPLOAD_ERR_OK && $scf['size'] > 0 && $scf['size'] < $max_up_size['map_pic'] && in_array(get_file_extension($scf['name']), $allowed_picture_exts))
	{
		$sc_do[] = $sc;
		$sc_paths[$sc] = $si_map_folder . 'sc'.$sc.'.'.get_file_extension($scf['name']);
	}
}

/*
 * Set paths
 */
$si_arc_file = $si_map_folder . 'map.'.get_file_extension($arc_file['name']);
$si_ovi_file = $si_map_folder . 'ov.'.get_file_extension($ovi_file['name']);

/*
 * Try to start dealing with stuff
 */

// Make map folder
@mkdir($ui->mapDir().$si_map_folder, 0755) or
	$layout->errorMsg('Error making folder for this map');

// Try saving archive file
@move_uploaded_file($arc_file['tmp_name'], $ui->mapDir().$si_arc_file) or
	$layout->errorMsg('Error saving archive file');
$f = $ui->mapDir().$si_arc_file;
@`chmod 755 $f`;

// Try saving overview file
@move_uploaded_file($ovi_file['tmp_name'], $ui->mapDir().$si_ovi_file) or
	$layout->errorMsg('Error saving overview file');
$f = $ui->mapDir().$si_ovi_file;
@`chmod 755 $f`;

// Try saving screenshots
foreach ($sc_do as $sc) {
	@move_uploaded_file($sc_files[$sc]['tmp_name'], $ui->mapDir().$sc_paths[$sc]);
	$f = $ui->mapDir().$sc_paths[$sc];
	@`chmod 755 $f`;
}

unset($f);

/*
 * By this point, stuff should be saved. Query?
 */
$sql->query("
	insert into `maps`
	set
		`user` = '".$ui->userID()."',
		`gametype` = '$gametype',
		`title` = '$title',
		`img` = '".$ui->mapDirPlain()."$si_ovi_file',
		`url` = '".$ui->mapDirPlain()."$si_arc_file',
		`info` = '$description',
		`date` = UNIX_TIMESTAMP(),
		`waypoints` = '$waypoints',
		`no_comments` = '$dis_comments',
		`rec_players_start` = '{$num_p[0]}',
		`rec_players_end` = '{$num_p[1]}',
		`sc1` = '".(in_array(1, $sc_do) ? $ui->mapDirPlain() . $sc_paths[1] : '')."',
		`sc2` = '".(in_array(2, $sc_do) ? $ui->mapDirPlain() . $sc_paths[2] : '')."',
		`sc3` = '".(in_array(3, $sc_do) ? $ui->mapDirPlain() . $sc_paths[3] : '')."'
");

/*
 * Get map id
 */
$mapid = $sql->lastId();

/*
 * Create stuff around it
 */
$sql->query("insert ignore into `map_downloads` set `mapid` = '$mapid'");

/*
 * Inc stats
 */
$sql->query("update `members` set `nummaps` = `nummaps` + 1 where `id` = '".$ui->userID()."' limit 1");
$sql->query("update `gametypes` set `nummaps` = `nummaps` + 1 where `id` = '$gametype' limit 1");

/*
 * Go to it
 */
redirect($entry_point_sm.'?map='.$mapid);
