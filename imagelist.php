<?php
include_once 'data.php';
include_once 'functions.php';

$id = sprintf("%05d", $_GET['id']);
$output_arr = array ();
$directory = IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($id);

if (is_dir($directory)) {

    $files = glob($directory . DIRECTORY_SEPARATOR . $id . '*');
    if (is_array($files)) {
        foreach ($files as $file) {

            $file = basename($file);
            $isimage = false;
            $image_array = array();
            $image_array = @getimagesize(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($file) . DIRECTORY_SEPARATOR . $file);
            $image_mime = $image_array['mime'];
            if ($image_mime == 'image/jpeg' || $image_mime == 'image/gif' || $image_mime == 'image/png')
                $isimage = true;

            if ($isimage) 
                $output_arr[] = array('title' => substr($file, 5), 'value' => 'attachment.php?attachment=' . $file);
        }
    }
}

echo json_encode($output_arr);
?>
