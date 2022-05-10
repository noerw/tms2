<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

$limit = 15;

/*
 * Load the map lister
 */
$list = new mapList;
$list->setRange(0, $limit);

/*
 * Decide what to do
 */
switch ($_GET['sa'])
{

	/*
	 * Latest maps
	 */
	case 'latest':

		// Sort them randomly
		$list->setSort('id', 'desc');

		// Get them
		$list->fetchMaps();

		// Start layout
		$layout->head($limit.' Latest maps');

		// List them coolishly
		$list->showList();

		// End layout
		$layout->foot();

	break;

	/*
	 * Get most downloaded maps this week
	 */
	case 'mapoftheweek':

		// Get the map of the week info
		$list->setBool('motw', true);

		// Sort them descending
		$list->setSort('motw.`dl`', 'down');

		// Get them
		$list->fetchMaps();

		// Start layout
		$layout->head('Maps of the week');

		// List them coolishly
		$list->showList(null, array(array('Downloads this week: ', 'motw_dl')));

		// End layout
		$layout->foot();

	break;

	/*
	 * Random maps
	 */
	case 'random':

		// Sort them randomly
		$list->setSort('rand()', '');

		// Get them
		$list->fetchMaps();

		// Start layout
		$layout->head($limit.' Random maps');

		// List them coolishly
		$list->showList();

		// End layout
		$layout->foot();

	break;

	/*
	 * Unknown top stat
	 */
	default:
		echo 'Dunno';
	break;
}
