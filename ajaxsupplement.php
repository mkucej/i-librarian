<?php

//AJAX backend for the supplementary file tab
include_once 'data.php';
include_once 'functions.php';

$proxy_name = '';
$proxy_port = '';
$proxy_username = '';
$proxy_password = '';

if (isset($_SESSION['connection']) && ($_SESSION['connection'] == "autodetect" || $_SESSION['connection'] == "url")) {
    if (!empty($_POST['proxystr'])) {
        $proxy_arr = explode(';', $_POST['proxystr']);
        foreach ($proxy_arr as $proxy_str) {
            if (stripos(trim($proxy_str), 'PROXY') === 0) {
                $proxy_str = trim(substr($proxy_str, 6));
                $proxy_name = parse_url($proxy_str, PHP_URL_HOST);
                $proxy_port = parse_url($proxy_str, PHP_URL_PORT);
                $proxy_username = parse_url($proxy_str, PHP_URL_USER);
                $proxy_password = parse_url($proxy_str, PHP_URL_PASS);
                break;
            }
        }
    }
} elseif (isset($_SESSION['connection']) && $_SESSION['connection'] == "proxy") {
    if (isset($_SESSION['proxy_name']))
        $proxy_name = $_SESSION['proxy_name'];
    if (isset($_SESSION['proxy_port']))
        $proxy_port = $_SESSION['proxy_port'];
    if (isset($_SESSION['proxy_username']))
        $proxy_username = $_SESSION['proxy_username'];
    if (isset($_SESSION['proxy_password']))
        $proxy_password = $_SESSION['proxy_password'];
}

session_write_close();

$error = null;
if (isset($_POST['filename']) && preg_match('/[^a-zA-Z0-9\.]/', $_POST['filename']) > 0)
    $error = 'Invalid request.';

##########	remove supplementary files	##########

if (!empty($_GET['files_to_delete'])) {

    while (list($key, $supplementary_file) = each($_GET['files_to_delete'])) {

        $supplementary_file = preg_replace('/[\/\\\]/', '_', $supplementary_file);
        if (is_file(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($supplementary_file) . DIRECTORY_SEPARATOR . $supplementary_file))
            unlink(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($supplementary_file) . DIRECTORY_SEPARATOR . $supplementary_file);
    }
}

##########	rename supplementary files	##########

if (!empty($_POST['rename']) && !empty($_POST['file'])) {

    while (list($old_name, $new_name) = each($_POST['rename'])) {

        $old_name = preg_replace('/[\/\\\]/', '_', $old_name);

        if (is_writable(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($old_name) . DIRECTORY_SEPARATOR . $old_name) && !empty($new_name)) {

            $new_name = preg_replace('/[\/\\\]/', '_', $new_name);
            $new_name = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $new_name);
            $new_name = substr($old_name, 0, 5) . $new_name;
            if ($old_name != $new_name)
                $rename = rename(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($old_name) . DIRECTORY_SEPARATOR . $old_name, IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_name) . DIRECTORY_SEPARATOR . $new_name);
        }
    }
}

##########	record supplementary files	##########

if (!empty($_FILES['form_supplementary_file']['name'])) {
    for ($i = 0; $i < count($_FILES['form_supplementary_file']['name']); $i++) {
        if (is_uploaded_file($_FILES['form_supplementary_file']['tmp_name'][$i])) {
            $new_name = preg_replace('/[\/\\\]/', '_', $_FILES['form_supplementary_file']['name'][$i]);
            $new_name = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $new_name);
            $new_name = sprintf("%05d", intval($_POST['file'])) . $new_name;

            move_uploaded_file($_FILES['form_supplementary_file']['tmp_name'][$i], IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_name, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . $new_name);
        }
    }
}


##########	record graphical abstract	##########

