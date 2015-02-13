<?php

//AJAX backend for the supplementary file tab
include_once 'data.php';
include_once 'functions.php';

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

session_write_close();

$error = null;
$library_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';
if (isset($_POST['filename']) && preg_match('/[^a-zA-Z0-9\.]/', $_POST['filename']) > 0)
    $error = 'Invalid request.';

##########	remove supplementary files	##########

if (!empty($_GET['files_to_delete'])) {

    while (list($key, $supplementary_file) = each($_GET['files_to_delete'])) {

        $supplementary_file = preg_replace('/[\/\\\]/', '_', $supplementary_file);
        if (is_file("library" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . "$supplementary_file"))
            unlink("library" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . "$supplementary_file");
    }
}

##########	rename supplementary files	##########

if (!empty($_POST['rename']) && !empty($_POST['file'])) {

    while (list($old_name, $new_name) = each($_POST['rename'])) {

        $old_name = preg_replace('/[\/\\\]/', '_', $old_name);

        if (is_writable("library" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . $old_name) && !empty($new_name)) {

            $new_name = preg_replace('/[\/\\\]/', '_', $new_name);
            $new_name = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $new_name);
            $new_name = substr($old_name, 0, 5) . $new_name;
            if ($old_name != $new_name)
                $rename = rename("library" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . $old_name, "library" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . $new_name);
        }
    }
}

##########	record supplementary files	##########

for ($i = 1; $i <= 5; $i++) {
    if (isset($_FILES['form_supplementary_file' . $i]) && is_uploaded_file($_FILES['form_supplementary_file' . $i]['tmp_name'])) {
        $new_name = preg_replace('/[\/\\\]/', '_', $_FILES['form_supplementary_file' . $i]['name']);
        $new_name = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '_', $new_name);
        $new_name = sprintf("%05d", intval($_POST['file'])) . $new_name;

        move_uploaded_file($_FILES['form_supplementary_file' . $i]['tmp_name'], "$library_path" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . "$new_name");
    }
}

##########	record graphical abstract	##########

if (isset($_FILES['form_graphical_abstract']) && is_uploaded_file($_FILES['form_graphical_abstract']['tmp_name'])) {
    $extension = pathinfo($_FILES['form_graphical_abstract']['name'], PATHINFO_EXTENSION);
    if (empty($extension))
        $extension = 'jpg';
    $new_name = sprintf("%05d", intval($_POST['file'])) . 'graphical_abstract.' . $extension;
    move_uploaded_file($_FILES['form_graphical_abstract']['tmp_name'], "$library_path" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . "$new_name");
}

##########	replace PDF	##########

