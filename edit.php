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

    include_once 'functions.php';

    $error = '';

    if (isset($_POST['file']))
        $_GET['file'] = $_POST['file'];

########## reference updating ##########

    if (isset($_POST['autoupdate'])) {

        database_connect(IL_USER_DATABASE_PATH, 'users');
        save_setting($dbHandle, 'autoupdate_database', $_POST['database']);
        $dbHandle = null;

        session_write_close();

        $doi = '';
        $nasa_id = '';
        $arxiv_id = '';
        $pmid = '';
        $ieee_id = '';
        if (!empty($_POST['doi'])) {
            $doi = trim($_POST['doi']);
            if (stripos($doi, 'doi:') === 0)
                $doi = trim(substr($doi, 4));
            if (stripos($doi, 'http') === 0)
                $doi = trim(substr(parse_url($doi, PHP_URL_PATH), 1));
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
            if ($uid_array2[0] == 'IEEE')
                $ieee_id = trim($uid_array2[1]);
        }

        if ($_POST['database'] == 'pubmed') {

            if (empty($pmid)) {

                $pubmed_query = array();

                $first_author = '';
                if (!empty($_POST['last_name'][0]))
                    $first_author = $_POST['last_name'][0];

                if (!empty($_POST['first_name'][0]))
                    $first_author .= ' ' . $_POST['first_name'][0];

                if (!empty($_POST['title'])) {

                    $title_words = str_word_count($_POST['title'], 1);

                    $strlens = array();

                    while (list($key, $word) = each($title_words)) {

                        $strlens[$word] = strlen($word);
                    }

                    arsort($strlens);
                    $title_word = key($strlens);
                }

                if (!empty($_POST['year'])) {
                    if (is_numeric($_POST['year'])) {
                        $year = $_POST['year'];
                    } else {
                        $year = date('Y', strtotime($_POST['year']));
                    }
                }

                if (!empty($first_author))
                    $pubmed_query[] = "$first_author [AU]";
                if (!empty($_POST['title']))
                    $pubmed_query[] = "$title_word [TI]";
                if (!empty($_POST['year']))
                    $pubmed_query[] = $year . " [DP]";
                if (!empty($_POST['volume']))
                    $pubmed_query[] = "$_POST[volume] [VI]";
                if (!empty($_POST['pages']))
                    $pubmed_query[] = "$_POST[pages] [PG]";

                $pubmed_query = join(" AND ", $pubmed_query);
                $pubmed_query = urlencode($pubmed_query);

                if (!empty($_POST['doi']))
                    $pubmed_query = $_POST['doi'] . '[AID]';

                $request_url = "https://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=Pubmed&term=$pubmed_query&usehistory=y&retstart=0&retmax=1&sort=&tool=I,Librarian&email=i.librarian.software@gmail.com";

                $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                if (empty($xml))
                    die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server. Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                $count = $xml->Count;
                if ($count == 1)
                    $pmid = $xml->IdList->Id;
            }

            if (!empty($pmid)) {
                //FETCH FROM PUBMED
                fetch_from_pubmed('', $pmid);
                if (isset($response['uid'])) {
                    $response['uid'] = array_merge_recursive($_POST['uid'], $response['uid']);
                    $response['uid'] = array_unique($response['uid']);
                }
                $_POST = array_replace_recursive($_POST, $response);
            } else {
                $error = "Error! Unique record not found in PubMed.";
            }
            if (empty($response['title']))
                $error = "Error! Unique record not found in PubMed.";
        }

        if ($_POST['database'] == 'nasaads') {

            if (empty($nasa_id) && empty($doi)) {

                $lookfor_query = array();

                $first_author = '';
                if (!empty($_POST['last_name'][0]))
                    $first_author = $_POST['last_name'][0];

                if (!empty($_POST['title'])) {

                    $title_words = str_word_count($_POST['title'], 1);

                    $strlens = array();

                    while (list($key, $word) = each($title_words)) {

                        $strlens[$word] = strlen($word);
                    }

                    arsort($strlens);
                    $title_word = key($strlens);
                }

                if (!empty($_POST['year'])) {
                    if (is_numeric($_POST['year'])) {
                        $year = $_POST['year'];
                    } else {
                        $year = date('Y', strtotime($_POST['year']));
                    }
                }

                if (!empty($first_author))
                    $lookfor_query[] = "author=" . urlencode($first_author) . "&aut_req=YES";
                if (!empty($_POST['title']))
                    $lookfor_query[] = "title=" . urlencode($title_word) . "&ttl_req=YES";
                if (!empty($_POST['year']))
                    $lookfor_query[] = "start_year=" . urlencode($year);
                if (!empty($_POST['volume']))
                    $lookfor_query[] = "volume=" . urlencode($_POST['volume']);
                if (!empty($_POST['pages'])) {
                    $pages = explode("-", $_POST['pages']);
                    $first_page = $pages[0];
                    $pages = null;
                    $lookfor_query[] = "page=" . urlencode($first_page);
                }

                $lookfor_query = join("&", $lookfor_query);

                $request_url = "http://adsabs.harvard.edu/cgi-bin/abs_connect?" . $lookfor_query . "&data_type=XML&return_req=no_params&start_nr=1&nr_to_return=1";

                $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                if (empty($xml))
                    die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server. Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                foreach ($xml->attributes() as $a => $b) {

                    if ($a == 'selected') {
                        $count = (string) $b;
                        break;
                    }
                }
            }
            // FETCH FROM NASA ADS
            if (!empty($doi) || !empty($nasa_id)) {
                $response = array();
                fetch_from_nasaads($doi, $nasa_id);
                if (isset($response['uid'])) {
                    $response['uid'] = array_merge_recursive($_POST['uid'], $response['uid']);
                    $response['uid'] = array_unique($response['uid']);
                }
                $_POST = array_replace_recursive($_POST, $response);
            }
            if (empty($response['title']))
                $error = "Error! Unique record not found in NASA ADS.";
        }

        if ($_POST['database'] == 'ieee') {
            // FETCH FROM IEEE XPLORE
            if (!empty($doi) || !empty($ieee_id)) {
                $response = array();
                fetch_from_ieee($doi, $ieee_id);
                if (isset($response['uid'])) {
                    $response['uid'] = array_merge_recursive($_POST['uid'], $response['uid']);
                    $response['uid'] = array_unique($response['uid']);
                }
                $_POST = array_replace_recursive($_POST, $response);
            }
            if (empty($response['title']))
                $error = "Error! Unique record not found in IEEE Xplore.";
        }

        if ($_POST['database'] == 'crossref') {

            if (empty($doi)) {

                $lookfor_query = array();

                $first_author = '';
                if (!empty($_POST['last_name'][0]))
                    $first_author = $_POST['last_name'][0];

                if (!empty($_POST['year'])) {
                    if (is_numeric($_POST['year'])) {
                        $year = $_POST['year'];
                    } else {
                        $year = date('Y', strtotime($_POST['year']));
                    }
                }

                if (!empty($first_author))
                    $lookfor_query[] = $first_author;
                if (!empty($_POST['title']))
                    $lookfor_query[] = $_POST['title'];
                if (!empty($year))
                    $lookfor_query[] = $year;
                if (!empty($_POST['volume']))
                    $lookfor_query[] = $_POST['volume'];
                if (!empty($_POST['pages'])) {
                    $pages = explode("-", $_POST['pages']);
                    $first_page = $pages[0];
                    $pages = null;
                    $lookfor_query[] = $first_page;
                }

                $lookfor_query = join(" ", $lookfor_query);
                $lookfor_query = preg_replace("/[^a-zA-Z0-9]/", " ", $lookfor_query);
                $lookfor_query = urlencode($lookfor_query);

                $request_url = "http://crossref.org/sigg/sigg/FindWorks?version=1&access=i.librarian.software@gmail.com&expression=" . $lookfor_query;

                $result = getFromWeb($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                $result = json_decode($result);

                if (count($result) == 1)
                    $doi = $result[0]->doi;

                if (empty($doi))
                    $error = "Error! Unique record not found in Crossref.";
            }

            if (!empty($doi)) {

                // FETCH FROM CROSSREF
                $response = array();
                fetch_from_crossref($doi);
                $_POST = array_replace_recursive($_POST, $response);
            }
            if (empty($response['title']))
                $error = "Error! Unique record not found in Crossref.";
        }
    }

    session_write_close();

########## save to database ##########

    if (!empty($_POST['title']) && isset($_POST['form_sent'])) {

        ##########	remove line breaks from certain POST values	##########

        $order = array("\r\n", "\n", "\r");
        $keys = array('title', 'abstract', 'keywords', 'affiliation');

        while (list($key, $field) = each($keys)) {

            if (!empty($_POST[$field])) {

                $_POST[$field] = str_replace($order, ' ', $_POST[$field]);
            }
        }

        ##########	record publication data, table library	##########

        $uid = '';
        database_connect(IL_DATABASE_PATH, 'library');

        $query = "UPDATE library SET authors=:authors, title=:title, journal=:journal, year=:year,
				abstract=:abstract, uid=:uid, volume=:volume, pages=:pages,
				secondary_title=:secondary_title, tertiary_title=:tertiary_title, editor=:editor, url=:url,
				reference_type=:reference_type, publisher=:publisher, place_published=:place_published,
				keywords=:keywords, doi=:doi, authors_ascii=:authors_ascii,
				title_ascii=:title_ascii, abstract_ascii=:abstract_ascii,
                                custom1=:custom1, custom2=:custom2, custom3=:custom3, custom4=:custom4, bibtex=:bibtex,
				affiliation=:affiliation, issue=:issue, modified_by=:modified_by,
                                modified_date=strftime('%Y-%m-%dT%H:%M:%S', 'now', 'localtime'), bibtex_type=:bibtex_type
			WHERE id=:id";

        $stmt = $dbHandle->prepare($query);

        $stmt->bindParam(':id', $file_id, PDO::PARAM_INT);
        $stmt->bindParam(':authors', $authors, PDO::PARAM_STR);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':journal', $journal_abbr, PDO::PARAM_STR);
        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
        $stmt->bindParam(':abstract', $abstract, PDO::PARAM_STR);
        $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
        $stmt->bindParam(':volume', $volume, PDO::PARAM_STR);
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
        $stmt->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
        $stmt->bindParam(':issue', $issue, PDO::PARAM_STR);
        $stmt->bindParam(':custom1', $custom1, PDO::PARAM_STR);
        $stmt->bindParam(':custom2', $custom2, PDO::PARAM_STR);
        $stmt->bindParam(':custom3', $custom3, PDO::PARAM_STR);
        $stmt->bindParam(':custom4', $custom4, PDO::PARAM_STR);
        $stmt->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);
        $stmt->bindParam(':modified_by', $modified_by, PDO::PARAM_INT);
        $stmt->bindParam(':bibtex_type', $bibtex_type, PDO::PARAM_STR);

        $file_id = (integer) $_POST['file'];

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

        $title = trim($_POST['title']);
        $title_ascii = utf8_deaccent($title);

        empty($_POST['journal_abbr']) ? $journal_abbr = '' : $journal_abbr = trim($_POST['journal_abbr']);

        empty($_POST['secondary_title']) ? $secondary_title = '' : $secondary_title = trim($_POST['secondary_title']);

        empty($_POST['tertiary_title']) ? $tertiary_title = '' : $tertiary_title = trim($_POST['tertiary_title']);

        empty($_POST['year']) ? $year = '' : $year = trim($_POST['year']);

        if (empty($_POST['abstract'])) {

            $abstract = '';
            $abstract_ascii = '';
        } else {

            $abstract = trim($_POST['abstract']);
            $abstract_ascii = utf8_deaccent($abstract);
        }

        empty($_POST['uid'][0]) ? $uid = '' : $uid = join('|', array_filter($_POST['uid']));

        empty($_POST['volume']) ? $volume = '' : $volume = trim($_POST['volume']);

        empty($_POST['issue']) ? $issue = '' : $issue = trim($_POST['issue']);

        empty($_POST['pages']) ? $pages = '' : $pages = trim($_POST['pages']);

        empty($_POST['editor']) ? $editor = '' : $editor = trim(htmlspecialchars_decode($_POST['editor']));

        empty($_POST['url'][0]) ? $url = '' : $url = implode('|', array_filter($_POST['url']));

        empty($_POST['reference_type']) ? $reference_type = 'article' : $reference_type = trim($_POST['reference_type']);

        empty($_POST['publisher']) ? $publisher = '' : $publisher = trim($_POST['publisher']);

        empty($_POST['place_published']) ? $place_published = '' : $place_published = trim($_POST['place_published']);

        empty($_POST['keywords']) ? $keywords = '' : $keywords = trim($_POST['keywords']);

        empty($_POST['affiliation']) ? $affiliation = '' : $affiliation = trim($_POST['affiliation']);

        empty($user_id) ? $modified_by = '' : $modified_by = (integer) $user_id;

        empty($_POST['doi']) ? $doi = '' : $doi = trim($_POST['doi']);

        empty($_POST['custom1']) ? $custom1 = '' : $custom1 = trim($_POST['custom1']);

        empty($_POST['custom2']) ? $custom2 = '' : $custom2 = trim($_POST['custom2']);

        empty($_POST['custom3']) ? $custom3 = '' : $custom3 = trim($_POST['custom3']);

        empty($_POST['custom4']) ? $custom4 = '' : $custom4 = trim($_POST['custom4']);

        empty($_POST['bibtex']) ? $bibtex = '' : $bibtex = trim($_POST['bibtex']);

        empty($_POST['bibtex_type']) ? $bibtex_type = '' : $bibtex_type = trim($_POST['bibtex_type']);

        if (!empty($title))
            $database_update = $stmt->execute();

        if ($database_update == false)
            $error = "Error! The database has not been updated.";

        $stmt = null;
        $dbHandle = null;

        if (empty($error))
            die('title:' . lib_htmlspecialchars($title));
    } elseif (isset($_POST['form_sent'])) {
        $error = 'Error! Title is mandatory.';
    }

    if (!empty($error))
        die($error);

