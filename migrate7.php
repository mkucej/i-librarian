<?php

/*
 * Upgrade to 4.4 - change PDFs and supplements folder structure.
 */

ignore_user_abort(true);

echo <<<EOT
    <script type="text/javascript">
        var div = parent.document.getElementById('first-loader').childNodes[1];
        div.innerHTML = div.innerHTML + '<p style="font-size: 26px;">Please wait, upgrading&hellip;</p>';
    </script>
EOT;

include_once 'data.php';
include_once 'functions.php';

// Install every non existing table and folder, to be sure.
include 'install.php';

function createDir($dir) {

    if (!is_dir($dir)) {

        return mkdir($dir, 0755, true);
    }

    return true;
}

function getSubDir($filename) {

    $id = substr($filename, 0, 5);

    if (is_numeric($id) && strlen($id) === 5) {

        $level_1 = substr($id, 0, 1);
        $level_2 = substr($id, 1, 1);

        return $level_1 . DIRECTORY_SEPARATOR . $level_2;

    } else {

        return false;
    }
}

// PDF files.
if (is_dir(IL_PDF_PATH . DIRECTORY_SEPARATOR . '01')) {

    $files = glob(IL_PDF_PATH . DIRECTORY_SEPARATOR . '01' . DIRECTORY_SEPARATOR . '*.pdf', GLOB_NOSORT);

    foreach ($files as $file) {

        set_time_limit(30);

        $filename = basename($file);

        $sub_dir = getSubDir($filename);

        if (!empty($sub_dir) && createDir(IL_PDF_PATH . DIRECTORY_SEPARATOR . $sub_dir)) {

            copy($file, IL_PDF_PATH . DIRECTORY_SEPARATOR . $sub_dir . DIRECTORY_SEPARATOR . $filename);
            unlink($file);
        }
    }

    rmdir(IL_PDF_PATH . DIRECTORY_SEPARATOR . '01');
}

// Supplementary files.
if (is_dir(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '01')) {

    $files = glob(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '01' . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);

    foreach ($files as $file) {

        set_time_limit(30);

        $filename = basename($file);

        $sub_dir = getSubDir($filename);

        if (!empty($sub_dir) && createDir(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . $sub_dir)) {

            copy($file, IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . $sub_dir . DIRECTORY_SEPARATOR . $filename);
            unlink($file);
        }
    }

    rmdir(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '01');
}
