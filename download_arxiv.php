<?php
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

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

########## register empty checkboxes ##############

    $checkboxes = array('physics', 'cs', 'math', 'nlin', 'q-bio', 'q-fin', 'stat',
        'physics_all', 'physics_astro-ph', 'physics_cond-mat', 'physics_gr-qc',
        'physics_hep-ex', 'physics_hep-lat', 'physics_hep-ph', 'physics_hep-th',
        'physics_math-ph', 'physics_nucl-ex', 'physics_nucl-th', 'physics_physics', 'physics_quant-ph');

    while (list($key, $value) = each($checkboxes)) {

        if (isset($_GET['arxiv_last_search']) && !isset($_GET['arxiv_category_' . $value]))
            $_GET['arxiv_category_' . $value] = '';
    }

########## reset button ##############

    if (isset($_GET['newsearch'])) {

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_arxiv'))
                unset($_SESSION[$key]);
        }
    }

########## save button ##############

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['arxiv_searchname'])) {

        database_connect(IL_DATABASE_PATH, 'library');

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $stmt2 = $dbHandle->prepare("INSERT INTO searches (userID,searchname,searchfield,searchvalue) VALUES (:user,:searchname,:searchfield,:searchvalue)");

        $stmt2->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt2->bindParam(':searchname', $searchname, PDO::PARAM_STR);
        $stmt2->bindParam(':searchfield', $searchfield, PDO::PARAM_STR);
        $stmt2->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

        $dbHandle->beginTransaction();

        $user = $_SESSION['user_id'];
        $searchname = "arxiv#" . $_GET['arxiv_searchname'];

        $stmt->execute();

        reset($_GET);

        while (list($key, $value) = each($_GET)) {

            if (!empty($key) && strstr($key, "arxiv_") && $key != 'arxiv_last_search') {

                $user = $_SESSION['user_id'];
                $searchname = "arxiv#" . $_GET['arxiv_searchname'];

                if ($key != "arxiv_searchname") {

                    $searchfield = $key;
                    $searchvalue = $value;
                    $stmt2->execute();
                }
            }
        }

        $user = $_SESSION['user_id'];
        $searchname = "arxiv#" . $_GET['arxiv_searchname'];
        $searchfield = 'arxiv_last_search';
        $searchvalue = '1';

        $stmt2->execute();

        $dbHandle->commit();
    }

