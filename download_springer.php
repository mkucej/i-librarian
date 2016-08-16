<?php
$microtime1 = microtime(true);

include 'data.php';
include 'functions.php';

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

// reset button

if (isset($_GET['newsearch'])) {

    while (list($key, $value) = each($_SESSION)) {

        if (strstr($key, 'session_download_springer'))
            unset($_SESSION[$key]);
    }
}

// save button

if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['springer_searchname'])) {

    database_connect(IL_DATABASE_PATH, 'library');

    $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

    $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

    $stmt2 = $dbHandle->prepare("INSERT INTO searches (userID,searchname,searchfield,searchvalue) VALUES (:user,:searchname,'',:searchvalue)");

    $stmt2->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt2->bindParam(':searchname', $searchname, PDO::PARAM_STR);
    $stmt2->bindParam(':searchvalue', $save_string, PDO::PARAM_STR);

    $dbHandle->beginTransaction();

    $user = $_SESSION['user_id'];
    $searchname = "springer#" . $_GET['springer_searchname'];

    $stmt->execute();

    $user = $_SESSION['user_id'];
    $searchname = "springer#" . $_GET['springer_searchname'];

    reset($_GET);
    $save_array = $_GET;
    unset($save_array['_']);
    unset($save_array['save']);
    unset($save_array['action']);
    unset($save_array['springer_searchname']);
    $save_array = array_filter($save_array);
    $save_string = serialize($save_array);

    $stmt2->execute();

    $dbHandle->commit();
}

// load button

if (isset($_GET['load']) && $_GET['load'] == '1' && !empty($_GET['saved_search'])) {

    database_connect(IL_DATABASE_PATH, 'library');

    $stmt = $dbHandle->prepare("SELECT searchvalue FROM searches WHERE userID=:user AND searchname=:searchname");

    $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

    $user = $_SESSION['user_id'];
    $searchname = "springer#" . $_GET['saved_search'];

    $stmt->execute();

    reset($_SESSION);
    while (list($key, $value) = each($_SESSION)) {
        if (strstr($key, 'session_download_springer'))
            unset($_SESSION[$key]);
    }

    $_GET = array();

    $load_string = $stmt->fetchColumn();
    $_GET = unserialize($load_string);
    $_GET['load'] = 'Load';
    $_GET['springer_searchname'] = substr($searchname, 9);
    while (list($key, $value) = each($_GET)) {
        if (!empty($_GET[$key]))
            $_SESSION['session_download_' . $key] = $value;
    }
}

// delete button

if (isset($_GET['delete']) && $_GET['delete'] == '1' && !empty($_GET['saved_search'])) {

    database_connect(IL_DATABASE_PATH, 'library');

    $dbHandle->beginTransaction();

    $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

    $stmt->bindParam(':user', $user, PDO::PARAM_STR);
    $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

    $user = $_SESSION['user_id'];
    $searchname = "springer#" . $_GET['saved_search'];

    $stmt->execute();

    $dbHandle->commit();

    while (list($key, $value) = each($_SESSION)) {

        if (strstr($key, 'session_download_springer'))
            unset($_SESSION[$key]);
    }

    $_GET = array();
}

