<?php
include_once 'data.php';
include_once 'functions.php';

// Check for an obsolete INI.
if (isset($_SESSION['permissions']) && $_SESSION['permissions'] === 'A') {

    if (file_exists('ilibrarian.ini')) {
        $ini_array = parse_ini_file("ilibrarian.ini");
    } else {
        $ini_array = parse_ini_file("ilibrarian-default.ini");
    }
    if (!isset($ini_array['library_path'])) {
        echo '<div class="ui-state-error" style="position:fixed;margin:1em;padding:1em;z-index:10000">'
            . 'This installation has an obsolete INI file. Please delete the file <b>'
            . __DIR__ . DIRECTORY_SEPARATOR . 'ilibrarian.ini</b>. Read <b>'
            . __DIR__ . DIRECTORY_SEPARATOR . 'ilibrarian-default.ini</b> for instructions'
            . ' on how to have custom INI settings.</div>';
    }
}

// Install or upgrade.
if (!is_file(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'library.sq3')) {

    include 'install.php';
} else {

    $isupgraded = false;
    $is_2_11 = false;
    database_connect(IL_DATABASE_PATH, 'library');
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
    $result = $dbHandle->query("PRAGMA user_version");
    $db_version = $result->fetchColumn();
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
    // Upgrade to 3.6.
    if (empty($db_version) || $db_version < '36')
        include_once 'migrate5.php';
    // Upgrade to 4.1.
    if (empty($db_version) || $db_version < '41')
        include_once 'migrate6.php';
    // Upgrade to 4.4.
    if (empty($db_version) || $db_version < '44')
        include_once 'migrate7.php';
}

/**
 * Garbage collection.
 */
if (mt_rand(1, 20) == 10) {
    // REMOVE EMPTY USER CACHE DIRS
    $clean_dirs = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_*', GLOB_NOSORT);
    if (is_array($clean_dirs)) {
        foreach ($clean_dirs as $clean_dir) {
            if (is_dir($clean_dir) && is_writable($clean_dir))
                @rmdir($clean_dir);
        }
    }
    // CLEAN PNG CACHE IF OVER 10,000 FILES
    $pngs = glob(IL_IMAGE_PATH . DIRECTORY_SEPARATOR . "*.jpg", GLOB_NOSORT);
    if (is_array($pngs)) {
        if (count($pngs) > 10000) {
            foreach ($pngs as $item) {
                $arr[$item] = filemtime($item);
            }
            asort($arr);
            reset($arr);
            for ($i = 0; $i <= 1000; $i++) {
                @unlink(key($arr));
                next($arr);
            }
        }
    }
    unset($pngs);
    unset($arr);
    // Clean PDF cache.
    $pdfs = glob(IL_PDF_CACHE_PATH . DIRECTORY_SEPARATOR . "*.sq3", GLOB_NOSORT);
    if (is_array($pdfs)) {
        if (count($pdfs) > 10000) {
            foreach ($pdfs as $item) {
                $arr[$item] = filemtime($item);
            }
            asort($arr);
            reset($arr);
            for ($i = 0; $i <= 1000; $i++) {
                @unlink(key($arr));
                next($arr);
            }
        }
    }
    unset($pdfs);
    unset($arr);
}
if (mt_rand(1, 100) == 50) {
    // CLEAN GLOBAL TEMP CACHE
    $clean_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);
    if (is_array($clean_files)) {
        foreach ($clean_files as $clean_file) {
            if (is_file($clean_file) && is_writable($clean_file)) {
                unlink($clean_file);
            }
        }
    }
}
/**
 * Parse ilibrarian.ini.
 */
if (file_exists('ilibrarian.ini')) {
    $ini_array = parse_ini_file("ilibrarian.ini");
} else {
    $ini_array = parse_ini_file("ilibrarian-default.ini");
}

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
    $_POST['form'] = 'autosign';

    include 'authenticate.php';
}
?>
<!DOCTYPE html>
<html style="width:100%;height:100%">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
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

            include 'keyboard.php';

            ?>
            <div style="height:40px;width:100%" id="top-panel"></div>
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
            <div id="floating-notes" class="ui-widget-content alternating_row"
                 style="display:none;position:fixed;bottom:0;right:0;width:600px;height:400px;z-index:50;box-shadow: 0 0 8px rgba(0,0,0,0.33);overflow: hidden;opacity:1">
                <div class="ui-widget-header" style="padding:0.4em 1em;cursor: move">
                    <i class="fa fa-times-circle" style="padding:0.25em;float:right;cursor: pointer"></i>
                    <i class="fa fa-minus-circle" style="padding:0.25em;float:right;margin-right: 0.25em;cursor: pointer"></i>
                    <i class="fa fa-plus-circle" style="padding:0.25em;float:right;margin-right: 0.25em;cursor: pointer"></i>
                    <div style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;margin-right: 6em"></div>
                </div>
                <div></div>
                <div id="iframe-fix" style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;background-color: rgba(55,55,55,0.1)"></div>
            </div>
            <?php
        } else {
            $signin_mode = '';
            $disallow_signup = '';
            database_connect(IL_USER_DATABASE_PATH, 'users');
            $all_users = $dbHandle->query("SELECT username FROM users ORDER BY username COLLATE NOCASE");
            $all_users_count = $dbHandle->query("SELECT count(*) FROM users");
            $setting1 = $dbHandle->query("SELECT setting_value FROM settings WHERE setting_name='signin_mode'");
            $setting2 = $dbHandle->query("SELECT setting_value FROM settings WHERE setting_name='disallow_signup'");
            $rows = $all_users_count->fetchColumn();
            $signin_mode = $setting1->fetchColumn();
            $disallow_signup = $setting2->fetchColumn();
            $dbHandle = null;

            ?>
            <div id="signin-background" style="height:100%;overflow:hidden;position:relative">
                <img src="img/bg.svg" style="position:fixed;top:0;left:0;width:100%">
                <div class="topindex" id="top-panel-form">
    <?php echo htmlspecialchars($ini_array['greeting'], ENT_COMPAT, 'UTF-8', FALSE); ?>
                </div>
                <div class="ui-corner-all item-sticker" style="position:absolute;top:0;left:0;width:26em" id="signin-container">
                    <div class="alternating_row ui-corner-all" style="padding:20px 26px;overflow:auto;height:12.5em;border:1px solid rgba(0,0,0,0.15)">
                        <form action="authenticate.php" method="POST" id="signinform">
                            <input type="hidden" name="form" value="signin">
                            <table style="width:100%;height:100%">
                                <tr>
                                    <td style="padding:6px;width:8em">
    <?php print ($ini_array['ldap_active']) ? 'LDAP ' : ''  ?>User:
                                    </td>
                                    <td style="padding:6px">
                                        <?php
                                        if ($signin_mode == 'textinput' || $ini_array['ldap_active']) {
                                            print '<input type="text" name="user" size="10" value="" style="width:95%" autocomplete="off">';
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
                                        if (!$ini_array['ldap_active'] && $disallow_signup != '1')
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
                        if (!$ini_array['ldap_active'] && $disallow_signup != '1') {

                            ?>
                            <form action="authenticate.php" method="POST" id="signupform" style="display: none">
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
                        I, Librarian <?php print $version ?> &copy; Scilico, LLC &middot; GPLv3
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
