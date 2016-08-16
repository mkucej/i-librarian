<?php

include_once 'data.php';
include_once 'functions.php';

function formatBytes($size, $precision = 1) {
    $base = log($size) / log(1024);
    $suffixes = array('', 'k', 'M', 'G', 'T');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];

}

if (isset($_SESSION['auth']) && isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {

    // Save Libre Office path.
    if (!empty($_GET['soffice_path'])) {

        database_connect(IL_USER_DATABASE_PATH, 'users');

        save_settings($dbHandle, array('global_soffice_path' => $_GET['soffice_path']));

        $dbHandle = null;

        die();
    }

    session_write_close();

    include_once 'functions.php';

    print '<b>&nbsp;Installation Details:</b>';

    print '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%">';

    if ($hosted == false) {

        print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Required software:</td></tr>";

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">PHP version</td>";

        print "<td class=\"details\">>5.4.0</td><td class=\"details\">" . PHP_VERSION . "</td>";

        print "<td class=\"details\">";

        print version_compare(PHP_VERSION, "5.4.0", "<") ? "<span style=\" font-weight: bold\">!!!</span>" : "<span style=\" font-weight: bold\">OK</span>";

        print "</td></tr>";

        database_connect(IL_DATABASE_PATH, 'library');

        $sqlite_version = $dbHandle->query("SELECT sqlite_version()");

        $dbHandle = null;

        $sqlite_version = $sqlite_version->fetchColumn();

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">SQLite database version</td>";

        print "<td class=\"details\">>3.7.11</td><td class=\"details\">$sqlite_version</td>";

        print "<td class=\"details\" style=\"\">";

        print version_compare($sqlite_version, "3.7.11", "<") ? "<span style=\" font-weight: bold\">!!!</span>" : "<span style=\" font-weight: bold\">OK</span>";

        print "</td></tr>";

        print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Required PHP extensions:</td></tr>";

        $extensions = array('pdo' => 'built-in SQLite database',
            'pdo_sqlite' => 'built-in SQLite database',
            'gd' => 'icon views and PDF viewer',
            'fileinfo' => 'file type detection',
            'openssl' => 'secure HTTP connections',
            'zip' => 'export to ZIP',
            'curl' => 'getting resources from the Web',
            'simplexml' => 'internal XML extension',
            'xml' => 'internal XML extension',
            'json' => 'JSON extension');

        while (list($extension, $feature) = each($extensions)) {

            print "<tr><td class=\"details\" style=\"white-space: nowrap\">$extension</td><td class=\"details\" style=\"white-space: nowrap\">$feature</td>";

            if (extension_loaded($extension)) {
                print "<td class=\"details\">installed</td><td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
            } else {
                print "<td class=\"details\">not installed</td><td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
            }
        }

        print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Optional PHP extensions:</td></tr>";

        $extensions = array('ldap' => 'LDAP authentication');

        while (list($extension, $feature) = each($extensions)) {

            print "<tr><td class=\"details\" style=\"white-space: nowrap\">$extension</td><td class=\"details\" style=\"white-space: nowrap\">$feature</td>";

            if (extension_loaded($extension)) {
                print "<td class=\"details\">installed</td><td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
            } else {
                print "<td class=\"details\">not installed</td><td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
            }
        }

        print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>";

        print "Required php.ini settings:</td></tr>";

        $directives = array(
            'file_uploads' => '1',
            'upload_max_filesize' => '200M',
            'post_max_size' => '800M',
            'max_input_time' => '60',
            'max_input_vars' => '10000');

        while (list($directive, $value) = each($directives)) {

            if (intval(ini_get($directive)) < intval($value)) {

                print "<tr><td class=\"details\" style=\"white-space: nowrap\">$directive</td>";
                print "<td class=\"details\" style=\"\">recommended $value</td>";
                print "<td class=\"details\" style=\"\">" . ini_get($directive) . "</td>";
                print "<td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
            } else {
                print "<tr><td class=\"details\" style=\"white-space: nowrap\">$directive</td>";
                print "<td class=\"details\" style=\"\">recommended $value</td>";
                print "<td class=\"details\" style=\"\">" . ini_get($directive) . "</td>";
                print "<td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
            }
        }

        if (ini_get('open_basedir') != false) {

            print "<tr><td class=\"details\" style=\"white-space: nowrap\">open_basedir</td>";
            print "<td class=\"details\" style=\"\">required disabled</td>";
            print "<td class=\"details\" style=\"\">" . ini_get('open_basedir') . "</td>";
            print "<td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
        } else {
            print "<tr><td class=\"details\" style=\"white-space: nowrap\">open_basedir</td>";
            print "<td class=\"details\" style=\"\">required disabled</td>";
            print "<td class=\"details\" style=\"\">disabled</td>";
            print "<td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
        }

        if (ini_get('allow_url_fopen') != true) {

            print "<tr><td class=\"details\" style=\"white-space: nowrap\">allow_url_fopen</td>";
            print "<td class=\"details\" style=\"\">required On</td>";
            print "<td class=\"details\" style=\"\">Off</td>";
            print "<td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
        } else {
            print "<tr><td class=\"details\" style=\"white-space: nowrap\">allow_url_fopen</td>";
            print "<td class=\"details\" style=\"\">required On</td>";
            print "<td class=\"details\" style=\"\">On</td>";
            print "<td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
        }

        print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Required binary executables:</td></tr>";

        print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Pdftotext</td>";

        print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">PDF full-text search</td>";

        print '<td class="details" id="details-1" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
        print '<td class="details" id="details-2" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

        print '</tr>';

        print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Pdfinfo</td>";

        print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">built-in PDF viewer</td>";

        print '<td class="details" id="details-3" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
        print '<td class="details" id="details-4" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

        print '</tr>';

        print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Pdftohtml</td>";

        print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">PDF search in the built-in PDF viewer</td>";

        print '<td class="details" id="details-5" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
        print '<td class="details" id="details-6" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

        print '</tr>';

        print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Ghostscript</td>";

        print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">icon views, built-in PDF viewer</td>";

        print '<td class="details" id="details-7" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
        print '<td class="details" id="details-8" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

        print '</tr>';

        print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Pdfdetach</td>";

        print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">unpacking PDF-attached files</td>";

        print '<td class="details" id="details-11" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
        print '<td class="details" id="details-12" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

        print '</tr>';

        print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>Optional binary executables:</td></tr>";

        print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">Tesseract OCR</td>";

        print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">optical character recognition</td>";

        print '<td class="details" id="details-13" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
        print '<td class="details" id="details-14" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';

        print '</tr>';

        print "<tr><td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">LibreOffice</td>";
        print "<td class=\"details\" style=\"white-space: nowrap;height:19px;line-height:19px\">office documents import and conversion</td>";
        print '<td class="details" id="details-15" style="white-space: nowrap;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td>';
        print '<td class="details" id="details-16" style="font-weight: bold;height:19px;line-height:19px"><img src="img/ajaxloader.gif" style="vertical-align:middle"></td></tr>';

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            if (empty($_SESSION['soffice_path'])) {
                // Translate default LibreOffice path.
                exec('echo %PROGRAMFILES%\\LibreOffice 5\\program', $output);
                $soffice_path = $output[0];
            } else {
                $soffice_path = $_SESSION['soffice_path'];
            }
            // If LibreOffice not there, prompt for new path.
            if (!is_executable($soffice_path[0] . DIRECTORY_SEPARATOR . 'soffice.exe')) {
                echo '<tr><td class="details">LibreOffice Path:</td><td class="details" colspan=3>';
                echo '<form id="details-form"><input type="text" name="soffice_path" value="' . $soffice_path . '" style="width:30em">';
                echo ' <span class="ui-state-default">&nbsp;Save&nbsp;</span></form>';
                echo '</td></tr>';
            }
        }
        echo "</td>";

        print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>I, Librarian " . $version . " is installed in \"" . __DIR__ . "\":</td></tr>";

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">Path to PDF files:</td><td class=\"details\" style=\"font-size: 11px\">" . IL_PDF_PATH  . "</td>";

        if (is_writable(IL_PDF_PATH) && @file_exists(IL_PDF_PATH . DIRECTORY_SEPARATOR . '.')) {

            print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
        } else {

            print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
        }

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">Path to supplementary files:</td><td class=\"details\" style=\"font-size: 11px\">" . IL_SUPPLEMENT_PATH . "</td>";

        if (is_writable(IL_SUPPLEMENT_PATH) && @file_exists(IL_SUPPLEMENT_PATH . DIRECTORY_SEPARATOR . '.')) {

            print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
        } else {

            print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
        }

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">Path to database files:</td><td class=\"details\" style=\"font-size: 11px\">" . IL_DATABASE_PATH . "</td>";

        if (is_writable(IL_DATABASE_PATH) && @file_exists(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . '.')) {

            print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
        } else {

            print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
        }

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">Path to PDF images:</td><td class=\"details\" style=\"font-size: 11px\">" . IL_IMAGE_PATH . "</td>";

        if (is_writable(IL_IMAGE_PATH) && @file_exists(IL_IMAGE_PATH . DIRECTORY_SEPARATOR . '.')) {

            print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
        } else {

            print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
        }

        print "<tr><td class=\"details\" style=\"white-space: nowrap\">Temporary directory:</td><td class=\"details\" style=\"font-size: 11px\">" . IL_TEMP_PATH . " <span id=\"clear-trash\" class=\"ui-state-default\">&nbsp;<i class=\"fa fa-trash-o\"></i> Clear&nbsp;</span></td>";

        if (is_writable(IL_TEMP_PATH) && @file_exists(IL_TEMP_PATH . DIRECTORY_SEPARATOR . '.')) {

            print "<td class=\"details\" style=\"white-space: nowrap\">writable, executable</td><td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
        } else {

            print "<td class=\"details\" style=\"white-space: nowrap\">not writable or executable</td><td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
        }
    }

    print "<tr><td class=\"details alternating_row\" style=\"width: 100%\" colspan=4>SQLite database files:</td></tr>";

    $database_files = scandir(IL_DATABASE_PATH);

    while (list($key, $database_file) = each($database_files)) {

        if (substr($database_file, -4) == '.sq3') {

            $dbsize = filesize(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . $database_file);
            $dbsize = formatBytes($dbsize);

            print "<tr><td class=\"details\">$database_file</td>";
            print "<td class=\"details\"><div class=\"file-size\" style=\"width:7em;float:left\">" . $dbsize . "B</div>";
            print ' <span class="ui-state-default integrity" data-db="' . basename($database_file, '.sq3') . '">&nbsp;Check Integrity&nbsp;</span>';
            print ' <span class="ui-state-default vacuum" data-db="' . basename($database_file, '.sq3') . '">&nbsp;Vacuum&nbsp;</span>';
            print '</td>';

            if (is_writable(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . $database_file)) {

                print "<td class=\"details\" style=\"white-space: nowrap\">writable</td><td class=\"details\" style=\" font-weight: bold\">OK</td></tr>";
            } else {

                print "<td class=\"details\" style=\"white-space: nowrap\">not writable</td><td class=\"details\" style=\" font-weight: bold\">!!!</td></tr>";
            }
        }
    }

    print '</table><br>';
} else {
    print 'Super User authorization required.';
}

?>