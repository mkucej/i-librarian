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
    } else {
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

            if (strstr($key, 'session_download_ieee'))
                unset($_SESSION[$key]);
        }
    }

    // save button

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['ieee_searchname'])) {

        database_connect($database_path, 'library');

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $stmt2 = $dbHandle->prepare("INSERT INTO searches (userID,searchname,searchfield,searchvalue) VALUES (:user,:searchname,'',:searchvalue)");

        $stmt2->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt2->bindParam(':searchname', $searchname, PDO::PARAM_STR);
        $stmt2->bindParam(':searchvalue', $save_string, PDO::PARAM_STR);

        $dbHandle->beginTransaction();

        $user = $_SESSION['user_id'];
        $searchname = "ieee#" . $_GET['ieee_searchname'];

        $stmt->execute();

        $user = $_SESSION['user_id'];
        $searchname = "ieee#" . $_GET['ieee_searchname'];

        reset($_GET);
        $save_array = $_GET;
        unset($save_array['_']);
        unset($save_array['save']);
        unset($save_array['action']);
        unset($save_array['ieee_searchname']);
        $save_array = array_filter($save_array);
        $save_string = serialize($save_array);

        $stmt2->execute();

        $dbHandle->commit();
    }

    // load button

    if (isset($_GET['load']) && $_GET['load'] == '1' && !empty($_GET['saved_search'])) {

        database_connect($database_path, 'library');

        $stmt = $dbHandle->prepare("SELECT searchvalue FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "ieee#" . $_GET['saved_search'];

        $stmt->execute();

        reset($_SESSION);
        while (list($key, $value) = each($_SESSION)) {
            if (strstr($key, 'session_download_ieee'))
                unset($_SESSION[$key]);
        }

        $_GET = array();

        $load_string = $stmt->fetchColumn();
        $_GET = unserialize($load_string);
        $_GET['load'] = 'Load';
        $_GET['ieee_searchname'] = substr($searchname, 5);
        while (list($key, $value) = each($_GET)) {
            if (!empty($_GET[$key]))
                $_SESSION['session_download_' . $key] = $value;
        }
    }

    // delete button

    if (isset($_GET['delete']) && $_GET['delete'] == '1' && !empty($_GET['saved_search'])) {

        database_connect($database_path, 'library');

        $dbHandle->beginTransaction();

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "ieee#" . $_GET['saved_search'];

        $stmt->execute();

        $dbHandle->commit();

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_ieee'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
    }

    if (!empty($_GET['action'])) {

        $microtime1 = microtime(true);

        reset($_GET);
        unset($_SESSION['session_download_ieee_refinements']);
        unset($_SESSION['session_download_ieee_openaccess']);
        while (list($key, $value) = each($_GET)) {
            if (!empty($_GET[$key]))
                $_SESSION['session_download_' . $key] = $value;
        }

        if (!isset($_GET['from'])) {
            $_GET['from'] = '1';
            $from = $_GET['from'];
        } else {
            $from = intval($_GET['from']);
        }

        // PREPARE QUERY

        $url_string = 'action=search';

        // SEARCH TYPE

        $type_string = '&searchField=Search_All';
        if ($_GET['ieee_type'] == 'fulltext')
            $type_string = '&searchField=Search_All_Text';
        $url_string .= '&ieee_type=' . urlencode($_GET['ieee_type']);

        //ADD RANGE

        $year_string = 'addRange=1872_' . date('Y') . '_Publication_Year';

        if (isset($_GET['ieee_range']) && $_GET['ieee_range'] == 'range') {
            $year_from = '1872';
            $year_to = date('Y');
            if (!empty($_GET['ieee_year_from']))
                $year_from = $_GET['ieee_year_from'];
            if (!empty($_GET['ieee_year_to']))
                $year_to = $_GET['ieee_year_to'];
            $year_string = 'addRange=' . $year_from . '_' . $year_to . '_Publication_Year';

            $url_string .= '&ieee_range=' . urlencode($_GET['ieee_range']) . '&'
                    . 'ieee_year_from=' . urlencode($_GET['ieee_year_from']) . '&'
                    . 'ieee_year_to=' . urlencode($_GET['ieee_year_to']);
        }

        //REFINEMENTS

        $refinement_string = '';
        $refinement_array = array();

        if (isset($_GET['ieee_refinements'])) {
            while (list($key, $value) = each($_GET['ieee_refinements'])) {
                $refinement_array[] = 'refinements=' . urlencode($value);
                $refinement_string = join('&', $refinement_array);
                $url_string .= '&ieee_refinements[]=' . urlencode($value);
            }
        }
        
        //OPEN ACCESS
        
        $openaccess_string = '';

        if (isset($_GET['ieee_openaccess'])) {
            $openaccess_string = 'openAccess=true' ;
            $url_string .= '&openAccess=true';
        }

        //SORTING

        $sortby_string = '';

        if (isset($_GET['ieee_sort'])) {
            $sortby_string = 'sortType=' . urlencode($_GET['ieee_sort']);
            $url_string .= '&ieee_sort=' . urlencode($_GET['ieee_sort']);
        }

        //PAGINATION

        $pagination_string = 'pageNumber=' . $from;

        //MAIN QUERY

        $query_string = '';
        $k = 1;

        for ($i = 1; $i < 11; $i++) {
            if (!empty($_GET['ieee_query' . $i])) {
                $query_string .= (($k > 1) ? ' ' . $_GET['ieee_operator' . $i] . ' ' : '') . $_GET['ieee_parenthesis' . $i . '-1'] . $_GET['ieee_searchin' . $i] . ':' . $_GET['ieee_query' . $i] . $_GET['ieee_parenthesis' . $i . '-2'];
                $k = $k + 1;
                if ($i > 1)
                    $url_string .= '&ieee_operator' . $i . '=' . urlencode($_GET['ieee_operator' . $i]);
                $url_string .= '&ieee_parenthesis' . $i . '-1=' . urlencode($_GET['ieee_parenthesis' . $i . '-1'])
                        . '&ieee_searchin' . $i . '=' . urlencode($_GET['ieee_searchin' . $i])
                        . '&ieee_query' . $i . '=' . urlencode($_GET['ieee_query' . $i])
                        . '&ieee_parenthesis' . $i . '-2=' . urlencode($_GET['ieee_parenthesis' . $i . '-2']);
            }
        }

        $query = urlencode('queryText=(' . $query_string . ')');

        // SEARCH

        if (!empty($query_string) && empty($_GET['load']) && empty($_GET['save']) && empty($_GET['delete'])) {

            // CACHE

            $cache_name = cache_name();
            $cache_name .= '_download';
            $db_change = database_change(array(
                'library'
            ));
            cache_start($db_change);

            ########## register the time of search ##############

            if (!empty($_SESSION['session_download_ieee_searchname']) && $from == 1) {

                database_connect($database_path, 'library');

                $stmt = $dbHandle->prepare("UPDATE searches SET searchvalue=:searchvalue WHERE userID=:user AND searchname=:searchname AND searchfield='ieee_last_search'");

                $stmt->bindParam(':user', $user, PDO::PARAM_STR);
                $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);
                $stmt->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

                $user = $_SESSION['user_id'];
                $searchname = "ieee#" . $_SESSION['session_download_ieee_searchname'];
                $searchvalue = time();

                $stmt->execute();
            }

            ########## search ieee ##############

            $request_url = "http://ieeexplore.ieee.org/search/searchresult.jsp?action=search&rowsPerPage=25&matchBoolean=true&"
                    . $query . "&"
                    . $refinement_string . "&"
                    . $sortby_string . "&"
                    . $pagination_string . "&"
                    . $year_string . "&"
                    . $openaccess_string . "&"
                    . $type_string;

            $dom = proxy_dom_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

            if (empty($dom))
                die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
        }

        // DISPLAY RESULTS

        if (!empty($dom)) {

            print '<div style="padding:2px;font-weight:bold">IEEE Xplore&reg; search';

            if (!empty($_SESSION['session_download_ieee_searchname']))
                print ': ' . htmlspecialchars($_SESSION['session_download_ieee_searchname']);

            print '</div>';

            //SCRAPE, BABY, SCRAPE!

            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            $doc->loadHTML($dom);
            $xpath = new DOMXPath($doc);
            $div = $doc->getElementById('content');
            $count_string = '';
            $count_obj = $xpath->query("span", $div)->item(0);
            if (is_object($count_obj))
                $count_string = $count_obj->nodeValue;
            $count = preg_replace('/\D/ui', '', $count_string);

            if (!empty($count) && $count > 0) {

                $maxfrom = $from * 25;
                if ($maxfrom > $count)
                    $maxfrom = $count;

                $microtime2 = microtime(true);
                $microtime = $microtime2 - $microtime1;
                $microtime = sprintf("%01.1f seconds", $microtime);

                print '<table class="top" style="margin-bottom:1px"><tr><td style="width: 13em">';

                print '<div class="ui-state-highlight ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                        '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=' . ($from - 1)) . '" style="color:black;display:block;width:100%">') .
                        '<i class="fa fa-caret-left"></i>&nbsp;Back'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '</td><td class="top" style="text-align: center">';

                print "Items " . (($from - 1) * 25 + 1) . " - $maxfrom of $count in $microtime.";

                print '</td><td class="top" style="width: 14em">';

                $lastpage = ceil($count / 25);

                print '<div class="ui-state-highlight ui-corner-top' . ($count > $from * 25 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px">'
                        . ($count > $from * 25 ? '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                        '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                        . ($count > $from * 25 ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-top' . ($count > $from * 25 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em">'
                        . ($count > $from * 25 ? '<a class="navigation" href="' . htmlspecialchars("download_ieee.php?$url_string&from=" . ($from + 1)) . '" style="color:black;display:block;width:100%">' : '') .
                        '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                        . ($count > $from * 25 ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px">PgDn</div>';

                print '</td></tr></table>';

                print '<div class="alternating_row">';

                database_connect($database_path, 'library');

                function DOMinnerHTML($element) {
                    $innerHTML = "";
                    $children = $element->childNodes;
                    foreach ($children as $child) {
                        $tmp_dom = new DOMDocument();
                        $tmp_dom->appendChild($tmp_dom->importNode($child, true));
                        $innerHTML.=trim($tmp_dom->saveHTML());
                    }
                    return $innerHTML;
                }

                $form = $doc->getElementById('search_results_form');
                $entries = $xpath->query("ul[1]", $form);
                $items = $xpath->query("li/div/div[3]", $entries->item(0));

                foreach ($items as $item) {
                    $id = '';
                    $title = '';
                    $secondary_title = '';
                    $abstract = '';
                    $authors = '';
                    $doi = '';
                    $year = '';
                    $volume = '';
                    $issue = '';
                    $pages = '';
                    $new_authors = array();
                    $array = array();
                    // TITLE
                    $title_obj = $xpath->query("h3/a", $item)->item(0);
                    if (is_object($title_obj))
                        $title = trim($title_obj->nodeValue);

                    $item_html = DOMinnerHTML($item);
                    $item_html = str_replace("\r\n", " ", $item_html);
                    $item_html = str_replace("\n", " ", $item_html);
                    $item_html = str_replace("\r", " ", $item_html);
                    // IEEE ID
                    preg_match('/(?<=arnumber\=)\d+?(?=\&)/ui', $item_html, $id_match);
                    if (isset($id_match[0])) {
                        $id = trim(strip_tags($id_match[0]));
                        $uid = 'IEEE:' . $id;
                    }
                    // AUTHORS
                    $authors_arr = array();
                    preg_match_all('/\<a href\=\"\#\" class\=\"(authorPreferredName)? prefNameLink\".*?\<\/a\>/ui', $item_html, $authors_match);
                    foreach ($authors_match[0] as $author_raw) {
                        $authors_arr[] = trim(strip_tags($author_raw));
                    }
                    $authors = join('; ', $authors_arr);
                    // DOI
                    preg_match('/10\.\d{4}\/.*?(?=\s)/ui', strip_tags($item_html), $doi_match);
                    if (isset($doi_match[0]))
                        $doi = trim($doi_match[0]);
                    // YEAR
                    preg_match('/(?<=Publication\syear\:\s)\d{4}/ui', strip_tags($item_html), $year_match);
                    if (isset($year_match[0]))
                        $year = trim($year_match[0]);

                    if (!empty($id) && !empty($title)) {

                        ########## gray out existing records ##############

                        $existing_id = '';
                        $title_query = $dbHandle->quote(substr($title, 0, -1) . "%");
                        $result_query = $dbHandle->query("SELECT id FROM library WHERE title LIKE $title_query AND length(title) <= length($title_query)+2 LIMIT 1");
                        $existing_id = $result_query->fetchColumn();

                        print '<div class="items" data-uid="' . htmlspecialchars($id) . '" style="padding:0">';

                        print '<div class="ui-widget-header" style="border-left:0;border-right:0">';

                        print '<div class="titles brief" style="overflow:hidden;margin-right:10px';

                        if (is_numeric($existing_id))
                            print ';color: #777';

                        print '">' . $title . '</div>';

                        print '</div>';

                        print '<div class="firstcontainer items">';

                        print htmlspecialchars($secondary_title);
                        
                        if (!empty($authors))
                            print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($authors) . '</div>';

                        if ($year != '')
                            print " ($year)";
                        
                        print '<br>';

                        print '<a href="' . htmlspecialchars('http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=' . $id) . '" target="_blank">IEEE</a>';
                        
                        if (!empty($doi))
                            print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://dx.doi.org/" . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                        print '</div>';
                        
                        print '<div class="abstract_container" style="display:none"></div>';

                        print '<div class="save_container"></div>';

                        print '</div>';
                    }
                }

                $dbHandle = null;

                print '</div>';

                print '<table class="top" style="margin-top:1px"><tr><td class="top" style="width: 50%">';

                print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                        '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em">'
                        . ($from == 1 ? '' : '<a class="navigation prevpage" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=' . ($from - 1)) . '" style="color:black;display:block;width:100%">') .
                        '<i class="fa fa-caret-left"></i>&nbsp;Back'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '</td><td class="top" style="width: 50%">';

                print '<div class="ui-state-highlight ui-corner-bottom' . ($count > $from * 25 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px">'
                        . ($count > $from * 25 ? '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                        '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                        . ($count > $from * 25 ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-bottom' . ($count > $from * 25 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em">'
                        . ($count > $from * 25 ? '<a class="navigation nextpage" href="' . htmlspecialchars("download_ieee.php?$url_string&from=" . ($from + 1)) . '" style="color:black;display:block;width:100%">' : '') .
                        '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                        . ($count > $from * 25 ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px">PgUp</div>';

                print '</td></tr></table><br>';
            } else {
                print '<div style="position:relative;top:43%;left:40%;color:#bbbbbb;font-size:28px;width:200px"><b>No Items</b></div>';
            }

            ############# caching #############
            cache_store();
        }
    } else {

########## input table ##############
        ?>
        <form enctype="application/x-www-form-urlencoded" action="download_ieee.php" method="GET" id="download-form">	
            <input type="hidden" value="" name="rowsPerPage">
            <input type="hidden" value="search" name="action">
            <div class="ui-state-highlight ui-corner-all" style="float:left;margin:4px 4px 2px 4px;padding:1px 4px">
                <a href="http://ieeexplore.ieee.org" target="_blank" style="display:block"><i class="fa fa-external-link"></i> IEEE Xplore</a>
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
                <tr>
                    <td class="threed" style="width:11em">
                        Search :
                    </td>
                    <td class="threed" colspan="2">
                        <input type="radio" value="" name="ieee_type"<?php print empty($_SESSION['session_download_ieee_type']) ? ' checked' : ''  ?>>Metadata
                        <input type="radio" value="fulltext" name="ieee_type"<?php print (isset($_SESSION['session_download_ieee_type']) && $_SESSION['session_download_ieee_type'] == 'fulltext') ? ' checked' : ''  ?>>Full Text
                    </td>
                </tr>
                <?php
                $type_text = 'Metadata';
                if (isset($_SESSION['session_download_ieee_type']) && $_SESSION['session_download_ieee_type'] == 'fulltext')
                    $type_text = 'Full Text';
                for ($i = 1; $i < 11; $i++) {
                    print '
                <tr>
                    <td class="threed" style="text-align:right">';
                    if ($i > 1)
                        print '
                        <select name="ieee_operator' . $i . '">
                            <option value="AND">
                                AND
                            </option>
                            <option value="OR"' . ((isset($_SESSION['session_download_ieee_operator' . $i]) && $_SESSION['session_download_ieee_operator' . $i] == 'OR') ? ' selected' : '') . '>
                                OR
                            </option>
                            <option value="NOT"' . ((isset($_SESSION['session_download_ieee_operator' . $i]) && $_SESSION['session_download_ieee_operator' . $i] == 'NOT') ? ' selected' : '') . '>
                                NOT
                            </option>
                        </select>';
                    print '
                        <select name="ieee_parenthesis' . $i . '-1">
                            <option value=""></option>
                            <option value="("' . ((isset($_SESSION['session_download_ieee_parenthesis' . $i . '-1']) && $_SESSION['session_download_ieee_parenthesis' . $i . '-1'] == '(') ? ' selected' : '') . '>(</option>
                        </select>
                    </td>
                    <td class="threed">
                        <input type="text" size="50" name="ieee_query' . $i . '" style="width:99%" value="' . (!empty($_SESSION['session_download_ieee_query' . $i]) ? $_SESSION['session_download_ieee_query' . $i] : '') . '">
                    </td>
                    <td class="threed" style="width:25em">
                        in
                        <select class="ieee-searchin" name="ieee_searchin' . $i . '">
                            <option value="">
                                ' . $type_text . '
                            </option>
                            <option value="&quot;Document Title&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Document Title"') ? ' selected' : '') . '>
                                Document Title
                            </option>
                            <option value="&quot;Authors&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Authors"') ? ' selected' : '') . '>
                                Authors
                            </option>
                            <option value="&quot;Publication Title&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Publication Title"') ? ' selected' : '') . '>
                                Publication Title
                            </option>
                            <option value="&quot;Abstract&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Abstract"') ? ' selected' : '') . '>
                                Abstract
                            </option>
                            <option value="&quot;Index Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Index Terms"') ? ' selected' : '') . '>
                                Index Terms
                            </option>
                            <option value="&quot;Author Affiliation&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Author Affiliation"') ? ' selected' : '') . '>
                                Author Affiliation
                            </option>
                            <option value="&quot;Accession Number&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Accession Number"') ? ' selected' : '') . '>
                                Accession Number
                            </option>
                            <option value="&quot;Article Number&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Article Number"') ? ' selected' : '') . '>
                                Article Number
                            </option>
                            <option value="&quot;Author Keywords&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Author Keywords"') ? ' selected' : '') . '>
                                Author Keywords
                            </option>
                            <option value="&quot;DOE Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"DOE Terms"') ? ' selected' : '') . '>
                                DOE Terms
                            </option>
                            <option value="&quot;DOI&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"DOI"') ? ' selected' : '') . '>
                                DOI
                            </option>
                            <option value="&quot;IEEE Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"IEEE Terms"') ? ' selected' : '') . '>
                                IEEE Terms
                            </option>
                            <option value="&quot;INSPEC Controlled Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"INSPEC Controlled Terms"') ? ' selected' : '') . '>
                                INSPEC Controlled Terms
                            </option>
                            <option value="&quot;INSPEC Non-Controlled Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"INSPEC Non-Controlled Terms"') ? ' selected' : '') . '>
                                INSPEC Non-Controlled Terms
                            </option>
                            <option value="&quot;ISBN&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"ISBN"') ? ' selected' : '') . '>
                                ISBN
                            </option>
                            <option value="&quot;ISSN&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"ISSN"') ? ' selected' : '') . '>
                                ISSN
                            </option>
                            <option value="&quot;Issue&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Issue"') ? ' selected' : '') . '>
                                Issue
                            </option>
                            <option value="&quot;MeSH Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"MeSH Terms"') ? ' selected' : '') . '>
                                MeSH Terms
                            </option>
                            <option value="&quot;PACS Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"PACS Terms"') ? ' selected' : '') . '>
                                PACS Terms
                            </option>
                            <option value="&quot;Parent Publication Number&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Parent Publication Number"') ? ' selected' : '') . '>
                                Parent Publication Number
                            </option>
                            <option value="&quot;Publication Number&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Publication Number"') ? ' selected' : '') . '>
                                Publication Number
                            </option>
                            <option value="&quot;Standard Number&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Standard Number"') ? ' selected' : '') . '>
                                Standard Number
                            </option>
                            <option value="&quot;Standards Dictionary Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Standards Dictionary Terms"') ? ' selected' : '') . '>
                                Standards Dictionary Terms
                            </option>
                            <option value="Topic"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == 'Topic') ? ' selected' : '') . '>
                                Topic
                            </option>
                        </select>
                        <select name="ieee_parenthesis' . $i . '-2">
                            <option value=""></option>
                            <option value=")"' . ((isset($_SESSION['session_download_ieee_parenthesis' . $i . '-2']) && $_SESSION['session_download_ieee_parenthesis' . $i . '-2'] == ')') ? ' selected' : '') . '>)</option>
                        </select>
                    </td>
                </tr>';
                }
                ?>

            </table>
            &nbsp;Limits and sorting:
            <table class="threed" width="100%">
                <tr>
                    <td class="threed" style="width:11em">
                        Open Access:
                    </td>
                    <td class="threed">
                        <input type="checkbox" value="1" name="ieee_openaccess"<?php print isset($_SESSION['session_download_ieee_openaccess']) ? ' checked' : ''  ?>>
                    </td>
                </tr>
                <tr>
                    <td class="threed" style="width:11em">
                        Publisher:
                    </td>
                    <td class="threed">
                        <input type="checkbox" value="4294967269" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4294967269', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        IEEE
                        <input type="checkbox" value="4293612683" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4293612683', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        AIP
                        <input type="checkbox" value="4294967119" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4294967119', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        IET
                        <input type="checkbox" value="4293292639" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4293292639', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        AVS
                        <input type="checkbox" value="4292403457" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4292403457', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        IBM
                        <input type="checkbox" value="4292760466" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4292760466', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        VDE
                        <input type="checkbox" value="4283401643" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4283401643', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        BIAI
                        <input type="checkbox" value="4283401642" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4283401642', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        TUP
                        <input type="checkbox" value="4282906416" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4282906416', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                        MITP
                    </td>
                </tr>
                <tr>
                    <td class="threed">
                        Content Types:
                    </td>
                    <td class="threed">
                        <div style="float:left;margin-right:10px">
                            <input type="checkbox" value="4291944822" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4291944822', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                            Conference Publications<br>
                            <input type="checkbox" value="4291944246" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4291944246', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                            Journals &amp; Magazines<br>
                            <input type="checkbox" value="4291944823" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4291944823', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                            Books &amp; eBooks
                        </div>
                        <div>
                            <input type="checkbox" value="4291944245" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4291944245', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                            Early Access Articles<br>
                            <input type="checkbox" value="4294965216" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4294965216', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                            Standards<br>
                            <input type="checkbox" value="4291944243" name="ieee_refinements[]"<?php print (isset($_SESSION['session_download_ieee_refinements']) && in_array('4291944243', $_SESSION['session_download_ieee_refinements'])) ? ' checked' : ''  ?>>
                            Education &amp; Learning
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="threed">
                        Publication Year:
                    </td>
                    <td class="threed">
                        <input type="radio" value="" name="ieee_range"<?php print empty($_SESSION['session_download_ieee_range']) ? ' checked' : ''  ?>> All Available Years<br>
                        <input type="radio" value="range" name="ieee_range"<?php print (isset($_SESSION['session_download_ieee_range']) && $_SESSION['session_download_ieee_range'] == 'range') ? ' checked' : ''  ?>> Specify Year Range
                        from:
                        <input type="text" size="4" name="ieee_year_from" value="<?php print isset($_SESSION['session_download_ieee_year_from']) ? htmlspecialchars($_SESSION['session_download_ieee_year_from']) : '1872'  ?>">
                        to:
                        <input type="text" size="4" name="ieee_year_to" value="<?php print isset($_SESSION['session_download_ieee_year_to']) ? htmlspecialchars($_SESSION['session_download_ieee_year_to']) : date('Y')  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threed">
                        Sort by:
                    </td>
                    <td class="threed">
                        <input type="radio" name="ieee_sort" value=""<?php print empty($_SESSION['session_download_ieee_sort']) ? ' checked' : ''  ?>>Relevance
                        <input type="radio" name="ieee_sort" value="desc_p_Publication_Year"<?php print (isset($_SESSION['session_download_ieee_sort']) && $_SESSION['session_download_ieee_sort'] == 'desc_p_Publication_Year') ? ' checked' : ''  ?>>Newest First
                        <input type="radio" name="ieee_sort" value="asc_p_Publication_Year"<?php print (isset($_SESSION['session_download_ieee_sort']) && $_SESSION['session_download_ieee_sort'] == 'asc_p_Publication_Year') ? ' checked' : ''  ?>>Oldest First
                        <input type="radio" name="ieee_sort" value="desc_p_Citation_Count"<?php print (isset($_SESSION['session_download_ieee_sort']) && $_SESSION['session_download_ieee_sort'] == 'desc_p_Citation_Count') ? ' checked' : ''  ?>>Most Cited
                        <input type="radio" name="ieee_sort" value="asc_p_Publication_Title"<?php print (isset($_SESSION['session_download_ieee_sort']) && $_SESSION['session_download_ieee_sort'] == 'asc_p_Publication_Title') ? ' checked' : ''  ?>>Publication Title A-Z
                        <input type="radio" name="ieee_sort" value="desc_p_Publication_Title"<?php print (isset($_SESSION['session_download_ieee_sort']) && $_SESSION['session_download_ieee_sort'] == 'desc_p_Publication_Title') ? ' checked' : ''  ?>>Publication Title Z-A
                    </td>
                </tr>
                <tr>
                    <td class="threed">
                        Save search as:
                    </td>
                    <td class="threed">
                        <input type="text" name="ieee_searchname" size="35" style="width:50%" value="<?php print isset($_SESSION['session_download_ieee_searchname']) ? htmlspecialchars($_SESSION['session_download_ieee_searchname']) : ''  ?>">
                        &nbsp;<button id="download-save"><i class="fa fa-save"></i> Save</button>
                    </td>
                    <td style="border: 0px; background-color: transparent">
                    </td>
                </tr>
            </table>
        </form>
        <br>
        <?php
        // CLEAN DOWNLOAD CACHE
        $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'page_*_download', GLOB_NOSORT);
        if (is_array($clean_files)) {
            foreach ($clean_files as $clean_file) {
                if (is_file($clean_file) && is_writable($clean_file))
                    @unlink($clean_file);
            }
        }
    }
}
?>