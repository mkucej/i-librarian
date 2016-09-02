<?php
include_once 'data.php';
include_once 'functions.php';
set_time_limit(0);

if ($hosted == true)
    die();

function safe_copy($file, $directory) {

    $path_to = str_replace(IL_LIBRARY_PATH, $directory, $file);

    if (!is_file($path_to) || (is_file($path_to) && filemtime($file) > filemtime($path_to))) {

        $fp = fopen($file, "r");

        if (flock($fp, LOCK_SH)) {

            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                exec('copy "' . $file . '" "' . $path_to . '" /Y');
            } else {
                exec(escapeshellcmd('cp -p "' . $file . '" "' . $path_to . '"'));
            }

            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            fclose($fp);
        }
    }
}

if (isset($_SESSION['auth']) && $_SESSION['permissions'] == 'A') {

    if (!empty($_GET['backup'])) {

        $directory = '';

        if (isset($_GET['directory'])) {
            $directory = $_GET['directory'];
        }

        // Convert DIRECTORY_SEPARATOR to forward slashes.
        $directory = str_replace(DIRECTORY_SEPARATOR, '/', $directory);

        // Remove directory trailing slash.
        if (substr($directory, -1) == '/') {
            $directory = substr($directory, 0, -1);
        }

        if (empty($directory)) {

            /*
             * Initial backup form. Calculate required drive space.
             */

            $required_space = null;
            $f_number = 1;

            $lit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(IL_LIBRARY_PATH));
            while ($lit->valid()) {
                $file = $lit->key();
                if (is_file($file)) {
                    $required_space += filesize($file);
                    $f_number++;
                }
                $lit->next();
            }

        } else {

            /*
             * Backup.
             */

            // Directory must end with /library.
            if (substr($directory, -8) !== '/library') {
                $directory .= '/library';
            }

            if (!is_dir($directory)) {
                $is_dir = @mkdir($directory);
            } else {
                $is_dir = true;
            }

            if ($is_dir && is_writable($directory)) {

                database_connect(IL_USER_DATABASE_PATH, 'users');
                save_setting($dbHandle, 'backup_dir', $directory);
                $dbHandle = null;

                // Create folders.
                $lit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(IL_LIBRARY_PATH));
                while ($lit->valid()) {
                    $dir = $lit->key();
                    if (is_dir($dir)) {
                        $path_to = str_replace(IL_LIBRARY_PATH, $directory, $dir);
                        if (!is_dir($path_to)) {
                            mkdir($path_to);
                        }
                    }
                    $lit->next();
                }

                // Copy files.
                $lit = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(IL_LIBRARY_PATH));
                while ($lit->valid()) {
                    $file = $lit->key();
                    if (is_file($file)) {
                        safe_copy($file, $directory);
                    }
                    $lit->next();
                }

                // DELETE NON-EXISTENT FILES
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
                while ($it->valid()) {
                    $backupfile = $it->key();
                    if (is_file($backupfile)) {
                        $libfile = str_replace($directory, IL_LIBRARY_PATH, $backupfile);
                        if (!is_file($libfile)) {
                            @unlink($backupfile);
                        }
                    }
                    $it->next();
                }

                die('Done');
            } else {
                die('Error! Access denied or directory cannot be created.');
            }
        }

        $backup_dir = get_setting('backup_dir');
        ?>

        <table style="width: 100%"><tr><td class="details alternating_row"><b>Backup</b></td></tr></table>
        <div class="item-sticker ui-widget-content ui-corner-all" style="margin:auto;margin-top:100px;width:500px">
            <div class="ui-dialog-titlebar ui-state-default ui-corner-top" style="border:0;padding-left:1em">
                Enter the directory path, where the backup copy should be created:
            </div>
            <div class="separator" style="margin:0"></div>
            <div class="alternating_row items ui-corner-bottom">
                <form action="backup.php" method="GET" class="form-backup">
                    <input type="hidden" name="backup" value="1">
                    <button class="open-dirs-button" title="Browse directories"><i class="fa fa-folder-open"></i></button>
                    <input type="text" size="50" style="width:420px" name="directory" value="<?php if (!empty($backup_dir)) print $backup_dir; ?>"><br>
                    Total library size: <?php

                    function formatBytes($size, $precision = 1) {
                        $base = log($size) / log(1024);
                        $suffixes = array('', 'k', 'M', 'G', 'T');
                        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
                    }

                    print formatBytes($required_space);
                    ?>B
                    (<?php print number_format($f_number, '0', '.', ','); ?> files)<br>
                    Make sure that the destination drive has sufficient free space.<br>
                    <input type="submit" value="Proceed">
                </form>
            </div>
        </div>

        <?php
    } elseif (!empty($_GET['restore'])) {

        $directory = '';
        if (isset($_GET['directory'])) {
            $directory = $_GET['directory'];
        }

        // Convert DIRECTORY_SEPARATOR to forward slashes.
        $directory = str_replace(DIRECTORY_SEPARATOR, '/', $directory);

        // Remove directory trailing slash.
        if (substr($directory, -1) == '/') {
            $directory = substr($directory, 0, -1);
        }

        if (!empty($directory)) {

            /*
             * Restore.
             */

            // Directory must end with /library.
            if (substr($directory, -8) !== '/library') {
                $directory .= '/library';
            }

            if (!is_dir($directory)) {
                $is_dir = false;
            } else {
                $is_dir = true;
            }

            if ($is_dir && is_writable(IL_LIBRARY_PATH)) {

                if (!is_readable($directory . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'library.sq3'))
                    die('Error! Access denied or directory does not exist.');

                if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                    exec("rmdir \"" . IL_LIBRARY_PATH . "\" /s/q");
                    mkdir(IL_LIBRARY_PATH);
                    exec("xcopy \"" . $directory . "\" \"" . IL_LIBRARY_PATH . "\" /c /v /q /s /e /h /y", $a);
                } else {
                    exec("rm -rf \"" . IL_LIBRARY_PATH . "\"");
                    mkdir(IL_LIBRARY_PATH, 0755);
                    exec(escapeshellcmd("cp -r \"" . $directory . "\" \"" . IL_LIBRARY_PATH . DIRECTORY_SEPARATOR . "\""));
                }
                die('Done');
            } else {
                die('Error! Access denied or directory does not exist.');
            }
        }

        $backup_dir = get_setting('backup_dir');
        ?>

        <table style="width: 100%"><tr><td class="details alternating_row"><b>Restore</b></td></tr></table>
        <div class="item-sticker ui-widget-content ui-corner-all" style="margin:auto;margin-top:100px;width:500px">
            <div class="ui-dialog-titlebar ui-state-default ui-corner-top" style="border:0;padding-left:1em">
                Enter the directory path, where the backup copy is stored:<br>
            </div>
            <div class="separator" style="margin:0"></div>
            <div class="alternating_row items ui-corner-bottom">
                <form action="backup.php" method="GET" class="form-backup">
                    <input type="hidden" name="restore" value="1">
                    <button class="open-dirs-button" title="Browse directories"><i class="fa fa-folder-open"></i></button>
                    <input type="text" size="50" style="width:420px" name="directory" value="<?php if (!empty($backup_dir)) print $backup_dir; ?>"><br>
                    <span class="ui-state-error-text">This action will permanently delete your current library!</span><br>
                    <input type="submit" value="Proceed">
                </form>
            </div>
        </div>

        <?php
    } else {
        ?>
        <table style="width: 100%"><tr><td class="details alternating_row"><b>Backup Assistant</b></td></tr></table>
        <div style="text-align:center">
            <div style="width:250px;margin:auto;margin-top:100px">
                <div style="width:250px">
                    <span id="unlock-restore" class="fa fa-lock" style="float:right;cursor:pointer" title="Unlock restore"></span>
                </div>
                <div style="clear:both"></div>
                <div id="select-backup" class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:100px;float:left;text-align:left;cursor:pointer">
                    <div class="ui-dialog-titlebar ui-state-default ui-corner-top" style="text-align:center;border:0">Backup</div>
                    <div class="separator" style="margin:0"></div>
                    <div class="alternating_row ui-corner-bottom" style="padding:1em;overflow:auto;height:1.4em;text-align:center">
                        <span class="fa fa-file-o"></span>
                        <span class="fa fa-arrow-right"></span>
                        <span class="fa fa-save"></span>
                    </div>
                </div>
                <div id="select-restore" class="item-sticker ui-widget-content ui-corner-all ui-state-disabled" style="margin-left:4px;margin-top:4px;width:100px;float:right;text-align:left;cursor:pointer">
                    <div class="ui-dialog-titlebar ui-state-default ui-corner-top" style="text-align:center;border:0">Restore</div>
                    <div class="separator" style="margin:0"></div>
                    <div class="alternating_row ui-corner-bottom" style="padding:1em;overflow:auto;height:1.4em;text-align:center">
                        <span class="fa fa-save"></span>
                        <span class="fa fa-arrow-right"></span>
                        <span class="fa fa-file-o"></span>
                    </div>
                </div>
            </div>
            <div style="clear:both"></div>
            <div><br><br>Make sure that nobody is using the library.</div>
        </div>

        <?php
    }
    ?>
    <?php
} else {
    print "<p>Super User permissions required.</p>";
}
?>
