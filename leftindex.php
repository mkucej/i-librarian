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
        <table class="ui-state-default" style="width:100%;border-bottom:none">
            <tr>
                <td class="quicksearch" style="padding:0">
                    <input type="text" name="global" placeholder="Global Search"
                           value="<?php echo isset($_SESSION['session_global']) ? htmlspecialchars($_SESSION['session_global']) : ''; ?>">
                    <input type="text" name="anywhere" placeholder="Quick Search" style="display:none"
                           value="<?php echo isset($_SESSION['session_anywhere']) ? htmlspecialchars($_SESSION['session_anywhere']) : ''; ?>">
                    <input type="text" name="fulltext" placeholder="PDF Search" style="display:none"
                           value="<?php echo isset($_SESSION['session_fulltext']) ? htmlspecialchars($_SESSION['session_fulltext']) : ''; ?>">
                    <input type="text" name="pdfnotes" placeholder="PDF Notes Search" style="display:none" 
                           value="<?php echo isset($_SESSION['session_pdfnotes']) ? htmlspecialchars($_SESSION['session_pdfnotes']) : ''; ?>">
                    <input type="text" name="notes" placeholder="Rich-Text Notes Search" style="display:none"
                           value="<?php echo isset($_SESSION['session_notes']) ? htmlspecialchars($_SESSION['session_notes']) : ''; ?>">
                </td>
            </tr>
        </table>
        <div id="global_separator" class="separators">
            <input type="radio" id="global-separator1" name="global-separator" value="AND"<?php
            if (!isset($_SESSION['session_global_separator']) || $_SESSION['session_global_separator'] == 'AND') echo 'checked';
            ?>>
            <label for="global-separator1">AND</label>
            <input type="radio" id="global-separator2" name="global-separator" value="OR"<?php
            if (isset($_SESSION['session_global_separator']) && $_SESSION['session_global_separator'] == 'OR') echo 'checked';
            ?>>
            <label for="global-separator2">OR</label>
            <input type="radio" id="global-separator3" name="global-separator" value="PHRASE"<?php
            if (isset($_SESSION['session_global_separator']) && $_SESSION['session_global_separator'] == 'PHRASE') echo 'checked';
            ?>>
            <label for="global-separator3">phrase</label>
        </div>
        <div id="anywhere_separator" class="separators" style="display:none">
            <input type="radio" id="anywhere-separator1" name="anywhere-separator" value="AND"<?php
            if (!isset($_SESSION['session_anywhere_separator']) || $_SESSION['session_anywhere_separator'] == 'AND') echo 'checked';
            ?>>
            <label for="anywhere-separator1">AND</label>
            <input type="radio" id="anywhere-separator2" name="anywhere-separator" value="OR"<?php
            if (isset($_SESSION['session_anywhere_separator']) && $_SESSION['session_anywhere_separator'] == 'OR') echo 'checked';
            ?>>
            <label for="anywhere-separator2">OR</label>
            <input type="radio" id="anywhere-separator3" name="anywhere-separator" value="PHRASE"<?php
            if (isset($_SESSION['session_anywhere_separator']) && $_SESSION['session_anywhere_separator'] == 'PHRASE') echo 'checked';
            ?>>
            <label for="anywhere-separator3">phrase</label>
        </div>
        <div id="fulltext_separator" class="separators" style="display:none">
            <input type="radio" id="fulltext-separator1" name="fulltext-separator" value="AND"<?php
            if (!isset($_SESSION['session_fulltext_separator']) || $_SESSION['session_fulltext_separator'] == 'AND') echo 'checked';
            ?>>
            <label for="fulltext-separator1">AND</label>
            <input type="radio" id="fulltext-separator2" name="fulltext-separator" value="OR"<?php
            if (isset($_SESSION['session_fulltext_separator']) && $_SESSION['session_fulltext_separator'] == 'OR') echo 'checked';
            ?>>
            <label for="fulltext-separator2">OR</label>
            <input type="radio" id="fulltext-separator3" name="fulltext-separator" value="PHRASE"<?php
            if (isset($_SESSION['session_fulltext_separator']) && $_SESSION['session_fulltext_separator'] == 'PHRASE') echo 'checked';
            ?>>
            <label for="fulltext-separator3">phrase</label>
        </div>
        <div id="pdfnotes_separator" class="separators" style="display:none">
            <input type="radio" id="pdfnotes-separator1" name="pdfnotes-separator" value="AND"<?php
            if (!isset($_SESSION['session_pdfnotes_separator']) || $_SESSION['session_pdfnotes_separator'] == 'AND') echo 'checked';
            ?>>
            <label for="pdfnotes-separator1">AND</label>
            <input type="radio" id="pdfnotes-separator2" name="pdfnotes-separator" value="OR"<?php
            if (isset($_SESSION['session_pdfnotes_separator']) && $_SESSION['session_pdfnotes_separator'] == 'OR') echo 'checked';
            ?>>
            <label for="pdfnotes-separator2">OR</label>
            <input type="radio" id="pdfnotes-separator3" name="pdfnotes-separator" value="PHRASE"<?php
            if (isset($_SESSION['session_pdfnotes_separator']) && $_SESSION['session_pdfnotes_separator'] == 'PHRASE') echo 'checked';
            ?>>
            <label for="pdfnotes-separator3">phrase</label>
        </div>
        <div id="notes_separator" class="separators" style="display:none">
            <input type="radio" id="notes-separator1" name="notes-separator" value="AND"<?php
            if (!isset($_SESSION['session_notes_separator']) || $_SESSION['session_notes_separator'] == 'AND') echo 'checked';
            ?>>
            <label for="notes-separator1">AND</label>
            <input type="radio" id="notes-separator2" name="notes-separator" value="OR"<?php
            if (isset($_SESSION['session_notes_separator']) && $_SESSION['session_notes_separator'] == 'OR') echo 'checked';
            ?>>
            <label for="notes-separator2">OR</label>
            <input type="radio" id="notes-separator3" name="notes-separator" value="PHRASE"<?php
            if (isset($_SESSION['session_notes_separator']) && $_SESSION['session_notes_separator'] == 'PHRASE') echo 'checked';
            ?>>
            <label for="notes-separator3">phrase</label>
        </div>
        <div style="clear:both"></div>
        <div style="padding:0.5em 0;text-align:center">
        <button id="search" style="width:42%;margin:auto"><i class="fa fa-search"></i> </button>
        <button id="clear" style="width:42%;margin:auto"><i class="fa fa-trash-o"></i> </button>
        </div>
        <input type="hidden" name="select" value="<?php print $_GET['select']; ?>">
        <input type="hidden" name="project" value="">
        <input type="hidden" name="searchtype" value="metadata">
        <input type="hidden" name="searchmode" value="quick">
        <input type="hidden" name="rating[]" value="1">
        <input type="hidden" name="rating[]" value="2">
        <input type="hidden" name="rating[]" value="3">
    </form>
    <div id="search-menu" style="width:100%">
        <div class="ui-state-default tabclicked" title="Global search"><i class="fa fa-database"></i></div>
        <div class="ui-state-default" title="Search metadata"><i class="fa fa-list"></i></div>
        <div class="ui-state-default" title="Search PDFs"><i class="fa fa-file-pdf-o"></i></div>
        <div class="ui-state-default" title="Search PDF notes"><i class="fa fa-comment"></i></div>
        <div class="ui-state-default" title="Search rich-text notes"><i class="fa fa-pencil"></i></div>
    </div>
    <div style="clear:both"></div>
    <button id="advancedsearchbutton" style="display:block;float:left;width:50%;margin:0;border-left:none;border-right:none">
        Advanced
    </button>
    <button id="expertsearchbutton" style="display:block;width:50%;margin:0;border-left:none;border-right:none">
        Expert
    </button>
    <br>
    <button id="savedsearchlink" class="menu">Saved searches</button>
    <div id="savedsearch_container" style="margin-top:0.5em;margin-left: 10px;display:none">
    </div>
    <button id="categorylink" class="menu">Categories</button>
    <div id="categories_top_container" style="margin-top:0.5em;margin-left:10px;display:none">
        <input type="text" size="25" style="width:190px" id="filter_categories" value="" placeholder="Filter">
        <div id="first_categories" style="white-space: nowrap"></div>
    </div>
    <button id="additiondatelink" class="menu">Addition&nbsp;Dates</button>
    <div id="datepicker" style="margin: 4px 0px 4px 6px;display:none"></div>
    <button id="authorlink" class="menu">Authors</button>
    <div id="authors_top_container" style="margin-left: 10px;display:none">
        <div id="authors_header" style="margin: 0.5em 6px 0.5em 0px">
            <?php
            $alphabet = array('a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E', 'f' => 'F', 'g' => 'G', 'h' => 'H', 'i' => 'I', 'j' => 'J', 'k' => 'K', 'l' => 'L', 'm' => 'M',
                'n' => 'N', 'o' => 'O', 'p' => 'P', 'q' => 'Q', 'r' => 'R', 's' => 'S', 't' => 'T', 'u' => 'U', 'v' => 'V', 'w' => 'W', 'x' => 'X', 'y' => 'Y', 'z' => 'Z', 'all' => 'All');

            while (list($small, $large) = each($alphabet)) {
                print "  <span class=\"letter\" style=\"cursor:pointer\">$large</span>" . PHP_EOL;
            }
            ?>
            <table style="margin: 0.5em 10px 0px 0px">
                <tr>
                    <td><input type="text" size="25" style="width:190px" id="filter_authors" value="" placeholder="Filter"></td>
                </tr>
                <tr>
                    <td>
                        <span class="ui-state-default" style="margin-left:2px;padding:1px 8px" id="prevprev_authors">
                            <i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>
                        </span>
                        <span class="ui-state-default" style="margin-left:2px;padding:1px 8px" id="prev_authors">
                            <i class="fa fa-caret-left"></i>
                        </span>
                        <span class="ui-state-default" style="float:right;margin-right:2px;padding:1px 8px" id="next_authors">
                            <i class="fa fa-caret-right"></i>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        <div id="authors_container" style="white-space: nowrap"></div>
        <div id="filtered_authors" style="white-space: nowrap"></div>
    </div>
    <button id="journallink" class="menu">Journals</button>
    <div id="journals_top_container" style="margin-top:0.5em;margin-left: 10px;display:none">
        <input type="text" size="25" style="width:190px" id="filter_journals" value="" placeholder="Filter">
        <div id="journals_container" style="white-space: nowrap"></div>
    </div>
    <button id="secondarytitlelink" class="menu">Secondary&nbsp;Titles</button>
    <div id="secondarytitles_top_container" style="margin-top:0.5em;margin-left: 10px;display:none">
        <input type="text" size="25" style="width:190px" id="filter_secondarytitles" value="" placeholder="Filter">
        <div id="secondarytitles_container" style="white-space: nowrap"></div>
    </div>
    <button id="tertiarytitlelink" class="menu">Tertiary&nbsp;Titles</button>
    <div id="tertiarytitles_top_container" style="margin-top:0.5em;margin-left: 10px;display:none">
        <input type="text" size="25" style="width:190px" id="filter_tertiarytitles" value="" placeholder="Filter">
        <div id="tertiarytitles_container" style="white-space: nowrap"></div>
    </div>
    <button id="keywordlink" class="menu">Keywords</button>
    <div id="keywords_top_container" style="margin-top:0.5em;margin-left: 10px;display:none">
        <table border=0 cellspacing=0 cellpadding=0 style="margin: 0px 10px 4px 0px">
            <tr>
                <td><input type="text" size="25" style="width:190px" id="filter_keywords" value="" placeholder="Filter" style="margin: 0px"></td>
            </tr>
            <tr>
                <td>
                    <span class="ui-state-default" style="margin-left:2px;padding:1px 8px" id="prevprev_keywords">
                        <i class="fa fa-caret-left"></i> <i class="fa fa-caret-left"></i>
                    </span>
                    <span class="ui-state-default" style="margin-left:2px;padding:1px 8px" id="prev_keywords">
                        <i class="fa fa-caret-left"></i>
                    </span>
                    <span class="ui-state-default" style="float:right;margin-right:2px;padding:1px 8px" id="next_keywords">
                        <i class="fa fa-caret-right"></i>
                    </span>
                </td>
            </tr>
        </table>
        <div id="keywords_container" style="white-space: nowrap"></div>
        <div id="filtered_keywords" style="white-space: nowrap"></div>
    </div>
    <?php
    if (isset($_GET['select']) && $_GET['select'] == 'library') {
        ?>
        <button id="misclink" class="menu">Miscellaneous</button>
        <div id="misc_container" style="margin-top:0.5em;margin-left: 10px;display:none">
            <span class="misc" id="noshelf">Items not in Shelf</span><br>
            <span class="misc" id="nopdf">Items without PDF</span><br>
            <span class="misc" id="noindex">Items with unindexed PDF</span><br>
            <span class="misc" id="myitems">Items added by me</span><br>
            <span class="misc" id="othersitems">Items added by others</span><br>
            <span class="misc" id="withnotes">Items with Notes</span><br>
            <span class="misc" id="discussed">Discussed Items</span><br>
        </div>
        <button id="historylink" class="menu">History</button>
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