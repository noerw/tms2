<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack


// Store polls and options here
$polls = array();
$options = array();

// Get the polls
$get_polls = $sql->query("
	select
		pq.poll_id,
		pq.total_votes,
		pq.question
	from
		poll_questions as pq
	order by pq.poll_id desc
");

// Stuff options array and create options array
while ($poll = $sql->fetch_assoc($get_polls)) {
	$polls[] = array(
		'id' => $poll['poll_id'],
		'votes' => $poll['total_votes'],
		'question' => stringprep($poll['question'], true)
	);
	$options[$poll['poll_id']] = array();
}

// Free memory that query used
$sql->free($get_polls);

// Get the options
$get_options = $sql->query("
	select
		po.poll_id,
		po.votes,
		po.option
	from
		poll_options as po
	order by
		po.votes
	desc
");

// Stuff options array
while ($option = $sql->fetch_assoc($get_options))
	$options[$option['poll_id']][] = array(
		'votes' => $option['votes'],
		'option' => stringprep($option['option'], true)
	);

// Free memory that query used
$sql->free($get_options);

// Start layout
$layout->head('Poll History');

// Show polls
foreach ($polls as $poll) {

	// Start off poll
	echo '
	<p class="poll_question">'.$poll['question'].'</p>
	<ul class="poll_options">';

	// Show options
	foreach ($options[$poll['id']] as $option) {
		$this_percent = $option['votes'] == 0 ? 0 : round(($option['votes'] / $poll['votes']) * 100);
		echo '
		<li class="border">
			<div class="alt poll_option_bar" style="width: '.$this_percent.'%;">
				<div class="poll_option">
					<span class="poll_option_op">'.$option['option'].'</span>  <span class="poll_option_sub">'.$this_percent.'% - '.$option['votes'].' votes</span>
				</div>
			</div>
		</li>';
	}

	// End poll
	echo '
	</ul>
	';
}

// Kill them
unset($polls, $options);


// End layout
$layout->foot();
