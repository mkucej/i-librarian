<?php
include_once 'data.php';

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

include_once 'functions.php';

##########	reference fetching from PubMed	##########

if (isset($_GET['id'])) {

    ##########	open efetch, read xml	##########

    if (!empty($_GET['id'])) {

        $request_url = 'http://ieeexplore.ieee.org/xpl/downloadCitations?reload=true&citations-format=citation-abstract&download-format=download-ris&recordIds=' . urlencode($_GET['id']);

        $ris = proxy_file_get_contents($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

        if (empty($ris))
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

        function trim_arr(&$v, $k) {
            $v = trim($v);
        }

        $file_records = explode('ER  -', $ris);
        array_walk($file_records, 'trim_arr');
        $file_records = array_filter($file_records);
        if (isset($file_records[0]))
            $record = $file_records[0];
        $record = html_entity_decode($record);

        $title = preg_match("/(?<=T1  - |TI  - ).+/u", $record, $title_match);

        if ($title == 1) {

            $record_array = explode("\n", $record);

            $type_match = array();
            $secondary_title_match = array();
            $volume_match = array();
            $issue_match = array();
            $year_match = array();
            $start_page_match = array();
            $end_page_match = array();
            $keywords_match = array();
            $editors_match = array();
            $authors_match = array();
            $doi_match = array();

            foreach ($record_array as $line) {

                if (strpos($line, "TY") === 0)
                    $type_match[0] = trim(substr($line, 6));
                if (strpos($line, "JF") === 0 || strpos($line, "JO") === 0 || strpos($line, "BT") === 0 || strpos($line, "T2") === 0)
                    $secondary_title_match[0] = trim(substr($line, 6));
                if (strpos($line, "VL") === 0)
                    $volume_match[0] = trim(substr($line, 6));
                if (strpos($line, "IS") === 0)
                    $issue_match[0] = trim(substr($line, 6));
                if (strpos($line, "PY") === 0)
                    $year_match[0] = trim(substr($line, 6));
                if (strpos($line, "SP") === 0)
                    $start_page_match[0] = trim(substr($line, 6));
                if (strpos($line, "EP") === 0)
                    $end_page_match[0] = trim(substr($line, 6));
                if (strpos($line, "KW") === 0)
                    $keywords_match[0][] = trim(substr($line, 6));
                if (strpos($line, "ED") === 0 || strpos($line, "A2") === 0)
                    $editors_match[0][] = trim(substr($line, 6));
                if (strpos($line, "AU") === 0 || strpos($line, "A1") === 0)
                    $authors_match[0][] = trim(substr($line, 6));
                if (strpos($line, "DO") === 0)
                    $doi_match[0] = trim(substr($line, 6));
                if (strpos($line, "AB") === 0)
                    $abstract_match[0] = trim(substr($line, 6));
            }

            $authors = '';
            $author_array = array();
            $names = '';
            $name_array = array();

            if (!empty($authors_match[0])) {
                foreach ($authors_match[0] as $author) {
                    $author_array = explode(",", $author);
                    $first_name = '';
                    if (isset($author_array[1]))
                        $first_name = $author_array[1];
                    $name_array[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($first_name) . '"';
                }
                $names = join(";", $name_array);
                $authors = join("; ", $authors_match[0]);
            }


            $title = '';

            if (!empty($title_match[0]))
                $title = strip_tags(trim($title_match[0]));

            $year = '';

            if (!empty($year_match[0])) {

                $date_array = array();
                $month = '01';
                $day = '01';
                $date_array = explode('/', $year_match[0]);
                if (!empty($date_array[0]))
                    $year = $date_array[0];
                if (!empty($date_array[1]))
                    $month = $date_array[1];
                if (!empty($date_array[2]))
                    $day = $date_array[2];
                if (!empty($year))
                    $year = $year . '-' . $month . '-' . $day;
                if (empty($year)) {
                    preg_match('/\d{4}/u', $year_match[0], $year_match2);
                    if (!empty($year_match2[0]))
                        $year = $year_match2[0] . '-01-01';
                }
            }

            $abstract = '';

            if (!empty($abstract_match[0]))
                $abstract = strip_tags(trim($abstract_match[0]));

            $volume = '';

            if (!empty($volume_match[0]))
                $volume = trim($volume_match[0]);

            $issue = '';

            if (!empty($issue_match[0]))
                $issue = trim($issue_match[0]);

            $pages = '';

            if (!empty($start_page_match[0]))
                $pages = trim($start_page_match[0]);

            if (!empty($end_page_match[0]))
                $pages .= '-' . trim($end_page_match[0]);

            $secondary_title = '';

            if (!empty($secondary_title_match[0]))
                $secondary_title = trim($secondary_title_match[0]);

            $editor = '';

            if (!empty($editors_match[0])) {
                $order = array("\r\n", "\n", "\r");
                $editors_match[0] = str_replace($order, ' ', $editors_match[0]);
                $editors_match[0] = join("#", $editors_match[0]);
                $patterns = array(',', '.', '#', '  ');
                $replacements = array(' ', '', ', ', ' ');
                $editor = str_replace($patterns, $replacements, $editors_match[0]);
            }

            $reference_type = 'article';

            if (!empty($type_match[0]))
                $reference_type = convert_type(trim($type_match[0]), 'ris', 'ilib');

            $keywords = '';

            if (!empty($keywords_match[0])) {
                $order = array("\r\n", "\n", "\r");
                $keywords_match[0] = str_replace($order, ' ', $keywords_match[0]);
                $patterns = array('[', ']', '|', '"', '/', '*');
                $keywords_match[0] = str_replace($patterns, ' ', $keywords_match[0]);
                array_walk($keywords_match[0], 'trim');
                $keywords_match[0] = join("#", $keywords_match[0]);
                $keywords = str_replace("#", " / ", $keywords_match[0]);
            }

            $doi = '';

            if (!empty($doi_match[0]))
                $doi = trim($doi_match[0]);
        }
    }

    ##########	print results into table	##########

    print '<form enctype="application/x-www-form-urlencoded" action="upload.php" method="POST" class="fetch-form">';

    print '<div class="items">';

    print '<div>';
    if (!empty($secondary_title))
        print htmlspecialchars($secondary_title);
    if (!empty($year))
        print " (" . htmlspecialchars($year) . ")";
    if (!empty($volume))
        print " <b>" . htmlspecialchars($volume) . "</b>";
    if (!empty($issue))
        print " <i>(" . htmlspecialchars($issue) . ")</i>";
    if (!empty($pages))
        print ": " . htmlspecialchars($pages);
    print '</div>';

    if (!empty($authors))
        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($authors) . '</div>';

    print '</div>';

    print '<div class="abstract" style="padding:0 10px">';

    !empty($abstract) ? print htmlspecialchars($abstract)  : print 'No abstract available.';

    print '</div><div class="items">';
    ?>
    <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
    <input type="hidden" name="uid[]" value="<?php if (!empty($_GET['id'])) print 'IEEE:' . htmlspecialchars($_GET['id']); ?>">
    <input type="hidden" name="reference_type" value="<?php if (!empty($reference_type)) print htmlspecialchars($reference_type); ?>">
    <input type="hidden" name="authors" value="<?php if (!empty($names)) print htmlspecialchars($names); ?>">
    <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
    <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
    <input type="hidden" name="year" value="<?php if (!empty($year)) print htmlspecialchars($year); ?>">
    <input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
    <input type="hidden" name="issue" value="<?php if (!empty($issue)) print htmlspecialchars($issue); ?>">
    <input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
    <input type="hidden" name="keywords" value="<?php if (!empty($keywords)) print htmlspecialchars($keywords); ?>">
    <input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract) : "No abstract available."; ?>">

    <?php
    ##########	print full text links	##########

    print '<b>Full text options:</b><br>';

    print '<a href="' . htmlspecialchars('http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=' . $_GET['id']) . '" target="_blank">IEEE</a>';

    if (!empty($doi))
        print ' <b>&middot;</b> <a href="' . htmlspecialchars('http://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publishers Website</a>';

    print '<br><button class="save-item"><i class="fa fa-save"></i> Save</button> <button class="quick-save-item"><i class="fa fa-save"></i> Quick Save</button>';

    print '</div>';
    ?>

    </form>
    <?php
} ##########	reference fetching from PubMed	##########
?>