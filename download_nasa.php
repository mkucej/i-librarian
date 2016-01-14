<?php
include_once 'data.php';
include_once 'functions.php';

if (isset($_SESSION['auth'])) {

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

    $checkboxes = array('nasa_db_key_AST', 'nasa_db_key_PHY', 'nasa_db_key_PRE',
        'nasa_abstract', 'nasa_article', 'nasa_preprint_link',
        'nasa_gif_link', 'nasa_article_link', 'nasa_open_link');

    while (list($key, $value) = each($checkboxes)) {

        if (isset($_GET['nasa_last_search']) && !isset($_GET[$value]))
            $_GET[$value] = '';
    }

########## reset button ##############

    if (isset($_GET['newsearch'])) {

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_nasa'))
                unset($_SESSION[$key]);
        }
    }

########## save button ##############

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['nasa_searchname'])) {

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
        $searchname = "nasaads#" . $_GET['nasa_searchname'];

        $stmt->execute();

        $keys = array(
            'nasa_object', 'nasa_obj_logic', 'nasa_obj_req',
            'nasa_author', 'nasa_aut_logic', 'nasa_aut_req',
            'nasa_title', 'nasa_ttl_logic', 'nasa_ttl_req',
            'nasa_text', 'nasa_txt_logic', 'nasa_txt_req',
            'nasa_keyword', 'nasa_kwd_logic', 'nasa_kwd_req',
            'nasa_start_year', 'nasa_end_year',
            'nasa_volume', 'nasa_page', 'nasa_bibcode',
            'nasa_db_key_AST', 'nasa_db_key_PHY', 'nasa_db_key_PRE',
            'nasa_edat', 'nasa_data_and',
            'nasa_abstract', 'nasa_article', 'nasa_preprint_link',
            'nasa_gif_link', 'nasa_article_link', 'nasa_open_link',
            'nasa_sort');

        while (list($key, $field) = each($keys)) {

            if (!empty($_GET[$field])) {

                $user = $_SESSION['user_id'];
                $searchname = "nasaads#" . $_GET['nasa_searchname'];
                $searchfield = $field;
                $searchvalue = $_GET[$field];

                $stmt2->execute();
            }
        }

        $user = $_SESSION['user_id'];
        $searchname = "nasaads#" . $_GET['nasa_searchname'];
        $searchfield = 'nasa_last_search';
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
        $searchname = "nasaads#" . $_GET['saved_search'];

        $stmt->execute();

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_nasa'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
        $_GET['load'] = 'Load';

        $_GET['nasa_searchname'] = substr($searchname, 8);

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
        $searchname = "nasaads#" . $_GET['saved_search'];

        $stmt->execute();

        $dbHandle->commit();

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_nasa'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
    }

########## main body ##############

    $microtime1 = microtime(true);

    $keys = array();

    $keys = array(
        'nasa_object', 'nasa_obj_logic', 'nasa_obj_req',
        'nasa_author', 'nasa_aut_logic', 'nasa_aut_req',
        'nasa_title', 'nasa_ttl_logic', 'nasa_ttl_req',
        'nasa_text', 'nasa_txt_logic', 'nasa_txt_req',
        'nasa_keyword', 'nasa_kwd_logic', 'nasa_kwd_req',
        'nasa_start_year', 'nasa_end_year',
        'nasa_volume', 'nasa_page', 'nasa_bibcode',
        'nasa_db_key_AST', 'nasa_db_key_PHY', 'nasa_db_key_PRE',
        'nasa_edat', 'nasa_last_search',
        'nasa_data_and',
        'nasa_abstract', 'nasa_article', 'nasa_preprint_link',
        'nasa_gif_link', 'nasa_article_link', 'nasa_open_link',
        'nasa_sort', 'nasa_searchname');

    while (list($key, $field) = each($keys)) {

        if (isset($_GET[$field]))
            $_SESSION['session_download_' . $field] = $_GET[$field];
    }

########## register variables ##############

    $parameter_string = '';

    if (!isset($_GET['from'])) {
        $from = '1';
    } else {
        $from = intval($_GET['from']);
    }

