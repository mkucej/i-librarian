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

##########	reference fetching from Crossref	##########

if (isset($_GET['doi'])) {

    $response = array();
    fetch_from_crossref($_GET['doi']);
    extract($response);

    $names_str = '';
    if (!empty($authors)) {
        $names = array();
        $new_names = array();
        $names = explode(';', $authors);
        $names = array_filter($names);
        if (!empty($names)) {
            foreach ($names as $name) {
                $array2 = explode(',', $name);
                $last = trim($array2[0]);
                $last = substr($array2[0], 3, -1);
                $first = '';
                if (isset($array2[1])) {
                    $first = trim($array2[1]);
                    $first = substr($array2[1], 3, -1);
                }
                if (!empty($last))
                    $new_names[] = $last . ', ' . $first;
            }
            $names_str = join('; ', $new_names);
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
        print " " . htmlspecialchars($volume);
    if (!empty($issue))
        print " ($issue)";
    if (!empty($pages))
        print ": " . htmlspecialchars($pages);
    print '</div>';

    if (!empty($names))
        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names_str) . '</div>';
    ?>
    <input type="hidden" name="doi" value="<?php if (!empty($_GET['doi'])) print htmlspecialchars($_GET['doi']); ?>">
    <input type="hidden" name="reference_type" value="<?php if (!empty($reference_type)) print htmlspecialchars($reference_type); ?>">
    <input type="hidden" name="last_name" value="<?php if (!empty($last_name)) print htmlspecialchars(json_encode($last_name)); ?>">
    <input type="hidden" name="first_name" value="<?php if (!empty($first_name)) print htmlspecialchars(json_encode($first_name)); ?>">
    <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
    <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
    <input type="hidden" name="year" value="<?php if (!empty($year)) print htmlspecialchars($year); ?>">
    <input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
    <input type="hidden" name="issue" value="<?php if (!empty($issue)) print htmlspecialchars($issue); ?>">
    <input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
    <input type="hidden" name="form_new_file_link" value="<?php print !empty($_GET['pdf']) ? htmlspecialchars($_GET['pdf']) : ""; ?>">

    <?php
    ##########	print full text links	##########

    print '<a href="' . htmlspecialchars("https://dx.doi.org/" . urlencode($_GET['doi'])) . '" target="_blank">Publisher Website</a>';

    if (!empty($_GET['pdf']))
        print ' &middot; <a href="' . htmlspecialchars($_GET['pdf']) . '" target="_blank">PDF</a>';

    print '<br><button class="save-item"><i class="fa fa-save"></i> Save</button> <button class="quick-save-item"><i class="fa fa-save"></i> Quick Save</button>';

    print '</div>';
    ?>
    </form>
    <?php
} ##########	reference fetching from Crossref	##########
?>