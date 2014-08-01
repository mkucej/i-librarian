<?php

include_once 'data.php';
include_once 'functions.php';
session_write_close();

// Files
$file = preg_replace('/^0-9\.pdf/', '', $_GET['file']);
$png = 'library/pngs/' . $file . '.1.png';
$icon = $temp_dir . DIRECTORY_SEPARATOR . $file . '.1.icon.png';

// Paths
$pdf_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';
$png_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'pngs';

//CACHE THIS FILE
$seconds_to_cache = 604800;
$ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
header("Expires: $ts");
header("Pragma: private");
header("Cache-Control: max-age=$seconds_to_cache");
header("Cache-Control: private");
header('Last-Modified: ' . gmdate(DATE_RFC1123, filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)));

// Content type
header('Content-Type: image/png');
header("Content-Disposition: inline");

// Output from cache
if (is_readable($icon) &&
        filemtime($pdf_path . DIRECTORY_SEPARATOR . $file) < filemtime($icon)) {
    ob_clean();
    flush();
    readfile($icon);
    die();
}

// Make big PNG if not found
if (!is_readable($png) ||
        filemtime($png_path . DIRECTORY_SEPARATOR . $file . '.1.png') < filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)) {
    exec(select_ghostscript() . " -dSAFER -sDEVICE=png16m -r150 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dDOINTERPOLATE -dFirstPage=1 -dLastPage=1 -o \"" . $png_path . DIRECTORY_SEPARATOR . $file . ".1.png\" \"" . $pdf_path . DIRECTORY_SEPARATOR . $file . "\"");
}

// Icon dimensions
$new_width = 360;
$new_height = 240;

if (!is_readable($png)) {

    // Error! Ghostscript DOES NOT WORK
    $image_p = @imagecreate($new_width, $new_height);
    $background_color = imagecolorallocate($image_p, 255, 255, 255);
    $text_color = imagecolorallocate($image_p, 255, 0, 0);
    imagestring($image_p, 3, 20, 20, "Error! Program Ghostscript not functional.", $text_color);
} else {

    // Resample
    list($width, $height) = getimagesize($png);
    $image_p = imagecreatetruecolor($new_width, $new_height);
    $image = imagecreatefrompng($png);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $width / 1.5);
}

//Color index
imagetruecolortopalette($image_p, false, 256);

// Output to a file than browser
imagepng($image_p, $icon, 1);
imagepng($image_p, null, 1);

imagedestroy($image_p);
?>
