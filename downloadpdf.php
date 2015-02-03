<?php

//PDF EXPORT IN BUILT-IN PDF VIEWER
if (!empty($_GET['file'])) {

    include_once 'data.php';
    include_once 'functions.php';
    session_write_close();

    $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';
    $file = preg_replace('/[^\d\.pdf]/', '', $_GET['file']);
    $file_name = $path . DIRECTORY_SEPARATOR . $file;

    if (is_readable($file_name)) {

        // get page size
        exec(select_pdfinfo() . '"' . $file_name . '"', $output);
        foreach ($output as $row) {
            if (strpos($row, 'Page size: ') === 0) {
                $row = str_replace('Page size: ', '', $row);
                $row = str_replace(' pts', '', $row);
                $arr = explode(' x ', $row);
                $w = round($arr[0]);
                $h = round($arr[1]);
                break;
            }
        }

        //ADD WATERMARKS
        if ($_SESSION['watermarks'] == 'nocopy') {
            $temp_file = $temp_dir . DIRECTORY_SEPARATOR . $file . '-nocopy.pdf';
            if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file_name))
                system(select_pdftk() . '"' . $file_name . '" multistamp "' . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'nocopy.pdf' . '"  output "' . $temp_file . '"', $ret);
            $file_name = $temp_file;
        } elseif ($_SESSION['watermarks'] == 'confidential') {
            $temp_file = $temp_dir . DIRECTORY_SEPARATOR . $file . '-confidential.pdf';
            if (!file_exists($temp_file) || filemtime($temp_file) < filemtime($file_name))
                system(select_pdftk() . '"' . $file_name . '" multistamp "' . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'confidential.pdf' . '"  output "' . $temp_file . '"', $ret);
            $file_name = $temp_file;
        }

        //ATTACH FILES
        if (isset($_GET['attachments'])) {
            $supfile_arr = array();

            //ATTACH PDF NOTES
            if (in_array('notes', $_GET['attachments'])) {
                database_connect($database_path, 'library');
                $userid = $dbHandle->quote($_SESSION['user_id']);
                $username = $dbHandle->quote($_SESSION['user']);
                $qfile = $dbHandle->quote($_GET['file']);

                //ATTACH PDF NOTES FROM USERS
                if (in_array('allusers', $_GET['attachments'])) {

                    $quoted_path = $dbHandle->quote($usersdatabase_path . 'users.sq3');

                    $dbHandle->exec("ATTACH DATABASE $quoted_path AS userdatabase");

                    $result = $dbHandle->query("SELECT id,annotation,page,top,left,userdatabase.users.username AS username FROM annotations
                                                JOIN userdatabase.users ON userdatabase.users.userID=annotations.userID
                                                WHERE filename=$qfile
                                                ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");

                    $dbHandle->exec("DETACH DATABASE userdatabase");

                    //ATTACH PDF NOTES FROM THIS USER
                } else {
                    $result = $dbHandle->query("SELECT id,annotation,page,top,left," . $username . " AS username FROM annotations
                                                WHERE filename=$qfile
                                                AND userID=$userid
                                                ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");
                }
                $notetxt = '';
                $pdfmark = '';
                while ($annotations = $result->fetch(PDO::FETCH_NAMED)) {
                    $notetxt = $notetxt . 'Page ' . $annotations['page'] . ', note ' . $annotations['id'] . PHP_EOL . PHP_EOL . $annotations['annotation'] . PHP_EOL . PHP_EOL;
                    $bottomx = round($w * ($annotations['left'] / 100));
                    $bottomy = round($h * (1 - $annotations['top'] / 100) - 20);
                    $annotation = strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $annotations['annotation'])));
                    $pdfmark .= '[ /Contents <FEFF' . $annotation . '>
                                /Rect [' . $bottomx . ' ' . $bottomy . ' ' . (20 + $bottomx) . ' ' . (20 + $bottomy) . ']
                                /Subtype /Text
                                /Name /Comment
                                /SrcPg ' . $annotations['page'] . '
                                /Open false
                                /Title (Comment #' . $annotations['id'] . ' by ' . $annotations['username'] . ')
                                /Color [0.6 0.65 0.9]
                                /ANN pdfmark' . PHP_EOL;
                }
                $result = null;
                //ATTACH HIGHLIGHTS FROM ALL USERS
                if (in_array('allusers', $_GET['attachments'])) {
                    $result = $dbHandle->query("SELECT id,page,top,left,width FROM yellowmarkers
                                                WHERE filename=" . $qfile);

                    //ATTACH HIGHLIGHTS FROM THIS USER
                } else {
                    $result = $dbHandle->query("SELECT id,page,top,left,width FROM yellowmarkers
                                                WHERE filename=$qfile
                                                AND userID=$userid");
                }
                while ($annotations = $result->fetch(PDO::FETCH_NAMED)) {
                    $bottomx = round($w * ($annotations['left'] / 100));
                    $bottomy = round($h * (1 - $annotations['top'] / 100) - 0.012 * $h);
                    $topx = round($w * ($annotations['left'] / 100) + (($annotations['width'] / 100) * $w));
                    $topy = round($h * (1 - $annotations['top'] / 100));
                    $pdfmark .= '[ /Subtype /Highlight
                                /Rect [ ' . $bottomx . ' ' . $bottomy . ' ' . $topx . ' ' . $topy . ' ]
                                /QuadPoints [ ' . $bottomx . ' ' . $topy . ' ' . $topx . ' ' . $topy . ' ' . $bottomx . ' ' . $bottomy . ' ' . $topx . ' ' . $bottomy . ' ] 
                                /SrcPg ' . $annotations['page'] . '
                                /Color [0.78 0.8 1]
                                /ANN pdfmark' . PHP_EOL;
                }
                $result = null;
                if (!empty($notetxt)) {
                    file_put_contents($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'annotations.txt', $notetxt);
                    $supfile_arr[] = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'annotations.txt';
                }
                if (!empty($pdfmark)) {
                    file_put_contents($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'pdfmark.txt', $pdfmark);
                    $temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $file . '-annotated.pdf';
                    exec(select_ghostscript() . ' -o "' . $temp_file . '" -dPDFSETTINGS=/prepress -sDEVICE=pdfwrite "' . $file_name . '" "' . $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'pdfmark.txt"', $out);
                    $file_name = $temp_file;
                }
            }

            //ATTACH RICH-TEXT NOTES
            if (in_array('richnotes', $_GET['attachments'])) {
                database_connect($database_path, 'library');
                $userid = $dbHandle->quote($_SESSION['user_id']);
                $qfile = $dbHandle->quote($_GET['file']);
                $result = $dbHandle->query("SELECT notes FROM notes
                                            WHERE fileID=(SELECT id FROM library WHERE file=$qfile)
                                            AND userID=$userid LIMIT 1");
                $notetxt = '';
                $notetxt = $result->fetchColumn();
                if (!empty($notetxt)) {
                    $notetxt = '<!DOCTYPE html><html style="width:100%;height:100%"><head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <title>I, Librarian - 2.4 Notes</title></head><body>' . $notetxt . '</body></html>';
                    file_put_contents($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'richnotes.html', $notetxt);
                    $supfile_arr[] = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'richnotes.html';
                }
            }

            //ATTACH SUPPLEMENTARY FILES
            if (in_array('supp', $_GET['attachments'])) {
                $supfiles = array();
                $integer = sprintf("%05d", intval($_GET['file']));
                $supfiles = glob($path . DIRECTORY_SEPARATOR . 'supplement' . DIRECTORY_SEPARATOR . $integer . '*');
                $supfile_arr = array_merge((array) $supfiles, $supfile_arr);
            }
            $supfile_str = join('" "', $supfile_arr);
            $supfile_str = trim($supfile_str);
            if (!empty($supfile_str)) {
                $temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $file . '-attachments.pdf';
                system(select_pdftk() . '"' . $file_name . '" attach_files "' . $supfile_str . '" output "' . $temp_file . '"', $ret);
                $file_name = $temp_file;
            }
        }

        // CUSTOM NAME
//        database_connect($database_path, 'library');
//        $qfile = $dbHandle->quote($_GET['file']);
//        $result = $dbHandle->query("SELECT title FROM library where file=" . $qfile);
//        $data = $result->fetch(PDO::FETCH_NAMED);
//        $file = str_replace(' ', '_', substr($data['title'],0,35)) . '.pdf';
        //RENDER FINISHED PDF
        header("Content-type: application/pdf");
        if (!isset($_GET['mode']))
            header("Content-Disposition: inline; filename=\"$file\"");
        if (isset($_GET['mode']) && $_GET['mode'] == 'download')
            header("Content-Disposition: attachment; filename=\"$file\"");
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
