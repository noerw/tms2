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
			<div class="small_form_row">
				<label for="msn">MSN:</label>
				<input type="text" name="msn" id="msn" class="nofill" value="<?=$current_info['msn']?>" />
			</div>
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
				<label for="email_pm">Email on PM:</label>
				<input<?=($current_info['pm_notif'] == 1 ? ' checked="checked"' : '')?> type="checkbox" id="email_pm" name="email_pm" value="yes" />
			</div>
			<div class="small_form_row alt">
				<label for="email_mr">Email on map rate:</label>
				<input<?=($current_info['mr_notif'] == 1 ? ' checked="checked"' : '')?> type="checkbox" id="email_mr" name="email_mr" value="yes" />
			</div>
			<div class="small_form_row">
				<label for="hide_email">Hide email address:</label>
				<input<?=($current_info['hide_email'] == 1 ? ' checked="checked"' : '')?> type="checkbox" id="hide_email" name="hide_email" value="yes" />
			</div>
			<div class="small_form_row alt">
				<label for="wordfilter">Badword Filter:</label>
				<input<?=($current_info['nobwfilter'] == 0 ? ' checked="checked"' : '')?> type="checkbox" id="wordfilter" name="wordfilter" value="yes" />
			</div>
			<div class="small_form_row">
				<label for="avatar">Change Avatar:</label>
				<div class="small_form_vals">
					<input type="file" id="avatar" name="avatar" />
					<div class="descript">
						Max size: 20KB. Max dimensions: 60x60. Allowed formats: img/png/gif/jpg
					</div>
				</div>
			</div>
			<div class="small_form_submit alt">
				<input type="submit" value="Save Usual Settings" />
			</div>
		</div>
	</fieldset>
</form>

<div class="divider">&sect;</div>

<form action="<?=$entry_point?>?action=profile" method="post">
	<fieldset>
		<legend>Account Settings</legend>
		<input type="hidden" name="<?=$ui->verifKey()?>" value="<?=$ui->verifVal()?>" />
		<input type="hidden" name="do_work" value="yes" />
		<input type="hidden" name="work_type" value="account" />
		<div class="small_form">
			<div class="small_form_row">
				<span class="key">Account Status:</span>
				<div class="small_form_vals txt"><?=( $ui->userActivated() ? '<span class="good">Activated Successfully</span>' : '<span class="lame">Pending Activation</span>')?></div>
			</div>
			<div class="small_form_row alt">
				<span class="key">Current Email Address:</span>
				<div class="small_form_vals txt"><?=$current_info['email']?></div>
			</div>
			<div class="small_form_row">
				<label for="email1">Change Email Address</label>
				<div class="small_form_vals">
					<input type="text" id="email1" name="email1" />
					<input type="text" id="email2" name="email2" />
					<div class="descript">(Enter it twice.)</div>
				</div>
			</div>
			<div class="small_form_row alt">
				<label for="pw1">Change Password</label>
				<div class="small_form_vals">
					<input type="password" id="pw1" name="pw1" />
					<input type="password" id="pw2" name="pw2" />
					<div class="descript">(Enter it twice.)</div>
				</div>
			</div>
			<div class="small_form_row">
				<label for="cpw">Current Password</label>
				<div class="small_form_vals">
					<input type="password" id="cpw" name="cpw" />
					<div class="descript">Your current password is required to make these changes.</div>
				</div>
			</div>
			<div class="small_form_submit alt">
				<input type="submit" value="Save Account Settings" />
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
		$msn = $sql->prot(trim(strip_tags($_POST['msn'])));
		$offset = is_numeric($_POST['hour_offset']) && $_POST['hour_offset'] < 23 && $_POST['hour_offset'] > -23 ? $_POST['hour_offset'] : 0;
		$pm_notif = $_POST['email_pm'] == 'yes' ? 1 : 0;
		$mr_notif = $_POST['email_mr'] == 'yes' ? 1 : 0;
		$hide_email = $_POST['hide_email'] == 'yes' ? 1 : 0;
		$dis_filter = $_POST['wordfilter'] == 'yes' ? 0 : 1;

		// Update
		$sql->query("
			update `members`
			set
				`msn` = '$msn',
				`timezone_offset` = '$offset',
				`hide_email` = '$hide_email',
				`nobwfilter` = '$dis_filter',
				`pm_notif` = '$pm_notif',
				`mr_notif` = '$mr_notif'
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
			if ($ave_info[0] > 60 || $ave_info[1] > 60)
				$layout->errorMsg('Avatar dimensions too big.');

			// Wrong type
			if (!in_array($ave_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
				$layout->errorMsg('Wrong seriously avatar image type.');

			// Wrong extension
			if (!in_array(get_file_extension($_FILES['avatar']['name']), $allowed_picture_exts))
				$layout->errorMsg('Wrong avatar image type.');

			// Seems fine. Formulate paths.
			$db_path = $sql->prod('avatars/u'.$ui->userID().'.'.image_type_to_extension($ave_info[2], false));
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

	// Account stuff
	case 'account':

		// Get possibly supplied fields
		$current_pw = trim($_POST['cpw']);
		$npw1 = trim($_POST['pw1']);
		$npw2 = trim($_POST['pw2']);
		$nem1 = trim($_POST['email1']);
		$nem2 = trim($_POST['email2']);

		// Firstly, make sure current password was provided
		if (md5($current_pw) != $current_info['password'])
			$layout->errorMsg('Your must supply your current password to change these.');

		// Hold what we're updating
		$updq = array();

		// Are we changing the password?
		if ($npw1 != '') {
			// Must match
			if ($npw1 != $npw2)
				$layout->errorMsg('New passwords must match');

			// Must be long enough
			if (strlen($npw1) < 4)
				$layout->errorMsg('New password too short. Must be at aleast 4 characters.');

			// Hash it
			$npwh = md5($npw1);

			// Update it
			$updq[] = "`password` = '$npwh'";

			$ui->loginCookieScrap();
			$_SESSION['pass'] = $npwh;
		}

		// Are we changing the email?
		if ($nem1 != '' && $nem1 == $nem2 && $current_info['email'] != $nem1) {

			// Localize
			$nem = $nem1;

			// Check syntax
			$emv = new EmailAddressValidator;
			if (!$emv->check_email_address($nem))
				$layout->errorMsg('New email address not valid.');

			// Unevalize
			$emv = $sql->prot($nem);

			// Email addresses are unique
			list($check_email) = $sql->fetch_row($sql->query("select count(*) from `members` where `email` = '$emv' limit 1"));
			$sql->freelast();
			if ($check_email == 1)
				$layout->errorMsg('This email address has already been taken.');

			// Update it
			$updq[] = "`email` = '$nem'";
		}

		// Not doing anything?
		if (count($updq) == 0)
			$layout->errorMsg('Not changing anything..');

		// Go for it
		$sql->query("
			update `members`
			set ".implode(',', $updq)."
			where `id` = '".$ui->userID()."' limit 1
		");

		// Done.
		$layout->notifMsg('Stuff changed successfully.');

	break;

	// No idea
	default:
		$layout->errorMsg('Unknown request');
	break;
}
