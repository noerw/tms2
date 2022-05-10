<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * What shall we do?
 */
switch ($_GET['sa'])
{
	/*
	 * Submit a tut
	 */
	case 'submit':

		/*
		 * Must be logged in
		 */
		if (!$ui->loggedIn())
			$layout->errorMsg('Must be logged in');

	break;

	/*
	 * View a tutorial
	 */
	case 'view':

		/*
		 * Get id
		 */
		$tid = $sql->prot($_GET['id']);

		/*
		 * Get it
		 */
		$get_info = $sql->query("
			select
				t.`title`,
				t.`content`,
				t.`user` as uid,
				t.`dateuploaded`,
				t.`dateupdated`,
				u.`username`
			from
				`tutorials` as t
				join `members` as u on u.`id` = t.`user`
			where
				t.`id` = '$tid'
			limit 1
		");

		// Doesn't exist?
		if ($sql->num($get_info) == 0)
			$layout->errorMsg('Invalid tutorial id');

		$tutorial_info = $sql->fetch_assoc($get_info);
		$sql->free($get_info);

		/*
		 * Assort info
		 */
		$username = stringprep($tutorial_info['username']);
		$title = stringprep($tutorial_info['title']);
		$content = stringprep($tutorial_info['content'], true, true, 2, true);
		//$content = $tutorial_info['content'];

		/*
		 * Start displaying
		 */
		$layout->head($title.' by '.$username);

		/*
		 * The tutorial
		 */
		echo '<div class="tutorial_body">'.$content.'</div>';

		/*
		 * Closing stuff
		 */

		echo '<h2><span>Comments</span></h2>';

		// Comments
		$get_comments = $sql->query("
			select
				c.`date`,
				c.`user` as userid,
				c.`comment`,
				u.`username`
			from
				`tutorial_comments` as c
				join `members` as u on u.`id` = c.`user`
			where
				`tutorial` = '$tid'
			order by
				c.`id`
			desc
		");

		// List them
		while ($info = $sql->fetch_assoc($get_comments)) {
			echo '
			<div class="gen_box_rh border avatar',$alt ? ' alt' : '','">
				<div class="gen_box_rh_head border',$alt ? ' alt' : '','">
					<h4>Written by <a href="'.$entry_point.'?action=view_profile&amp;u='.$info['userid'].'">'.stringprep($info['username']).'</a>
					',!empty($info['date']) ? ' on '. $ui->myDate('F d, Y @ h:i A', $info['date']) : '','</h4>
				</div>
				<div class="gen_box_rh_content">
					<div class="gen_box_rh_avatar">
						<img src="'.$entry_point.'?action=view_avatar&amp;u='.$info['userid'].'" alt="'.stringprep($info['username']).'" />
					</div>
					<div class="gen_box_rh_sub_content border',$alt ? ' alt' : '','">
						<p>'.stringprep($info['comment'], true, true, 2).'</p>
					</div>
				</div>
			</div>
			';
		}

		// Free that ram
		$sql->free($get_comments);

		/*
		 * End layout
		 */
		$layout->foot();

	break;

	/*
	 * List them
	 */
	default:

		/*
		 * Get them
		 */
		$get_tutorials = $sql->query("
			select
				t.`id`,
				t.`user` as uid,
				t.`title`,
				t.`dateuploaded`,
				u.`username`
			from
				`tutorials` as t
				join `members` as u on u.`id` = t.`user`
			order by
				t.`id`
			desc
		");

		$tutorials = array();

		while ($info = $sql->fetch_assoc($get_tutorials))
			$tutorials[] = $info;

		$sql->free($get_tutorials);

		/*
		 * Deal with layout
		 */
		$layout->head('Tutorials ('.count($tutorials).')');

		// Show submit link, if logged in
		if ($ui->loggedIn())
		echo '
		<p><a href="'.$entry_point.'?action=tutorials&amp;sa=submit">Submit a tutorial</a></p>
		';

		// Start list table
		echo '
		<table>
			<tr>
				<th>Title</th>
				<th>Author</th>
				<th>Date Submitted</th>
			</tr>
		';

		/*
		 * List them
		 */
		$alt = false;
		foreach ($tutorials as $info) {
			echo '
			<tr class="sm_date',$alt ? ' alt' : '','">
				<td><a href="'.$entry_point.'?action=tutorials&amp;sa=view&amp;id='.$info['id'].'">'.stringprep($info['title']).'</a></td>
				<td><a href="'.$entry_point.'?action=view_profile&amp;u='.$info['uid'].'">'.stringprep($info['username']).'</a></td>
				<td>'.$ui->myDate('m/d/Y @ h:i A', $info['dateuploaded']).'</td>
			</tr>
			';

			$alt = !$alt;
		}


		echo '
		</table>
		';

		$layout->foot();

	break;
}
