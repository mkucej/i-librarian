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
        unset($_SESSION['session_download_pmc_searchname']);
        unset($_SESSION['session_download_pmc_tagged_query']);
        unset($_SESSION['session_download_pmc_full_text']);
        unset($_SESSION['session_download_pmc_author']);
        unset($_SESSION['session_download_pmc_title']);
        unset($_SESSION['session_download_pmc_abstract']);
        unset($_SESSION['session_download_pmc_journal']);
        unset($_SESSION['session_download_pmc_year']);
        unset($_SESSION['session_download_pmc_volume']);
        unset($_SESSION['session_download_pmc_pagination']);
        unset($_SESSION['session_download_pmc_pmcdat']);
        unset($_SESSION['session_download_pmc_pmcid']);
        unset($_SESSION['session_download_pmc_pmid']);
        unset($_SESSION['session_download_pmc_doi']);
        unset($_SESSION['session_download_pmc_sort']);
        unset($_SESSION['session_download_pmc_last_search']);
    }

########## save button ##############

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['pmc_searchname'])) {

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
        $searchname = "pmc#" . $_GET['pmc_searchname'];

        $stmt->execute();

        $keys = array('pmc_tagged_query', 'pmc_full_text', 'pmc_author', 'pmc_title', 'pmc_abstract', 'pmc_journal', 'pmc_year',
            'pmc_volume', 'pmc_pagination', 'pmc_pmcdat', 'pmc_sort', 'pmc_pmcid', 'pmc_pmid', 'pmc_doi');

        while (list($key, $field) = each($keys)) {

            if (!empty($_GET[$field])) {

                $user = $_SESSION['user_id'];
                $searchname = "pmc#" . $_GET['pmc_searchname'];
                $searchfield = $field;
                $searchvalue = $_GET[$field];

                $stmt2->execute();
            }
        }

        $user = $_SESSION['user_id'];
        $searchname = "pmc#" . $_GET['pmc_searchname'];
        $searchfield = 'pmc_last_search';
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
        $searchname = "pmc#" . $_GET['saved_search'];

        $stmt->execute();

        unset($_SESSION['session_download_pmc_searchname']);
        unset($_SESSION['session_download_pmc_tagged_query']);
        unset($_SESSION['session_download_pmc_full_text']);
        unset($_SESSION['session_download_pmc_author']);
        unset($_SESSION['session_download_pmc_title']);
        unset($_SESSION['session_download_pmc_abstract']);
        unset($_SESSION['session_download_pmc_journal']);
        unset($_SESSION['session_download_pmc_year']);
        unset($_SESSION['session_download_pmc_volume']);
        unset($_SESSION['session_download_pmc_pagination']);
        unset($_SESSION['session_download_pmc_pmcdat']);
        unset($_SESSION['session_download_pmc_pmcid']);
        unset($_SESSION['session_download_pmc_pmid']);
        unset($_SESSION['session_download_pmc_doi']);
        unset($_SESSION['session_download_pmc_sort']);
        unset($_SESSION['session_download_pmc_last_search']);

        $_GET = array();
        $_GET['load'] = 'Load';

        $_GET['pmc_searchname'] = substr($searchname, 4);

        while ($search = $stmt->fetch(PDO::FETCH_BOTH)) {
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
        $searchname = "pmc#" . $_GET['saved_search'];

        $stmt->execute();

        unset($_SESSION['session_download_pmc_searchname']);
        unset($_SESSION['session_download_pmc_tagged_query']);
        unset($_SESSION['session_download_pmc_full_text']);
        unset($_SESSION['session_download_pmc_author']);
        unset($_SESSION['session_download_pmc_title']);
        unset($_SESSION['session_download_pmc_abstract']);
        unset($_SESSION['session_download_pmc_journal']);
        unset($_SESSION['session_download_pmc_year']);
        unset($_SESSION['session_download_pmc_volume']);
        unset($_SESSION['session_download_pmc_pagination']);
        unset($_SESSION['session_download_pmc_pmcdat']);
        unset($_SESSION['session_download_pmc_pmcid']);
        unset($_SESSION['session_download_pmc_pmid']);
        unset($_SESSION['session_download_pmc_doi']);
        unset($_SESSION['session_download_pmc_sort']);
        unset($_SESSION['session_download_pmc_last_search']);

        $_GET = array();
    }

########## main body ##############

    $microtime1 = microtime(true);

    if (isset($_GET['pmc_searchname']))
        $_SESSION['session_download_pmc_searchname'] = $_GET['pmc_searchname'];
    if (isset($_GET['pmc_tagged_query']))
        $_SESSION['session_download_pmc_tagged_query'] = $_GET['pmc_tagged_query'];
    if (isset($_GET['pmc_full_text']))
        $_SESSION['session_download_pmc_full_text'] = $_GET['pmc_full_text'];
    if (isset($_GET['pmc_author']))
        $_SESSION['session_download_pmc_author'] = $_GET['pmc_author'];
    if (isset($_GET['pmc_title']))
        $_SESSION['session_download_pmc_title'] = $_GET['pmc_title'];
    if (isset($_GET['pmc_abstract']))
        $_SESSION['session_download_pmc_abstract'] = $_GET['pmc_abstract'];
    if (isset($_GET['pmc_journal']))
        $_SESSION['session_download_pmc_journal'] = $_GET['pmc_journal'];
    if (isset($_GET['pmc_year']))
        $_SESSION['session_download_pmc_year'] = $_GET['pmc_year'];
    if (isset($_GET['pmc_volume']))
        $_SESSION['session_download_pmc_volume'] = $_GET['pmc_volume'];
    if (isset($_GET['pmc_pagination']))
        $_SESSION['session_download_pmc_pagination'] = $_GET['pmc_pagination'];
    if (isset($_GET['pmc_pmcid']))
        $_SESSION['session_download_pmc_pmcid'] = $_GET['pmc_pmcid'];
    if (isset($_GET['pmc_pmid']))
        $_SESSION['session_download_pmc_pmid'] = $_GET['pmc_pmid'];
    if (isset($_GET['pmc_doi']))
        $_SESSION['session_download_pmc_doi'] = $_GET['pmc_doi'];
    if (isset($_GET['pmc_pmcdat']))
        $_SESSION['session_download_pmc_pmcdat'] = $_GET['pmc_pmcdat'];
    if (isset($_GET['pmc_sort']))
        $_SESSION['session_download_pmc_sort'] = $_GET['pmc_sort'];
    if (isset($_GET['pmc_last_search']))
        $_SESSION['session_download_pmc_last_search'] = $_GET['pmc_last_search'];

########## register variables ##############

    $pmc_query = '';
    $pmc_query_array = array();

    if (!isset($_GET['retstart'])) {
        $retstart = '0';
    } else {
        $retstart = $_GET['retstart'];
    }

    if (!isset($_GET['pmc_sort'])) {
        $pmc_sort = '';
    } else {
        $pmc_sort = $_GET['pmc_sort'];
    }

    if (!isset($_GET['webenv'])) {
        $webenv = '';
    } else {
        $webenv = $_GET['webenv'];
    }

    if (!isset($_GET['querykey'])) {
        $querykey = '';
    } else {
        $querykey = $_GET['querykey'];
    }

    if (!isset($_GET['count'])) {
        $count = '';
    } else {
        $count = $_GET['count'];
    }

########## prepare PubMed Central query ##############

    if (!empty($_GET['pmc_pmcdat'])) {
        if ($_GET['pmc_pmcdat'] == 'last search') {
            $pmcdat = date('Y/m/d', $_GET['pmc_last_search']) . ':' . date('Y/m/d');
        } else {
            $pmcdat = date('Y/m/d', time() - ($_GET['pmc_pmcdat'] - 1) * 86400) . ':' . date('Y/m/d');
        }
    } else {
        $pmcdat = '';
    }

    if (!empty($_GET['pmc_full_text']))
        $pmc_query_array[] = "\"$_GET[pmc_full_text]\" [TW]";
    if (!empty($_GET['pmc_author']))
        $pmc_query_array[] = "\"$_GET[pmc_author]\" [AU]";
    if (!empty($_GET['pmc_title']))
        $pmc_query_array[] = "\"$_GET[pmc_title]\" [TI]";
    if (!empty($_GET['pmc_abstract']))
        $pmc_query_array[] = "\"$_GET[pmc_abstract]\" [AB]";
    if (!empty($_GET['pmc_journal']))
        $pmc_query_array[] = "\"$_GET[pmc_journal]\" [TA]";
    if (!empty($_GET['pmc_year']))
        $pmc_query_array[] = "$_GET[pmc_year] [PDAT]";
    if (!empty($_GET['pmc_volume']))
        $pmc_query_array[] = "$_GET[pmc_volume] [VI]";
    if (!empty($_GET['pmc_pagination']))
        $pmc_query_array[] = "$_GET[pmc_pagination] [PG]";

    $pmc_query = implode(" AND ", $pmc_query_array);

    if (!empty($_GET['pmc_tagged_query'])) {

        $order = array("\r\n", "\n", "\r");
        $_GET['pmc_tagged_query'] = str_replace($order, ' ', $_GET['pmc_tagged_query']);
        $pmc_query = "($_GET[pmc_tagged_query])";
    }

    if (!empty($pmcdat))
        $pmc_query = "$pmc_query AND $pmcdat [PMCDAT]";

    if (!empty($_GET['pmc_pmcid']))
        $pmc_query = "$_GET[pmc_pmcid] [UID]";
    if (!empty($_GET['pmc_pmid']))
        $pmc_query = "$_GET[pmc_pmid] [PMID]";
    if (!empty($_GET['pmc_doi']))
        $pmc_query = "$_GET[pmc_doi] [DOI]";

########## search PubMed Central ##############

    if (!empty($pmc_query) && empty($_GET['load']) && empty($_GET['save']) && empty($_GET['delete'])) {

        ############# caching ################

        $cache_name = cache_name();
        $cache_name .= '_download';
        $db_change = database_change(array(
            'library'
                ));
        cache_start($db_change);

        ########## register the time of search ##############

        if (!empty($_SESSION['session_download_pmc_searchname'])) {

            database_connect(IL_DATABASE_PATH, 'library');

            $stmt = $dbHandle->prepare("UPDATE searches SET searchvalue=:searchvalue WHERE userID=:user AND searchname=:searchname AND searchfield=:searchfield");

            $stmt->bindParam(':user', $user, PDO::PARAM_STR);
            $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);
            $stmt->bindParam(':searchfield', $searchfield, PDO::PARAM_STR);
            $stmt->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

            $user = intval($_SESSION['user_id']);
            $searchname = "pmc#" . $_SESSION['session_download_pmc_searchname'];
            $searchfield = 'pmc_last_search';
            $searchvalue = time();

            $stmt->execute();
        }

        ########## search PubMed Central ##############

        $pmc_query = urlencode($pmc_query);

        $request_url = 'https://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pmc&term=' . $pmc_query . '&usehistory=y&retstart=0&retmax=1000&sort=' . $pmc_sort . '&rettype=uilist&tool=I,Librarian&email=i.librarian.software@gmail.com';

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

        if (empty($xml))
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

        $count = $xml->Count;
        $webenv = $xml->WebEnv;
        $querykey = $xml->QueryKey;
    }