##########	read reference data	##########

    database_connect(IL_DATABASE_PATH, 'library');

    $file_query = $dbHandle->quote($_GET['file']);

    $record = $dbHandle->query("SELECT * FROM library WHERE id=$file_query LIMIT 1");
    $paper = $record->fetch(PDO::FETCH_ASSOC);

    $paper_urls = array();
    if (!empty($paper['url']))
        $paper_urls = explode('|', $paper['url']);

    $paper_uids = array();
    if (!empty($paper['uid']))
        $paper_uids = explode('|', $paper['uid']);

    $record = null;
    $dbHandle = null;

    $autoupdate_database = get_setting('autoupdate_database');

    ?>
    <form id="metadataform" enctype="multipart/form-data" action="edit.php" method="POST">
        <input type="hidden" name="form_sent" value="1">
        <input type="hidden" name="file" value="<?php print htmlspecialchars($paper['id']) ?>">
        <table class="threed" style="width: 100%;margin-top: 0px;margin-bottom:1px">
            <tr>
                <td class="threedleft">
                    <button id="savemetadata"><i class="fa fa-save"></i> Save</button>
                </td>
                <td class="threedright">
                    <button id="autoupdate" title="Attempt to fetch more data from Internet repositories" style="float:left"><i class="fa fa-refresh"></i> Update</button>
                    <table style="margin-top:0em">
                        <tr>
                            <td class="select_span" style="padding:0.4em">
                                <input type="radio" style="display:none" name="database" value="pubmed"
                                <?php
                                if (empty($autoupdate_database) || $autoupdate_database == 'pubmed') {
                                    echo 'checked>&nbsp;<i class="fa fa-circle"></i>';
                                } elseif (!isset($autoupdate_database)) {
                                    echo 'checked>&nbsp;<i class="fa fa-circle"></i>';
                                } else {
                                    echo '>&nbsp;<i class="fa fa-circle-o"></i>';
                                }

                                ?>
                                       PubMed
                            </td>
                            <td class="select_span" style="padding:0.4em">
                                <input type="radio" style="display:none" name="database" value="nasaads"
                                <?php
                                if ($autoupdate_database == 'nasaads') {
                                    echo 'checked>&nbsp;<i class="fa fa-circle"></i>';
                                } else {
                                    echo '>&nbsp;<i class="fa fa-circle-o"></i>';
                                }

                                ?>
                                       NASA
                            </td>
                            <td class="select_span" style="padding:0.4em">
                                <input type="radio" style="display:none" name="database" value="ieee"
                                <?php
                                if ($autoupdate_database == 'ieee') {
                                    echo 'checked>&nbsp;<i class="fa fa-circle"></i>';
                                } else {
                                    echo '>&nbsp;<i class="fa fa-circle-o"></i>';
                                }

                                ?>
                                       IEEE
                            </td>
                            <td class="select_span" style="padding:0.4em">
                                <input type="radio" style="display:none" name="database" value="crossref"
                                <?php
                                if ($autoupdate_database == 'crossref') {
                                    echo 'checked>&nbsp;<i class="fa fa-circle"></i>';
                                } else {
                                    echo '>&nbsp;<i class="fa fa-circle-o"></i>';
                                }

                                ?>
                                       CrossRef
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    I, Librarian ID:
                </td>
                <td class="threedright">
                    <?php print $paper['id'] ?>
                </td>
            </tr>
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
                    <i class="fa fa-plus-circle" id="adduidrow" style="float:right;cursor:pointer"></i>
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="uid[]" style="width: 99%" value="" title="<b>Examples:</b><br>PMID:123456<br>PMCID:123456<br>NASAADS:123456<br>ARXIV:123456">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    DOI:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="doi" style="width: 99%" value="<?php print isset($paper['doi']) ? htmlspecialchars($paper['doi']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    BibTex key:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="bibtex" style="width: 99%" value="<?php print isset($paper['bibtex']) ? htmlspecialchars($paper['bibtex']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publication type:
                </td>
                <td class="threedright">
                    <select name="reference_type">
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'article') ? 'selected' : ''  ?>>article</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'book') ? 'selected' : ''  ?>>book</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'chapter') ? 'selected' : ''  ?>>chapter</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'conference') ? 'selected' : ''  ?>>conference</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'manual') ? 'selected' : ''  ?>>manual</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'thesis') ? 'selected' : ''  ?>>thesis</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'patent') ? 'selected' : ''  ?>>patent</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'report') ? 'selected' : ''  ?>>report</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'electronic') ? 'selected' : ''  ?>>electronic</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'unpublished') ? 'selected' : ''  ?>>unpublished</option>
                    </select>
                    &nbsp;Bibtex type:
                    <select name="bibtex_type">
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'article') ? 'selected' : ''  ?>>article</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'book') ? 'selected' : ''  ?>>book</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'booklet') ? 'selected' : ''  ?>>booklet</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'conference') ? 'selected' : ''  ?>>conference</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'inbook') ? 'selected' : ''  ?>>inbook</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'incollection') ? 'selected' : ''  ?>>incollection</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'inproceedings') ? 'selected' : ''  ?>>inproceedings</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'manual') ? 'selected' : ''  ?>>manual</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'mastersthesis') ? 'selected' : ''  ?>>mastersthesis</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'misc') ? 'selected' : ''  ?>>misc</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'phdthesis') ? 'selected' : ''  ?>>phdthesis</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'proceedings') ? 'selected' : ''  ?>>proceedings</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'techreport') ? 'selected' : ''  ?>>techreport</option>
                        <option <?php print (!empty($paper['bibtex_type']) && $paper['bibtex_type'] == 'unpublished') ? 'selected' : ''  ?>>unpublished</option>

                    </select>
                </td>
            </tr>
            <tr>
                <td class="threedleft td-title">
                    Title:
                </td>
                <td class="threedright">
                    <textarea name="title" cols="80" rows="2" style="width: 99%"><?php echo isset($paper['title']) ? htmlspecialchars($paper['title']) : '' ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Authors:
                </td>
                <td class="threedright">
                    <div class="author-inputs" style="max-height: 200px;overflow:auto">
                        <?php
                        if (!empty($paper['authors'])) {
                            $array = array();
                            $new_authors = array();
                            $array = explode(';', $paper['authors']);
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
                                        print '<div>Last name: <input type="text" name="last_name[]" value="' . htmlspecialchars($last) . '">
                                        &nbsp;<i class="fa fa-exchange flipnames"></i>&nbsp;
                                        First name: <input type="text" name="first_name[]" value="' . htmlspecialchars($first) . '"></div>';
                                }
                            }
                        }

                        ?>
                        <div>Last name: <input type="text" name="last_name[]" value="">
                            &nbsp;<i class="fa fa-exchange flipnames"></i>&nbsp;
                            First name: <input type="text" name="first_name[]" value="">
                            <i class="addauthorrow fa fa-plus-circle" style="cursor:pointer"></i></div>
                    </div>
                    <input type="hidden" name="authors" value="<?php echo isset($paper['authors']) ? htmlspecialchars($paper['authors']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft td-affiliation">
                    <?php
                    if (!empty($paper['reference_type']) && $paper['reference_type'] == 'patent') {
                        echo "Assignee:";
                    } else {
                        echo "Affiliation:";
                    }

                    ?>
                </td>
                <td class="threedright">
                    <textarea cols="80" rows="2" name="affiliation" style="width: 99%"><?php echo isset($paper['affiliation']) ? htmlspecialchars($paper['affiliation']) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Journal abbreviation:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="journal_abbr" style="width: 99%" value="<?php print isset($paper['journal']) ? htmlspecialchars($paper['journal']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft td-secondary-title">
                    <?php
                    if (!empty($paper['reference_type'])) {
                        switch ($paper['reference_type']) {
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
                    <input type="text" size="80" name="secondary_title" style="width: 99%" value="<?php print isset($paper['secondary_title']) ? htmlspecialchars($paper['secondary_title']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft td-tertiary-title">
                    <?php
                    if (!empty($paper['reference_type']) && $paper['reference_type'] == 'chapter') {
                        echo "Series Title:";
                    } else {
                        echo "Tertiary Title:";
                    }

                    ?>
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="tertiary_title" title="This can be a collection title." style="width:99%" value="<?php print isset($paper['tertiary_title']) ? htmlspecialchars($paper['tertiary_title']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publication date:
                </td>
                <td class="threedright">
                    <input type="text" size="10" maxlength="10" name="year" value="<?php echo isset($paper['year']) ? htmlspecialchars($paper['year']) : '' ?>"> YYYY-MM-DD
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Volume:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="volume" value="<?php echo isset($paper['volume']) ? htmlspecialchars($paper['volume']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Issue:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="issue" value="<?php echo isset($paper['issue']) ? htmlspecialchars($paper['issue']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Pages:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="pages" value="<?php echo isset($paper['pages']) ? htmlspecialchars($paper['pages']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Abstract:
                </td>
                <td class="threedright">
                    <textarea name="abstract" cols="80" rows="6" style="width: 99%"><?php echo isset($paper['abstract']) ? htmlspecialchars($paper['abstract']) : '' ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Editors:
                </td>
                <td class="threedright">
                    <div class="editor-inputs" style="max-height: 200px;overflow:auto">
                        <?php
                        if (!empty($paper['editor'])) {
                            $array = array();
                            $new_authors = array();
                            $array = explode(';', $paper['editor']);
                            $array = array_filter($array);
                            if (!empty($array)) {
                                foreach ($array as $author) {
                                    $array2 = explode(',', $author);
                                    $last = trim($array2[0]);
                                    $last = substr($array2[0], 3, -1);
                                    $first = trim($array2[1]);
                                    $first = substr($array2[1], 3, -1);
                                    if (!empty($last))
                                        print '<div>Last name: <input type="text" value="' . $last . '">
                                        &nbsp;<i class="fa fa-exchange flipnames"></i>&nbsp;
                                        First name: <input type="text" value="' . $first . '"></div>';
                                }
                            }
                        }

                        ?>
                        <div>Last name: <input type="text" value="">
                            &nbsp;<i class="fa fa-exchange flipnames"></i>&nbsp;
                            First name: <input type="text" value="">
                            <i class="addauthorrow fa fa-plus-circle" style="cursor:pointer"></i></div>
                    </div>
                    <input type="hidden" name="editor" value="<?php echo isset($paper['editor']) ? htmlspecialchars($paper['editor']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publisher:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="publisher" style="width: 99%" value="<?php echo isset($paper['publisher']) ? htmlspecialchars($paper['publisher']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Place published:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="place_published" style="width: 99%" value="<?php echo isset($paper['place_published']) ? htmlspecialchars($paper['place_published']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Keywords:
                </td>
                <td class="threedright">
                    <textarea name="keywords" cols="80" rows=2 style="width: 99%" title="Reserved for keywords provided by internet databases. For your custom keywords use Categories.<br>Separator: space, forward slash, space &quot; / &quot;"><?php echo isset($paper['keywords']) ? htmlspecialchars($paper['keywords']) : '' ?></textarea>
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
                    <i class="fa fa-plus-circle" id="addurlrow" style="float:right;cursor:pointer"></i>
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="url[]" style="width: 99%" value="">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    <?php print (!empty($_SESSION['custom1'])) ? $_SESSION['custom1'] : 'Custom 1'  ?>:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom1" style="width: 99%" value="<?php print isset($paper['custom1']) ? htmlspecialchars($paper['custom1']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    <?php print (!empty($_SESSION['custom2'])) ? $_SESSION['custom2'] : 'Custom 2'  ?>:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom2" style="width: 99%" value="<?php print isset($paper['custom2']) ? htmlspecialchars($paper['custom2']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    <?php print (!empty($_SESSION['custom3'])) ? $_SESSION['custom3'] : 'Custom 3'  ?>:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom3" style="width: 99%" value="<?php print isset($paper['custom3']) ? htmlspecialchars($paper['custom3']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    <?php print (!empty($_SESSION['custom4'])) ? $_SESSION['custom4'] : 'Custom 4'  ?>:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom4" style="width: 99%" value="<?php print isset($paper['custom4']) ? htmlspecialchars($paper['custom4']) : ''  ?>">
                </td>
            </tr>
        </table>
    </form>
    <br><br>
    <?php
} else {
    print 'Super User or User permissions required.';
}

?>