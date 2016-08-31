<?php
include_once 'data.php';

if (isset($_SESSION['auth']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

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

    $user_id = intval($_SESSION['user_id']);

    session_write_close();

    include_once 'functions.php';

    $error = array();
    $message = array();

##########	let user know when the file is bigger than the directives allow	##########

    $directives = ' Your current settings in php.ini are:';
    $directives .= '<br>upload_max_filesize: ' . ini_get('upload_max_filesize');
    $directives .= '<br>post_max_size: ' . ini_get('post_max_size');

    if (isset($_FILES['form_new_file']['error']) && $_FILES['form_new_file']['error'] > 0) {

        if ($_FILES['form_new_file']['error'] == 1)
            $error[] = "Error! The uploaded file size exceeds the <b>upload_max_filesize</b> directive in php.ini.$directives";
        if ($_FILES['form_new_file']['error'] == 3)
            $error[] = "Error! The uploaded file was only partially uploaded.";
        if ($_FILES['form_new_file']['error'] == 6)
            $error[] = "Error! Missing a temporary folder.";
        if ($_FILES['form_new_file']['error'] == 7)
            $error[] = "Error! Failed to write file to disk.";
        if ($_FILES['form_new_file']['error'] == 8)
            $error[] = "Error! File upload stopped by extension.";
    }

    if (isset($_FILES['form_supplementary_file']['error']) && $_FILES['form_supplementary_file']['error'] > 0) {

        if ($_FILES['form_supplementary_file']['error'] == 1)
            $error[] = "Error! The uploaded file size exceeds the <b>upload_max_filesize</b> directive in php.ini.$directives";
        if ($_FILES['form_supplementary_file']['error'] == 3)
            $error[] = "Error! The uploaded file was only partially uploaded.";
        if ($_FILES['form_supplementary_file']['error'] == 6)
            $error[] = "Error! Missing a temporary folder.";
        if ($_FILES['form_supplementary_file']['error'] == 7)
            $error[] = "Error! Failed to write file to disk.";
        if ($_FILES['form_supplementary_file']['error'] == 8)
            $error[] = "Error! File upload stopped by extension.";
    }

##########	clean temp directory		##########

    $temp_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_*.pdf', GLOB_NOSORT);
    if (is_array($temp_files)) {
        foreach ($temp_files as $temp_file) {
            if (time() - filemtime($temp_file) > 3600)
                @unlink($temp_file);
        }
    }
    $temp_pngs = glob(IL_IMAGE_PATH . DIRECTORY_SEPARATOR . 'lib_*.jpg', GLOB_NOSORT);
    if (is_array($temp_pngs)) {
        foreach ($temp_pngs as $temp_png) {
            if (time() - filemtime($temp_png) > 3600)
                @unlink($temp_png);
        }
    }
    if (isset($_POST['form_sent'])) {
        if (isset($_POST['uid']))
            $_POST['uid'] = array_filter($_POST['uid']);
        if (isset($_POST['url']))
            $_POST['url'] = array_filter($_POST['url']);
    }

##########	reference recording	##########

    if (!empty($_POST['title']) && isset($_POST['form_sent'])) {

        ##########	remove line breaks from certain POST values	##########

        $order = array("\r\n", "\n", "\r");
        $keys = array('affiliation', 'title', 'abstract', 'keywords');

        while (list($key, $field) = each($keys)) {
            if (!empty($_POST[$field])) {
                $_POST[$field] = str_replace($order, ' ', $_POST[$field]);
            }
        }

        ##########	record publication data, table library	##########

        database_connect(IL_DATABASE_PATH, 'library');

        $query = "INSERT INTO library (file, authors, affiliation, title, journal, year, addition_date, abstract, rating, uid, volume, issue, pages, secondary_title, tertiary_title, editor,
					url, reference_type, publisher, place_published, keywords, doi, authors_ascii, title_ascii, abstract_ascii, added_by, custom1, custom2, custom3, custom4, bibtex, bibtex_type)
		 VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :affiliation, :title, :journal, :year, :addition_date,
                 :abstract, :rating, :uid, :volume, :issue, :pages, :secondary_title, :tertiary_title, :editor,
			:url, :reference_type, :publisher, :place_published, :keywords, :doi, :authors_ascii, :title_ascii, :abstract_ascii, :added_by, :custom1, :custom2, :custom3, :custom4, :bibtex, :bibtex_type)";

        $stmt = $dbHandle->prepare($query);

        $stmt->bindParam(':authors', $authors, PDO::PARAM_STR);
        $stmt->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':journal', $journal, PDO::PARAM_STR);
        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
        $stmt->bindParam(':addition_date', $addition_date, PDO::PARAM_STR);
        $stmt->bindParam(':abstract', $abstract, PDO::PARAM_STR);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
        $stmt->bindParam(':volume', $volume, PDO::PARAM_STR);
        $stmt->bindParam(':issue', $issue, PDO::PARAM_STR);
        $stmt->bindParam(':pages', $pages, PDO::PARAM_STR);
        $stmt->bindParam(':secondary_title', $secondary_title, PDO::PARAM_STR);
        $stmt->bindParam(':tertiary_title', $tertiary_title, PDO::PARAM_STR);
        $stmt->bindParam(':editor', $editor, PDO::PARAM_STR);
        $stmt->bindParam(':url', $url, PDO::PARAM_STR);
        $stmt->bindParam(':reference_type', $reference_type, PDO::PARAM_STR);
        $stmt->bindParam(':publisher', $publisher, PDO::PARAM_STR);
        $stmt->bindParam(':place_published', $place_published, PDO::PARAM_STR);
        $stmt->bindParam(':keywords', $keywords, PDO::PARAM_STR);
        $stmt->bindParam(':doi', $doi, PDO::PARAM_STR);
        $stmt->bindParam(':authors_ascii', $authors_ascii, PDO::PARAM_STR);
        $stmt->bindParam(':title_ascii', $title_ascii, PDO::PARAM_STR);
        $stmt->bindParam(':abstract_ascii', $abstract_ascii, PDO::PARAM_STR);
        $stmt->bindParam(':added_by', $added_by, PDO::PARAM_INT);
        $stmt->bindParam(':custom1', $custom1, PDO::PARAM_STR);
        $stmt->bindParam(':custom2', $custom2, PDO::PARAM_STR);
        $stmt->bindParam(':custom3', $custom3, PDO::PARAM_STR);
        $stmt->bindParam(':custom4', $custom4, PDO::PARAM_STR);
        $stmt->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);
        $stmt->bindParam(':bibtex_type', $bibtex_type, PDO::PARAM_STR);

        if (empty($_POST['authors']) && empty($_POST['last_name'])) {

            $authors = '';
            $authors_ascii = '';
        } elseif (!empty($_POST['authors'])) {

            $authors = htmlspecialchars_decode($_POST['authors']);
            $authors_ascii = utf8_deaccent($authors);
        } elseif (!empty($_POST['last_name'])) {

            if (!empty($_POST['last_name']) && is_string($_POST['last_name'])) {
                $_POST['last_name'] = json_decode($_POST['last_name'], true);
            }
            if (!empty($_POST['first_name']) && is_string($_POST['first_name'])) {
                $_POST['first_name'] = json_decode($_POST['first_name'], true);
            }
            $names = array();
            for ($i = 0; $i < count($_POST['last_name']); $i++) {
                if (!empty($_POST['last_name'][$i])) {
                    // Get last and first name, deaccent.
                    $names[] = 'L:"' . $_POST['last_name'][$i] . '",F:"' . $_POST['first_name'][$i] . '"';
                }
            }
            $authors = join(';', $names);
            $authors_ascii = utf8_deaccent($authors);
        }

        empty($_POST['affiliation']) ? $affiliation = '' : $affiliation = $_POST['affiliation'];

        $title = $_POST['title'];
        $title_ascii = utf8_deaccent($title);

        empty($_POST['journal_abbr']) ? $journal = '' : $journal = $_POST['journal_abbr'];

        empty($_POST['secondary_title']) ? $secondary_title = '' : $secondary_title = $_POST['secondary_title'];

        empty($_POST['tertiary_title']) ? $tertiary_title = '' : $tertiary_title = $_POST['tertiary_title'];

        empty($_POST['year']) ? $year = '' : $year = $_POST['year'];

        $addition_date = date('Y-m-d');

        if (empty($_POST['abstract'])) {

            $abstract = '';
            $abstract_ascii = '';
        } else {

            $abstract = $_POST['abstract'];
            $abstract_ascii = utf8_deaccent($abstract);
        }

        empty($_POST['rating']) ? $rating = '2' : $rating = $_POST['rating'];

        empty($_POST['uid'][0]) ? $uid = '' : $uid = join('|', array_filter($_POST['uid']));

        empty($_POST['volume']) ? $volume = '' : $volume = $_POST['volume'];

        empty($_POST['issue']) ? $issue = '' : $issue = $_POST['issue'];

        empty($_POST['pages']) ? $pages = '' : $pages = $_POST['pages'];

        empty($_POST['editor']) ? $editor = '' : $editor = htmlspecialchars_decode($_POST['editor']);

        empty($_POST['url'][0]) ? $url = '' : $url = join('|', array_filter($_POST['url']));

        empty($_POST['reference_type']) ? $reference_type = 'article' : $reference_type = $_POST['reference_type'];

        empty($_POST['publisher']) ? $publisher = '' : $publisher = $_POST['publisher'];

        empty($_POST['place_published']) ? $place_published = '' : $place_published = $_POST['place_published'];

        if (empty($_POST['keywords'])) {
            $keywords = '';
        } else {
            $keywords = $_POST['keywords'];
            empty($_POST['keyword_separator']) ? $keyword_separator = '/' : $keyword_separator = $_POST['keyword_separator'];
            $keyword_array = explode($keyword_separator, $keywords);

            function trim_value(&$value) {
                $value = trim($value);

            }

            array_walk($keyword_array, 'trim_value');
            $keywords = implode(' / ', $keyword_array);
        }

        empty($_POST['doi']) ? $doi = '' : $doi = $_POST['doi'];

        empty($_POST['custom1']) ? $custom1 = '' : $custom1 = $_POST['custom1'];

        empty($_POST['custom2']) ? $custom2 = '' : $custom2 = $_POST['custom2'];

        empty($_POST['custom3']) ? $custom3 = '' : $custom3 = $_POST['custom3'];

        empty($_POST['custom4']) ? $custom4 = '' : $custom4 = $_POST['custom4'];

        empty($_POST['bibtex']) ? $bibtex = '' : $bibtex = $_POST['bibtex'];

        empty($_POST['bibtex_type']) ? $bibtex_type = '' : $bibtex_type = trim($_POST['bibtex_type']);

        $added_by = $user_id;

        ##########	get the new filename and record item	##########
        $dbHandle->beginTransaction();

        if (!empty($title))
            $insert = $stmt->execute();
        $stmt = null;

        $id = $dbHandle->lastInsertId();
        $new_file = str_pad($id, 5, "0", STR_PAD_LEFT) . '.pdf';

        // Save default citation key.
        if (empty($_POST['bibtex'])) {

            $stmt = $dbHandle->prepare("UPDATE library SET bibtex=:bibtex WHERE id=:id");

            $stmt->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            empty($_POST['last_name'][0]) ? $bibtex_author = 'unknown' : $bibtex_author = utf8_deaccent($_POST['last_name'][0]);

            $bibtex_author = str_replace(' ', '', $bibtex_author);

            empty($_POST['year']) ? $bibtex_year = '0000' : $bibtex_year = substr($_POST['year'], 0, 4);

            $bibtex = $bibtex_author . '-' . $bibtex_year . '-ID' . $id;

            $stmt->execute();
        }

        $dbHandle->commit();

        if ($insert == false)
            $error[] = htmlspecialchars('Error! The item has not been recorded.<br>' . $title);
        if ($insert == true)
            $message[] = htmlspecialchars('The item has been recorded.<br>' . $title);

        ####### record new category into categories, if not exists #########

        $category_ids = array();

        if (!empty($_POST['category2'])) {

            $_POST['category2'] = preg_replace('/\s{2,}/', '', $_POST['category2']);
            $_POST['category2'] = preg_replace('/^\s$/', '', $_POST['category2']);
            $_POST['category2'] = array_filter($_POST['category2']);

            $query = "INSERT INTO categories (category) VALUES (:category)";
            $stmt = $dbHandle->prepare($query);
            $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

            $dbHandle->beginTransaction();

            while (list($key, $new_category) = each($_POST['category2'])) {
                $new_category_quoted = $dbHandle->quote($new_category);
                $result = $dbHandle->query("SELECT categoryID FROM categories WHERE category=$new_category_quoted");
                $exists = $result->fetchColumn();
                $category_ids[] = $exists;
                $result = null;
                if (empty($exists)) {
                    $stmt->execute();
                    $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM categories");
                    $category_ids[] = $last_id->fetchColumn();
                    $last_id = null;
                }
            }

            $dbHandle->commit();
            $stmt = null;
        }

        ####### record new relations into filescategories #########

        $categories = array();

        if (!empty($_POST['category']) || !empty($category_ids)) {
            $categories = array_merge((array) $_POST['category'], (array) $category_ids);
            $categories = array_filter(array_unique($categories));
        }

        $query = "INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES (:fileid,:categoryid)";

        $stmt = $dbHandle->prepare($query);
        $stmt->bindParam(':fileid', $id);
        $stmt->bindParam(':categoryid', $category_id);

        $dbHandle->beginTransaction();
        while (list($key, $category_id) = each($categories)) {
            if (!empty($id))
                $stmt->execute();
        }
        $dbHandle->commit();
        $stmt = null;

        ##########	record publication data, table shelves	##########

        if (isset($_POST['shelf'])) {

            $user_query = $dbHandle->quote($user_id);
            $rows = $dbHandle->exec("INSERT OR IGNORE INTO shelves (fileID,userID) VALUES ($id,$user_query)");
            if ($rows == '0')
                $error[] = htmlspecialchars("Warning! The item has not been added to shelf.<br>" . $title);
            $rows = null;
        }

        ##########	record to projectsfiles	##########

        if (isset($_POST['project']) && !empty($_POST['projectID'])) {

            $rows = $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_POST['projectID']) . ",$id)");
            if ($rows == '0')
                $error[] = htmlspecialchars("Warning! The item has not been added to the project." . $title);
            $rows = null;
        }

        ##########	record to clipboard	##########

        if (isset($_POST['clipboard'])) {

            attach_clipboard($dbHandle);

            $query = "INSERT OR IGNORE INTO files (id) VALUES(:fileid)";

            $stmt = $dbHandle->prepare($query);
            $stmt->bindParam(':fileid', $id);
            $stmt->execute();
            $stmt = null;
        }

        ##########	ANALYZE	##########

        if (rand(1, 1000) == 500)
            $dbHandle->exec("ANALYZE");

        $dbHandle = null;

        ##########	record pdf	##########

        $hash = '';

        if (isset($_FILES['form_new_file']))
            $file_extension = pathinfo($_FILES['form_new_file']['name'], PATHINFO_EXTENSION);

        if (isset($_FILES['form_new_file']) && is_uploaded_file($_FILES['form_new_file']['tmp_name'])) {
            $pdf_contents = file_get_contents($_FILES['form_new_file']['tmp_name'], NULL, NULL, 0, 100);
            if (stripos($pdf_contents, '%PDF') === 0) {
                $move = move_uploaded_file($_FILES['form_new_file']['tmp_name'], IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_PDF_PATH) . DIRECTORY_SEPARATOR . $new_file);
                if ($move == false)
                    $error[] = "Error! The PDF file has not been recorded.<br>" . htmlspecialchars($title);
                if ($move == true) {
                    $message[] = htmlspecialchars("The PDF file has been recorded.<br>" . $title);
                    $hash = md5_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file);
                }
            } elseif (in_array($file_extension, array('doc', 'docx', 'vsd', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'))) {
                $move = move_uploaded_file($_FILES['form_new_file']['tmp_name'], IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name']);
                if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
                    putenv('HOME=' . IL_TEMP_PATH);
                exec(select_soffice() . ' --headless --convert-to pdf --outdir "' . IL_TEMP_PATH . '" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name'] . '"');
                if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
                    putenv('HOME=""');
                $converted_file = IL_TEMP_PATH . DIRECTORY_SEPARATOR . basename($_FILES['form_new_file']['name'], '.' . $file_extension) . '.pdf';
                if (!is_file($converted_file)) {
                    $error[] = "Error! Conversion to PDF failed.<br>" . htmlspecialchars($title);
                } else {
                    copy($converted_file, IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_PDF_PATH) . DIRECTORY_SEPARATOR . $new_file);
                    $message[] = htmlspecialchars("The PDF file has been recorded.<br>" . $title);
                    unlink($converted_file);
                }
                $supplement_filename = sprintf("%05d", intval($new_file)) . $_FILES['form_new_file']['name'];
                copy(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name'], IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . $supplement_filename);
            } else {
                $error[] = "Error! No PDF was found.<br>" . $title;
            }
        }

        if (!empty($_POST['form_new_file_link'])) {

            $contents = getFromWeb($_POST['form_new_file_link'], $proxy_name, $proxy_port, $proxy_username, $proxy_password);

            if (empty($contents)) {

                $error[] = 'Error! I, Librarian could not connect to the URL. Possible reasons:<br><br>You access the Web through a proxy server. Enter your proxy details in Tools->Settings.<br><br>The external service may be temporarily down. Try again later.';
            }

            if (stripos($contents, '%PDF') === 0) {

                $move = file_put_contents(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_PDF_PATH) . DIRECTORY_SEPARATOR . $new_file, $contents);

                if ($move == false) {

                    $error[] = htmlspecialchars("Error! The PDF file has not been recorded.<br>" . $title);
                }

                if ($move == true) {

                    $message[] = htmlspecialchars("The PDF file has been recorded.<br>" . $title);
                    $hash = md5_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file);
                }

            } else {

                $error[] = 'Error! The link you provided does not lead to a PDF.';
            }
        }

        if (isset($_POST['filename']) && is_readable(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_POST['filename'])) {

            $_POST['filename'] = str_replace(array('\\', '/'), '', $_POST['filename']);
            $copy = copy(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_POST['filename'], IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_PDF_PATH) . DIRECTORY_SEPARATOR . $new_file);
            unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $_POST['filename']);
            if ($copy == false)
                $error[] = htmlspecialchars('Error! The PDF file has not been recorded.<br>' . $title);
            if ($copy == true) {
                $message[] = htmlspecialchars("The PDF file has been recorded.<br>" . $title);
                $hash = md5_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file);
            }
        }

        //RECORD FILE HASH FOR DUPLICATE DETECTION
        if (!empty($hash)) {
            database_connect(IL_DATABASE_PATH, 'library');
            $hash = $dbHandle->quote($hash);
            $dbHandle->exec('UPDATE library SET filehash=' . $hash . ' WHERE id=' . $id);
            $dbHandle = null;
        }

        ##########	record supplementary files	##########

        if (!empty($_FILES['form_supplementary_file']['name'])) {

            for ($i = 0; $i < count($_FILES['form_supplementary_file']['name']); $i++) {

                if (is_uploaded_file($_FILES['form_supplementary_file']['tmp_name'][$i])) {

                    $supplement_filename = preg_replace('/[\/\\\]/', '_', $_FILES['form_supplementary_file']['name'][$i]);
                    $supplement_filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $supplement_filename);
                    $supplement_filename = sprintf("%05d", $new_file) . $supplement_filename;

                    $move = move_uploaded_file($_FILES['form_supplementary_file']['tmp_name'][$i], IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . $supplement_filename);
                    if ($move == false)
                        $error[] = "Error! The supplementary file " . $_FILES['form_supplementary_file']['name'][$i] . " has not been recorded.";
                    if ($move == true)
                        $message[] = "The supplementary file file " . $_FILES['form_supplementary_file']['name'][$i] . " has been recorded.";
                }
            }
        }

        if (file_exists(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file)) {
            $unpack_dir = IL_TEMP_PATH . DIRECTORY_SEPARATOR . $new_file;
            mkdir($unpack_dir);
            exec(select_pdfdetach() . ' -saveall -o "' . $unpack_dir . '" "' . IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file . '"');
            $unpacked_files = scandir($unpack_dir);
            foreach ($unpacked_files as $unpacked_file) {
                if (is_file($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file))
                    @rename($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file, IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . sprintf("%05d", intval($new_file)) . $unpacked_file);
            }
            rmdir($unpack_dir);
        }


        ##########	record graphical abstract	##########

        if (isset($_FILES['form_graphical_abstract']) && is_uploaded_file($_FILES['form_graphical_abstract']['tmp_name'])) {
            $extension = pathinfo($_FILES['form_graphical_abstract']['name'], PATHINFO_EXTENSION);
            if (empty($extension))
                $extension = 'jpg';
            $new_name = sprintf("%05d", intval($new_file)) . 'graphical_abstract.' . $extension;
            move_uploaded_file($_FILES['form_graphical_abstract']['tmp_name'], IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . $new_name);
        }

        ##########	extract text from pdf	##########

        if ((isset($copy) && $copy) || (isset($move) && $move)) {

            $error[] = recordFulltext($id, $new_file);
        }

        $_POST = array();

        // Send errors.
        $error = array_filter($error);

        if (count($error) > 0 || count($message) > 0) {
            $json = array();
            if (count($error) > 0)
                $json['error'] = $error;
            if (count($message) > 0)
                $json['message'] = $message;
            $json_output = json_encode($json);
            die($json_output);
        }

