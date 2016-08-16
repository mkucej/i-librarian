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

########## reset button ##############

    if (isset($_GET['newsearch'])) {

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_highwire'))
                unset($_SESSION[$key]);
        }
    }

########## save button ##############

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['highwire_searchname'])) {

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
        $searchname = "highwire#" . $_GET['highwire_searchname'];

        $stmt->execute();

        reset($_GET);

        while (list($key, $value) = each($_GET)) {

            if (!empty($key) && strstr($key, "highwire_")) {

                $user = $_SESSION['user_id'];
                $searchname = "highwire#" . $_GET['highwire_searchname'];

                if ($key != "highwire_searchname") {

                    $searchfield = $key;
                    $searchvalue = $value;

                    $stmt2->execute();
                }
            }
        }

        $dbHandle->commit();
    }

########## load button ##############

    if (isset($_GET['load']) && $_GET['load'] == '1' && !empty($_GET['saved_search'])) {

        database_connect(IL_DATABASE_PATH, 'library');

        $stmt = $dbHandle->prepare("SELECT searchfield,searchvalue FROM searches WHERE userID=:user AND searchname=:searchname");
        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "highwire#" . $_GET['saved_search'];

        $stmt->execute();

        reset($_SESSION);

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_highwire'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
        $_GET['load'] = 'Load';

        $_GET['highwire_searchname'] = substr($searchname, 9);

        while ($search = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_GET{$search['searchfield']} = $search['searchvalue'];
        }
    }

########## delete button ##############

    if (isset($_GET['delete']) && $_GET['delete'] == '1' && !empty($_GET['saved_search'])) {

        database_connect(IL_DATABASE_PATH, 'library');

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");
        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "highwire#" . $_GET['saved_search'];

        $stmt->execute();

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_highwire'))
                unset($_SESSION[$key]);
        }
        die();
    }

########## main body ##############

    $microtime1 = microtime(true);

    reset($_GET);

    while (list($key, $value) = each($_GET)) {

        if (!empty($_GET[$key]))
            $_SESSION['session_download_' . $key] = $value;
    }

    if (isset($_GET['highwire_searchname']))
        $_SESSION['session_download_highwire_searchname'] = $_GET['highwire_searchname'];

########## register variables ##############

    $parameter_string = '';

    if (!isset($_GET['from'])) {
        $from = '1';
    } else {
        $from = intval($_GET['from']);
    }

    $url_array = array();
    reset($_GET);

    while (list($key, $value) = each($_GET)) {

        if ($key != 'from')
            $url_array[] = "$key=" . urlencode($value);
    }

    $url_string = join("&", $url_array);

########## prepare highwire query ##############

    $query_string = '';

    if (!empty($_GET['highwire_query'])) {

        $_GET['highwire_query'] = str_replace("\"", "\\\"", $_GET['highwire_query']);

        if ($_GET['highwire_selection'] == 'all') {

            $query_string = urlencode("\"$_GET[highwire_query]\"");
        } elseif ($_GET['highwire_selection'] == 'au') {

            $query_string = urlencode("dc.creator =\"$_GET[highwire_query]\"");
        } elseif ($_GET['highwire_selection'] == 'ti') {

            $query_string = urlencode("title =\"$_GET[highwire_query]\"");
        } elseif ($_GET['highwire_selection'] == 'abs') {

            $query_string = urlencode("dc.description =\"$_GET[highwire_query]\"");
        }
    }

