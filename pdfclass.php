<?php

class PDFViewer {

    public $file_name;
    public $pdf_path;
    public $pdf_full_path;
    public $pdf_cache_path = IL_PDF_CACHE_PATH;
    public $image_path = IL_IMAGE_PATH;
    public $database_path = IL_DATABASE_PATH;
    public $supplement_path = IL_SUPPLEMENT_PATH;
    public $temp_path = IL_TEMP_PATH;
    public $page_resolution = 192;
    public $thumb_resolution = 20;

    public function __construct($file_name) {

        $this->file_name = $file_name;

        $this->pdf_path = IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($file_name);

        if (substr($this->file_name, 0, 4) === 'lib_') {
            $this->pdf_path = $this->temp_path;
        }

        $this->pdf_full_path = $this->pdf_path . DIRECTORY_SEPARATOR . $this->file_name;

        if (!is_file($this->pdf_full_path)) {
            die('<div style="text-align:center;padding-top:270px;color:#b6b8bc;font-size:36px">No PDF</div>');
        }

    }

    public function getPageInfo() {

        $output = array();

        // Get number and sizes of all pages.
        exec(select_pdfinfo() . ' -f 1 -l 100000 "' . $this->pdf_full_path . '"', $output);

        foreach ($output as $line) {

            if (strpos($line, "Pages:") === 0) {

                $page_number = intval(substr($line, 6));
            } elseif (strpos($line, "Page") === 0 && strpos($line, "size:") !== FALSE) {

                preg_match("/(size: )(\d+\.?\d+)( x )(\d+\.?\d+)( pts)/", $line, $match);
                $page_sizes[] = array(
                    round($this->page_resolution * $match[2] / 72),
                    round($this->page_resolution * $match[4] / 72)
                );
            }
        }

        if (empty($page_number)) {
            displayError('Pdfinfo: malformed PDF.');
        }

        if ($page_number !== count($page_sizes)) {
            displayError('Pdfinfo: malformed PDF.');
        }

        return array('page_number' => $page_number, 'page_sizes' => $page_sizes);

    }

    public function getInitialPageNumber() {

        $page = 1;

        if (isset($_GET['page'])) {

            $page = intval($_GET['page']);
        } else {

            $dbHandle = database_connect($this->database_path, 'history');

            $user_id_q = $dbHandle->quote($_SESSION['user_id']);

            $file_name_q = $dbHandle->quote($this->file_name);

            $result = $dbHandle->query("SELECT page FROM bookmarks WHERE userID=$user_id_q AND file=$file_name_q");

            if (is_object($result)) {
                $page = $result->fetchColumn();
            }

            if (!$page) {
                $page = 1;
            }

            $page_info = $this->getPageInfo();

            // History can be stale.
            if ($page_info['page_number'] < $page) {
                $page = 1;
            }

            $dbHandle = null;

            return $page;
        }

    }

    public function extractImage($image, $x, $y, $w, $h) {

        if (!extension_loaded('gd')) {
            sendError('PHP GD extension is not installed.');
        }

        $src = array();

        // Get page number from image URL.
        parse_str(parse_url($image, PHP_URL_QUERY), $src);

        $page_arr = explode('.', $src['png']);

        $page = intval($page_arr[2]);

        $src_image = $this->image_path . DIRECTORY_SEPARATOR . preg_replace("/[^a-z0-9\.]/", "", $src['png']);

        if (!is_file($src_image)) {
            sendError('Invalid input. ' . __LINE__);
        }

        if ($x < 0 || $x > 10000) {
            sendError('Invalid input. ' . __LINE__);
        }

        if ($y < 0 || $y > 10000) {
            sendError('Invalid input. ' . __LINE__);
        }

        if ($w > 10000) {
            sendError('Invalid input. ' . __LINE__);
        }

        if ($h > 10000) {
            sendError('Invalid input. ' . __LINE__);
        }

        $img_array = getimagesize($src_image);

        if ($img_array['mime'] == 'image/png') {

            $img_r = imagecreatefrompng($src_image);
        } elseif ($img_array['mime'] == 'image/jpg' || $img_array['mime'] == 'image/jpeg') {

            $img_r = imagecreatefromjpeg($src_image);
        } else {

            sendError('Invalid input.');
        }

        $dst_r = imagecreatetruecolor($w, $h);

        imagecopy($dst_r, $img_r, 0, 0, $x, $y, $w, $h);

        $img_copy = imagecreatetruecolor($w, $h);
        imagecopy($img_copy, $img_r, 0, 0, $x, $y, $w, $h);
        imagetruecolortopalette($img_copy, false, 256);
        if (imagecolorstotal($img_copy) < 256) {
            imagetruecolortopalette($dst_r, false, 256);
        }

        $fileID = substr($this->file_name, 0, strpos($this->file_name, '.'));

        if (isset($_GET['mode']) && $_GET['mode'] == 'save') {

            $png_saved = imagepng($dst_r, $this->supplement_path . DIRECTORY_SEPARATOR
                    . get_subfolder($this->file_name, $this->supplement_path) . DIRECTORY_SEPARATOR
                    . $fileID . 'image-p' . $page . '-' . $x . 'x' . $y . '.png', 6);

            imagedestroy($dst_r);
            imagedestroy($img_r);

            if ($png_saved) {

                die('Image saved.');
            } else {

                sendError('Error! Image not saved');
            }
        } else {

            header('Content-type: image/png');
            header("Content-Disposition: attachment; filename=" . $fileID . "image-p" . $page . "-" . $x . "x" . $y . ".png");
            header("Pragma: no-cache");
            header("Expires: 0");
            imagepng($dst_r, null, 6);
            imagedestroy($dst_r);
            imagedestroy($img_r);
        }

    }

