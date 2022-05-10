<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Set these up
 */
define('POSTS_PER_PAGE', 15);
defined('TIME_FORMAT') or define('TIME_FORMAT', 'm/d/Y @ h:i A');
define('POST_THRESHOLD', 120);

/*
 * Forum staff
 */
$forum_mods = array(2);

/*
 * Am I a mod?
 */
$user_mod = in_array($ui->userID(), $forum_mods);

/*
 * Are we posting again too soon? (spammer alert)
 */
function checkFlood() {
	global $sql, $ui, $layout;

	// Get date of my last reply
	$get_post_time = $sql->query("
		select
			UNIX_TIMESTAMP() - fp.date
		from
			forum_posts as fp
		where
			fp.poster = '".$ui->userID()."'
		order by
			fp.id
		desc
		limit 1
	");

	// Well?
	if ($sql->num($get_post_time) == 1 && (list($post_time) = $sql->fetch_row($get_post_time)) && $sql->freelast()) {
		if ((int) $post_time < POST_THRESHOLD)
			$layout->errorMsg('You cannot post more than once within '.POST_THRESHOLD.' seconds.');
	}

	// Get date of my last new topic
	$get_topic_time = $sql->query("
		select
			UNIX_TIMESTAMP() - ft.date
		from
			forum_topics as ft
		where
			ft.poster = '".$ui->userID()."'
		order by
			ft.id
		desc
		limit 1
	");

	// Well?
	if ($sql->num($get_topic_time) == 1 && (list($topic_time) = $sql->fetch_row($get_topic_time)) && $sql->freelast()) {
		if ((int) $topic_time < POST_THRESHOLD)
			$layout->errorMsg('You cannot post more than once within '.POST_THRESHOLD.' seconds.');
	}

	// Done
	return true;

}

/*
 * Need to be logged in
 */
$ui->loggedIn() or
	$layout->errorMsg('Must be logged in');

/*
 * Decide what to do
 */
switch ($_GET['sa']) {

	/*
	 * View a topic
	 */
	case 'topic':

		/*
		 * Get topic id
		 */
		$tid = $sql->prot($_GET['id']);

		/*
		 * Get info for it, as well as first post
		 */
		$get_topic = $sql->query("
			select
				t.title,
				t.locked,
				t.sticky,
				t.poster as uid,
				t.date,
				t.lastedit,
				t.post as body,
				t.numreplies,
				u.username
			from
				forum_topics as t
				join members as u on u.id = t.poster
			where
				t.id = '$tid'
			limit 1
		");

		/*
		 * Make sure it exists
		 */
		if ($sql->num($get_topic) == 0)
			$layout->errorMsg('Invalid topic id');

		/*
		 * Get info for it
		 */
		$topic_info = $sql->fetch_assoc($get_topic);
		$sql->free($get_topic);
		$topic_title = stringprep($topic_info['title']);
		$num_posts = ((int) $topic_info['numreplies'] ) ;

		/*
		 * Configure Pagination
		 */
		$pager = new Pagination;
		$pager->setPerPage(POSTS_PER_PAGE);
		$pager->setRows($num_posts);
		$pager->setBaseUrl($entry_point.'?action=forum&amp;sa=topic&amp;id='.$tid);
		$pager->init();

		/*
		 * Deal with post output
		 */
		$post_lister = new Threads;
		$post_lister->setItemId('r', 'repid');

		/*
		 * Insert posts
		 */

		// Only show first post if on first page
		if ($pager->getCurrentPage() == 1) {
			$post_lister->add(array(
				'username' => $topic_info['username'],
				'userid' => $topic_info['uid'],
				'date' => $topic_info['date'],
				'lastedit' => $topic_info['lastedit'],
				'comment' => $topic_info['body']
			));
		}

		// Get replies
		$get_replies = $sql->query("
			select
				u.username,
				r.poster as uid,
				r.date,
				r.lastedit,
				r.post as body,
				r.id as repid
			from
				forum_posts as r
				join members as u on u.id = r.poster
			where
				r.topic = '$tid'
			order by
				r.id asc
			limit
			".$pager->getStartAt().", ".POSTS_PER_PAGE."
		");

		// Add them to lister
		while ($info = $sql->fetch_row($get_replies)) {
			$post_lister->items[] = array(
				'username' => $info[0],
				'userid' => $info[1],
				'date' => $info[2],
				'lastedit' => $info[3],
				'comment' => $info[4],
				'repid' => $info[5],
				'tr' => ($info[1] == USER_ID || $user_mod ? '
					<a onclick="return confirm(\'Really delete this?\');" href="'.$entry_point.'?action=forum&amp;sa=delete_post&amp;pid='.$info[5].'&amp;'.VERIF_KEY.'='.VERIF_VAL.'">Delete</a> |
					<a href="'.$entry_point.'?action=forum&amp;sa=edit_post&amp;pid='.$info[5].'&amp;'.VERIF_KEY.'='.VERIF_VAL.'">Edit</a>
				' : false)
			);
		}

		// Free ram
		$sql->free($get_replies);

		/*
		 * Start layout
		 */
		$layout->head('Forum &raquo; '.$topic_title);

		/*
		 * Back to topics list
		 */
		echo '
		<p><a href="'.$entry_point.'?action=forum">&laquo; Return to topics list</a></p>
		';

		/*
		 * Show pages
		 */
		$pager->showPagination();

		/*
		 * Show posts
		 */
		$post_lister->show();

		/*
		 * Show pages
		 */
		$pager->showPagination();

		/*
		 * Reply
		 */
		echo '
		<h2><span>Reply</span></h2>
		<form action="'.$entry_point.'?action=forum&amp;sa=reply" method="post">
			<div class="forum_form">
				<input type="hidden" name="'.$ui->verifKey().'" value="'.$ui->verifVal().'" />
				<input type="hidden" id="tid" name="tid" value="'.$tid.'" />
				<input type="hidden" id="do_reply" name="do_reply" value="yes" />
				<label for="rply_msg">Message:</label><br />
				<textarea id="rply_msg" name="rply_msg" cols="30" rows="4"></textarea><br />
				<input type="submit" value="Post" />
			</div>
		</form>
		';

		/*
		 * End layout
		 */
		$layout->foot();

	break;

	/*
	 * Reply to a topic
	 */
	case 'reply':

		if (!$ui->userActivated())
			$layout->errorMsg('Account not activated');

		// Must be submitted
		if (@$_POST['do_reply'] != 'yes')
			$layout->errorMsg('Not replied?');

		// Don't flood
		checkFlood();

		// Make sure we're verified
		if (!$ui->isVerified())
			$layout->errorMsg('Invalid verification key.');

		// Get submitted info
		$tid = $sql->prot($_POST['tid']);
		$msg = $sql->prot(trim($_POST['rply_msg']));

		// Verify we said something
		if ($msg == '')
			$layout->errorMsg('You must type a post.');

		// Verify that is a real topic that isn't locked. If it is locked, make sure I have powers
		$check_topic = $sql->query("select `locked` from `forum_topics` where `id` = '$tid' limit 1");

		// Not real?
		if ($sql->num($check_topic) == 0)
			$layout->errorMsg('Invalid topic id');

		// Get if it's locked or not
		list($locked) = $sql->fetch_row($check_topic);

		// I don't have perms?
		if (((bool) $locked) && !in_array($ui->userID(), (array) $forum_mods))
			$layout->errorMsg('Insufficent permissions.');

		// Reply
		$sql->query("
			insert into `forum_posts` set
				`topic` = '$tid',
				`poster` = '".$ui->userID()."',
				`date` = UNIX_TIMESTAMP(),
				`post` = '$msg'
			");


		// Get reply id
		$reply_id = $sql->lastid();


		// Update latest info for this topic
		$sql->query("
			update `forum_topics` set
				`lastpost` = UNIX_TIMESTAMP(),
				`lastposter` = '".$ui->userID()."',
				`numreplies` = `numreplies` + 1
			where
				`id` = '$tid'
			limit 1
		");

		// Return to topic
		redirect($entry_point.'?action=forum&sa=topic&id='.$tid.'#r'.$reply_id);

	break;

	/*
	 * Delete a post
	 */
	case 'delete_post':

		// Make sure we're verified
		if (!$ui->isVerified())
			$layout->errorMsg('Not verified.');

		// Post ID
		$pid = $sql->prot($_GET['pid']);

		// Get post info
		$get_reply_info = $sql->query("
			select
				p.`poster`,
				p.`topic`,
				t.`locked`
			from
				`forum_posts` as p
				join `forum_topics` as t on t.`id` = p.`topic`
			where
				p.`id` = '$pid'
			limit 1
		");

		// Make sure this post really does exist
		if ($sql->num($get_reply_info) == 0)
			$layout->errorMsg('This post does not exist.');

		// Get them
		list($poster_id, $topic_id, $is_locked) = $sql->fetch_row($get_reply_info);
		$sql->free($get_reply_info);

		// Not editing stuff in a locked topic unless we're a mod
		if ($is_locked == 1 && !in_array($ui->userID(), (array) $forum_mods))
			$layout->errorMsg('You cannot delete posts in a locked topic.');

		// Not deleting stuff that isn't by me and I'm not a mod
		if ($poster_id != $ui->userID() && !in_array($ui->userID(), (array) $forum_mods))
			$layout->errorMsg('You can only delete your own posts.');

		// Kill reply
		$sql->query("delete from `forum_posts` where `id` = '$pid' limit 1");

		// Get current last posts stuff for topic
		list($topic_last_poster, $topic_date_last_post) = $sql->fetch_row($sql->query("
			select
				`poster`,
				`date`
			from
				`forum_posts`
			where
				`topic` = '$topic_id'
			order by
				`id`
			desc
			limit 1
		"));
		$sql->freelast();

		// Lessen number of replies for the topic, and set the last stuff appropriately
		// We're using typecasting here to force the values to be zero if there is no-
		// last post
		$sql->query("
			update `forum_topics`
			set
				`numreplies` = `numreplies` - 1,
				`lastposter` = '".((int)$topic_last_poster)."',
				`lastpost` = '".((int)$topic_date_last_post)."'
			where
				`id` = '$topic_id'
			limit 1");

		// Go back to topic
		redirect($entry_point.'?action=forum&sa=topic&id='.$topic_id);

	break;

	/*
	 * Create a new topic
	 */
	case 'new':

		if (!$ui->userActivated())
			$layout->errorMsg('Account not activated');

		/*
		 * Form submitted already?
		 */
		if (@$_POST['do_make_topic'] == 'yes')
		{
			// Make sure we're verified
			if ($_POST[$ui->verifKey()] != $ui->verifVal())
				$layout->errorMsg('Invalid verification key.');

			// No flooding
			checkFlood();

			// Get posted fields
			$title = $sql->prot(trim($_POST['title']));
			$body = $sql->prot(trim($_POST['body']));

			// Neither can be blank
			if ($title == '' || $body == '')
				$layout->errorMsg('Must fill in both title and body.');

			// Insert
			$sql->query("
				insert into `forum_topics`
				set
					`title` = '$title',
					`poster` = '".$ui->userID()."',
					`date` = UNIX_TIMESTAMP(),
					`post` = '$body'
			");

			// Get id
			$topic_id = $sql->lastid();

			// Go to it
			redirect($entry_point.'?action=forum&sa=topic&id='.$topic_id);

			// Redundancy
			exit;
		}

		/*
		 * Show form
		 */

		// Start layout
		$layout->head('Forum &raquo; New Topic');

		// Form
		echo '
		<form action="'.$entry_point.'?action=forum&amp;sa=new" method="post">
			<div id="new_topic_form">
				<input type="hidden" name="'.$ui->verifKey().'" value="'.$ui->verifVal().'" />
				<input type="hidden" id="do_make_topic" name="do_make_topic" value="yes" />
				<div class="forum_field_sep">
					<label for="title">Title:</label>
					<input type="text" id="title" name="title" />
				</div>
				<div class="forum_field_sep">
					<label for="body">Content:</label><br />
					<textarea cols="5" rows="5" id="body" name="body"></textarea>
				</div>
				<div class="forum_field_sep">
					<input type="submit" value="Post" />
				</div>
			</div>
		</form>
		';

		// End layout
		$layout->foot();

	break;

	/*
	 * List topics
	 */
	default:

		/*
		 * Number of them
		 */
		list($num_topics) = $sql->fetch_row($sql->query("select count(*) from `forum_topics`"));
		$sql->freelast();

		/*
		 * Pagination
		 */
		$perpage = 20;
		$pager = new Pagination;
		$pager->setPerPage($perpage);
		$pager->setRows($num_topics);
		$pager->setBaseUrl($entry_point.'?action=forum');
		$pager->init();

		/*
		 * Get them
		 */
		$get_topics = $sql->query("
			select
				t.id as tid,
				t.title,
				t.locked,
				t.sticky,
				t.date,
				t.lastedit,
				t.poster,
				t.lastpost,
				t.lastposter,
				t.numreplies,
				up.username as postername,
				ur.username as lastpostername
			from
				forum_topics as t
				join members as up on up.id = t.poster
				left join members as ur on ur.id = t.lastposter
			order by
				t.sticky desc,
				t.lastpost desc,
				t.id desc
			limit ".$pager->getStartAt().", $perpage
		");

		$topics = array();

		while ($info = $sql->fetch_assoc($get_topics))
			$topics[] = $info;

		$sql->free($get_topics);

		/*
		 * Start layout
		 */
		$layout->head('Forum');

		// Show pagination
		$pager->showPagination();

		// Make new topic
		echo '
		<p>
			<a href="'.$entry_point.'?action=forum&amp;sa=new">New Topic</a>
		</p>
		';

		// Show topics
		echo '
		<table>
			<tr>
				<th style="text-align: left;">Title</th>
				<th>Author</th>
				<th>Replies</th>
				<th style="text-align: left;">Last Post</th>
			</tr>
		';

		$alt = false;
		foreach ($topics as $info) {

			echo '
			<tr',$alt ? ' class="alt"' : '','>
				<td style="text-align: left;"><a href="'.$entry_point.'?action=forum&amp;sa=topic&amp;id='.$info['tid'].'">'.stringprep($info['title'], true).'</a></td>
				<td>'.userLink($info['poster'], $info['postername']).'</td>
				<td>'.number_format($info['numreplies']).'</td>
				<td style="text-align: left;"><span class="sm_date">';

				if (is_numeric($info['lastpost']) && is_numeric($info['lastposter']))
					echo $ui->myDate(TIME_FORMAT, $info['lastpost']) . '<br>By: '.userLink($info['lastposter'], $info['lastpostername']);
				else
					echo $ui->myDate(TIME_FORMAT, $info['date']) . '<br>By: '.userLink($info['poster'], $info['postername']);

				echo '</span></td>
			</tr>
			';

			$alt = !$alt;
		}

		echo '
		</table>
		';

		/*
		 * End layout
		 */
		$layout->foot();
	break;
}
