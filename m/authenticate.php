<?php

include_once '../data.php';
include_once '../functions.php';

/**
 * Parse ilibrarian.ini.
 */
if (file_exists('../ilibrarian.ini')) {
    $ini_array = parse_ini_file("../ilibrarian.ini");
} else {
    $ini_array = parse_ini_file("../ilibrarian-default.ini");
}

/**
 * LDAP settings from ilibrarian.ini.
 */
$ldap_active = $ini_array['ldap_active'];
$ldap_version = $ini_array['ldap_version'];
$ldap_server = $ini_array['ldap_server'];
$ldap_port = $ini_array['ldap_port'];
$ldap_basedn = $ini_array['ldap_basedn'];
$ldap_binduser_rdn = $ini_array['ldap_binduser_rdn'];
$ldap_binduser_pw = $ini_array['ldap_binduser_pw'];
$ldap_username_attr = $ini_array['ldap_username_attr'];
$ldap_user_rdn = $ini_array['ldap_user_rdn'];
$ldap_group_rdn = $ini_array['ldap_group_rdn'];
$ldap_usergroup_cn = $ini_array['ldap_usergroup_cn'];
$ldap_admingroup_cn = $ini_array['ldap_admingroup_cn'];
$ldap_filter = $ini_array['ldap_filter'];
if (!extension_loaded('ldap'))
    $ldap_active = false;

/**
 * Sign out.
 */
if (isset($_GET['action']) && $_GET['action'] == 'signout') {
    // DELETE USER'S FILE CACHE
    $clean_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);
    if (is_array($clean_files)) {
        foreach ($clean_files as $clean_file) {
            if (is_file($clean_file) && is_writable($clean_file))
                @unlink($clean_file);
        }
    }
    $_SESSION = array();
    session_destroy();
    if (!isset($ini_array['autosign']) || $ini_array['autosign'] != 1) {
        die('OK');
    }
}

/**
 * New user registration.
 */
if (isset($_POST['form']) && $_POST['form'] == 'signup' && !empty($_POST['user']) && !empty($_POST['pass']) && !empty($_POST['pass2'])) {

    // If registration not allowed, exit with error.
    $dbHandle = database_connect(IL_USER_DATABASE_PATH, 'users');
    
    $result = $dbHandle->query("SELECT setting_value as v FROM settings"
            . " WHERE setting_name = 'disallow_signup'");
    
    $disallow_signup = $result->fetchColumn();

    $result = null;
    $dbHandle = null;
    
    if ($disallow_signup) {
        sendError('Registration is not allowed.');
    }
    
    // If LDAP is on, registration is not allowed.
    if ($ldap_active) {
        sendError('Only LDAP registered users can access this library.');
    }

    // Password checks.    
    if ($_POST['pass'] !== $_POST['pass2']) {
        sendError('Password typo.');
    }

    if (strlen($_POST['pass']) < 8) {
        sendError('Password must be at least 8 characters long.');
    }

    database_connect(IL_DATABASE_PATH, 'library');

    $quoted_path = $dbHandle->quote(IL_USER_DATABASE_PATH . DIRECTORY_SEPARATOR . 'users.sq3');

    $dbHandle->exec("ATTACH DATABASE $quoted_path AS userdatabase");

    $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

    // How many users are there?
    $result = $dbHandle->query("SELECT count(*) FROM userdatabase.users");
    $users = $result->fetchColumn();
    $result = null;

    // Read default user permissions.
    $result = $dbHandle->query("SELECT setting_value FROM userdatabase.settings WHERE setting_name='default_permissions'");
    $default_permissions = $result->fetchColumn();
    $result = null;

    // First user must be admin. The others will have default permissions.
    if ($users == 0) {
        $permissions = 'A';
    } else {
        !empty($default_permissions) ? $permissions = $default_permissions : $permissions = 'U';
    }

    $quoted_user = $dbHandle->quote($_POST['user']);

    // Check if this username is unique.
    $rows = 0;
    if ($users > 0) {
        $result = $dbHandle->query("SELECT count(*) FROM userdatabase.users WHERE username=$quoted_user");
        $rows = $result->fetchColumn();
        $result = null;
    }

    if ($rows > 0) {
        // CHECK IF PASSWORD IS EMPTY (FORMER LDAP USERS)
        $result = $dbHandle->query("SELECT userID, password FROM userdatabase.users WHERE username=" . $quoted_user);
        $existing_user = $result->fetch(PDO::FETCH_ASSOC);
        $result = null;
        extract($existing_user);

        if (empty($password)) {
            // Former LDAP users are allowed to enter new password here.
            // Encrypt the password.
            $quoted_password = $dbHandle->quote(generate_encrypted_password($_POST['pass']));
            // Update the database.
            $dbHandle->exec("UPDATE userdatabase.users SET password=" . $quoted_password . " WHERE username=" . $quoted_user);
            // Write session vars.
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userID;
            $_SESSION['user'] = $_POST['user'];
            $_SESSION['permissions'] = $permissions;
            $_SESSION['auth'] = true;
        } else {
            $dbHandle->exec("ROLLBACK");
            sendError('Username already exists.');
        }
    } else {
        // Encrypt the password.
        $quoted_password = $dbHandle->quote(generate_encrypted_password($_POST['pass']));
        // Save the user to database.
        $dbHandle->exec("INSERT INTO userdatabase.users (username,password,permissions) VALUES (" . $quoted_user . "," . $quoted_password . ",'" . $permissions . "')");
        // Get user ID.
        $id = $dbHandle->lastInsertId();
        // Write session vars.
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['user'] = $_POST['user'];
        $_SESSION['permissions'] = $permissions;
        $_SESSION['auth'] = true;
    }

    $dbHandle->exec("COMMIT TRANSACTION");

    $dbHandle->exec("DETACH DATABASE userdatabase");
    $dbHandle = null;

    die('OK');
}

