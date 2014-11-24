<?php
include_once 'data.php';
include_once 'functions.php';

//UPGRADING DATABASE
if (is_file($database_path . 'library.sq3')) {
    $isupgraded = false;
    $is_2_11 = false;
    database_connect($database_path, 'library');
    $result = $dbHandle->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='categories'");
    get_db_error($dbHandle, basename(__FILE__), __LINE__);
    $newtable = $result->fetchColumn();
    $result = null;
    $result = $dbHandle->query("PRAGMA main.table_info(library)");
    get_db_error($dbHandle, basename(__FILE__), __LINE__);
    while ($libtable = $result->fetch(PDO::FETCH_NAMED)) {
        if ($libtable['name'] == 'bibtex') {
            $isupgraded = true;
        }
        if ($libtable['name'] == 'tertiary_title') {
            $is_2_11 = true;
        }
    }
    $result = null;
    $result = $dbHandle->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='library_log'");
    get_db_error($dbHandle, basename(__FILE__), __LINE__);
    $logtable = $result->fetchColumn();
    $result = null;
    $dbHandle = null;
//UPGRADE 2.0 to 2.1
    if ($newtable == 0)
        include_once 'migrate.php';
//UPGRADE 2.1 to 2.5
    if (!$isupgraded)
        include_once 'migrate2.php';
//UPGRADE 2.7 to 2.8
    if ($logtable == 0)
        include_once 'migrate3.php';
//UPGRADE 2.10 to 2.11
    if (!$is_2_11)
        include_once 'migrate4.php';
}

// GARBAGE COLLECTION
if (mt_rand(1, 10) == 5) {
    // REMOVE EMPTY USER CACHE DIRS
    $clean_dirs = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*', GLOB_NOSORT);
    if (is_array($clean_dirs)) {
        foreach ($clean_dirs as $clean_dir) {
            if (is_dir($clean_dir) && is_writable($clean_dir))
                @rmdir($clean_dir);
        }
    }
    // CLEAN PNG CACHE IF OVER 10,000 FILES
    $pngs = glob($library_path . "pngs" . DIRECTORY_SEPARATOR . "*.png", GLOB_NOSORT);
    if (is_array($pngs)) {
        if (count($pngs) > 10000) {
            foreach ($pngs as $png) {
                $arr[$png] = filemtime($png);
            }
            asort($arr);
            reset($arr);
            for ($i = 0; $i <= 1000; $i++) {
                @unlink(key($arr));
                next($arr);
            }
        }
    }
}
if (mt_rand(1, 100) == 50) {
    // CLEAN GLOBAL TEMP CACHE
    $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);
    if (is_array($clean_files)) {
        foreach ($clean_files as $clean_file) {
            if (is_file($clean_file) && is_writable($clean_file))
                @unlink($clean_file);
        }
    }
}

