<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

/*
 * Get map id
 */
$mid = $sql->prot($_GET['id']);

/*
 * Get screenshot number
 */
$screenshot = $sql->prot($_GET['scn']);

/*
 *  Decide what size to cache the screenshot as
 */

// Really small
if (isset($_GET['tiny']) && $_GET['tiny'] == 1)
	$size = 'tiny';

// Something custom, although not too huge or too small
elseif (
		ctype_digit($_GET['sw']) &&
		$_GET['sw'] > 0 &&
		$_GET['sw'] < OV_Thumbs::MAX_WIDTH &&
		ctype_digit($_GET['sh']) &&
		$_GET['sh'] > 0 &&
		$_GET['sh'] < OV_Thumbs::MAX_HEIGHT
	)
	$size = array($_GET['sw'], $_GET['sh']);

// The usual size
else
	$size = 'normal';


/*
 *  We need the class for this
 */
try {
	$cache = new SCN_Thumbs($mid, $size, $screenshot);
}
catch (cacheException $e) {
	exit('Error : '.$e->getMessage());
}

// Already cached? Output it.
if ($cache->isCached())
{
	output_image($cache->cachePath());
}

// Otherwise deal with it
else
{
	// Deal with it
	try {
		$cache->cache(true, true);
	}

	// Error saving it? Just try outputting it then.
	catch (cacheSaveException $e) {
		try {
			$cache->output_unsaved('png');
		}
		catch (cacheOutputException $e) {
			exit($e->getMessage());
		}
	}

	// Error converting?
	catch (cacheResampleException $e) {
		exit('Error resampling image: '.$e->getMessage());
	}

	// Something else
	catch (cacheException $e) {
		exit('Error : '.$e->getMessage());
	}

	// Successfully saved. Now output it
	output_image($cache->cachePath());
}
