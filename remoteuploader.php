<?php
include_once 'data.php';
include_once 'functions.php';

if (!empty($_FILES)) {

    if (isset($_GET['proxy_name']))
        $proxy_name = $_GET['proxy_name'];
    if (isset($_GET['proxy_port']))
        $proxy_port = $_GET['proxy_port'];
    if (isset($_GET['proxy_username']))
        $proxy_username = $_GET['proxy_username'];
    if (isset($_GET['proxy_password']))
        $proxy_password = $_GET['proxy_password'];
    if (!empty($_GET['user']))
        $user = $_GET['user'];
    if (!empty($_GET['userID']))
        $userID = $_GET['userID'];

    $database_pubmed = '';
    $database_nasaads = '';
    $database_crossref = '';
    $failed = '';

    if (isset($_GET['database_pubmed']))
        $database_pubmed = $_GET['database_pubmed'];
    if (isset($_GET['database_nasaads']))
        $database_nasaads = $_GET['database_nasaads'];
    if (isset($_GET['database_crossref']))
        $database_crossref = $_GET['database_crossref'];
    if (isset($_GET['failed']))
        $failed = $_GET['failed'];

    database_connect(IL_USER_DATABASE_PATH, 'users');

    save_settings($dbHandle, array(
        'batchimport_database_pubmed' => $database_pubmed,
        'batchimport_database_nasaads' => $database_nasaads,
        'batchimport_database_crossref' => $database_crossref,
        'batchimport_failed' => $failed
    ));

    $dbHandle = null;

    $user_dir = IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id();

    session_write_close();

    $order = array("\r\n", "\n", "\r");

    $i = 0;

    if (isset($_FILES['Filedata']) && is_uploaded_file($_FILES['Filedata']['tmp_name'])) {

        $file = $_FILES['Filedata']['tmp_name'];
        $orig_filename = $_FILES['Filedata']['name'];

        if (isset($_FILES['Filedata']))
            $file_extension = pathinfo($orig_filename, PATHINFO_EXTENSION);

        if (in_array($file_extension, array('doc', 'docx', 'vsd', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'))) {
            $move = move_uploaded_file($file, IL_TEMP_PATH . DIRECTORY_SEPARATOR . $orig_filename);
            if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
                putenv('HOME=' . IL_TEMP_PATH);
            exec(select_soffice() . ' --headless --convert-to pdf --outdir "' . IL_TEMP_PATH . '" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . $orig_filename . '"');
            if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
                putenv('HOME=""');
            $file = IL_TEMP_PATH . DIRECTORY_SEPARATOR . basename($orig_filename, '.' . $file_extension) . '.pdf';
        }

        $i = $i + 1;

        if (is_readable($file)) {

            $string = '';
            $xml = '';
            $record = '';
            $count = '';
            $url = array();
            $authors = '';
            $authors_array = array();
            $affiliation = '';
            $title = '';
            $abstract = '';
            $secondary_title = '';
            $tertiary_title = '';
            $year = '';
            $volume = '';
            $issue = '';
            $pages = '';
            $last_page = '';
            $journal_abbr = '';
            $keywords = '';
            $name_array = array();
            $mesh_array = array();
            $new_file = '';
            $addition_date = date('Y-m-d');
            $rating = 2;
            $uid = array();
            $editor = '';
            $reference_type = 'article';
            $publisher = '';
            $place_published = '';
            $doi = '';
            $authors_ascii = '';
            $title_ascii = '';
            $abstract_ascii = '';
            $unpacked_files = array();
            $response = array();

            if (file_exists(IL_TEMP_PATH . DIRECTORY_SEPARATOR . "librarian_temp" . $i . ".txt"))
                unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . "librarian_temp" . $i . ".txt");

            ##########	extract text from pdf	##########

            system(select_pdftotext() . ' -enc UTF-8 -f 1 -l 3 "' . $file . '" "' . IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'librarian_temp' . $i . '.txt"', $ret);

            if (file_exists(IL_TEMP_PATH . DIRECTORY_SEPARATOR . "librarian_temp" . $i . ".txt"))
                $string = file_get_contents(IL_TEMP_PATH . DIRECTORY_SEPARATOR . "librarian_temp" . $i . ".txt");

            if (empty($string)) {

                if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                    database_connect(IL_DATABASE_PATH, 'library');
                    record_unknown($dbHandle, $orig_filename, $file, $userID);

                    $put = basename($orig_filename) . ": Recorded as unknown. Full text not indexed (copying disallowed).<br>";
                } else {

                    $put = basename($orig_filename) . ": copying disallowed.<br>";
                }
            } else {

                $string = str_replace($order, ' ', $string);
                $order = array("\xe2\x80\x93", "\xe2\x80\x94");
                $replace = '-';
                $string = str_replace($order, $replace, $string);

                preg_match_all('/10\.\d{4}\/\S+/ui', $string, $doi, PREG_PATTERN_ORDER);

                if (count($doi[0]) < 1) {

                    if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                        $string = preg_replace('/(^|\s)\S{1,2}(\s|$)/u', ' ', $string);
                        $string = preg_replace('/\s{2,}/u', " ", $string);

                        database_connect(IL_DATABASE_PATH, 'library');
                        record_unknown($dbHandle, $orig_filename, $file, $userID);

                        $put = basename($orig_filename) . ": Recorded as unknown. DOI not found.<br>";
                    } else {

                        $put = basename($orig_filename) . ": DOI not found.<br>";
                    }
                } else {

                    $doi = $doi[0][0];

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

                    $title = '';

                    if (isset($_GET['database_pubmed']) && $_GET['database_pubmed'] == '1') {

                        fetch_from_pubmed($doi, '');
                        extract($response);

                        $uid = join("|", (array) $uid);
                        $url = join("|", (array) $url);
                    }

                    if (isset($_GET['database_nasaads']) && $_GET['database_nasaads'] == '1' && empty($title)) {

                        fetch_from_nasaads($doi, '');
                        extract($response);

                        $uid = join("|", (array) $uid);
                        $url = join("|", (array) $url);
                    }

                    if (isset($_GET['database_crossref']) && $_GET['database_crossref'] == '1' && empty($title)) {

                        fetch_from_crossref($doi);
                        extract($response);
                    }

                    //TRY AGAIN WITH DOI ONE CHARACTER SHORTER
                    if (empty($title)) {

                        $doi = substr($doi, 0, -1);

                        if (isset($_GET['database_pubmed']) && $_GET['database_pubmed'] == '1') {

                            fetch_from_pubmed($doi, '');
                            extract($response);

                            $uid = join("|", (array) $uid);
                            $url = join("|", (array) $url);
                        }

                        if (isset($_GET['database_nasaads']) && $_GET['database_nasaads'] == '1' && empty($title)) {

                            fetch_from_nasaads($doi, '');
                            extract($response);

                            $uid = join("|", (array) $uid);
                            $url = join("|", (array) $url);
                        }

                        if (isset($_GET['database_crossref']) && $_GET['database_crossref'] == '1' && empty($title)) {

                            fetch_from_crossref($doi);
                            extract($response);
                        }
                    }

                    if (empty($title)) {

                        if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                            $string = preg_replace('/(^|\s)\S{1,2}(\s|$)/', ' ', $string);
                            $string = preg_replace('/\s{2,}/', " ", $string);

                            database_connect(IL_DATABASE_PATH, 'library');
                            record_unknown($dbHandle, $orig_filename, $string, $file, $userID);

                            $put = " ($i) " . basename($orig_filename) . ": Recorded into category !unknown. No database record found.<br>";
                        } else {

                            $put = " ($i) " . basename($orig_filename) . ": No database record found.<br>";
                        }
                    }

                    if (!empty($title)) {

                        database_connect(IL_DATABASE_PATH, 'library');

                        if (!empty($authors))
                            $authors_ascii = utf8_deaccent($authors);

                        $title_ascii = utf8_deaccent($title);

                        if (!empty($abstract))
                            $abstract_ascii = utf8_deaccent($abstract);

                        ##########	record publication data, table library	##########

                        $query = "INSERT INTO library (file, authors, affiliation, title, journal, year, addition_date, abstract, rating, uid, volume, issue,
                            pages, secondary_title, tertiary_title, editor,
                            url, reference_type, publisher, place_published, keywords, doi, authors_ascii, title_ascii, abstract_ascii, added_by)
                            VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :affiliation,
                            :title, :journal, :year, :addition_date, :abstract, :rating, :uid, :volume, :issue, :pages, :secondary_title, :tertiary_title, :editor,
                            :url, :reference_type, :publisher, :place_published, :keywords, :doi, :authors_ascii, :title_ascii, :abstract_ascii, :added_by)";

                        $stmt = $dbHandle->prepare($query);

                        $stmt->bindParam(':authors', $authors, PDO::PARAM_STR);
                        $stmt->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
                        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                        $stmt->bindParam(':journal', $journal_abbr, PDO::PARAM_STR);
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
                        $stmt->bindParam(':added_by', $userID, PDO::PARAM_INT);

                        $dbHandle->beginTransaction();

                        $stmt->execute();
                        $stmt = null;

                        $id = $dbHandle->lastInsertId();
                        $new_file = str_pad($id, 5, "0", STR_PAD_LEFT) . '.pdf';

                        // Save citation key.
                        $stmt6 = $dbHandle->prepare("UPDATE library SET bibtex=:bibtex WHERE id=:id");

                        $stmt6->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);
                        $stmt6->bindParam(':id', $id, PDO::PARAM_INT);

                        $bibtex_author = 'unknown';

                        if (!empty($last_name[0])) {
                            $bibtex_author = utf8_deaccent($last_name[0]);
                        }

                        $bibtex_author = str_replace(' ', '', $bibtex_author);

                        empty($year) ? $bibtex_year = '0000' : $bibtex_year = substr($year, 0, 4);

                        $bibtex = $bibtex_author . '-' . $bibtex_year . '-ID' . $id;

                        $insert = $stmt6->execute();
                        $insert = null;

                        if (isset($_GET['shelf']) && !empty($userID)) {
                            $user_query = $dbHandle->quote($userID);
                            $file_query = $dbHandle->quote($id);
                            $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");
                        }

                        if (isset($_GET['project']) && !empty($_GET['projectID'])) {
                            $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_GET['projectID']) . "," . intval($id) . ")");
                        }

                        ####### record new category into categories, if not exists #########

                        if (!empty($_GET['category2'])) {

                            $category_ids = array();

                            $_GET['category2'] = preg_replace('/\s{2,}/', '', $_GET['category2']);
                            $_GET['category2'] = preg_replace('/^\s$/', '', $_GET['category2']);
                            $_GET['category2'] = array_filter($_GET['category2']);

                            $query = "INSERT INTO categories (category) VALUES (:category)";
                            $stmt = $dbHandle->prepare($query);
                            $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

                            while (list($key, $new_category) = each($_GET['category2'])) {
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
                            $stmt = null;
                        }

                        ####### record new relations into filescategories #########

                        $categories = array();

                        if (!empty($_GET['category']) || !empty($category_ids)) {
                            $categories = array_merge((array) $_GET['category'], (array) $category_ids);
                            $categories = array_filter(array_unique($categories));
                        }

                        $query = "INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES (:fileid,:categoryid)";

                        $stmt = $dbHandle->prepare($query);
                        $stmt->bindParam(':fileid', $id);
                        $stmt->bindParam(':categoryid', $category_id);

                        while (list($key, $category_id) = each($categories)) {
                            if (!empty($id))
                                $stmt->execute();
                        }
                        $stmt = null;

                        $dbHandle->commit();

                        copy($file, IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_PDF_PATH) . DIRECTORY_SEPARATOR . $new_file);

                        $hash = md5_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file);


                        //RECORD FILE HASH FOR DUPLICATE DETECTION
                        if (!empty($hash)) {
                            $hash = $dbHandle->quote($hash);
                            $dbHandle->exec('UPDATE library SET filehash=' . $hash . ' WHERE id=' . $id);
                        }

                        $dbHandle = null;

                        recordFulltext($id, $new_file);

                        $unpack_dir = IL_TEMP_PATH . DIRECTORY_SEPARATOR . $new_file;
                        mkdir($unpack_dir);
                        exec(select_pdfdetach() . ' -saveall -o "' . $unpack_dir . '" "' . IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file) . DIRECTORY_SEPARATOR . $new_file . '"');
                        $unpacked_files = scandir($unpack_dir);
                        foreach ($unpacked_files as $unpacked_file) {
                            if (is_file($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file))
                                rename($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file, IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . get_subfolder($new_file, IL_SUPPLEMENT_PATH) . DIRECTORY_SEPARATOR . sprintf("%05d", intval($new_file)) . $unpacked_file);
                        }
                        rmdir($unpack_dir);

                        $put = basename($orig_filename) . ": Recorded.<br>";
                    }
                }
            }
        } else {
            $put = basename($orig_filename) . ": Not readable.<br>";
        }
    } ####if
    ##########  ANALYZE  ##########
    if (rand(1, 1000) == 500) {
        database_connect(IL_DATABASE_PATH, 'library');
        $dbHandle->exec("ANALYZE");
        $dbHandle = null;
    }

    ###### clean the temp directory ########
    for ($j = $i; $j >= 1; $j--) {

        if (file_exists(IL_TEMP_PATH . DIRECTORY_SEPARATOR . "librarian_temp" . $j . ".txt"))
            unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . "librarian_temp" . $j . ".txt");
    }

    die($put);
}

