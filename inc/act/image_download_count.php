<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;

// Define image dimensions
$dimensions = array(35, 12);

// Get map id
$mid = $sql->prot($_GET['id']);

// Get map info
try {
	$map = new MapInfo($mid);
	$map_info = $map->getStatsInfo();
}
catch (MapException $e){
	exit($e->getMessage());
}

// Won't need sql at this point
$sql->close();

// Downs
$downloads = $map_info->DOWNLOADS_FILE;

try {
	// Try to make the text centered depending on how long it is
	switch (strlen($downloads)) {
		case 1:
			$text_x_pos = 9;
		break;
		case 2:
			$text_x_pos = 6;
		break;
		case 3:
			$text_x_pos = 3;
		break;
		case 4:
		default:
			$text_x_pos = 0;
		break;
	}

	// Create image resource
	if (!($resource = @imagecreatetruecolor($dimensions[0], $dimensions[1])))
		throw new Exception('Cannot generate image.');

	// Ensure it is really transparent
	imagesavealpha($resource, true);
	imagealphablending($resource, false);

	// Even more so
	$transparentColor = imagecolorallocatealpha($resource, 200, 200, 200, 127);
	imagefill($resource, 0, 0, $transparentColor); // Fill it
	imagecolordeallocate($resource, $transparentColor); // Free that

	// Allocate text color - #2e488e
	if (!($text_color = imagecolorallocate($resource, 0x2E, 0x48, 0x8E)))
		throw new Exception('Cannot generate color.');

	// Text string
	$text_string = "[$downloads]";

	// Write download count to image
  if (!@imagestring($resource, 2, $text_x_pos, 0, $text_string, $text_color))
    throw new Exception('Cannot write download count to image');

	imagecolordeallocate($resource, $text_color); // Release that ram

	// Start outputting it
	ob_start();
	imagepng($resource, null, 9);
	imagedestroy($resource);
	$image = ob_get_clean();

	// Give it
	header('Content-type: image/png');
	header('Content-length: '.strlen($image));
	echo $image;

	//halt
	exit;
}

/*
 * Handle errors
 */
catch (Exception $e){
	if (is_resource($resource)) // Kill image resource
		imagedestroy($resource);
	exit($e->getMessage()); // Send out error message
}
