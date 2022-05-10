<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

$user = 33;

// Get some maps
$get_maps = $sql->query("
	select `id`, `title`, `img` from `maps` where `missing` = '0' and `user` = '$user' order by rand() limit 3
");

echo '
	<a title="My Maps" style="position: relative; margin: 10px;" href="'.$entry_point_sm.'?user='.$user.'">
';

	for($i = 0; $map = $sql->fetch_assoc($get_maps); $i += 5) {

		$path = $sp['maps'] . str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $map['img']);

		if (!is_file($path))
			continue;

		echo '
		<img style="position: absolute; top: '.$i.'; left: '.$i.'; border: 1px solid #000;" src="'.$entry_point.'?action=map_thumbnail&id='.$map['id'].'&sw=45&sh=45" />
		';

	}

$sql->free($get_maps);

echo '
	</a>
';