if (isset($_FILES['form_graphical_abstract']) && is_uploaded_file($_FILES['form_graphical_abstract']['tmp_name'])) {
    $extension = pathinfo($_FILES['form_graphical_abstract']['name'], PATHINFO_EXTENSION);
    if (empty($extension))
        $extension = 'jpg';
    $new_name = sprintf("%05d", intval($_POST['file'])) . 'graphical_abstract.' . $extension;
    move_uploaded_file($_FILES['form_graphical_abstract']['tmp_name'], IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_name, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . $new_name);
}

##########	replace PDF	##########

if (isset($_FILES['form_new_file']) && is_uploaded_file($_FILES['form_new_file']['tmp_name'])) {

    $file_extension = pathinfo($_FILES['form_new_file']['name'], PATHINFO_EXTENSION);

    if (in_array($file_extension, array('doc', 'docx', 'vsd', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'))) {
        $move = move_uploaded_file($_FILES['form_new_file']['tmp_name'], IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name']);
        if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
            putenv('HOME=' . IL_TEMP_PATH);
        exec(select_soffice() . ' --headless --convert-to pdf --outdir "' . IL_TEMP_PATH . '" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name'] . '"');
        if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
            putenv('HOME=""');
        $converted_file = IL_TEMP_PATH . DIRECTORY_SEPARATOR . basename($_FILES['form_new_file']['name'], '.' . $file_extension) . '.pdf';
        if (!is_file($converted_file)) {
            die("Error! Conversion to PDF failed.<br>" . htmlspecialchars($title));
        } else {
            copy($converted_file, IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename']);
            $supplement_filename = sprintf("%05d", intval($_POST['filename'])) . $_FILES['form_new_file']['name'];
            copy(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name'], IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($supplement_filename, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . $supplement_filename);
            unlink($converted_file);
        }
    } else {
        move_uploaded_file($_FILES['form_new_file']['tmp_name'], IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename']);
    }
}

if (!empty($_POST['form_new_file_link'])) {

    $contents = getFromWeb($_POST['form_new_file_link'], $proxy_name, $proxy_port, $proxy_username, $proxy_password);

    if (empty($contents)) {

        die('Error! I, Librarian could not find the PDF. Possible reasons:<br><br>'
                . 'You access the Web through a proxy server. Enter your proxy details'
                . ' in Tools->Settings.<br><br>The external service may be temporarily down.'
                . ' Try again later.<br><br>The link you provided is not for a PDF.');
    }

    $pdf_contents = strstr($contents, "%PDF");

    if (empty($pdf_contents)) {

        die('Error! This link does not lead to a PDF file.');
    }

    file_put_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename'], $pdf_contents);
}

if (!empty($_POST['filename']) && is_writable(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename'])) {

    $uploaded_file_content = file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename'], FILE_BINARY, null, 0, 100);

    if (stripos($uploaded_file_content, '%PDF') === 0) {
        copy(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename'], IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($_POST['filename'], IL_PDF_PATH) . DIRECTORY_SEPARATOR . $_POST['filename']);
        unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename']);
        $hash = md5_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($_POST['filename']) . DIRECTORY_SEPARATOR . $_POST['filename']);
    } else {
        $error = "This is not a PDF.";
    }

    //RECORD FILE HASH FOR DUPLICATE DETECTION
    if (!empty($hash)) {
        database_connect(IL_DATABASE_PATH, 'library');
        $hash = $dbHandle->quote($hash);
        $file = $dbHandle->quote($_POST['filename']);
        $dbHandle->exec('UPDATE library SET filehash=' . $hash . ' WHERE file=' . $file);
        $dbHandle = null;
    }

    ##########	extract text from pdf	##########

    if (!isset($error)) {

        $filename = $_POST['filename'];
        $error = recordFulltext($_POST['file'], $filename);
    }
}

// DELETE USERS' CACHED PAGES BECAUSE FILES HAVE CHANGED
$clean_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR . 'page_*', GLOB_NOSORT);
if (is_array($clean_files)) {
    foreach ($clean_files as $clean_file) {
        if (is_file($clean_file) && is_writable($clean_file))
            @unlink($clean_file);
    }
}

if (!empty($error)) {
    print "Error! " . $error;
}
