<?php

include_once 'data.php';

// REMOVE EMPTY USER CACHE DIRS
$clean_dirs = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_*', GLOB_NOSORT);
if (is_array($clean_dirs)) {
    foreach ($clean_dirs as $clean_dir) {
        if (is_dir($clean_dir) && is_writable($clean_dir))
            @rmdir($clean_dir);
    }
}

$i = 0;

// CLEAN GLOBAL TEMP CACHE
$clean_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);
if (is_array($clean_files)) {
    foreach ($clean_files as $clean_file) {
        if (is_file($clean_file) && is_writable($clean_file)) {
            @unlink($clean_file);
            $i++;
        }
    }
}

echo $i . ' files deleted.';
?>