##########	no form data, only PDF, try to fetch DOI and PMID	##########
    } elseif (isset($_POST['form_sent']) && ((isset($_FILES['form_new_file']) && is_uploaded_file($_FILES['form_new_file']['tmp_name'])) || !empty($_POST['form_new_file_link']))) {

        $rand = uniqid('lib_' . mt_rand(10, 99));

        if (isset($_FILES['form_new_file']) && is_uploaded_file($_FILES['form_new_file']['tmp_name'])) {
            $pdf_contents = file_get_contents($_FILES['form_new_file']['tmp_name'], NULL, NULL, 0, 100);
            if (stripos($pdf_contents, '%PDF') === 0) {
                move_uploaded_file($_FILES['form_new_file']['tmp_name'], IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".pdf");
                $_POST['title'] = $_FILES['form_new_file']['name'];
                $_POST['tempfile'] = $rand . ".pdf";
            } else {
                $error[] = "Error! No PDF was found.";
            }
        }

        if (!empty($_POST['form_new_file_link'])) {

            $contents = getFromWeb($_POST['form_new_file_link'], $proxy_name, $proxy_port, $proxy_username, $proxy_password);

            if (empty($contents)) {

                $error[] = 'Error! I, Librarian could not connect to the URL. Possible reasons:<br><br>You access the Web through a proxy server. Enter your proxy details in Tools->Settings.<br><br>The external service may be temporarily down. Try again later.';
            }

            if (stripos($contents, '%PDF') === 0) {

                file_put_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".pdf", $contents);

                $_POST['title'] = $rand . ".pdf";
                $_POST['tempfile'] = $rand . ".pdf";

            } else {

                $error[] = 'Error! The link you provided does not lead to a PDF.';
            }
        }

        if (is_readable(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".pdf")) {

            ##########	try to find DOI on the first page	##########

            system(select_pdftotext() . ' -enc UTF-8 -l 1 "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . '.pdf" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . '.txt"', $ret);

            if (is_file(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".txt")) {

                $string = file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".txt");
                unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".txt");
            }

            if (!empty($string)) {

                $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);

                $order = array("\r\n", "\n", "\r");
                $replace = ' ';
                $string = str_replace($order, $replace, $string);

                $order = array("\xe2\x80\x93", "\xe2\x80\x94");
                $replace = '-';
                $string = str_replace($order, $replace, $string);

                preg_match('/10\.\d{4}\/\S+/ui', $string, $doi);
                $doi = current($doi);

                if (substr($doi, -1) == '.')
                    $doi = substr($doi, 0, -1);
                if (substr($doi, -1) == ',')
                    $doi = substr($doi, 0, -1);
                if (substr($doi, -1) == ';')
                    $doi = substr($doi, 0, -1);
                if (substr($doi, -1) == ')' || substr($doi, -1) == ']') {
                    preg_match_all('/(.)(doi:\s?)?(10\.\d{4}\/\S+)/ui', $string, $doi2, PREG_PATTERN_ORDER);
                    if (substr($doi, -1) == ')' && $doi2[1][0] == '(')
                        $doi = substr($doi, 0, -1);
                    if (substr($doi, -1) == ']' && $doi2[1][0] == '[')
                        $doi = substr($doi, 0, -1);
                }

                $_POST['doi'] = $doi;

                preg_match('/(?<=arXiv:)\S+/ui', $string, $arxiv_id);
                $arxiv_id = current($arxiv_id);
            }

            if (empty($doi) && empty($arxiv_id)) {

                ##########	try to find DOI in the whole PDF	##########

                system(select_pdftotext() . ' -enc UTF-8 "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . '.pdf" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . '.txt"', $ret);

                if (is_file(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".txt")) {

                    $string = file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".txt");
                    unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . $rand . ".txt");
                }

                if (!empty($string)) {

                    $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);

                    $order = array("\r\n", "\n", "\r");
                    $replace = ' ';
                    $string = str_replace($order, $replace, $string);

                    $order = array("\xe2\x80\x93", "\xe2\x80\x94");
                    $replace = '-';
                    $string = str_replace($order, $replace, $string);

                    preg_match('/10\.\d{4}\/\S+/ui', $string, $doi);
                    $doi = current($doi);

                    if (substr($doi, -1) == '.')
                        $doi = substr($doi, 0, -1);
                    if (substr($doi, -1) == ')' || substr($doi, -1) == ']') {
                        preg_match_all('/(.)(doi:\s?)?(10\.\d{4}\/\S+)/ui', $string, $doi2, PREG_PATTERN_ORDER);
                        if (substr($doi, -1) == ')' && $doi2[1][0] == '(')
                            $doi = substr($doi, 0, -1);
                        if (substr($doi, -1) == ']' && $doi2[1][0] == '[')
                            $doi = substr($doi, 0, -1);
                    }

                    $_POST['doi'] = $doi;

                    preg_match('/(?<=arXiv:)\S+/ui', $string, $arxiv_id);
                    $arxiv_id = current($arxiv_id);
                }
            }
        }
    }