    public function createPageImage($page) {

        $image_full_path = $this->image_path . DIRECTORY_SEPARATOR . $this->file_name . '.' . $page . '.jpg';

        $lock_file = IL_TEMP_PATH . DIRECTORY_SEPARATOR . $this->file_name . '.' . $page . '.jpg.log';

        // Delay if another process is running on this image.
        if (is_file($lock_file)) {

            // Wait up to 10 sec.
            for ($i = 1; $i <= 40; $i++) {

                clearstatcache();

                if (is_file($lock_file)) {

                    usleep(250000);

                } else {

                    break;
                }
            }
        }

        // The image not found. Create it.
        if (!file_exists($image_full_path) || filemtime($image_full_path) < filemtime($this->pdf_full_path)) {

            // Create lock file.
            file_put_contents($lock_file, '');

            // Create images.
            exec(select_ghostscript() . " -dSAFER"
                    . " -sDEVICE=jpeg -dJPEGQ=75 -r" . $this->page_resolution
                    . " -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dDOINTERPOLATE"
                    . " -dFirstPage=$page -dLastPage=" . $page
                    . " -o \"" . $image_full_path . "\""
                    . " \"" . $this->pdf_full_path . "\"");

            // Delete lock file.
            unlink($lock_file);
        }

        // At this point, if image not found, exit with error.
        if (!file_exists($image_full_path)) {

            sendError('PDF page conversion failed.');
        }

    }

    public function createPageThumbs($page) {

        $from = 1;
        if (isset($page)) {
            $from = intval($page);
        }

        $image_full_path = $this->image_path . DIRECTORY_SEPARATOR . $this->file_name . '.t' . $from . '.jpg';

        if (!file_exists($image_full_path) || filemtime($image_full_path) < filemtime($this->pdf_full_path)) {

            // This function may run concurrently on the same PDF file. Generated JPGs must be unique.
            $unique = uniqid();

            exec(select_ghostscript() . " -dSAFER"
                    . " -sDEVICE=jpeg -dJPEGQ=75 -r" . $this->thumb_resolution
                    . " -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dDOINTERPOLATE"
                    . " -dFirstPage=$from -dLastPage=" . ($from + 9)
                    . " -o \"" . $this->image_path . DIRECTORY_SEPARATOR . $this->file_name . ".t-%d-.jpg-" . $unique . "\""
                    . " \"" . $this->pdf_full_path . "\"");

            for ($i = 1; $i <= 10; $i++) {
                rename($this->image_path . DIRECTORY_SEPARATOR . $this->file_name . ".t-$i-.jpg-" . $unique, $this->image_path . DIRECTORY_SEPARATOR . $this->file_name . ".t" . ($from + $i - 1) . ".jpg");
            }
        }

        if (!file_exists($image_full_path)) {
            sendError("PDF page previews failed.");
        }

    }