########## search highwire ##############

    if (!empty($query_string) && empty($_GET['load']) && empty($_GET['save']) && empty($_GET['delete'])) {

        ############# caching ################

        $cache_name = cache_name();
        $cache_name .= '_download';
        $db_change = database_change(array(
            'library'
        ));
        cache_start($db_change);

        ########## search highwire ##############

        $request_url = "http://highwire.stanford.edu/cgi/sru?version=1.1&operation=searchRetrieve&query=$query_string&startRecord=" . ($from - 1) . "&sendit=Search";

        $xml = getFromWeb($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

        if ($xml === '') {
            
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
        }
    }

########## display search result summaries ##############

    if (isset($xml)) {

        print '<div style="padding:2px;font-weight:bold">Highwire search';

        if (!empty($_SESSION['session_download_highwire_searchname']))
            print ': ' . htmlspecialchars($_SESSION['session_download_highwire_searchname']);

        print '</div>';

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace("srw", "http://www.loc.gov/zing/srw/");
        $xpath->registerNamespace("dc", "http://pulr.org/dc/elements/1.1/");
        $xpath->registerNamespace("prism", "http://prismstandard.org/namespaces/1.2/basic/");

        $count = $xpath->evaluate('//srw:numberOfRecords')->item(0)->nodeValue;

        if (!empty($count) && $count > 0) {

            $maxfrom = $from + 19;
            if ($maxfrom > $count)
                $maxfrom = $count;

            $microtime2 = microtime(true);
            $microtime = $microtime2 - $microtime1;
            $microtime = sprintf("%01.1f seconds", $microtime);

            print '<table class="top" style="margin-bottom:1px"><tr><td style="width: 13em">';

            print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_highwire.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_highwire.php?' . $url_string . '&from=' . ($from - 20)) . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="text-align: center">';

            print "Items $from - $maxfrom of $count in $microtime.";

            print '</td><td class="top" style="width: 14em">';

            (($count % 20) == 0) ? $lastpage = 1 + $count - 20 : $lastpage = 1 + $count - ($count % 20);

            print '<div class="ui-state-default ui-corner-top' . ($count >= $from + 20 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count >= $from + 20 ? '<a class="navigation" href="' . htmlspecialchars('download_highwire.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count >= $from + 20 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($count >= $from + 20 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count >= $from + 20 ? '<a class="navigation" href="' . htmlspecialchars("download_highwire.php?$url_string&from=" . ($from + 20)) . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count >= $from + 20 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px;text-align: center">PgDn</div>';

            print '</td></tr></table>';

            print '<div class="alternating_row">';

            $items = $xpath->evaluate('//srw:recordData');
            for ($j = 0; $j <= 19; $j++) {
                // start page
                $spage = $xpath->evaluate('prism:startingPage', $items->item($j));
                if ($spage->length > 0)
                    $add[$j]['spage'] = $spage->item(0)->nodeValue;
                // end page
                $epage = $xpath->evaluate('prism:endingPage', $items->item($j));
                if ($epage->length > 0)
                    $add[$j]['epage'] = $epage->item(0)->nodeValue;
                // issue
                $issue = $xpath->evaluate('prism:number', $items->item($j));
                if ($issue->length > 0)
                    $add[$j]['issue'] = $issue->item(0)->nodeValue;
                // volume
                $volume = $xpath->evaluate('prism:volume', $items->item($j));
                if ($volume->length > 0)
                    $add[$j]['volume'] = $volume->item(0)->nodeValue;
                // journal
                $secondary_title = $xpath->evaluate('prism:publicationName', $items->item($j));
                if ($secondary_title->length > 0)
                    $add[$j]['secondary_title'] = $secondary_title->item(0)->nodeValue;
                // title
                $title = $xpath->evaluate('dc:dc/dc:title', $items->item($j));
                if ($title->length > 0)
                    $add[$j]['title'] = $title->item(0)->nodeValue;
                // date
                $date = $xpath->evaluate('dc:dc/dc:date', $items->item($j));
                if ($date->length > 0)
                    $add[$j]['date'] = $date->item(0)->nodeValue;
                // doi
                $doi = $xpath->evaluate('dc:dc/dc:identifier', $items->item($j));
                if ($doi->length > 0)
                    $add[$j]['doi'] = $doi->item(0)->nodeValue;
                // authors
                $authors = $xpath->evaluate('dc:dc/dc:contributor', $items->item($j));
                if ($authors->length > 0) {
                    foreach ($authors as $author) {
                        $add[$j]['authors'][] = $author->nodeValue;
                    }
                }
            }

            database_connect(IL_DATABASE_PATH, 'library');

            foreach ($add as $record) {

                $doi = '';
                $title = '';
                $names = '';
                $secondary_title = '';
                $volume = '';
                $issue = '';
                $pages = '';
                $new_authors = array();
                $array = array();

                if (isset($record['title']))
                    $title = $record['title'];
                if (isset($record['date']))
                    $date = $record['date'];
                if (isset($record['doi']))
                    $doi = $record['doi'];
                if (isset($record['secondary_title']))
                    $secondary_title = $record['secondary_title'];
                if (isset($record['volume']))
                    $volume = $record['volume'];
                if (isset($record['issue']))
                    $issue = $record['issue'];
                if (isset($record['spage']))
                    $pages = $record['spage'];
                if (!empty($record['epage']))
                    $pages = $record['spage'] . '-' . $record['epage'];

                $last_name = array();
                $first_name = array();
                if (!empty($record['authors'])) {
                    $name_array = array();
                    foreach ($record['authors'] as $author) {
                        $author_array = explode(' ', $author);
                        $last = array_pop($author_array);
                        $first = join(' ', $author_array);
                        $name_array[] = $last . ', ' . $first;
                        $last_name[] = $last;
                        $first_name[] = $first;
                    }
                    if (count($name_array) > 0)
                        $names = join("; ", $name_array);
                }

                if (!empty($title)) {

                    ########## gray out existing records ##############

                    $existing_id = '';
                    $title_query = $dbHandle->quote(substr($title, 0, -1) . "%");
                    $result_query = $dbHandle->query("SELECT id FROM library WHERE title LIKE $title_query AND length(title) <= length($title_query)+2 LIMIT 1");
                    $existing_id = $result_query->fetchColumn();

                    print '<div class="items" style="padding:0">';

                    print '<div class="ui-widget-header" style="border-left:0;border-right:0">';

                    print '<div class="titles brief" style="overflow:hidden;margin-right:10px';

                    if (is_numeric($existing_id))
                        print ';color: #777';

                    print '">' . $title . '</div></div>';

                    print '<div class="firstcontainer items">';

                    print htmlspecialchars($secondary_title);

                    if ($date != '')
                        print " ($date)";

                    if ($names != '')
                        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names) . '</div>';

                    if (!empty($doi))
                        print '<a href="' . htmlspecialchars('http://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                    print '</div>';

                    print '<div class="abstract_container" style="display:none">';

                    ##########	print results into table	##########

                    print '<form enctype="application/x-www-form-urlencoded" action="upload.php" method="POST" class="fetch-form">';

                    print '<div class="items">';

                    print '<div>';
                    if (!empty($secondary_title))
                        print htmlspecialchars($secondary_title);
                    if (!empty($date))
                        print " (" . htmlspecialchars($date) . ")";
                    if (!empty($volume))
                        print " " . htmlspecialchars($volume);
                    if (!empty($issue))
                        print " ($issue)";
                    if (!empty($pages))
                        print ": " . htmlspecialchars($pages);
                    print '</div>';

                    if ($names != '')
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

                    print '</div>';

                    print '<div class="abstract" style="padding:0 10px">';

                    !empty($abstract) ? print htmlspecialchars($abstract)  : print 'No abstract available.';

                    print '</div><div class="items">';
                    ?>
                    <input type="hidden" name="uid[]" value="">
                    <input type="hidden" name="url[]" value="">
                    <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
                    <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
                    <input type="hidden" name="last_name" value="<?php if (!empty($last_name)) print htmlspecialchars(json_encode($last_name)); ?>">
                    <input type="hidden" name="first_name" value="<?php if (!empty($first_name)) print htmlspecialchars(json_encode($first_name)); ?>">
                    <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
                    <input type="hidden" name="year" value="<?php if (!empty($date)) print htmlspecialchars($date); ?>">
                    <input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
                    <input type="hidden" name="issue" value="<?php if (!empty($issue)) print htmlspecialchars($issue); ?>">
                    <input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
                    <input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract) : "No abstract available."; ?>">

                    <?php
                    ##########	print full text links	##########

                    print '<b>Full text options:</b><br>';

                    if (!empty($doi))
                        print '<a href="' . htmlspecialchars('http://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                    print '<br><button class="save-item">Save</button> <button class="quick-save-item">Quick Save</button>';

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
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_highwire.php?' . $url_string . '&from=1') . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($from == 1 ? '' : '<a class="navigation prevpage" href="' . htmlspecialchars('download_highwire.php?' . $url_string . '&from=' . ($from - 20)) . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($count >= $from + 20 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count >= $from + 20 ? '<a class="navigation" href="' . htmlspecialchars('download_highwire.php?' . $url_string . '&from=' . $lastpage) . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count >= $from + 20 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($count >= $from + 20 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count >= $from + 20 ? '<a class="navigation nextpage" href="' . htmlspecialchars("download_highwire.php?$url_string&from=" . ($from + 20)) . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count >= $from + 20 ? '</a>' : '') .
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
            <form enctype="application/x-www-form-urlencoded" action="download_highwire.php" method="GET" id="download-form">
                <div class="ui-state-default ui-corner-all" style="float:left;margin:4px 4px 2px 4px;padding:1px 4px">
                    <a href="http://highwire.stanford.edu" target="_blank" style="display:block"><i class="fa fa-external-link"></i> HighWire Press</a>
                </div>
                <div style="clear:both"></div>
                <table class="threed" style="width:100%">
                    <tr>
                        <td class="threed" colspan="2">
                            <button id="download-search"><i class="fa fa-save"></i> Search</button>
                            <button id="download-reset"><i class="fa fa-reply"></i> Reset</button>
                            <button id="download-clear"><i class="fa fa-trash-o"></i> Clear</button>
                        </td>
                    </tr>
                    <tr>
                        <td class="threed" style="width:12em">
                            <select name="highwire_selection">
                                <?php
                                print '
	<option value="all" ' . ((isset($_SESSION['session_download_highwire_selection']) && $_SESSION['session_download_highwire_selection'] == 'all') ? 'selected' : '') . '>full record</option>
	<option value="au" ' . ((isset($_SESSION['session_download_highwire_selection']) && $_SESSION['session_download_highwire_selection'] == 'au') ? 'selected' : '') . '>author</option>
	<option value="ti" ' . ((isset($_SESSION['session_download_highwire_selection']) && $_SESSION['session_download_highwire_selection'] == 'ti') ? 'selected' : '') . '>title</option>
	<option value="abs" ' . ((isset($_SESSION['session_download_highwire_selection']) && $_SESSION['session_download_highwire_selection'] == 'abs') ? 'selected' : '') . '>title and abstract</option>';
                                ?>
                            </select>
                        </td>
                        <td class="threed">
                            <input type="text" name="highwire_query" value="<?php print htmlspecialchars((isset($_SESSION['session_download_highwire_query'])) ? $_SESSION['session_download_highwire_query'] : ''); ?>" size="65" style="width:99%">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Save search as:
                        </td>
                        <td class="threed">
                            <input type="text" name="highwire_searchname" size="35" style="width:50%" value="<?php print isset($_SESSION['session_download_highwire_searchname']) ? htmlspecialchars($_SESSION['session_download_highwire_searchname']) : ''  ?>">
                            &nbsp;<button id="download-save"><i class="fa fa-save"></i> Save</button>
                        </td>
                    </tr>
                </table>
            </form>
            &nbsp;<a href="http://highwire.stanford.edu/about/terms-of-use.dtl" target="_blank">Terms of Use</a>
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