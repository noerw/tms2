<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

// Must be logged in
$ui->loggedIn() or 
	$layout->errorMsg('Must be logged in');

/*
 * PM
 */
switch ($_GET['sa']) {

	/*
	 * New PM
	 */
	case 'compose':
		exit('wip');
	break;

	/*
	 * Delete recieved PM
	 */
	case 'delete':
		
		// Get pm id
		$pmid = $sql->prot($_GET['pid']);

		// Get info in it
		$get_info = $sql->query("select `to` from `pm` where `id` = '$pmid' limit 1");
		
		// See if it doesn't exist
		if ($sql->num($get_info) == 0)
			$layout->errorMsg('Invalid pm id');

		// It does; get guy it was sent to
		list($pm_ee) = $sql->fetch_row($get_info);
		$sql->freelast();

		// If it isn't me, say so
		if ($ui->userID() != $pm_ee)
			$layout->errorMsg('This PM was not sent to you; you cannot delete it.');
		
		// I can delete it. Ask me first though.
		$layout->prompt('Are you sure you wish to delete this PM?');

		// Okay, I accepted it. Kill the pm
		$sql->query("delete from `pm` where `id` = '$pmid' limit 1");
		
		// Go back to PM list
		redirect($entry_point.'?action=pm');

	break;

	/*
	 * List my sent PM's
	 */
	case 'sent':
		// Get num unread and num total
		list($num_sent) = $sql->fetch_row($sql->query("select count(*) from `pm` where `from` = '".$ui->userID()."'"));
		$sql->freelast();

		// Start layout
		$layout->head('Personal Messages - Sent ('.$num_sent.')');

		// Pagination
		$perpage = 20;
		$pager = new Pagination;
		$pager->setPerPage($perpage);
		$pager->setRows($num_sent);
		$pager->setBaseUrl($entry_point.'?action='.SITE_ACTION.';sa=sent');
		$pager->init();

		?>
		<ul>
			<li><a href="<?=$entry_point?>?action=pm;sa=compose">Compose new PM</a></li>
			<li><a href="<?=$entry_point?>?action=pm">View Inbox</a></li>
		</ul>
		<?php

		// Show pagination
		$pager->showPagination();
		
		// Get them
		$get_pm = $sql->query("
			select
				m.`id`,
				m.`date`,
				m.`subject`,
				u.`username`,
				m.`message`,
				m.`to`
			from
				`pm` as m
				join `members` as u on u.`id` = m.`to`
			where
				m.`from` = '".$ui->userID()."' 
			order by
				m.`id` 
			desc
			limit ".$pager->getStartAt().", $perpage
		");

		// PM lister
		$lister = new Threads;

		// Stuff it
		while($pm_info = $sql->fetch_assoc($get_pm))
			$lister->add(array(
				'username' => $ui->userName(),
				'userid' => $ui->userID(),
				'date' => $pm_info['date'],
				'comment' => $pm_info['message'],
				'tr' => 'From me to <a href="'.$entry_point.'?action=view_profile&amp;u='.$pm_info['to'].'">'.$pm_info['username'].'</a>'
			));

		// Free ram
		$sql->free($get_pm);
		
		// Show them
		$lister->show();

		// End layout
		$layout->foot();

	break;

	/*
	 * List of my PM's
	 */
	default:
		// Get num unread and num total
		list($num_unread, $num_total) = $sql->fetch_row($sql->query("
		select
			count(*),
			(select count(*) from `pm` where `to` = '".$ui->userID()."')
		from
			`pm`
		where
			`to` = '".$ui->userID()."' and
			`read` = '0'
		"));
		$sql->freelast();
		

		// Start layout
		$layout->head('Personal Messages - ('.$num_unread.' new; '.$num_total.' total)');

		// Pagination
		$perpage = 20;
		$pager = new Pagination;
		$pager->setPerPage($perpage);
		$pager->setRows($num_total);
		$pager->setBaseUrl($entry_point.'?action='.SITE_ACTION);
		$pager->init();

		?>
		<ul>
			<li><a href="<?=$entry_point?>?action=pm;sa=compose">Compose new PM</a></li>
			<li><a href="<?=$entry_point?>?action=pm;sa=sent">View Sent</a></li>
		</ul>
		<?php

		// Show pagination
		$pager->showPagination();
		
		// Get them
		$get_pm = $sql->query("
			select
				m.`id`,
				m.`from`,
				m.`date`,
				m.`subject`,
				u.`username`,
				m.`message`
			from
				`pm` as m
				join `members` as u on u.`id` = m.`from`
			where
				m.`to` = '".$ui->userID()."' 
			order by
				m.`id` 
			desc
			limit ".$pager->getStartAt().", $perpage
		");

		// PM lister
		$lister = new Threads;

		// Stuff it
		while($pm_info = $sql->fetch_assoc($get_pm))
			$lister->add(array(
				'username' => $pm_info['username'],
				'userid' => $pm_info['from'],
				'date' => $pm_info['date'],
				'comment' => $pm_info['message'],
				'tr' => '<a href="'.$entry_point.'?action=pm;sa=compose;origid='.$pm_info['id'].'">Reply</a> | <a onclick="return confirm(\'Are you sure?\');" href="'.$entry_point.'?action=pm;sa=delete;pid='.$pm_info['id'].'">Delete</a>'
			));

		// Free ram
		$sql->free($get_pm);
		
		// Show them
		$lister->show();

		// End layout
		$layout->foot();
	break;

}
