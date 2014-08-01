<?php
if(!empty($_GET['file'])) {
    
    include_once 'data.php';
    include_once '../functions.php';
    session_write_close();
    
    $file = preg_replace('/[^\d\.pdf]/', '', $_GET['file']);
    $file_name = $library_path.$file;

    if (is_readable($file_name)) {
        
        //ADD WATERMARKS
        if ($_SESSION['watermarks'] == 'nocopy') {
            $temp_file = $temp_dir.DIRECTORY_SEPARATOR.$file.'-nocopy.pdf';
            if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file_name))
                system(select_pdftk().'"'.$file_name.'" multistamp "../nocopy.pdf" output "'.$temp_file.'"', $ret);
            $file_name = $temp_file;
        } elseif ($_SESSION['watermarks'] == 'confidential') {
            $temp_file = $temp_dir.DIRECTORY_SEPARATOR.$file.'-confidential.pdf';
            if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file_name))
                system(select_pdftk().'"'.$file_name.'" multistamp "../confidential.pdf" output "'.$temp_file.'"', $ret);
            $file_name = $temp_file;
        }
        
        //RENDER FINISHED PDF
        header("Content-type: application/pdf");
        if (!isset($_GET['mode']))
            header("Content-Disposition: inline; filename=$file");
        if (isset($_GET['mode']) && $_GET['mode'] == 'download')
            header("Content-Disposition: attachment; filename=$file");
        header("Pragma: no-cache");
        header("Expires: 0");
        header('Content-Length: ' . filesize($file_name));
        ob_clean();
        flush();
        readfile($file_name);
    }
} else {
    die();
}
?>