/**
 * User authentication.
 */
if (isset($_POST['form']) && $_POST['form'] == 'signin' && !empty($_POST['user']) && !empty($_POST['pass']) && !isset($_SESSION['auth'])) {

    $username = $_POST['user'];
    $password = $_POST['pass'];

    database_connect(IL_USER_DATABASE_PATH, 'users');

    $username_quoted = $dbHandle->quote($username);

    // LDAP authentication.
    if ($ldap_active) {

        // Connect.
        if (!$ldap_connect = ldap_connect($ldap_server, $ldap_port)) {
            sendError("Could not connect to LDAP server");
        }

        if (!ldap_set_option($ldap_connect, LDAP_OPT_PROTOCOL_VERSION, $ldap_version)) {
            sendError("Failed to set version to protocol $ldap_version");
        }

        // Bind.
        if (!empty($ldap_binduser_rdn)) {
            $ldap_binduser_dn = $ldap_binduser_rdn . ',' . $ldap_basedn;

            if (!$ldap_bind = @ldap_bind($ldap_connect, $ldap_binduser_dn, $ldap_binduser_pw)) {
                sendError("Failed to bind as proxy user.");
            }

            /**
             * Lookup.
             * Users matching the following criteria are eligible:
             * - must be a person object of class user or iNetOrgPerson
             * - username must match the CN attribute specified in INI file
             * - must be situated below the base search DN
             */
            $ldap_filter_string = '(&(|(objectClass=user)(objectClass=iNetOrgPerson))' .
                    '(' . $ldap_username_attr . '=' . $username . '))';

            if (!$ldap_sr = @ldap_search($ldap_connect, $ldap_user_rdn . ',' . $ldap_basedn, $ldap_filter_string, array($ldap_username_attr))) {
                sendError("Bad username or password.");
            }

            $ldap_num_entries = ldap_count_entries($ldap_connect, $ldap_sr);

            if ($ldap_num_entries != 1) {
                sendError("Bad username or password.");
            }

            $ldap_user_sr = ldap_first_entry($ldap_connect, $ldap_sr);
            $ldap_user_dn = ldap_get_dn($ldap_connect, $ldap_user_sr);
        } else {

            $bind_rdn = '';
            if (!empty($ldap_username_attr)) {
                $bind_rdn .= $ldap_username_attr . '=' . $username . ',';
            }
            if (!empty($ldap_user_rdn)) {
                $bind_rdn .= $ldap_user_rdn . ',';
            }
            // Authenticate.
            if (!$ldap_bind = ldap_bind($ldap_connect, $bind_rdn . $ldap_basedn, $password)) {
                sendError("Failed to authenticate.");
            }
        }

        // Authorize: Check if user is in admin group.
        $ldap_admin_group_dn = $ldap_admingroup_cn . ',' . $ldap_group_rdn . ',' . $ldap_basedn;
        $ldap_sr = @ldap_read($ldap_connect, $ldap_admin_group_dn, '(' . $ldap_filter . '=' . $ldap_user_dn . ')', array('member'));
        $ldap_info_group = @ldap_get_entries($ldap_connect, $ldap_sr);

        if ($ldap_info_group['count'] > 0) {
            $permissions = 'A';
        } else {
            /**
             * If we don't have a ldap_usergroup_cn setting, assume all
             * users under the search base are eligible
             */
            if (empty($ldap_usergroup_cn)) {
                $permissions = 'U';
            } else {
                $ldap_user_group_dn = $ldap_usergroup_cn . ',' . $ldap_group_rdn . ',' . $ldap_basedn;
                $ldap_sr = @ldap_read($ldap_connect, $ldap_user_group_dn, '(' . $ldap_filter . '=' . $user_dn . ')', array('member'));
                $ldap_info_group = @ldap_get_entries($ldap_connect, $ldap_sr);
                if ($ldap_info_group['count'] > 0) {
                    $permissions = 'U';
                } else {
                    sendError("Bad username or password.");
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

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userID;
        $_SESSION['user'] = $_POST['user'];
        $_SESSION['permissions'] = $permissions;
        $_SESSION['auth'] = true;
    } else {

        /* IF LDAP NOT ENABLED, CHECK THE LOCAL DB */
        // CHECK FOR FORMER LDAP USER
        $result = $dbHandle->query("SELECT password FROM users WHERE username=" . $username_quoted);
        $user_password = $result->fetchColumn();
        $result = null;

        if ($user_password === '') {
            sendError('Your local password is not set. Use Create Account to set a new password.');
        }

        // Verify password.
        if (!empty($user_password) && check_encrypted_password($dbHandle, $username, $password)) {

            $result = $dbHandle->query("SELECT userID,permissions FROM users WHERE username=" . $username_quoted);
            $user = $result->fetch(PDO::FETCH_ASSOC);
            $result = null;

            if (!empty($user['userID'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['userID'];
                $_SESSION['user'] = $_POST['user'];
                $_SESSION['permissions'] = $user['permissions'];
                $_SESSION['auth'] = true;
            }
        } else {
            sendError('Bad username or password.');
        }
    }

    $dbHandle = null;
}

/**
 * If user is authorized, read settings and create user-specific temp dirs.
 */
if (isset($_SESSION['auth']) && isset($_POST['form'])) {

    database_connect(IL_USER_DATABASE_PATH, 'users');

    $user_id_q = $dbHandle->quote($_SESSION['user_id']);

    // Session management.
    if ($ini_array['autosign'] == 0) {

        $session_id_q = $dbHandle->quote(session_id());

        // Save this signin in db.
        $dbHandle->exec("DELETE FROM logins"
                . " WHERE sessionID=$session_id_q AND userID=$user_id_q");
        $dbHandle->exec("INSERT INTO logins (userID, sessionID, logintime)"
                . " VALUES ($user_id_q, $session_id_q,'" . time() . "')");

        // Delete all other signed in devices for this user.
        $result = $dbHandle->query("SELECT sessionID FROM logins"
                . " WHERE sessionID!=$session_id_q AND userID=$user_id_q");

        while ($oldsession = $result->fetch(PDO::FETCH_ASSOC)) {
            // Delete session file.
            @unlink(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'I,_Librarian_sessions' . DIRECTORY_SEPARATOR . 'sess_' . $oldsession['sessionID']);
            // Clen session temp dir.
            $clean_files = array();
            $clean_files = glob(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . $oldsession['sessionID'] . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT);
            if ($clean_files) {
                foreach ($clean_files as $clean_file) {
                    if (is_file($clean_file) && is_writable($clean_file))
                        @unlink($clean_file);
                }
            }
            if (is_dir(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . $oldsession['sessionID'])) {
                rmdir(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . $oldsession['sessionID']);
            }
        }

        $result = null;
        $dbHandle->query("DELETE FROM logins WHERE"
                . " sessionID!=$session_id_q AND userID=$user_id_q");
    }

    // Cookie time out.
    if (isset($_POST['keepsigned']) && $_POST['keepsigned'] == 1) {
        $keepsigned = 1;
        save_setting($dbHandle, 'keepsigned', '1');
        setcookie(session_name(), session_id(), time() + 604800);
    } else {
        save_setting($dbHandle, 'keepsigned', '');
        setcookie(session_name(), session_id(), 0);
    }

    $result = $dbHandle->query("SELECT setting_name as n, setting_value as v FROM settings"
            . " WHERE userID= '' OR userID=$user_id_q");

    while ($setting = $result->fetch(PDO::FETCH_ASSOC)) {

        $_SESSION[$setting['n']] = htmlspecialchars($setting['v']);
    }

    $result = null;

    // Create user specific temp directory.
    @mkdir(IL_TEMP_PATH . DIRECTORY_SEPARATOR . 'lib_' . session_id());

    if (!isset($ini_array['autosign']) || $ini_array['autosign'] != 1) {
        die('OK');
    }
}