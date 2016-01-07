<?php
include_once 'data.php';
include_once '../functions.php';

//UPGRADING DATABASE
if (is_file($database_path . 'library.sq3')) {
    $isupgraded = false;
    database_connect($database_path, 'library');
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

$ini_array = parse_ini_file("../ilibrarian.ini");

/////////////// LDAP SETTINGS //////////////////////////////
$ldap_active = $ini_array['ldap_active'];
$ldap_version = $ini_array['ldap_version'];
$ldap_server = $ini_array['ldap_server'];
$ldap_port = $ini_array['ldap_port'];
$ldap_basedn = $ini_array['ldap_basedn'];
$ldap_binduser_rdn = $ini_array['ldap_binduser_rdn'];
$ldap_binduser_pw = $ini_array['ldap_binduser_pw'];
$ldap_user_rdn = $ini_array['ldap_user_rdn'];
$ldap_group_rdn = $ini_array['ldap_group_rdn'];
$ldap_username_attr = $ini_array['ldap_username_attr'];
$ldap_usergroup_cn = $ini_array['ldap_usergroup_cn'];
$ldap_admingroup_cn = $ini_array['ldap_admingroup_cn'];
$ldap_filter = $ini_array['ldap_filter'];
if (!extension_loaded('ldap'))
    $ldap_active = false;
/////////////// END LDAP SETTINGS //////////////////////////////
///////////////start sign out//////////////////////////////

if (isset($_GET['action']) && $_GET['action'] == 'signout') {
    // DELETE USER'S FILE CACHE
    $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);
    if (is_array($clean_files)) {
        foreach ($clean_files as $clean_file) {
            if (is_file($clean_file) && is_writable($clean_file))
                @unlink($clean_file);
        }
    }
    $_SESSION = array();
    session_regenerate_id(TRUE);
    session_destroy();
    die('OK');
}
///////////////end sign out////////////////////////////////
///////////////auto sign in start////////////////////////////////

if (!isset($_POST['form']) && !isset($_SESSION['auth']) && $ini_array['autosign'] == 1) {

    database_connect($usersdatabase_path, 'users');
    $quoted_user = $dbHandle->quote($ini_array['username']);
    $autosign_query = $dbHandle->query("SELECT password FROM users WHERE username=$quoted_user");
    if ($autosign_query)
        $autosign = true;

    if ($autosign) {
        $autosign_user = $autosign_query->fetch(PDO::FETCH_ASSOC);
        $_POST['form'] = 'signin';
        $_POST['user'] = $ini_array['username'];
        $_POST['pass'] = $autosign_user['password'];
        $_POST['keepsigned'] = 1;
    }
    $autosign_query = null;
    $dbHandle = null;
}

