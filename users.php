<?php
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

    // CHANGE USER'S PASSWORD
    if (!empty($_GET['change_password']) && !empty($_GET['old_password'])) {

        if (empty($_GET['new_password1']) || empty($_GET['new_password2']))
            die('Error! Password was not changed. New password required.');
        if ($_GET['new_password1'] !== $_GET['new_password2'])
            die('Error! Password was not changed. New password typo.');

        $password_changed = NULL;

        database_connect(IL_USER_DATABASE_PATH, 'users');

        if (check_encrypted_password($dbHandle, $_SESSION['user'], $_GET['old_password'])) {

            $user_query = $dbHandle->quote($_SESSION['user_id']);
            $new_password_query = $dbHandle->quote(generate_encrypted_password($_GET['new_password1']));
            $password_changed = $dbHandle->exec("UPDATE users SET password=" . $new_password_query . " WHERE userID=" . $user_query);
            $error = $dbHandle->errorInfo();
        } else {
            die('Error! Password was not changed. Existing password is incorrect.');
        }

        $dbHandle = null;
        if ($password_changed !== 1)
            die('Error! Password was not changed. Database error: ' . $error[2]);
    }

    // DELETE A USER
    if (!empty($_GET['delete']) && !empty($_GET['id'])) {

        database_connect(IL_DATABASE_PATH, 'library');
        $dbHandle->exec("ATTACH DATABASE '" . IL_USER_DATABASE_PATH . DIRECTORY_SEPARATOR . "users.sq3' AS userdatabase");

        $id_query = $dbHandle->quote($_GET['id']);

        $result = $dbHandle->query("SELECT projectID FROM projects WHERE userID=$id_query");

        while ($project = $result->fetch(PDO::FETCH_ASSOC)) {
            if (is_writable(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'project' . $project['projectID'] . '.sq3'))
                unlink(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'project' . $project['projectID'] . '.sq3');
        }

        $result = null;

        $dbHandle->beginTransaction();

        $result = $dbHandle->query("SELECT MIN(userID) FROM userdatabase.users");
        $minID = $result->fetchColumn();
        $result = null;

        $dbHandle->exec("DELETE FROM userdatabase.users WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM userdatabase.settings WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM projectsusers WHERE projectID IN (SELECT projectID FROM projects WHERE userID=$id_query)");
        $dbHandle->exec("DELETE FROM projectsfiles WHERE projectID IN (SELECT projectID FROM projects WHERE userID=$id_query)");
        $dbHandle->exec("DELETE FROM projects WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM projectsusers WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM notes WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM searches WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM shelves WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM yellowmarkers WHERE userID=$id_query");
        $dbHandle->exec("DELETE FROM annotations WHERE userID=$id_query");
        $dbHandle->exec("UPDATE library SET added_by=$minID WHERE added_by=$id_query");
        $dbHandle->exec("UPDATE library SET modified_by=$minID WHERE modified_by=$id_query");

        $dbHandle->commit();
        $dbHandle->exec("DETACH DATABASE userdatabase");
        $error = $dbHandle->errorInfo();
        $dbHandle = null;
    }

    // CREATE NEW USER
    if (!empty($_GET['create_user']) && !empty($_GET['username']) && !empty($_GET['permissions'])) {

        if (empty($_GET['password']))
            die('Error! User was not created. Password required.');
        
        $slashes = array("/","\\");
        $_GET['username'] = str_replace($slashes, "", $_GET['username']);

        $create = NULL;

        database_connect(IL_USER_DATABASE_PATH, 'users');
        $username_query = $dbHandle->quote($_GET['username']);
        $password_query = $dbHandle->quote(generate_encrypted_password($_GET['password']));
        $permissions_query = $dbHandle->quote($_GET['permissions']);
        $create = $dbHandle->exec("INSERT INTO users (username,password,permissions) VALUES($username_query,$password_query,$permissions_query)");
        $error = $dbHandle->errorInfo();
        $dbHandle = null;

        if ($create !== 1)
            die('Error! User was not created. Database error: ' . $error[2]);
    }

    // CHANGE USER'S PERMISSIONS
    if (!empty($_GET['change_permissions']) && !empty($_GET['id'])) {

        if ($_GET['new_permissions'] == 'A') {
            $new_permissions = 'A';
        } elseif ($_GET['new_permissions'] == 'U') {
            $new_permissions = 'U';
        } elseif ($_GET['new_permissions'] == 'G') {
            $new_permissions = 'G';
        }

        database_connect(IL_USER_DATABASE_PATH, 'users');
        $permissions_query = $dbHandle->quote($new_permissions);
        $id_query = $dbHandle->quote($_GET['id']);
        $dbHandle->exec("UPDATE users SET permissions=$permissions_query WHERE userID=$id_query");
        $error = $dbHandle->errorInfo();
        $dbHandle = null;
    }

    // RENAME USER
    if (!empty($_GET['rename']) && !empty($_GET['id']) && !empty($_GET['username'])) {
        
        $slashes = array("/","\\");
        $_GET['username'] = str_replace($slashes, "", $_GET['username']);

        database_connect(IL_USER_DATABASE_PATH, 'users');
        $username_query = $dbHandle->quote($_GET['username']);
        $id_query = $dbHandle->quote($_GET['id']);
        $rename = $dbHandle->exec("UPDATE users SET username=$username_query WHERE userID=$id_query");
        $error = $dbHandle->errorInfo();
        $dbHandle = null;

        if ($rename !== 1)
            die('Error! User was not renamed correctly. Database error: ' . $error[2]);
    }

    // FORCE NEW PASSWORD FOR EXISTING USER
    if (!empty($_GET['force_password']) && !empty($_GET['id']) && !empty($_GET['new_password'])) {

        database_connect(IL_USER_DATABASE_PATH, 'users');
        $id_query = $dbHandle->quote($_GET['id']);
        $password_query = $dbHandle->quote(generate_encrypted_password($_GET['new_password']));
        $update = $dbHandle->exec("UPDATE users SET password=$password_query WHERE userID=$id_query");
        $error = $dbHandle->errorInfo();
        $dbHandle = null;

        if ($update !== 1)
            die('Error! Password was not saved correctly. Database error: ' . $error[2]);
    }

    print '<form action="users.php" method="GET">';

    print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

    print "<tr><td class=\"details alternating_row\"><b>Change password for user " . htmlspecialchars($_SESSION['user']) . "</b></td></tr>";

    print "<tr><td class=\"details\">";

    print "Old Password: <input type=\"password\" size=\"10\" name=\"old_password\">
    New Password: <input type=\"password\" size=\"10\" name=\"new_password1\">
    Re-type New Password: <input type=\"password\" size=\"10\" name=\"new_password2\"><br>";

    print "</td></tr>";

    print "<tr><td class=\"details\">";

    print "<input type=\"submit\" name=\"change_password\" value=\"Change\">";

    print "</td></tr></table>";

    print '</form><br>';

    if ($_SESSION['permissions'] == 'A') {

        $number1 = rand(2, 9);
        $number2 = rand(2, 9);
        $upper1 = rand(65, 90);
        if ($upper1 == '79')
            $upper1 = '80';
        if ($upper1 == '73')
            $upper1 = '74';
        $upper2 = rand(65, 90);
        if ($upper2 == '79')
            $upper2 = '80';
        if ($upper2 == '73')
            $upper2 = '74';
        $upper3 = rand(65, 90);
        if ($upper3 == '79')
            $upper3 = '80';
        if ($upper3 == '73')
            $upper3 = '74';
        $lower1 = rand(97, 122);
        if ($lower1 == '108')
            $lower1 = '109';
        $lower2 = rand(97, 122);
        if ($lower2 == '108')
            $lower2 = '109';
        $lower3 = rand(97, 122);
        if ($lower3 == '108')
            $lower3 = '109';
        $random_password = str_shuffle(chr($upper1) . chr($lower1) . $number1 . $number2 . chr($lower2) . chr($upper2) . chr($upper3) . chr($lower3));

        print '<form action="users.php" method="GET" id="users-create">';

        print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

        print "<tr><td class=\"details alternating_row\"><b>Create new user</b></td></tr>";

        print "<tr><td class=\"details\">";

        print "Username: <input type=\"text\" size=\"10\" name=\"username\">
        Password: <input type=\"text\" size=\"8\" name=\"password\" value=\"$random_password\">
        Permissions:<input type=\"radio\" name=\"permissions\" value=\"A\">Super User
        <input type=\"radio\" name=\"permissions\" value=\"U\" checked>User
        <input type=\"radio\" name=\"permissions\" value=\"G\">Guest<br>";

        print "</td></tr>";

        print "<tr><td class=\"details\">";

        print "<input type=\"submit\" name=\"create_user\" value=\"Create\">";

        print "</td></tr></table>";

        print '</form>';

        database_connect(IL_USER_DATABASE_PATH, 'users');

        $users = $dbHandle->query("SELECT userID,username,permissions FROM users");

        $dbHandle = null;

        print '<br>Be careful. Some of these changes cannot be undone&#172;';

        print '<br><table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

        print "<tr><td class=\"details alternating_row\" ><b>User administration:</b></td></tr>";

        print '</table>';

        while ($username = $users->fetch(PDO::FETCH_ASSOC)) {

            $number1 = rand(2, 9);
            $number2 = rand(2, 9);
            $upper1 = rand(65, 90);
            if ($upper1 == '79')
                $upper1 = '80';
            if ($upper1 == '73')
                $upper1 = '74';
            $upper2 = rand(65, 90);
            if ($upper2 == '79')
                $upper2 = '80';
            if ($upper2 == '73')
                $upper2 = '74';
            $upper3 = rand(65, 90);
            if ($upper3 == '79')
                $upper3 = '80';
            if ($upper3 == '73')
                $upper3 = '74';
            $lower1 = rand(97, 122);
            if ($lower1 == '108')
                $lower1 = '109';
            $lower2 = rand(97, 122);
            if ($lower2 == '108')
                $lower2 = '109';
            $lower3 = rand(97, 122);
            if ($lower3 == '108')
                $lower3 = '109';
            $random_password = str_shuffle(chr($upper1) . chr($lower1) . $number1 . $number2 . chr($lower2) . chr($upper2) . chr($upper3) . chr($lower3));

            if ($username['permissions'] == 'A') {
                $user_string = '<input type="radio" name="new_permissions" value="A" checked>Super User<input type="radio" name="new_permissions" value="U">User<input type="radio" name="new_permissions" value="G">Guest';
            } elseif ($username['permissions'] == 'U') {
                $user_string = '<input type="radio" name="new_permissions" value="A">Super User<input type="radio" name="new_permissions" value="U" checked>User<input type="radio" name="new_permissions" value="G">Guest';
            } elseif ($username['permissions'] == 'G') {
                $user_string = '<input type="radio" name="new_permissions" value="A">Super User<input type="radio" name="new_permissions" value="U">User<input type="radio" name="new_permissions" value="G" checked>Guest';
            }
            ?>
            <table border="0" cellpadding="0" cellspacing="0" style="width: 100%"><tr>
                    <td class="details" style="white-space: nowrap">
                        <form action="users.php" method="GET" id="users-delete">
                            <input type="hidden" name="id" value="<?php print $username['userID'] ?>">
                            <input type="hidden" name="delete" value="1">
                            <input type="submit" value="Delete" class="deletebutton" <?php if ($username['userID'] == 1) print 'disabled'; ?>> ID <?php print $username['userID'] ?>
                        </form>
                    </td>
                    <td class="details" style="white-space: nowrap">
                        <form action="users.php" method="GET" id="users-perm">
                            <input type="hidden" name="id" value="<?php print $username['userID'] ?>">
                            <input type="submit" name="change_permissions" value="Change" <?php if ($username['userID'] == 1) print 'disabled'; ?>> <?php print $user_string ?>
                        </form>
                    </td>
                    <td class="details">
                        <form action="users.php" method="GET" id="users-rename">
                            <input type="hidden" name="id" value="<?php print $username['userID'] ?>">
                            <input type="submit" name="rename" value="Rename"><input type="text" size="10" name="username" value="<?php print htmlspecialchars($username['username']) ?>">
                        </form>
                    </td>
                    <td class="details">
                        <form action="users.php" method="GET" id="users-force">
                            <input type="hidden" name="id" value="<?php print $username['userID'] ?>">
                            <input type="submit" name="force_password" value="Force Password"><input type="text" size="8" name="new_password" value="<?php print $random_password ?>">
                        </form>
                    </td>
                </tr></table>
            <?php
        }
    }
    ?>
    <div id="delete-confirm" title="Delete User?"></div>
    <?php
}
?>