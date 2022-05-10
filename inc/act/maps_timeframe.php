<?php
defined('in_tms') or exit;	// Anti inclusion hack

// select YEAR(FROM_UNIXTIME(`date`)) as year, count(*) from maps where date > 0 group by year asc

//turn number into month
$months = array(
	 1 => 'January',
	 2 => 'February',
	 3 => 'March',
	 4 => 'April',
	 5 => 'May',
	 6 => 'June',
	 7 => 'July',
	 8 => 'August',
	 9 => 'September',
	10 => 'October',
	11 => 'November',
	12 => 'December'
);


//first and last years we have maps
list($first_year) =
	$sql->fetch_row($sql->query("
		select
			YEAR(FROM_UNIXTIME(`date`))
		from
			`maps`
			where `missing` = '0'
		order by `id` asc
		limit 1
"));
$sql->freelast();
list($last_year) =
	$sql->fetch_row($sql->query("
		select
			YEAR(FROM_UNIXTIME(`date`))
		from
			`maps`
			where `missing` = '0'
		order by `id` desc
		limit 1
"));
$sql->freelast();

//array to hold the years
$years = array();

//insert them
for ($c = $first_year; $years[] = $c, $c < $last_year; $c++);

//determine year
$desired_year = trim($_GET['year']);

if (!in_array($desired_year, $years) || !ctype_digit($desired_year)) {
	$desired_year = $years[0];
}


//also do month?
if (ctype_digit($_GET['month']) && $_GET['month'] >= 1 && $_GET['month'] <= 12) {
	$desired_month = $_GET['month'];
	$do_month = true;
} else {
	$do_month = false;
}

//get maps for this year
$get_maps_query = $sql->query("
	select
		m.`id`,
		m.`url`,
		m.`title`,
		m.`gametype`,
		m.`user`,
		m.`beta`,
		m.`missing`,
		m.`date`,
		g.`name` as gt,
		u.`username`,
		d.`file`,
		d.`pic`,
		(select avg(rating) from `map_ratings` where `mapid` = m.`id` and `rating` != '0') as r
	from
		`maps` as m
		join `members` as u on u.`id` = m.`user`
		join `map_downloads` as d on d.`mapid` = m.`id`
		join `gametypes` as g on m.`gametype` = g.`id`
	where
		YEAR(FROM_UNIXTIME(m.`date`)) = '$desired_year' and
		`missing` = '0'".
		(empty($do_month) ? '' : " and MONTH(FROM_UNIXTIME(m.`date`)) = '$desired_month'")	//also do month?
		."
	order by
		m.`id` asc
");

//start sending stuff out
$layout->head("Maps from $desired_year".(empty($do_month) ? '' :
" in the month of {$months[$desired_month]}" ).
" (".$sql->num($get_maps_query).')');


echo '
<div style="clear: both; float: none;">
	<div style="float: right;">
		<form action="'.$_SERVER['SCRIPT_NAME'].'" method="get" style="margin-right: 30px;">
			<input type="hidden" id="action" name="action" value="'.SITE_ACTION.'" />
			<input type="hidden" id="year" name="year" value="'.$desired_year.'" />
			<label for="month">Month: </label>
			<select id="month" name="month" onchange="this.form.submit()">
				<option value="0"',empty($desired_month) ? ' selected="selected"' : '','>All</option>';
			foreach ($months as $m => $n) {
				echo '
				<option value="'.$m.'"',($m == $desired_month) ? ' selected="selected"' : '','>'.$n.'</option>';
				unset($m, $n);
			}
echo '			
			</select>
			<input type="submit" value="Go" class="js_hide" />
		</form>
	</div>
<p>Year:
';
foreach ($years as $i => $y) {
	if ($desired_year == $y)
		echo ' <strong>'.$y.'</strong>';
	else
		echo ' <a href="'.$_SERVER['SCRIPT_NAME'].'?action=maps_timeframe&amp;year='.$y.'">'.$y.'</a>';
	if ($i != count($years) - 1)	//insert a | between each year link
		echo ' | ';
	unset($i, $y);
}
echo '
</p>
</div>';

//show them
echo '
<table>
	<tr>
		<th>Name</th>
		<th>Author</th>
		<th>Download</th>
		<th>Overview</th>
		<th>Rating</th>
		<th>',empty($do_month) ? 'Month/' : '','Day</th>
	</tr>
	';


for($i = 0; $m = $sql->fetch_assoc($get_maps_query); $i++) {
	echo '<tr',($i % 2) ? ' class="alt"' : '','>
		<td><a href="'.$web_url.'?map='.$m['id'].'">'.stringprep($m['title']).'</a></td>
		<td>'.userLink($m['user'], $m['username']).'</td>
		<td><a href="'.$web_url.'?download='.$m['id'].'" rel="external">Go!</a></td>
		<td><a href="'.$web_url.'?overview='.$m['id'].'" rel="external">Go!</a></td>
		<td>',empty($m['r']) ? '<em>not rated</em>' : '<div class="br_'.ceil($m['r']).'">'.ceil($m['r']).'/5</div>','</td>
		<td>'.$ui->mydate((empty($do_month) ? 'm/' : '').'d', $m['date']).'</td>
	</tr>
	';
}

$sql->free($get_maps_query);

echo '</table>';


$layout->foot();