##########	if user sent only DOI, or PMID, or PDF	##########

    if (isset($_POST['form_sent']) && (!empty($doi) || !empty($_POST['doi']) || !empty($_POST['uid'][0]) || !empty($arxiv_id))) {

        $pmid = '';
        $nasa_id = '';
        $isbn = '';
        $ol_id = '';
        $ieee_id = '';

        if (!empty($_POST['doi'])) {
            $doi = trim($_POST['doi']);
            if (stripos($doi, 'doi:') === 0)
                $doi = trim(substr($doi, 4));
            if (stripos($doi, 'http') === 0)
                $doi = trim(substr(parse_url($doi, PHP_URL_PATH), 1));
        } else {
            $doi = '';
        }

        foreach ($_POST['uid'] as $uid_element) {
            $uid_array2 = explode(":", $uid_element);
            $uid_array2[0] = trim(strtoupper($uid_array2[0]));
            if ($uid_array2[0] == 'PMID')
                $pmid = trim($uid_array2[1]);
            if ($uid_array2[0] == 'ARXIV')
                $arxiv_id = trim($uid_array2[1]);
            if ($uid_array2[0] == 'NASAADS')
                $nasa_id = trim($uid_array2[1]);
            if ($uid_array2[0] == 'PAT') {
                $patent_id = trim($uid_array2[1]);
                $patent_id = str_replace(array(',', ' '), '', $patent_id);
                $_POST['uid'] = $patent_id;
            }
            if ($uid_array2[0] == 'IEEE')
                $ieee_id = trim($uid_array2[1]);
            if ($uid_array2[0] == 'OL')
                $ol_id = trim($uid_array2[1]);
            if ($uid_array2[0] == 'ISBN')
                $isbn = trim($uid_array2[1]);
        }

        $response = array();

        //FETCH FROM ARXIV
        if (empty($response) && !empty($arxiv_id)) {
            fetch_from_arxiv($arxiv_id);
            $_POST = array_replace_recursive($_POST, $response);
            $_POST['form_new_file_link'] = 'http://arxiv.org/pdf/' . $arxiv_id;
        }

        //FETCH FROM PUBMED
        if (empty($response) && (!empty($pmid) || (isset($_POST['fetch-pubmed']) && !empty($doi)))) {
            fetch_from_pubmed($doi, $pmid);
            $_POST = array_replace_recursive($_POST, $response);
        }

        // FETCH FROM NASA ADS
        if (empty($response) && (!empty($nasa_id) || (isset($_POST['fetch-nasaads']) && !empty($doi) && empty($pmid)))) {
            fetch_from_nasaads($doi, $nasa_id);
            $_POST = array_replace_recursive($_POST, $response);
        }

        // FETCH FROM IEEE
        if (empty($response) && (!empty($ieee_id) || (isset($_POST['fetch-ieee']) && !empty($doi) && empty($pmid) && empty($nasa_id)))) {
            fetch_from_ieee($doi, $ieee_id);
            $_POST = array_replace_recursive($_POST, $response);
        }

        // FETCH FROM CROSSREF
        if (empty($response) && (isset($_POST['fetch-crossref']) && !empty($doi) && empty($pmid) && empty($nasa_id) && empty($ieee_id))) {
            fetch_from_crossref($doi);
            $_POST = array_replace_recursive($_POST, $response);
        }

        // FETCH FROM GOOGLE PATENTS
        if (empty($response) && !empty($patent_id)) {
            fetch_from_googlepatents($patent_id);
            $_POST = array_replace_recursive($_POST, $response);
            $_POST['reference_type'] = 'patent';
            $_POST['url'][] = 'https://www.google.com/patents/' . $patent_id;
        }

        // FETCH FROM OPEN LIBRARY
        if (empty($response) && (!empty($ol_id) || !empty($isbn))) {
            fetch_from_ol($ol_id, $isbn);
            if (isset($response['uid'])) {
                $response['uid'] = array_merge_recursive($_POST['uid'], $response['uid']);
                $response['uid'] = array_unique($response['uid']);
            }
            $_POST = array_replace_recursive($_POST, $response);
        }

        ##########	check for duplicate titles in table library	##########

        database_connect(IL_DATABASE_PATH, 'library');

        $result = null;

        if (!empty($_POST['doi'])) {
            $doi_query = $dbHandle->quote($_POST['doi']);
            $result = $dbHandle->query("SELECT id,title FROM library WHERE doi=$doi_query LIMIT 1");
            $result = $result->fetchAll(PDO::FETCH_ASSOC);
        } elseif (!empty($_POST['title'])) {
            $title_query = $dbHandle->quote($_POST['title']);
            $result = $dbHandle->query("SELECT id,title FROM library WHERE title=$title_query LIMIT 1");
            $result = $result->fetchAll(PDO::FETCH_ASSOC);
        }

        $dbHandle = null;

        if (count($result) > 0) {
            $error[] = "Warning! This article is a possible duplicate of:<br>
		<a href=\"" . htmlspecialchars("stable.php?id=" . urlencode($result[0]['id'])) . "\" style=\"color:#fff;font-weight:bold\" target=\"_blank\">" . htmlspecialchars($result[0]['title']) . "</a>";
        }
        $result = null;

        $paper_urls = array();
        if (!empty($_POST['url']) && is_string($_POST['url'])) {

            $paper_urls = explode('|', $_POST['url']);
        }

        if (!empty($_POST['form_new_file_link'])) {

            $_POST['url'][] = $_POST['form_new_file_link'];
        }

        $paper_uids = array();
        if (!empty($_POST['uid']) && is_string($_POST['uid']))
            $paper_uids = explode('|', $_POST['uid']);
    }

    if (!empty($_POST['title']) && !isset($_POST['form_sent'])) {

        ##########	check for duplicate titles in table library	##########

        database_connect(IL_DATABASE_PATH, 'library');

        $result = null;

        if (!empty($_POST['doi'])) {
            $doi_query = $dbHandle->quote($_POST['doi']);
            $result = $dbHandle->query("SELECT id,title FROM library WHERE doi=$doi_query LIMIT 1");
            $result = $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $title_query = $dbHandle->quote($_POST['title']);
            $result = $dbHandle->query("SELECT id,title FROM library WHERE title=" . $title_query . " LIMIT 1");
            $result = $result->fetchAll(PDO::FETCH_ASSOC);
        }

        $dbHandle = null;

        if (count($result) > 0) {
            $error[] = "Warning! This article is a possible duplicate of:<br>
		<a href=\"" . htmlspecialchars("stable.php?id=" . urlencode($result[0]['id'])) . "\" style=\"color:#fff;font-weight:bold\" target=\"_blank\">" . htmlspecialchars($result[0]['title']) . "</a>";
        }
        $result = null;
    }

    if (!empty($_POST['uid']) && is_array($_POST['uid'])) {
        $paper_uids = array();
        $paper_uids = $_POST['uid'];
        foreach ($paper_uids as $paper_uid) {
            $split_uid = array();
            $split_uid = explode(':', $paper_uid);
            if ($split_uid[0] == 'PMCID')
                $web_pdf = 'https://www.ncbi.nlm.nih.gov/pmc/articles/PMC' . urlencode($split_uid[1]) . '/pdf';
        }
    }
    if (!empty($_POST['url']) && is_array($_POST['url']) && empty($paper_urls)) {
        $paper_urls = $_POST['url'];
    }
    if (!empty($_POST['form_new_file_link']))
        $web_pdf = $_POST['form_new_file_link'];
    if (!empty($error)) {
        foreach ($error as $err) {
            print '<div class="upload-errors" style="display:none">' . $err . '</div>';
        }
    }

    if (!isset($_POST['form_sent']) && empty($_POST['title']) && empty($_GET['none'])) {

        ?>
        <form enctype="multipart/form-data" action="upload.php" method="POST" class="uploadform">
            <input type="hidden" name="form_sent">
            <br><br>
            <div style="width:80%;margin:auto">
                <div class="ui-state-default ui-corner-all" style="float:left;margin-bottom:4px;padding:1px 4px;cursor:auto">
                    <i class="fa fa-signin"></i>
                    Add single items using:
                </div>
            </div>
            <div style="clear: both"></div>
            <div class="item-sticker alternating_row ui-widget-content ui-corner-all" style="width:80%;margin:auto;padding:0">
                <table class="alternating_row ui-corner-all" style="width:100%;border-spacing:1.5em;margin:auto">
                    <tr>
                        <td>
                            <b>Local PDF file</b><br>
                            <input type="file" name="form_new_file" accept="application/pdf">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b>PDF from the Web</b><br>
                            <input type="text" name="form_new_file_link" value="" size="85" style="width:99%" placeholder="http://www.example.com/document.pdf">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b>Database number</b>
                            <table>
                                <tr>
                                    <td style="padding-right:0.5em">
                                        <select>
                                            <option value="">Database</option>
                                            <option value="">--</option>
                                            <option value="ARXIV">ArXiv</option>
                                            <option value="PAT">Google Patents</option>
                                            <option value="IEEE">IEEE Xplore</option>
                                            <option value="ISBN">ISBN</option>
                                            <option value="NASAADS">NASA ADS</option>
                                            <option value="OL">Open Library</option>
                                            <option value="PMID">PubMed</option>
                                        </select>
                                    </td>
                                    <td style="width:99%">
                                        <input type="text" size="80" name="uid[]" style="width:99%" value=""
                                               placeholder="123467">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b>DOI number</b><br>
                            <input type="text" size="80" name="doi" style="width:99%" value="" placeholder="10.1000/182">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <b>Fetch DOI data from</b>
                            <table>
                                <tr>
                                    <?php if (!isset($_SESSION['remove_pubmed'])) { ?>
                                        <td class="select_span" style="line-height:22px;padding-right:1em">
                                            <input type="checkbox" class="uploadcheckbox" style="display:none" name="fetch-pubmed" value="1" checked>
                                            <i class="fa fa-check-square"></i>
                                            PubMed
                                        </td>
                                        <?php
                                    }
                                    if (!isset($_SESSION['remove_nasaads'])) {

                                        ?>
                                        <td class="select_span" style="line-height:22px;padding-right:1em">
                                            <input type="checkbox" class="uploadcheckbox" style="display:none" name="fetch-nasaads" value="1" checked>
                                            <i class="fa fa-check-square"></i>
                                            NASA
                                        </td>
                                        <?php
                                    }
                                    if (!isset($_SESSION['remove_ieee'])) {

                                        ?>
                                        <td class="select_span" style="line-height:22px;padding-right:1em">
                                            <input type="checkbox" class="uploadcheckbox" style="display:none" name="fetch-ieee" value="1" checked>
                                            <i class="fa fa-check-square"></i>
                                            IEEE
                                        </td>
                                        <?php }

                                    ?>
                                    <td class="select_span" style="line-height:22px;padding-right:1em">
                                        <input type="checkbox" class="uploadcheckbox" style="display:none" name="fetch-crossref" value="1" checked>
                                        <i class="fa fa-check-square"></i>
                                        CrossRef
                                    </td>
                                </tr>
                            </table>
                            <button class="uploadsave" style="margin-top:1.5em;width:10em"><i class="fa fa-save"></i> Proceed</button>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="item-sticker alternating_row ui-widget-content ui-corner-all" style="width:80%;margin:auto;padding:0;margin-top:2em">
                <table cellspacing="0" class="alternating_row ui-corner-all" style="width:100%;border-spacing:1.5em;margin:auto">
                    <tr>
                        <td style="vertical-align: middle">
                            <b>Unpublished PDFs, office documents</b><br>
                            <button id="button-none" style="margin-top:1.5em;width:10em">Manual Upload</buttton>
                        </td>
                    </tr>
                </table>
            </div>
        </form>
        <?php
    } else {

        ?>

        <form>
            <!-- This mock form is here due to IE and/or jQuery form plug-in bug. When form is inserted after ajaxSubmit dynamically, IE erases it. -->
            <input type="submit" style="display:none">
        </form>

        <form enctype="multipart/form-data" action="upload.php" method="POST" class="uploadform">
            <input type="hidden" name="form_sent">
            <table cellspacing=0 style="width:100%;margin-top: 4px;margin-bottom: 1px;">
                <tr>
                    <td>
                        <div class="ui-state-default ui-corner-all" style="float:left;margin-left:4px;padding:1px 4px;cursor:auto">
                            <i class="fa fa-signin"></i>
                            Add New Item
                        </div>
                        <div class="ui-state-default ui-corner-top open2" style="float:right;margin-left:2px;margin-right:4px;padding:1px 4px">
                            <i class="fa fa-paperclip"></i> Supplements
                        </div>
                        <div class="ui-state-default ui-corner-top open3" style="float:right;margin-left:2px;padding:1px 4px">
                            <i class="fa fa-tags"></i> Categories
                        </div>
                        <div class="ui-state-default ui-corner-top open1 clicked" style="float:right;padding:1px 4px">
                            <i class="fa fa-file-text-o"></i> Metadata
                        </div>
                    </td>
                </tr>
            </table>

            <table cellspacing="0" style="width:100%;" class="threed">
                <tr>
                    <td class="threedleft">
                        <button class="uploadsave"><i class="fa fa-save"></i> Save</button>
                    </td>
                    <td class="threedright">
                        <table cellspacing=0>
                            <tr>
                                <td class="select_span" style="line-height:22px;width:10em">
                                    <input type="checkbox" checked class="uploadcheckbox" style="display:none" name="shelf">
                                    &nbsp;<i class="fa fa-check-square"></i>
                                    Add to Shelf
                                </td>
                                <td class="select_span" style="line-height:22px;width:11em">
                                    <input type="checkbox" class="uploadcheckbox" style="display:none" name="clipboard">
                                    <i class="fa fa-square-o"></i>
                                    Add to Clipboard
                                </td>
                                <td class="select_span" style="line-height:22px;width: 10em;text-align:right">
                                    <input type="checkbox" class="uploadcheckbox" style="display:none" name="project">
                                    <div style="float:right">Add&nbsp;to&nbsp;Project&nbsp;</div>
                                    <i class="fa fa-square-o"></i>&nbsp;
                                </td>
                                <td style="line-height:22px;width: 18em">
                                    <select name="projectID" style="width:200px">
                                        <?php
                                        database_connect(IL_DATABASE_PATH, 'library');

                                        $desktop_projects = array();
                                        $desktop_projects = read_desktop($dbHandle);

                                        foreach ($desktop_projects as $project) {
                                            print '<option value="' . $project['projectID'] . '">' . htmlspecialchars($project['project']) . '</option>' . PHP_EOL;
                                        }

                                        $dbHandle = null;

                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Paper rating:
                    </td>
                    <td class="threedright">
                        <table cellspacing=0>
                            <tr>
                                <td class="select_span" style="line-height:16px;width:6em">
                                    <input type="radio" class="uploadcheckbox" style="display:none" name="rating" value="1">
                                    &nbsp;<i class="fa fa-circle-o"></i>
                                    Low
                                </td>
                                <td class="select_span" style="line-height:16px;width:8em">
                                    <input type="radio" checked class="uploadcheckbox" style="display:none" name="rating" value="2">
                                    <i class="fa fa-circle"></i>
                                    Medium
                                </td>
                                <td class="select_span" style="line-height:16px;width:6em">
                                    <input type="radio" class="uploadcheckbox" style="display:none" name="rating" value="3">
                                    <i class="fa fa-circle-o"></i>
                                    High
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table cellspacing="0" style="width:100%" class="table1 threed">
                <tr>
                    <td class="threedleft">
                        Local file (PDF, Office):
                    </td>
                    <td class="threedright">
                        <?php
                        if (!empty($_POST['tempfile'])) {

                            print "<input type=\"hidden\" name=\"filename\" value=\"$_POST[tempfile]\">";
                            if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external')
                                print "<iframe class=\"pdf-file\" src=\"temp.php?tempfile=$_POST[tempfile]#pagemode=none&scrollbar=1&page=1&navpanes=0&toolbar=0&statusbar=0&view=FitH,20&zoom=page-width\" style=\"display:block;width:99%;height:300px;border:1px inset #afaea9\"></iframe>";
                            if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal')) {
                                print "<iframe class=\"pdf-file\" src=\"pdfviewer.php?toolbar=0&preview=1&file=$_POST[tempfile]\" style=\"display:block;width:99%;height:300px;border:1px inset #afaea9\"></iframe>";
                            }
                        } else {
                            print '<input type="file" name="form_new_file">';
                        }

                        ?>
                    </td>
                </tr>
                <?php
                if (empty($_POST['tempfile'])) {

                    ?>
                    <tr>
                        <td class="threedleft">
                            PDF from the Web:
                        </td>
                        <td class="threedright">
                            <input type="text" name="form_new_file_link" value="<?php if (!empty($web_pdf)) print $web_pdf; ?>" size="85" style="width:99%">
                        </td>
                    </tr>
                    <?php
                }

                ?>
                <?php
                if (!empty($paper_uids)) {
                    foreach ($paper_uids as $paper_uid) {

                        ?>
                        <tr>
                            <td class="threedleft">
                                Database UID:
                            </td>
                            <td class="threedright">
                                <input type="text" size="80" name="uid[]" style="width: 99%" value="<?php print htmlspecialchars($paper_uid) ?>">
                            </td>
                        </tr>
                        <?php
                    }
                }

                ?>
                <tr>
                    <td class="threedleft">
                        Database UID:
                        <i class="fa fa-plus-circle adduidrow" style="float:right;cursor:pointer"></i>
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="uid[]" style="width:99%" value=""
                               title="<b>Examples:</b><br>PMID:123456<br>PMCID:123456<br>NASAADS:123456<br>ARXIV:123456">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        DOI:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="doi" style="width:99%" value="<?php print isset($_POST['doi']) ? htmlspecialchars($_POST['doi']) : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        BibTex key:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="bibtex" style="width:99%" value="<?php print isset($_POST['bibtex']) ? htmlspecialchars($_POST['bibtex']) : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Publication type:
                    </td>
                    <td class="threedright">
                        <select name="reference_type">
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'article') ? 'selected' : ''  ?>>article</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'book') ? 'selected' : ''  ?>>book</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'chapter') ? 'selected' : ''  ?>>chapter</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'conference') ? 'selected' : ''  ?>>conference</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'manual') ? 'selected' : ''  ?>>manual</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'thesis') ? 'selected' : ''  ?>>thesis</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'patent') ? 'selected' : ''  ?>>patent</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'report') ? 'selected' : ''  ?>>report</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'electronic') ? 'selected' : ''  ?>>electronic</option>
                            <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'unpublished') ? 'selected' : ''  ?>>unpublished</option>
                        </select>
                        &nbsp;Bibtex type:
                        <select name="bibtex_type">
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'article') ? 'selected' : ''  ?>>article</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'book') ? 'selected' : ''  ?>>book</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'booklet') ? 'selected' : ''  ?>>booklet</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'conference') ? 'selected' : ''  ?>>conference</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'inbook') ? 'selected' : ''  ?>>inbook</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'incollection') ? 'selected' : ''  ?>>incollection</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'inproceedings') ? 'selected' : ''  ?>>inproceedings</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'manual') ? 'selected' : ''  ?>>manual</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'mastersthesis') ? 'selected' : ''  ?>>mastersthesis</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'misc') ? 'selected' : ''  ?>>misc</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'phdthesis') ? 'selected' : ''  ?>>phdthesis</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'proceedings') ? 'selected' : ''  ?>>proceedings</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'techreport') ? 'selected' : ''  ?>>techreport</option>
                            <option <?php print (!empty($_POST['bibtex_type']) && $_POST['bibtex_type'] == 'unpublished') ? 'selected' : ''  ?>>unpublished</option>

                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="threedleft td-title ui-state-error-text">
                        <?php
                        if (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'book') {
                            echo 'Book Title:';
                        } else {
                            echo 'Title:';
                        }

                        ?>
                    </td>
                    <td class="threedright">
                        <textarea name="title" title="Article title, chapter title, or book title." cols=80 rows=2 wrap="soft" style="width:99%"><?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Authors:
                    </td>
                    <td class="threedright">
                        <div class="author-inputs" style="max-height: 200px;overflow:auto">
                            <?php
                            if (empty($_POST['last_name'])) {

                                ?>
                                <div>
                                    Last name: <input type="text" name="last_name[]" value="">
                                    First name: <input type="text" name="first_name[]" value="">
                                </div>
                                <div>
                                    Last name: <input type="text" name="last_name[]" value="">
                                    First name: <input type="text" name="first_name[]" value="">
                                </div>
                                <div>
                                    Last name: <input type="text" name="last_name[]" value="">
                                    First name: <input type="text" name="first_name[]" value="">
                                </div>
                                <div>
                                    Last name: <input type="text" name="last_name[]" value="">
                                    First name: <input type="text" name="first_name[]" value="">
                                </div>
                                <div>
                                    Last name: <input type="text" name="last_name[]" value="">
                                    First name: <input type="text" name="first_name[]" value="">
                                    <i class="fa fa-plus-circle addauthorrow" style="cursor:pointer"></i>
                                </div>
                                <?php
                            } else {
                                if (is_string($_POST['last_name']))
                                    $_POST['last_name'] = json_decode($_POST['last_name'], true);
                                if (is_string($_POST['first_name']))
                                    $_POST['first_name'] = json_decode($_POST['first_name'], true);
                                for ($i = 0; $i < count($_POST['last_name']); $i++) {
                                    $last = htmlspecialchars($_POST['last_name'][$i]);
                                    $first = htmlspecialchars($_POST['first_name'][$i]);
                                    if (!empty($last))
                                        print '<div>Last name: <input class="author-last" type="text" name="last_name[]" value="' . $last . '">'
                                                . ' First name: <input class="author-first" name="first_name[]" type="text" value="' . $first . '"></div>';
                                }
                                print '<div>
                            Last name: <input type="text" value="">
                            First name: <input type="text" value="">
                            <i class="fa fa-plus-circle addauthorrow" style="cursor:pointer"></i>
                            </div>';
                            }

                            ?>
                        </div>
                        <input type="hidden" name="authors" value="<?php echo isset($_POST['authors']) ? htmlspecialchars($_POST['authors']) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft td-affiliation">
                        <?php
                        if (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'patent') {
                            echo "Assignee:";
                        } else {
                            echo "Affiliation:";
                        }

                        ?>
                    </td>
                    <td class="threedright">
                        <textarea cols="80" rows="2" name="affiliation" style="width:99%"><?php echo isset($_POST['affiliation']) ? htmlspecialchars($_POST['affiliation']) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Journal abbreviation:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="journal_abbr" style="width:99%" value="<?php print isset($_POST['journal_abbr']) ? htmlspecialchars($_POST['journal_abbr']) : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft td-secondary-title">
                        <?php
                        if (empty($_POST['reference_type']))
                            $_POST['reference_type'] = 'article';
                        if (!empty($_POST['reference_type'])) {
                            switch ($_POST['reference_type']) {
                                case 'article':
                                    echo "Full journal name:";
                                    break;
                                case 'chapter':
                                    echo "Book Title:";
                                    break;
                                case 'book':
                                    echo "Series Title:";
                                    break;
                                case 'thesis':
                                    echo "School:";
                                    break;
                                case 'conference':
                                    echo "Conference:";
                                    break;
                                case 'patent':
                                    echo "Source:";
                                    break;
                                default:
                                    echo "Secondary Title:";
                            }
                        } else {
                            echo "Secondary Title:";
                        }

                        ?>
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="secondary_title" title="This can be journal full name or book title." style="width:99%" value="<?php print isset($_POST['secondary_title']) ? htmlspecialchars($_POST['secondary_title']) : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft td-tertiary-title">
                        <?php
                        if (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'chapter') {
                            echo "Series Title:";
                        } else {
                            echo "Tertiary Title:";
                        }

                        ?>
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="tertiary_title" title="This can be a collection title." style="width:99%" value="<?php print isset($_POST['tertiary_title']) ? htmlspecialchars($_POST['tertiary_title']) : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Publication date:
                    </td>
                    <td class="threedright">
                        <input type="text" size="10" maxlength="10" name="year" value="<?php echo isset($_POST['year']) ? htmlspecialchars($_POST['year']) : '' ?>"> YYYY-MM-DD
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Volume:
                    </td>
                    <td class="threedright">
                        <input type="text" size="10" name="volume" value="<?php echo isset($_POST['volume']) ? htmlspecialchars($_POST['volume']) : '' ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Issue:
                    </td>
                    <td class="threedright">
                        <input type="text" size="10" name="issue" value="<?php echo isset($_POST['issue']) ? htmlspecialchars($_POST['issue']) : '' ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Pages:
                    </td>
                    <td class="threedright">
                        <input type="text" size="10" name="pages" value="<?php echo isset($_POST['pages']) ? htmlspecialchars($_POST['pages']) : '' ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Abstract:
                    </td>
                    <td class="threedright">
                        <textarea name="abstract" cols=80 rows=5 wrap="soft" style="width:99%"><?php echo isset($_POST["abstract"]) ? htmlspecialchars($_POST['abstract']) : '' ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Editors:
                    </td>
                    <td class="threedright">
                        <div class="editor-inputs" style="max-height: 200px;overflow:auto">
                            <?php
                            if (empty($_POST['editor'])) {

                                ?>
                                <div>
                                    Last name: <input type="text" value="">
                                    First name: <input type="text" value="">
                                </div>
                                <div>
                                    Last name: <input type="text" value="">
                                    First name: <input type="text" value="">
                                </div>
                                <div>
                                    Last name: <input type="text" value="">
                                    First name: <input type="text" value="">
                                    <i class="fa fa-plus-circle addauthorrow" style="cursor:pointer"></i>
                                </div>
                                <?php
                            } else {
                                $array = array();
                                $new_authors = array();
                                $array = explode(';', $_POST['editor']);
                                $array = array_filter($array);
                                if (!empty($array)) {
                                    foreach ($array as $author) {
                                        $array2 = explode(',', $author);
                                        $last = trim($array2[0]);
                                        $last = substr($array2[0], 3, -1);
                                        $first = '';
                                        if (isset($array2[1])) {
                                            $first = trim($array2[1]);
                                            $first = substr($array2[1], 3, -1);
                                        }
                                        if (!empty($last))
                                            print '<div>Last name: <input class="author-last" type="text" value="' . $last . '"> First name: <input class="author-first" type="text" value="' . $first . '"></div>';
                                    }
                                }
                                print '<div>
                            Last name: <input type="text" value="">
                            First name: <input type="text" value="">
                            <i class="fa fa-plus-circle addauthorrow" style="cursor:pointer"></i>
                            </div>';
                            }

                            ?>
                        </div>
                        <input type="hidden" name="editor" value="<?php echo isset($_POST['editor']) ? htmlspecialchars($_POST['editor']) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Publisher:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="publisher" style="width:99%" value="<?php echo isset($_POST['publisher']) ? htmlspecialchars($_POST['publisher']) : '' ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Place published:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="place_published" style="width:99%" value="<?php echo isset($_POST['place_published']) ? htmlspecialchars($_POST['place_published']) : '' ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Keywords:
                    </td>
                    <td class="threedright">
                        <textarea name="keywords" cols=80 rows=2 wrap="soft" style="width:99%" title="Reserved for keywords provided by internet databases. For your custom keywords use Categories.<br>Separator: space, forward slash, space &quot; / &quot;"><?php echo!empty($_POST["keywords"]) ? htmlspecialchars($_POST['keywords']) : '' ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Keyword separator:
                    </td>
                    <td class="threedright">
                        <select name="keyword_separator">
                            <option> / </option>
                            <option>;</option>
                            <option>,</option>
                        </select>
                    </td>
                </tr>
                <?php
                if (!empty($paper_urls)) {
                    foreach ($paper_urls as $paper_url) {

                        ?>
                        <tr>
                            <td class="threedleft">
                                URL:
                            </td>
                            <td class="threedright">
                                <input type="text" size="80" name="url[]" style="width: 99%" value="<?php print htmlspecialchars($paper_url) ?>">
                            </td>
                        </tr>
                        <?php
                    }
                }

                ?>
                <tr>
                    <td class="threedleft">
                        URL:
                        <i class="fa fa-plus-circle addurlrow" style="float:right;cursor:pointer"></i>
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="url[]" style="width:99%" value="">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
        <?php print (!empty($_SESSION['custom1'])) ? $_SESSION['custom1'] : 'Custom 1'  ?>:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="custom1" style="width: 99%" value="<?php print isset($_POST['custom1']) ? htmlspecialchars($_POST['custom1']) : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
        <?php print (!empty($_SESSION['custom2'])) ? $_SESSION['custom2'] : 'Custom 2'  ?>:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="custom2" style="width: 99%" value="<?php print isset($_POST['custom2']) ? htmlspecialchars($_POST['custom2']) : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
        <?php print (!empty($_SESSION['custom3'])) ? $_SESSION['custom3'] : 'Custom 3'  ?>:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="custom3" style="width: 99%" value="<?php print isset($_POST['custom3']) ? htmlspecialchars($_POST['custom3']) : ''  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
        <?php print (!empty($_SESSION['custom4'])) ? $_SESSION['custom4'] : 'Custom 4'  ?>:
                    </td>
                    <td class="threedright">
                        <input type="text" size="80" name="custom4" style="width: 99%" value="<?php print isset($_POST['custom4']) ? htmlspecialchars($_POST['custom4']) : ''  ?>">
                    </td>
                </tr>
            </table>
            <table cellspacing="0" style="width:100%;display:none" class="table2 threed">
                <tr>
                    <td class="threedleft">
                        Graphical abstract:
                    </td>
                    <td class="threedright">
                        <input type="file" name="form_graphical_abstract">
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Supplementary files:
                    </td>
                    <td class="threedright">
                        <input type="file" name="form_supplementary_file[]" multiple>
                    </td>
                </tr>
            </table>
            <table cellspacing="0" style="width:100%;display:none" class="table3 threed">
                <tr>
                    <td class="threedleft">
                        Choose&nbsp;category:<br>
                    </td>
                    <td class="threedright">
                        <input type="text" id="filtercategories" value="" placeholder="Filter categories" style="width:300px;margin:0.75em 0">
                        <div class="categorydiv" style="width: 99%;overflow:scroll; height: 400px;background-color: white;color: black;border: 1px solid rgba(0,0,0,0.15)">
                            <table cellspacing=0 style="float:left;width: 49%">
                                <?php
                                $category_string = null;
                                database_connect(IL_DATABASE_PATH, 'library');
                                $result = $dbHandle->query("SELECT count(*) FROM categories");
                                $totalcount = $result->fetchColumn();
                                $result = null;

                                $i = 1;
                                $isdiv = null;
                                $result = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
                                while ($category = $result->fetch(PDO::FETCH_ASSOC)) {
                                    $cat_all[$category['categoryID']] = $category['category'];
                                    if ($i > (1 + $totalcount / 2) && !$isdiv) {
                                        print '</table><table cellspacing=0 style="width: 49%;float: right;padding:2px">';
                                        $isdiv = true;
                                    }
                                    print PHP_EOL . '<tr><td class="select_span">';
                                    print "<input type=\"checkbox\" name=\"category[]\" value=\"" . htmlspecialchars($category['categoryID']) . "\"";
                                    print " style=\"display:none\">&nbsp;<i class=\"fa fa-square-o\"></i> " . htmlspecialchars($category['category']) . "</td></tr>";
                                    $i = $i + 1;
                                }
                                $result = null;
                                $dbHandle = null;

                                ?>
                            </table>
                        </div>
                        <div class="suggestions" style="width: 574px">
                            <?php
                            if (!empty($_POST['title']) && !empty($_POST['abstract']) ) {
                                $cat_all = array_unique($cat_all);
                                while (list($key, $value) = each($cat_all)) {
                                    if (stristr("$_POST[title] $_POST[abstract]", $value))
                                        $suggested_categories[] = "<span style=\"cursor: pointer\">$value</span>";
                                }
                                if (!empty($suggested_categories))
                                    print 'Suggestions: ' . implode(", ", $suggested_categories);
                            }

                            ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="threedleft">
                        Add to new categories:
                    </td>
                    <td class="threedright">
                        <input type="text" size="30" name="category2[]" value=""><br>
                        <input type="text" size="30" name="category2[]" value=""><br>
                        <input type="text" size="30" name="category2[]" value=""><br>
                        <input type="text" size="30" name="category2[]" value=""><br>
                        <input type="text" size="30" name="category2[]" value="">
                    </td>
                </tr>
            </table>
        </form>
        <br><br>
        <?php
    }
} else {
    print 'Super User or User permissions required.';
}

?>