///////////////auto sign in end////////////////////////////////
///////////////start authentication////////////////////////
if (isset($_POST['form']) && $_POST['form'] == 'signin' && !empty($_POST['user']) && !empty($_POST['pass']) && !isset($_SESSION['auth'])) {

    $username = trim($_POST['user']);
    $password = trim($_POST['pass']);

    database_connect($usersdatabase_path, 'users');

    $username_quoted = $dbHandle->quote($username);

    $dbHandle->exec("CREATE TABLE IF NOT EXISTS logins (
            id INTEGER PRIMARY KEY,
            userID INTEGER NOT NULL DEFAULT '',
            sessionID TEXT NOT NULL DEFAULT '',
            logintime TEXT NOT NULL DEFAULT ''
            )");

    if ($ini_array['autosign'] == 0) {

        // IMPOSE 2 SEC SIGN IN TIME OUT TO ELIMINATE BRUTE FORCE ATTACKS

        $result = $dbHandle->query("SELECT logintime FROM logins WHERE sessionID='" . session_id() . "' AND
            userID=(SELECT userID FROM users WHERE username=" . $username_quoted . ")");
        $logintime = $result->fetchColumn();
        $result = null;

        if (!$logintime) {
            $dbHandle->exec("INSERT INTO logins (userID,sessionID,logintime)
                VALUES ((SELECT userID FROM users WHERE username=" . $username_quoted . "),'" . session_id() . "','" . time() . "')");
        } else {
            $dbHandle->exec("UPDATE logins SET logintime='" . time() . "' WHERE sessionID='" . session_id() . "'
                AND userID=(SELECT userID FROM users WHERE username=" . $username_quoted . ")");
        }

        if ((time() - $logintime) < 2)
            die('Failed sign-in time out in effect.');
    }

    /* IS THE USER AN LDAP USER? */
    if ($ldap_active) {

        /* CONNECT */
        if (!$ldap_connect = ldap_connect($ldap_server, $ldap_port))
            die("Could not connect to LDAP server");

        if (!ldap_set_option($ldap_connect, LDAP_OPT_PROTOCOL_VERSION, $ldap_version))
            die("Failed to set version to protocol $ldap_version");

        /* BIND */
        $ldap_binduser_dn = $ldap_binduser_rdn . ',' . $ldap_basedn;
        if (!$ldap_bind = @ldap_bind($ldap_connect, $ldap_binduser_dn, $ldap_binduser_pw))
            die("Failed to bind as proxy user.");

        /* LOOKUP */
        /* Users matching the following criteria are eligible:
         * - must be a person object of class user or iNetOrgPerson
         * - username must match the CN attribute specified in INI file
         * - must be situated below the base search DN
         */

        $ldap_filter_string = '(&(|(objectClass=user)(objectClass=iNetOrgPerson))' .
            '(' . $ldap_username_attr . '=' . $username . '))';

        if (!$ldap_sr = @ldap_search($ldap_connect, $ldap_user_rdn . ',' . $ldap_basedn, $ldap_filter_string, array($ldap_username_attr)))
            die("Bad username or password.");
        $ldap_num_entries = ldap_count_entries($ldap_connect, $ldap_sr);
        if ($ldap_num_entries != 1)
            die("Bad username or password.");
        $ldap_user_sr = ldap_first_entry($ldap_connect, $ldap_sr);
        $ldap_user_dn = ldap_get_dn($ldap_connect, $ldap_user_sr);

        /* AUTHENTICATE */
        if ($ldap_bind = @ldap_bind($ldap_connect, $ldap_user_dn, $password)) {

            /* AUTHORIZE */
            /* Check if user is in admin group */
            $ldap_admin_group_dn = $ldap_admingroup_cn . ',' . $ldap_group_rdn . ',' . $ldap_basedn;
            $ldap_sr = @ldap_read($ldap_connect, $ldap_admin_group_dn, '(' . $ldap_filter . '=' . $ldap_user_dn . ')', array('member'));
            $ldap_info_group = @ldap_get_entries($ldap_connect, $ldap_sr);

            if ($ldap_info_group['count'] > 0) {
                $permissions = 'A';
            } else {
                /* If we don't have a ldap_usergroup_cn setting, assume all
                 * users under the search base are eligible */
                if (is_null($ldap_usergroup_cn)) {
                    $permissions = 'U';
                } else {
                    $ldap_user_group_dn = $ldap_usergroup_cn . ',' . $ldap_group_rdn . ',' . $ldap_basedn;
                    $ldap_sr = @ldap_read($ldap_connect, $ldap_user_group_dn, '(' . $ldap_filter . '=' . $user_dn . ')', array('member'));
                    $ldap_info_group = @ldap_get_entries($ldap_connect, $ldap_sr);
                    if ($ldap_info_group['count'] > 0) {
                        $permissions = 'U';
                    } else {
                        die("Bad username or password.");
                    }
                }
            }

            $dbHandle->beginTransaction();

            $count = $dbHandle->query("SELECT count(*) FROM users WHERE username=$username_quoted LIMIT 1");
            $rows = $count->fetchColumn();
            $count = null;

            if ($rows == 0) {

                $dbHandle->exec("INSERT INTO users (username,password,permissions) VALUES ($username_quoted,'','$permissions')");

                $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM users");
                $id = $last_id->fetchColumn();
                $last_id = null;

                $dbHandle->exec("INSERT INTO projects (userID,project) VALUES ($id,$username_quoted || '''s project', '1')");
            }

            $result = $dbHandle->query("SELECT userID FROM users WHERE username=$username_quoted LIMIT 1");
            $id = $result->fetchColumn();
            $result = null;

            $dbHandle->commit();

            $_SESSION['user_id'] = $id;
            $_SESSION['user'] = $_POST['user'];
            $_SESSION['permissions'] = $permissions;
            $_SESSION['auth'] = true;
        }
    } else {

        /* IF LDAP NOT ENABLED, CHECK THE LOCAL DB */
        if (check_encrypted_password($dbHandle, $username, $password)) {

            $result = $dbHandle->query("SELECT userID,permissions FROM users WHERE username=" . $username_quoted);
            $user = $result->fetch(PDO::FETCH_ASSOC);
            $result = null;

            if (!empty($user['userID'])) {
                $_SESSION['user_id'] = $user['userID'];
                $_SESSION['user'] = $_POST['user'];
                $_SESSION['permissions'] = $user['permissions'];
                $_SESSION['auth'] = true;
                $_SESSION['watermarks'] = '';
            }
        }
    }

    /* OK, THIS IS A REGISTERED USER. DO THE PROXY SETTINGS AND CREATE A TEMP DIR */
    if (isset($_SESSION['auth'])) {

        if ($ini_array['autosign'] == 0) {

            // ALLOW ONLY ONE LOGIN AT A TIME

            $result = $dbHandle->query("SELECT sessionID FROM logins WHERE sessionID != '" . session_id() . "' AND
            userID=(SELECT userID FROM users WHERE username=" . $username_quoted . ")");

            while ($oldsession = $result->fetch(PDO::FETCH_ASSOC)) {
                @unlink($temp_dir . DIRECTORY_SEPARATOR . 'I,_Librarian_sessions' . DIRECTORY_SEPARATOR . 'sess_' . $oldsession['sessionID']);
                $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . $oldsession['sessionID'] . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);
                if (is_array($clean_files)) {
                    foreach ($clean_files as $clean_file) {
                        if (is_file($clean_file) && is_writable($clean_file))
                            @unlink($clean_file);
                    }
                }
            }
            $result = null;
            $dbHandle->query("DELETE FROM logins WHERE sessionID != '" . session_id() . "' AND
            userID=(SELECT userID FROM users WHERE username=" . $username_quoted . ")");
        }

        if (isset($_POST['keepsigned']) && $_POST['keepsigned'] == 1) {
            $keepsigned = 1;
            save_setting($dbHandle, 'keepsigned', '1');
            setcookie(session_name(), session_id(), time() + 604800);
        } else {
            save_setting($dbHandle, 'keepsigned', '');
            setcookie(session_name(), session_id(), 0);
        }

        $connection = '';
        $proxy_setting = array();

        $proxy = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE setting_name LIKE 'settings_global_%'");

        $proxy_settings = $proxy->fetchAll(PDO::FETCH_ASSOC);

        while (list($key, $proxy_setting) = each($proxy_settings)) {
            if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'proxy') {
                $connection = 'proxy';
            }
            if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'autodetect') {
                $_SESSION['connection'] = "autodetect";
            }
            if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'url') {
                $_SESSION['connection'] = "url";
            }
            if ($proxy_setting['setting_name'] == 'settings_global_wpad_url') {
                $_SESSION['wpad_url'] = $proxy_setting['setting_value'];
            }
            if ($proxy_setting['setting_name'] == 'settings_global_watermarks') {
                $_SESSION['watermarks'] = $proxy_setting['setting_value'];
            }
        }

        if ($connection == "proxy") {
            $proxy_setting = array();
            reset($proxy_settings);
            while (list($key, $proxy_setting) = each($proxy_settings)) {
                $setting_name = substr($proxy_setting['setting_name'], 16);
                $_SESSION[$setting_name] = $proxy_setting['setting_value'];
            }
        }

        $result = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE userID=" . intval($_SESSION['user_id']));

        while ($custom_settings = $result->fetch(PDO::FETCH_ASSOC)) {

            $_SESSION[substr($custom_settings['setting_name'], 9)] = $custom_settings['setting_value'];
        }

        //MOBILE VERSION - ALWAYS START WITH TITLES
        $_SESSION['display'] = 'brief';

        ####### create directory for caching ########

        @mkdir($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id());
    } else {
        die('Bad username or password.');
    }

    $dbHandle = null;

    if (!isset($autosign) || $autosign != 1)
        die('OK');
}
///////////////end authentication/////////////////////////
?>
<!DOCTYPE html>
<html style="width:100%;height:100%">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>I, Librarian Mobile <?php print $version ?></title>
        <link type="text/css" href="css/librarian-mobile.min.css?v=<?php print $version ?>" rel="stylesheet">
        <link rel="stylesheet" href="css/jquery.mobile.structure-1.3.1.min.css">
        <link type="text/css" href="css/static.css?v=<?php print $version ?>" rel="stylesheet">
        <script type="text/javascript" src="js/jquery-1.9.1.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.mobile.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery.form.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/javascript.js?v=<?php print $version ?>"></script>
    </head>
    <body style="margin:0;border:0;padding:0;width:100%">
        <?php
        if (isset($_SESSION['auth'])) {
            ?>
            <div id="top-page" data-role="page" data-theme="a">
                <div id="splash2">
                    <h1>I, Librarian</h1>
                    <h3>loading</h3>
                </div>
                <div data-role="panel" data-position="left" data-display="overlay" data-theme="none"
                     id="panel-desk" style="background-color:rgba(0,0,0,0.85);color:#fff;"><div>loading projects...</div>
                </div>
                <div data-role="panel" data-position="right" data-display="overlay" data-theme="none"
                     id="panel-menu" style="background-color:rgba(0,0,0,0.85);color:#fff;">
                    <div data-role="collapsible-set" data-content-theme="a" style="margin:0">
                        <div data-role="collapsible">
                            <h3>Search</h3>
                            <div>
                                <form id="quicksearch" action="search.php" method="GET">
                                    <input type="text" name="anywhere" placeholder="Quick Search" data-mini="true">
                                    <fieldset data-role="controlgroup" data-type="horizontal" data-mini="true">
                                        <input name="anywhere_separator" id="anywhere_separator_and" value="AND" checked type="radio">
                                        <label for="anywhere_separator_and" style="width:67px">AND</label>
                                        <input name="anywhere_separator" id="anywhere_separator_or" value="OR" type="radio">
                                        <label for="anywhere_separator_or" style="width:67px">OR</label>
                                        <input name="anywhere_separator" id="anywhere_separator_phrase" value="PHRASE" type="radio">
                                        <label for="anywhere_separator_phrase" style="width:72px">phrase</label>
                                    </fieldset>
                                    <table style="width:100%">
                                        <tr>
                                            <td style="width:64%;padding-right:2%">
                                                <button id="search">Search</button>
                                            </td>
                                            <td>
                                                <button id="clear">Clear</button>
                                            </td>
                                        </tr>
                                    </table>
                                    <input type="hidden" name="searchtype" value="metadata">
                                    <input type="hidden" name="searchmode" value="quick">
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
                                <button id="add-clipboard">Add All to Clipboard</button>
                                <button id="clear-clipboard">Clear Clipboard</button>
                            </div>
                        </div>
                    </div>
                    <a href="#" id="link-signout" data-role="button" data-inline="true" data-icon="back" data-mini="true" style="margin-top:6px">Sign Out</a>
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
                            <button id="page-prev"><div style="width:37px">Prev</div></button>
                            <button id="link-menu" data-icon="bars" data-iconpos="left">Menu</button>
                            <button id="page-next"><div style="width:37px">Next</div></button>
                            <button id="page-last">Last</button>
                        </div>
                    </div>
                    <div id="bottom-panel" style="min-height: 200px"></div>
                    <div style="text-align:center;margin:20px 8px">
                        <div data-role="controlgroup" data-type="horizontal" data-mini="true" style="margin:auto" id="menubar2">
                            <button id="page-prev2" data-icon="arrow-l"><div style="width:107px">Prev</div></button>
                            <button id="page-next2" data-icon="arrow-r" data-iconpos="right"><div style="width:107px">Next</div></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div id="signin-background" data-role="page" data-theme="a">
                <div data-role="panel" data-position="right" data-display="overlay" data-theme="none"
                     id="mypanel" style="background-color:rgba(0,0,0,0.85);color:#fff;padding-top:40px">
                    <button data-icon="alert" data-iconpos="left">Wrong credentials.</button>
                </div>
                <div data-role="header">
                    <div style="text-align:center;padding: 12px 0;font-size:1.2em">I, Librarian Mobile</div>
                    <div style="text-align: center;font-size:0.8em;padding-bottom:8px;display:none">
                        <?php print $version; ?> &copy; 2013 Martin Kucej GPLv3
                    </div>
                </div>
                <div data-role="content">
                    <div id="splash">
                        <h1>I, Librarian</h1>
                    </div>
                    <form action="index2.php" method="POST" id="signinform">
                        <input type="hidden" name="form" value="signin">
                        <ul data-role="listview" data-inset="true" style="width:290px;margin:auto;margin-top:12px">
                            <li style="padding:0 10px">
                                <input type="text" name="user" size="10" value="" placeholder="<?php print ($ldap_active) ? 'LDAP ' : ''  ?>Username">
                                <input type="password" name="pass" size="10" value="" placeholder="Password">
                                <button id="signinbutton" data-icon="edit" data-inline="false">Sign In</button>
                                <label><input type="checkbox" name="keepsigned" value="1" checked>Keep me signed in</label>
                            </li>
                        </ul>
                    </form>
                </div>
            </div>
            <?php
        }
        ?>
    </body>
</html>