if (isset($_SESSION['auth']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

    $proxy_name = '';
    $proxy_port = '';
    $proxy_username = '';
    $proxy_password = '';

    if (isset($_SESSION['connection']) && ($_SESSION['connection'] == "autodetect" || $_SESSION['connection'] == "url")) {
        if (!empty($_GET['proxystr'])) {
            $proxy_arr = explode(';', $_GET['proxystr']);
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

    $batchimport_database_pubmed = get_setting('batchimport_database_pubmed');
    $batchimport_database_nasaads = get_setting('batchimport_database_nasaads');
    $batchimport_database_crossref = get_setting('batchimport_database_crossref');
    $batchimport_failed = get_setting('batchimport_failed');

    ?>
    <div style="margin:4px;font-weight:bold">PDF Batch Import</div>
    <form id="batchimportform2" action="remoteuploader.php" method="GET">
        <input type="hidden" name="commence" value="1">
        <input type="hidden" name="user" value="<?php print htmlspecialchars($_SESSION['user']); ?>">
        <input type="hidden" name="userID" value="<?php print htmlspecialchars($_SESSION['user_id']); ?>">
        <input type="hidden" name="proxy_name" value="<?php print htmlspecialchars($proxy_name); ?>">
        <input type="hidden" name="proxy_port" value="<?php print htmlspecialchars($proxy_port); ?>">
        <input type="hidden" name="proxy_username" value="<?php print htmlspecialchars($proxy_username); ?>">
        <input type="hidden" name="proxy_password" value="<?php print htmlspecialchars($proxy_password); ?>">
        <table cellspacing="0" style="width: 100%" class="threed">
            <tr>
                <td valign="top" class="threedleft">
                    <div id="uploaderOverlay">
                        <button id="select-button"><i class="fa fa-folder-open"></i> Select Files</button>
                    </div>
                </td>
                <td class="threedright" style="padding-left: 18px">
                    You selected <span id="file-count">0 files</span>.
                    (Note that PDFs must contain a <a href="http://en.wikipedia.org/wiki/Digital_object_identifier" target="_blank">DOI</a> in order to track the corresponding metadata.)
                </td>
            </tr>
            <tr>
                <td valign="top" class="threedleft">
                    <button id="import-button" disabled><i class="fa fa-save"></i> Import</button>
                </td>
                <td valign="top" class="threedright">
                    <table cellspacing=0>
                        <tr>
                            <td class="select_span" style="line-height:22px;width:10em">
                                <input type="checkbox" checked class="uploadcheckbox" style="display:none" name="shelf">
                                &nbsp;<i class="fa fa-check-square"></i>
                                Add to Shelf
                            </td>
                            <td class="select_span" style="line-height:22px;width: 10em;text-align:right">
                                <input type="checkbox" class="uploadcheckbox" style="display:none" name="project">
                                <i class="fa fa-square-o"></i>
                                Add&nbsp;to&nbsp;Project&nbsp;
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
        </table>
        <table cellspacing="0" style="width: 100%" id="table1" class="threed">
            <tr><td class="threedleft">Select database:</td>
                <td class="threedright">
                    <table cellspacing="0">
    <?php if (!isset($_SESSION['remove_pubmed'])) { ?>
                            <tr>
                                <td class="select_span"><input type="checkbox" name="database_pubmed" value="1" style="display:none" <?php print (isset($batchimport_database_pubmed) && $batchimport_database_pubmed == '1') ? 'checked' : ''  ?>>
                                    &nbsp;<i class="fa fa-<?php print (isset($batchimport_database_pubmed) && $batchimport_database_pubmed == '1') ? 'check-square' : 'square-o'  ?>"></i> PubMed (biomedicine)</td>
                            </tr>
        <?php
    }
    if (!isset($_SESSION['remove_nasaads'])) {

        ?>
                            <tr>
                                <td class="select_span"><input type="checkbox" name="database_nasaads" value="1" style="display:none" <?php print (isset($batchimport_database_nasaads) && $batchimport_database_nasaads == '1') ? 'checked' : ''  ?>>
                                    &nbsp;<i class="fa fa-<?php print (isset($batchimport_database_nasaads) && $batchimport_database_nasaads == '1') ? 'check-square' : 'square-o'  ?>"></i> NASA ADS (physics, astronomy)</td>
                            </tr>
    <?php } ?>
                        <tr>
                            <td class="select_span"><input type="checkbox" name="database_crossref" value="1" style="display:none" <?php print (isset($batchimport_database_crossref) && $batchimport_database_crossref == '1') ? 'checked' : ''  ?>>
                                &nbsp;<i class="fa fa-<?php print (isset($batchimport_database_crossref) && $batchimport_database_crossref == '1') ? 'check-square' : 'square-o'  ?>"></i> CrossRef (other sciences)</td>
                        </tr>
                    </table>
                </td></tr>
            <tr><td class="threedleft">If metadata not found:</td>
                <td class="threedright" style="line-height: 16px">
                    <table cellspacing="0">
                        <tr>
                            <td class="select_span" style="line-height: 16px">
                                <input type="checkbox" name="failed" value="1" style="display: none" <?php print (isset($batchimport_failed) && $batchimport_failed == '1') ? 'checked' : ''  ?>>
                                &nbsp;<i class="fa fa-<?php print (isset($batchimport_failed) && $batchimport_failed == '1') ? 'check-square' : 'square-o'  ?>"></i>
                                Import the PDF into the category !unknown. All PDF files will be recorded and indexed!
                            </td>
                        </tr>
                    </table>
                </td>
            </tr></table>
        <table cellspacing="0" style="width:100%" id="table2" class="threed">
            <tr>
                <td class="threedleft">
                    Choose&nbsp;category:<br>
                </td>
                <td class="threedright">
                    <input type="text" id="filtercategories" value="" placeholder="Filter categories" style="width:300px;margin:0.75em 0">
                    <div class="categorydiv" style="width: 99%;overflow:scroll; height: 400px;background-color: white;color: black;border: 1px solid #C5C6C9">
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
    <br>
    <?php
}

?>