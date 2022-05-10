<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Get available gametypes
 */
$get_gametypes = $sql->query("
	select
		g.`id`,
		g.`name`,
		(select count(*) from `maps` as m where m.`missing` = '0' and m.`gametype` = g.`id`) as 'num_maps'
	from
		`gametypes` as g
	order by
		g.`name` asc,
		'num_maps' asc
");

$gametypes = array();

while ($info = $sql->fetch_row($get_gametypes))
	$gametypes[$info[0]] = array(stringprep($info[1]), $info[2]);

$sql->free($get_gametypes);

/*
 * Current gametype
 */
$desired_gametype = $sql->prot($_GET['gametype']);
$current_gametype_info = array_key_exists($desired_gametype, $gametypes) ? $gametypes[$desired_gametype] : current($gametypes);
$current_gametype = array_key_exists($desired_gametype, $gametypes) ? $desired_gametype : key($gametypes);

// Sanity check
if (!is_array($current_gametype_info))
	$layout->errorMsg('Failed to get current gametype');

list($gametype_name, $num_maps) = $current_gametype_info;

/*
 * Set up pagination
 */
$perpage = MAPS_PER_PAGE;
$pager = new Pagination;
$pager->setPerPage($perpage);
$pager->setRows($num_maps);
$pager->setBaseUrl($entry_point.'?action=map_gt&amp;gametype='.$current_gametype);
$pager->init();

/*
 * Set up map lister
 */
$list = new mapList();
$list->setGametype($current_gametype);
$list->setRange($pager->getStartAt(), $perpage);
$list->fetchMaps();

/*
 * Deal with layout
 */
$layout->head($gametype_name.' Maps ('.number_format($num_maps).')');

/*
 * Gametype picker
 */
echo '
<form action="'.$entry_point.'" method="get">
	<div style="text-align: center; margin: 10px;">
		<input type="hidden" name="action" value="map_gt" />
		<label for="mapgt_sel">Choose gametype:</label><br />
		<select id="mapgt_sel" name="gametype" onchange="this.form.submit()">
		';

		// List each
		foreach ($gametypes as $id => $gt)
			echo '<option',$id == $current_gametype ? ' selected="selected"' : '',' value="'.$id.'">'.$gt[0].' ('.$gt['1'].' maps)</option>';

		echo '
		</select><br />
		<input type="submit" value="Filter" />
	</div>
</form>
';

/*
 * Show pagination for this gametype
 */
$pager->showPagination();

/*
 * Show maps
 */
$list->showList();

/*
 * End layout
 */
$layout->foot();
