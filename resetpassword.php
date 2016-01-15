<?php
if (file_exists('ilibrarian.ini')) {
    $ini_array = parse_ini_file("ilibrarian.ini");
} else {
    $ini_array = parse_ini_file("ilibrarian-default.ini");
}

if ($ini_array['reset_password'] == 0) die('<p style="padding:0 4px">Contact the database administrator. If none is available, edit ilibrarian.ini to enable the password reset.
    <br><br><span style="cursor:pointer" id="backtologin">Back</span><br><br><br></p>');

if ($_SERVER['REMOTE_ADDR'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {

    include_once 'data.php';
    include_once 'functions.php';

    if (!empty($_GET['username']) && !empty($_GET['new_password1']) && !empty($_GET['new_password2'])
            && $_GET['new_password1'] == $_GET['new_password2']) {

        database_connect(IL_USER_DATABASE_PATH, 'users');
        $new_password_query = $dbHandle->quote(generate_encrypted_password($_GET['new_password1']));
        $user_query = $dbHandle->quote($_GET['username']);
        $password_changed = $dbHandle->exec("UPDATE users SET password=$new_password_query WHERE username=$user_query");
        $dbHandle = null;
        if (isset($password_changed) && $password_changed == 1) {
            die('Password was reset.');
        } else {
            die('Password reset failed!');
        }
    }
    ?>
    <form action="resetpassword.php" method="GET" id="resetpasswordform">
        <table cellspacing="0" style="width:100%">
            <tr>
                <td style="padding:6px;width:90px">
                    Username:
                </td>
                <td style="padding:6px">
                    <input type="text" size="10" name="username" style="width:90%">
                </td>
            </tr>
            <tr>
                <td style="padding:6px">
                    New password:
                </td>
                <td style="padding:6px">
                    <input type="password" size="10" name="new_password1" style="width:90%">
                </td>
            </tr>
            <tr>
                <td style="padding:6px">
                    Re-type:
                </td>
                <td style="padding:6px">
                    <input type="password" size="10" name="new_password2" style="width:90%">
                </td>
            </tr>
            <tr>
                <td style="padding:6px">
                    <button id="resetpasswordbutton"><i class="fa fa-exclamation-triangle ui-state-error-text"></i> Reset</button>
                </td>
                <td style="padding:6px;vertical-align:middle">
                    <span style="cursor:pointer" id="backtologin">Sign In</span>
                </td>
            </tr>
        </table>
    </form>
    <?php
} else {
    print 'Accessible only from localhost.<br><br><span style="cursor:pointer" id="backtologin">Back</span>';
}
?>
