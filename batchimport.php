<?php
##short ajax dir check###
if (isset($_GET['check_dir']) && !empty($_GET['directory'])) {
    if (is_dir($_GET['directory']) && is_readable($_GET['directory'])) {
        die('1');
    } else {
        die('0');
    }
}

include_once 'data.php';

if ($hosted == true)
    die();

if (!empty($_GET['user']))
    $user = $_GET['user'];
if (!empty($_GET['userID']))
    $userID = $_GET['userID'];

if (!isset($_GET['commence'])) {

    if (isset($_SESSION['auth']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

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

        if (empty($_GET['directory'])) {

            database_connect($usersdatabase_path, 'users');
            $batchimport_dir = get_setting($dbHandle, 'batchimport_dir');
            $batchimport_recursive = get_setting($dbHandle, 'batchimport_recursive');
            $dbHandle = null;
            ?>
            <br>
            <br>
            <div style="width:600px;margin:auto">
                <div class="ui-state-highlight ui-corner-all" style="float:left;margin-bottom:4px;padding:1px 4px;cursor:auto">
                    <i class="fa fa-signin"></i>
                    PDF Batch Import
                </div>
            </div>
            <div style="clear:both"></div>
            <div class="item-sticker ui-widget-content ui-corner-all" style="width:600px;margin:auto">
                <div class="alternating_row items ui-corner-top">
                    Import PDF files from directory:<br>
                    <button class="open-dirs-button" title="Browse directories"><i class="fa fa-folder-open"></i></button>
                    <form id="batchimportform" action="batchimport.php" method="GET">
                        <input type="text" name="directory" value="<?php print $batchimport_dir; ?>" size="45" style="width:510px"><br>
                        <table cellspacing=0 style="margin: 2px 0px 2px 0px">
                            <tr>
                                <td class="select_span">
                                    <input type="checkbox" name="recursive" value="1" style="display:none" <?php print empty($batchimport_recursive) ? '' : 'checked'  ?>>
                                    <i class="fa fa-<?php print empty($batchimport_recursive) ? 'check-square' : 'square-o'  ?>"></i>
                                    Include files in subdirectories.
                                </td>
                            </tr>
                        </table>
                </div>
                <div class="separator" style="margin:0"></div>
                <div class="alternating_row items ui-corner-bottom">
                    <button id="batchimportbutton"><i class="fa fa-save"></i> Import</button>
                    </form>
                </div>
            </div>
            <?php
        } else {

            if (substr($_GET['directory'], -1) == DIRECTORY_SEPARATOR)
                $_GET['directory'] = substr($_GET['directory'], 0, -1);

            database_connect($usersdatabase_path, 'users');
            save_setting($dbHandle, 'batchimport_dir', $_GET['directory']);
            if (isset($_GET['recursive'])) {
                save_setting($dbHandle, 'batchimport_recursive', $_GET['recursive']);
            } else {
                save_setting($dbHandle, 'batchimport_recursive', '');
            }
            $batchimport_database_pubmed = get_setting($dbHandle, 'batchimport_database_pubmed');
            $batchimport_database_nasaads = get_setting($dbHandle, 'batchimport_database_nasaads');
            $batchimport_database_crossref = get_setting($dbHandle, 'batchimport_database_crossref');
            $batchimport_failed = get_setting($dbHandle, 'batchimport_failed');
            $batchimport_log = get_setting($dbHandle, 'batchimport_log');
            $dbHandle = null;

            $pdf_files = array();

            $lit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_GET['directory']), RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
            $lit->rewind();
            while ($lit->valid()) {
                $file = $lit->key();
                if (is_readable($file) && pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
                    $pdf_files[] = $file;
                }
                $lit->next();
            }

            $pdf_count = count($pdf_files);
            ?>
            <div class="ui-state-highlight ui-corner-all" style="float:left;margin:4px;padding:1px 4px;cursor:auto">
                <i class="fa fa-signin"></i>
                PDF Batch Import
            </div>
            <div style="clear:both"></div>
            <form id="batchimportform2" action="batchimport.php" method="GET">
                <input type="hidden" name="commence" value="">
                <input type="hidden" name="directory" value="<?php print htmlspecialchars($_GET['directory']); ?>">
                <input type="hidden" name="recursive" value="<?php print isset($_GET['recursive']) ? '1' : '0'; ?>">
                <input type="hidden" name="user" value="<?php print htmlspecialchars($_SESSION['user']); ?>">
                <input type="hidden" name="userID" value="<?php print htmlspecialchars($_SESSION['user_id']); ?>">
                <input type="hidden" name="proxy_name" value="<?php print htmlspecialchars($proxy_name); ?>">
                <input type="hidden" name="proxy_port" value="<?php print htmlspecialchars($proxy_port); ?>">
                <input type="hidden" name="proxy_username" value="<?php print htmlspecialchars($proxy_username); ?>">
                <input type="hidden" name="proxy_password" value="<?php print htmlspecialchars($proxy_password); ?>">
                <table cellspacing="0" style="width: 100%;border-top: solid 1px #D5D6D9">
                    <tr>
                        <td valign="top" class="threedleft">
                            <button id="batchimportbutton2" <?php
                            if ($pdf_count == 0)
                                print 'disabled';
                            ?>><i class="fa fa-save"></i> Import</button>
                        </td>
                        <td valign="top" class="threedright">
                            <table cellspacing=0>
                                <tr>
                                    <td class="select_span" style="line-height:22px;width:10em">
                                        <input type="checkbox" checked class="uploadcheckbox" style="display:none" name="shelf">
                                        &nbsp;<i class="fa fa-check-square"></i>
                                        Add to Shelf
                                    </td>
                                    <td class="select_span" style="line-height:22px;width: 10em;text-align:right">
                                        <input type="checkbox" class="uploadcheckbox" style="display:none" name="project">
                                        <i class="fa fa-square-o"></i>
                                        Add&nbsp;to&nbsp;Project&nbsp;
                                    </td>
                                    <td style="line-height:22px;width: 18em">
                                        <select name="projectID" style="width:200px">
                                            <?php
                                            database_connect($database_path, 'library');

                                            $desktop_projects = array();
                                            $desktop_projects = read_desktop($dbHandle);

                                            foreach ($desktop_projects as $project) {
                                                print '<option value="' . $project['projectID'] . '">' . htmlspecialchars($project['project']) . '</option>' . PHP_EOL;
                                            }

                                            $dbHandle = null;
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <table cellspacing="0" style="width: 100%" id="table1">
                    <tr><td class="threedleft">Info:</td>
                        <td class="threedright" style="padding-left: 18px">
                            Found <?php print $pdf_count; ?> file<?php print ($pdf_count == 1) ? '' : 's'; ?>.
                            (Note that PDFs must contain a <a href="http://en.wikipedia.org/wiki/Digital_object_identifier" target="_blank">DOI</a> in order to track the corresponding metadata.)
                            <?php
                            if ($pdf_count > 100) {
                                ?>
                                <div class="batch-errors" style="display:none">Warning! Uploading too many files<br> puts a load on the database servers and may
                                    result in banning your access to the used database. If possible, limit the number of PDFs to 100 at a time.</div>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                    <tr><td class="threedleft">Select database:</td>
                        <td class="threedright">
                            <table>
                                <?php if (!isset($_SESSION['remove_pubmed'])) { ?>
                                    <tr>
                                        <td class="select_span"><input type="checkbox" name="database_pubmed" value="1" style="display:none" <?php print (isset($batchimport_database_pubmed) && $batchimport_database_pubmed == '1') ? 'checked' : ''  ?>>
                                            &nbsp;<i class="fa fa-<?php print (isset($batchimport_database_pubmed) && $batchimport_database_pubmed == '1') ? 'check-square' : 'square-o'  ?>"></i> PubMed (biomedicine)
                                        </td>
                                    </tr>
                                <?php }
                                if (!isset($_SESSION['remove_nasaads'])) {
                                    ?>
                                    <tr>
                                        <td class="select_span"><input type="checkbox" name="database_nasaads" value="1" style="display:none" <?php print (isset($batchimport_database_nasaads) && $batchimport_database_nasaads == '1') ? 'checked' : ''  ?>>
                                            &nbsp;<i class="fa fa-<?php print (isset($batchimport_database_nasaads) && $batchimport_database_nasaads == '1') ? 'check-square' : 'square-o'  ?>"></i> NASA ADS (physics, astronomy)
                                        </td>
                                    </tr>
            <?php } ?>
                                <tr>
                                    <td class="select_span"><input type="checkbox" name="database_crossref" value="1" style="display:none" <?php print (isset($batchimport_database_crossref) && $batchimport_database_crossref == '1') ? 'checked' : ''  ?>>
                                        &nbsp;<i class="fa fa-<?php print (isset($batchimport_database_crossref) && $batchimport_database_crossref == '1') ? 'check-square' : 'square-o'  ?>"></i> CrossRef (other sciences)
                                    </td>
                                </tr>
                            </table>
                        </td></tr>
                    <tr><td class="threedleft">If metadata not found:</td>
                        <td class="threedright" style="line-height: 16px">
                            <table cellspacing="0">
                                <tr>
                                    <td class="select_span" style="line-height: 16px">
                                        <input type="checkbox" name="failed" value="1" style="display: none" <?php print (isset($batchimport_failed) && $batchimport_failed == '1') ? 'checked' : ''  ?>>
                                        &nbsp;<i class="fa fa-<?php print (isset($batchimport_failed) && $batchimport_failed == '1') ? 'check-square' : 'square-o'  ?>"></i>
                                        Import the PDF into the category !unknown. All PDF files will be recorded and indexed!
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="threedleft">During import:</td>
                        <td class="threedright" style="line-height: 16px">
                            <table cellspacing="0">
                                <tr>
                                    <td class="select_span" style="line-height: 16px">
                                        <input type="radio" name="log" value="1" style="display: none" <?php print (empty($batchimport_log) || (isset($batchimport_log) && $batchimport_log == '1')) ? 'checked' : ''  ?>>
                                        &nbsp;<i class="fa fa-circle<?php print (empty($batchimport_log) || (isset($batchimport_log) && $batchimport_log == '1')) ? '' : '-o'  ?>"></i>
                                        Continue working, let the import run in background.
                                    </td>
                                </tr>
                                <tr>
                                    <td class="select_span" style="line-height: 16px">
                                        <input type="radio" name="log" value="2" style="display: none" <?php print (isset($batchimport_log) && $batchimport_log == '2') ? 'checked' : ''  ?>>
                                        &nbsp;<i class="fa fa-circle<?php print (isset($batchimport_log) && $batchimport_log == '2') ? '' : '-o'  ?>"></i>
                                        Watch the import log.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="threedleft">
                            Choose&nbsp;category:<br>
                        </td>
                        <td class="threedright">
                            <div class="categorydiv" style="width: 99%;overflow:scroll; height: 400px;background-color: white;color: black;border: 1px solid #C5C6C9">
                                <table cellspacing=0 style="float:left;width: 49%">
                                    <?php
                                    $category_string = null;
                                    database_connect($database_path, 'library');
                                    $result = $dbHandle->query("SELECT count(*) FROM categories");
                                    $totalcount = $result->fetchColumn();
                                    $result = null;

                                    $i = 1;
                                    $isdiv = null;
                                    $result = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
                                    while ($category = $result->fetch(PDO::FETCH_ASSOC)) {
                                        if ($i > (1 + $totalcount / 2) && !$isdiv) {
                                            print '</table><table cellspacing=0 style="width: 49%;float: right;padding:2px">';
                                            $isdiv = true;
                                        }
                                        print PHP_EOL . '<tr><td class="select_span">';
                                        print "<input type=\"checkbox\" name=\"category[]\" value=\"" . htmlspecialchars($category['categoryID']) . "\"";
                                        print " style=\"display:none\">&nbsp;<i class=\"fa fa-square-o\"></i> " . htmlspecialchars($category['category']) . "</td></tr>";
                                        $i = $i + 1;
                                    }
                                    $result = null;
                                    $dbHandle = null;
                                    ?>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="threedleft">
                            Add to new categories:
                        </td>
                        <td class="threedright">
                            <input type="text" size="30" name="category2[]" value=""><br>
                            <input type="text" size="30" name="category2[]" value=""><br>
                            <input type="text" size="30" name="category2[]" value="">
                        </td>
                    </tr>
                </table>
            </form>
            <?php
        }
    } else {
        print 'Super User or User permissions required.';
    }
} else {

    if (isset($_GET['proxy_name']))
        $proxy_name = $_GET['proxy_name'];
    if (isset($_GET['proxy_port']))
        $proxy_port = $_GET['proxy_port'];
    if (isset($_GET['proxy_username']))
        $proxy_username = $_GET['proxy_username'];
    if (isset($_GET['proxy_password']))
        $proxy_password = $_GET['proxy_password'];

    include_once 'functions.php';

    ########create log file###########
    $log = $temp_dir . DIRECTORY_SEPARATOR . md5($_GET['user']) . '-librarian-import.log';
    file_put_contents($log, '<b>Log:</b><br>');

    $database_pubmed = '';
    $database_nasaads = '';
    $database_crossref = '';
    $failed = '';
    $batchimport_log = '';

    if (isset($_GET['database_pubmed']))
        $database_pubmed = $_GET['database_pubmed'];
    if (isset($_GET['database_nasaads']))
        $database_nasaads = $_GET['database_nasaads'];
    if (isset($_GET['database_crossref']))
        $database_crossref = $_GET['database_crossref'];
    if (isset($_GET['failed']))
        $failed = $_GET['failed'];
    if (isset($_GET['log']))
        $batchimport_log = $_GET['log'];

    database_connect($usersdatabase_path, 'users');
    save_setting($dbHandle, 'batchimport_database_pubmed', $database_pubmed);
    save_setting($dbHandle, 'batchimport_database_nasaads', $database_nasaads);
    save_setting($dbHandle, 'batchimport_database_crossref', $database_crossref);
    save_setting($dbHandle, 'batchimport_failed', $failed);
    save_setting($dbHandle, 'batchimport_log', $batchimport_log);
    $dbHandle = null;

    $user_dir = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id();

    session_write_close();
    include_once 'functions.php';

    if (substr($_GET['directory'], -1) == DIRECTORY_SEPARATOR)
        $_GET['directory'] = substr($_GET['directory'], 0, -1);

    $files = array();

    $lit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($_GET['directory']), RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
    $lit->rewind();
    while ($lit->valid()) {
        $file = $lit->key();
        if (is_readable($file) && pathinfo($file, PATHINFO_EXTENSION) == 'pdf') {
            $files[] = $file;
        }
        $lit->next();
    }

    $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, during, each, edu, eg, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, followed, following, follows, for, former, formerly, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, several, shall, she, should, shouldn't, since, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, this, thorough, thoroughly, those, though, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, un, under, unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves";

    $stopwords = explode(', ', $stopwords);

    $patterns = join("\b/ui /\b", $stopwords);
    $patterns = "/\b$patterns\b/ui";
    $patterns = explode(" ", $patterns);

    $order = array("\r\n", "\n", "\r");

    $i = 0;

    while (list($key, $file) = each($files)) {

        $i = $i + 1;

        if (is_readable($file)) {

            $string = '';
            $xml = '';
            $record = '';
            $count = '';
            $url = '';
            $authors = '';
            $authors_array = array();
            $affiliation = '';
            $title = '';
            $abstract = '';
            $secondary_title = '';
            $tertiary_title = '';
            $year = '';
            $volume = '';
            $issue = '';
            $pages = '';
            $last_page = '';
            $journal_abbr = '';
            $keywords = '';
            $name_array = array();
            $mesh_array = array();
            $new_file = '';
            $addition_date = date('Y-m-d');
            $rating = 2;
            $uid = '';
            $editor = '';
            $reference_type = 'article';
            $publisher = '';
            $place_published = '';
            $doi = '';
            $authors_ascii = '';
            $title_ascii = '';
            $abstract_ascii = '';
            $unpacked_files = array();
            $temp_file = $temp_dir . DIRECTORY_SEPARATOR . $_GET['user'] . "_librarian_temp" . $i . ".txt";

            if (file_exists($temp_file))
                unlink($temp_file);

            ##########	extract text from pdf	##########

            system(select_pdftotext() . '"' . $file . '" "' . $temp_file . '"', $ret);

            if (file_exists($temp_file))
                $string = file_get_contents($temp_file);

            if (empty($string)) {

                if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                    database_connect($database_path, 'library');
                    record_unknown($dbHandle, '', $string, $file, $userID);

                    $put = " ($i) " . basename($file) . ": Recorded into category !unknown. Full text not indexed (copying disallowed).<br>";
                    file_put_contents($log, $put, FILE_APPEND);
                } else {

                    $put = " ($i) " . basename($file) . ": copying disallowed.<br>";
                    file_put_contents($log, $put, FILE_APPEND);
                }
            } else {

                $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);

                $string = str_replace($order, ' ', $string);
                $order = array("\xe2\x80\x93", "\xe2\x80\x94");
                $replace = '-';
                $string = str_replace($order, $replace, $string);

                preg_match_all('/10\.\d{4}\/\S+/ui', $string, $doi, PREG_PATTERN_ORDER);

                if (count($doi[0]) < 1) {

                    if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                        $string = preg_replace($patterns, ' ', $string);
                        $string = preg_replace('/(^|\s)\S{1,2}(\s|$)/u', ' ', $string);
                        $string = preg_replace('/\s{2,}/u', " ", $string);

                        $fulltext_array = array();
                        $fulltext_unique = array();

                        $fulltext_array = explode(" ", $string);
                        $fulltext_unique = array_unique($fulltext_array);
                        $string = implode(" ", $fulltext_unique);

                        database_connect($database_path, 'library');
                        record_unknown($dbHandle, '', $string, $file, $userID);

                        $put = " ($i) " . basename($file) . ": Recorded into category !unknown. DOI not found.<br>";
                        file_put_contents($log, $put, FILE_APPEND);
                    } else {

                        $put = " ($i) " . basename($file) . ": DOI not found.<br>";
                        file_put_contents($log, $put, FILE_APPEND);
                    }
                } else {

                    $doi = $doi[0][0];

                    if (substr($doi, -1) == '.')
                        $doi = substr($doi, 0, -1);
                    if (substr($doi, -1) == ')' || substr($doi, -1) == ']') {
                        preg_match_all('/(.)(doi:\s?)?(10\.\d{4}\/\S+)/ui', $string, $doi2, PREG_PATTERN_ORDER);
                        if (substr($doi, -1) == ')' && $doi2[1][0] == '(')
                            $doi = substr($doi, 0, -1);
                        if (substr($doi, -1) == ']' && $doi2[1][0] == '[')
                            $doi = substr($doi, 0, -1);
                    }

                    $title = '';

                    if (isset($_GET['database_pubmed']) && $_GET['database_pubmed'] == '1') {

                        $put = " ($i) " . basename($file) . ": Querying Pubmed with $doi <br>";
                        file_put_contents($log, $put, FILE_APPEND);

                        $response = array();
                        fetch_from_pubmed($doi, '');
                        extract($response);

                        $uid = join("|", (array) $uid);
                        $url = join("|", (array) $url);
                    }

                    if (isset($_GET['database_nasaads']) && $_GET['database_nasaads'] == '1' && empty($title)) {

                        $put = " ($i) " . basename($file) . ": Querying NASA ADS with $doi <br>";
                        file_put_contents($log, $put, FILE_APPEND);

                        $response = array();
                        fetch_from_nasaads($doi, '');
                        extract($response);

                        $uid = join("|", (array) $uid);
                        $url = join("|", (array) $url);
                    }

                    if (isset($_GET['database_crossref']) && $_GET['database_crossref'] == '1' && empty($title)) {

                        $put = " ($i) " . basename($file) . ": Querying CrossRef with $doi <br>";
                        file_put_contents($log, $put, FILE_APPEND);

                        ############ CrossRef ##############
                        $response = array();
                        fetch_from_crossref($doi);
                        extract($response);
                    }

                    //TRY AGAIN WITH DOI ONE CHARACTER SHORTER
                    if (empty($title)) {

                        $doi = substr($doi, 0, -1);

                        if (isset($_GET['database_pubmed']) && $_GET['database_pubmed'] == '1') {

                            $put = " ($i) " . basename($file) . ": Querying PubMed again with $doi <br>";
                            file_put_contents($log, $put, FILE_APPEND);

                            ##########	open esearch, fetch PMID	##########

                            $response = array();
                            fetch_from_pubmed($doi, '');
                            extract($response);

                            $uid = join("|", (array) $uid);
                            $url = join("|", (array) $url);
                        }

                        if (isset($_GET['database_nasaads']) && $_GET['database_nasaads'] == '1' && empty($title)) {

                            $put = " ($i) " . basename($file) . ": Querying NASA ADS again with $doi <br>";
                            file_put_contents($log, $put, FILE_APPEND);

                            $response = array();
                            fetch_from_nasaads($doi, '');
                            extract($response);

                            $uid = join("|", (array) $uid);
                            $url = join("|", (array) $url);
                        }

                        if (isset($_GET['database_crossref']) && $_GET['database_crossref'] == '1' && empty($title)) {

                            $put = " ($i) " . basename($file) . ": Querying CrossRef again with $doi<br>";
                            file_put_contents($log, $put, FILE_APPEND);

                            ############ CrossRef ##############
                            $response = array();
                            fetch_from_crossref($doi);
                            extract($response);
                        }
                    }

                    if (empty($title)) {

                        if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                            $string = preg_replace($patterns, ' ', $string);
                            $string = preg_replace('/(^|\s)\S{1,2}(\s|$)/', ' ', $string);
                            $string = preg_replace('/\s{2,}/', " ", $string);

                            $fulltext_array = array();
                            $fulltext_unique = array();

                            $fulltext_array = explode(" ", $string);
                            $fulltext_unique = array_unique($fulltext_array);
                            $string = implode(" ", $fulltext_unique);

                            database_connect($database_path, 'library');
                            record_unknown($dbHandle, '', $string, $file, $userID);

                            $put = " ($i) " . basename($file) . ": Recorded into category !unknown. No database record found.<br>";
                            file_put_contents($log, $put, FILE_APPEND);
                        } else {

                            $put = " ($i) " . basename($file) . ": No database record found.<br>";
                            file_put_contents($log, $put, FILE_APPEND);
                        }
                    }

                    if (!empty($title)) {
                        
                        database_connect($database_path, 'library');

                        if (!empty($authors))
                            $authors_ascii = utf8_deaccent($authors);

                        $title_ascii = utf8_deaccent($title);

                        if (!empty($abstract))
                            $abstract_ascii = utf8_deaccent($abstract);

                        ##########	record publication data, table library	##########

                        $query = "INSERT INTO library (file, authors, affiliation, title, journal, year, addition_date, abstract, rating, uid, volume, issue, pages,
                            secondary_title, tertiary_title, editor,
                            url, reference_type, publisher, place_published, keywords, doi, authors_ascii, title_ascii, abstract_ascii, added_by)
                            VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :affiliation,
                            :title, :journal, :year, :addition_date, :abstract, :rating, :uid, :volume, :issue, :pages, :secondary_title, :tertiary_title, :editor,
                            :url, :reference_type, :publisher, :place_published, :keywords, :doi, :authors_ascii, :title_ascii, :abstract_ascii, :added_by)";

                        $stmt = $dbHandle->prepare($query);

                        $stmt->bindParam(':authors', $authors, PDO::PARAM_STR);
                        $stmt->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
                        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                        $stmt->bindParam(':journal', $journal_abbr, PDO::PARAM_STR);
                        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
                        $stmt->bindParam(':addition_date', $addition_date, PDO::PARAM_STR);
                        $stmt->bindParam(':abstract', $abstract, PDO::PARAM_STR);
                        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
                        $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
                        $stmt->bindParam(':volume', $volume, PDO::PARAM_STR);
                        $stmt->bindParam(':issue', $issue, PDO::PARAM_STR);
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
                        $stmt->bindParam(':added_by', $userID, PDO::PARAM_INT);

                        $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

                        $stmt->execute();
                        $stmt = null;

                        $last_insert = $dbHandle->query("SELECT last_insert_rowid(),max(file) FROM library");
                        $last_row = $last_insert->fetch(PDO::FETCH_ASSOC);
                        $last_insert = null;
                        $id = $last_row['last_insert_rowid()'];
                        $new_file = $last_row['max(file)'];
                        
                        if (isset($_GET['shelf']) && !empty($userID)) {
                            $user_query = $dbHandle->quote($userID);
                            $file_query = $dbHandle->quote($id);
                            $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");

                            @unlink($user_dir . DIRECTORY_SEPARATOR . 'shelf_files');
                        }

                        if (isset($_GET['project']) && !empty($_GET['projectID'])) {
                            $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_GET['projectID']) . "," . intval($id) . ")");

                            $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR . 'desk_files', GLOB_NOSORT);
                            if (is_array($clean_files)) {
                                foreach ($clean_files as $clean_file) {
                                    if (is_file($clean_file) && is_writable($clean_file))
                                        @unlink($clean_file);
                                }
                            }
                        }

                        ####### record new category into categories, if not exists #########

                        if (!empty($_GET['category2'])) {

                            $_GET['category2'] = preg_replace('/\s{2,}/', '', $_GET['category2']);
                            $_GET['category2'] = preg_replace('/^\s$/', '', $_GET['category2']);
                            $_GET['category2'] = array_filter($_GET['category2']);

                            $query = "INSERT INTO categories (category) VALUES (:category)";
                            $stmt = $dbHandle->prepare($query);
                            $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

                            while (list($key, $new_category) = each($_GET['category2'])) {
                                $new_category_quoted = $dbHandle->quote($new_category);
                                $result = $dbHandle->query("SELECT categoryID FROM categories WHERE category=$new_category_quoted");
                                $exists = $result->fetchColumn();
                                $category_ids[] = $exists;
                                $result = null;
                                if (empty($exists)) {
                                    $stmt->execute();
                                    $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM categories");
                                    $category_ids[] = $last_id->fetchColumn();
                                    $last_id = null;
                                }
                            }
                            $stmt = null;
                        }

                        ####### record new relations into filescategories #########

                        $categories = array();

                        if (!empty($_GET['category']) || !empty($category_ids)) {
                            $categories = array_merge((array) $_GET['category'], (array) $category_ids);
                            $categories = array_filter(array_unique($categories));
                        }

                        $query = "INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES (:fileid,:categoryid)";

                        $stmt = $dbHandle->prepare($query);
                        $stmt->bindParam(':fileid', $id);
                        $stmt->bindParam(':categoryid', $category_id);

                        while (list($key, $category_id) = each($categories)) {
                            if (!empty($id))
                                $stmt->execute();
                        }
                        $stmt = null;

                        $dbHandle->exec("COMMIT");

                        copy($file, dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . $new_file);

                        $hash = md5_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . $new_file);

                        //RECORD FILE HASH FOR DUPLICATE DETECTION
                        if (!empty($hash)) {
                            $hash = $dbHandle->quote($hash);
                            $dbHandle->exec('UPDATE library SET filehash=' . $hash . ' WHERE id=' . $id);
                        }

                        $dbHandle = null;

                        $string = preg_replace($patterns, ' ', $string);
                        $string = preg_replace('/(^|\s)\S{1,2}(\s|$)/', ' ', $string);
                        $string = preg_replace('/\s{2,}/', " ", $string);

                        $fulltext_array = array();
                        $fulltext_unique = array();

                        $fulltext_array = explode(" ", $string);
                        $fulltext_unique = array_unique($fulltext_array);
                        $string = implode(" ", $fulltext_unique);

                        database_connect($database_path, 'fulltext');

                        $file_query = $dbHandle->quote($id);
                        $fulltext_query = $dbHandle->quote($string);

                        $dbHandle->beginTransaction();
                        $dbHandle->exec("DELETE FROM full_text WHERE fileID=$file_query");
                        $insert = $dbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES ($file_query,$fulltext_query)");
                        $dbHandle->commit();

                        $dbHandle = null;

                        $unpack_dir = $temp_dir . DIRECTORY_SEPARATOR . $new_file;
                        @mkdir($unpack_dir);
                        exec(select_pdftk() . '"' . $library_path . DIRECTORY_SEPARATOR . $new_file . '" unpack_files output "' . $unpack_dir . '"');
                        $unpacked_files = scandir($unpack_dir);
                        foreach ($unpacked_files as $unpacked_file) {
                            if (is_file($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file))
                                @rename($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file, $library_path . DIRECTORY_SEPARATOR . supplement . DIRECTORY_SEPARATOR . sprintf("%05d", intval($new_file)) . $unpacked_file);
                        }
                        @rmdir($unpack_dir);

                        $put = " ($i) " . basename($file) . ": Recorded.<br>";
                        file_put_contents($log, $put, FILE_APPEND);
                    }
                }
            }
        } else {
            $put = " ($i) " . basename($file) . ": Not readable.<br>";
            file_put_contents($log, $put, FILE_APPEND);
        }
    } ####while loop
    ##########  ANALYZE  ##########
    database_connect($database_path, 'library');
    $dbHandle->exec("ANALYZE");
    $dbHandle = null;

    ###### clean the temp directory ########
    for ($j = $i; $j >= 1; $j--) {

        $temp_file = $temp_dir . DIRECTORY_SEPARATOR . $_GET['user'] . "_librarian_temp" . $j . ".txt";
        if (file_exists($temp_file))
            unlink($temp_file);
    }

    print "OK";
    sleep(1);
    @unlink($log);
}
?>