<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Deal with pagination
list($num_users) = $sql->fetch_row($sql->query("select count(*) from members as m where m.pending = '0' and m.banned = '0'"));
$sql->freelast();

// Start layout
$layout->head('Members List');

// Pagination
$perpage = 50;
$pager = new Pagination;
$pager->setPerPage($perpage);
$pager->setRows($num_users);
$pager->setBaseUrl($entry_point.'?action=members');
$pager->init();

// Get them
$get_users = $sql->query("
select
	m.id,
	m.username,
	m.nummaps,
	m.lastaction,
	m.regdate
from
	members as m
where
	m.banned = '0' and 
	m.pending = '0'
order by m.id desc
limit ".$pager->getStartAt().", $perpage
");

// Show pagination
$pager->showPagination();

// Show them
echo '
<table>
	<tr>
		<th>Username</th>
		<th>Maps</th>
		<th>Date Registered</th>
		<th>Last Seen</th>
	</tr>
';

// List users
for ($alt = false; $user = $sql->fetch_assoc($get_users); $alt = !$alt)
echo '
	<tr',$alt ? ' class="alt"' : '','>
		<td><a href="'.$entry_point.'?action=view_profile&amp;u='.$user['id'].'">'.stringprep($user['username']).'</a></td>
		<td>',$user['nummaps'] > 0 ? '<a href="'.$entry_point_sm.'?user='.$user['id'].'">'.$user['nummaps'].'</a>' : 0,'</td>
		<td class="sm_date">'.$ui->myDate(DATE_FORMAT, $user['regdate']).'</td>
		<td class="sm_date">', $user['lastaction'] ? $ui->myDate(DATE_FORMAT, $user['lastaction']) : '', '</td>
	</tr>
';

// Free ram
$sql->free($get_users);

// End list
echo '
</table>
';

// End layout
$layout->foot();
