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

########## clear button ##############

    if (isset($_GET['newsearch'])) {
        unset($_SESSION['session_download_searchname']);
        unset($_SESSION['session_download_tagged_query']);
        unset($_SESSION['session_download_pmid']);
        unset($_SESSION['session_download_author']);
        unset($_SESSION['session_download_title']);
        unset($_SESSION['session_download_abstract']);
        unset($_SESSION['session_download_journal']);
        unset($_SESSION['session_download_year']);
        unset($_SESSION['session_download_volume']);
        unset($_SESSION['session_download_pagination']);
        unset($_SESSION['session_download_edat']);
        unset($_SESSION['session_download_sort']);
        unset($_SESSION['session_download_limit']);
        unset($_SESSION['session_download_last_search']);
        unset($_SESSION['session_download_hasabstract']);
        unset($_SESSION['session_download_review']);
        unset($_SESSION['session_download_english']);
    }

########## save button ##############

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['searchname'])) {

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
        $searchname = "pubmed#" . $_GET['searchname'];

        $stmt->execute();

        $keys = array('tagged_query', 'pmid', 'author', 'title', 'abstract', 'journal', 'year', 'volume',
            'pagination', 'edat', 'sort', 'limit', 'hasabstract', 'review', 'english');

        while (list($key, $field) = each($keys)) {

            if (!empty($_GET[$field])) {

                $user = $_SESSION['user_id'];
                $searchname = "pubmed#" . $_GET['searchname'];
                $searchfield = $field;
                $searchvalue = $_GET[$field];

                $stmt2->execute();
            }
        }

        $user = $_SESSION['user_id'];
        $searchname = "pubmed#" . $_GET['searchname'];
        $searchfield = 'last_search';
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
        $searchname = "pubmed#" . $_GET['saved_search'];

        $stmt->execute();

        unset($_SESSION['session_download_searchname']);
        unset($_SESSION['session_download_tagged_query']);
        unset($_SESSION['session_download_pmid']);
        unset($_SESSION['session_download_author']);
        unset($_SESSION['session_download_title']);
        unset($_SESSION['session_download_abstract']);
        unset($_SESSION['session_download_journal']);
        unset($_SESSION['session_download_year']);
        unset($_SESSION['session_download_volume']);
        unset($_SESSION['session_download_pagination']);
        unset($_SESSION['session_download_edat']);
        unset($_SESSION['session_download_sort']);
        unset($_SESSION['session_download_limit']);
        unset($_SESSION['session_download_last_search']);
        unset($_SESSION['session_download_hasabstract']);
        unset($_SESSION['session_download_review']);
        unset($_SESSION['session_download_english']);

        $_GET = array();
        $_GET['load'] = 'Load';

        $_GET['searchname'] = substr($searchname, 7);

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
        $searchname = "pubmed#" . $_GET['saved_search'];

        $stmt->execute();

        unset($_SESSION['session_download_searchname']);
        unset($_SESSION['session_download_tagged_query']);
        unset($_SESSION['session_download_pmid']);
        unset($_SESSION['session_download_author']);
        unset($_SESSION['session_download_title']);
        unset($_SESSION['session_download_abstract']);
        unset($_SESSION['session_download_journal']);
        unset($_SESSION['session_download_year']);
        unset($_SESSION['session_download_volume']);
        unset($_SESSION['session_download_pagination']);
        unset($_SESSION['session_download_edat']);
        unset($_SESSION['session_download_sort']);
        unset($_SESSION['session_download_limit']);
        unset($_SESSION['session_download_last_search']);
        unset($_SESSION['session_download_hasabstract']);
        unset($_SESSION['session_download_review']);
        unset($_SESSION['session_download_english']);

        $_GET = array();
    }