if (isset($_FILES['form_new_file']) && is_uploaded_file($_FILES['form_new_file']['tmp_name'])) {
    
    $file_extension = pathinfo($_FILES['form_new_file']['name'], PATHINFO_EXTENSION);

    if (in_array($file_extension, array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'))) {
        $move = move_uploaded_file($_FILES['form_new_file']['tmp_name'], $temp_dir . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name']);
        if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
            putenv('HOME=' . $temp_dir);
        exec(select_soffice() . ' --headless --convert-to pdf --outdir "' . $temp_dir . '" "' . $temp_dir . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name'] . '"');
        if (PHP_OS == 'Linux' || PHP_OS == 'Darwin')
            putenv('HOME=""');
        $converted_file = $temp_dir . DIRECTORY_SEPARATOR . basename($_FILES['form_new_file']['name'], '.' . $file_extension) . '.pdf';
        if (!is_file($converted_file)) {
            die("Error! Conversion to PDF failed.<br>" . htmlspecialchars($title));
        } else {
            copy($converted_file, $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename']);
            $supplement_filename = sprintf("%05d", intval($_POST['filename'])) . $_FILES['form_new_file']['name'];
            copy($temp_dir . DIRECTORY_SEPARATOR . $_FILES['form_new_file']['name'], $library_path . DIRECTORY_SEPARATOR . 'supplement' . DIRECTORY_SEPARATOR . $supplement_filename);
            unlink($converted_file);
        }
    } else {
        move_uploaded_file($_FILES['form_new_file']['tmp_name'], $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename']);
    }
}

if (!empty($_POST['form_new_file_link'])) {
    $pdf_contents = proxy_file_get_contents($_POST['form_new_file_link'], $proxy_name, $proxy_port, $proxy_username, $proxy_password);
    if (empty($pdf_contents))
        die('Error! I, Librarian could not find the PDF. Possible reasons:<br><br>You access the Web through a proxy server. Enter your proxy details in Tools->Settings.<br><br>The external service may be temporarily down. Try again later.<br><br>The link you provided is not for a PDF.');
    file_put_contents($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename'], $pdf_contents);
}

if (!empty($_POST['filename']) && is_writable($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename'])) {

    $uploaded_file_content = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename'], FILE_BINARY, null, 0, 100);

    if (stripos($uploaded_file_content, '%PDF') === 0) {
        copy($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename'], $library_path . DIRECTORY_SEPARATOR . $_POST['filename']);
        unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $_POST['filename']);
        $hash = md5_file($library_path . DIRECTORY_SEPARATOR . $_POST['filename']);
    } else {
        $error = "This is not a PDF.";
    }

    //RECORD FILE HASH FOR DUPLICATE DETECTION
    if (!empty($hash)) {
        database_connect($database_path, 'library');
        $hash = $dbHandle->quote($hash);
        $file = $dbHandle->quote($_POST['filename']);
        $dbHandle->exec('UPDATE library SET filehash=' . $hash . ' WHERE file=' . $file);
        $dbHandle = null;
    }

    ##########	extract text from pdf	##########

    if (!isset($error)) {

        $filename = $_POST['filename'];

        if (is_file($library_path . DIRECTORY_SEPARATOR . $filename)) {

            system(select_pdftotext() . '"' . $library_path . DIRECTORY_SEPARATOR . $filename . '" "' . $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $filename . '.txt"', $ret);

            if (is_file($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $filename . ".txt")) {

                $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, during, each, edu, eg, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, followed, following, follows, for, former, formerly, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, several, shall, she, should, shouldn't, since, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, this, thorough, thoroughly, those, though, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, un, under, unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves";

                $stopwords = explode(', ', $stopwords);

                $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $filename . ".txt");
                unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . $filename . ".txt");

                if (!empty($string)) {

                    $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);

                    $patterns = join("\b/ui /\b", $stopwords);
                    $patterns = "/\b$patterns\b/ui";
                    $patterns = explode(" ", $patterns);

                    $order = array("\r\n", "\n", "\r");
                    $string = str_replace($order, ' ', $string);
                    $string = preg_replace($patterns, '', $string);
                    $string = preg_replace('/\s{2,}/ui', ' ', $string);

                    $fulltext_array = array();
                    $fulltext_unique = array();

                    $fulltext_array = explode(" ", $string);
                    $fulltext_unique = array_unique($fulltext_array);
                    $string = implode(" ", $fulltext_unique);

                    $output = null;

                    database_connect($database_path, 'fulltext');
                    $file_query = $dbHandle->quote(intval($_POST['file']));
                    $fulltext_query = $dbHandle->quote($string);

                    $dbHandle->beginTransaction();
                    $result = $dbHandle->query("SELECT id FROM full_text WHERE fileID=$file_query LIMIT 1");
                    $record_exists = $result->fetchColumn();
                    $result = null;
                    if (!$record_exists)
                        $output = $dbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES ($file_query,$fulltext_query)");
                    if ($record_exists)
                        $output = $dbHandle->exec("UPDATE full_text SET full_text=$fulltext_query WHERE id=$record_exists");
                    $dbHandle->commit();
                    $dbHandle = null;
                } else {
                    $error = "File recorded, however, a text extraction error occured.";
                }
            } else {
                $error = "File recorded, however, text extracting was not allowed.";
            }
        } else {
            $error = "File not found.";
        }
    }
}

// DELETE USERS' CACHED PAGES BECAUSE FILES HAVE CHANGED
$clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR . 'page_*', GLOB_NOSORT);
if (is_array($clean_files)) {
    foreach ($clean_files as $clean_file) {
        if (is_file($clean_file) && is_writable($clean_file))
            @unlink($clean_file);
    }
}

if (!empty($error))
    print "Error! " . $error;
?>