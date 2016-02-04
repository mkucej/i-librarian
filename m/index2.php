<?php
include_once '../data.php';
include_once '../functions.php';

//UPGRADING DATABASE
if (is_file(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'library.sq3')) {
    $isupgraded = false;
    database_connect(IL_DATABASE_PATH, 'library');
    $result = $dbHandle->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='categories'");
    $newtable = $result->fetchColumn();
    $result = null;
    $result = $dbHandle->query("PRAGMA main.table_info(library)");
    while ($libtable = $result->fetch(PDO::FETCH_NAMED)) {
        if ($libtable['name'] == 'bibtex') {
            $isupgraded = true;
            break;
        }
    }
    $result = null;
    $result = $dbHandle->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='library_log'");
    $logtable = $result->fetchColumn();
    $result = null;
    $dbHandle = null;
    //UPGRADE 2.0 to 2.1
    if ($newtable == 0)
        die('Error! Obsolete database version.');
    //UPGRADE 2.1 to 2.5
    if (!$isupgraded)
        die('Error! Obsolete database version.');
    //UPGRADE 2.7 to 2.8
    if ($logtable == 0)
        die('Error! Obsolete database version.');
}

if (file_exists('../ilibrarian.ini')) {
    $ini_array = parse_ini_file("../ilibrarian.ini");
} else {
    $ini_array = parse_ini_file("../ilibrarian-default.ini");
}

$greeting = isset($ini_array['greeting']) ? htmlspecialchars($ini_array['greeting'], ENT_COMPAT, 'UTF-8', FALSE) : 'I, Librarian';

/**
 * LDAP settings from ilibrarian.ini.
 */
$ldap_active = $ini_array['ldap_active'];
if (!extension_loaded('ldap'))
    $ldap_active = false;

/**
 * Auto-sign in.
 */
if (!isset($_POST['form']) && !isset($_SESSION['auth']) && $ini_array['autosign'] == 1) {

    database_connect(IL_USER_DATABASE_PATH, 'users');

    $username_quoted = $dbHandle->quote($ini_array['username']);

    $result = $dbHandle->query("SELECT userID, permissions FROM users WHERE username=$username_quoted");

    $row = $result->fetch(PDO::FETCH_ASSOC);
        $result = null;
    $dbHandle = null;

    extract($row);

    $_SESSION['user_id'] = $userID;
    $_SESSION['user'] = $ini_array['username'];
            $_SESSION['permissions'] = $permissions;
            $_SESSION['auth'] = true;
    $_POST['keepsigned'] = 1;

    // Set fake form submit to read settings.
    $_POST['form'] = 1;

    include 'authenticate.php';
}