########## prepare NASA ADS query ##############

    $edat_string = '';

    if (!empty($_GET['nasa_edat'])) {

        if ($_GET['nasa_edat'] == 'last search') {

            $entry_day = date("d", $_GET['nasa_last_search']);
            $entry_month = date("m", $_GET['nasa_last_search']);
            $entry_year = date("Y", $_GET['nasa_last_search']);
        } else {

            $entry_day = date("d", time() - ($_GET['nasa_edat'] - 1) * 86400);
            $entry_month = date("m", time() - ($_GET['nasa_edat'] - 1) * 86400);
            $entry_year = date("Y", time() - ($_GET['nasa_edat'] - 1) * 86400);
        }

        $edat_string = "start_entry_day=$entry_day&start_entry_mon=$entry_month&start_entry_year=$entry_year";
        $edat_string .= "&end_entry_day=" . date("d") . "&end_entry_mon=" . date("m") . "&end_entry_year=" . date("Y");
    }

    $object_string = "sim_query=YES&ned_query=YES&adsobj_query=YES";

    $parameter_array = array();
    $url_array = array();
    $keys = array();

    $keys = array(
        'nasa_object', 'nasa_obj_logic', 'nasa_obj_req',
        'nasa_author', 'nasa_aut_logic', 'nasa_aut_req',
        'nasa_title', 'nasa_ttl_logic', 'nasa_ttl_req',
        'nasa_text', 'nasa_txt_logic', 'nasa_txt_req',
        'nasa_keyword', 'nasa_kwd_logic', 'nasa_kwd_req',
        'nasa_start_year', 'nasa_end_year',
        'nasa_volume', 'nasa_page', 'nasa_bibcode',
        'nasa_db_key_AST', 'nasa_db_key_PHY', 'nasa_db_key_PRE',
        'nasa_edat', 'nasa_last_search',
        'nasa_data_and',
        'nasa_abstract', 'nasa_article', 'nasa_preprint_link',
        'nasa_gif_link', 'nasa_article_link', 'nasa_open_link',
        'nasa_sort');

    while (list($key, $field) = each($keys)) {

        if (!empty($_GET[$field]))
            $url_array[] = "$field=" . urlencode($_GET[$field]);

        if (!empty($_GET[$field]) && strstr($field, 'nasa_db_key_')) {

            $parameter_array[] = substr($field, 5, -4) . "=" . urlencode($_GET[$field]);
        } elseif (!empty($_GET[$field])) {

            $parameter_array[] = substr($field, 5) . "=" . urlencode($_GET[$field]);
        }
    }

    $parameter_string = join("&", $parameter_array);
    $url_string = join("&", $url_array);
    if (!empty($_GET['proxystr']))
        $url_string .= '&proxystr=' . urlencode($_GET['proxystr']);

########## search NASA ADS ##############

    if (!empty($parameter_string) && empty($_GET['load']) && empty($_GET['save']) && empty($_GET['delete'])) {

        ############# caching ################

        $cache_name = cache_name();
        $cache_name .= '_download';
        $db_change = database_change(array(
            'library'
        ));
        cache_start($db_change);

        ########## register the time of search ##############

        if (!empty($_SESSION['session_download_nasa_searchname']) && $from == 1) {

            database_connect(IL_DATABASE_PATH, 'library');

            $stmt = $dbHandle->prepare("UPDATE searches SET searchvalue=:searchvalue WHERE userID=:user AND searchname=:searchname AND searchfield='nasa_last_search'");

            $stmt->bindParam(':user', $user, PDO::PARAM_STR);
            $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);
            $stmt->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

            $user = $_SESSION['user_id'];
            $searchname = "nasaads#" . $_SESSION['session_download_nasa_searchname'];
            $searchvalue = time();

            $stmt->execute();
        }

        ########## search NASA ADS ##############

        $request_url = "http://adsabs.harvard.edu/cgi-bin/abs_connect?$parameter_string&$object_string&$edat_string&data_type=XML&return_req=no_params&start_nr=$from&nr_to_return=10";

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
        if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

        
        foreach ($xml->attributes() as $a => $b) {

            if ($a == 'selected') {
                $count = $b;
                break;
            }
        }
    }

