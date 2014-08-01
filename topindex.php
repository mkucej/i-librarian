<?php
include_once 'data.php';
include_once 'functions.php';
?>
<table class="noprint" style="width:100%">
    <tr>
        <td class="topindex" id="bottomrow" style="padding-top:5px;padding-left:4px;height:22px">
            <a href="leftindex.php?select=library" title="All Items" class="topindex topindex_clicked ui-corner-all" id="link-library">Library</a>
            <?php
            if (isset($_SESSION['auth'])) {
            ?>
            <a href="leftindex.php?select=shelf" title="Personal Shelf" class="topindex ui-corner-all" id="link-shelf">Shelf</a>
            <a href="leftindex.php?select=desktop" title="Create/Open Projects" class="topindex ui-corner-all" id="link-desk">Desk</a>
            <a href="leftindex.php?select=clipboard" title="Temporary List" class="topindex ui-corner-all" id="link-clipboard">Clipboard</a>
            <?php
            if (isset($_SESSION['permissions']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {
            ?>
            <a href="addarticle.php" class="topindex ui-corner-all" id="link-record">Add Record</a>
            <?php
            }
            ?>
            <a href="tools.php" class="topindex ui-corner-all" id="link-tools">Tools</a>
            <i id="keyboardswitch" class="fa fa-keyboard-o" style="font-size:16px;margin-left:0.5em;cursor:pointer" title="Extended Keyboard (F2)"></i>
            <?php
            }
            ?>
        </td>
        <td class="topindex" style="padding-top:6px">
            <table style="float:right;margin-right:12px">
                <tr>
                    <td id="link-signout" style="line-height:22px;height:22px;cursor:pointer" title="Sign Out">
                        <span id="username-span"><?php print htmlspecialchars($_SESSION['user']) ?></span>
                        &nbsp;&nbsp;<i class="fa fa-power-off"></i>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>