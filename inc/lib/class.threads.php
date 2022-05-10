<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Stuff like forum posts, news articles, map ratings, tutorial comments, etc etc etc
 */

class Threads {

	/*
	 * Store the items
	 */
	var
		$items = array(),
		$id = false;

	/*
	 * Say how to id each item listed
	 */
	public function setItemId($prep, $field) {
		$this->id = array($prep, $field);
	}

	/*
	 * Add one item
	 */
	public function add($item) {
		$this->items[] = $item;
	}

	/*
	 * Show the items
	 */
	public function show() {

		// Snag that
		global $entry_point, $ui;

		$alt = false;

		// Show them
		foreach ($this->items as $info) {

			// Each
			echo '
			<div class="gen_box_rh border avatar',$alt ? ' alt' : '','">
				<div',$this->id ? ' id="'.$this->id[0].''.$info[$this->id[1]].'"' : '',' class="gen_box_rh_head border',$alt ? ' alt' : '','">
					<h4>Written by '.userLink($info['userid'], $info['username']).'
					',$info['date'] ? ' on '. $ui->myDate('F d, Y @ h:i A', $info['date']) : '','</h4>
				</div>
				',!empty($info['tr']) ? '<div class="gen_box_rh_head_r border'.($alt ? ' alt' : '').'">'.$info['tr'].'</div>' : '','
				<div class="gen_box_rh_content">
					<div class="gen_box_rh_avatar">
						<img src="/a/'.$info['userid'].'" alt="'.stringprep($info['username']).'" />
					</div>
					<div class="gen_box_rh_sub_content border',$alt ? ' alt' : '','">
						<p>'.stringprep($info['comment'], true, true, true, true).'</p>
					</div>
				</div>
			</div>';

			// Alternate bg's
			$alt = !$alt;
		}
	}
}
