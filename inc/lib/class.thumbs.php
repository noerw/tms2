<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;


/*
 *  Custom exceptions used by following classes
 */
class cacheResampleException extends Exception {}
class cacheSaveException extends Exception {}
class cacheException extends Exception {}
class cacheOutputException extends Exception {}

/*
 *  Some standards used by these classes
 */
interface Thumbs {

	// Settings
	const
		// Default sizes
		DEFAULT_WIDTH = 100,
		DEFAULT_HEIGHT = 75,
		DEFAULT_TINY_WIDTH = 30,
		DEFAULT_TINY_HEIGHT = 23,
		MAX_WIDTH = 1000,
		MAX_HEIGHT = 1000,

		// Allow custom sizes?
		// In theory this could cause problems. If so, make it false
		AllowCustomSizes = true,

		// Format for cached thumbs. (one of jpg, png, or gif)
		IMG_FORMAT = 'png',

		// And lastly, max cache custom sized thumbs
		MAX_CUST_SIZES = 20;

	public function isCached();
	public function cachePath();
	public function remove($all = false);
	public function numCaches();
	public function cache($overwrite = true, $save = true);
	public function output_unsaved($type = self::IMG_FORMAT);
	public function get_orig_path();
}

/*
 * Functions and properties in common between these two classes
 */
abstract class Thumbs_base implements Thumbs {

	// Hold these
	protected $mid, $path, $size;

	// When we're working on the new pic, keep resource here
	protected
		$new_image_resource;

	/*
	 *  Is this particular overview and size cached as a thumb?
	 */
	public function isCached() {
		return file_exists($this->path) && is_readable($this->path);
	}

	/*
	 *  Where is it?
	 */
	public function cachePath() {
		return $this->path;
	}

	/*
	 * Remove it?
	 */
	public function remove($all = false) {
		global $sp;

		// Remove all cached thumbs for this overview
		if ($all === true) {

			// Decide where all of them are
			switch ($this->t_type) {

				// Overview thumbs
				case 'ov':
					$paths = $sp['thumbs'] . $this->mid. '_*x*.'.self::IMG_FORMAT;
				break;

				// Thumbs of this specific screenshot
				case 'scn':
					$paths = $sp['thumbs'] . $this->mid. '_sc'.(!is_numeric($this->scrn) ? '*' : $this->scrn).'_*x*.'.self::IMG_FORMAT;
				break;

				// This shouldn't happen, but if it comes to this kill 'em all to be thorough
				default:
					$paths = $sp['thumbs'] . $this->mid. '_*.'.self::IMG_FORMAT;
				break;
			}

			// Go through and kill them
			foreach ((array) @glob($paths) as $p) {
				$p = realpath($p);
				@unlink($p);
				@`rm -f $p`;
			}
		}

		// Just remove the one at hand
		else {
			$this->path = realpath($this->path);
			@unlink($this->path);
			@`rm -f $this->path`;
		}
	}

	/*
	 * Find out just how many custom sized thumbs we have saved for this image..
	 */
	public function numCaches() {
		return count((array) @glob($sp['thumbs'] . $this->mid. '_'.($this->t_type == 'scn' ? "sc{$this->scn}_" : '').'*x*.'.self::IMG_FORMAT));
	}

	/*
	 * Cache it, overwriting as necessary
	 */
	public function cache($overwrite = true, $save = true) {
		global $sql, $allowed_picture_exts;

		// If it exists and we're set not to overwrite and to save
		if ($this->isCached() && $overwrite == false && $save == true)
			throw new cacheException('Thumbnail exists and set not to overwrite.');

		// If we've got too many and wanting to save this one, remove others
		if ($save == true && $this->numCaches() >= self::MAX_CUST_SIZES)
			$this->remove(true);

		/*
		 * Get original overview pic ready
		 */

		// Get path to it
		$orig_path = $this->get_orig_path();

		// Get it pretty
		$orig_basename = basename($orig_path);
		$orig_ext = get_file_extension($orig_basename);

		// Make sure it has the usual image file extension
		if (!in_array($orig_ext, $allowed_picture_exts))
			throw new cacheException('Invalid original image file extension - '.$orig_path);

		// Make sure it's an image and get the original dimensions
		if (!($orig_info = @getimagesize($orig_path)))
			throw new cacheException('Error getting original image info. Not a real image?');

		// Get original height
		list($orig_width, $orig_height, $orig_type) = $orig_info;

		// Let's not make a piece of crap. (resize upwards)
		if ($orig_width < $this->size[0] && $orig_height < $this->size[1]) {
			$this->size = array($orig_width, $orig_height);
		}
		// Maintain aspect ratio if desired size has one 0 in it
		$ratio = $orig_width/$orig_height;
		if ($this->size[0] == 0) {
		   $this->size[0] = this->size[1] * $ratio;
		} else if $this->size[1] == 0) {
		   $this->size[1] = $this->size[0] / $ratio;
		}

