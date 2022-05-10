<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Compat with old url
 */
if ($_GET['sa'] == 'user_maps' && is_numeric($_GET['u']))
	redirect($web_url.'?user='.$_GET['u']);

/*
 * Load map lister class
 */
$list = new mapList;
$list->setBool('downs', true);

/*
 * Fetch search parameters
 */

if (@$_GET['st'] == '') {
	$desired_gametype = is_numeric($_GET['gt']) ? $_GET['gt'] : null;
	$desired_authors = strip_tags(trim($_GET['author']));
	$desired_authors_a = (array) explode(',', $desired_authors, 5);
	$search_term = strip_tags(trim($_GET['search']));
	$search_descript = $_GET['descript'] == 'yes';
} else {
	$b64 = @unserialize(@base64_decode($_GET['st']));
	if (is_array($b64)) {
		$desired_gametype = is_numeric($b64['gt']) ? $b64['gt'] : null;
		$desired_authors = strip_tags(trim($b64['author']));
		$desired_authors_a = (array) explode(',', $desired_authors, 5);
		$search_term = strip_tags(trim($b64['search']));
		$search_descript = $b64['descript'] == 'yes';
		$_GET['sort'] = $b64['sort'];
	}
}

/*
 * Sorting
 */
switch ($_GET['sort']) {
	case 'downloads':
		$sort = 'fd';
		$good_sort = true;
	break;
	case 'rating':
		$sort = 'rating';
		$good_sort = true;
	break;
	case 'date':
		$sort = 'm.id';
		$good_sort = true;
	break;
	case 'name':
	default:
		$sort = 'm.title';
		$good_sort = false;
	break;
}
$list->setSort($sort, $_GET['dir'] == 'up' ? 'up' : 'down');


// For next time (pagination)
$next_b64 = base64_encode(serialize(array(
	'gt' => $desired_gametype,
	'author' => $desired_authors,
	'search' => $search_term,
	'descript' => ($search_descript == 'yes' ? 'yes' : ''),
	'sort' => $good_sort ? $_GET['sort'] : '',
	'dir' => $_GET['dir'] == 'up' ? 'up' : 'down'
)));

// So we can decide which are incorrect
$valid_desired_authors_a = array();

$desired_authors_a = array_unique($desired_authors_a);


/*
 *  Configure list
 */

// Game type
if ($desired_gametype !== null)
	$list->setGametype($desired_gametype);

// Authors
if (count($desired_authors_a) > 0) {

	// Array to hold working user id's
	$authors = array();

	// Go through each username. See if it has a valid id
	foreach ($desired_authors_a as $k => $a) {

		// Remove crap
		$a = trim($a);
		$desired_authors_a[$k] = trim($a);

		// Don't waste time with crap
		if ($a == '')
			continue;

		// Attempt getting a valid uid out of it
		$uid = username2uid($a);

		// If we did, append it to the authors array
		if ($uid !== false) {

			// So we can see which usernames are real
			$valid_desired_authors_a[] = $a;

			// So we can tell the lister class which id's to filter in
			$authors[] = $uid;
		}
	}

	// If we've got valid user uid's, use them only
	if (count($authors) > 0)
		$list->setAuthor($authors);
}


// Search term
if ($search_term != '') {
	$search_term = $sql->like_prot($search_term);
	$list->setSearch('%'.$search_term.'%', $search_descript);
}

/*
 * Sorting
 */
switch ($_GET['sort']) {
	case 'downloads':
		$sort = 'fd';
	break;
	case 'rating':
		$sort = 'rating';
	break;
	case 'date':
		$sort = 'm.id';
	break;
	default:
		$sort = 'm.title';
	break;
}
$list->setSort($sort, $_GET['dir'] == 'up' ? 'up' : 'down');

/*
 * Pagination
 */
