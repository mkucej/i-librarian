<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'desk' &&
        $_GET['select'] != 'clipboard') {

    $_GET['select'] = 'library';
}

if ($_GET['select'] == 'desk') {
    include 'desktop.php';
    die();
}
?>
<div class="leftindex" id="leftindex-left" style="float:left;width:233px;height:100%;overflow:scroll;border:0;margin:0px">
    <form id="quicksearch" action="search.php" method="GET" target="rightpanel">
        <table class="ui-state-highlight" style="width:100%;border-bottom:1px solid rgba(0,0,0,0.15)">
            <tr>
                <td class="quicksearch">
                    <input type="text" name="anywhere" placeholder="Quick Search" style="width:99%" value="<?php print isset($_SESSION['session_anywhere']) ? htmlspecialchars($_SESSION['session_anywhere']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <td class="quicksearch">
                    <table style="float:left;margin-top:0.2em;margin-left:2px">
                        <tr>
                            <td class="select_span">
                                <input type="radio" name="anywhere_separator" value="AND" style="display:none" checked>
                                <i class="fa fa-circle"></i> and&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="anywhere_separator" value="OR" style="display:none">
                                <i class="fa fa-circle-o"></i> or&nbsp;&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="radio" name="anywhere_separator" value="PHRASE" style="display:none">
                                <i class="fa fa-circle-o"></i> phrase
                            </td>
                        </tr>
                    </table>
                    <button id="search" style="width:32px;height:24px" title="Search"><i class="fa fa-search"></i></button><button
                        id="clear" style="width:24px;height:24px" title="Clear"><i class="fa fa-trash-o"></i></button>
                    <input type="hidden" name="select" value="<?php print $_GET['select']; ?>">
                    <input type="hidden" name="project" value="">
                    <input type="hidden" name="searchtype" value="metadata">
                    <input type="hidden" name="searchmode" value="quick">
                    <input type="hidden" name="rating[]" value="1">
                    <input type="hidden" name="rating[]" value="2">
                    <input type="hidden" name="rating[]" value="3">
                </td>
            </tr>
        </table>
    </form>
    <div id="advancedsearchbutton" class="ui-corner-bl leftleftbutton" style="width:6.2em;float:left;margin-left:8px;text-align:center;height:auto;cursor:pointer">
        Advanced
    </div>
    <div id="expertsearchbutton" class="ui-corner-br leftleftbutton" style="width:5.2em;float:left;margin-left:1px;text-align:center;height:auto;cursor:pointer">
        Expert
    </div>
    <div style="clear:both"></div>
    <br>
    <?php
    if (isset($_SESSION['auth'])) {
        ?>
        <table cellspacing=0 width="95%" style="margin: 6px 0">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton ui-widget-header ui-corner-right" id="savedsearchlink">
                    Saved searches
                </td>
            </tr>
        </table>
        <div id="savedsearch_container" style="margin-left: 10px;display:none">
        </div>
        <?php
    }
    ?>
    <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="categorylink">
                Categories
            </td>
        </tr>
    </table>
    <div id="categories_top_container" style="margin-left:10px;display:none">
        <input type="text" size="25" style="width:190px" id="filter_categories" value="" placeholder="Filter">
        <div id="first_categories" style="white-space: nowrap"></div>
    </div>
    <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="additiondatelink">
                Addition&nbsp;Dates
            </td>
        </tr>
    </table>
    <div id="datepicker" style="margin: 4px 0px 4px 6px;display:none"></div>
    <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="authorlink">
                Authors
            </td>
        </tr>
    </table>
    <div id="authors_top_container" style="margin-left: 10px;display:none">
        <div id="authors_header" style="margin: 0px 30px 4px 0px">
            <?php
            $alphabet = array('a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E', 'f' => 'F', 'g' => 'G', 'h' => 'H', 'i' => 'I', 'j' => 'J', 'k' => 'K', 'l' => 'L', 'm' => 'M',
                'n' => 'N', 'o' => 'O', 'p' => 'P', 'q' => 'Q', 'r' => 'R', 's' => 'S', 't' => 'T', 'u' => 'U', 'v' => 'V', 'w' => 'W', 'x' => 'X', 'y' => 'Y', 'z' => 'Z', 'all' => 'All');

            while (list($small, $large) = each($alphabet)) {
                print "  <span class=\"letter\" style=\"cursor:pointer\">$large</span>" . PHP_EOL;
            }
            ?>
            <table style="margin: 0px 10px 0px 0px">
                <tr>
                    <td><input type="text" size="25" style="width:190px" id="filter_authors" value="" placeholder="Filter"></td>
                </tr>
                <tr>
                    <td>
                        <span class="ui-state-highlight" style="margin-left:2px;padding:1px 8px" id="prevprev_authors">
                            <i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>
                        </span>
                        <span class="ui-state-highlight" style="margin-left:2px;padding:1px 8px" id="prev_authors">
                            <i class="fa fa-caret-left"></i>
                        </span>
                        <span class="ui-state-highlight" style="float:right;margin-right:2px;padding:1px 8px" id="next_authors">
                            <i class="fa fa-caret-right"></i>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        <div id="authors_container" style="white-space: nowrap"></div>
        <div id="filtered_authors" style="white-space: nowrap"></div>
    </div>
    <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="journallink">
                Journals
            </td>
        </tr>
    </table>
    <div id="journals_top_container" style="margin-left: 10px;display:none">
        <input type="text" size="25" style="width:190px" id="filter_journals" value="" placeholder="Filter">
        <div id="journals_container" style="white-space: nowrap"></div>
    </div>
    <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="secondarytitlelink">
                Secondary&nbsp;Titles
            </td>
        </tr>
    </table>
    <div id="secondarytitles_top_container" style="margin-left: 10px;display:none">
        <input type="text" size="25" style="width:190px" id="filter_secondarytitles" value="" placeholder="Filter">
        <div id="secondarytitles_container" style="white-space: nowrap"></div>
    </div>
    <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="tertiarytitlelink">
                Tertiary&nbsp;Titles
            </td>
        </tr>
    </table>
    <div id="tertiarytitles_top_container" style="margin-left: 10px;display:none">
        <input type="text" size="25" style="width:190px" id="filter_tertiarytitles" value="" placeholder="Filter">
        <div id="tertiarytitles_container" style="white-space: nowrap"></div>
    </div>
    <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
        <tr>
            <td class="leftleftbutton">&nbsp;</td>
            <td class="leftbutton ui-widget-header ui-corner-right" id="keywordlink">
                Keywords
            </td>
        </tr>
    </table>
    <div id="keywords_top_container" style="margin-left: 10px;display:none">
        <table border=0 cellspacing=0 cellpadding=0 style="margin: 0px 10px 4px 0px">
            <tr>
                <td><input type="text" size="25" style="width:190px" id="filter_keywords" value="" placeholder="Filter" style="margin: 0px"></td>
            </tr>
            <tr>
                <td>
                    <span class="ui-state-highlight" style="margin-left:2px;padding:1px 8px" id="prevprev_keywords">
                        <i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>
                    </span>
                    <span class="ui-state-highlight" style="margin-left:2px;padding:1px 8px" id="prev_keywords">
                        <i class="fa fa-caret-left"></i>
                    </span>
                    <span class="ui-state-highlight" style="float:right;margin-right:2px;padding:1px 8px" id="next_keywords">
                        <i class="fa fa-caret-right"></i>
                    </span>
                </td>
            </tr>
        </table>
        <div id="keywords_container" style="white-space: nowrap"></div>
        <div id="filtered_keywords" style="white-space: nowrap"></div>
    </div>
    <?php
    if (isset($_SESSION['auth']) && $_GET['select'] == 'library') {
        ?>
        <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton ui-widget-header ui-corner-right" id="misclink">
                    Miscellaneous
                </td>
            </tr>
        </table>
        <div id="misc_container" style="margin-left: 10px;display:none">
            <span class="misc" id="noshelf">Items not in Shelf</span><br>
            <span class="misc" id="nopdf">Items without PDF</span><br>
            <span class="misc" id="noindex">Items with unindexed PDF</span><br>
            <span class="misc" id="myitems">Items added by me</span><br>
            <span class="misc" id="othersitems">Items added by others</span><br>
        </div>
        <table border=0 cellspacing=0 cellpadding=0 width="95%" style="margin: 6px 0">
            <tr>
                <td class="leftleftbutton">&nbsp;</td>
                <td class="leftbutton ui-widget-header ui-corner-right" id="historylink">
                    History
                </td>
            </tr>
        </table>
        <?php
    }
    ?>
    <div style="height:1200px;width:50%">&nbsp;</div>
</div>
<div class="alternating_row middle-panel"
     style="float:left;width:6px;height:100%;overflow:hidden;border-right:1px solid #b5b6b8;cursor:pointer">
    <i class="fa fa-caret-left" style="position:relative;left:1px;top:46%"></i>
</div>
<div style="width:auto;height:100%;overflow:scroll" id="right-panel"><div>