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

include_once 'functions.php';

##########	reference fetching from PubMed	##########

if (isset($_GET['id'])) {

    ##########	open efetch, read xml	##########

    if (!empty($_GET['id'])) {

        $request_url = 'https://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=Pubmed&rettype=abstract&retmode=XML&id=' . urlencode($_GET['id']) . '&tool=I,Librarian&email=i.librarian.software@gmail.com';

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

        if (empty($xml))
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');


        foreach ($xml->PubmedArticle->PubmedData->ArticleIdList->ArticleId as $articleid) {

            foreach ($articleid->attributes() as $a => $b) {

                if ($a == 'IdType' && $b == 'doi')
                    $doi = $articleid[0];
            }
        }

        $pmid = $xml->PubmedArticle->MedlineCitation->PMID;

        $pmcid = $_GET['pmcid'];

        $reference_type = 'article';

        $title = $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;

        $xml_abstract = $xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText;

        if (!empty($xml_abstract)) {
            foreach ($xml_abstract as $mini_ab) {
                foreach ($mini_ab->attributes() as $a => $b) {
                    if ($a == 'Label')
                        $mini_ab = $b . ": " . $mini_ab;
                }
                $abstract_array[] = "$mini_ab";
            }
            $abstract = implode(' ', $abstract_array);
        }

        $secondary_title = $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;

        $day = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day;
        $month = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month;
        $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;

        if (empty($year)) {
            $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->MedlineDate;
            preg_match('/\d{4}/', $year, $year_match);
            $year = $year_match[0];
        }

        $date = '';
        if (!empty($year)) {
            if (empty($day))
                $day = '01';
            if (empty($month))
                $month = '01';
            $date = date('Y-m-d', strtotime($day . '-' . $month . '-' . $year));
        }

        $volume = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;

        $issue = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;

        $pages = $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;

        $journal_abbr = $xml->PubmedArticle->MedlineCitation->MedlineJournalInfo->MedlineTA;

        $affiliation = '';

        $authors = $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author;

        if (!empty($authors)) {
            foreach ($authors as $author) {
                $name_array[] = $author->LastName . ', ' . $author->ForeName;
                $last_name[] = (string) $author->LastName;
                $first_name[] = (string) $author->ForeName;
                if (empty($affiliation))
                    $affiliation = $author->AffiliationInfo->Affiliation;
            }
        }

        $keywords = $xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading;

        if (!empty($keywords)) {

            foreach ($keywords as $keywordsheading) {

                $keywords_array[] = $keywordsheading->DescriptorName;
            }
        }

        if (isset($name_array))
            $names = join("; ", $name_array);
        if (isset($keywords_array))
            $keywords = join(" / ", $keywords_array);
    }

    if (empty($_GET['id']) && !empty($_GET['pmcid'])) {

        $request_url = 'https://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pmc&rettype=abstract&retmode=XML&id=' . urlencode($_GET['pmcid']) . '&tool=I,Librarian&email=i.librarian.software@gmail.com';

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

        foreach ($xml->article->front->{'article-meta'}->{'article-id'} as $articleid) {

            foreach ($articleid->attributes() as $a => $b) {

                if ($a == 'pub-id-type' && $b == 'doi')
                    $doi = $articleid[0];
                if ($a == 'pub-id-type' && $b == 'pmid')
                    $pmid = $articleid[0];
            }
        }

        $pmcid = $_GET['pmcid'];

        $reference_type = 'article';

        $title = strip_tags($xml->article->front->{'article-meta'}->{'title-group'}->{'article-title'}->asXML());

        $xml_abstract = $xml->article->front->{'article-meta'}->abstract;

        if (!empty($xml_abstract)) {
            foreach ($xml_abstract as $mini_ab) {
                foreach ($mini_ab->attributes() as $a => $b) {
                    if ($a == 'Label')
                        $mini_ab = $b . ": " . $mini_ab;
                }
                $abstract_array[] = "$mini_ab";
            }
            $abstract = implode(' ', $abstract_array);
            $abstract = trim($abstract);
        }

        if (empty($abstract))
            $abstract = trim(strip_tags((string) $xml->article->front->{'article-meta'}->abstract->asXML()));

        foreach ($xml->article->front->{'journal-meta'}->{'journal-id'} as $journalid) {

            foreach ($journalid->attributes() as $a => $b) {

                if ($a == 'journal-id-type' && $b == 'nlm-ta')
                    $journal_abbr = $journalid[0];
            }
        }

        $secondary_title = $xml->article->front->{'journal-meta'}->{'journal-title-group'}->{'journal-title'};

        $year = (string) $xml->article->front->{'article-meta'}->{'pub-date'}->year;
        $month = (string) $xml->article->front->{'article-meta'}->{'pub-date'}->month;
        $day = (string) $xml->article->front->{'article-meta'}->{'pub-date'}->day;

        if (empty($day))
            $day = '01';
        if (empty($month))
            $month = '01';
        if (empty($year))
            $year = '1969';
        $date = date('Y-m-d', strtotime($day . '-' . $month . '-' . $year));

        $volume = $xml->article->front->{'article-meta'}->volume;

        $issue = $xml->article->front->{'article-meta'}->issue;

        $fpage = $xml->article->front->{'article-meta'}->fpage;

        $lpage = $xml->article->front->{'article-meta'}->lpage;

        $elocation = $xml->article->front->{'article-meta'}->{'elocation-id'};

        if (!empty($fpage)) {

            if ($fpage > $lpage) {

                $pages = "$fpage-$lpage";
            } else {

                $pages = $fpage;
            }
        } elseif (!empty($elocation)) {

            $pages = $elocation;
        }

        $affiliation = strip_tags($xml->article->front->{'article-meta'}->aff->asXML());

        $authors = $xml->article->front->{'article-meta'}->{'contrib-group'};

        if (!empty($authors)) {

            foreach ($authors->contrib as $author) {

                foreach ($author->attributes() as $a => $b) {

                    if ($a == 'contrib-type' && $b == 'author') {
                        $name_array[] = $author->name->surname . ", " . $author->name->{'given-names'};
                        $last_name[] = (string) $author->name->surname;
                        $first_name[] = (string)$author->name->{'given-names'};
                    }
                }
            }
        }

        $keywords = $xml->article->front->{'article-meta'}->{'kwd-group'}->kwd;

        if (!empty($keywords)) {

            foreach ($keywords as $keyword) {

                $keywords_array[] = $keyword;
            }
        }

        if (isset($name_array))
            $names = join("; ", $name_array);
        if (isset($keywords_array))
            $keywords = join(" / ", $keywords_array);
    }

    ##########	print results into table	##########

    print '<form enctype="application/x-www-form-urlencoded" action="upload.php" method="POST" class="fetch-form">';

    print '<div class="items">';

    print '<div>';
    if (!empty($journal_abbr))
        print htmlspecialchars($journal_abbr);
    if (!empty($year))
        print " (" . htmlspecialchars($date) . ")";
    if (!empty($volume))
        print " " . htmlspecialchars($volume);
    if (!empty($issue))
        print " ($issue)";
    if (!empty($pages))
        print ": " . htmlspecialchars($pages);
    print '</div>';

    if (!empty($names)) {
        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names) . '</div>';
        $array = explode(';', $names);
        $array = array_filter($array);
        if (!empty($array)) {
            foreach ($array as $author) {
                $array2 = explode(',', $author);
                $last = trim($array2[0]);
                $first = '';
                if (isset($array2[1])) {
                    $first = trim($array2[1]);
                }
                $new_authors[] = 'L:"' . $last . '",F:"' . $first . '"';
            }
            $names = join(';', $new_authors);
        }
    }
    if (!empty($affiliation))
        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($affiliation) . '</div>';

    print '</div>';

    print '<div class="abstract" style="padding:0 10px">';

    !empty($abstract) ? print htmlspecialchars($abstract) : print 'No abstract available.';

    print '</div><div class="items">';

    $uid_array[] = 'PMCID:' . $pmcid;
    if (!empty($pmid))
        $uid_array[] = 'PMID:' . $pmid;
    foreach ($uid_array as $uid) {
        print '<input type="hidden" name="uid[]" value="' . htmlspecialchars($uid) . '">';
    }
    $url_array[] = 'https://www.ncbi.nlm.nih.gov/pmc/articles/PMC' . $pmcid . '/';
    if (!empty($pmid))
        $url_array[] = 'https://www.ncbi.nlm.nih.gov/pubmed/' . $pmid;
    foreach ($url_array as $url) {
        print '<input type="hidden" name="url[]" value="' . htmlspecialchars($url) . '">';
    }
    ?>
    <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
    <input type="hidden" name="reference_type" value="<?php if (!empty($reference_type)) print htmlspecialchars($reference_type); ?>">
    <input type="hidden" name="last_name" value="<?php if (!empty($last_name)) print htmlspecialchars(json_encode($last_name)); ?>">
    <input type="hidden" name="first_name" value="<?php if (!empty($first_name)) print htmlspecialchars(json_encode($first_name)); ?>">
    <input type="hidden" name="affiliation" value="<?php if (!empty($affiliation)) print htmlspecialchars($affiliation); ?>">
    <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
    <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
    <input type="hidden" name="journal_abbr" value="<?php if (!empty($journal_abbr)) print htmlspecialchars($journal_abbr); ?>">
    <input type="hidden" name="year" value="<?php if (!empty($date)) print htmlspecialchars($date); ?>">
    <input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
    <input type="hidden" name="issue" value="<?php if (!empty($issue)) print htmlspecialchars($issue); ?>">
    <input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
    <input type="hidden" name="keywords" value="<?php if (!empty($keywords)) print htmlspecialchars($keywords); ?>">
    <input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract) : "No abstract available."; ?>">
    <input type="hidden" name="form_new_file_link" value="<?php print !empty($pmcid) ? htmlspecialchars("https://www.ncbi.nlm.nih.gov/pmc/articles/PMC" . $pmcid . "/pdf") : ""; ?>">

    <?php
    ##########	print full text links	##########

    print '<b>Full text options:</b><br>';

    print '<a href="' . htmlspecialchars('https://www.ncbi.nlm.nih.gov/pmc/articles/PMC' . $pmcid . '/') . '" target="_blank">
	PubMed Central</a>';

    print ' <b>&middot;</b> <a href="' . htmlspecialchars('https://www.ncbi.nlm.nih.gov/pmc/articles/PMC' . $pmcid . '/pdf/') . '" target="_blank">
	Full Text PDF</a>';

    if (!empty($doi))
        print ' <b>&middot;</b> <a href="' . htmlspecialchars('https://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publishers Website</a>';

    print '<br><button class="save-item"><i class="fa fa-save"></i> Save</button> <button class="quick-save-item"><i class="fa fa-save"></i> Quick Save</button>';

    print '</div>';
    ?>

    </form>
    <?php
} ##########	reference fetching from PubMed	##########
?>