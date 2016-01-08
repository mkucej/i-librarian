<?php

include_once 'data.php';
include_once 'functions.php';
include_once 'pdfclass.php';
session_write_close();

// Files
$file = preg_replace('/^0-9\.pdf/', '', $_GET['file']);
$image = IL_IMAGE_PATH . DIRECTORY_SEPARATOR . $file . '.1.jpg';
$icon = IL_TEMP_PATH . DIRECTORY_SEPARATOR . $file . '.1.icon.jpg';

// Paths
$pdf_path = IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($file);

//CACHE THIS FILE
$seconds_to_cache = 604800;
$ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
header("Expires: $ts");
header("Pragma: private");
header("Cache-Control: max-age=$seconds_to_cache");
header("Cache-Control: private");
header('Last-Modified: ' . gmdate(DATE_RFC1123, filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)));

// Content type
header('Content-Type: image/jpeg');
header("Content-Disposition: inline");

// Output from cache
if (is_readable($icon) && filemtime($pdf_path . DIRECTORY_SEPARATOR . $file) < filemtime($icon)) {
    ob_clean();
    flush();
    readfile($icon);
    die();
}

// Make big image if not found
if (!is_readable($image) || filemtime($image) < filemtime($pdf_path . DIRECTORY_SEPARATOR . $file)) {
    
    $pdfHandler = new PDFViewer($file);
    $pdfHandler->createPageImage(1);
}

// Icon dimensions
$new_width = 720;
$new_height = 480;

if (!is_readable($image)) {

    // Error! Ghostscript DOES NOT WORK
    $image_p = imagecreate($new_width, $new_height);
    $background_color = imagecolorallocate($image_p, 255, 255, 255);
    $text_color = imagecolorallocate($image_p, 255, 0, 0);
    imagestring($image_p, 3, 20, 20, "Error! Program Ghostscript not functional.", $text_color);
} else {

    // Resample
    list($width, $height) = getimagesize($image);
    $image_p = imagecreatetruecolor($new_width, $new_height);
    $image = imagecreatefromjpeg($image);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $width / 1.5);
}

// Output to a file than browser
imagejpeg($image_p, $icon, 75);
imagejpeg($image_p, null, 75);

imagedestroy($image_p);
