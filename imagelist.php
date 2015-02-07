<?php

$id = sprintf("%05d", $_GET['id']);
$output_arr = array ();
$directory = dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . "supplement";

if (is_dir($directory)) {

    $files = glob($directory . DIRECTORY_SEPARATOR . $id . '*');
    if (is_array($files)) {
        foreach ($files as $file) {

            $file = basename($file);
            $isimage = false;
            $image_array = array();
            $image_array = @getimagesize('library/supplement/' . $file);
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
