<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// are we needed?
$done = true;

// txt output
header('Content-type: text/plain');

// done?
if ($done)
	exit('Not currently needed...');

// logo whatever
echo  
'TMS INTEGRITY CHECK
'.str_repeat('=', 80)."\n";

// counters
$existing_maps = 0;
$existing_scrns = 0;
$missing_maps = 0;
$missing_scrns = 0;

// get them
$get_maps = $sql->query("
	select
		`id`, `missing`, `title`, `url`, `img`, `sc1`, `sc2`, `sc3`
	from
		`maps`
	order by 
		`title` asc
");

// go through each
while ($map = $sql->fetch_assoc($get_maps)) {
	
	// fix url's
	foreach (array('url', 'img', 'sc1', 'sc2', 'sc3') as $k) {
		$map[$k] = fix_url($map[$k]);
		$map[$k] = $map[$k] != '' ? realpath($sp['maps'] . $map[$k]) : '';
	}

	echo $map['title'];

	// Map exists?
	$map_exists = is_file($map['url']);
	
	// Mark map 
	if ($map_exists === false) {
		echo ' -- > Missing';
		if ($map['missing'] == '0')
			$sql->query("update `maps` set `missing` = '1' where `id` = '{$map['id']}' limit 1");

		$missing_maps++;
	}
	else {
		$existing_maps++;
		echo ' -- > Okay';
		if ($map['missing'] == '1')
			$sql->query("update `maps` set `missing` = '0' where `id` = '{$map['id']}' limit 1");
	}

	// Other things
	foreach (array('sc1', 'sc2', 'sc3') as $sck) {
		if ($map[$sck] == '' || $map_exists == false)
			continue;

		if (!is_file($map[$sck])) {
			$missing_scrns++;
			echo ' -- > Missing screenshot #' . substr($sck, -1);
			$sql->query("update `maps` set `$sck` = '' where `id` = '{$map['id']}' limit 1");
		}
		else {
			$existing_scrns++;
		}

	}

	echo "\n";

	flush();

}

// free
$sql->free($get_maps);

// Total
echo "

Existing maps: $existing_maps
Missing maps: $missing_maps
Existing screen shots: $existing_scrns
Missing screen shots: $missing_scrns
";

// fix url's
function fix_url($u) {
	return str_replace(array('maps/', '/home/jrgporg/public_html/tms/'), '', $u);
}