########## display search result summaries ##############

    if (!empty($xml)) {

        print '<div style="padding:2px;font-weight:bold">NASA ADS search';

        if (!empty($_SESSION['session_download_nasa_searchname']))
            print ': ' . htmlspecialchars($_SESSION['session_download_nasa_searchname']);

        print '</div>';

        if (!empty($count) && $count > 0) {

            $maxfrom = $from + 9;
            if ($maxfrom > $count)
                $maxfrom = $count;

            $microtime2 = microtime(true);
            $microtime = $microtime2 - $microtime1;
            $microtime = sprintf("%01.1f seconds", $microtime);

            print '<table class="top" style="margin-bottom:1px"><tr><td style="width: 13em">';

            print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_nasa.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_nasa.php?' . $url_string . '&from=' . ($from - 10)) . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="text-align: center">';

            print "Items $from - $maxfrom of $count in $microtime.";

            print '</td><td class="top" style="width: 14em">';

            (($count % 10) == 0) ? $lastpage = 1 + $count - 10 : $lastpage = 1 + $count - ($count % 10);

            print '<div class="ui-state-default ui-corner-top' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars('download_nasa.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_nasa.php?$url_string&from=" . ($from + 10)) . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px;text-align: center">PgDn</div>';

            print '</td></tr></table>';

            print '<div class="alternating_row">';

            database_connect(IL_DATABASE_PATH, 'library');
            $jdbHandle = new PDO('sqlite:journals.sq3');

            foreach ($xml->record as $record) {

                $bibcode = '';
                $doi = '';
                $title = '';
                $secondary_title = '';
                $pub_date = '';
                $eprintid = '';
                $volume = '';
                $pages = '';
                $year = '';
                $abstract = '';
                $nasa_url = '';
                $ejournal_url = '';
                $preprint_url = '';
                $gif_url = '';
                $affiliation = '';
                $authors = array();
                $names = '';
                $keywords = '';
                $uid = '';
                $url = '';
                $name_array = array();
                $keywords_array = array();
                $new_authors = array();

                $bibcode = $record->bibcode;
                $doi = $record->DOI;
                $title = $record->title;

                $secondary_title = $record->journal;
                if (strstr($secondary_title, ","))
                    $secondary_title = substr($secondary_title, 0, strpos($secondary_title, ','));

                $eprintid = $record->eprintid;
                if (!empty($eprintid))
                    $eprintid = substr($eprintid, strpos($eprintid, ":") + 1);

                if (strstr($secondary_title, "arXiv")) {

                    $eprintid = substr($secondary_title, strpos($secondary_title, ":") + 1);
                    $secondary_title = 'eprint';
                }

                $volume = $record->volume;
                $pages = $record->page;

                $year = $record->pubdate;
                $year = date('Y-m-d', strtotime($year));

                $abstract = $record->abstract;
                $nasa_url = $record->url;

                foreach ($record->link as $links) {

                    foreach ($links->attributes() as $a => $b) {

                        if ($a == 'type' && $b == 'EJOURNAL') {
                            $ejournal_url = $links->url;
                        } elseif ($a == 'type' && $b == 'PREPRINT') {
                            $preprint_url = $links->url;
                        } elseif ($a == 'type' && $b == 'GIF') {
                            $gif_url = $links->url;
                        }
                    }
                }

                $affiliation = $record->affiliation;

                $authors = $record->author;

                $last_name = array();
                $first_name = array();
                if (!empty($authors)) {

                    foreach ($authors as $author) {

                        $name_array[] = $author;
                        $auth_arr = explode(',', $author);
                        if(!empty($auth_arr[0]))
                            $last_name[] = trim($auth_arr[0]);
                        if(!empty($auth_arr[1]))
                            $first_name[] = trim($auth_arr[1]);
                    }
                }

                if (isset($name_array))
                    $names = join("; ", $name_array);

                $keywords = $record->keywords;

                if (!empty($keywords)) {

                    foreach ($keywords as $keyword) {

                        $keywords_array[] = $keyword->keyword;
                    }
                }

                if (isset($keywords_array))
                    $keywords = join(" / ", $keywords_array);

                $uid_array = array();
                if (!empty($bibcode))
                    $uid_array[] = "NASAADS:$bibcode";
                if (!empty($eprintid))
                    $uid_array[] = "ARXIV:$eprintid";

                $url_array = array();
                $url_array[] = $nasa_url;
                if (!empty($eprintid))
                    $url_array[] = "http://arxiv.org/abs/$eprintid";

                if (!empty($bibcode) && !empty($title)) {

                    //JOURNAL RATING
                    if (!empty($secondary_title)) {
                        $secondary_title_query = $jdbHandle->quote($secondary_title);
                        if (stripos($secondary_title, 'the') === 0)
                            $secondary_title_query = $jdbHandle->quote(substr($secondary_title, 4));
                        $result = $jdbHandle->query("SELECT rating FROM journals WHERE full_name=upper($secondary_title_query) LIMIT 1");
                        $rating = $result->fetchColumn();
                        if (empty($rating))
                            $rating = 0;
                        $result = null;
                    }

                    ########## gray out existing records ##############

                    $existing_id = '';
                    $title_query = $dbHandle->quote(substr($title, 0, -1) . "%");
                    $result_query = $dbHandle->query("SELECT id FROM library WHERE title LIKE $title_query AND length(title) <= length($title_query)+2 LIMIT 1");
                    $existing_id = $result_query->fetchColumn();

                    //IS FLAGGED?
                    $relation = 0;
                    $id_query = $dbHandle->quote($bibcode);
                    $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . intval($_SESSION['user_id']) . " AND database='nasaads' AND uid=" . $id_query . " LIMIT 1");
                    if ($result)
                        $relation = $result->fetchColumn();
                    $result = null;

                    print '<div class="items" data-uid="' . htmlspecialchars($bibcode) . '" style="padding:0">';
                    
                    print '<div class="ui-widget-header" style="border-left:0;border-right:0">';

                    print '<div class="flag ' . (($relation == 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="float:right;margin:4px"><i class="fa fa-flag"></i></div>';

                    print '<div class="titles brief" style="overflow:hidden;margin-right:30px';

                    if (is_numeric($existing_id))
                        print ';color: #777';

                    print '">' . $title . '</div>';
                    
                    print '</div>';

                    print '<div class="firstcontainer items">';

                    print '<i class="star fa fa-star ' . (($rating >= 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"></i>&nbsp;';
                    print '<i class="star fa fa-star ' . (($rating >= 2) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"></i>&nbsp;';
                    print '<i class="star fa fa-star ' . (($rating == 3) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"></i>';

                    print '&nbsp;<b>&middot;</b> ';

                    print htmlspecialchars($secondary_title);

                    if ($year != '')
                        print " ($year)";

                    if (!empty($names))
                        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names) . '</div>';

                    print '<a href="' . htmlspecialchars('http://adsabs.harvard.edu/abs/' . urlencode($bibcode)) . '" target="_blank">NASA ADS</a>';
                    if (!empty($doi))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars('http://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publisher Website</a>';
                    if (!empty($eprintid))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://arxiv.org/pdf/$eprintid") . '" target="_blank">PDF preprint</a> (ArXiv)';

                    print '</div>';

                    print '<div class="abstract_container" style="display:none">';

                    ##########	print results into table	##########

                    print '<form enctype="application/x-www-form-urlencoded" action="upload.php" method="POST" class="fetch-form">';

                    print '<div class="items">';

                    if (!empty($secondary_title))
                        print htmlspecialchars($secondary_title);
                    if (!empty($year))
                        print " (" . htmlspecialchars($year) . ")";
                    if (!empty($volume))
                        print " " . htmlspecialchars($volume);
                    if (!empty($issue))
                        print " ($issue)";
                    if (!empty($pages))
                        print ": " . htmlspecialchars($pages);
                    print '<br>';

                    if (!empty($names)) {
                        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names) . '</div>';
                        $array = explode(';', $names);
                        $array = array_filter($array);
                        if (!empty($array)) {
                            foreach ($array as $author) {
                                $last = '';
                                $first = '';
                                $array2 = explode(',', $author);
                                $last = trim($array2[0]);
                                if (isset($array2[1])) $first = trim($array2[1]);
                                $new_authors[] = 'L:"' . $last . '",F:"' . $first . '"';
                            }
                            $names = join(';', $new_authors);
                        }
                    }
                    if (!empty($affiliation))
                        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($affiliation) . '</div>';

                    print '</div>';

                    print '<div class="abstract" style="padding:0 10px">';

                    !empty($abstract) ? print htmlspecialchars($abstract)  : print 'No abstract available.';

                    print '</div><div class="items">';

                    foreach ($uid_array as $uid) {
                        print '<input type="hidden" name="uid[]" value="' . htmlspecialchars($uid) . '">';
                    }
                    foreach ($url_array as $url) {
                        print '<input type="hidden" name="url[]" value="' . htmlspecialchars($url) . '">';
                    }
                    ?>
                    <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
                    <input type="hidden" name="last_name" value="<?php if (!empty($last_name)) print htmlspecialchars(json_encode($last_name)); ?>">
                    <input type="hidden" name="first_name" value="<?php if (!empty($first_name)) print htmlspecialchars(json_encode($first_name)); ?>">
                    <input type="hidden" name="affiliation" value="<?php if (!empty($affiliation)) print htmlspecialchars($affiliation); ?>">
                    <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
                    <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
                    <input type="hidden" name="journal_abbr" value="<?php if (!empty($journal_abbr)) print htmlspecialchars($journal_abbr); ?>">
                    <input type="hidden" name="year" value="<?php if (!empty($year)) print htmlspecialchars($year); ?>">
                    <input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
                    <input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
                    <input type="hidden" name="keywords" value="<?php if (!empty($keywords)) print htmlspecialchars($keywords); ?>">
                    <input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract) : "No abstract available."; ?>">
                    <input type="hidden" name="form_new_file_link" value="<?php print !empty($eprintid) ? htmlspecialchars("http://arxiv.org/pdf/" . $eprintid) : ""; ?>">
                    <?php
                    ##########	print full text links	##########

                    print '<b>Full text options:</b><br>';

                    print '<a href="' . htmlspecialchars('http://adsabs.harvard.edu/abs/' . urlencode($bibcode)) . '" target="_blank">NASA ADS</a>';
                    if (!empty($ejournal_url))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars($ejournal_url) . '" target="_blank">Full text</a>';
                    if (!empty($preprint_url))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars($preprint_url) . '" target="_blank">Preprint</a>';
                    if (!empty($doi))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://dx.doi.org/" . urlencode($doi)) . '" target="_blank">Publisher Website</a>';
                    if (!empty($gif_url))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars($gif_url) . '" target="_blank">Scanned article</a>';
                    if (!empty($eprintid))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://arxiv.org/pdf/$eprintid") . '" target="_blank">PDF preprint</a> (ArXiv)';

                    print '<br><button class="save-item"><i class="fa fa-save"></i> Save</button> <button class="quick-save-item"><i class="fa fa-save"></i> Quick Save</button>';

                    print '</div></form>';

                    print '</div>';

                    print '<div class="save_container"></div>';

                    print '</div>';
                }
            }

            $dbHandle = null;
            $jdbHandle = null;

            print '</div>';

            print '<table class="top" style="margin-top:1px"><tr><td class="top" style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_nasa.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation prevpage" href="' . htmlspecialchars('download_nasa.php?' . $url_string . '&from=' . ($from - 10)) . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars('download_nasa.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count >= $from + 10 ? '<a class="navigation nextpage" href="' . htmlspecialchars("download_nasa.php?$url_string&from=" . ($from + 10)) . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px;text-align: center">PgUp</div>';

            print '</td></tr></table><br>';
        } else {
            print '<div style="position:relative;top:43%;left:40%;color:#bbbbbb;font-size:28px;width:200px"><b>No Items</b></div>';
        }

        ############# caching #############
        cache_store();
    } else {

########## input table ##############
        ?>
        <div style="text-align: left">
            <form enctype="application/x-www-form-urlencoded" action="download_nasa.php" method="GET" id="download-form">
                <div class="ui-state-default ui-corner-all" style="float:left;margin:4px 4px 2px 4px;padding:1px 4px">
                    <a href="http://adsabs.harvard.edu/abstract_service.html" target="_blank" style="display:block"><i class="fa fa-external-link"></i> NASA ADS</a>
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
                        <td class="threed" style="width:12em">
                            Objects:
                        </td>
                        <td class="threed">
                            <textarea cols=50 rows=2 name="nasa_object" style="width:99%"><?php print isset($_SESSION['session_download_nasa_object']) ? htmlspecialchars($_SESSION['session_download_nasa_object']) : ''; ?></textarea>
                            <input type="radio" name="nasa_obj_logic" value="AND" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_obj_logic']) && $_SESSION['session_download_nasa_obj_logic'] == 'AND') ? 'checked' : ''  ?>>AND
                            <input type="radio" name="nasa_obj_logic" value="OR" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_obj_logic']) && $_SESSION['session_download_nasa_obj_logic'] == 'OR') ? 'checked' : ''  ?>>OR
                        </td>
                        <td class="threed" style="width:6em">
                            <input type="radio" name="nasa_obj_req" value="YES" <?php print (isset($_SESSION['session_download_nasa_obj_req']) && $_SESSION['session_download_nasa_obj_req'] == 'YES') ? 'checked' : ''  ?>>AND<br>
                            <input type="radio" name="nasa_obj_req" <?php print (!isset($_SESSION['session_download_nasa_obj_req'])) ? 'checked' : ''  ?>>OR&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Authors:
                        </td>
                        <td class="threed">
                            <textarea cols=50 rows=2 name="nasa_author" style="width:99%"><?php print isset($_SESSION['session_download_nasa_author']) ? htmlspecialchars($_SESSION['session_download_nasa_author']) : ''; ?></textarea>
                            <input type="radio" name="nasa_aut_logic" value="AND" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_aut_logic']) && $_SESSION['session_download_nasa_aut_logic'] == 'AND') ? 'checked' : ''  ?>>AND
                            <input type="radio" name="nasa_aut_logic" value="OR" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_aut_logic']) && $_SESSION['session_download_nasa_aut_logic'] == 'OR') ? 'checked' : ''  ?>>OR
                            <input type="radio" name="nasa_aut_logic" value="SIMPLE" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_aut_logic']) && $_SESSION['session_download_nasa_aut_logic'] == 'SIMPLE') ? 'checked' : ''  ?>>simple logic
                        </td>
                        <td class="threed">
                            <input type="radio" name="nasa_aut_req" value="YES" <?php print (isset($_SESSION['session_download_nasa_aut_req']) && $_SESSION['session_download_nasa_aut_req'] == 'YES') ? 'checked' : ''  ?>>AND<br>
                            <input type="radio" name="nasa_aut_req" <?php print (!isset($_SESSION['session_download_nasa_aut_req'])) ? 'checked' : ''  ?>>OR&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Title:
                        </td>
                        <td class="threed">
                            <textarea cols=50 rows=2 name="nasa_title" style="width:99%"><?php print isset($_SESSION['session_download_nasa_title']) ? htmlspecialchars($_SESSION['session_download_nasa_title']) : ''; ?></textarea>
                            <input type="radio" name="nasa_ttl_logic" value="AND" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_ttl_logic']) && $_SESSION['session_download_nasa_ttl_logic'] == 'AND') ? 'checked' : ''  ?>>AND
                            <input type="radio" name="nasa_ttl_logic" value="OR" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_ttl_logic']) && $_SESSION['session_download_nasa_ttl_logic'] == 'OR') ? 'checked' : ''  ?>>OR
                            <input type="radio" name="nasa_ttl_logic" value="SIMPLE" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_ttl_logic']) && $_SESSION['session_download_nasa_ttl_logic'] == 'SIMPLE') ? 'checked' : ''  ?>>simple logic
                            <input type="radio" name="nasa_ttl_logic" value="BOOL" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_ttl_logic']) && $_SESSION['session_download_nasa_ttl_logic'] == 'BOOL') ? 'checked' : ''  ?>>boolean logic
                        </td>
                        <td class="threed">
                            <input type="radio" name="nasa_ttl_req" value="YES" <?php print (isset($_SESSION['session_download_nasa_ttl_req']) && $_SESSION['session_download_nasa_ttl_req'] == 'YES') ? 'checked' : ''  ?>>AND<br>
                            <input type="radio" name="nasa_ttl_req" <?php print (!isset($_SESSION['session_download_nasa_ttl_req'])) ? 'checked' : ''  ?>>OR&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Abstract:
                        </td>
                        <td class="threed">
                            <textarea cols=50 rows=2 name="nasa_text" style="width:99%"><?php print isset($_SESSION['session_download_nasa_text']) ? htmlspecialchars($_SESSION['session_download_nasa_text']) : ''; ?></textarea>
                            <input type="radio" name="nasa_txt_logic" value="AND" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_txt_logic']) && $_SESSION['session_download_nasa_txt_logic'] == 'AND') ? 'checked' : ''  ?>>AND
                            <input type="radio" name="nasa_txt_logic" value="OR" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_txt_logic']) && $_SESSION['session_download_nasa_txt_logic'] == 'OR') ? 'checked' : ''  ?>>OR
                            <input type="radio" name="nasa_txt_logic" value="SIMPLE" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_txt_logic']) && $_SESSION['session_download_nasa_txt_logic'] == 'SIMPLE') ? 'checked' : ''  ?>>simple logic
                            <input type="radio" name="nasa_txt_logic" value="BOOL" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_txt_logic']) && $_SESSION['session_download_nasa_txt_logic'] == 'BOOL') ? 'checked' : ''  ?>>boolean logic
                        </td>
                        <td class="threed">
                            <input type="radio" name="nasa_txt_req" value="YES" <?php print (isset($_SESSION['session_download_nasa_txt_req']) && $_SESSION['session_download_nasa_txt_req'] == 'YES') ? 'checked' : ''  ?>>AND<br>
                            <input type="radio" name="nasa_txt_req" <?php print (!isset($_SESSION['session_download_nasa_txt_req'])) ? 'checked' : ''  ?>>OR&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Keywords:
                        </td>
                        <td class="threed">
                            <textarea cols=50 rows=2 name="nasa_keyword" style="width:99%"><?php print isset($_SESSION['session_download_nasa_keyword']) ? htmlspecialchars($_SESSION['session_download_nasa_keyword']) : ''; ?></textarea>
                            <input type="radio" name="nasa_kwd_logic" value="AND" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_kwd_logic']) && $_SESSION['session_download_nasa_kwd_logic'] == 'AND') ? 'checked' : ''  ?>>AND
                            <input type="radio" name="nasa_kwd_logic" value="OR" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_kwd_logic']) && $_SESSION['session_download_nasa_kwd_logic'] == 'OR') ? 'checked' : ''  ?>>OR
                            <input type="radio" name="nasa_kwd_logic" value="SIMPLE" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_kwd_logic']) && $_SESSION['session_download_nasa_kwd_logic'] == 'SIMPLE') ? 'checked' : ''  ?>>simple logic
                            <input type="radio" name="nasa_kwd_logic" value="BOOL" class="uploadcheckbox" <?php print (isset($_SESSION['session_download_nasa_kwd_logic']) && $_SESSION['session_download_nasa_kwd_logic'] == 'BOOL') ? 'checked' : ''  ?>>boolean logic
                        </td>
                        <td class="threed">
                            <input type="radio" name="nasa_kwd_req" value="YES" <?php print (isset($_SESSION['session_download_nasa_kwd_req']) && $_SESSION['session_download_nasa_kwd_req'] == 'YES') ? 'checked' : ''  ?>>AND<br>
                            <input type="radio" name="nasa_kwd_req" <?php print (!isset($_SESSION['session_download_nasa_kwd_req'])) ? 'checked' : ''  ?>>OR&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Years published:
                        </td>
                        <td class="threed">
                            <input type="text" name="nasa_start_year" size="5" value="<?php print isset($_SESSION['session_download_nasa_start_year']) ? htmlspecialchars($_SESSION['session_download_nasa_start_year']) : ''; ?>">
                            - <input type="text" name="nasa_end_year" size="5" value="<?php print isset($_SESSION['session_download_nasa_end_year']) ? htmlspecialchars($_SESSION['session_download_nasa_end_year']) : ''; ?>">
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Volume:
                        </td>
                        <td class="threed">
                            <input type="text" name="nasa_volume" size="5" value="<?php print isset($_SESSION['session_download_nasa_volume']) ? htmlspecialchars($_SESSION['session_download_nasa_volume']) : ''; ?>">
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            First page:
                        </td>
                        <td class="threed">
                            <input type="text" name="nasa_page" size="5" value="<?php print isset($_SESSION['session_download_nasa_page']) ? htmlspecialchars($_SESSION['session_download_nasa_page']) : ''; ?>">
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Bibcode:
                        </td>
                        <td class="threed">
                            <input type="text" name="nasa_bibcode" size="65" value="<?php print isset($_SESSION['session_download_nasa_bibcode']) ? htmlspecialchars($_SESSION['session_download_nasa_bibcode']) : ''; ?>">
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 0px; background-color: transparent">
                            Limits and sorting:
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    <tr>
                        <td class="threed">
                            Databases to query:
                        </td>
                        <td class="threed">
                            <input type="checkbox" name="nasa_db_key_AST" value="AST" <?php print (!empty($_SESSION['session_download_nasa_db_key_AST'])) ? 'checked' : ''  ?>> Astronomy
                            <input type="checkbox" name="nasa_db_key_PHY" value="PHY" <?php print (!empty($_SESSION['session_download_nasa_db_key_PHY'])) ? 'checked' : ''  ?>> Physics
                            <input type="checkbox" name="nasa_db_key_PRE" value="PRE" <?php print (!empty($_SESSION['session_download_nasa_db_key_PRE'])) ? 'checked' : ''  ?>> ArXiv
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Search within the last:
                        </td>
                        <td class="threed">
                            <select name="nasa_edat">
                                <option value=""></option>
                                <option value="1" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '1' ? 'selected' : ''; ?>>1 day</option>
                                <option value="2" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '2' ? 'selected' : ''; ?>>2 days</option>
                                <option value="5" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '5' ? 'selected' : ''; ?>>5 days</option>
                                <option value="7" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '7' ? 'selected' : ''; ?>>1 week</option>
                                <option value="14" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '14' ? 'selected' : ''; ?>>2 weeks</option>
                                <option value="31" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '31' ? 'selected' : ''; ?>>1 month</option>
                                <option value="92" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '92' ? 'selected' : ''; ?>>3 months</option>
                                <option value="183" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '183' ? 'selected' : ''; ?>>6 months</option>
                                <option value="365" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == '365' ? 'selected' : ''; ?>>1 year</option>
                                <option value="last search" <?php print isset($_SESSION['session_download_nasa_edat']) && $_SESSION['session_download_nasa_edat'] == 'last search' ? 'selected' : ''; ?>>since last search</option>
                            </select>
                            <input type="hidden" name="nasa_last_search" value="<?php print isset($_SESSION['session_download_nasa_last_search']) ? $_SESSION['session_download_nasa_last_search'] : '1'; ?>">
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Select references with:
                        </td>
                        <td class="threed">
                            <input type="radio" name="nasa_data_and" value="NO" <?php print isset($_SESSION['session_download_nasa_data_and']) && $_SESSION['session_download_nasa_data_and'] == 'NO' ? 'checked' : ''; ?>>at least one of the following (OR)
                            <input type="radio" name="nasa_data_and" value="YES" <?php print isset($_SESSION['session_download_nasa_data_and']) && $_SESSION['session_download_nasa_data_and'] == 'YES' ? 'checked' : ''; ?>>all of the following (AND)<br>
                            <input type="checkbox" name="nasa_abstract" value="YES" <?php print isset($_SESSION['session_download_nasa_abstract']) && $_SESSION['session_download_nasa_abstract'] == 'YES' ? 'checked' : ''; ?>>abstract<br>
                            <input type="checkbox" name="nasa_article" value="YES" <?php print isset($_SESSION['session_download_nasa_article']) && $_SESSION['session_download_nasa_article'] == 'YES' ? 'checked' : ''; ?>>full text article<br>
                            <input type="checkbox" name="nasa_preprint_link" value="YES" <?php print isset($_SESSION['session_download_nasa_preprint_link']) && $_SESSION['session_download_nasa_preprint_link'] == 'YES' ? 'checked' : ''; ?>>arXiv e-print<br>
                            <input type="checkbox" name="nasa_gif_link" value="YES" <?php print isset($_SESSION['session_download_nasa_gif_link']) && $_SESSION['session_download_nasa_gif_link'] == 'YES' ? 'checked' : ''; ?>>scanned article<br>
                            <input type="checkbox" name="nasa_article_link" value="YES" <?php print isset($_SESSION['session_download_nasa_article_link']) && $_SESSION['session_download_nasa_article_link'] == 'YES' ? 'checked' : ''; ?>>electronic article<br>
                            <input type="checkbox" name="nasa_open_link" value="YES" <?php print isset($_SESSION['session_download_nasa_open_link']) && $_SESSION['session_download_nasa_open_link'] == 'YES' ? 'checked' : ''; ?>>open access article<br>
                            <input type="radio" name="nasa_data_and" value="ALL" <?php print isset($_SESSION['session_download_nasa_data_and']) && $_SESSION['session_download_nasa_data_and'] == 'ALL' ? 'checked' : ''; ?>>all bibliographic entries
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Sort by:
                        </td>
                        <td class="threed">
                            <select name="nasa_sort" style="width: 50%">
                                <option value="SCORE" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'SCORE' ? 'selected' : ''; ?>>score</option>
                                <option value="NORMSCORE" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'NORMSCORE' ? 'selected' : ''; ?>>normalized score</option>
                                <option value="CITATIONS" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'CITATIONS' ? 'selected' : ''; ?>>citation count</option>
                                <option value="NORMCITATIONS" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'NORMCITATIONS' ? 'selected' : ''; ?>>normalized citation count</option>
                                <option value="AUTHOR" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'AUTHOR' ? 'selected' : ''; ?>>first author</option>
                                <option value="AUTHOR_CNT" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'AUTHOR_CNT' ? 'selected' : ''; ?>>number of authors</option>
                                <option value="NDATE" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'NDATE' ? 'selected' : ''; ?>>date (most recent first)</option>
                                <option value="ODATE" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'ODATE' ? 'selected' : ''; ?>>date (oldest first)</option>
                                <option value="ENTRY" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'ENTRY' ? 'selected' : ''; ?>>entry date</option>
                                <option value="PAGE" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'PAGE' ? 'selected' : ''; ?>>page</option>
                                <option value="BIBCODE" <?php print isset($_SESSION['session_download_nasa_sort']) && $_SESSION['session_download_nasa_sort'] == 'BIBCODE' ? 'selected' : ''; ?>>bibcode</option>
                            </select>
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Save search as:
                        </td>
                        <td class="threed">
                            <input type="text" name="nasa_searchname" size="35" style="width:50%" value="<?php print isset($_SESSION['session_download_nasa_searchname']) ? htmlspecialchars($_SESSION['session_download_nasa_searchname']) : '' ?>">
                            &nbsp;<button id="download-save"><i class="fa fa-save"></i> Save</button>
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                </table>
                &nbsp;<a href="http://doc.adsabs.harvard.edu/abs_doc/help_pages/" target="_blank">Help</a>
                <b>&middot;</b> <a href="http://doc.adsabs.harvard.edu/abs_doc/help_pages/overview.html#use" target="_blank">Terms and Conditions</a>
            </form>
        </div>
        <?php
        // CLEAN DOWNLOAD CACHE
        $clean_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id(). DIRECTORY_SEPARATOR . 'page_*_download', GLOB_NOSORT);
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