    public function extractBookmarks() {

        // Temporary XML output.
        $temp_xml = $this->temp_path . DIRECTORY_SEPARATOR . $this->file_name . '.p1';

        // Pdftohtml.
        // XML output file not found. Create one.
        if (!file_exists($temp_xml . '.xml') || filemtime($temp_xml . '.xml') < filemtime($this->pdf_full_path)) {
            system(select_pdftohtml() . ' -q -enc UTF-8 -nomerge -i -f 1 -l 1 -xml "' . $this->pdf_full_path . '" "' . $temp_xml . '"');
        }

        // Try to repair some malformed files.
        $string = file_get_contents($temp_xml . '.xml');
        // Bad UTF-8 encoding.
        $string = utf8_encode($string);
        // Remove invalid XML characters.
        $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u', ' ', $string);
        $string = preg_replace('/\s{2,}/ui', ' ', $string);
        // Remove unneeded tags. They are often malformed.
        $string = strip_tags(strstr($string, '<pdf2xml'), '<pdf2xml><page><fontspec><text><a><outline><item>');
        $string = '<?xml version="1.0" encoding="UTF-8"?> <!DOCTYPE pdf2xml SYSTEM "pdf2xml.dtd"> ' . $string;

        // Load XML file into object.
        $xml = @simplexml_load_string($string);

        if ($xml === FALSE) {
            sendError('Invalid XML encoding.');
        }

        $bookmark = array();
        $level = 1;

        // Compile output.
        if (!empty($xml->outline)) {
            $this->traverseXMLOutline($bookmark, $xml->outline, $level);
        }

        return json_encode($bookmark);

    }

    private function traverseXMLOutline(&$bookmark, $outline, &$level) {

        foreach ($outline->children() as $child) {

            if ($child->getName() === 'item' && isset($child['page'])) {

                $bookmark[] = array('title' => (string) $child, 'page' => (integer) $child['page'], 'level' => $level);
            } elseif ($child->getName() === 'outline') {

                $level++;
                $this->traverseXMLOutline($bookmark, $child, $level);
            }
        }
        $level--;

    }

