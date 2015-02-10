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
    
    fetch_from_ieee($_GET['id']);
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
                $first = trim($array2[1]);
                $first = substr($array2[1], 3, -1);
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
        print " <b>" . htmlspecialchars($volume) . "</b>";
    if (!empty($issue))
        print " <i>(" . htmlspecialchars($issue) . ")</i>";
    if (!empty($pages))
        print ": " . htmlspecialchars($pages);
    print '</div>';

    if (!empty($names_str))
        print '<div class="authors"><i class="author_expander fa fa-plus-circle"></i> ' . htmlspecialchars($names_str) . '</div>';

    print '</div>';

    print '<div class="abstract" style="padding:0 10px">';

    !empty($abstract) ? print htmlspecialchars($abstract)  : print 'No abstract available.';

    print '</div><div class="items">';
    ?>
    <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
    <input type="hidden" name="uid[]" value="<?php if (!empty($_GET['id'])) print 'IEEE:' . htmlspecialchars($_GET['id']); ?>">
    <input type="hidden" name="reference_type" value="<?php if (!empty($reference_type)) print htmlspecialchars($reference_type); ?>">
    <input type="hidden" name="authors" value="<?php if (!empty($authors)) print htmlspecialchars($authors); ?>">
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