$ini_array = parse_ini_file("ilibrarian.ini");

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
    session_regenerate_id(true);
    session_destroy();
    die('OK');
}
///////////////end sign out////////////////////////////////
///////////////start register new user////////////////////
if (!$ldap_active && isset($_POST['form']) && $_POST['form'] == 'signup' && !empty($_POST['user']) && !empty($_POST['pass']) && !empty($_POST['pass2'])) {

    if ($_POST['pass'] == $_POST['pass2']) {

        $slashes = array("/", "\\");
        $_POST['user'] = str_replace($slashes, "", $_POST['user']);

        if (strlen($_POST['pass']) < 8)
            die('Password must be at least 8 characters long.');

        database_connect($database_path, 'library');

        $quoted_path = $dbHandle->quote($usersdatabase_path . 'users.sq3');

        $dbHandle->exec("ATTACH DATABASE $quoted_path AS userdatabase");

        $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

        $result = $dbHandle->query("SELECT count(*) FROM userdatabase.users");
        $users = $result->fetchColumn();
        $result = null;

        $result = $dbHandle->query("SELECT setting_value FROM userdatabase.settings WHERE setting_name='settings_global_default_permissions' LIMIT 1");
        $default_permissions = $result->fetchColumn();
        $result = null;

        // FIRST USER MUST BE ADMIN, OTHERS WILL HAVE DEFAULT PERMISSIONS
        if ($users == 0) {
            $permissions = 'A';
        } else {
            !empty($default_permissions) ? $permissions = $default_permissions : $permissions = 'U';
        }

        $rows = 0;

        $quoted_user = $dbHandle->quote($_POST['user']);

        if ($users > 0) {
            $count = $dbHandle->query("SELECT count(*) FROM userdatabase.users WHERE username=$quoted_user LIMIT 1");
            $rows = $count->fetchColumn();
            $count = null;
        }

        // REGISTER NEW USER
        if ($rows == 0) {

            $quoted_password = $dbHandle->quote(generate_encrypted_password($_POST['pass']));
            $dbHandle->exec("INSERT INTO userdatabase.users (username,password,permissions) VALUES (" . $quoted_user . "," . $quoted_password . ",'" . $permissions . "')");

            $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM userdatabase.users");
            $id = $last_id->fetchColumn();
            $last_id = null;

            $dbHandle->exec("INSERT INTO projects (userID,project,active) VALUES ($id,$quoted_user || '''s project', '1')");

            $_SESSION['user_id'] = $id;
            $_SESSION['user'] = $_POST['user'];
            $_SESSION['permissions'] = $permissions;
            $_SESSION['auth'] = true;
        } else {

            // CHECK IF PASSWORD IS EMPTY (FORMER LDAP USERS)
            $result = $dbHandle->query("SELECT userID,password,permissions FROM userdatabase.users WHERE username=" . $quoted_user);
            $existing_user = $result->fetch(PDO::FETCH_ASSOC);
            $result = null;
            extract($existing_user);

            // CHECK FOR FORMER LDAP USERS
            if (empty($password)) {

                $quoted_password = $dbHandle->quote(generate_encrypted_password($_POST['pass']));
                $dbHandle->exec("UPDATE userdatabase.users SET password=" . $quoted_password . " WHERE username=" . $quoted_user);

                $_SESSION['user_id'] = $userID;
                $_SESSION['user'] = $_POST['user'];
                $_SESSION['permissions'] = $permissions;
                $_SESSION['auth'] = true;
            } else {

                $dbHandle->exec("ROLLBACK");
                die('Username already exists.');
            }
        }

        $dbHandle->exec("COMMIT TRANSACTION");

        if (isset($_SESSION['auth'])) {

            $connection = '';
            $proxy_setting = array();

            $proxy = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE setting_name LIKE 'settings_global_%'");

            $proxy_settings = $proxy->fetchAll(PDO::FETCH_ASSOC);

            while (list($key, $proxy_setting) = each($proxy_settings)) {
                if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'proxy') {
                    $connection = 'proxy';
                    break;
                }
                if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'autodetect') {
                    $_SESSION['connection'] = "autodetect";
                    break;
                }
                if ($proxy_setting['setting_name'] == 'settings_global_connection' && $proxy_setting['setting_value'] == 'url') {
                    $_SESSION['connection'] = "url";
                }
                if ($proxy_setting['setting_name'] == 'settings_global_wpad_url') {
                    $_SESSION['wpad_url'] = $proxy_setting['setting_value'];
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

####### create directory for caching ########

            @mkdir($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id());
        }

        $dbHandle->exec("DETACH DATABASE userdatabase");
        $dbHandle = null;
    } else {

        die('Password typo.');
    }

    die('OK');
}