########## main body ##############

    $microtime1 = microtime(true);

    if (isset($_GET['searchname']))
        $_SESSION['session_download_searchname'] = $_GET['searchname'];
    if (isset($_GET['tagged_query']))
        $_SESSION['session_download_tagged_query'] = $_GET['tagged_query'];
    if (isset($_GET['pmid']))
        $_SESSION['session_download_pmid'] = $_GET['pmid'];
    if (isset($_GET['author']))
        $_SESSION['session_download_author'] = $_GET['author'];
    if (isset($_GET['title']))
        $_SESSION['session_download_title'] = $_GET['title'];
    if (isset($_GET['abstract']))
        $_SESSION['session_download_abstract'] = $_GET['abstract'];
    if (isset($_GET['journal']))
        $_SESSION['session_download_journal'] = $_GET['journal'];
    if (isset($_GET['year']))
        $_SESSION['session_download_year'] = $_GET['year'];
    if (isset($_GET['volume']))
        $_SESSION['session_download_volume'] = $_GET['volume'];
    if (isset($_GET['pagination']))
        $_SESSION['session_download_pagination'] = $_GET['pagination'];
    if (isset($_GET['edat']))
        $_SESSION['session_download_edat'] = $_GET['edat'];
    if (isset($_GET['sort']))
        $_SESSION['session_download_sort'] = $_GET['sort'];
    if (isset($_GET['limit']))
        $_SESSION['session_download_limit'] = $_GET['limit'];
    if (isset($_GET['last_search']))
        $_SESSION['session_download_last_search'] = $_GET['last_search'];
    if (isset($_GET['hasabstract']))
        $_SESSION['session_download_hasabstract'] = $_GET['hasabstract'];
    if (isset($_GET['review']))
        $_SESSION['session_download_review'] = $_GET['review'];
    if (isset($_GET['english']))
        $_SESSION['session_download_english'] = $_GET['english'];