?>
<!DOCTYPE html>
<html style="width:100%;height:100%">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>I, Librarian Mobile</title>
        <link rel="stylesheet" href="css/i-librarian.min.css?v=<?php print $version ?>" rel="stylesheet">
        <link rel="stylesheet" href="css/jquery.mobile.icons.min.css">
        <link rel="stylesheet" href="css/jquery.mobile.structure-1.4.5.min.css">
        <link rel="stylesheet" href="css/static.css?v=<?php print $version ?>" rel="stylesheet">
        <script type="text/javascript" src="js/jquery-1.11.3.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.mobile-1.4.5.min.js"></script>
        <script type="text/javascript" src="js/jquery.form.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/javascript.js?v=<?php print $version ?>"></script>
    </head>
    <body style="margin:0;border:0;padding:0;width:100%">
        <?php
        if (isset($_SESSION['auth'])) {

            ?>
            <div id="top-page" data-role="page" data-theme="a">
                <div id="splash2">
                    <h1><?php echo $greeting; ?></h1>
                    <h3>loading</h3>
                </div>
                <div data-role="panel" data-position="left" data-display="overlay" data-theme="none"
                     id="panel-desk" style="background-color:rgba(40,40,50,1);color:#fff;"><div>loading projects...</div>
                </div>
                <div data-role="panel" data-position="right" data-display="overlay" data-theme="none"
                     id="panel-menu" style="background-color:rgba(40,40,50,1);color:#fff;">
                    <div data-role="collapsible-set" data-content-theme="a" style="margin:0">
                        <div data-role="collapsible">
                            <h3>Search</h3>
                            <div>
                                <form id="quicksearch" action="search.php" method="GET">
                                    <input type="text" name="anywhere" placeholder="Quick Search" data-mini="true">
                                    <fieldset data-role="controlgroup" data-type="horizontal" data-mini="true">
                                        <input name="anywhere_separator" id="anywhere_separator_and" value="AND" checked type="radio">
                                        <label for="anywhere_separator_and" style="width:38px;text-align:center">and</label>
                                        <input name="anywhere_separator" id="anywhere_separator_or" value="OR" type="radio">
                                        <label for="anywhere_separator_or" style="width:38px;text-align:center">or</label>
                                        <input name="anywhere_separator" id="anywhere_separator_phrase" value="PHRASE" type="radio">
                                        <label for="anywhere_separator_phrase" style="width:48px;text-align:center">phrase</label>
                                    </fieldset>
                                    <table style="width:100%">
                                        <tr>
                                            <td style="width:50%;padding-right:2%">
                                                <button id="search" class="ui-btn ui-corner-all">Search</button>
                                            </td>
                                            <td>
                                                <button id="clear" class="ui-btn ui-corner-all">Clear</button>
                                            </td>
                                        </tr>
                                    </table>
                                    <input type="hidden" name="searchtype" value="metadata">
                                    <input type="hidden" name="searchmode" value="advanced">
                                    <input type="hidden" name="rating[]" value="1">
                                    <input type="hidden" name="rating[]" value="2">
                                    <input type="hidden" name="rating[]" value="3">
                                </form>
                            </div>
                        </div>
                        <div data-role="collapsible">
                            <h3>Sorting</h3>
                            <div>
                                <fieldset data-role="controlgroup" data-type="vertical">
                                    <input name="radio-orderby" id="radio-orderby-id" value="id" type="radio" 
                                    <?php
                                    if (empty($_SESSION['orderby']) || (!empty($_SESSION['orderby']) && $_SESSION['orderby'] == 'id'))
                                        print ' checked';

                                    ?>>
                                    <label for="radio-orderby-id">Date Added</label>
                                    <input name="radio-orderby" id="radio-orderby-year" value="year" type="radio"
                                    <?php
                                    if (!empty($_SESSION['orderby']) && $_SESSION['orderby'] == 'year')
                                        print 'checked';

                                    ?>>
                                    <label for="radio-orderby-year">Date Published</label>
                                    <input name="radio-orderby" id="radio-orderby-journal" value="journal" type="radio"
                                    <?php
                                    if (!empty($_SESSION['orderby']) && $_SESSION['orderby'] == 'journal')
                                        print 'checked';

                                    ?>>
                                    <label for="radio-orderby-journal">Journal</label>
                                    <input name="radio-orderby" id="radio-orderby-title" value="title" type="radio"
                                    <?php
                                    if (!empty($_SESSION['orderby']) && $_SESSION['orderby'] == 'title')
                                        print 'checked';

                                    ?>>
                                    <label for="radio-orderby-title">Title</label>
                                </fieldset>
                            </div>
                        </div>
                        <div data-role="collapsible">
                            <h3>Display Type</h3>
                            <div>
                                <fieldset data-role="controlgroup" data-type="vertical">
                                    <input name="radio-display" id="radio-display-titles" value="titles" type="radio"
                                    <?php
                                    if (!empty($_SESSION['display']) && $_SESSION['display'] == 'brief')
                                        print ' checked';

                                    ?>
                                           >
                                    <label for="radio-display-titles">Titles</label>
                                    <input name="radio-display" id="radio-display-icons" value="icons" type="radio"
                                    <?php
                                    if (!empty($_SESSION['display']) && $_SESSION['display'] == 'icons')
                                        print ' checked';

                                    ?>>
                                    <label for="radio-display-icons">Icons</label>
                                </fieldset>
                            </div>
                        </div>
                        <div data-role="collapsible">
                            <h3>Clipboard</h3>
                            <div>
                                <button id="add-clipboard" class="ui-btn ui-corner-all">Add All to Clipboard</button>
                                <button id="clear-clipboard" class="ui-btn ui-corner-all">Clear Clipboard</button>
                            </div>
                        </div>
                    </div>
                    <a href="#" id="link-signout" class="ui-btn ui-corner-all ui-icon-power ui-btn-icon-left" style="margin-top:6px">Sign Out</a>
                </div>
                <div data-role="content" style="padding:0;margin:0;border:0">
                    <div data-role="navbar" id="top-panel">
                        <ul>
                            <li><a href="#" id="link-library" class="ui-btn-active">Library</a></li>
                            <li><a href="#" id="link-shelf">Shelf</a></li>
                            <li><a href="#" id="link-desk">Desk</a></li>
                            <li><a href="#" id="link-clipboard">Clipboard</a></li>
                        </ul>
                    </div>
                    <div style="text-align:center;margin-top:20px">
                        <div data-role="controlgroup" data-type="horizontal" data-mini="true" style="margin:auto" id="menubar">
                            <button id="page-first">First</button>
                            <button id="page-prev">Prev</button>
                            <button id="link-menu" data-icon="bars" data-iconpos="left">Menu</button>
                            <button id="page-next">Next</button>
                            <button id="page-last">Last</button>
                        </div>
                    </div>
                    <div id="bottom-panel" style="min-height: 200px"></div>
                    <div style="text-align:center;margin:20px 0">
                        <div data-role="controlgroup" data-type="horizontal" data-mini="true" style="margin:auto" id="menubar2">
                            <button id="page-prev2" data-icon="arrow-l"><div style="width:84px">Prev</div></button>
                            <button id="page-next2" data-icon="arrow-r" data-iconpos="right"><div style="width:84px">Next</div></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {

            ?>
            <div id="signin-background" data-role="page" data-theme="a">
                <div data-role="panel" data-position="right" data-display="overlay" data-theme="none"
                     id="mypanel" style="background-color:rgba(40,40,50,1);color:#fff;padding-top:40px">
                    <button data-icon="alert" data-iconpos="left">Wrong credentials.</button>
                </div>
                <div data-role="header">
                    <div style="text-align:center;padding: 12px 0;font-size:1.2em"><?php echo $greeting; ?></div>
                </div>
                <div data-role="content">
                    <div id="splash">
                        <h1><?php echo $greeting; ?></h1>
                    </div>
                    <form action="index2.php" method="POST" id="signinform">
                        <input type="hidden" name="form" value="signin">
                        <div class="ui-body ui-body-a" style="margin:auto;width:250px">
                                <input type="text" name="user" size="10" value="" placeholder="<?php print ($ldap_active) ? 'LDAP ' : ''  ?>Username">
                                <input type="password" name="pass" size="10" value="" placeholder="Password">
                            <button id="signinbutton" class="ui-btn ui-corner-all ui-btn-icon-left ui-icon-user" data-icon="edit">Sign In</button>
                                <label><input type="checkbox" name="keepsigned" value="1" checked>Keep me signed in</label>
                        </div>
                    </form>
                </div>
                <div style="text-align: center;font-size:0.8em">
                    &copy; <?php echo date('Y'); ?> Scilico, LLC
            </div>
            </div>
            <?php
        }

        ?>
    </body>
</html>