///////////////end register////////////////////////////////
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

    $username = $_POST['user'];
    $password = $_POST['pass'];

    database_connect($usersdatabase_path, 'users');

    $username_quoted = $dbHandle->quote($username);

    $dbHandle->exec("CREATE TABLE IF NOT EXISTS logins (
            id INTEGER PRIMARY KEY,
            userID INTEGER NOT NULL DEFAULT '',
            sessionID TEXT NOT NULL DEFAULT '',
            logintime TEXT NOT NULL DEFAULT ''
            )");

    if ($ini_array['autosign'] == 0) {

        // IMPOSE 2 SEC SIGN IN TIME OUT TO MITIGATE BRUTE FORCE ATTACKS

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

            $count = $dbHandle->query("SELECT count(*) FROM users WHERE username=" . $username_quoted);
            $rows = $count->fetchColumn();
            $count = null;

            // REGISTER LDAP USER INTO LOCAL DATABASE
            if ($rows == 0) {

                // FIRST REGISTERED USER MUST BE ADMIN
                $count = $dbHandle->query("SELECT count(*) FROM users");
                $totalusers = $count->fetchColumn();
                $count = null;

                if ($totalusers == 0)
                    $permissions = 'A';

                $dbHandle->exec("INSERT INTO users (username,password,permissions) VALUES ($username_quoted,'','$permissions')");

                $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM users");
                $id = $last_id->fetchColumn();
                $last_id = null;

                $dbHandle->exec("INSERT INTO projects (userID,project) VALUES ($id,$username_quoted || '''s project', '1')");
            }

            // DELETE USER'S PASSWORD FROM LOCAL STORAGE FOR INCREASED SECURITY
            if ($rows == 1) {
                $dbHandle->exec("UPDATE users SET password='' WHERE username=" . $username_quoted);
            }

            $result = $dbHandle->query("SELECT userID,permissions FROM users WHERE username=" . $username_quoted);
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $result = null;
            extract($row);

            $dbHandle->commit();

            $_SESSION['user_id'] = $userID;
            $_SESSION['user'] = $_POST['user'];
            $_SESSION['permissions'] = $permissions;
            $_SESSION['auth'] = true;
        }
    } else {

        /* IF LDAP NOT ENABLED, CHECK THE LOCAL DB */

        // CHECK FOR FORMER LDAP USER

        $result = $dbHandle->query("SELECT password FROM users WHERE username=" . $username_quoted);
        $user_password = $result->fetchColumn();
        $result = null;

        if ($user_password === '')
            die('Error. Your local password is not set.<br> Use <b>Create account</b> link to set new password.');

        if (!empty($user_password) && check_encrypted_password($dbHandle, $username, $password)) {

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
                $clean_files = array();
                $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . $oldsession['sessionID'] . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);
                if ($clean_files) {
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
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>I, Librarian <?php print $version ?></title>
        <link type="text/css" href="css/custom-theme/jquery-ui-custom.min.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/plugins.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="css/font-awesome.css?v=<?php print $version ?>" rel="stylesheet">
        <link type="text/css" href="style.php?v=<?php print $version ?>" rel="stylesheet">
        <script type="text/javascript" src="js/jquery.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/jquery-ui-custom.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/plugins.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/plupload/plupload.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/tinymce/tinymce.min.js?v=<?php print $version ?>"></script>
        <script type="text/javascript" src="js/javascript.js?v=<?php print $version ?>"></script>
    </head>
    <body style="margin:0;border:0;padding:0;width:100%;height:100%;overflow:hidden">
        <?php
        if (isset($_SESSION['auth'])) {
            if($hosted == true && $forced_ssl == false && stripos($url, 'https://') === 0) {
                die ('<script type="text/javascript"> top.location.assign("' . str_ireplace('https://', 'http://', $url) . '") </script></body></html>');
            }
            include 'keyboard.php';
            ?>
            <div style="height:35px;width:100%" id="top-panel"></div>
            <div style="height:100%;overflow:hidden" id="bottom-panel"></div>
            <div style="display:none;height:100%;overflow:hidden" id="items-container"></div>
            <div style="height:100%;overflow:hidden;display:none" id="addrecord-panel"></div>
            <div id="dialog-confirm" style="display:none"></div>
            <div id="advancedsearch" style="display:none"></div>
            <div id="expertsearch" style="display:none"></div>
            <div id="exportdialog" style="display:none"></div>
            <div id="omnitooldiv" style="display:none"></div>
            <div id="delete-file" title="Delete Record?" style="display:none"></div>
            <div id="dialog-error" style="display:none"></div>
            <div id="open-dirs"></div>
            <?php
        } else {
            if ($hosted == true && stripos($url, 'http://') === 0) {
                die ('<script type="text/javascript"> top.location.assign("' . str_ireplace('http://', 'https://', $url) . '") </script></body></html>');
            }
            $signin_mode = '';
            $disallow_signup = '';
            database_connect($usersdatabase_path, 'users');
            $all_users = $dbHandle->query("SELECT username FROM users ORDER BY username COLLATE NOCASE");
            $all_users_count = $dbHandle->query("SELECT count(*) FROM users");
            $setting1 = $dbHandle->query("SELECT setting_value FROM settings WHERE setting_name='settings_global_signin_mode'");
            $setting2 = $dbHandle->query("SELECT setting_value FROM settings WHERE setting_name='settings_global_disallow_signup'");
            $rows = $all_users_count->fetchColumn();
            $signin_mode = $setting1->fetchColumn();
            $disallow_signup = $setting2->fetchColumn();
            $dbHandle = null;
            ?>
            <div id="signin-background" style="height:100%;overflow:hidden;position:relative">
                <img src="img/bg.svg" style="position:fixed;top:0;left:0;width:100%">
                <div class="topindex" id="top-panel-form">
                    <?php echo htmlspecialchars($ini_array['greeting']); ?>
                </div>
                <div class="ui-corner-all item-sticker" style="position:absolute;top:0;left:0;width:26em" id="signin-container">
                    <div class="alternating_row ui-corner-all" style="padding:20px 26px;overflow:auto;height:12.5em;border:1px solid rgba(0,0,0,0.15)">
                        <form action="index2.php" method="POST" id="signinform">
                            <input type="hidden" name="form" value="signin">
                            <table style="width:100%;height:100%">
                                <tr>
                                    <td style="padding:6px;width:8em">
                                        <?php print ($ldap_active) ? 'LDAP ' : ''  ?>User:
                                    </td>
                                    <td style="padding:6px">
                                        <?php
                                        if ($signin_mode == 'textinput' || $ldap_active) {
                                            print '<input type="text" name="user" size="10" value="" style="width:95%">';
                                        } else {
                                            print '<select name="user" style="width:95%"><option></option>';
                                            while ($user = $all_users->fetch(PDO::FETCH_ASSOC)) {
                                                print '<option';
                                                if ($rows == 1)
                                                    print ' selected';
                                                print ' value="' . htmlspecialchars($user['username']) . '">' . htmlspecialchars($user['username']) . '</option>';
                                            }
                                            print '</select>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px">
                                        Password:
                                    </td>
                                    <td style="padding:6px">
                                        <input type="password" name="pass" size="10" value="" style="width:95%">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px">
                                        <button id="signinbutton"><i class="fa fa-sign-in"></i> Sign In</button>
                                    </td>
                                    <td style="padding:6px;vertical-align:middle">
                                        <?php
                                        if (!$ldap_active && $disallow_signup != '1')
                                            print '<span style="cursor:pointer" id="register">Create Account <i class="fa fa-caret-right"></i></span>';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 6px;text-align:right" colspan=2>
                                        <?php if ($hosted == true) {
                                            ?>
                                            <span style="position:absolute;left:15px;bottom:16px">
                                                <i class="fa fa-check"></i>
                                                I agree with <a href="#" id="tos-link">Terms of Service</a>
                                            </span>
                                            <div id="tos-container" style="display:none" title="Terms of Service"></div>
                                            <?php
                                        }
                                        ?>
                                        <input type="checkbox" id="sign-options" style="cursor:pointer">
                                        <label for="sign-options">
                                            <i class="fa fa-cog"></i>&nbsp;&nbsp;<i class="fa fa-caret-down"></i>
                                        </label>
                                        <div id="sign-options-list" class="ui-corner-all lib-shadow-bottom alternating_row" style="display:none;position:fixed;border:1px solid #c8c9cf;padding:10px">
                                            <table>
                                                <tr>
                                                    <td class="select_span" style="line-height:16px">
                                                        <input type="checkbox" name="keepsigned" value="1" style="display:none" checked>
                                                        <i class="fa fa-check-square"></i>
                                                        Keep me signed in&nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                            <br>
                                            <span style="cursor: pointer" id="openresetpassword"><i class="fa fa-question-circle"></i> Forgotten password</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </form>
                        <?php
                        if (!$ldap_active && $disallow_signup != '1') {
                            ?>
                            <form action="index2.php" method="POST" id="signupform" style="display: none">
                                <input type="hidden" name="form" value="signup">
                                <table style="width:100%;height:100%">
                                    <tr>
                                        <td style="padding:6px;width:8em">
                                            User:
                                        </td>
                                        <td style="padding:6px">
                                            <input type="text" name="user" size="10" value="" style="width:90%">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px">
                                            Password:
                                        </td>
                                        <td style="padding:6px">
                                            <input type="password" name="pass" size="10" value="" style="width:90%">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px">
                                            Retype:
                                        </td>
                                        <td style="padding:6px">
                                            <input type="password" name="pass2" size="10" value="" style="width:90%">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px">
                                            <button id="signupbutton"><i class="fa fa-pencil"></i> Sign Up</button>
                                        </td>
                                        <td style="padding:6px;vertical-align:middle">
                                            <span style="cursor:pointer" id="login"><i class="fa fa-caret-left"></i> Sign In</span>
                                        </td>
                                    </tr>
                                </table>
                            </form>
                            <?php
                        }
                        ?>
                        <div id="resetpassword-container" style="display:none"></div>
                    </div>
                </div>
                <?php
                if ($hosted == false) {
                    ?>
                    <div id="credits" style="position: absolute;right:10px;bottom:10px;cursor:pointer">
                        I, Librarian <?php print $version ?> &copy; 2013 Martin Kucej &middot; GPLv3
                    </div>
                    <?php
                }
                ?>
                <div style="position: absolute;left:10px;bottom:10px;cursor:pointer">
                    <a href="m/index.html" target="_blank">I, Librarian Mobile</a>
                </div>
            </div>
            <script type="text/javascript">
    <?php
    if ($rows == 0) {
        ?>
                    $.jGrowl("<b>Welcome to I, Librarian!</b><br>Create an account first.", {sticky: true});
                    $.jGrowl("<b>After you sign up</b>, go to Tools->Installation details to see if everything works.", {sticky: true});
                    $.jGrowl("<b>After you sign up</b>, go to Tools->Settings to adjust I, Librarian to your liking.", {sticky: true});
        <?php
    }
    ?>
                index2.init();
            </script>
            <?php
        }
        ?>
    </body>
</html>
