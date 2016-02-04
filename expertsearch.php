<?php
include_once 'data.php';

//METADATA

if (isset($_GET['searchtype']) && $_GET['searchtype'] == 'metadata') {

    $input = $_GET['search-metadata'];

    //SPLIT SEARCH INTO INDIVIDUAL TERMS
    $search_array = preg_split('/ AND | OR | NOT /', $input);

    //CREATE A QUERY FOR EACH TERM
    foreach ($search_array as $search) {

        //SPLIT THE TERM INTO AN OPTIONAL PARETHESIS, LAZY TEXT, AND A TAG
        if (preg_match('/([\s\(]*)(.*)?(\[[a-z0-9]{2}\])/i', $search, $matches) == 1) {

            //FORMAT THE TEXT FOR SEARCH
            $like_query_esc = str_replace("\\", "\\\\", trim($matches[2]));
            $like_query_esc = str_replace("%", "\%", $like_query_esc);
            $like_query_esc = str_replace("_", "\_", $like_query_esc);
            $like_query_esc = str_replace("<*>", "%", $like_query_esc);
            $like_query_esc = str_replace("<?>", "_", $like_query_esc);

            $regexp_query_esc = preg_quote(trim($matches[2]));
            $regexp_query_esc = str_replace('\<\*\>', '.*', $regexp_query_esc);
            $regexp_query_esc = str_replace('\<\?\>', '.?', $regexp_query_esc);

            $like_query = $dbHandle->quote("%$like_query_esc%");
            $regexp_query = str_replace("'", "''", $regexp_query_esc);

            //CHECK WHETHER TEXT CONTAINS UTF-8
            $translation = utf8_deaccent($like_query_esc);

            //AUTHORS [AU]
            if (strtolower($matches[3]) == '[au]') {

                $like_query = $dbHandle->quote("%L:\"$like_query_esc%");
                $regexp_query = str_replace("'", "''", "L:\"" . $regexp_query);

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%L:\"$translation%");
                    $regexp_query_translated = str_replace("'", "''", "L:\"" . $translation_regexp);
                    $like_sql = "authors LIKE $like_query ESCAPE '\' OR"
                            . " editor LIKE $like_query ESCAPE '\' OR"
                            . " authors_ascii LIKE $like_query_translated ESCAPE '\'";

                    $regexp_sql = "regexp_match(authors, '$regexp_query', $case2) OR"
                            . " regexp_match(editor, '$regexp_query', $case2) OR"
                            . " regexp_match(authors_ascii, '$regexp_query_translated', $case2)";
                } else {
                    $like_sql = "authors_ascii LIKE $like_query ESCAPE '\' OR"
                            . " editor LIKE $like_query ESCAPE '\'";

                    $regexp_sql = "regexp_match(authors_ascii, '$regexp_query', $case2) OR"
                            . " regexp_match(editor, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //JOURNAL [JO]
            } elseif (strtolower($matches[3]) == '[jo]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(journal LIKE $like_query ESCAPE '\' OR journal LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(journal, '$regexp_query', $case2) OR regexp_match(journal, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "journal LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(journal, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //SECONDARY TITLE [T2]
            } elseif (strtolower($matches[3]) == '[t2]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(secondary_title LIKE $like_query ESCAPE '\' OR secondary_title LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(secondary_title, '$regexp_query', $case2) OR regexp_match(secondary_title, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "secondary_title LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(secondary_title, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //TERTIARY TITLE [T3]
            } elseif (strtolower($matches[3]) == '[t3]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(tertiary_title LIKE $like_query ESCAPE '\' OR tertiary_title LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(tertiary_title, '$regexp_query', $case2) OR regexp_match(tertiary_title, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "tertiary_title LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(tertiary_title, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //AFFILIATION [AF]
            } elseif (strtolower($matches[3]) == '[af]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(affiliation LIKE $like_query ESCAPE '\' OR affiliation LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(affiliation, '$regexp_query', $case2) OR regexp_match(affiliation, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "affiliation LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(affiliation, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //TITLE [TI]
            } elseif (strtolower($matches[3]) == '[ti]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(title LIKE $like_query ESCAPE '\' OR title_ascii LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(title, '$regexp_query', $case2) OR regexp_match(title_ascii, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "title_ascii LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(title_ascii, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //KEYWORDS [KW]
            } elseif (strtolower($matches[3]) == '[kw]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(keywords LIKE $like_query ESCAPE '\' OR keywords LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(keywords, '$regexp_query', $case2) OR regexp_match(keywords, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "keywords LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(keywords, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //ABSTRACT AND TITLE [AB]
            } elseif (strtolower($matches[3]) == '[ab]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(title LIKE $like_query ESCAPE '\' OR abstract LIKE $like_query ESCAPE '\' OR
						title_ascii LIKE $like_query_translated ESCAPE '\' OR abstract_ascii LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(title, '$regexp_query', $case2) OR regexp_match(abstract, '$regexp_query', $case2) OR
						regexp_match(title_ascii, '$regexp_query_translated', $case2) OR regexp_match(abstract_ascii, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "(title_ascii LIKE $like_query ESCAPE '\' OR abstract_ascii LIKE $like_query ESCAPE '\')";
                    $regexp_sql = "(regexp_match(title_ascii, '$regexp_query', $case2) OR regexp_match(abstract_ascii, '$regexp_query', $case2))";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //CUSTOM 1 [C1]
            } elseif (strtolower($matches[3]) == '[c1]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(custom1 LIKE $like_query ESCAPE '\' OR custom1 LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(custom1, '$regexp_query', $case2) OR regexp_match(custom1, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "custom1 LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(custom1, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //CUSTOM 2 [C2]
            } elseif (strtolower($matches[3]) == '[c2]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(custom2 LIKE $like_query ESCAPE '\' OR custom2 LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(custom2, '$regexp_query', $case2) OR regexp_match(custom2, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "custom2 LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(custom2, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //CUSTOM 3 [C3]
            } elseif (strtolower($matches[3]) == '[c3]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(custom3 LIKE $like_query ESCAPE '\' OR custom3 LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(custom3, '$regexp_query', $case2) OR regexp_match(custom3, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "custom3 LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(custom3, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //CUSTOM 4 [C4]
            } elseif (strtolower($matches[3]) == '[c4]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(custom4 LIKE $like_query ESCAPE '\' OR custom4 LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(custom4, '$regexp_query', $case2) OR regexp_match(custom4, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "custom4 LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(custom4, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = "(($final) AND ($regexp_sql))";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //YEAR [YR]
            } elseif (strtolower($matches[3]) == '[yr]') {

                $final = "(year=" . intval($matches[2]) . " OR strftime('%Y', year) LIKE '" . intval($matches[2]) . "')";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);
            }
        } else {
            $input = '';
            break;
        }
    }

    $search_string = str_ireplace(' NOT ', ' AND NOT ', $input);
    
    $sql = "$in $rating_search $type_search $category_search $search_string";