// search
if (!empty($_GET['action'])) {

    reset($_GET);
    while (list($key, $value) = each($_GET)) {
        if (isset($_GET[$key]))
            $_SESSION['session_download_' . $key] = $value;
    }

    if (!isset($_GET['from'])) {
        $_GET['from'] = '1';
        $from = $_GET['from'];
    } else {
        $from = intval($_GET['from']);
    }

    // PREPARE URL STRING
    $url_string = 'action=search';
    $url_array = array('springer_andwords', 'springer_phrase', 'springer_orwords', 'springer_nowords', 'springer_discipline',
        'springer_title', 'springer_author', 'springer_datemode', 'springer_startyear', 'springer_endyear', 'springer_showall');
    foreach ($url_array as $el) {
        if (isset($_GET[$el]))
            $url_string .= '&' . $el . '=' . $_GET[$el];
    }

    // PREPARE QUERY
    $query_array = array();
    $query = '';
    if (!empty($_GET['springer_andwords']))
        $query_array[] = urlencode(str_replace(' ', ' AND ', $_GET['springer_andwords']));
    if (!empty($_GET['springer_phrase']))
        $query_array[] = '"' . urlencode($_GET['springer_phrase']) . '"';
    if (!empty($_GET['springer_orwords']))
        $query_array[] = '(' . urlencode(str_replace(' ', ' OR ', $_GET['springer_orwords'])) . ')';
    if (!empty($_GET['springer_nowords']))
        $query_array[] = 'NOT (' . urlencode(str_replace(' ', ' AND ', $_GET['springer_nowords'])) . ')';

    if (count($query_array) > 0) {
        $query = join(' AND ', $query_array);
        $query = '&query=' . $query;
    }

    // ADD TITLE
    $title = '';
    if (!empty($_GET['springer_title']))
        $title = '&dc.title=' . urlencode($_GET['springer_title']);

    // ADD AUTHOR
    $author = '';
    if (!empty($_GET['springer_author']))
        $author = '&dc.creator=' . urlencode($_GET['springer_author']);

    // ADD YEARS
    $year_mode = '';
    if (!empty($_GET['springer_datemode']))
        $year_mode = '&date-facet-mode=' . $_GET['springer_datemode'];
    $year_start = '';
    if (!empty($_GET['springer_startyear']))
        $year_start = '&facet-start-year=' . $_GET['springer_startyear'];
    $year_end = '';
    if (!empty($_GET['springer_endyear']))
        $year_end = '&facet-end-year=' . $_GET['springer_endyear'];

    // ADD DISCIPLINE
    $discipline = '';
    if ($_GET['springer_discipline'] !== "0")
        $discipline = '&facet-discipline="' . urlencode($_GET['springer_discipline']) . '"';

    // SHOW ALL?
    $showall = '&showAll=true';
    if (!empty($_GET['springer_showall']))
        $showall = '&showAll=false';

    // FINAL URL
    $springer_url = 'http://link.springer.com/search/csv?sortOrder=newestFirst' . $showall . $query . $title . $author . $year_mode . $year_start . $year_end . $discipline;

    // IF NO STORAGE, SEARCH SPRINGER AND SAVE RESULTS
    if (!file_exists(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR
                    . 'springer_' . md5($springer_url) . '.sq3')) {

        $contents = getFromWeb($springer_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

        if (empty($contents)) {

            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
        }

        $csv_string = strstr($contents, "Item Title");

        $csv_string = str_replace("\n\"", "{newline}\"", $csv_string);
        $csv_array = explode("{newline}", $csv_string);

        $index = array();

        if (isset($csv_array[0]))
            unset($csv_array[0]);

        // OPEN STORAGE
        $dbHandle2 = database_connect(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id(), 'springer_' . md5($springer_url));

        $dbHandle2->exec("CREATE TABLE IF NOT EXISTS items (
                id INTEGER PRIMARY KEY,
                title TEXT NOT NULL DEFAULT '',
                secondary_title TEXT NOT NULL DEFAULT '',
                volume TEXT NOT NULL DEFAULT '',
                issue TEXT NOT NULL DEFAULT '',
                doi TEXT NOT NULL DEFAULT '',
                year TEXT NOT NULL DEFAULT '',
                reference_type TEXT NOT NULL DEFAULT ''
                )");

        $query = "INSERT INTO items (title, secondary_title, volume, issue, doi, year, reference_type)
		 VALUES (:title, :secondary_title, :volume, :issue, :doi, :year, :reference_type)";

        $stmt = $dbHandle2->prepare($query);

        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':secondary_title', $secondary_title, PDO::PARAM_STR);
        $stmt->bindParam(':volume', $volume, PDO::PARAM_STR);
        $stmt->bindParam(':issue', $issue, PDO::PARAM_STR);
        $stmt->bindParam(':doi', $doi, PDO::PARAM_STR);
        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
        $stmt->bindParam(':reference_type', $reference_type, PDO::PARAM_STR);

        $dbHandle2->beginTransaction();

        foreach ($csv_array as $csv_line) {

            $item = str_getcsv($csv_line);

            $title = $item[0];
            $secondary_title = $item[1];
            if (empty($secondary_title))
                $secondary_title = $item[2];
            $volume = $item[3];
            $issue = $item[4];
            $doi = $item[5];
            $reference_type = $item[9];
            $year = $item[7];
            if (!empty($year))
                date('Y-m-d', strtotime($year));

            $stmt->execute();
        }

        $dbHandle2->commit();
        $dbHandle2 = null;
    }

    print '<div style="padding:2px;font-weight:bold">Springer search';

    if (!empty($_SESSION['session_download_springer_searchname']))
        print ': ' . htmlspecialchars($_SESSION['session_download_springer_searchname']);

    print '</div>';

    //LOAD RESULTS AND DISPLAY
    // OPEN STORAGE
    $dbHandle2 = database_connect(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id(), 'springer_' . md5($springer_url));

    $result = $dbHandle2->query("SELECT count(*) FROM items");
    $count = $result->fetchColumn();
    $result = NULL;

    if ($count > 0) {

        $microtime2 = microtime(true);
        $microtime = $microtime2 - $microtime1;
        $microtime = sprintf("%01.1f seconds", $microtime);

        print '<table class="top" style="margin-bottom:1px"><tr><td style="width: 13em">';

        print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_springer.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                . ($from == 1 ? '' : '</a>') .
                '</div>';

        print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_springer.php?' . $url_string . '&from=' . ($from - 10)) . '" style="color:black;display:block;width:100%">') .
                '<i class="fa fa-caret-left"></i>&nbsp;Back'
                . ($from == 1 ? '' : '</a>') .
                '</div>';

        print '</td><td class="top" style="text-align: center">';

        print "Items " . $from . " - " . min($from + 9, $count) . " of " . $count . " in " . $microtime;

        print '</td><td class="top" style="width: 14em">';

        (($count % 10) == 0) ? $lastpage = $count - 9 : $lastpage = $count - ($count % 10) + 1;

        print '<div class="ui-state-default ui-corner-top' . ($count > $from + 9 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                . ($count > $from + 9 ? '<a class="navigation" href="' . htmlspecialchars('download_springer.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                . ($count > $from + 9 ? '</a>' : '') .
                '</div>';

        print '<div class="ui-state-default ui-corner-top' . ($count > $from + 9 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                . ($count > $from + 9 ? '<a class="navigation" href="' . htmlspecialchars("download_springer.php?$url_string&from=" . ($from + 10)) . '" style="color:black;display:block;width:100%">' : '') .
                '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                . ($count > $from + 9 ? '</a>' : '') .
                '</div>';

        print '<div class="ui-state-default ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px;text-align: center">PgDn</div>';

        print '</td></tr></table>';

        print '<div class="alternating_row">';

        $id_range = join(',', range($from, $from + 9));
        $result = $dbHandle2->query("SELECT * FROM items WHERE id IN (" . $id_range . ")");

        database_connect(IL_DATABASE_PATH, 'library');

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            extract($row);

            if (!empty($title)) {

                ########## gray out existing records ##############
                $existing_id = '';
                $doi_query = $dbHandle->quote($doi);
                $result_query = $dbHandle->query("SELECT id FROM library WHERE doi=" . $doi_query);
                $existing_id = $result_query->fetchColumn();

                print '<div class="items" data-doi="' . htmlspecialchars($doi)
                        . '" data-pdf="' . htmlspecialchars('http://link.springer.com/content/pdf/' . urlencode($doi) . '.pdf') . '" style="padding:0">';

                print '<div class="ui-widget-header" style="border-left:0;border-right:0">';

                print '<div class="titles brief" style="overflow:hidden;margin-right:10px';

                if (is_numeric($existing_id))
                    print ';color: #777';

                print '">' . $title . '</div>';

                print '</div>';

                print '<div class="firstcontainer items">';

                print htmlspecialchars($secondary_title);

                if ($year != '')
                    print " ($year)";

                if (!empty($doi)) {
                    print ' <br> <a href="' . htmlspecialchars("http://dx.doi.org/" . urlencode($doi)) . '" target="_blank">Publisher Website</a>
                    &middot; <a href="http://link.springer.com/content/pdf/' . urlencode($doi) . '.pdf" target="_blank">PDF</a>';
                }

                print '</div>';

                print '<div class="abstract_container" style="display:none"></div>';

                print '<div class="save_container"></div>';

                print '</div>';
            }
        }

        print '</div>';

        print '<table class="top" style="margin-top:1px"><tr><td class="top" style="width: 50%">';

        print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_springer.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                . ($from == 1 ? '' : '</a>') .
                '</div>';

        print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                . ($from == 1 ? '' : '<a class="navigation prevpage" href="' . htmlspecialchars('download_springer.php?' . $url_string . '&from=' . ($from - 10)) . '" style="color:black;display:block;width:100%">') .
                '<i class="fa fa-caret-left"></i>&nbsp;Back'
                . ($from == 1 ? '' : '</a>') .
                '</div>';

        print '</td><td class="top" style="width: 50%">';

        print '<div class="ui-state-default ui-corner-bottom' . ($count > $from + 9 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                . ($count > $from + 9 ? '<a class="navigation" href="' . htmlspecialchars('download_springer.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                . ($count > $from + 9 ? '</a>' : '') .
                '</div>';

        print '<div class="ui-state-default ui-corner-bottom' . ($count > $from + 9 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                . ($count > $from + 9 ? '<a class="navigation nextpage" href="' . htmlspecialchars("download_springer.php?$url_string&from=" . ($from + 10)) . '" style="color:black;display:block;width:100%">' : '') .
                '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                . ($count > $from + 9 ? '</a>' : '') .
                '</div>';

        print '<div class="ui-state-default ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px;text-align: center">PgUp</div>';

        print '</td></tr></table><br>';
    } else {
        print '<div style="position:relative;top:43%;left:40%;color:#bbbbbb;font-size:28px;width:200px"><b>No Items</b></div>';
    }
} else {
    ?>
    <div class="ui-state-default ui-corner-all" style="float:left;margin:4px 4px 2px 4px;padding:1px 4px">
        <a href="http://link.springer.com/" target="_blank" style="display:block"><span class="fa fa-external-link"></span> Springer</a>
    </div>
    <div style="clear:both"></div>
    <form method="GET" action="download_springer.php" id="download-form">
        <input type="hidden" name="action" value="search">
        <table class="threed" style="width:100%">
            <tr>
                <td class="threed" colspan="2">
                    <button id="download-search"><i class="fa fa-save"></i> Search</button>
                    <button id="download-reset"><i class="fa fa-reply"></i> Reset</button>
                    <button id="download-clear"><i class="fa fa-trash-o"></i> Clear</button>
                </td>
            </tr>
            <tr>
                <td class="threed" style="width:17em">
                    All of the words:
                </td>
                <td class="threed">
                    <input type="text" name="springer_andwords" value="<?php print isset($_SESSION['session_download_springer_andwords']) ? htmlspecialchars($_SESSION['session_download_springer_andwords']) : ''  ?>" style="width:99%">
                </td>
            </tr>
            <tr>
                <td class="threed">
                    The exact phrase:
                </td>
                <td class="threed">
                    <input type="text" name="springer_phrase" value="<?php print isset($_SESSION['session_download_springer_phrase']) ? htmlspecialchars($_SESSION['session_download_springer_phrase']) : ''  ?>" style="width:99%">
                </td>
            </tr>
            <tr>
                <td class="threed">
                    At least one of the words:
                </td>
                <td class="threed">
                    <input type="text" name="springer_orwords" value="<?php print isset($_SESSION['session_download_springer_orwords']) ? htmlspecialchars($_SESSION['session_download_springer_orwords']) : ''  ?>" style="width:99%">
                </td>
            </tr>
            <tr>
                <td class="threed">
                    None of these words:
                </td>
                <td class="threed">
                    <input type="text" name="springer_nowords" value="<?php print isset($_SESSION['session_download_springer_nowords']) ? htmlspecialchars($_SESSION['session_download_springer_nowords']) : ''  ?>" style="width:99%">
                </td>
            </tr>
            <tr>
                <td class="threed">
                    The title contains:
                </td>
                <td class="threed">
                    <input type="text" name="springer_title" value="<?php print isset($_SESSION['session_download_springer_title']) ? htmlspecialchars($_SESSION['session_download_springer_title']) : ''  ?>" style="width:99%">
                </td>
            </tr>
            <tr>
                <td class="threed">
                    The author / editor is:
                </td>
                <td class="threed">
                    <input type="text" name="springer_author" value="<?php print isset($_SESSION['session_download_springer_author']) ? htmlspecialchars($_SESSION['session_download_springer_author']) : ''  ?>" style="width:99%">
                </td>
            </tr>
            <tr>
                <td class="threed">
                    Show documents published
                </td>
                <td class="threed">
                    <select name="springer_datemode">
                        <option value="between" <?php print (isset($_SESSION['session_download_springer_datemode']) && $_SESSION['session_download_springer_datemode'] == 'between') ? 'selected' : ''; ?>>between</option>
                        <option value="in" <?php print (isset($_SESSION['session_download_springer_datemode']) && $_SESSION['session_download_springer_datemode'] == 'in') ? 'selected' : ''; ?>>in</option>
                    </select>
                    <input type="text" name="springer_startyear" size="4" value="<?php print isset($_SESSION['session_download_springer_startyear']) ? htmlspecialchars($_SESSION['session_download_springer_startyear']) : ''  ?>">
                    and
                    <input type="text" name="springer_endyear" size="4" value="<?php print isset($_SESSION['session_download_springer_endyear']) ? htmlspecialchars($_SESSION['session_download_springer_endyear']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threed">
                    Only documents with free PDF
                </td>
                <td class="threed">
                    <input type="checkbox" name="springer_showall" value="no" <?php print isset($_SESSION['session_download_springer_showall']) ? 'checked' : ''  ?>>
                </td>
            </tr>
            <tr>
                <td class="threed">
                    Discipline:
                </td>
                <td class="threed">
                    <div style="-moz-columns: 2;columns: 2;-webkit-columns: 2">
                        <label><input type="radio" name="springer_discipline" value="0" <?php print (!isset($_SESSION['session_download_springer_discipline']) || (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == 0)) ? 'checked' : ''  ?>>All</label><br>
                        <label><input type="radio" name="springer_discipline" value="Architecture & Design" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Architecture & Design") ? 'checked' : ''  ?>>Architecture & Design</label><br>
                        <label><input type="radio" name="springer_discipline" value="Astronomy" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Astronomy") ? 'checked' : ''  ?>>Astronomy</label><br>
                        <label><input type="radio" name="springer_discipline" value="Biomedical Sciences" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Biomedical Sciences") ? 'checked' : ''  ?>>Biomedical Sciences</label><br>
                        <label><input type="radio" name="springer_discipline" value="Business & Management" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Business & Management") ? 'checked' : ''  ?>>Business & Management</label><br>
                        <label><input type="radio" name="springer_discipline" value="Chemistry" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Chemistry") ? 'checked' : ''  ?>>Chemistry</label><br>
                        <label><input type="radio" name="springer_discipline" value="Computer Science" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Computer Science") ? 'checked' : ''  ?>>Computer Science</label><br>
                        <label><input type="radio" name="springer_discipline" value="Earth Sciences & Geography" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Earth Sciences & Geography") ? 'checked' : ''  ?>>Earth Sciences & Geography</label><br>
                        <label><input type="radio" name="springer_discipline" value="Economics" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Economics") ? 'checked' : ''  ?>>Economics</label><br>
                        <label><input type="radio" name="springer_discipline" value="Education & Language" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Education & Language") ? 'checked' : ''  ?>>Education & Language</label><br>
                        <label><input type="radio" name="springer_discipline" value="Energy" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Energy") ? 'checked' : ''  ?>>Energy</label><br>
                        <label><input type="radio" name="springer_discipline" value="Engineering" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Engineering") ? 'checked' : ''  ?>>Engineering</label><br>
                        <label><input type="radio" name="springer_discipline" value="Environmental Sciences" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Environmental Sciences") ? 'checked' : ''  ?>>Environmental Sciences</label><br>
                        <label><input type="radio" name="springer_discipline" value="Food Science & Nutrition" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Food Science & Nutrition") ? 'checked' : ''  ?>>Food Science & Nutrition</label><br>
                        <label><input type="radio" name="springer_discipline" value="Law" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Law") ? 'checked' : ''  ?>>Law</label><br>
                        <label><input type="radio" name="springer_discipline" value="Life Sciences" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Life Sciences") ? 'checked' : ''  ?>>Life Sciences</label><br>
                        <label><input type="radio" name="springer_discipline" value="Materials" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Materials") ? 'checked' : ''  ?>>Materials</label><br>
                        <label><input type="radio" name="springer_discipline" value="Mathematics" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Mathematics") ? 'checked' : ''  ?>>Mathematics</label><br>
                        <label><input type="radio" name="springer_discipline" value="Medicine" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Medicine") ? 'checked' : ''  ?>>Medicine</label><br>
                        <label><input type="radio" name="springer_discipline" value="Philosophy" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Philosophy") ? 'checked' : ''  ?>>Philosophy</label><br>
                        <label><input type="radio" name="springer_discipline" value="Physics" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Physics") ? 'checked' : ''  ?>>Physics</label><br>
                        <label><input type="radio" name="springer_discipline" value="Psychology" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Psychology") ? 'checked' : ''  ?>>Psychology</label><br>
                        <label><input type="radio" name="springer_discipline" value="Public Health" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Public Health") ? 'checked' : ''  ?>>Public Health</label><br>
                        <label><input type="radio" name="springer_discipline" value="Social Sciences" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Social Sciences") ? 'checked' : ''  ?>>Social Sciences</label><br>
                        <label><input type="radio" name="springer_discipline" value="Statistics" <?php print (isset($_SESSION['session_download_springer_discipline']) && $_SESSION['session_download_springer_discipline'] == "Statistics") ? 'checked' : ''  ?>>Statistics</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="threed">
                    Save search as:
                </td>
                <td class="threed">
                    <input type="text" name="springer_searchname" size="35" style="width:50%" value="<?php print isset($_SESSION['session_download_springer_searchname']) ? htmlspecialchars($_SESSION['session_download_springer_searchname']) : ''  ?>">
                    &nbsp;<button id="download-save"><i class="fa fa-save"></i> Save</button>
                </td>
            </tr>
        </table>
    </form>

    <?php
}
?>
