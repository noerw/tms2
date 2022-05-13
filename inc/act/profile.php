<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Must be logged in
$ui->loggedIn() or
	$layout->errorMsg('Must be logged in');

// Get current settings
$get_current = $sql->query("select * from `members` where `id` = '".$ui->userID()."' limit 1");
$current_info = $sql->fetch_assoc($get_current);
$sql->free($get_current);

foreach($current_info as $k => $v)
	$current_info[$k] = stringprep($v, true);

// Form(s) not submitted? show them
if ($_POST['do_work'] != 'yes') {

	// Start layout
	$layout->head('Profile Settings');

	// We updated goodly
	if ($_GET['upd'] == 1)
		echo '<p class="good">Updated successfully</p>';
?>
<form enctype="multipart/form-data" action="<?=$entry_point?>?action=profile" method="post">
	<fieldset>
		<legend>Usual Stuff</legend>
		<input type="hidden" name="<?=$ui->verifKey()?>" value="<?=$ui->verifVal()?>" />
		<input type="hidden" name="do_work" value="yes" />
		<input type="hidden" name="work_type" value="usual" />
		<div class="small_form">
			<div class="small_form_row alt">
				<label for="hour_offset">Timezone Hour Offset:</label>
				<div class="small_form_vals">
					<input type="text" maxlength="2" size="2" name="hour_offset" id="hour_offset" class="nofill" value="<?=$current_info['timezone_offset']?>" />
					<div class="descript">
						Current server time: <?=$ui->myDate('h:i A')?> &mdash;
						Adjusted time: <?=$ui->myDate('h:i A')?>
					</div>
				</div>
			</div>
			<div class="small_form_row">
				<label for="wordfilter">Badword Filter:</label>
				<input<?=($current_info['nobwfilter'] == 0 ? ' checked="checked"' : '')?> type="checkbox" id="wordfilter" name="wordfilter" value="yes" />
			</div>
			<div class="small_form_row alt">
				<label for="avatar">Change Avatar:</label>
				<div class="small_form_vals">
					<input type="file" id="avatar" name="avatar" />
					<div class="descript">
						Max size: 20MB. Allowed formats: png/gif/jpg
					</div>
				</div>
			</div>
			<div class="small_form_submit">
				<input type="submit" value="Save Settings" />
			</div>
		</div>
	</fieldset>
</form>

<ul>
	<li><a href="<?=$entry_point?>?action=view_profile&amp;u=<?=$ui->userID()?>">View my profile</a></li>
	<li>Public maps link: <a href="<?=$entry_point_sm?>?user=<?=$ui->userID()?>"><?=$entry_point_sm?>?user=<?=$ui->userID()?></a></li>
</ul>
<?php

	// End layout
	$layout->foot();
	exit;
}

/*
 * Form submitted
 */

// Anti hack
$ui->isVerified() or
	$layout->errorMsg('Not verified');


// Which job?
switch ($_POST['work_type']) {
	// Usual stuff
	case 'usual':

		/*
		 * Textual info
		 */

		// Get input
		$offset = is_numeric($_POST['hour_offset']) && $_POST['hour_offset'] < 23 && $_POST['hour_offset'] > -23 ? $_POST['hour_offset'] : 0;
		$dis_filter = $_POST['wordfilter'] == 'yes' ? 0 : 1;

		// Update
		$sql->query("
			update `members`
			set
				`timezone_offset` = '$offset',
				`nobwfilter` = '$dis_filter'
			where
				`id` = '".$ui->userID()."'
			limit 1
		");

		/*
		 * Handle a new avatar
		 */
		if ($_FILES['avatar']['error'] == UPLOAD_ERR_OK && $_FILES['avatar']['size'] > 0) {

			// Validate what we got

			// Too big
			if  ($_FILES['avatar']['size'] > $max_up_size['avatar'])
				$layout->errorMsg('Avatar size too high.');

			// Not an image
			if (!($ave_info = @getimagesize($_FILES['avatar']['tmp_name'])))
				$layout->errorMsg('Avatar not an image');

			// Too big
			if ($ave_info[0] > 200 || $ave_info[1] > 200)
				$layout->errorMsg('Avatar dimensions too big.');

			// Wrong type
			if (!in_array($ave_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
				$layout->errorMsg('Wrong seriously avatar image type.');

			// Wrong extension
			if (!in_array(get_file_extension($_FILES['avatar']['name']), $allowed_picture_exts))
				$layout->errorMsg('Wrong avatar image type.');

			// Seems fine. Formulate paths.
			$db_path = $sql->prot('avatars/u'.$ui->userID().'.'.image_type_to_extension($ave_info[2], false));
			$real_path = realpath($sp['avatars']).'/u'.$ui->userID().'.'.image_type_to_extension($ave_info[2], false);

			// Try saving it
			if (!@move_uploaded_file($_FILES['avatar']['tmp_name'], $real_path))
				$layout->errorMsg('Error saving avatar');

			// Okay, now save path in db
			$sql->query("update `members` set `avatar_url` = '$db_path' where `id` = '".$ui->userID()."' limit 1");
		}

		// Back
		redirect($entry_point.'?action=profile;upd=1');

	break;

	// No idea
	default:
		$layout->errorMsg('Unknown request');
	break;
}
