<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Some extra stuff relating to stats
$layout->add_dep('css', 'home.css');

// For news posts. (they're just like forum posts)
$layout->add_dep('css', 'forum.css');

// Start layout
$layout->head('Home');

?>

<p>
  <strong>Welcome to jrgp's Soldat2 Mapping Showcase!</strong>  This is an online community dedicated to Soldat2  maps (sometimes referred to as "levels").
We provide hosting for your Soldat2 maps, a map rating / commenting system,
PM's to keep in touch with fellow mappers, a forum for map related discussion, and much more.
 I hope you find this site useful.
	<br /><em>Truly yours, jrgp </em>
</p>

<?php

/*
 * Show the 3 maps on the homepage
 */

// Get latest/map of the week/random maps
//latest map info query
$latest_map = $sql->fetch_assoc($sql->query("
select
	m.id,
	m.title,
	m.beta,
	m.user,
	u.username
from
	maps as m
	join members as u on u.id = m.user
where
	m.missing = '0'
order by id desc limit 1
"));

$sql->freelast();

//most popular map info query
$map_of_the_week = $sql->fetch_assoc($sql->query("
select
	m.id,
	m.title,
	m.user,
	u.username,
	m.beta
from
		mapoftheweek as d
		join maps as m on m.id = d.mid
		join members as u on u.id = m.user
where
	m.missing = '0'
order by d.dl desc limit 1"));

$sql->freelast();

// Random map info query
$random_map = $sql->fetch_assoc($sql->query("
select
	m.id,
	m.title,
	m.beta,
	m.user,
	u.username
from
	maps as m
	join members as u on u.id = m.user
where
	m.missing = '0'
order by rand() limit 1"));

$sql->freelast();

// Get the values presentable
array_walk($latest_map, create_function('&$v, $k', '$v = stringprep($v);'));
array_walk($map_of_the_week, create_function('&$v, $k', '$v = stringprep($v);'));
array_walk($random_map, create_function('&$v, $k', '$v = stringprep($v);'));


echo '
<div class="col3">
	<div class="col center">
		<h3><a href="'.$entry_point.'?action=top_stat&amp;sa=latest">Latest Map</a></h3>
    '.($latest_map != null ? '
		<a href="'.$entry_point_sm.'?map='.$latest_map['id'].'">
			<img src="/t/'.$latest_map['id'].':150x113" alt="'.$latest_map['title'].'" class="map_thumb_big" />
		</a><br />
		<a href="'.$entry_point_sm.'?map='.$latest_map['id'].'">'.$latest_map['title'].'</a> by '.userLink($latest_map['user'], $latest_map['username']).'
     ' : 'No maps').'
	</div>
	<div class="col center">
		<h3><a href="'.$entry_point.'?action=top_stat&amp;sa=mapoftheweek">Map of the Week</a></h3>
    '.($map_of_the_week != null ? '
		<a href="'.$entry_point_sm.'?map='.$map_of_the_week['id'].'">
			<img src="/t/'.$map_of_the_week['id'].':150x113" alt="'.$map_of_the_week['title'].'" class="map_thumb_big" />
		</a><br />
		<a href="'.$entry_point_sm.'?map='.$map_of_the_week['id'].'">'.$map_of_the_week['title'].'</a> by '.userLink($map_of_the_week['user'], $map_of_the_week['username']).'
     ' : 'No maps').'
	</div>
	<div class="col center">
		<h3><a href="'.$entry_point.'?action=top_stat&amp;sa=random">Random Map</a></h3>
    '.($random_map != null ? '
		<a href="'.$entry_point_sm.'?map='.$random_map['id'].'">
			<img src="/t/'.$random_map['id'].':150x113" alt="'.$random_map['title'].'" class="map_thumb_big" />
		</a><br />
		<a href="'.$entry_point_sm.'?map='.$random_map['id'].'">'.$random_map['title'].'</a> by '.userLink($random_map['user'], $random_map['username']).'
     ' : 'No maps').'
	</div>
</div>

<h2><span>News (last 6 articles)</span></h2>
';

/*
 * Show news
 */

// Get news
$get_news = $sql->query("
select
	n.date,
	n.news as comment,
	u.username,
	n.user as userid
from
	news as n
	join members as u on u.id = n.user
order
	by n.id
desc limit 6
");

// Load threader
$news_lister = new Threads;

// Stuff it
while ($info = $sql->fetch_assoc($get_news))
	$news_lister->items[] = $info;

// Save that ram
$sql->free($get_news);

// Show them
$news_lister->show();

// Get number of articles total
list($num_articles) = $sql->fetch_row($sql->query("select count(*) from `news`"));
$sql->freelast();

echo '
<p id="more_news"><a href="'.$entry_point.'?action=news_archive">View all '.$num_articles.' news articles</a></p>
';

/*
 * Finish up
 */

// End layout
$layout->foot();
