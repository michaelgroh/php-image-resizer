<?php
ini_set("memory_limit","80M");

try {
	// Ensure expected prams exist.
	if (!isset($_GET['size']) || !isset($_GET['method'])) {
		throw new Exception('size or method param not provided');
	}
	// Setup
	$maxArea = 4000000; // Maximum allowable area of the requested image 2000x2000
	$size = (int) $_GET['size'];
	$method = $_GET['method'];
	$sqpBgColors = array( // If image is padded we need a color for padding
		'jpg'  => 'white'
		,'png' => 'transparent'
	);
	$pngBackgroundColor = 'transparent';
	$jpegQuality = 70;
	$cacheSubfolderName = 'cache'; // Will be prepended to the rel url. Make sure it matches your web server config.

	// Make sure we have a valid resize method and choose our resize command.
	switch($method) {
		case 'w': // Width: Size proportionately to a height of $size
			$command = 'convert %1$s -resize %5$s %6$s %4$s';
			break;
		case 'h': // Height: Size proportionately to a height of $size
			$command = 'convert %1$s -resize x%5$s %6$s %4$s';
			break;
		case 'sqp':  // Squarepad: Size longest edge to $size and pad the shorter edge to make a square.
			$command = 'convert %1$s -strip -resize \'%5$sx%5$s\' -background %7$s -gravity center -extent %5$sx%5$s %6$s %4$s';
			break;
		case 'sqc':   // Squarecrop: Size shortest edge to $size and crop the long edge to a square.
			$command = 'convert %1$s -strip -resize \'%5$sx%5$s^\' -gravity center -extent %5$sx%5$s %6$s %4$s';
			break;
		default:
			if (headers_sent()) {
				die('Invalid resize method');
			}
			header('400 Bad Request', true, 400);
			header('X-Error-Info: Invalid resize method');
			exit();
	}
	$target = $_SERVER['DOCUMENT_ROOT'] . '/' . $cacheSubfolderName . $_SERVER['SCRIPT_NAME'];
	// Get the path without any query string from REQUEST_URI. SCRIPT_NAME was
	// originally used to allow use of .htaccess but I couldn't get that to work
	// right away and decided it wasn't a priority.
	$source = parse_url($_SERVER['REQUEST_URI']);
	// Replace the size and method in our path and assign it the path element of a new array.
	$source = array('path' => $_SERVER['DOCUMENT_ROOT'] . str_replace("$size$method/", '', $source['path']));

	if (!file_exists($source['path'])) {
		if (headers_sent()) {
			die('Source file not found');
		}
		header('404 Not Found', true, 404);
		exit();
	}

	// We need dimensions for the square commands and to check final size.
	$imageSize = getimagesize($source['path']);

	if ($imageSize === false) {
		throw new Exception('Could not retrieve source image dimensions of ' . $source['path']);
	}
	$source['width'] = $imageSize[0];
	$source['height'] = $imageSize[1];

	// Now that we have source dimenions we can check the target area.
	if ($method == 'w') {
		$targetArea = $size/$source['width']*$source['height']*$size;
	} elseif ($method == 'h') {
		$targetArea = $size/$source['height']*$source['width']*$size;
	} else {
		$targetArea = $size * $size;
	}
	if ($targetArea > $maxArea || $targetArea < 1) {
		if (headers_sent()) {
			die('Resized image would be too small or too large');
		}
		header('400 Bad Request', true, 400);
		header('X-Error-Info: Resized image would be too small or too large ' . (int) $targetArea);
		exit();
	}

	// We need the extesion and the directory so it can be created if necessary.
	$pathInfo = pathinfo($source['path']);
	$source['extension'] = $pathInfo['extension'];

	// We may need to create our target folder.
	$targetFolder = pathinfo($target, PATHINFO_DIRNAME);

	if (!file_exists($targetFolder)) {
		mkdir($targetFolder, 0777, true);
	}

	// Plug in the values to our command.
	$command = sprintf($command
		,$source['path']			// 1. Source Path
		,$source['width']			// 2. Source Width
		,$source['height']			// 3. Source Height
		,$target					// 4. Target Path
		,$size						// 5. Target Size
		,"-quality $jpegQuality"	// 6. Jpeg Quality
		,$sqpBgColors[$source['extension']]			// 7. Background Color
		,($source['extension'] == 'jpg' ? 'jpeg' : $source['extension'])	// 8. Define file type.
	);

	// Execute our command
	$commandOutput = array();
	$retval = null;
	
	exec($command, $commandOutput, $retval);

	if ($retval !== 0) {
		throw new Exception('Resized command failed. Check for permission problems or an old version of convert.');
	}
	$contentType = 'image/' . ($source['extension'] == 'jpg' ? 'jpeg' : $source['extension']);
	// If we don't send an Expires header some (all?) browsers will immediately
	// send a get request with an If-modified header.
	header('Expires: '.gmdate('D, d M Y H:i:s', time('+24 hours')).' GMT'); //24 Hour Cache
	header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');
	header("Content-Type: $contentType");
	header("Content-Disposition: inline; filename=\"".$pathInfo['filename']."\"");
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: ' . filesize($target));
	// Just want to put something in the header for troubleshooting purposes.
	header('X-Resized-By: MG PHP Image Resizer');
	set_time_limit(0);
	ob_end_flush();
	readfile($target);
} catch (Exception $e) {
	if (headers_sent()) {
		die('Cannot send HTTP Status Code 500. ' . $e->getMessage());
	}
	header( '500 Internal Server Error', true, 500);
	header('X-Error-Info: ' . $e->getMessage());
	exit();
}
?>