########## register variables ##############

    $pubmed_query = '';
    $pubmed_query_array = array();

    if (!isset($_GET['retstart'])) {
        $retstart = '0';
    } else {
        $retstart = $_GET['retstart'];
    }

    if (!isset($_GET['sort'])) {
        $sort = '';
    } else {
        $sort = $_GET['sort'];
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

########## prepare PubMed query ##############

    if (!empty($_GET['edat'])) {
        if ($_GET['edat'] == 'last search') {
            $edat = date('Y/m/d', $_GET['last_search']) . ':' . date('Y/m/d');
        } else {
            $edat = date('Y/m/d', time() - ($_GET['edat'] - 1) * 86400) . ':' . date('Y/m/d');
        }
    } else {
        $edat = '';
    }

    if (!empty($_GET['author']))
        $pubmed_query_array[] = "\"$_GET[author]\" [AU]";
    if (!empty($_GET['title']))
        $pubmed_query_array[] = "$_GET[title] [TI]";
    if (!empty($_GET['abstract']))
        $pubmed_query_array[] = "$_GET[abstract] [TIAB]";
    if (!empty($_GET['journal']))
        $pubmed_query_array[] = "\"$_GET[journal]\" [TA]";
    if (!empty($_GET['year']))
        $pubmed_query_array[] = "$_GET[year] [DP]";
    if (!empty($_GET['volume']))
        $pubmed_query_array[] = "$_GET[volume] [VI]";
    if (!empty($_GET['pagination']))
        $pubmed_query_array[] = "$_GET[pagination] [PG]";

    $pubmed_query = implode(" AND ", $pubmed_query_array);

    if (!empty($_GET['tagged_query'])) {

        $order = array("\r\n", "\n", "\r");
        $_GET['tagged_query'] = str_replace($order, ' ', $_GET['tagged_query']);
        $pubmed_query = "($_GET[tagged_query])";
    }

    if (!empty($edat))
        $pubmed_query = "$pubmed_query AND $edat [EDAT]";
    if (!empty($_GET['limit']))
        $pubmed_query = "$pubmed_query AND $_GET[limit] [SB]";
    if (!empty($_GET['hasabstract']))
        $pubmed_query = "$pubmed_query AND hasabstract";
    if (!empty($_GET['review']))
        $pubmed_query = "$pubmed_query AND review [PT]";
    if (!empty($_GET['english']))
        $pubmed_query = "$pubmed_query AND english [LA]";

    if (!empty($_GET['pmid']))
        $pubmed_query = "$_GET[pmid] [PMID]";

########## search PubMed ##############

    if (!empty($pubmed_query) && empty($_GET['load']) && empty($_GET['save']) && empty($_GET['delete'])) {

        ############# caching ################

        $cache_name = cache_name();
        $cache_name .= 'download_';
        $db_change = database_change(array(
            'library'
        ));
        cache_start($db_change);

        ########## register the time of search ##############

        if (!empty($_SESSION['session_download_searchname'])) {

            database_connect(IL_DATABASE_PATH, 'library');

            $stmt = $dbHandle->prepare("UPDATE searches SET searchvalue=:searchvalue WHERE userID=:userID AND searchname=:searchname AND searchfield='last_search'");

            $stmt->bindParam(':userID', $userID, PDO::PARAM_STR);
            $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);
            $stmt->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

            $userID = intval($_SESSION['user_id']);
            $searchname = "pubmed#" . $_SESSION['session_download_searchname'];
            $searchvalue = time();

            $stmt->execute();
        }

        ########## search PubMed ##############

        $pubmed_query = urlencode($pubmed_query);

        $request_url = 'https://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=Pubmed&term=' . $pubmed_query . '&usehistory=y&retstart=0&retmax=1000&sort=' . urlencode($sort) . '&tool=I,Librarian&email=i.librarian.software@gmail.com';

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

        if ($count > 0) {

            $request_url = 'https://www.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=Pubmed&retmode=XML&retstart=' . $retstart . '&retmax=10&WebEnv=' . urlencode($webenv) . '&query_key=' . urlencode($querykey) . '&tool=I,Librarian&email=i.librarian.software@gmail.com';

            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

            if (empty($xml))
                die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
        }

        print '<div style="padding:2px;font-weight:bold">PubMed search';

        if (!empty($_SESSION['session_download_searchname']))
            print ': ' . htmlspecialchars($_SESSION['session_download_searchname']);

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
                    . ($retstart == 0 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_pubmed.php?webenv=' . urlencode($webenv) . '&querykey=' . urlencode($querykey) . '&retstart=0&count=' . $count) . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($retstart == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($retstart == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($retstart == 0 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_pubmed.php?webenv=' . urlencode($webenv) . '&querykey=' . urlencode($querykey) . '&retstart=' . ($retstart - 10) . '&count=' . $count) . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($retstart == 0 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="text-align: center">';

            print "Items " . ($retstart + 1) . " - $retend of $count in $microtime.";

            print '</td><td class="top" style="width: 14em">';

            (($count % 10) == 0) ? $lastpage = $count - 10 : $lastpage = $count - ($count % 10);

            print '<div class="ui-state-default ui-corner-top' . ($count > $retstart + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count > $retstart + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_pubmed.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=$lastpage&count=$count") . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count > $retstart + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top' . ($count > $retstart + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count > $retstart + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_pubmed.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=" . ($retstart + 10) . "&count=$count") . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count > $retstart + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-top pgdown" style="float: right;width: 4em;margin-right:2px;text-align: center">PgDn</div>';

            print '</td></tr></table>';

            print '<div class="alternating_row">';

            database_connect(IL_DATABASE_PATH, 'library');
            $jdbHandle = new PDO('sqlite:journals.sq3');

            foreach ($xml->DocSum as $docsum) {

                $title = '';
                $journal = '';
                $pub_date = '';
                $authors = '';
                $pmcid = '';
                $doi = '';
                $issn = '';
                $rating = 0;

                $id = (string) $docsum->Id;

                foreach ($docsum->Item as $c) {

                    foreach ($c->attributes() as $a => $b) {

                        if ($a == 'Name' && $b == 'Title')
                            $title = (string) $c[0];
                        if ($a == 'Name' && $b == 'Source')
                            $journal = (string) $c[0];
                        if ($a == 'Name' && $b == 'PubDate')
                            $pub_date = (string) $c[0];
                        if ($a == 'Name' && $b == 'DOI')
                            $doi = (string) $c[0];
                        if ($a == 'Name' && $b == 'ISSN')
                            $issn = (string) $c[0];
                        if ($a == 'Name' && $b == 'AuthorList') {
                            $author = array();
                            foreach ($c->Item as $d) {
                                foreach ($d->attributes() as $a => $b) {
                                    if ($a == 'Name' && $b == 'Author')
                                        $author[] = (string) $d[0];
                                }
                            }
                            $authors = implode(", ", $author);
                        }
                        if ($a == 'Name' && $b == 'ArticleIds') {
                            foreach ($c->Item as $d) {
                                foreach ($d->attributes() as $a => $b) {
                                    if ($a == 'Name' && $b == 'pmc')
                                        $pmcid = (string) $d[0];
                                }
                            }
                        }
                    }
                }

                if (empty($title))
                    $title = 'Title not available';

                if (isset($id) && isset($title) && isset($journal)) {

                    //JOURNAL RATING
                    if (!empty($issn) || !empty($journal)) {
                        $issn_query = $jdbHandle->quote($issn);
                        $journal_query = $jdbHandle->quote($journal);
                        $result = $jdbHandle->query("SELECT rating FROM journals WHERE issn=$issn_query OR pubmed_abbr=upper($journal_query) LIMIT 1");
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
                    $id_query = $dbHandle->quote($id);
                    $result = $dbHandle->query("SELECT count(*) FROM flagged WHERE userID=" . intval($_SESSION['user_id']) . " AND database='pubmed' AND uid=" . $id_query . " LIMIT 1");
                    if ($result)
                        $relation = $result->fetchColumn();
                    $result = null;

                    print '<div class="items" id="UID-' . urlencode($id) . '" style="padding:0">';

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

                    if (!empty($journal))
                        print htmlspecialchars($journal);
                    if (empty($journal) && !empty($secondary_title))
                        print htmlspecialchars($secondary_title);

                    if ($pub_date != '')
                        print " ($pub_date)";

                    print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($authors) . '</div>';

                    print '<a href="' . htmlspecialchars('https://www.ncbi.nlm.nih.gov/pubmed/' . urlencode($id)) . '" target="_blank">PubMed</a>';

                    if (!empty($doi))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars('https://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                    if (!empty($pmcid))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars('https://www.ncbi.nlm.nih.gov/pmc/articles/' . urlencode($pmcid) . '/pdf/') . '" target="_blank">Full Text PDF</a> (PubMed Central)';

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
                    . ($retstart == 0 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_pubmed.php?webenv=' . urlencode($webenv) . '&querykey=' . urlencode($querykey) . '&retstart=0&count=' . $count) . '" style="display:block;width:26px">') .
                    '&nbsp;<i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>&nbsp;'
                    . ($retstart == 0 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($retstart == 0 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:4em;text-align: center">'
                    . ($retstart == 0 ? '' : '<a class="navigation prevpage" href="' . htmlspecialchars('download_pubmed.php?webenv=' . urlencode($webenv) . '&querykey=' . urlencode($querykey) . '&retstart=' . ($retstart - 10) . '&count=' . $count) . '" style="color:black;display:block;width:100%">') .
                    '<i class="fa fa-caret-left"></i>&nbsp;Back'
                    . ($retstart == 0 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="width: 50%">';

            print '<div class="ui-state-default ui-corner-bottom' . ($count > $retstart + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:26px;text-align: center">'
                    . ($count > $retstart + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_pubmed.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=$lastpage&count=$count") . '" style="display:block;width:26px">' : '') .
                    '<i class="fa fa-caret-right"></i>&nbsp;<i class="fa fa-caret-right"></i>'
                    . ($count > $retstart + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom' . ($count > $retstart + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:4em;text-align: center">'
                    . ($count > $retstart + 10 ? '<a class="navigation nextpage" href="' . htmlspecialchars("download_pubmed.php?webenv=" . urlencode($webenv) . "&querykey=" . urlencode($querykey) . "&retstart=" . ($retstart + 10) . "&count=$count") . '" style="color:black;display:block;width:100%">' : '') .
                    '&nbsp;Next <i class="fa fa-caret-right"></i>&nbsp;'
                    . ($count > $retstart + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-default ui-corner-bottom pgup" style="float:right;width:4em;margin-right:2px;text-align: center">PgUp</div>';

            print '</td></tr></table><br>';
        } else {
            print '<div style="position:relative;top:43%;left:40%;color:#bbbbbb;font-size:28px;width:200px"><b>No Items</b></div>';
        }

        cache_store();
    } else {

########## input table ##############
        ?>
        <div style="text-align: left">
            <form enctype="application/x-www-form-urlencoded" action="download_pubmed.php" method="GET" id="download-form">
                <input type="hidden" name="form_submitted" value="">
                <div class="ui-state-default ui-corner-all" style="float:left;margin:4px 4px 2px 4px;padding:1px 4px">
                    <a href="https://www.ncbi.nlm.nih.gov/pubmed/" target="_blank" style="display:block"><i class="fa fa-external-link"></i> PubMed</a>
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
                            Boolean query: <a href="https://www.ncbi.nlm.nih.gov/bookshelf/br.fcgi?book=helppubmed&part=pubmedhelp#pubmedhelp.Combining_search_ter" target="_blank">?</a>
                        </td>
                        <td class="threed">
                            <textarea class="tagged_query" name="tagged_query" cols="55" rows="6" style="width:99%"><?php print isset($_SESSION['session_download_tagged_query']) ? htmlspecialchars($_SESSION['session_download_tagged_query']) : ''; ?></textarea>
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
                            Author:
                        </td>
                        <td class="threed">
                            <input type="text" class="matcher" name="author" size="35" value="<?php print isset($_SESSION['session_download_author']) ? htmlspecialchars($_SESSION['session_download_author']) : ''; ?>">
                            [AU]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Title:
                        </td>
                        <td class="threed">
                            <input type="text" class="matcher" name="title" size="35" value="<?php print isset($_SESSION['session_download_title']) ? htmlspecialchars($_SESSION['session_download_title']) : ''; ?>">
                            [TI]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Title/Abstract:
                        </td>
                        <td class="threed">
                            <input type="text" class="matcher" name="abstract" size="35" value="<?php print isset($_SESSION['session_download_abstract']) ? htmlspecialchars($_SESSION['session_download_abstract']) : ''; ?>">
                            [TIAB]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Journal:
                        </td>
                        <td class="threed">
                            <input type="text" class="matcher" name="journal" size="35" value="<?php print isset($_SESSION['session_download_journal']) ? htmlspecialchars($_SESSION['session_download_journal']) : ''; ?>">
                            [TA]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Year:
                        </td>
                        <td class="threed">
                            <input type="text" class="matcher" name="year" size="8" value="<?php print isset($_SESSION['session_download_year']) ? htmlspecialchars($_SESSION['session_download_year']) : ''; ?>">
                            [DP]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Volume:
                        </td>
                        <td class="threed">
                            <input type="text" class="matcher" name="volume" size="8" value="<?php print isset($_SESSION['session_download_volume']) ? htmlspecialchars($_SESSION['session_download_volume']) : ''; ?>">
                            [VI]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            First page:
                        </td>
                        <td class="threed">
                            <input type="text" class="matcher" name="pagination" size="8" value="<?php print isset($_SESSION['session_download_pagination']) ? htmlspecialchars($_SESSION['session_download_pagination']) : ''; ?>">
                            [PG]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            PMID:
                        </td>
                        <td class="threed">
                            <input type="text" class="matcher" name="pmid" size="35" value="<?php print isset($_SESSION['session_download_pmid']) ? htmlspecialchars($_SESSION['session_download_pmid']) : ''; ?>">
                            [PMID]
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 0px; background-color: transparent">
                            Limits and sorting:
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    <tr>
                        <td class="threed">
                            Search within the last:
                        </td>
                        <td class="threed">
                            <select name="edat" style="width: 50%">
                                <option value=""></option>
                                <option value="1" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '1' ? 'selected' : ''; ?>>1 day</option>
                                <option value="2" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '2' ? 'selected' : ''; ?>>2 days</option>
                                <option value="5" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '5' ? 'selected' : ''; ?>>5 days</option>
                                <option value="7" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '7' ? 'selected' : ''; ?>>1 week</option>
                                <option value="14" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '14' ? 'selected' : ''; ?>>2 weeks</option>
                                <option value="31" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '31' ? 'selected' : ''; ?>>1 month</option>
                                <option value="92" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '92' ? 'selected' : ''; ?>>3 months</option>
                                <option value="183" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '183' ? 'selected' : ''; ?>>6 months</option>
                                <option value="365" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == '365' ? 'selected' : ''; ?>>1 year</option>
                                <option value="last search" <?php print isset($_SESSION['session_download_edat']) && $_SESSION['session_download_edat'] == 'last search' ? 'selected' : ''; ?>>since last search</option>
                            </select>
                            <input type="hidden" name="last_search" value="<?php print isset($_SESSION['session_download_last_search']) ? $_SESSION['session_download_last_search'] : '1'; ?>">
                            [EDAT]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Only items with links to:
                        </td>
                        <td class="threed">
                            <select name="limit" style="width: 50%">
                                <option value=""></option>
                                <option value="free full text" <?php print isset($_SESSION['session_download_limit']) && $_SESSION['session_download_limit'] == 'free full text' ? 'selected' : ''; ?>>free full text</option>
                                <option value="full text" <?php print isset($_SESSION['session_download_limit']) && $_SESSION['session_download_limit'] == 'full text' ? 'selected' : ''; ?>>full text</option>
                            </select>
                            [SB]
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Only items that:
                        </td>
                        <td class="threed">
                            <table cellspacing="0">
                                <tr>
                                    <td class="select_span">
                                        <input type="checkbox" name="hasabstract" style="display:none" <?php print !empty($_SESSION['session_download_hasabstract']) ? 'checked' : ''; ?>>
                                        &nbsp;<i class="fa fa-<?php print !empty($_SESSION['session_download_hasabstract']) ? 'check-square' : 'square-o'  ?>"></i>
                                        have abstracts (hasabstract)
                                    </td>
                                </tr>
                                <tr>
                                    <td class="select_span">
                                        <input type="checkbox" name="review" style="display:none" <?php print !empty($_SESSION['session_download_review']) ? 'checked' : ''; ?>>
                                        &nbsp;<i class="fa fa-<?php print !empty($_SESSION['session_download_review']) ? 'check-square' : 'square-o'  ?>"></i>
                                        are reviews (review [PT])
                                    </td>
                                </tr>
                                <tr>
                                    <td class="select_span">
                                        <input type="checkbox" name="english" style="display:none" <?php print !empty($_SESSION['session_download_english']) ? 'checked' : ''; ?>>
                                        &nbsp;<i class="fa fa-<?php print !empty($_SESSION['session_download_english']) ? 'check-square' : 'square-o' ?>"></i>
                                        are in English (english [LA])
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Sort by:
                        </td>
                        <td class="threed">
                            <select name="sort" style="width: 50%">
                                <option value="" <?php print isset($_SESSION['session_download_sort']) && $_SESSION['session_download_sort'] == '' ? 'selected' : ''; ?>>PubMed date</option>
                                <option value="pub date" <?php print isset($_SESSION['session_download_sort']) && $_SESSION['session_download_sort'] == 'pub date' ? 'selected' : ''; ?>>publication year</option>
                                <option value="journal" <?php print isset($_SESSION['session_download_sort']) && $_SESSION['session_download_sort'] == 'journal' ? 'selected' : ''; ?>>journal</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Save search as:
                        </td>
                        <td class="threed">
                            <input type="text" name="searchname" size="35" style="width:50%" value="<?php print isset($_SESSION['session_download_searchname']) ? htmlspecialchars($_SESSION['session_download_searchname']) : ''  ?>">
                            &nbsp;<button id="download-save"><i class="fa fa-save"></i> Save</button>
                        </td>
                    </tr>
                </table>
                &nbsp;<a href="https://www.ncbi.nlm.nih.gov/books/NBK3827/" target="_blank">Help</a>
                &nbsp;&nbsp;<a href="https://www.ncbi.nlm.nih.gov/home/about/policies.shtml" target="_blank">Disclaimer</a>
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