########## display search result summaries ##############

    if (!empty($webenv) && !empty($querykey)) {

        ############# caching ################

        $cache_name = cache_name();
        $cache_name .= '_download';
        $db_change = database_change(array(
            'library'
                ));
        cache_start($db_change);

        ############# caching ################

        if ($count > 0) {

            $request_url = 'https://www.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pmc&WebEnv=' . urlencode($webenv) . '&query_key=' . urlencode($querykey) . '&retmode=XML&retstart=' . $retstart . '&retmax=10&tool=I,Librarian&email=i.librarian.software@gmail.com&version=2.0';

            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

            if (empty($xml))
                die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
        }

        print '<div style="padding:2px;font-weight:bold">PubMed Central search';

        if (!empty($_SESSION['session_download_pmc_searchname']))
            print ': ' . htmlspecialchars($_SESSION['session_download_pmc_searchname']);

        print '</div>';

        if ($count > 0) {

            if ($retstart + 10 > $count) {
                $retend = $count;
            } else {
                $retend = $retstart + 10;
            }

            $microtime2 = microtime(true);
            $microtime = $microtime2 - $microtime1;
            $microtime = sprintf("%01.1f seconds", $microtime);

            print '<table class="top" style="margin-bottom:1px"><tr><td style="width: 13em">';

            print '<div class="ui-state-default ui-corner-top' . ($retstart == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                    . ($retstart == 0 ? '' : '<a class="navigation" href="' . htmlspecialchars("download_pmc.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=0&count=$count") . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($retstart == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($retstart == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($retstart == 0 ? '' : '<a class="navigation" href="' . htmlspecialchars("download_pmc.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=" . ($retstart - 10) . "&count=$count") . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($retstart == 0 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="text-align: center">';

            print "Items " . ($retstart+1) . " - $retend of $count in $microtime.";

            print '</td><td class="top" style="width: 14em">';

            (($count % 10) == 0) ? $lastpage = $count - 10 : $lastpage = $count - ($count % 10);

            print '<div class="ui-state-default ui-corner-top' . ($count > $retstart + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count > $retstart + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_pmc.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=$lastpage&count=$count") . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count > $retstart + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($count > $retstart + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count > $retstart + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_pmc.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=" . ($retstart + 10) . "&count=$count") . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count > $retstart + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px;text-align: center">PgDn</div>';

            print '</td></tr></table>';

            print '<div class="alternating_row">';

            database_connect(IL_DATABASE_PATH, 'library');
            $jdbHandle = new PDO('sqlite:journals.sq3');

            foreach ($xml->DocumentSummarySet->DocumentSummary as $docsum) {

                $title = '';
                $journal = '';
                $pub_date = '';
                $pmid = '';
                $authors = '';
                $rating = 0;

                $title = (string) $docsum->Title;
                $journal = (string) $docsum->Source;
                $pub_date = (string) $docsum->PubDate;

                foreach ($docsum->attributes() as $a => $b) {
                    if ($a == 'uid')
                        $uid = $b;
                }

                $author_array = array();
                foreach ($docsum->Authors->Author as $authors) {
                    $author_array[] = (string) $authors->Name;
                }
                $authors = join(', ', $author_array);

                foreach ($docsum->ArticleIds->ArticleId as $ids) {
                    if ((string) $ids->IdType == 'pmid')
                        $pmid = (string) $ids->Value;
                    if ((string) $ids->IdType == 'doi')
                        $doi = (string) $ids->Value;
                }

                if (!empty($uid) && !empty($title) && !empty($journal)) {

                    //JOURNAL RATING
                    $journal_query = $jdbHandle->quote($journal);
                    $result = $jdbHandle->query("SELECT rating FROM journals WHERE pubmed_abbr=upper($journal_query) OR isi_abbr=upper($journal_query) LIMIT 1");
                    $rating = $result->fetchColumn();
                    if (empty($rating))
                        $rating = 0;
                    $result = null;

                    ########## gray out existing records ##############

                    $existing_id = '';
                    $title_query = $dbHandle->quote(substr($title, 0, -1) . "%");
                    $result_query = $dbHandle->query("SELECT id FROM library WHERE title LIKE $title_query AND length(title) <= length($title_query)+2 LIMIT 1");
                    $existing_id = $result_query->fetchColumn();

                    //IS FLAGGED?
                    $relation = 0;
                    $id_query = $dbHandle->quote($uid);
                    $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . intval($_SESSION['user_id']) . " AND database='pmc' AND uid=" . $id_query . " LIMIT 1");
                    if ($result)
                        $relation = $result->fetchColumn();
                    $result = null;

                    print '<div class="items" id="UID-' . urlencode($pmid) . '" data-pmcid="' . urlencode($uid) . '"  style="padding:0">';

                    print '<div class="ui-widget-header" style="border-left:0;border-right:0">';

                    print '<div class="flag ' . (($relation == 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="float:right;margin:4px"><i class="fa fa-flag"></i></div>';

                    print '<div class="titles brief" style="overflow:hidden;margin-right:30px';

                    if (is_numeric($existing_id))
                        print ';color: #777';

                    print '">' . $title.'</div>';

                    print '</div>';

                    print '<div class="firstcontainer items">';

                    print '<i class="star fa fa-star ' . (($rating >= 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"></i>&nbsp;';
                    print '<i class="star fa fa-star ' . (($rating >= 2) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"></i>&nbsp;';
                    print '<i class="star fa fa-star ' . (($rating == 3) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"></i>';

                    print '&nbsp;<b>&middot;</b> ';

                    if (!empty($journal))
                        print htmlspecialchars($journal);

                    if ($pub_date != '')
                        print " ($pub_date)";

                    if (!empty($authors))
                    print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($authors) . '</div>';

                    print '<a href="' . htmlspecialchars('https://www.ncbi.nlm.nih.gov/pmc/articles/' . urlencode($uid)) . '" target="_blank">PubMed Central</a>';

                    print ' <b>&middot;</b> <a href="' . htmlspecialchars('https://www.ncbi.nlm.nih.gov/pmc/articles/' . urlencode($uid) . '/pdf/') . '" target="_blank">Full Text PDF</a>';

                    if (!empty($doi))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars('https://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                    print '</div>';

                    print '<div class="abstract_container"></div>';

                    print '<div class="save_container"></div>';

                    print '</div>';
                }
            }

            $dbHandle = null;
            $jdbHandle = null;

            print '</div>';

            print '<table class="top" style="margin-top:1px"><tr><td class="top" style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($retstart == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:26px;text-align: center">'
                    . ($retstart == 0 ? '' : '<a class="navigation" href="' . htmlspecialchars("download_pmc.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=0&count=$count") . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($retstart == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($retstart == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($retstart == 0 ? '' : '<a class="navigation prevpage" href="' . htmlspecialchars("download_pmc.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=" . ($retstart - 10) . "&count=$count") . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($retstart == 0 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($count > $retstart + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count > $retstart + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_pmc.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=$lastpage&count=$count") . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count > $retstart + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($count > $retstart + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count > $retstart + 10 ? '<a class="navigation nextpage" href="' . htmlspecialchars("download_pmc.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=" . ($retstart + 10) . "&count=$count") . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count > $retstart + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px;text-align: center">PgUp</div>';

            print '</td></tr></table>';
        } else {
            print '<div style="position:relative;top:43%;left:40%;color:#bbbbbb;font-size:28px;width:200px"><b>No Items</b></div>';
        }

        ############# caching #############
        cache_store();
    } else {
        ?>
        <div style="text-align: left">
            <form enctype="application/x-www-form-urlencoded" action="download_pmc.php" method="GET" id="download-form">
                <div class="ui-state-default ui-corner-all" style="float:left;margin:4px 4px 2px 4px;padding:1px 4px">
                    <a href="https://www.ncbi.nlm.nih.gov/pmc/" target="_blank" style="display:block"><i class="fa fa-external-link"></i> PubMed Central</a>
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
                        <td class="threed" style="width:14em">
                            Boolean query: <a href="https://www.ncbi.nlm.nih.gov/books/NBK3825/#pmchelp.Searching_PMC" target="_blank">?</a>
                        </td>
                        <td class="threed">
                            <textarea name="pmc_tagged_query" class="tagged_query" cols="55" rows="6" style="width:99%"><?php print isset($_SESSION['session_download_pmc_tagged_query']) ? htmlspecialchars($_SESSION['session_download_pmc_tagged_query']) : ''; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 0px; background-color: transparent">
                            Single keyword matcher:
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Full Text:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_full_text" class="matcher" size="35" value="<?php print isset($_SESSION['session_download_pmc_full_text']) ? htmlspecialchars($_SESSION['session_download_pmc_full_text']) : ''; ?>">
                            [TW]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Author:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_author" class="matcher" size="35" value="<?php print isset($_SESSION['session_download_pmc_author']) ? htmlspecialchars($_SESSION['session_download_pmc_author']) : ''; ?>">
                            [AU]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Title:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_title" class="matcher" size="35" value="<?php print isset($_SESSION['session_download_pmc_title']) ? htmlspecialchars($_SESSION['session_download_pmc_title']) : ''; ?>">
                            [TI]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Abstract:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_abstract" class="matcher" size="35" value="<?php print isset($_SESSION['session_download_pmc_abstract']) ? htmlspecialchars($_SESSION['session_download_pmc_abstract']) : ''; ?>">
                            [AB]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Journal:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_journal" class="matcher" size="35" value="<?php print isset($_SESSION['session_download_pmc_journal']) ? htmlspecialchars($_SESSION['session_download_pmc_journal']) : ''; ?>">
                            [TA]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Year:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_year" class="matcher" size="8" value="<?php print isset($_SESSION['session_download_pmc_year']) ? htmlspecialchars($_SESSION['session_download_pmc_year']) : ''; ?>">
                            [PDAT]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Volume:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_volume" class="matcher" size="8" value="<?php print isset($_SESSION['session_download_pmc_volume']) ? htmlspecialchars($_SESSION['session_download_pmc_volume']) : ''; ?>">
                            [VI]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            First page:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_pagination" class="matcher" size="8" value="<?php print isset($_SESSION['session_download_pmc_pagination']) ? htmlspecialchars($_SESSION['session_download_pmc_pagination']) : ''; ?>">
                            [PG]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            PMCID:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_pmcid" class="matcher" size="35" value="<?php print isset($_SESSION['session_download_pmc_pmcid']) ? htmlspecialchars($_SESSION['session_download_pmc_pmcid']) : ''; ?>">
                            [UID]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            PMID:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_pmid" class="matcher" size="35" value="<?php print isset($_SESSION['session_download_pmc_pmid']) ? htmlspecialchars($_SESSION['session_download_pmc_pmid']) : ''; ?>">
                            [PMID]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            DOI:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_doi" class="matcher" size="35" value="<?php print isset($_SESSION['session_download_pmc_doi']) ? htmlspecialchars($_SESSION['session_download_pmc_doi']) : ''; ?>">
                            [DOI]
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 0px; background-color: transparent">
                            Limits and sorting:
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Search within the last:
                        </td>
                        <td class="threed">
                            <select name="pmc_pmcdat" style="width: 50%">
                                <option value=""></option>
                                <option value="1" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '1' ? 'selected' : ''; ?>>1 day</option>
                                <option value="2" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '2' ? 'selected' : ''; ?>>2 days</option>
                                <option value="5" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '5' ? 'selected' : ''; ?>>5 days</option>
                                <option value="7" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '7' ? 'selected' : ''; ?>>1 week</option>
                                <option value="14" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '14' ? 'selected' : ''; ?>>2 weeks</option>
                                <option value="31" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '31' ? 'selected' : ''; ?>>1 month</option>
                                <option value="92" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '92' ? 'selected' : ''; ?>>3 months</option>
                                <option value="183" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '183' ? 'selected' : ''; ?>>6 months</option>
                                <option value="365" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == '365' ? 'selected' : ''; ?>>1 year</option>
                                <option value="last search" <?php print isset($_SESSION['session_download_pmc_pmcdat']) && $_SESSION['session_download_pmc_pmcdat'] == 'last search' ? 'selected' : ''; ?>>since last search</option>
                            </select>
                            <input type="hidden" name="pmc_last_search" value="<?php print isset($_SESSION['session_download_pmc_last_search']) ? $_SESSION['session_download_pmc_last_search'] : '1'; ?>">
                            [PMCDAT]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Sort by:
                        </td>
                        <td class="threed">
                            <select name="pmc_sort" style="width: 50%">
                                <option value="" <?php print isset($_SESSION['session_download_pmc_sort']) && $_SESSION['session_download_pmc_sort'] == '' ? 'selected' : ''; ?>>PubMed Central date</option>
                                <option value="PrintPubDate" <?php print isset($_SESSION['session_download_pmc_sort']) && $_SESSION['session_download_pmc_sort'] == 'PrintPubDate' ? 'selected' : ''; ?>>publication year</option>
                                <option value="journal" <?php print isset($_SESSION['session_download_pmc_sort']) && $_SESSION['session_download_pmc_sort'] == 'journal' ? 'selected' : ''; ?>>journal</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Save search as:
                        </td>
                        <td class="threed">
                            <input type="text" name="pmc_searchname" style="width:50%" size="35" value="<?php print isset($_SESSION['session_download_pmc_searchname']) ? htmlspecialchars($_SESSION['session_download_pmc_searchname']) : '' ?>">
                            &nbsp;<button id="download-save"><i class="fa fa-save"></i> Save</button>
                        </td>
                    </tr>
                </table>
                &nbsp;<a href="https://www.ncbi.nlm.nih.gov/books/NBK3825/" target="_blank">Help</a>
                &nbsp;&nbsp;<a href="https://www.ncbi.nlm.nih.gov/About/disclaimer.html" target="_blank">Disclaimer</a>
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