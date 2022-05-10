<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Get map ID
 */
$mid = $sql->prot($_GET['map']);

/*
 * Get info on map
 */
try {
	$map = new MapInfo($mid);
	$map_info = $map->getMinInfo();
}
catch (MapException $e){
	$layout->errorMsg($e->getMessage());
}

/*
 * Must be there.
 */

if ($map_info->MISSING)
	$layout->error_message('Map files are missing. Sorry.');

// Go
$layout->head('BBC for '.$map_info->MAP_NAME.' by '.$map_info->AUTHOR_USERNAME);

//the bbcode
echo '
<form action="#" onsubmit="return false;">
<textarea style="width: 98%;height: 400px;" readonly="readonly">[center][color=Teal][b][size=20pt]'.$map_info->MAP_NAME.'[/size][/b][/color]
[color=Beige][i]By '.$map_info->AUTHOR_USERNAME.'[/i][/color]
__________________

'.$map_info->INFO.'

[url='.$entry_point_sm.'?overview='.$mid.'][img]'.$root_url.'t/'.$mid.'[/img][/url]
[i](^Click To Get Hard^)[/i]';


$scp = array(
	0 => $map_info->SC1_PATH,
	1 => $map_info->SC2_PATH,
	2 => $map_info->SC3_PATH
);

if ($map_info->SC1_PATH != '' || !empty($map_info->SC2_PATH) || !empty($map_info->SC3_PATH))
{
	echo "\n\n".'[color=Beige]Screenshots:[/color]'."\n\n";
	foreach ($scp as $cs => $pa)
		if (!empty($pa))
			echo '[url='.$entry_point_sm.'?act=download&amp;sa=scr&amp;scn='.($cs + 1).'&amp;map='.$mid.'][img]'.$root_url.'t/'.$mid.':sc'.($cs + 1).'[/img][/url] ';
}

echo "\n".'
________________________________________'."\n".'
[b][url='.$entry_point_sm.'?download='.$mid.'][size=14pt]Download[/size][/url][/b] [img]'.$entry_point_sm.'?act=image_download_count&id='.$mid.'[/img] [size=14pt]/[/size] [b][url='.$entry_point_sm.'?map='.$mid.'][size=14pt]Rate on TMS[/size][/url][/b]
________________________________________

[url='.$entry_point_sm.'?user='.$map_info->AUTHOR_ID.']See more maps by '.$map_info->AUTHOR_USERNAME.'[/url] [img]'.$entry_point_sm.'?act=image_user_maps_count&amp;id='
,$map_info->AUTHOR_ID,
'[/img]

Lovingly hosted by jrgp\'s [url='.$entry_point_sm.']Soldat Mapping Showcase[/url].[/center]
</textarea></form>';


$layout->foot();