		// Attempt loading it based on ext
		if ($orig_type == IMAGETYPE_JPEG && !($orig_resource = @imagecreatefromjpeg($orig_path)))
			throw new cacheResampleException('Could not load image from JPG');
		elseif ($orig_type == IMAGETYPE_GIF && !($orig_resource = @imagecreatefromgif($orig_path)))
			throw new cacheResampleException('Could not load image from GIF');
		elseif ($orig_type == IMAGETYPE_PNG && !($orig_resource = imagecreatefrompng($orig_path)))
			throw new cacheResampleException('Could not load image from PNG');
		elseif ($orig_type == IMAGETYPE_BMP && !($orig_resource = imagecreatefrombmp($orig_path))) // custom function used here
			throw new cacheResampleException('Could not load image from BMP');

		// We have it?
		if (!is_resource($orig_resource))
			throw new cacheResampleException("Could not load image resource - $orig_ext - $orig_type");

		/*
		 * Get new image ready
		 */
		if (!($this->new_image_resource = @imagecreatetruecolor($this->size[0], $this->size[1])))
			throw new cacheResampleException('Could not create new image');

		/*
		 * Resize original onto new
		 */

		if (!@imagecopyresampled($this->new_image_resource, $orig_resource, 0, 0, 0, 0, $this->size[0], $this->size[1], $orig_width, $orig_height))
			throw new cacheResampleException('Could not resample image.');

		/*
		 * Clean up
		 */
		@imagedestroy($orig_resource);

		/*
		 * Now what?
		 */

		// If we're set to save it, do it depending on what we want
		if ($save == true) {
			// Save it as whateva
			switch (self::IMG_FORMAT) {
				case 'png':
					if (!@imagepng($this->new_image_resource, $this->cachePath(), 9))
						throw new cacheSaveException('Error saving as png');
				break;
				case 'jpg':
					if (!@imagejpeg($this->new_image_resource, $this->cachePath(), 90))
						throw new cacheSaveException('Error saving as jpg');
				break;
				case 'gif':
					if (!@imagegif($this->new_image_resource, $this->cachePath()))
						throw new cacheSaveException('Error saving as gif');
				break;
				default:
					throw new cacheSaveException('Not sure what to save it as');
				break;
			}

			// I assume we're done with it?
			imagedestroy($this->new_image_resource);
		}
		else {
			// Otherwise, we're done. Some other method will deal with
			// the $this->new_image_resource resource
		}
	}

	/*
	 * Output a previously cached()'d (but not saved) image to browser directly.
	 */
	public function output_unsaved($type = 'png') {

		// Gotta exist..
		if (!is_resource($this->new_image_resource))
			throw new cacheException('Cannot output nonexistant image.');

		// Deal with giving it
		switch ($type) {
			case 'png':
				if (!@imagepng($this->new_image_resource, null, 9))
					throw new cacheOutputException('Error outputting as png');
			break;
			case 'jpg':
				if (!@imagejpeg($this->new_image_resource))
					throw new cacheOutputException('Error outputting as jpeg');
			break;
			case 'gif':
				if (!@imagegif($this->new_image_resource))
					throw new cacheOutputException('Error outputting as gif');
			break;
			default:
				throw new cacheOutputException('Not sure what to output it as');
			break;
		}
	}
}

/*
 * Deal with caching and generating overview thumbnails
 */
class OV_Thumbs extends Thumbs_base implements Thumbs {

