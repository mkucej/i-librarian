<?php
include_once 'data.php';
include_once 'functions.php';
set_time_limit(0);

if($hosted == true) die();

if (isset($_SESSION['auth']) && $_SESSION['permissions'] == 'A') {

    $directory = '';
    if (isset($_GET['directory']))
        $directory = $_GET['directory'];
    if (substr($directory, -1) == DIRECTORY_SEPARATOR)
        $directory = substr($directory, 0, -1);

    if (!empty($directory)) {

        if (!is_dir($directory)) {
            $is_dir = false;
        } else {
            $is_dir = true;
        }
        
        if (!$is_dir) die('Error! Directory does not exist.');
        
        if (!is_dir($directory . DIRECTORY_SEPARATOR .'database')) die('Error! Cannot find libary files.');
        
        if (filemtime(IL_DATABASE_PATH . DIRECTORY_SEPARATOR .'library.sq3') >
            filemtime($directory.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'library.sq3'))
            die('Error! This backup is older then your library. Use Restore function instead.');

        if ($is_dir && is_writable(IL_LIBRARY_PATH)) {

            if (!is_readable($directory . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'library.sq3'))
                die('Error! Access denied. Cannot read the library.');

            database_connect(IL_USER_DATABASE_PATH, 'users');
            save_setting($dbHandle, 'backup_dir', $directory);
            $dbHandle = null;

            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                exec("xcopy \"" . $directory . "\" \"" . IL_LIBRARY_PATH . "\" /c /v /q /s /e /h /y /d");
            } else {
                exec(escapeshellcmd("cp -pru \"" . $directory . "\" \"" . IL_LIBRARY_PATH . DIRECTORY_SEPARATOR . "\""));
            }
            die('Done');
        } else {
            die('Error! Access denied or directory does not exist.');
        }
    }

    $backup_dir = get_setting('backup_dir');
    ?>

    <table style="width: 100%"><tr><td class="details alternating_row"><b>Synchronize library with a backup copy</b></td></tr></table>
    <div class="item-sticker ui-widget-content ui-corner-all" style="margin:auto;margin-top:100px;width:500px">
        <div class="ui-dialog-titlebar ui-state-default ui-corner-top" style="border:0;padding-left:1em">
            Enter the directory path, where the backup copy is stored:<br>
        </div>
        <div class="separator" style="margin:0"></div>
        <div class="alternating_row items ui-corner-bottom">
            <button class="open-dirs-button" title="Browse directories"><i class="fa fa-folder-open"></i></button>
            <form action="synchronize.php" method="GET" id="form-synchronize">
                <input type="text" size="50" style="width:420px" name="directory" value="<?php if (!empty($backup_dir)) print $backup_dir; ?>"><br>
                <span class="ui-state-error-text">The backup must be newer then this library.</span><br>
                <input type="submit" value="Proceed">
            </form>
        </div>
    </div>


    <?php
} else {
    print "<p>Super User permissions required.</p>";
}
?>