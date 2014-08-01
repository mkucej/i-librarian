<?php

$id = sprintf("%05d", $_GET['id']);
$delimiter = PHP_EOL;
$output = "var tinymceImageList = [ ";
$directory = dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "supplement";

if (is_dir($directory)) {

    $files = glob($directory . DIRECTORY_SEPARATOR . $id . '*');
    if (is_array($files)) {
        foreach ($files as $file) {

            $file = basename($file);
            $isimage = null;
            $image_array = array();
            $image_array = @getimagesize('library/supplement/' . $file);
            $image_mime = $image_array['mime'];
            if ($image_mime == 'image/jpeg' || $image_mime == 'image/gif' || $image_mime == 'image/png')
                $isimage = true;

            if ($isimage) {

                $output .= $delimiter
                        . ' { title: "'
                        . utf8_encode(substr($file, 5))
                        . '", value: "'
                        . utf8_encode('attachment.php?attachment=' . $file)
                        . '" },';
            }
        }
    }

    $output = substr($output, 0, -1);
    $output .= $delimiter;
}

$output .= ' ];';

header('Content-type: text/javascript');

echo $output;
?>
