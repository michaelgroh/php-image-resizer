# php-image-resizer

## Purpose

To resize images on the fly for responsive web applications using specially crafted URLs. Resized images are served from a cache, without involvement from php, after they have been created.

Features Include:

* Proportionately resizing to a specified width or height.
* Square by resizing the shortest edge and cropping any excess. Portrait images will have the top and bottom cropped and landscape images will have the left and right sides cropped.
* Square by resizing the longest edge and padding any empty space. Portrait images will have padding added to the left and right and landscape images will have padding added to the top and bottom.
* Limiting the total area of the images that can be created. Defaults to 4,000,000 (avg of 2000x2000).
    
Notes:

* **There is nothing in this project that will clean up the cached images or check disk space.**
* Supports only jpg and png conversion.
* Errors will be reported in the HTTP headers with status codes and explanations in the *X-Error-Info* header.

## Prerequisites
* Ensure /cache does not exist in your web root or that you change the cache folder in resize.php and the web server config.
* Ensure you have ImageMagick v6.3.2 or greater installed.

## Install

1. Place resize.php in to your web root.
2. Add the following to your web config:

	RewriteCond %{DOCUMENT_ROOT}/cache%{REQUEST_URI} -f
	RewriteRule .* /cache%{REQUEST_URI} [L]
	RewriteRule ^(.*)/([0-9]{1,4})(h|w|sqp|sqc)(/.*\.(jpg|png))$ /resize.php?size=$2&method=$3 [L]
    
## Usage

To resize images you will insert the size and resize method in to a normal image URL just before the file name.

Resize Methods:

* w = resize to width
* h = resize to height
* sqp = square with pad
* sqc = square with crop

Example URLs:

* /graphics/landscape.jpg (200 x 100)
* /graphics/portrait.jpg (100 x 200)

### To resize based on width:

Resize URL: /graphics/100w/landscape.jpg  
Result: JPEG Image, 100 x 50

Resize URL: /graphics/50w/portrait.jpg  
Result: JPEG Image, 50 x 100

### To resize based on width:

Resize URL: /graphics/50h/landscape.jpg   
Result: JPEG Image, 100 x 50

Resize URL: /graphics/50h/portrait.jpg  
Result: JPEG Image, 25 x 50

### Square with padding:

Resize URL: /graphics/75sqp/landscape.jpg  
Result: JPEG Image, 75 x 75 with padding on top and bottom

Resize URL: /graphics/75sqp/portrait.jpg  
Result: JPEG Image, 75 x 75 with padding on left and right

### Square with a  crop:

Resize URL: /graphics/75sqc/landscape.jpg  
Result: JPEG Image, 75 x 75 left and right sides cropped

Resize URL: /graphics/75sqc/portrait.jpg  
Result: JPEG Image, 75 x 75 top and bottom cropped