########## load button ##############

    if (isset($_GET['load']) && $_GET['load'] == '1' && !empty($_GET['saved_search'])) {

        database_connect(IL_DATABASE_PATH, 'library');

        $stmt = $dbHandle->prepare("SELECT searchfield,searchvalue FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "arxiv#" . $_GET['saved_search'];

        $stmt->execute();

        reset($_SESSION);

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_arxiv'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
        $_GET['load'] = 'Load';

        $_GET['arxiv_searchname'] = substr($searchname, 6);

        while ($search = $stmt->fetch(PDO::FETCH_BOTH)) {
            $_GET{$search['searchfield']} = $search['searchvalue'];
        }
    }

########## delete button ##############

    if (isset($_GET['delete']) && $_GET['delete'] == '1' && !empty($_GET['saved_search'])) {

        database_connect(IL_DATABASE_PATH, 'library');

        $dbHandle->beginTransaction();

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "arxiv#" . $_GET['saved_search'];

        $stmt->execute();

        $dbHandle->commit();

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_arxiv'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
    }


########## main body ##############

    $microtime1 = microtime(true);

    reset($_GET);

    while (list($key, $value) = each($_GET)) {

        if (!empty($_GET[$key]))
            $_SESSION['session_download_' . $key] = $value;
    }

    if (isset($_GET['arxiv_searchname']))
        $_SESSION['session_download_arxiv_searchname'] = $_GET['arxiv_searchname'];

########## register variables ##############

    $parameter_string = '';

    if (!isset($_GET['from'])) {
        $_GET['from'] = '1';
        $from = $_GET['from'];
    } else {
        $from = intval($_GET['from']);
    }

########## prepare arXiv query ##############

    $edat_string = '';

    if (!empty($_GET['arxiv_edat'])) {

        if ($_GET['arxiv_edat'] == 'last search') {

            $entry_date = date("Ymd", $_GET['arxiv_last_search']);
        } else {

            $entry_date = date("Ymd", time() - ($_GET['arxiv_edat'] - 1) * 86400);
        }

        $edat_string = urlencode(" AND submittedDate:[$entry_date TO " . date("Ymd") . "]");
    }

    $year_string = '';

    if (!empty($_GET['arxiv_start_year']) && $_GET['arxiv_start_year'] < 2100 && $_GET['arxiv_start_year'] > 1900) {

        if (!empty($_GET['arxiv_end_year']) && $_GET['arxiv_start_year'] < $_GET['arxiv_end_year'] && $_GET['arxiv_end_year'] < 2100 && $_GET['arxiv_end_year'] > 1900) {

            $year_array = array();

            for ($i = $_GET['arxiv_start_year']; $i <= $_GET['arxiv_end_year']; $i++) {

                $year_array[] = $i;
            }

            $year_string = join(" OR ", $year_array);
            $year_string = "($year_string)";
        } else {

            $year_string = $_GET['arxiv_start_year'];
        }

        $year_string = urlencode(" AND date:$year_string");
    }


    $category_array = array();
    $category_array2 = array();
    $category_string = '';
    $url_array = array();

    reset($_GET);

    while (list($key, $value) = each($_GET)) {

        if (strstr($key, "arxiv_category_")) {

            if (!strstr($key, "arxiv_category_physics_") && $_GET[$key] != 'all' && !empty($_GET[$key]))
                $category_array[] = $_GET[$key] . '.*';

            if (strstr($key, "arxiv_category_physics_") && !empty($_GET[$key]) && $_GET[$key] != 'all')
                $category_array[] = $_GET[$key];

            if ($_GET['arxiv_category_physics_all'] == 'all') {

                $category_array2 = array('astro-ph', 'cond-mat', 'gr-qc', 'hep-ex', 'hep-lat', 'hep-ph',
                    'hep-th', 'math-ph', 'nucl-ex', 'nucl-th', 'physics', 'quant-ph');
            }
        }

        if ($key != 'from' && $key != 'proxystr')
            $url_array[] = "$key=" . urlencode($value);
    }

    $category_array = array_merge($category_array, $category_array2);
    $category_array = array_filter(array_unique($category_array));

    if (!empty($category_array)) {

        $category_string = join(" OR ", $category_array);
        $category_string = urlencode(" AND cat:($category_string)");
    }

    $url_string = join("&", $url_array);

    $query_array = array();
    $id_array = array();

    $k = 1;

    for ($i = 1; $i < 8; $i++) {

        if (!empty($_GET['arxiv_query' . $i])) {

            $query_array[] = (($k > 1) ? ' ' . $_GET['arxiv_operator' . $i] . ' ' : '') . $_GET['arxiv_selection' . $i] . ':' . $_GET['arxiv_query' . $i];
            if ($_GET['arxiv_selection' . $i] == 'id')
                $id_array[] = $_GET['arxiv_query' . $i];
            $k = $k + 1;
        }
    }

    $query_array = array_filter($query_array);
    $query_string = join('', $query_array);
    $query_string = 'search_query=' . urlencode($query_string);

    if (!empty($id_array)) {
        $query_string = join(',', $id_array);
        $query_string = 'id_list=' . urlencode($query_string);
    }

########## search arXiv ##############

    if (!empty($query_array) && empty($_GET['load']) && empty($_GET['save']) && empty($_GET['delete'])) {

        ############# caching ################

        $cache_name = cache_name();
        $cache_name .= '_download';
        $db_change = database_change(array(
            'library'
        ));
        cache_start($db_change);

        ########## register the time of search ##############

        if (!empty($_SESSION['session_download_arxiv_searchname']) && $from == 1) {

            database_connect(IL_DATABASE_PATH, 'library');

            $stmt = $dbHandle->prepare("UPDATE searches SET searchvalue=:searchvalue WHERE userID=:user AND searchname=:searchname AND searchfield='arxiv_last_search'");

            $stmt->bindParam(':user', $user, PDO::PARAM_STR);
            $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);
            $stmt->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

            $user = $_SESSION['user_id'];
            $searchname = "arxiv#" . $_SESSION['session_download_arxiv_searchname'];
            $searchvalue = time();

            $stmt->execute();
        }

        ########## search arXiv ##############

        $request_url = "http://export.arxiv.org/api/query?" . $query_string . $category_string . $edat_string . $year_string . "&start=" . ($from - 1) . "&max_results=10&sortBy=lastUpdatedDate&sortOrder=descending";

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

        if (empty($xml))
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
    }

