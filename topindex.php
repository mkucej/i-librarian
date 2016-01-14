<?php
include_once 'data.php';
include_once 'functions.php';
?>
<table class="noprint" style="width:100%;height:100%">
    <tr>
        <td class="topindex" id="bottomrow" style="padding-left:6px;vertical-align: middle;height:100%">
            <a href="leftindex.php?select=library" title="All Items" class="topindex topindex_clicked" id="link-library">Library</a>
            <?php
            if (isset($_SESSION['auth'])) {
            ?>
            <a href="leftindex.php?select=shelf" title="Personal Shelf" class="topindex" id="link-shelf">Shelf</a>
            <a href="leftindex.php?select=desktop" title="Create/Open Projects" class="topindex" id="link-desk">Desk</a>
            <a href="leftindex.php?select=clipboard" title="Temporary List" class="topindex" id="link-clipboard">Clipboard</a>
            <?php
            if (isset($_SESSION['permissions']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {
            ?>
            <a href="addarticle.php" class="topindex" id="link-record">Add Record</a>
            <?php
            }
            ?>
            <a href="tools.php" class="topindex" id="link-tools">Tools</a>
            <i id="keyboardswitch" class="fa fa-keyboard-o" style="font-size:18px;margin-left:0.5em;cursor:pointer" title="Extended Keyboard (F2)"></i>
            <?php
            }
            ?>
        </td>
        <td class="topindex" style="padding-right:1em;vertical-align: middle;height:100%;text-align:right">
            <span id="link-signout" style="cursor:pointer" title="Sign Out">
                <span id="username-span"><?php print htmlspecialchars($_SESSION['user']) ?></span>
                &nbsp;&nbsp;<i class="fa fa-power-off"></i>
            </span>
        </td>
    </tr>
</table>