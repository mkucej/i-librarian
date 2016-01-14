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

    // reset button

    if (isset($_GET['newsearch'])) {

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_ieee'))
                unset($_SESSION[$key]);
        }
    }

    // save button

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['ieee_searchname'])) {

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

        database_connect(IL_DATABASE_PATH, 'library');

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

        database_connect(IL_DATABASE_PATH, 'library');

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
        unset($_SESSION['session_download_ieee_openaccess']);
        while (list($key, $value) = each($_GET)) {
            if (!empty($_GET[$key])) {
                $_SESSION['session_download_' . $key] = $value;
            }
        }

        if (!isset($_GET['from'])) {
            $_GET['from'] = '1';
            $from = $_GET['from'];
        } else {
            $from = intval($_GET['from']);
        }

        // PREPARE QUERY

        $url_string = 'action=search&';

        // Open Access
        $openaccess_string = '';
        if (isset($_GET['ieee_openaccess'])) {
            $openaccess_string = 'oa=1';
            $url_string .= '&ieee_openaccess=1';
        }

        // Publisher
        $publisher_string = '';
        if (isset($_GET['ieee_publisher'])) {
            $publisher_string = 'ctype=' . urlencode($_GET['ieee_publisher']);
            $url_string .= '&ieee_content_type=' . urlencode($_GET['ieee_publisher']);
        }

        // Content type
        $content_type_string = '';
        if (isset($_GET['ieee_content_type'])) {
            $content_type_string = 'ctype=' . urlencode($_GET['ieee_content_type']);
            $url_string .= '&ieee_content_type=' . urlencode($_GET['ieee_content_type']);
        }

        // Year range.
        $year_string = '';
        if (isset($_GET['ieee_range']) && $_GET['ieee_range'] == 'range') {

            $year_from = '1872';
            $year_to = date('Y');

            if (!empty($_GET['ieee_year_from'])) {
                $year_from = $_GET['ieee_year_from'];
            }

            if (!empty($_GET['ieee_year_to'])) {
                $year_to = $_GET['ieee_year_to'];
            }
        
            $year_string = 'pys=' . $year_from . '&pye=' . $year_to;
        
            $url_string .= '&ieee_range=range&'
                    . 'ieee_year_from=' . urlencode($_GET['ieee_year_from']) . '&'
                    . 'ieee_year_to=' . urlencode($_GET['ieee_year_to']);
        }

        // Sorting
        $sortby_string = '';
        if ($_GET['ieee_sort'] === 'year') {
            $sortby_string = 'sortorder=desc&sortfield=py';
            $url_string .= '&ieee_sort=year';
        } elseif ($_GET['ieee_sort'] === 'author') {
            $sortby_string = 'sortorder=asc&sortfield=au';
            $url_string .= '&ieee_sort=author';
        } elseif ($_GET['ieee_sort'] === 'title') {
            $sortby_string = 'sortorder=asc&sortfield=ti';
            $url_string .= '&ieee_sort=title';
        } elseif ($_GET['ieee_sort'] === 'publication') {
            $sortby_string = 'sortorder=asc&sortfield=jn';
            $url_string .= '&ieee_sort=publication';
        }

        // Pagination
        $pagination_string = 'rs=' . $from;

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

        // Search type.
        $query = '';
        if ($_GET['ieee_type'] === 'fulltext') {
            $query = urlencode('queryText=(' . $query_string . ')');
            $url_string .= '&ieee_type=fulltext';
        } elseif ($_GET['ieee_type'] === 'metadata') {
            $query = urlencode('md=(' . $query_string . ')');
            $url_string .= '&ieee_type=metadata';
        }

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

                database_connect(IL_DATABASE_PATH, 'library');

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

            $request_url = "http://ieeexplore.ieee.org/gateway/ipsSearch.jsp?"
                    . $query . "&"
                    . $content_type_string . "&"
                    . $sortby_string . "&"
                    . $pagination_string . "&"
                    . $year_string . "&"
                    . $openaccess_string . "&"
                    . $publisher_string;

            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

            if ($xml === FALSE) {
                die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
            }
        }

        // DISPLAY RESULTS

        if (is_object($xml)) {

            print '<div style="padding:2px;font-weight:bold">IEEE Xplore&reg; search';

            if (!empty($_SESSION['session_download_ieee_searchname']))
                print ': ' . htmlspecialchars($_SESSION['session_download_ieee_searchname']);

            print '</div>';

            libxml_use_internal_errors(true);

            $count = 0;
            $count = (string) $xml->totalfound;

            if (!empty($count) && $count > 0) {

                $maxfrom = $from + 24;
                if ($maxfrom > $count) {
                    $maxfrom = $count;
                }

                $microtime2 = microtime(true);
                $microtime = $microtime2 - $microtime1;
                $microtime = sprintf("%01.1f seconds", $microtime);

                print '<table class="top" style="margin-bottom:1px"><tr><td style="width: 13em">';

                print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                        '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=' . ($from - 25)) . '" style="color:black;display:block;width:100%">') .
                        '<i class="fa fa-caret-left"></i>&nbsp;Back'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '</td><td class="top" style="text-align: center">';

                print "Items " . $from . " - $maxfrom of $count in $microtime.";

                print '</td><td class="top" style="width: 14em">';

                $lastpage = 25 * floor($count / 25);

                print '<div class="ui-state-default ui-corner-top' . ($count > $maxfrom ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                        . ($count > $maxfrom ? '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                        '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                        . ($count > $maxfrom ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-default ui-corner-top' . ($count > $maxfrom ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                        . ($count > $maxfrom ? '<a class="navigation" href="' . htmlspecialchars("download_ieee.php?$url_string&from=" . ($from + 25)) . '" style="color:black;display:block;width:100%">' : '') .
                        '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                        . ($count > $maxfrom ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-default ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px;text-align: center">PgDn</div>';

                print '</td></tr></table>';

                print '<div class="alternating_row">';

                database_connect(IL_DATABASE_PATH, 'library');

                foreach ($xml->document as $item) {

                    $id = '';
                    $title = '';
                    $secondary_title = '';
                    $abstract = '';
                    $authors = '';
                    $last_name = array();
                    $first_name = array();
                    $affiliation = '';
                    $doi = '';
                    $year = '';
                    $volume = '';
                    $issue = '';
                    $pages = '';
                    $keywords = '';
                    $publisher = '';
                    $abstract = '';
                    $uid = array();
                    $reference_type = 'article';
                    $pdflink = '';

                    // TITLE
                    $title = (string) $item->title;

                    // IEEE ID
                    $id = (string) $item->arnumber;
                    $uid[] = 'IEEE:' . $id;

                    // AUTHORS
                    $authors = (string) $item->authors;
                    $author_array = explode(";", $authors);
                    foreach ($author_array as $author) {

                        $author = trim($author);
                        $comma = strpos($author, ",");
                        $space = strpos($author, " ");

                        if ($comma === FALSE) {

                            $first_name[] = trim(substr($author, 0, $space));
                            $last_name[] = trim(substr($author, $space + 1));
                        } else {

                            $last_name[] = trim(substr($author, 0, $comma));
                            $first_name[] = trim(substr($author, $comma + 1));
                    }
                    }

                    // Affiliation.
                    $affiliation = (string) $item->affiliations;

                    // DOI
                    $doi = (string) $item->doi;

                    // YEAR
                    $year = (string) $item->py;

                    // Secondary title.
                    $secondary_title = (string) $item->pubtitle;

                    // Volume.
                    $volume = (string) $item->volume;

                    // Issue.
                    $issue = (string) $item->issue;

                    // Pages.
                    $pages = (string) $item->spage;
                    $epage = (string) $item->epage;
                    if (!empty($epage) && $epage != $pages) {
                        $pages .= '-' . $epage;
                    }

                    // Keywords.
                    $keywords_array = array();
                    if (count($item->controlledterms->term) > 0) {

                        foreach ($item->controlledterms->term as $keyword) {

                            $keywords_array[] = (string) $keyword;
                        }

                        if (!empty($keywords_array)) {
                            $keywords = join(" / ", $keywords_array);
                        }
                    }

                    // Publisher.
                    $publisher = (string) $item->publisher;

                    // Abstract.
                    $abstract = (string) $item->abstract;
                    
                    // Reference type.
                    $reference_type = (string) $item->pubtype;

                    if ($reference_type == 'Conference Publications') {
                        
                        $reference_type = 'conference';
                        
                    } elseif ($reference_type == 'Journals & Magazines') {
                        
                        $reference_type = 'article';
                        
                    } elseif ($reference_type == 'Books & eBooks') {
                        
                        $reference_type = 'chapter';
                        
                    } elseif ($reference_type == 'Early Access Articles') {
                        
                        $reference_type = 'article';
                        
                    } elseif ($reference_type == 'Standards') {
                        
                        $reference_type = 'manual';
                    }
                    
                    // PDF link.
                    $pdflink = (string) $item->pdf;

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
                        
                        if (!empty($authors)) {
                            print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($authors) . '</div>';
                        }

                        if ($year != '')
                            print " ($year)";
                        
                        print '<br>';

                        print '<a href="' . htmlspecialchars('http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=' . $id) . '" target="_blank">IEEE</a>';
                        
                        if (!empty($doi)) {
                            print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://dx.doi.org/" . urlencode($doi)) . '" target="_blank">Publisher Website</a>';
                        }

                        if (!empty($pdflink)) {
                            print ' <b>&middot;</b> <a href="' . htmlspecialchars($pdflink) . '" target="_blank">PDF</a>';
                        }

                        print '</div>';
                        
                        print '<div class="abstract_container" style="display:none">';

                        ##########	print results into table	##########

                        print '<form enctype="application/x-www-form-urlencoded" action="upload.php" method="POST" class="fetch-form">';

                        print '<div class="items">';

                        print '<div>';
                        if (!empty($secondary_title))
                            print htmlspecialchars($secondary_title);
                        if (!empty($authors)) {
                            print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($authors) . '</div>';
                        }
                        if (!empty($year))
                            print " (" . htmlspecialchars($year) . ")";
                        if (!empty($volume))
                            print " <b>" . htmlspecialchars($volume) . "</b>";
                        if (!empty($issue))
                            print " <i>(" . htmlspecialchars($issue) . ")</i>";
                        if (!empty($pages))
                            print ": " . htmlspecialchars($pages);
                        print '</div>';

                        if (!empty($names_str))
                            print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names_str) . '</div>';

                        print '</div>';

                        print '<div class="abstract" style="padding:0 10px">';

                        !empty($abstract) ? print htmlspecialchars($abstract) : print 'No abstract available.';

                        print '</div><div class="items">';

                        ?>
                        <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
                        <input type="hidden" name="uid[]" value="<?php if (!empty($id)) print 'IEEE:' . htmlspecialchars($id); ?>">
                        <input type="hidden" name="reference_type" value="<?php if (!empty($reference_type)) print htmlspecialchars($reference_type); ?>">
                        <input type="hidden" name="last_name" value="<?php if (!empty($last_name)) print htmlspecialchars(json_encode($last_name), ENT_COMPAT,'UTF-8', FALSE); ?>">
                        <input type="hidden" name="first_name" value="<?php if (!empty($first_name)) print htmlspecialchars(json_encode($first_name), ENT_COMPAT,'UTF-8', FALSE); ?>">
                        <input type="hidden" name="affiliation" value="<?php if (!empty($affiliation)) print htmlspecialchars($affiliation, ENT_COMPAT,'UTF-8', FALSE); ?>">
                        <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title, ENT_COMPAT,'UTF-8', FALSE); ?>">
                        <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title, ENT_COMPAT,'UTF-8', FALSE); ?>">
                        <input type="hidden" name="year" value="<?php if (!empty($year)) print htmlspecialchars($year); ?>">
                        <input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
                        <input type="hidden" name="issue" value="<?php if (!empty($issue)) print htmlspecialchars($issue); ?>">
                        <input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
                        <input type="hidden" name="keywords" value="<?php if (!empty($keywords)) print htmlspecialchars($keywords); ?>">
                        <input type="hidden" name="publisher" value="<?php if (!empty($publisher)) print htmlspecialchars($publisher, ENT_COMPAT,'UTF-8', FALSE); ?>">
                        <input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract, ENT_COMPAT,'UTF-8', FALSE) : ""; ?>">
                        <input type="hidden" name="reference_type" value="<?php if (!empty($reference_type)) print htmlspecialchars($reference_type); ?>">

                        <?php
                        ##########	print full text links	##########

                        print '<b>Full text options:</b><br>';

                        print '<a href="' . htmlspecialchars('http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=' . $id) . '" target="_blank">IEEE</a>';

                        if (!empty($doi))
                            print ' <b>&middot;</b> <a href="' . htmlspecialchars('http://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publishers Website</a>';

                        print '<br><button class="save-item"><i class="fa fa-save"></i> Save</button> <button class="quick-save-item"><i class="fa fa-save"></i> Quick Save</button>';

                        print '</div></form>';

                        echo '</div>';

                        print '<div class="save_container"></div>';

                        print '</div>';
                    }
                }

                $dbHandle = null;

                print '</div>';

                print '<table class="top" style="margin-top:1px"><tr><td class="top" style="width: 50%">';

                print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                        '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                        . ($from == 1 ? '' : '<a class="navigation prevpage" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=' . ($from - 25)) . '" style="color:black;display:block;width:100%">') .
                        '<i class="fa fa-caret-left"></i>&nbsp;Back'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '</td><td class="top" style="width: 50%">';

                print '<div class="ui-state-default ui-corner-bottom' . ($count > $maxfrom ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                        . ($count > $maxfrom ? '<a class="navigation" href="' . htmlspecialchars('download_ieee.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                        '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                        . ($count > $maxfrom ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-default ui-corner-bottom' . ($count > $maxfrom ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                        . ($count > $maxfrom ? '<a class="navigation nextpage" href="' . htmlspecialchars("download_ieee.php?$url_string&from=" . ($from + 25)) . '" style="color:black;display:block;width:100%">' : '') .
                        '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                        . ($count > $maxfrom ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-default ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px;text-align: center">PgUp</div>';

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
            <div class="ui-state-default ui-corner-all" style="float:left;margin:4px 4px 2px 4px;padding:1px 4px">
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
                        <input type="radio" value="metadata" name="ieee_type"<?php print !isset($_SESSION['session_download_ieee_type']) || $_SESSION['session_download_ieee_type'] == 'metadata' ? ' checked' : ''  ?>>Metadata
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
                            <option value="">Anywhere
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
                            <option value="&quot;Author Affiliation&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Author Affiliation"') ? ' selected' : '') . '>
                                Author Affiliation
                            </option>
                            <option value="&quot;Article Number&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Article Number"') ? ' selected' : '') . '>
                                Article Number
                            </option>
                            <option value="&quot;DOI&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"DOI"') ? ' selected' : '') . '>
                                DOI
                            </option>
                            <option value="&quot;INSPEC Controlled Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"INSPEC Controlled Terms"') ? ' selected' : '') . '>
                                INSPEC Controlled Terms
                            </option>
                            <option value="&quot;ISBN&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"ISBN"') ? ' selected' : '') . '>
                                ISBN
                            </option>
                            <option value="&quot;ISSN&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"ISSN"') ? ' selected' : '') . '>
                                ISSN
                            </option>
                            <option value="&quot;Publication Year&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Publication Year"') ? ' selected' : '') . '>
                                Publication Year
                            </option>
                            <option value="&quot;Part Number&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Part Number"') ? ' selected' : '') . '>
                                Part Number
                            </option>
                            <option value="&quot;Thesaurus Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Thesaurus Terms"') ? ' selected' : '') . '>
                                Thesaurus Terms
                            </option>
                            <option value="&quot;Search Index Terms&quot;"' . ((isset($_SESSION['session_download_ieee_searchin' . $i]) && $_SESSION['session_download_ieee_searchin' . $i] == '"Search Index Terms"') ? ' selected' : '') . '>
                                Search Index Terms
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
                        <input type="radio" value="IEEE" name="ieee_publisher"<?php print (isset($_SESSION['session_download_ieee_publisher']) && $_SESSION['session_download_ieee_publisher'] === 'IEEE') ? ' checked' : ''  ?>>
                        IEEE
                        <input type="radio" value="AIP" name="ieee_publisher"<?php print (isset($_SESSION['session_download_ieee_publisher']) && $_SESSION['session_download_ieee_publisher'] === 'AIP') ? ' checked' : ''  ?>>
                        AIP
                        <input type="radio" value="IET" name="ieee_publisher"<?php print (isset($_SESSION['session_download_ieee_publisher']) && $_SESSION['session_download_ieee_publisher'] === 'IET') ? ' checked' : ''  ?>>
                        IET
                        <input type="radio" value="AVS" name="ieee_publisher"<?php print (isset($_SESSION['session_download_ieee_publisher']) && $_SESSION['session_download_ieee_publisher'] === 'AVS') ? ' checked' : ''  ?>>
                        AVS
                        <input type="radio" value="IBM" name="ieee_publisher"<?php print (isset($_SESSION['session_download_ieee_publisher']) && $_SESSION['session_download_ieee_publisher'] === 'IBM') ? ' checked' : ''  ?>>
                        IBM
                        <input type="radio" value="VDE" name="ieee_publisher"<?php print (isset($_SESSION['session_download_ieee_publisher']) && $_SESSION['session_download_ieee_publisher'] === 'VDE') ? ' checked' : ''  ?>>
                        VDE
                    </td>
                </tr>
                <tr>
                    <td class="threed">
                        Content Types:
                    </td>
                    <td class="threed">
                        <div style="float:left;margin-right:10px">
                            <input type="radio" value="Conferences" name="ieee_content_type"<?php print (isset($_SESSION['session_download_ieee_content_type']) && $_SESSION['session_download_ieee_content_type'] === 'Conferences') ? ' checked' : ''  ?>>
                            Conference Publications<br>
                            <input type="radio" value="Journals" name="ieee_content_type"<?php print (isset($_SESSION['session_download_ieee_content_type']) && $_SESSION['session_download_ieee_content_type'] === 'Journals') ? ' checked' : ''  ?>>
                            Journals &amp; Magazines<br>
                            <input type="radio" value="Books" name="ieee_content_type"<?php print (isset($_SESSION['session_download_ieee_content_type']) && $_SESSION['session_download_ieee_content_type'] === 'Books') ? ' checked' : ''  ?>>
                            Books &amp; eBooks
                        </div>
                        <div>
                            <input type="radio" value="Early Access" name="ieee_content_type"<?php print (isset($_SESSION['session_download_ieee_content_type']) && $_SESSION['session_download_ieee_content_type'] === 'Early Access') ? ' checked' : ''  ?>>
                            Early Access Articles<br>
                            <input type="radio" value="Standards" name="ieee_content_type"<?php print (isset($_SESSION['session_download_ieee_content_type']) && $_SESSION['session_download_ieee_content_type'] === 'Standards') ? ' checked' : ''  ?>>
                            Standards<br>
                            <input type="radio" value="Educational Courses" name="ieee_content_type"<?php print (isset($_SESSION['session_download_ieee_content_type']) && $_SESSION['session_download_ieee_content_type'] === 'Educational Courses') ? ' checked' : ''  ?>>
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
                        Sort:
                    </td>
                    <td class="threed">
                        <input type="radio" value="year" name="ieee_sort"<?php print !isset($_SESSION['session_download_ieee_sort']) || $_SESSION['session_download_ieee_sort'] == 'year' ? ' checked' : ''  ?>> Newest first
                        <input type="radio" value="title" name="ieee_sort"<?php print isset($_SESSION['session_download_ieee_sort']) && $_SESSION['session_download_ieee_sort'] == 'title' ? ' checked' : ''  ?>> Title A&#x2192;Z
                        <input type="radio" value="publication" name="ieee_sort"<?php print isset($_SESSION['session_download_ieee_sort']) && $_SESSION['session_download_ieee_sort'] == 'publication' ? ' checked' : ''  ?>> Publication A&#x2192;Z
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