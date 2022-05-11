<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

/*
 * Ever popular user profile page!
 */

// User id
$uid = $sql->prot($_GET['u']);

// Get info for it
$get_info = $sql->query("
select
	m.username,
	m.regdate,
	m.pending,
	m.lastaction,
	m.lastip,
	m.banned,
	m.nummaps,
	(select count(*) as num from shoutbox where user = m.id) as num_shouts,
	(select count(*) from map_ratings where userid = m.id and rating > 0) as num_ratings,
	(select count(*) from map_ratings where userid = m.id and rating = 0) as num_comments,
	(select count(*) from resources where author = m.id) as num_resources,
	(select count(*) from prefabs where author = m.id) as num_prefabs,
	(select count(*) from poll_votes where user_id = m.id) as num_votes,
	(select count(*) from forum_posts where poster = m.id) + (select count(*) from forum_topics where poster = m.id) as forum_posts
from
	members as m
where
	m.id = '$uid'
limit 1
");

// User doesn't exist?
if ($sql->num($get_info) == 0) {
	$sql->free($get_info);
	$layout->errorMsg('Invalid user ID');
}

// We must be logged in to view profiles
if (!$ui->loggedIn()) {
	$sql->free($get_info);
	$layout->errorMsg('You must be logged in to view profiles. <a href="'.$entry_point.'?user='.$uid.'">You can however view this user\'s maps.</a>');
}

// Get info
$user_info = $sql->fetch_assoc($get_info);
$sql->free($get_info);

foreach ($user_info as $k => $v)
	$user_info[$k] = stringprep($v);

// Start displaying it

// Start layout
$layout->add_dep('css', 'user_profile.css');
$layout->head($user_info['username']);
?>

<div class="gen_box_rh border">
	<div class="gen_box_rh_head border">
		<h4>Info</h4>
	</div>
	<div class="gen_box_rh_content">

		<img src="<?=$entry_point?>?action=view_avatar&amp;u=<?=$uid?>" style="float: right; margin: 10px;" alt="Avatar" />

	<?php

		/*
		 * Get values for info
		 */

		// Maps percent / num
		list($total_num_maps) = $sql->fetch_row($sql->query("select count(*) from `maps` where `missing` = '0'"));
		$sql->freelast();
		$num_maps = $user_info['nummaps'];
		$maps_percent = $num_maps > 0 && $total_num_maps > 0 ? round($num_maps / $total_num_maps, 4) * 100 : 0;

		// Shoutbox shouts
		list($total_num_shouts) = $sql->fetch_row($sql->query("select count(*) from shoutbox "));
		$sql->freelast();
		$num_shouts = $user_info['num_shouts'];
		$shouts_percent = $num_shouts > 0 && $total_num_shouts > 0 ? round($num_shouts / $total_num_shouts, 4) * 100 : 0;


	?>
		<ul>
			<li>Date registered: <?=$ui->myDate(DATE_FORMAT, $user_info['regdate'])?></li>
			<li>Last seen: <?=$ui->myDate(DATE_FORMAT, $user_info['lastaction'])?></li>
			<li>Maps: <a href="<?=$entry_point_sm?>?user=<?=$uid?>"><?=number_format($num_maps)?></a> <em>(<?=$maps_percent?>% of total)</em></li>
			<li>Maps rated: <a href="<?=$entry_point?>?action=user_ratings&amp;u=<?=$uid?>"><?=number_format($user_info['num_ratings'])?></a></li>
			<li>Maps commented on: <a href="<?=$entry_point?>?action=user_ratings&amp;u=<?=$uid?>"><?=number_format($user_info['num_comments'])?></a></li>
			<li>Shoutbox shouts: <a href="<?=$entry_point?>?action=all_shouts&amp;user=<?=$uid?>"><?=number_format($num_shouts)?></a> <em>(<?=$shouts_percent?>% of total)</em></li>
			<li>Mapping Resources Uploaded: <?=number_format($user_info['num_resources'])?></li>
			<li>Prefabs Uploaded: <?=number_format($user_info['num_prefabs'])?></li>
			<li>Polls Voted On: <?=number_format($user_info['num_votes'])?></li>
			<li>Forum Posts: <?=number_format($user_info['forum_posts'])?></li>
		</ul>
	</div>
</div>

<div class="gen_box_rh border">
	<div class="gen_box_rh_head border">
		<h4>Showcase</h4>
	</div>
	<div class="gen_box_rh_content">
		<?php
			/*
			 * Show "best" maps
			 */
			$map_list = new mapList;
			$map_list->setAuthor($uid);
			$map_list->setBool('downs', true);
			$map_list->setBool('scrots', false);
			$map_list->setCSort("fd desc, rating desc");
			$map_list->setRange(0, 10);
			$map_list->fetchMaps();
			$map_list->showList();
			unset($map_list);
		?>
	</div>
</div>



<?php
// End layout
$layout->foot();
