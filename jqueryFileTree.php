<?php

$_POST['dir'] = urldecode($_POST['dir']);
$_POST['dir'] = str_replace("\\", "/", $_POST['dir']);

if (file_exists($_POST['dir']) && is_readable($_POST['dir'])) {
    $files = scandir($_POST['dir']);

    if (count($files) > 2) { /* The 2 accounts for . and .. */
        echo '<ul class="jqueryFileTree" style="display: none">';
        // All dirs
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && is_dir($_POST['dir'] . $file) && $file[0] != '.') {
                echo '<li class="directory collapsed"><a href="#" rel="'
                . htmlentities($_POST['dir'] . $file) . '/"><span class="fa fa-folder" style="padding-top: 2px"></span>&nbsp;'
                . htmlentities($file) . '</a></li>';
            }
        }
        echo "</ul>";
    }
}
?>