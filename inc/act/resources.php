<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Make sure this is an array
 */
$_SESSION['downloaded_resources'] = is_array($_SESSION['downloaded_resources']) ? $_SESSION['downloaded_resources'] : array();

/*
 * Decide what to do
 */
switch ($_GET['sa']) {

	// Download
	case 'download':
		rsc_down();
	break;

	// View one
	case 'info':
		rsc_info();
	break;

	// Upload
	case 'upload':
		rsc_upload();
	break;

	// Show list
	default:
		rsc_show_list();
	break;
}

/*
 * Function defs for above
 */

// Download
function rsc_down() {
	global $sql, $sp;

	// Resource ID
	$rid = $sql->prot($_GET['id']);

	// Get info for it
	$get_info = $sql->query("select `filename`, `title`, `type`, `author` from `resources` where `id` = '$rid' limit 1");
	list($fn, $title, $type, $author) = $sql->fetch_row($get_info);
	$sql->free($get_info);

	// Decide what to do with it
	switch ($type) {
		
		// A scenery
		case '2':
			$path = $sp['sceneries'] . $fn;	
		break;
		
		// A texture
		case '1':
			$path = $sp['textures'] . $fn;	
		break;
		
		// A what?
		default:
			exit('Unknown file.');
		break;
	}

	// Does it exist?
	if (!file_exists($path))
		exit('File not found.');

	// Go for it
	output_image($path, $title) or
		exit('Error outting image');

	// Deal with stats
	if (is_array($_SESSION['downloaded_resources']) && !in_array($_SESSION['downloaded_resources']) && (!$ui->loggedIn() || $ui->currentID() != $author))
		$sql->query("update `resources` set `downloads` = `downloads` + 1 where `id` = '$rid' limit 1");

}

// View one
function rsc_info() {
	global $layout, $ui, $sql, $entry_point;
	
	// Resource ID
	$rid = $sql->prot($_GET['id']);

	// Get info for it
	$get_info = $sql->query("
		select
			r.`title`,
			r.`type`,
			r.`author`,
			r.`info`,
			u.`username`,
			concat(r.`title`, '.', substring(r.`filename`, -3)) as fn
		from
			`resources` as r
			join `members` as u on u.`id` = r.`author`
		where
			r.`id` = '$rid'
		limit 1
	");

	// Doesn't exist?
	if ($sql->num($get_info) == 0)
		$layout->errorMsg('Invalid id');

	// Get info
	$info = $sql->fetch_assoc($get_info);
	$sql->free($get_info);

	stringprep_recursive($info);
	
	// Start layout
	$layout->head($info['title'].' by '.$info['username']);

	// Back?
	echo '
	<p>
		<a href="'.$entry_point.'?action=resources">&laquo; Back to Resources List</a>
	</p>
	';
	
	// Description
	echo '
	<div class="gen_box_rh border">
		<div class="gen_box_rh_head border">
			<h4>Description</h4>
		</div>
		<div class="gen_box_rh_content">
			<p>'.($info['info'] == '' ? '<em>none</em>' : $info['info']).'</p>
		</div>
	</div>
	';
	
	// Info
	echo '
	<div class="gen_box_rh border">
		<div class="gen_box_rh_head border">
			<h4>Info</h4>
		</div>
		<div class="gen_box_rh_content">
			<ul>
				<li>Type: '.($info['type'] == 2 ? 'Texture'  : 'Scenery').'</li>
				<li>Author: <a href="'.$entry_point.'?action=view_profile&amp;u='.$info['author'].'">'.$info['username'].'</a></li>
				<li>Download: <a href="'.$entry_point.'?action=resources&amp;sa=download&amp;id='.$rid.'">'.$info['fn'].'</a></li>
			</ul>
		</div>
	</div>
	
	';
	
	// End layout
	$layout->foot();
}

// Upload
function rsc_upload() {

}

// List them
function rsc_show_list() {
	global $layout, $ui, $sql, $entry_point;

	// Get resources
	$get_resources = $sql->query("
		select
			r.`id`,
			r.`title`,
			r.`type`,
			r.`author`,
			u.`username`
		from
			`resources` as r
			join `members` as u on u.`id` = r.`author`
		order by
			r.`type` asc,
			r.`title` asc
	");
	
	// Number of them
	$num = $sql->num($get_resources);

	// Fetch
	$resources = array();
	while ($info = $sql->fetch_assoc($get_resources))
		$resources[] = $info;
	$sql->free($get_resources);

	// Start layout
	$layout->head('Mapping Resources ('.$num.')');

	// Offer to upload one?
	if ($ui->loggedIn())
		echo '
		<p><a href="'.$entry_point.'?action=resources&amp;sa=upload">Upload</a></p>
		';

	// Start list
	echo '
	<table>
	<tr>
		<th>Name</th>
		<th>Type</th>
		<th>Author</th>
	</tr>
	';

	// List them
	$alt = false;
	foreach($resources as $resource)
	{
		echo '
		<tr',$alt ? ' class="alt"' : '','>
			<td><a href="'.$entry_point.'?action=resources&amp;sa=info&amp;id='.$resource['id'].'">'.stringprep($resource['title']).'</a></td>	
			<td>',$resource['type'] == '1' ? 'Scenery' : 'Texture','</td>	
			<td><a href="'.$entry_point.'?action=view_profile&amp;u='.$resource['author'].'">'.stringprep($resource['username']).'</a></td>	
		</tr>
		';
		
		// Alternating bg
		$alt = !$alt;
	}

	echo '
	</table>
	';
	
	// End layout
	$layout->foot();
}
