<?php
include_once 'data.php';
?>
<div class="leftindex" style="float:left;width:240px;height:100%;overflow:scroll;margin:0px;padding:0px;border:0px" id="tools-left">
    <table cellspacing=0 style="margin:8px 0 6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="rtfscanlink">
                Citation Scan
            </td>
        </tr>
    </table>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="citationstyleslink">
                Citation Styles
            </td>
        </tr>
    </table>
    <?php
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
        ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="duplicateslink">
                Find Duplicates
            </td>
        </tr>
    </table>
    <?php
    }
    ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="fontslink">
                Fonts & Colors
            </td>
        </tr>
    </table>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="userslink">
                User Management
            </td>
        </tr>
    </table>
    <?php
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A' && $hosted == false) {
        ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="backuplink">
                Backup / Restore
            </td>
        </tr>
    </table>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="synclink">
                Synchronize
            </td>
        </tr>
    </table>
    <?php
    }
    if ($_SESSION['auth'] && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {
    ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="renamejournallink">
                Rename Journal
            </td>
        </tr>
    </table>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="renamecategorylink">
                Edit Categories
            </td>
        </tr>
    </table>
    <?php
    }
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
        ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="detailslink">
                Installation Details
            </td>
        </tr>
    </table>
    <?php
    }
    ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="settingslink">
                Settings
            </td>
        </tr>
    </table>
    <?php
    if ($_SESSION['auth'] && $_SESSION['permissions'] == 'A') {
        ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="reindexlink">
                Batch PDF indexing
            </td>
        </tr>
    </table>
    <?php
    }
    ?>
    <table cellspacing=0 style="margin:6px 0;width:93%">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="aboutlink">
                About I, Librarian
            </td>
        </tr>
    </table>
</div>
<div style="width:auto;height:100%;overflow:auto" id="right-panel"></div>