	/*
	 * Get map ID and size localized
	 */
	function __construct($mid, $size = 'normal') {
		global $sp, $sql;

		/*
		 *  Localize map id
		 */
		$this->mid = $sql->prot($mid);

		/*
		 * Localize type
		 */
		$this->t_type = 'ov';

		/*
		 * Determine size
		 */


		// Small. Used in map lists
		if ($size == 'tiny')
			$this->size = array(self::DEFAULT_TINY_WIDTH, self::DEFAULT_TINY_HEIGHT);

		// Custom. Nothing insanely huge since these are thumbs
		elseif (is_array($size) && count($size) == 2 && (int) $size[0] < self::MAX_WIDTH && (int) $size[1] < self::MAX_HEIGHT && self::AllowCustomSizes === true)
			$this->size = $size;
		// Usual
		else
			$this->size = array(self::DEFAULT_WIDTH, self::DEFAULT_HEIGHT);


		// Determine path
		$this->path = $sp['thumbs'] . $this->mid . '_' . $this->size[0] . 'x' . $this->size[1] . '.'.self::IMG_FORMAT;
	}

	/*
	 * Get path to original overview image
	 */
	public function get_orig_path() {
		global $sql, $sp;

		// Load current overview image
		$get_orig = $sql->query("select `img` from `maps` where `id` = '{$this->mid}' limit 1");

		// Map doesn't exist??
		if ($sql->num($get_orig) == 0)
			throw new cacheException('Image ID `'.$this->mid.'` does not exist');

		// Get path to overview
		list($orig_path) = $sql->fetch_row($get_orig);
		$sql->free($get_orig);

		// Remove terrible things
		$orig_path = str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $orig_path);

		// Base path off of where we are
		$orig_path = $sp['maps'] . $orig_path;

		// Make sure it exists..
		if (!file_exists($orig_path))
			throw new cacheException('Original image not found');

		// Return it..
		return realpath($orig_path);
	}
}

/*
 * Deal with caching and generating screenshot thumbnails
 * In some areas it differs significantly from the above
 */
class SCN_Thumbs extends Thumbs_base implements Thumbs {

	// Screenshot number
	protected $scrn;

	/*
	 * Get map ID and size localized
	 */
	function __construct($mid, $size = 'normal', $screenshot = 1) {
		global $sp, $sql;

		/*
		 *  Localize map id and screenshot number
		 */
		$this->mid = $sql->prot($mid);
		$this->scrn = $sql->prot($screenshot);

		/*
		 * Validate screenshot number. Can only be 1-3
		 */
		if (!in_array($screenshot, range(1, 3)))
			throw new cacheException('Invalid screenshot number');

		/*
		 * Localize type
		 */
		$this->t_type = 'scn';

		/*
		 * Determine size
		 */

		// Small. Used in map lists
		if ($size == 'tiny')
			$this->size = array(self::DEFAULT_TINY_WIDTH, self::DEFAULT_TINY_HEIGHT);

		// Custom. Nothing insanely huge since these are thumbs
		elseif (is_array($size) && count($size) == 2 && (int) $size[0] < self::MAX_WIDTH && (int) $size[1] < self::MAX_HEIGHT && self::AllowCustomSizes === true)
			$this->size = $size;
		// Usual
		else
			$this->size = array(self::DEFAULT_WIDTH, self::DEFAULT_HEIGHT);

		// Determine path
		$this->path = $sp['thumbs'] . $this->mid . '_sc'.$screenshot.'_' . $this->size[0] . 'x' . $this->size[1] . '.'.self::IMG_FORMAT;
	}

	/*
	 * Get path to original screenshot image
	 */
	public function get_orig_path() {
		global $sql, $sp;

		// Load current overview image
		$get_orig = $sql->query("select `sc{$this->scrn}` from `maps` where `id` = '{$this->mid}' limit 1");

		// Map doesn't exist??
		if ($sql->num($get_orig) == 0)
			throw new cacheException('Image ID does not exist');

		// Get path to screenshot
		list($orig_path) = $sql->fetch_row($get_orig);
		$sql->free($get_orig);

		// None?
		$orig_path = trim($orig_path);
		if ($orig_path == '')
			throw new cacheException('Map does not have screenshot #'.$this->scrn);

		// Remove terrible things
		$orig_path = str_replace(array('/home/jrgporg/public_html/tms/','maps/'), '', $orig_path);

		// Base path off of where we are
		$orig_path = $sp['maps'] . $orig_path;

		// Make sure it exists..
		if (!file_exists($orig_path) || !is_file($orig_path))
			throw new cacheException('Original screenshot not found');

		// Return it..
		return realpath($orig_path);
	}
}