    public function extractXMLText() {

        // Temporary XML output.
        $temp_xml = $this->temp_path . DIRECTORY_SEPARATOR . $this->file_name;

        // Temporary lock file.
        $temp_log = $temp_xml . '.xml.log';

        // SQLite storage.
        $temp_db = $this->pdf_cache_path . DIRECTORY_SEPARATOR . $this->file_name . '.sq3';

        // Another process is running on this file. Delay.
        if (is_file($temp_log)) {

            // Wait up to 30 sec.
            for ($i = 1; $i <= 60; $i++) {

                clearstatcache();

                if (is_file($temp_log)) {

                    usleep(500000);

                } else {

                    break;
                }
            }
        }

        // If the SQLite storage does not exist, or it is stale.
        if (!file_exists($temp_db) || filemtime($temp_db) < filemtime($this->pdf_full_path)) {

            // Create lock file.
            file_put_contents($temp_log, '');

            // Create/edit the database.
            $dbHandle = database_connect($this->pdf_cache_path, $this->file_name);

            $dbHandle->exec('PRAGMA journal_mode = DELETE');

            // Delete stale database table.
            $dbHandle->exec("DROP TABLE IF EXISTS texts");

            $dbHandle->exec("CREATE TABLE IF NOT EXISTS texts ("
                    . "id INTEGER PRIMARY KEY, "
                    . "top TEXT NOT NULL DEFAULT '', "
                    . "left TEXT NOT NULL DEFAULT '', "
                    . "height TEXT NOT NULL DEFAULT '', "
                    . "width TEXT NOT NULL DEFAULT '', "
                    . "text TEXT NOT NULL DEFAULT '', "
                    . "link TEXT NOT NULL DEFAULT '', "
                    . "page_number INTEGER NOT NULL DEFAULT '')");

            // XML output file not found. Create one.
            if (!file_exists($temp_xml . '.xml')) {
                exec(select_pdftohtml() . ' -nodrm -q -enc UTF-8 -nomerge -i -hidden -xml "' . $this->pdf_full_path . '" "' . $temp_xml . '"');
            }

            if (!file_exists($temp_xml . '.xml')) {

                // Delete lock file.
                unlink($temp_log);

                sendError('PDF to XML conversion failed.');
            }

            // Try to repair some malformed files.
            $string = file_get_contents($temp_xml . '.xml');
            // Bad UTF-8 encoding.
            $is_utf = preg_match('//u', $string);
            if (!$is_utf) {
                $string = utf8_encode($string);
            }
            // Remove invalid XML characters.
            $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]+/u', ' ', $string);
            $string = preg_replace('/\s{2,}/ui', ' ', $string);
            // Remove unneeded tags. They are often malformed.
            $string = strip_tags(strstr($string, '<pdf2xml'), '<pdf2xml><page><fontspec><text><a><outline><item>');
            $string = '<?xml version="1.0" encoding="UTF-8"?> <!DOCTYPE pdf2xml SYSTEM "pdf2xml.dtd"> ' . $string;

            // Load XML file into object.
            $xml = @simplexml_load_string($string);

            if ($xml === FALSE) {

                // Delete lock file.
                unlink($temp_log);

                sendError('Invalid XML encoding.');
            }

            // Write new data to the database.
            $dbHandle->beginTransaction();

            // Iterate XML page by page.
            foreach ($xml->page as $page) {

                // Get page number and size.
                foreach ($page->attributes() as $a => $b) {

                    if ($a == 'number') {
                        $page_number = (string) $b;
                    }

                    if ($a == 'height') {
                        $page_height = (string) $b;
                    }

                    if ($a == 'width') {
                        $page_width = (string) $b;
                    }
                }

                // Sanitize db input.
                $page_number_q = $dbHandle->quote($page_number);

                // Get info on each text element.
                $i = 0;

                foreach ($page->text as $row) {

                    // Fetch links.
                    $href = '';

                    if ($row->a) {
                        $attrs = $row->a->attributes();
                        $href = (string) $attrs['href'];
                    }

                    $row = strip_tags($row->asXML());

                    foreach ($page->text[$i]->attributes() as $a => $b) {

                        if ($a == 'top') {
                            $row_top = 100 * round($b / $page_height, 3);
                        }

                        if ($a == 'left') {
                            $row_left = 100 * round($b / $page_width, 3);
                        }

                        if ($a == 'height') {
                            $row_height = 100 * round($b / $page_height, 3);
                        }

                        if ($a == 'width') {
                            $row_width = 100 * round($b / $page_width, 3);
                        }
                    }

                    $i = $i + 1;

                    // Sanitize db input.
                    $row_top_q = $dbHandle->quote($row_top);
                    $row_left_q = $dbHandle->quote($row_left);
                    $row_height_q = $dbHandle->quote($row_height);
                    $row_width_q = $dbHandle->quote($row_width);
                    $row_q = $dbHandle->quote($row);
                    $href_q = $dbHandle->quote($href);

                    $dbHandle->exec("INSERT INTO texts (top,left,height,width,text,link,page_number) "
                            . "VALUES($row_top_q, $row_left_q, $row_height_q, $row_width_q, $row_q, $href_q, $page_number_q)");
                }
            }

            $dbHandle->commit();

            $dbHandle->exec("CREATE INDEX IF NOT EXISTS ind_pages ON texts(page_number)");

            $dbHandle = null;

            // Delete XML file.
            unlink($temp_xml . '.xml');

            // Delete lock file.
            unlink($temp_log);
        }

    }

    public function getTextLayer($from) {

        $text_hits = array();

        // Temporary SQLite storage.
        $temp_db = $this->pdf_cache_path . DIRECTORY_SEPARATOR . $this->file_name . '.sq3';

        // Make sure SQLite storage exists.
        $this->extractXMLText();

        // At this point, the database must exist.
        if (!file_exists($temp_db)) {

            sendError('Text cache not found.');
        }

        // Fetch text from the database (8 PDF pages).
        $dbHandle = database_connect($this->pdf_cache_path, $this->file_name);

        $from_q = $dbHandle->quote($from);

        $result = $dbHandle->query("SELECT top,left,height,width,text,page_number"
                . " FROM texts WHERE page_number >= $from_q AND page_number <= $from_q + 7 ORDER BY page_number ASC");

        // Compile search results.
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            extract($row);
            $text_hits[] = array(
                'p' => $page_number,
                't' => $top,
                'l' => $left,
                'h' => $height,
                'w' => $width,
                'tx' => $text
            );
        }

        // If the result set is empty, the PDF has no text layer. It is allowed.
        return json_encode($text_hits);

    }

    public function searchTextLayer($term) {

        $text_hits = array();

        // Convert wildcards.
        $term = str_replace('<?>', '_', $term);
        $term = str_replace('<*>', '%', $term);

        // Temporary SQLite storage.
        $temp_db = $this->pdf_cache_path . DIRECTORY_SEPARATOR . $this->file_name . '.sq3';

        // Make sure SQLite storage exists.
        $this->extractXMLText();

        // At this point, the database must exist.
        if (!file_exists($temp_db)) {

            sendError('Text cache not found.');
        }

        // Search text from the database.
        $dbHandle = database_connect($this->pdf_cache_path, $this->file_name);

        $term_q = $dbHandle->quote('%' . $term . '%');

        $result = $dbHandle->query("SELECT top,left,height,width,text,page_number"
                . " FROM texts WHERE text LIKE $term_q ORDER BY page_number ASC");

        // Compile search results.
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            extract($row);
            $text_hits[] = array(
                'p' => $page_number,
                't' => $top,
                'l' => $left,
                'h' => $height,
                'w' => $width,
                'tx' => $text
            );
        }

        // If the result set is empty, check if there is any text at all.
        if (empty($text_hits)) {

            $result = $dbHandle->query("SELECT count(*) FROM texts");
            $count = $result->fetchColumn();

            if ($count == 0) {
                sendError('This PDF has no searchable text.');
            }
        }

        // If the result set is empty, the PDF has no text layer. It is allowed.
        return json_encode($text_hits);

    }

    public function getLinks() {

        $text_hits = array();

        // Temporary SQLite storage.
        $temp_db = $this->pdf_cache_path . DIRECTORY_SEPARATOR . $this->file_name . '.sq3';

        // Make sure SQLite storage exists.
        $this->extractXMLText();

        // At this point, the database must exist.
        if (!file_exists($temp_db)) {

            return json_encode($text_hits);
        }

        // Search text from the database.
        $dbHandle = database_connect($this->pdf_cache_path, $this->file_name);

        $result = $dbHandle->query("SELECT top,left,height,width,link,page_number"
                . " FROM texts WHERE link!='' ORDER BY page_number ASC");

        // Compile search results.
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            extract($row);
            $text_hits[] = array(
                'p' => $page_number,
                't' => $top,
                'l' => $left,
                'h' => $height,
                'w' => $width,
                'lk' => $link
            );
        }

        // If the result set is empty, check if there is any text at all.
        if (empty($text_hits)) {

            $result = $dbHandle->query("SELECT count(*) FROM texts");
            $count = $result->fetchColumn();

            if ($count == 0) {

                return json_encode($text_hits);
            }
        }

        // If the result set is empty, the PDF has no text layer. It is allowed.
        return json_encode($text_hits);

    }

    public function deletePDFAnnotation($type, $dbids = array()) {

        $dbHandle = database_connect($this->database_path, 'library');

        $file_name_q = $dbHandle->quote($this->file_name);
        $user_id_q = $dbHandle->quote($_SESSION['user_id'], PDO::PARAM_INT);

        if (!empty($dbids)) {

            $dbids_q = array();

            foreach ($dbids as $dbid) {

                $dbids_q[] = $dbHandle->quote($dbid, PDO::PARAM_INT);
            }

            $marker_dbid_q = $annot_dbid_q = $dbid_q = implode(', ', $dbids_q);
        } else {

            $marker_dbid_q = "SELECT id FROM yellowmarkers WHERE userID=$user_id_q AND filename=$file_name_q";
            $annot_dbid_q = "SELECT id FROM annotations WHERE userID=$user_id_q AND filename=$file_name_q";
        }

        $deleted1 = $deleted2 = 0;

        if ($type === 'all' || $type === 'yellowmarker') {
            // Delete markers.
            $deleted1 = $dbHandle->exec("DELETE FROM yellowmarkers WHERE userID=$user_id_q AND id IN ($marker_dbid_q)");
        }

        if ($type === 'all' || $type === 'annotation') {
            // Delete notes.
            $deleted2 = $dbHandle->exec("DELETE FROM annotations WHERE userID=$user_id_q AND id IN ($annot_dbid_q)");
        }

        return $deleted1 + $deleted2;

    }

    public function editPDFNote($dbid, $text) {

        $dbHandle = database_connect($this->database_path, 'library');

        $user_id_q = $dbHandle->quote($_SESSION['user_id'], PDO::PARAM_INT);
        $dbid_q = $dbHandle->quote($dbid, PDO::PARAM_INT);
        $text_q = $dbHandle->quote($text);

        $updated = $dbHandle->exec("UPDATE annotations SET annotation=$text_q WHERE id=$dbid_q AND userID=$user_id_q");

        return $updated;

    }

    public function savePDFNote($page, $top, $left) {

        $dbHandle = database_connect($this->database_path, 'library');

        $user_id_q = $dbHandle->quote($_SESSION['user_id'], PDO::PARAM_INT);
        $file_name_q = $dbHandle->quote($this->file_name);
        $page_q = $dbHandle->quote($page);
        $top_q = $dbHandle->quote($top);
        $left_q = $dbHandle->quote($left);

        $dbHandle->exec("INSERT OR IGNORE INTO annotations"
                . " (userID, filename, page, top, left, annotation)"
                . " VALUES ($user_id_q, $file_name_q, $page_q, $top_q, $left_q, '')");

        $last_id = $dbHandle->lastInsertId();

        return $last_id;

    }

    public function savePDFMarkers($page, $markers) {

        $dbHandle = database_connect($this->database_path, 'library');

        $user_id_q = $dbHandle->quote($_SESSION['user_id'], PDO::PARAM_INT);
        $file_name_q = $dbHandle->quote($this->file_name);
        $page_q = $dbHandle->quote($page);

        $last_ids = array();

        $dbHandle->beginTransaction();

        foreach ($markers as $marker) {

            $top_q = $dbHandle->quote($marker['top']);
            $left_q = $dbHandle->quote($marker['left']);
            $width_q = $dbHandle->quote($marker['width']);

            $dbHandle->exec("INSERT OR IGNORE INTO yellowmarkers"
                    . " (userID, filename, page, top, left, width)"
                    . " VALUES ($user_id_q, $file_name_q, $page_q, $top_q, $left_q, $width_q)");

            $last_ids[] = array('markid' => $marker['id'], 'dbid' => $dbHandle->lastInsertId());
        }

        $dbHandle->commit();

        return json_encode($last_ids);

    }

    public function getPDFMarkers($users = null) {

        $dbHandle = database_connect($this->database_path, 'library');

        if (!isset($users)) {
            $user_id_q = $dbHandle->quote($_SESSION['user_id'], PDO::PARAM_INT);
            $user_string = "AND userID=$user_id_q";
        } elseif (isset($users) && $users === 'other') {
            $user_id_q = $dbHandle->quote($_SESSION['user_id'], PDO::PARAM_INT);
            $user_string = "AND userID!=$user_id_q";
        } elseif (isset($users) && $users === 'all') {
            $user_string = '';
        }

        $file_name_q = $dbHandle->quote($this->file_name);

        $result = $dbHandle->query("SELECT id, page, top, left, width FROM yellowmarkers"
                . " WHERE filename=$file_name_q $user_string ORDER BY CAST(page AS INTEGER) ASC");

        $output = array();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            $output[] = array_map('htmlspecialchars', $row);
        }

        return json_encode($output);

    }

    public function getPDFNotes($users = null) {

        $dbHandle = database_connect($this->database_path, 'library');

        $quoted_path = $dbHandle->quote($this->database_path . DIRECTORY_SEPARATOR . 'users.sq3');

        $dbHandle->exec("ATTACH DATABASE $quoted_path AS userdatabase");

        if (!isset($users)) {
            $user_id_q = $dbHandle->quote($_SESSION['user_id'], PDO::PARAM_INT);
            $user_string = "AND annotations.userID=$user_id_q";
        } elseif (isset($users) && $users === 'other') {
            $user_id_q = $dbHandle->quote($_SESSION['user_id'], PDO::PARAM_INT);
            $user_string = "AND annotations.userID!=$user_id_q";
        } elseif (isset($users) && $users === 'all') {
            $user_string = '';
        }

        $file_name_q = $dbHandle->quote($this->file_name);

        $result = $dbHandle->query("SELECT id, top, left, annotation, page, userdatabase.users.username AS username"
                . " FROM annotations JOIN userdatabase.users ON userdatabase.users.userID=annotations.userID"
                . " WHERE filename=$file_name_q $user_string ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");

        $output = array();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            $output[] = array_map('htmlspecialchars', $row);
        }

        $dbHandle->exec("DETACH DATABASE userdatabase");

        return json_encode($output);

    }

    public function downloadPDF($mode = null, $attachments = array()) {

        // Add watermarks.
        if (!empty($_SESSION['watermarks'])) {

            if ($_SESSION['watermarks'] == 'nocopy') {

                $watermark = 'nocopy';
                $message = str_repeat('DO NOT COPY    ', 5);
            } elseif ($_SESSION['watermarks'] == 'confidential') {

                $watermark = 'confidential';
                $message = str_repeat('CONFIDENTIAL   ', 5);
            }

            $pdfmark = '<<'
                    . ' /EndPage'
                    . ' {'
                    . ' 2 eq { pop false }'
                    . ' {'
                    . ' gsave'
                    . ' /Helvetica_Bold 30 selectfont'
                    . ' 1 0 0 setrgbcolor 10 5 moveto (' . $message . ') show'
                    . ' grestore'
                    . ' true'
                    . ' } ifelse'
                    . ' } bind'
                    . ' >> setpagedevice';

            $pdfmark_file = $this->temp_path . DIRECTORY_SEPARATOR
                    . 'lib_' . session_id() . DIRECTORY_SEPARATOR
                    . $watermark . '.ps';

            file_put_contents($pdfmark_file, $pdfmark);

            $temp_file = $this->temp_path . DIRECTORY_SEPARATOR
                    . $this->file_name . $watermark . '.pdf';

            exec(select_ghostscript() . ' -o "' . $temp_file . '" -dPDFSETTINGS=/prepress -sDEVICE=pdfwrite "' . $pdfmark_file . '" "' . $this->pdf_full_path . '"');

            $this->pdf_full_path = $temp_file;
        }

        // Attach PDF annotations.
        if (isset($attachments) && in_array('notes', $attachments)) {

            // Get page sizes.
            $pages = $this->getPageInfo();

            $page_sizes = $pages['page_sizes'];

            $dbHandle = database_connect($this->database_path, 'library');

            $user_id_q = $dbHandle->quote($_SESSION['user_id']);
            $user_name_q = $dbHandle->quote($_SESSION['user']);
            $file_name_q = $dbHandle->quote($this->file_name);

            // Attach from all users.
            if (in_array('allusers', $attachments)) {

                $quoted_path = $dbHandle->quote($this->database_path . DIRECTORY_SEPARATOR . 'users.sq3');

                $dbHandle->exec("ATTACH DATABASE $quoted_path AS userdatabase");

                $result = $dbHandle->query("SELECT id,annotation,page,top,left,userdatabase.users.username AS username FROM annotations
                                                JOIN userdatabase.users ON userdatabase.users.userID=annotations.userID
                                                WHERE filename=$file_name_q
                                                ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");

                $dbHandle->exec("DETACH DATABASE userdatabase");
            } else {

                // Attach from this user.
                $result = $dbHandle->query("SELECT id,annotation,page,top,left," . $user_name_q . " AS username FROM annotations
                                                WHERE filename=$file_name_q
                                                AND userID=$user_id_q
                                                ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");
            }

            $pdfmark = '';

            while ($annotations = $result->fetch(PDO::FETCH_NAMED)) {

                // Calculate width and height in points for each page.
                $w = round($page_sizes[$annotations['page'] + 1][0] * 72 / $this->page_resolution);
                $h = round($page_sizes[$annotations['page'] + 1][1] * 72 / $this->page_resolution);

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

            // Attach highlights.
            if (in_array('allusers', $attachments)) {

                // Attach from all users
                $quoted_path = $dbHandle->quote($this->database_path . DIRECTORY_SEPARATOR . 'users.sq3');

                $dbHandle->exec("ATTACH DATABASE $quoted_path AS userdatabase");

                $result = $dbHandle->query("SELECT id,page,top,left,width,userdatabase.users.username AS username FROM yellowmarkers
                                JOIN userdatabase.users ON userdatabase.users.userID=yellowmarkers.userID
                                WHERE filename=" . $file_name_q);

                $dbHandle->exec("DETACH DATABASE userdatabase");
            } else {

                // Attach from this user.
                $result = $dbHandle->query("SELECT id,page,top,left,width," . $user_name_q . " AS username FROM yellowmarkers
                                                WHERE filename=$file_name_q
                                                AND userID=$user_id_q");
            }

            // Compile Pdfmark for highlights.
            while ($annotations = $result->fetch(PDO::FETCH_NAMED)) {

                // Calculate width and height in points for each page.
                $w = round($page_sizes[$annotations['page'] + 1][0] * 72 / $this->page_resolution);
                $h = round($page_sizes[$annotations['page'] + 1][1] * 72 / $this->page_resolution);

                $bottomx = round($w * ($annotations['left'] / 100));
                $bottomy = round($h * (1 - $annotations['top'] / 100) - 0.012 * $h);
                $topx = round($w * ($annotations['left'] / 100) + (($annotations['width'] / 100) * $w));
                $topy = round($h * (1 - $annotations['top'] / 100));

                $pdfmark .= '[ /Subtype /Highlight
                                /Rect [ ' . $bottomx . ' ' . $bottomy . ' ' . $topx . ' ' . $topy . ' ]
                                /QuadPoints [ ' . $bottomx . ' ' . $topy . ' ' . $topx . ' ' . $topy . ' ' . $bottomx . ' ' . $bottomy . ' ' . $topx . ' ' . $bottomy . ' ]
                                /SrcPg ' . $annotations['page'] . '
                                /Color [0.78 0.8 1]
                                /Title (Highlight by ' . $annotations['username'] . ')
                                /ANN pdfmark' . PHP_EOL;
            }

            $result = null;

            // Write all annotations to the PDF.
            if (!empty($pdfmark)) {

                $pdfmark_file = $this->temp_path . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'pdfmark.txt';

                file_put_contents($pdfmark_file, $pdfmark);

                $temp_file = $this->temp_path . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $this->file_name . '-annotated.pdf';

                exec(select_ghostscript() . ' -o "' . $temp_file . '" -dPDFSETTINGS=/prepress -sDEVICE=pdfwrite "' . $this->pdf_full_path . '" "' . $pdfmark_file . '"');

                $this->pdf_full_path = $temp_file;
            }
        }

        // Attach files.
        $supfile_arr = array();

        // Attach rich-text notes.
        if (isset($attachments) && in_array('richnotes', $attachments)) {

            $dbHandle = database_connect($this->database_path, 'library');

            $user_id_q = $dbHandle->quote($_SESSION['user_id']);
            $file_name_q = $dbHandle->quote($this->file_name);

            $result = $dbHandle->query("SELECT notes FROM notes
                                            WHERE fileID=(SELECT id FROM library WHERE file=$file_name_q)
                                            AND userID=$user_id_q");

            $notetxt = $result->fetchColumn();

            if (!empty($notetxt)) {

                $notetxt = '<!DOCTYPE html><html style="width:100%;height:100%"><head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <title>I, Librarian - Notes</title></head><body>' . $notetxt . '</body></html>';

                file_put_contents($this->temp_path . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'richnotes.html', $notetxt);

                $supfile_arr[] = $this->temp_path . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'richnotes.html';
            }
        }

        // Attach supplementary files.
        if (isset($attachments) && in_array('supp', $attachments)) {

            $supfiles = array();
            $integer = substr($this->file_name, 0, strpos($this->file_name, '.'));

            $supfiles = glob($this->supplement_path . DIRECTORY_SEPARATOR . get_subfolder($integer) . DIRECTORY_SEPARATOR . $integer . '*');
            $supfile_arr = array_merge((array) $supfiles, $supfile_arr);
        }

        // Ghostscript can attach files to PDF, but let's do it with Zip.
        if (!empty($supfile_arr) && extension_loaded('zip')) {

            $temp_file = $this->temp_path . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'output.zip';

            $zip = new ZipArchive;
            $open = $zip->open($temp_file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

            // Add PDF.
            $zip->addFile($this->pdf_full_path, basename($this->file_name));

            // Add supplementary files.
            foreach ($supfile_arr as $supfile) {
                $zip->addFile($supfile, basename($supfile));
            }

            $zip->close();

            $this->pdf_full_path = $temp_file;
            $this->file_name = $this->file_name . '.zip';
        }

        ob_end_clean();

        // Output finished PDF.
        if (!empty($supfile_arr) && extension_loaded('zip')) {
            header("Content-type: application/zip");
        } else {
            header("Content-type: application/pdf");
        }

        if (isset($mode) && $mode == 'download') {
            header("Content-Disposition: attachment; filename=\"{$this->file_name}\"");
        } else {
            header("Content-Disposition: inline");
        }

        header("Pragma: no-cache");
        header("Expires: 0");
        header('Content-Length: ' . filesize($this->pdf_full_path));

        readfile($this->pdf_full_path);

    }

}