########## display search result summaries ##############

    if (!empty($xml)) {

        print '<div style="padding:2px;font-weight:bold">arXiv search';

        if (!empty($_SESSION['session_download_arxiv_searchname']))
            print ': ' . htmlspecialchars($_SESSION['session_download_arxiv_searchname']);

        print '</div>';

        $count = 0;
        $xmlxml = $xml->asXML();
        preg_match('/<opensearch:totalResults.*opensearch:totalResults>/', $xmlxml, $xmlxml_match);
        if (isset($xmlxml_match[0]))
            $count = strip_tags($xmlxml_match[0]);

        if ($count > 0) {

            $maxfrom = $from + 9;
            if ($maxfrom > $count)
                $maxfrom = $count;

            $microtime2 = microtime(true);
            $microtime = $microtime2 - $microtime1;
            $microtime = sprintf("%01.1f seconds", $microtime);

            print '<table class="top" style="margin-bottom:1px"><tr><td class="top" style="width: 13em">';

            print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_arxiv.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_arxiv.php?' . $url_string . '&from=' . ($from - 10)) . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="text-align: center">';

            print "Items $from - $maxfrom of $count in $microtime.";

            print '</td><td class="top" style="width: 14em">';

            (($count % 10) == 0) ? $lastpage = 1 + $count - 10 : $lastpage = 1 + $count - ($count % 10);

            print '<div class="ui-state-default ui-corner-top' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars('download_arxiv.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_arxiv.php?$url_string&from=" . ($from + 10)) . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px;text-align: center">PgDn</div>';

            print '</td></tr></table>';

            print '<div class="alternating_row">';

            database_connect(IL_DATABASE_PATH, 'library');

            $id = '';
            $title = '';
            $journal_abbr = '';
            $pub_date = '';

            foreach ($xml->entry as $record) {

                $id = '';
                $doi = '';
                $title = '';
                $secondary_title = '';
                $pub_date = '';
                $year = '';
                $abstract = '';
                $authors = array();
                $name_array = array();
                $names = '';
                $keywords = '';
                $uid = '';
                $url = '';
                $new_authors = array();

                $id = $record->id;
                $id = preg_replace('/http:\/\/arxiv\.org\/abs\//i', '', $id);

                $title = $record->title;

                $journal_ref = '';
                $secondary_title = 'eprint';
                $children = $record->children('http://arxiv.org/schemas/atom');
                $journal_ref = $children->journal_ref;
                if (!empty($journal_ref))
                    $secondary_title = $journal_ref;

                $doi = $children->doi;

                $pub_date = $record->published;
                $year = date("Y-m-j", strtotime($pub_date));

                $abstract = trim($record->summary);

                $authors = $record->author;

                $last_name = array();
                $first_name = array();
                if (!empty($authors)) {

                    foreach ($authors as $author) {

                        $author = $author->name;
                        $author_array = explode(' ', $author);
                        $last = array_pop($author_array);
                        $first = join(' ', $author_array);
                        $name_array[] = $last . ', ' . $first;
                        $last_name[] = $last;
                        $first_name[] = $first;
                    }
                }

                if (isset($name_array))
                    $names = join("; ", $name_array);

                $category = $children->primary_category;
                $keywords = $category->attributes();

                $uid = "ARXIV:$id";

                $url = "http://arxiv.org/abs/$id";

                if (!empty($id) && !empty($title)) {

                    ########## gray out existing records ##############

                    $existing_id = '';
                    $title_query = $dbHandle->quote(substr($title, 0, -1) . "%");
                    $result_query = $dbHandle->query("SELECT id FROM library WHERE title LIKE $title_query AND length(title) <= length($title_query)+2 LIMIT 1");
                    $existing_id = $result_query->fetchColumn();

                    //IS FLAGGED?
                    $relation = 0;
                    $id_query = $dbHandle->quote($id);
                    $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . intval($_SESSION['user_id']) . " AND database='arxiv' AND uid=" . $id_query . " LIMIT 1");
                    if ($result)
                        $relation = $result->fetchColumn();
                    $result = null;

                    print '<div class="items" data-uid="' . htmlspecialchars($id) . '" style="padding:0">';

                    print '<div class="ui-widget-header" style="border-left:0;border-right:0">';

                    print '<div class="flag ' . (($relation == 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="float:right;margin:4px"><i class="fa fa-flag"></i></div>';

                    print '<div class="titles brief" style="overflow:hidden;margin-right:30px';

                    if (is_numeric($existing_id))
                        print ';color: #777';

                    print '">' . $title . '</div>';

                    print '</div>';

                    print '<div class="firstcontainer items">';

                    print htmlspecialchars($secondary_title);

                    if ($year != '')
                        print " ($year)";

                    if (!empty($names))
                        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names) . '</div>';

                    print '<a href="' . htmlspecialchars('http://arxiv.org/abs/' . $id) . '" target="_blank">arXiv</a>';
                    print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://arxiv.org/pdf/" . $id) . '" target="_blank">PDF preprint</a>';
                    if (!empty($doi))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://dx.doi.org/" . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                    print '</div>';

                    print '<div class="abstract_container" style="display:none">';

                    ##########	print results into table	##########

                    print '<form enctype="application/x-www-form-urlencoded" action="upload.php" method="POST" class="fetch-form">';

                    print '<div class="items">';

                    print '<div>';
                    if (!empty($secondary_title))
                        print htmlspecialchars($secondary_title);
                    if (!empty($year))
                        print " (" . htmlspecialchars($year) . ")";
                    print '</div>';

                    if (!empty($names)) {
                        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names) . '</div>';
                        $array = explode(';', $names);
                        $array = array_filter($array);
                        if (!empty($array)) {
                            foreach ($array as $author) {
                                $array2 = explode(',', $author);
                                $last = trim($array2[0]);
                                $first = trim($array2[1]);
                                $new_authors[] = 'L:"' . $last . '",F:"' . $first . '"';
                            }
                            $names = join(';', $new_authors);
                        }
                    }

                    print '</div>';

                    print '<div class="abstract" style="padding:0 10px">';

                    !empty($abstract) ? print htmlspecialchars($abstract) : print 'No abstract available.';

                    print '</div><div class="items">';
                    ?>

                    <input type="hidden" name="uid[]" value="<?php if (!empty($uid)) print htmlspecialchars($uid); ?>">
                    <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
                    <input type="hidden" name="url[]" value="<?php if (!empty($url)) print htmlspecialchars($url); ?>">
                    <input type="hidden" name="last_name" value="<?php if (!empty($last_name)) print htmlspecialchars(json_encode($last_name)); ?>">
                    <input type="hidden" name="first_name" value="<?php if (!empty($first_name)) print htmlspecialchars(json_encode($first_name)); ?>">
                    <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
                    <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
                    <input type="hidden" name="journal_abbr" value="<?php if (!empty($journal_abbr)) print htmlspecialchars($journal_abbr); ?>">
                    <input type="hidden" name="year" value="<?php if (!empty($year)) print htmlspecialchars($year); ?>">
                    <input type="hidden" name="keywords" value="<?php if (!empty($keywords)) print htmlspecialchars($keywords); ?>">
                    <input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract) : "No abstract available."; ?>">
                    <input type="hidden" name="form_new_file_link" value="<?php print !empty($id) ? htmlspecialchars("http://arxiv.org/pdf/" . $id) : ""; ?>">
                    <?php
                    ##########	print full text links	##########

                    print '<b>Full text options:</b><br>';

                    print '<a href="' . htmlspecialchars('http://arxiv.org/abs/' . $id) . '" target="_blank">arXiv</a>';
                    print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://arxiv.org/pdf/" . $id) . '" target="_blank">PDF preprint</a>';
                    if (!empty($doi))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://dx.doi.org/" . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                    print '<br><button class="save-item"><i class="fa fa-save"></i> Save</button> <button class="quick-save-item"><i class="fa fa-save"></i> Quick Save</button>';

                    print '</div></form>';

                    print '</div>';

                    print '<div class="save_container"></div>';

                    print '</div>';
                }
            }

            $dbHandle = null;

            print '</div>';

            print '<table class="top" style="margin-top:1px"><tr><td class="top" style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_arxiv.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation prevpage" href="' . htmlspecialchars('download_arxiv.php?' . $url_string . '&from=' . ($from - 10)) . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars('download_arxiv.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count >= $from + 10 ? '<a class="navigation nextpage" href="' . htmlspecialchars("download_arxiv.php?$url_string&from=" . ($from + 10)) . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px;text-align: center">PgUp</div>';

            print '</td></tr></table>';
        } else {
            print '<div style="position:relative;top:43%;left:40%;color:#bbbbbb;font-size:28px;width:200px"><b>No Items</b></div>';
        }

        ############# caching #############
        cache_store();
    } else {

########## input table ##############
        ?>
        <div style="text-align: left">
            <form enctype="application/x-www-form-urlencoded" action="download_arxiv.php" method="GET" id="download-form">
                <div class="ui-state-default ui-corner-all" style="float:left;margin:4px 4px 2px 4px;padding:1px 4px">
                    <a href="http://arxiv.org/find" target="_blank" style="display:block"><i class="fa fa-external-link"></i> arXiv</a>
                </div>
                <div style="clear:both"></div>
                <table class="threed" style="width:100%">
                    <tr>
                        <td class="threed" colspan="3">
                            <button id="download-search"><i class="fa fa-save"></i> Search</button>
                            <button id="download-reset"><i class="fa fa-reply"></i> Reset</button>
                            <button id="download-clear"><i class="fa fa-trash-o"></i> Clear</button>
                        </td>
                    </tr>
                    <?php
                    for ($i = 1; $i < 8; $i++) {

                        print ' <tr>
  <td class="threed" style="width:12em">
  <select name="arxiv_selection' . $i . '">
	<option value="all" ' . ((($i == 1 && !isset($_SESSION['session_download_arxiv_selection' . $i])) || (isset($_SESSION['session_download_arxiv_selection' . $i]) && $_SESSION['session_download_arxiv_selection' . $i] == 'all')) ? 'selected' : '') . '>anywhere</option>
	<option value="au" ' . ((($i == 2 && !isset($_SESSION['session_download_arxiv_selection' . $i])) || (isset($_SESSION['session_download_arxiv_selection' . $i]) && $_SESSION['session_download_arxiv_selection' . $i] == 'au')) ? 'selected' : '') . '>authors</option>
	<option value="ti" ' . ((($i == 3 && !isset($_SESSION['session_download_arxiv_selection' . $i])) || (isset($_SESSION['session_download_arxiv_selection' . $i]) && $_SESSION['session_download_arxiv_selection' . $i] == 'ti')) ? 'selected' : '') . '>title</option>
	<option value="abs" ' . ((($i == 4 && !isset($_SESSION['session_download_arxiv_selection' . $i])) || (isset($_SESSION['session_download_arxiv_selection' . $i]) && $_SESSION['session_download_arxiv_selection' . $i] == 'abs')) ? 'selected' : '') . '>abstract</option>
	<option value="jr" ' . ((($i == 5 && !isset($_SESSION['session_download_arxiv_selection' . $i])) || (isset($_SESSION['session_download_arxiv_selection' . $i]) && $_SESSION['session_download_arxiv_selection' . $i] == 'jr')) ? 'selected' : '') . '>journal</option>
	<option value="rn" ' . ((($i == 6 && !isset($_SESSION['session_download_arxiv_selection' . $i])) || (isset($_SESSION['session_download_arxiv_selection' . $i]) && $_SESSION['session_download_arxiv_selection' . $i] == 'rn')) ? 'selected' : '') . '>report number</option>
	<option value="id" ' . ((($i == 7 && !isset($_SESSION['session_download_arxiv_selection' . $i])) || (isset($_SESSION['session_download_arxiv_selection' . $i]) && $_SESSION['session_download_arxiv_selection' . $i] == 'id')) ? 'selected' : '') . '>identifier</option>
  </select>
  </td>
  <td class="threed">
  <input type="text" name="arxiv_query' . $i . '" value="' . htmlspecialchars((isset($_SESSION['session_download_arxiv_query' . $i])) ? $_SESSION['session_download_arxiv_query' . $i] : '') . '" size="65" style="width:99.5%">
  </td>
  <td class="threed" style="width:10em">
  <select name="arxiv_operator' . $i . '">
	<option ' . ((isset($_SESSION['session_download_arxiv_operator' . $i]) && $_SESSION['session_download_arxiv_operator' . $i] == 'AND') ? 'selected' : '') . '>AND</option>
	<option ' . ((isset($_SESSION['session_download_arxiv_operator' . $i]) && $_SESSION['session_download_arxiv_operator' . $i] == 'OR') ? 'selected' : '') . '>OR</option>
	<option ' . ((isset($_SESSION['session_download_arxiv_operator' . $i]) && $_SESSION['session_download_arxiv_operator' . $i] == 'ANDNOT') ? 'selected' : '') . '>ANDNOT</option>
  </select>
  </td>
 </tr>';
                    }
                    ?>
                    <tr>
                        <td class="threed">
                            Years published:
                        </td>
                        <td class="threed">
                            <input type="text" name="arxiv_start_year" size="5" value="<?php print isset($_SESSION['session_download_arxiv_start_year']) ? htmlspecialchars($_SESSION['session_download_arxiv_start_year']) : ''; ?>">
                            - (<input type="text" name="arxiv_end_year" size="5" value="<?php print isset($_SESSION['session_download_arxiv_end_year']) ? htmlspecialchars($_SESSION['session_download_arxiv_end_year']) : ''; ?>">)
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 0px; background-color: transparent">
                            Limits:
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    <tr>
                        <td class="threed">
                            Categories to query:
                        </td>
                        <td class="threed">
                            <div style="float: left;margin-right: 10px">
                                <input type="checkbox" name="arxiv_category_physics" value="physics" <?php print (!empty($_SESSION['session_download_arxiv_category_physics'])) ? 'checked' : ''  ?>>Physics ---------------------><br>
                                <input type="checkbox" name="arxiv_category_cs" value="cs" <?php print (!empty($_SESSION['session_download_arxiv_category_cs'])) ? 'checked' : ''  ?>>Computer Science<br>
                                <input type="checkbox" name="arxiv_category_math" value="math" <?php print (!empty($_SESSION['session_download_arxiv_category_math'])) ? 'checked' : ''  ?>>Mathematics<br>
                                <input type="checkbox" name="arxiv_category_nlin" value="nlin" <?php print (!empty($_SESSION['session_download_arxiv_category_nlin'])) ? 'checked' : ''  ?>>Nonlinear Sciences<br>
                                <input type="checkbox" name="arxiv_category_q-bio" value="q-bio" <?php print (!empty($_SESSION['session_download_arxiv_category_q-bio'])) ? 'checked' : ''  ?>>Quantitative Biology<br>
                                <input type="checkbox" name="arxiv_category_q-fin" value="q-fin" <?php print (!empty($_SESSION['session_download_arxiv_category_q-fin'])) ? 'checked' : ''  ?>>Quantitative Finance<br>
                                <input type="checkbox" name="arxiv_category_stat" value="stat" <?php print (!empty($_SESSION['session_download_arxiv_category_stat'])) ? 'checked' : ''  ?>>Statistics
                            </div>
                            <div style="float: left;margin-right: 10px">
                                <input type="checkbox" name="arxiv_category_physics_all" value="all" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_all'])) ? 'checked' : ''  ?>>all<br>
                                <input type="checkbox" name="arxiv_category_physics_astro-ph" value="astro-ph" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_astro-ph'])) ? 'checked' : ''  ?>>astro-ph<br>
                                <input type="checkbox" name="arxiv_category_physics_cond-mat" value="cond-mat" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_cond-mat'])) ? 'checked' : ''  ?>>cond-mat<br>
                                <input type="checkbox" name="arxiv_category_physics_gr-qc" value="gr-qc" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_gr-qc'])) ? 'checked' : ''  ?>>gr-qc<br>
                                <input type="checkbox" name="arxiv_category_physics_hep-ex" value="hep-ex" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_hep-ex'])) ? 'checked' : ''  ?>>hep-ex<br>
                                <input type="checkbox" name="arxiv_category_physics_hep-lat" value="hep-lat" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_hep-lat'])) ? 'checked' : ''  ?>>hep-lat<br>
                                <input type="checkbox" name="arxiv_category_physics_hep-ph" value="hep-ph" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_hep-ph'])) ? 'checked' : ''  ?>>hep-ph<br>
                            </div>
                            <div style="float: left;margin-top: 19px">
                                <input type="checkbox" name="arxiv_category_physics_hep-th" value="hep-th" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_hep-th'])) ? 'checked' : ''  ?>>hep-th<br>
                                <input type="checkbox" name="arxiv_category_physics_math-ph" value="math-ph" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_math-ph'])) ? 'checked' : ''  ?>>math-ph<br>
                                <input type="checkbox" name="arxiv_category_physics_nucl-ex" value="nucl-ex" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_nucl-ex'])) ? 'checked' : ''  ?>>nucl-ex<br>
                                <input type="checkbox" name="arxiv_category_physics_nucl-th" value="nucl-th" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_nucl-th'])) ? 'checked' : ''  ?>>nucl-th<br>
                                <input type="checkbox" name="arxiv_category_physics_physics" value="physics" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_physics'])) ? 'checked' : ''  ?>>physics<br>
                                <input type="checkbox" name="arxiv_category_physics_quant-ph" value="quant-ph" <?php print (!empty($_SESSION['session_download_arxiv_category_physics_quant-ph'])) ? 'checked' : ''  ?>>quant-ph
                            </div>
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Search within the last:
                        </td>
                        <td class="threed">
                            <select name="arxiv_edat">
                                <option value=""></option>
                                <option value="2" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == '2' ? 'selected' : ''; ?>>2 days</option>
                                <option value="5" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == '5' ? 'selected' : ''; ?>>5 days</option>
                                <option value="7" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == '7' ? 'selected' : ''; ?>>1 week</option>
                                <option value="14" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == '14' ? 'selected' : ''; ?>>2 weeks</option>
                                <option value="31" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == '31' ? 'selected' : ''; ?>>1 month</option>
                                <option value="92" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == '92' ? 'selected' : ''; ?>>3 months</option>
                                <option value="183" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == '183' ? 'selected' : ''; ?>>6 months</option>
                                <option value="365" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == '365' ? 'selected' : ''; ?>>1 year</option>
                                <option value="last search" <?php print isset($_SESSION['session_download_arxiv_edat']) && $_SESSION['session_download_arxiv_edat'] == 'last search' ? 'selected' : ''; ?>>since last search</option>
                            </select>
                            <input type="hidden" name="arxiv_last_search" value="<?php print isset($_SESSION['session_download_arxiv_last_search']) ? $_SESSION['session_download_arxiv_last_search'] : '1'; ?>">
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Save search as:
                        </td>
                        <td class="threed">
                            <input type="text" name="arxiv_searchname" size="35" style="width:50%" value="<?php print isset($_SESSION['session_download_arxiv_searchname']) ? htmlspecialchars($_SESSION['session_download_arxiv_searchname']) : ''  ?>">
                            &nbsp;<button id="download-save"><i class="fa fa-save"></i> Save</button>
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                </table>
                &nbsp;<a href="http://arxiv.org/find#help" target="_blank">Help</a>
            </form>
        </div>
        <?php
        // CLEAN DOWNLOAD CACHE
        $clean_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'page_*_download', GLOB_NOSORT);
        if (is_array($clean_files)) {
            foreach ($clean_files as $clean_file) {
                if (is_file($clean_file) && is_writable($clean_file))
                    @unlink($clean_file);
            }
        }
    }
}
?>
<br>