//PDFS
} elseif (isset($_GET['searchtype']) && $_GET['searchtype'] == 'pdf') {

    $input = $_GET['search-pdfs'];

    //SPLIT SEARCH INTO INDIVIDUAL TERMS
    $search_array = preg_split('/ AND | OR | NOT /', $input);

    //CREATE A QUERY FOR EACH TERM
    foreach ($search_array as $search) {

        //SPLIT THE TERM INTO AN OPTIONAL PARETHESES, LAZY TEXT, AND PARETHESES
        if (preg_match('/([\s\(]*)([^\(\)]*)?([\s\)]*)/i', $search, $matches) == 1) {


            //FORMAT THE TEXT FOR SEARCH
            $like_query = str_replace("\\", "\\\\", trim($matches[2]));
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = preg_quote(trim($matches[2]));
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            //CHECK WHETHER TEXT CONTAINS UTF-8
            $translation = utf8_deaccent($matches[2]);

            if ($translation != $matches[2]) {
                $like_query_translated = $dbHandle->quote("%$translation%");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "(full_text LIKE $like_query ESCAPE '\' OR full_text LIKE $like_query_translated ESCAPE '\')";
                $regexp_sql = "(regexp_match(full_text, '$regexp_query', $case2) OR regexp_match(full_text, '$regexp_query_translated', $case2))";
            } else {
                $like_sql = "full_text LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(full_text, '$regexp_query', $case2)";
            }

            $final = $like_sql;
            if ($whole_words == 1)
                $final = "(($final) AND ($regexp_sql))";

            $input = str_replace($matches[0], $matches[1] . " $final " . $matches[3], $input);
        } else {
            $input = '';
            break;
        }
    }

    $search_string = str_ireplace(' NOT ', ' AND NOT ', $input);
    
    $fulltext_query = "SELECT fileID FROM fulltextdatabase.full_text WHERE $search_string";

    $sql = "$in $rating_search $type_search $category_search id IN ($fulltext_query)";

//NOTES
} elseif (isset($_GET['searchtype']) && $_GET['searchtype'] == 'notes') {

    $input = $_GET['search-notes'];

    //SPLIT SEARCH INTO INDIVIDUAL TERMS
    $search_array = preg_split('/ AND | OR | NOT /', $input);

    //CREATE A QUERY FOR EACH TERM
    foreach ($search_array as $search) {

        //SPLIT THE TERM INTO AN OPTIONAL PARETHESES, LAZY TEXT, AND PARETHESES
        if (preg_match('/([\s\(]*)([^\(\)]*)?([\s\)]*)/i', $search, $matches) == 1) {

            //FORMAT THE TEXT FOR SEARCH
            $like_query = str_replace("\\", "\\\\", trim($matches[2]));
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = preg_quote(trim($matches[2]));
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $like_sql = "search_strip_tags(notes) LIKE $like_query ESCAPE '\'";
            $regexp_sql = "regexp_match(search_strip_tags(notes), '$regexp_query', $case2)";

            $final = $like_sql;
            if ($whole_words == 1)
                $final = "(($final) AND ($regexp_sql))";

            $input = str_replace($matches[0], $matches[1] . " $final " . $matches[3], $input);
        } else {
            $input = '';
            break;
        }
    }

    $search_string = str_ireplace(' NOT ', ' AND NOT ', $input);
    
    $notes_in = str_replace("id IN", "fileID IN", $in);
    $notes_category_search = str_replace("id IN", "fileID IN", $category_search);

    $dbHandle->sqliteCreateFunction('search_strip_tags', 'sqlite_strip_tags', 1);
    
    $notes_query = "SELECT fileID FROM notes WHERE $notes_in userID=" . intval($_SESSION['user_id']) . " AND $notes_category_search $search_string";

    $sql = "$rating_search $type_search id IN ($notes_query)";

    //PDF NOTES
} elseif (isset($_GET['searchtype']) && $_GET['searchtype'] == 'pdfnotes') {

    $input = $_GET['search-pdfnotes'];

    //SPLIT SEARCH INTO INDIVIDUAL TERMS
    $search_array = preg_split('/ AND | OR | NOT /', $input);

    //CREATE A QUERY FOR EACH TERM
    foreach ($search_array as $search) {

        //SPLIT THE TERM INTO AN OPTIONAL PARETHESES, LAZY TEXT, AND PARETHESES
        if (preg_match('/([\s\(]*)([^\(\)]*)?([\s\)]*)/i', $search, $matches) == 1) {

            //FORMAT THE TEXT FOR SEARCH
            $like_query = str_replace("\\", "\\\\", trim($matches[2]));
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes(trim($matches[2]), "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $like_sql = "annotation LIKE $like_query ESCAPE '\'";
            $regexp_sql = "regexp_match(annotation, '$regexp_query', $case2)";

            $final = $like_sql;
            if ($whole_words == 1)
                $final = "(($final) AND ($regexp_sql))";

            $input = str_replace($matches[0], $matches[1] . " $final " . $matches[3], $input);
        } else {
            $input = '';
            break;
        }
    }

    $search_string = str_ireplace(' NOT ', ' AND NOT ', $input);
    
    $pdfnotes_query = "SELECT filename FROM annotations WHERE userID=" . intval($_SESSION['user_id']) . " AND $search_string";

    $sql = "$in $rating_search $type_search $category_search file IN ($pdfnotes_query)";

    //EXPERT SEARCH SPECIFIC END
} else {
    ?>
    <form action="search.php" method="GET" id="expertsearchform">
        <input type="hidden" name="searchtype" value="metadata">
        <input type="hidden" name="searchmode" value="expert">
        <table style="width:100%">
            <tr>
                <td style="width:50%">
                    <div id="expertsearchtabs">
                    <input type="radio" id="tab-search-ref" name="radio" checked>
                    <label for="tab-search-ref">References</label>
                    <input type="radio" id="tab-search-pdf" name="radio">
                    <label for="tab-search-pdf">PDFs</label>
                    <input type="radio" id="tab-search-pdfnotes" name="radio">
                    <label for="tab-search-pdfnotes">PDF Notes</label>
                    <input type="radio" id="tab-search-notes" name="radio">
                    <label for="tab-search-notes">Rich-Text Notes</label>
                  </div>
                </td>
                <td style="width:50%" colspan=2>
                    <table style="margin-left:1px;margin-right:auto;width:100%">
                        <tr>
                            <td>
                                <table>
                                    <tr>
                                        <td class="select_span">
                                            <input type="radio" name="include-categories" value="1" style="display:none"
                                            <?php
                                            if (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 1)
                                                print ' checked'
                                                ?>>
                                            &nbsp;<i class="fa fa-circle<?php print (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 1) ? '' : '-o'  ?>">
                                            </i> Include&nbsp;
                                        </td>
                                        <td class="select_span">
                                            <input type="radio" name="include-categories" value="2" style="display:none"
                                            <?php
                                            if (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 2)
                                                print ' checked'
                                                ?>>
                                            &nbsp;<i class="fa fa-circle<?php print (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 2) ? '' : '-o'  ?>">
                                            </i> Exclude categories:&nbsp;
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="text-align:right">
                                <input type="text" id="expert-filter" value="" placeholder="Filter" style="margin-left:auto;margin-right:2px">
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table cellspacing=0 class="threed" style="width:100%">
            <tr>
                <td class="threed" style="width:50%">
                    <textarea name="search-metadata" rows=10 cols=75 placeholder=" Search metadata..."
                              style="resize:none;width:99%;height:249px"><?php print isset($_SESSION['session_search-metadata']) ? htmlspecialchars($_SESSION['session_search-metadata']) : ''; ?></textarea>
                    <textarea name="search-pdfs" rows=10 cols=75 placeholder=" Search PDF fulltext..."
                              style="resize:none;width:99%;height:249px;display:none"><?php print isset($_SESSION['session_search-pdfs']) ? htmlspecialchars($_SESSION['session_search-pdfs']) : ''; ?></textarea>
                    <textarea name="search-pdfnotes" rows=10 cols=75 placeholder=" Search PDF notes..."
                              style="resize:none;width:99%;height:249px;display:none"><?php print isset($_SESSION['session_search-pdfnotes']) ? htmlspecialchars($_SESSION['session_search-pdfnotes']) : ''; ?></textarea>
                    <textarea name="search-notes" rows=10 cols=75 placeholder=" Search rich-text notes..."
                              style="resize:none;width:99%;height:249px;display:none"><?php print isset($_SESSION['session_search-notes']) ? htmlspecialchars($_SESSION['session_search-notes']) : ''; ?></textarea>
                </td>
                <td class="threed" style="width:50%" colspan=2>
                    <div style="height:250px;overflow:auto;border: 1px solid #C5C6C9;background-color:#FFF">
                        <table cellspacing=0 style="width: 50%;float: left;padding:2px">
                            <tr>
                                <td class="select_span">
                                    <input type="checkbox" name="category[]" value="NA" style="display:none">&nbsp;<i class="fa fa-square-o"></i> !unassigned
                                </td>
                            </tr>
                            <?php
                            include_once 'functions.php';
                            database_connect(IL_DATABASE_PATH, 'library');
                            $category_string = null;

                            $result3 = $dbHandle->query("SELECT count(*) FROM categories");
                            $totalcount = $result3->fetchColumn();
                            $result3 = null;

                            $i = 1;
                            $isdiv = null;
                            $result3 = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
                            while ($category = $result3->fetch(PDO::FETCH_ASSOC)) {
                                $cat_all[$category['categoryID']] = $category['category'];
                                if ($i > 1 && $i > ($totalcount / 2) && !$isdiv) {
                                    print '</table><table cellspacing=0 style="width: 50%;padding:2px">';
                                    $isdiv = true;
                                }
                                print PHP_EOL . '<tr><td class="select_span">';
                                print "<input type=\"checkbox\" name=\"category[]\" value=\"" . htmlspecialchars($category['categoryID']) . "\"";
                                print " style=\"display:none\">&nbsp;<i class=\"fa fa-square-o\"></i> " . htmlspecialchars($category['category']) . "</td></tr>";
                                $i = $i + 1;
                            }
                            $result3 = null;
                            ?>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="threed" rowspan="5">
                    <button style="width:52px;margin-left:4px">AND</button>
                    <button style="width:52px">OR</button>
                    <button style="width:52px">NOT</button>
                    <button>(</button> <button>)</button>
                    <div class="metadata-buttons" style="margin-top:5px">
                        <button title="author or editor" style="width:52px;margin-left:4px">[AU]</button>
                        <button title="title" style="width:52px">[TI]</button>
                        <button title="title or abstract" style="width:52px">[AB]</button>
                        <button title="journal abbreviation" style="width:52px">[JO]</button>
                        <button title="secondary title" style="width:52px">[T2]</button>
                        <button title="tertiary title" style="width:52px">[T3]</button>
                        <button title="affiliation" style="width:52px">[AF]</button>
                    </div>
                    <div class="metadata-buttons" style="margin-top:5px">
                        <button title="keyword" style="width:52px;margin-left:4px">[KW]</button>
                        <button title="publication year" style="width:52px">[YR]</button>
                        <button title="<?php print (!empty($_SESSION['custom1'])) ? $_SESSION['custom1'] : 'Custom 1' ?>" style="width:52px">[C1]</button>
                        <button title="<?php print (!empty($_SESSION['custom2'])) ? $_SESSION['custom2'] : 'Custom 2' ?>" style="width:52px">[C2]</button>
                        <button title="<?php print (!empty($_SESSION['custom3'])) ? $_SESSION['custom3'] : 'Custom 3' ?>" style="width:52px">[C3]</button>
                        <button title="<?php print (!empty($_SESSION['custom4'])) ? $_SESSION['custom4'] : 'Custom 4' ?>" style="width:52px">[C4]</button>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="threed">
                    Type:
                </td>
                <td class="threed">
                    <select name="reference_type">
                        <option></option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'article') ? 'selected' : ''  ?>>article</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'book') ? 'selected' : ''  ?>>book</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'chapter') ? 'selected' : ''  ?>>chapter</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'conference') ? 'selected' : ''  ?>>conference</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'manual') ? 'selected' : ''  ?>>manual</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'thesis') ? 'selected' : ''  ?>>thesis</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'patent') ? 'selected' : ''  ?>>patent</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'report') ? 'selected' : ''  ?>>report</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'electronic') ? 'selected' : ''  ?>>electronic</option>
                        <option <?php print (!empty($_SESSION['session_reference_type']) && $_SESSION['session_reference_type'] == 'unpublished') ? 'selected' : ''  ?>>unpublished</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="threed" style="width:6em">
                    Match:
                </td>
                <td class="threed">
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="whole_words" value="1" style="display:none" <?php
                                if (isset($_SESSION['session_whole_words']) && $_SESSION['session_whole_words'] == 1)
                                    print ' checked'
                                    ?>>
                                &nbsp;<i class="fa fa-<?php print (isset($_SESSION['session_whole_words']) && $_SESSION['session_whole_words'] == 1) ? 'check-square' : 'square-o'  ?>">
                                </i> Whole words only&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="checkbox" name="case" value="1" style="display:none" <?php print (isset($_SESSION['session_case']) && $_SESSION['session_case'] == 1) ? 'checked' : ''  ?>>
                                &nbsp;<i class="fa fa-<?php print (isset($_SESSION['session_case']) && $_SESSION['session_case'] == 1) ? 'check-square' : 'square-o'  ?>">
                                </i> Match case&nbsp;
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="threed">
                    Rating:
                </td>
                <td class="threed">
                    <table>
                        <tr>
                            <td class="select_span">
                                <input type="checkbox" name="rating[]" value="1" style="display:none" <?php
                                if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(1, $_SESSION['session_rating'])))
                                    print ' checked'
                                    ?>>
                                &nbsp;<i class="fa fa-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(1, $_SESSION['session_rating']))) ? 'check-square' : 'square-o'  ?>">
                                </i> Low&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="checkbox" name="rating[]" value="2" style="display:none" <?php
                                if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(2, $_SESSION['session_rating'])))
                                    print ' checked'
                                    ?>>
                                &nbsp;<i class="fa fa-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(2, $_SESSION['session_rating']))) ? 'check-square' : 'square-o'  ?>">
                                </i> Medium&nbsp;
                            </td>
                            <td class="select_span">
                                <input type="checkbox" name="rating[]" value="3" style="display:none" <?php
                                if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(3, $_SESSION['session_rating'])))
                                    print ' checked'
                                    ?>>
                                &nbsp;<i class="fa fa-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(3, $_SESSION['session_rating']))) ? 'check-square' : 'square-o'  ?>">
                                </i> High&nbsp;
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="threed">
                    Save as:
                </td>
                <td class="threed">
                    <input type="text" name="searchname" style="width:99.5%" <?php if (!isset($_SESSION['auth'])) echo 'disabled' ?>>
                </td>
            </tr>
        </table>
        <input type="submit" style="position:absolute;left:-999px;top:0;height:1px">
    </form>
    <?php
}
?>