$num_maps = $list->getNum();
$perpage = MAPS_PER_PAGE;
$pager = new Pagination;
$pager->setPerPage($perpage);
$pager->setRows($num_maps);
$pager->setBaseUrl($entry_point.'?action='.SITE_ACTION.'&amp;st='.$next_b64);
$pager->init();
$list->setRange($pager->getStartAt(), $perpage);


/*
 * Fetch maps
 */
$list->fetchMaps();

/*
 * Deal with layout
 */
// We need this extra css file here
$layout->add_dep('css', 'powersearch.css');

// Start layout
$layout->head('Maps ('.number_format($num_maps).')');

// Show form
?>
<form action="<?=$entry_point?>" method="get">
	<fieldset id="powersearch_form">
		<legend>
			Power Search
		</legend>
		<div id="powersearch_form_content">
			<input type="hidden" name="action" value="<?=SITE_ACTION?>" />
			<input type="hidden" name="go" value="1" />
			<div class="sect alt" id="powersearch_form_gt">
				<label for="ps_gt">Gametype:</label>
				<div>
					<select id="ps_gt" name="gt">
						<option value="">Any</option>
						<?php

							// Fetch gametypes
							$get_gt = $sql->query("select `id`, `name` from `gametypes` where `nummaps` > 0 order by `name` asc");

							while (list($gid, $gn) = $sql->fetch_row($get_gt))
								echo '<option',$gid == $desired_gametype ? ' selected="selected"' : '',' value="'.$gid.'">'.$gn.'</option>';

							$sql->free($get_gt);

						?>
					</select>
				</div>
			</div>
			<div class="sect" id="powersearch_form_author">
				<label for="ps_author">Author(s):</label>
				<div>
					<input value="<?php

					// Only show authors in the list which exist.
					$box_authors = array();
					foreach (array_unique($desired_authors_a) as $a) {
						if (in_array($a, $valid_desired_authors_a))
							$box_authors[] =  stringprep($a, true);
					}

					// Faggotry perfectionalism
					sort($box_authors);

					// Comma separated
					echo implode(',', $box_authors);

					?>" class="txt" type="text" id="ps_author" name="author" />
					<div class="sub">Separate multiple authors with commas</div>
				</div>
			</div>
			<div class="sect alt" id="powersearch_form_keywords">
				<label for="ps_search">Search for:</label>
				<div>
					<input value="<?=htmlentities($search_term)?>" class="txt" type="text" id="ps_search" name="search" /><br />
					<div class="sub">
						<input<?=($search_descript ? ' checked="checked"' : '')?> type="checkbox" id="ps_descript" name="descript" value="yes" />
						<label for="ps_descript">Also search in descriptions</label>
					</div>
				</div>
			</div>
			<div class="sect">
				<label for="ps_sort">Sort by:</label>
				<div>
					<select id="ps_sort" name="sort">
						<option<?=($sort == 'm.title' ? ' selected="selected"' : '')?> value="name">Name</option>
						<option<?=($sort == 'fd' ? ' selected="selected"' : '')?> value="downloads">Downloads</option>
						<option<?=($sort == 'rating' ? ' selected="selected"' : '')?> value="rating">Rating</option>
						<option<?=($sort == 'm.id' ? ' selected="selected"' : '')?> value="date">Date</option>
					</select>
				</div>
			</div>
			<div class="sect alt" id="powersearch_form_search">
				<input type="reset" value="Clear" onclick="window.location='<?=$entry_point?>?action=<?=SITE_ACTION?>';" />
				<input type="submit" value="Search!" />
			</div>
		</div>
	</fieldset>
</form>
<?php

/*
 * Show pagination
 */
$pager->showPagination();

/*
 * Show maps
 */

$list->showList();

/*
 * Show pagination again
 */
$pager->showPagination();

/*
 * Additional things
 */
echo '
<p>
	You may also view maps by <a href="'.$web_url.'?action=maps_timeframe">month/year</a>.
</p>
';

/*
 * Finish layout
 */
$layout->foot();
