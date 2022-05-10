<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Handle pagination
 */
class Pagination {
	private
		$current_page,
		$per_page,
		$num_pages,
		$num_rows,
		$base_url,
		$link,
		$current,
		$next,
		$prev,
		$padding;

	/*
	 * Set number of whatevers per page
	 */
	public function setPerPage($per_page) {
		$this->per_page = $per_page;
	}

	/*
	 * Set number of rows
	 */
	public function setRows($num_rows) {
		$this->num_rows = $num_rows;
	}

	/*
	 * Get row we start at
	 */
	public function getStartAt() {
		return ($this->current_page - 1) * $this->per_page;
	}

	/*
	 * Set base url
	 */
	public function setBaseUrl($base_url) {
		$this->base_url = $base_url;
	}

	public function init() {

		// Determine number of pages
		$this->num_pages = ceil($this->num_rows / $this->per_page);

		// Determine current page
		$this->current_page = isset($_GET['p']) && ctype_digit($_GET['p']) && $_GET['p'] > 0 && $_GET['p'] <= $this->num_pages ? $_GET['p'] : 1;

		// Link htmls
		$this->current = '[<strong>%d</strong>]';
		$this->prev = '<a href="'.$this->base_url.'&amp;p=%d">&laquo;</a>';
		$this->next = '<a href="'.$this->base_url.'&amp;p=%d">&raquo;</a>';
		$this->link = '<a href="'.$this->base_url.'&amp;p=%1$d">%1$d</a>';
		$this->padding  = '...';
	}

	/*
	 * Get current page
	 */
	public function getCurrentPage() {
		return $this->current_page;
	}

	/*
	 * Show list of links of pages
	 */
	public function showPagination() {

		// Wrap it
		echo '
		<p class="pagination">Page:
		';

		// Zero or some other buggish thing?
		if ($this->num_rows == 0 || !is_numeric($this->num_rows))
		{
			echo sprintf($this->current, 1).'</p>';
			return;
		}

		// Display link to $this->previous page?
		if ($this->current_page != 1) // Can't be first page
			echo sprintf($this->prev, $this->current_page - 1) . ' ';

		// If under or equal to 8 pages, just show them
		if ($this->num_pages <= 8) {
			for ($p = 1; $p <= $this->num_pages; $p++)
				echo sprintf($p == $this->current_page ? $this->current : $this->link, $p).' ';
			unset($p);
		}
		// Over 5 pages?
		else {
			// Am I on page 1?
			if ($this->current_page == 1) {
				echo sprintf($this->current, $this->current_page) . ' ';
				echo sprintf($this->link, 2) . ' ';
				echo $this->padding . ' ';
				printf($this->link, $this->num_pages);
			}
			// Do something similiar for the last
			elseif ($this->current_page == $this->num_pages) {
				echo sprintf($this->link, 1) . ' ';
				echo $this->padding . ' ';
				echo sprintf($this->link, $this->current_page - 1) . ' ';
				printf($this->current, $this->current_page);

			}
			// Something under 4?
			elseif ($this->current_page < 4) {
				for ($p = 1; $p < 5; $p++)
					echo sprintf($p == $this->current_page ? $this->current : $this->link, $p).' ';
				echo $this->padding . ' ';
				printf($this->link, $this->num_pages);
			}
			// Do something similiar for ones near the last
			elseif (($this->num_pages - $this->current_page) < 3) {
				echo sprintf($this->link, 1) . ' ';
				echo $this->padding . ' ';
				for ($p = $this->num_pages - 3; $p <= $this->num_pages; $p++)
					echo ' ' . sprintf($p == $this->current_page ? $this->current : $this->link, $p);
			}
			// Are we smack dab in the middle?
			else {
				echo sprintf($this->link, 1) . ' ';
				echo $this->padding . ' ';
				for ($p = $this->current_page - 1; $p < $this->current_page + 2; $p++)
					echo sprintf($p == $this->current_page ? $this->current : $this->link, $p).' ';
				echo $this->padding . ' ';
				printf($this->link, $this->num_pages);
			}
		}

		// Display link to next page
		if ($this->current_page != $this->num_pages) // Can't be last page
			echo ' ' . sprintf($this->next, $this->current_page + 1);


		// Finish
		echo '
		</p>
		';
	}
}
