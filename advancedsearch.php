<?php
if (isset($_GET['searchtype']) && ($_GET['searchtype'] == 'metadata' || $_GET['searchtype'] == 'global')) {

    if (!empty($_GET['anywhere'])) {

        $anywhere_array = array($_GET['anywhere']);
        if ($_GET['anywhere_separator'] == 'AND' || $_GET['anywhere_separator'] == 'OR')
            $anywhere_array = explode(' ', $_GET['anywhere']);

        while ($anywhere = each($anywhere_array)) {

            $like_query = str_replace("\\", "\\\\", $anywhere[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($anywhere[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $author_like_query = $dbHandle->quote("%L:\"$like_query%");
            $like_query = $dbHandle->quote("%$like_query%");
            $author_regexp_query = str_replace("'", "''", "L:\"$regexp_query");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {
                $author_like_query_translated = utf8_deaccent($author_like_query);
                $like_query_translated = $translation;
                $author_regexp_query_translated = utf8_deaccent($author_regexp_query);
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "authors LIKE $author_like_query ESCAPE '\' OR"
                        . " editor LIKE $author_like_query ESCAPE '\' OR"
                        . " journal LIKE $like_query ESCAPE '\' OR"
                        . " secondary_title LIKE $like_query ESCAPE '\' OR"
                        . " tertiary_title LIKE $like_query ESCAPE '\' OR"
                        . " affiliation LIKE $like_query ESCAPE '\' OR"
                        . " title LIKE $like_query ESCAPE '\' OR"
                        . " abstract LIKE $like_query ESCAPE '\' OR"
                        . " year LIKE $like_query ESCAPE '\' OR"
                        . " id=" . intval($anywhere[1]) . " OR"
                        . " bibtex LIKE " . $like_query . " ESCAPE '\' OR"
                        . " keywords LIKE $like_query ESCAPE '\' OR"
                        . " authors_ascii LIKE $author_like_query_translated ESCAPE '\' OR"
                        . " title_ascii LIKE $like_query_translated ESCAPE '\' OR"
                        . " abstract_ascii LIKE $like_query_translated ESCAPE '\'";

                $regexp_sql = "regexp_match(authors, '$author_regexp_query', $case2) OR"
                        . " regexp_match(editor, '$author_regexp_query', $case2) OR"
                        . " regexp_match(journal, '$regexp_query', $case2) OR"
                        . " regexp_match(secondary_title, '$regexp_query', $case2) OR"
                        . " regexp_match(tertiary_title, '$regexp_query', $case2) OR"
                        . " regexp_match(affiliation, '$regexp_query', $case2) OR"
                        . " regexp_match(title, '$regexp_query', $case2) OR"
                        . " regexp_match(abstract, '$regexp_query', $case2) OR"
                        . " regexp_match(year, '$regexp_query', $case2) OR"
                        . " regexp_match(bibtex, '$regexp_query', $case2) OR"
                        . " regexp_match(keywords, '$regexp_query', $case2) OR"
                        . " regexp_match(authors_ascii, '$author_regexp_query_translated', $case2) OR"
                        . " regexp_match(title_ascii, '$regexp_query_translated', $case2) OR"
                        . " regexp_match(abstract_ascii, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "authors_ascii LIKE $author_like_query ESCAPE '\' OR"
                        . " editor LIKE $author_like_query ESCAPE '\' OR"
                        . " journal LIKE $like_query ESCAPE '\' OR"
                        . " secondary_title LIKE $like_query ESCAPE '\' OR"
                        . " tertiary_title LIKE $like_query ESCAPE '\' OR"
                        . " affiliation LIKE $like_query ESCAPE '\' OR"
                        . " title_ascii LIKE $like_query ESCAPE '\' OR"
                        . " abstract_ascii LIKE $like_query ESCAPE '\' OR"
                        . " year LIKE $like_query ESCAPE '\' OR"
                        . " id=" . intval($anywhere[1]) . " OR"
                        . " bibtex LIKE " . $like_query . " ESCAPE '\' OR"
                        . " keywords LIKE $like_query ESCAPE '\'";

                $regexp_sql = "regexp_match(authors_ascii, '$author_regexp_query', $case2) OR"
                        . " regexp_match(editor, '$author_regexp_query', $case2) OR"
                        . " regexp_match(journal, '$regexp_query', $case2) OR"
                        . " regexp_match(secondary_title, '$regexp_query', $case2) OR"
                        . " regexp_match(tertiary_title, '$regexp_query', $case2) OR"
                        . " regexp_match(affiliation, '$regexp_query', $case2) OR"
                        . " regexp_match(title_ascii, '$regexp_query', $case2) OR"
                        . " regexp_match(abstract_ascii, '$regexp_query', $case2) OR"
                        . " regexp_match(year, '$regexp_query', $case2) OR"
                        . " regexp_match(bibtex, '$regexp_query', $case2) OR"
                        . " regexp_match(keywords, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $anywhere_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $anywhere_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['anywhere_separator'] == 'AND')
            $search_string[] = join(' AND ', $anywhere_regexp);
        if ($_GET['anywhere_separator'] == 'OR')
            $search_string[] = join(' OR ', $anywhere_regexp);
        if ($_GET['anywhere_separator'] == 'PHRASE')
            $search_string[] = join('', $anywhere_regexp);
    }

    #######################################################################

    if (!empty($_GET['authors'])) {

        $authors_array = array($_GET['authors']);
        if ($_GET['authors_separator'] == 'AND' || $_GET['authors_separator'] == 'OR')
            $authors_array = explode(' ', $_GET['authors']);

        while ($authors = each($authors_array)) {

            $like_query = str_replace("\\", "\\\\", $authors[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($authors[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $filter_arr_like = array();
            $filter_arr_regexp = array();
            if (strstr($like_query, ',') !== 0) {
                $filter_arr_like = explode(',', $like_query);
                $filter_arr_regexp = explode(',', $regexp_query);
            }
            if (!empty($filter_arr_like[1])) {
                $like_query = trim($filter_arr_like[0]) . '",F:"' . trim($filter_arr_like[1]);
            }
            if (!empty($filter_arr_regexp[1])) {
                $regexp_query = trim($filter_arr_regexp[0]) . '",F:"' . trim($filter_arr_regexp[1]);
            }

            $like_query = $dbHandle->quote('%L:"' . $like_query . '%');
            $regexp_query = 'L:"' . str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

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

            if ($whole_words == 1) {
                $authors_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $authors_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['authors_separator'] == 'AND')
            $search_string[] = join(' AND ', $authors_regexp);
        if ($_GET['authors_separator'] == 'OR')
            $search_string[] = join(' OR ', $authors_regexp);
        if ($_GET['authors_separator'] == 'PHRASE')
            $search_string[] = join('', $authors_regexp);
    }

    #######################################################################

    if (!empty($_GET['journal'])) {

        $journal_array = array($_GET['journal']);
        if ($_GET['journal_separator'] == 'AND' || $_GET['journal_separator'] == 'OR')
            $journal_array = explode(' ', $_GET['journal']);

        while ($journal = each($journal_array)) {

            $like_query = str_replace("\\", "\\\\", $journal[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($journal[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "journal LIKE $like_query ESCAPE '\' OR journal LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(journal, '$regexp_query', $case2) OR regexp_match(journal, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "journal LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(journal, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $journal_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $journal_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['journal_separator'] == 'AND')
            $search_string[] = join(' AND ', $journal_regexp);
        if ($_GET['journal_separator'] == 'OR')
            $search_string[] = join(' OR ', $journal_regexp);
        if ($_GET['journal_separator'] == 'PHRASE')
            $search_string[] = join('', $journal_regexp);
    }

    #######################################################################

    if (!empty($_GET['secondary_title'])) {

        $secondary_title_array = array($_GET['secondary_title']);
        if ($_GET['secondary_title_separator'] == 'AND' || $_GET['secondary_title_separator'] == 'OR')
            $secondary_title_array = explode(' ', $_GET['secondary_title']);

        while ($secondary_title = each($secondary_title_array)) {

            $like_query = str_replace("\\", "\\\\", $secondary_title[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($secondary_title[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "secondary_title LIKE $like_query ESCAPE '\' OR secondary_title LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(secondary_title, '$regexp_query', $case2) OR regexp_match(secondary_title, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "secondary_title LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(secondary_title, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $secondary_title_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $secondary_title_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['secondary_title_separator'] == 'AND')
            $search_string[] = join(' AND ', $secondary_title_regexp);
        if ($_GET['secondary_title_separator'] == 'OR')
            $search_string[] = join(' OR ', $secondary_title_regexp);
        if ($_GET['secondary_title_separator'] == 'PHRASE')
            $search_string[] = join('', $secondary_title_regexp);
    }

    #######################################################################

    if (!empty($_GET['tertiary_title'])) {

        $tertiary_title_array = array($_GET['tertiary_title']);
        if ($_GET['tertiary_title_separator'] == 'AND' || $_GET['tertiary_title_separator'] == 'OR')
            $tertiary_title_array = explode(' ', $_GET['tertiary_title']);

        while ($tertiary_title = each($tertiary_title_array)) {

            $like_query = str_replace("\\", "\\\\", $tertiary_title[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($tertiary_title[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "tertiary_title LIKE $like_query ESCAPE '\' OR tertiary_title LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(tertiary_title, '$regexp_query', $case2) OR regexp_match(tertiary_title, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "tertiary_title LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(tertiary_title, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $tertiary_title_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $tertiary_title_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['tertiary_title_separator'] == 'AND')
            $search_string[] = join(' AND ', $tertiary_title_regexp);
        if ($_GET['tertiary_title_separator'] == 'OR')
            $search_string[] = join(' OR ', $tertiary_title_regexp);
        if ($_GET['tertiary_title_separator'] == 'PHRASE')
            $search_string[] = join('', $tertiary_title_regexp);
    }

    #######################################################################

    if (!empty($_GET['affiliation'])) {

        $affiliation_array = array($_GET['affiliation']);
        if ($_GET['affiliation_separator'] == 'AND' || $_GET['affiliation_separator'] == 'OR')
            $affiliation_array = explode(' ', $_GET['affiliation']);

        while ($affiliation = each($affiliation_array)) {

            $like_query = str_replace("\\", "\\\\", $affiliation[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($affiliation[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "affiliation LIKE $like_query ESCAPE '\' OR affiliation LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(affiliation, '$regexp_query', $case2) OR regexp_match(affiliation, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "affiliation LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(affiliation, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $affiliation_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $affiliation_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['affiliation_separator'] == 'AND')
            $search_string[] = join(' AND ', $affiliation_regexp);
        if ($_GET['affiliation_separator'] == 'OR')
            $search_string[] = join(' OR ', $affiliation_regexp);
        if ($_GET['affiliation_separator'] == 'PHRASE')
            $search_string[] = join('', $affiliation_regexp);
    }

    #######################################################################

    if (!empty($_GET['custom1'])) {

        $custom1_array = array($_GET['custom1']);
        if ($_GET['custom1_separator'] == 'AND' || $_GET['custom1_separator'] == 'OR')
            $custom1_array = explode(' ', $_GET['custom1']);

        while ($custom1 = each($custom1_array)) {

            $like_query = str_replace("\\", "\\\\", $custom1[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($custom1[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "custom1 LIKE $like_query ESCAPE '\' OR custom1 LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(custom1, '$regexp_query', $case2) OR regexp_match(custom1, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "custom1 LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(custom1, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $custom1_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $custom1_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['custom1_separator'] == 'AND')
            $search_string[] = join(' AND ', $custom1_regexp);
        if ($_GET['custom1_separator'] == 'OR')
            $search_string[] = join(' OR ', $custom1_regexp);
        if ($_GET['custom1_separator'] == 'PHRASE')
            $search_string[] = join('', $custom1_regexp);
    }

    #######################################################################

    if (!empty($_GET['custom2'])) {

        $custom2_array = array($_GET['custom2']);
        if ($_GET['custom2_separator'] == 'AND' || $_GET['custom2_separator'] == 'OR')
            $custom2_array = explode(' ', $_GET['custom2']);

        while ($custom2 = each($custom2_array)) {

            $like_query = str_replace("\\", "\\\\", $custom2[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($custom2[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "custom2 LIKE $like_query ESCAPE '\' OR custom2 LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(custom2, '$regexp_query', $case2) OR regexp_match(custom2, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "custom2 LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(custom2, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $custom2_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $custom2_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['custom2_separator'] == 'AND')
            $search_string[] = join(' AND ', $custom2_regexp);
        if ($_GET['custom2_separator'] == 'OR')
            $search_string[] = join(' OR ', $custom2_regexp);
        if ($_GET['custom2_separator'] == 'PHRASE')
            $search_string[] = join('', $custom2_regexp);
    }

    #######################################################################

    if (!empty($_GET['custom3'])) {

        $custom3_array = array($_GET['custom3']);
        if ($_GET['custom3_separator'] == 'AND' || $_GET['custom3_separator'] == 'OR')
            $custom3_array = explode(' ', $_GET['custom3']);

        while ($custom3 = each($custom3_array)) {

            $like_query = str_replace("\\", "\\\\", $custom3[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($custom3[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "custom3 LIKE $like_query ESCAPE '\' OR custom3 LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(custom3, '$regexp_query', $case2) OR regexp_match(custom3, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "custom3 LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(custom3, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $custom3_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $custom3_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['custom3_separator'] == 'AND')
            $search_string[] = join(' AND ', $custom3_regexp);
        if ($_GET['custom3_separator'] == 'OR')
            $search_string[] = join(' OR ', $custom3_regexp);
        if ($_GET['custom3_separator'] == 'PHRASE')
            $search_string[] = join('', $custom3_regexp);
    }

    #######################################################################

    if (!empty($_GET['custom4'])) {

        $custom4_array = array($_GET['custom4']);
        if ($_GET['custom4_separator'] == 'AND' || $_GET['custom4_separator'] == 'OR')
            $custom4_array = explode(' ', $_GET['custom4']);

        while ($custom4 = each($custom4_array)) {

            $like_query = str_replace("\\", "\\\\", $custom4[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($custom4[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "custom4 LIKE $like_query ESCAPE '\' OR custom4 LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(custom4, '$regexp_query', $case2) OR regexp_match(custom4, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "custom4 LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(custom4, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $custom4_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $custom4_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['custom4_separator'] == 'AND')
            $search_string[] = join(' AND ', $custom4_regexp);
        if ($_GET['custom4_separator'] == 'OR')
            $search_string[] = join(' OR ', $custom4_regexp);
        if ($_GET['custom4_separator'] == 'PHRASE')
            $search_string[] = join('', $custom4_regexp);
    }

    #######################################################################

    if (!empty($_GET['title'])) {

        $title_array = array($_GET['title']);
        if ($_GET['title_separator'] == 'AND' || $_GET['title_separator'] == 'OR')
            $title_array = explode(' ', $_GET['title']);

        while ($title = each($title_array)) {

            $like_query = str_replace("\\", "\\\\", $title[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($title[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "title LIKE $like_query ESCAPE '\' OR title_ascii LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(title, '$regexp_query', $case2) OR regexp_match(title_ascii, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "title_ascii LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(title_ascii, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $title_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $title_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['title_separator'] == 'AND')
            $search_string[] = join(' AND ', $title_regexp);
        if ($_GET['title_separator'] == 'OR')
            $search_string[] = join(' OR ', $title_regexp);
        if ($_GET['title_separator'] == 'PHRASE')
            $search_string[] = join('', $title_regexp);
    }


    #######################################################################

    if (!empty($_GET['keywords'])) {

        $keywords_array = array($_GET['keywords']);
        if ($_GET['keywords_separator'] == 'AND' || $_GET['keywords_separator'] == 'OR')
            $keywords_array = explode(' ', $_GET['keywords']);

        while ($keywords = each($keywords_array)) {

            $like_query = str_replace("\\", "\\\\", $keywords[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($keywords[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "keywords LIKE $like_query ESCAPE '\' OR keywords LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(keywords, '$regexp_query', $case2) OR regexp_match(keywords, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "keywords LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(keywords, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $keywords_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $keywords_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['keywords_separator'] == 'AND')
            $search_string[] = join(' AND ', $keywords_regexp);
        if ($_GET['keywords_separator'] == 'OR')
            $search_string[] = join(' OR ', $keywords_regexp);
        if ($_GET['keywords_separator'] == 'PHRASE')
            $search_string[] = join('', $keywords_regexp);
    }


    #######################################################################

    if (!empty($_GET['abstract'])) {

        $abstract_array = array($_GET['abstract']);
        if ($_GET['abstract_separator'] == 'AND' || $_GET['abstract_separator'] == 'OR')
            $abstract_array = explode(' ', $_GET['abstract']);

        while ($abstract = each($abstract_array)) {

            $like_query = str_replace("\\", "\\\\", $abstract[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($abstract[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "title LIKE $like_query ESCAPE '\' OR abstract LIKE $like_query ESCAPE '\' OR
						title_ascii LIKE $like_query_translated ESCAPE '\' OR abstract_ascii LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(title, '$regexp_query', $case2) OR regexp_match(abstract, '$regexp_query', $case2) OR
						regexp_match(title_ascii, '$regexp_query_translated', $case2) OR regexp_match(abstract_ascii, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "title_ascii LIKE $like_query ESCAPE '\' OR abstract_ascii LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(title_ascii, '$regexp_query', $case2) OR regexp_match(abstract_ascii, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $abstract_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $abstract_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['abstract_separator'] == 'AND')
            $search_string[] = join(' AND ', $abstract_regexp);
        if ($_GET['abstract_separator'] == 'OR')
            $search_string[] = join(' OR ', $abstract_regexp);
        if ($_GET['abstract_separator'] == 'PHRASE')
            $search_string[] = join('', $abstract_regexp);
    }

    #######################################################################

    if (!empty($_GET['year'])) {

        $year_array = explode(' ', $_GET['year']);

        while ($year = each($year_array)) {
            $year_regexp[] = "(year=" . intval($year[1]) . " OR strftime('%Y', year) LIKE '" . intval($year[1]) . "')";
        }

        $search_string[] = join(' OR ', $year_regexp);
    }

    #######################################################################

    if (!empty($_GET['search_id'])) {

        $search_id_array = explode(' ', $_GET['search_id']);

        while ($search_id = each($search_id_array)) {

            $like_query = str_replace("\\", "\\\\", $search_id[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($search_id[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($like_query);

            if ($translation != $like_query) {

                $like_query_translated = $translation;
                $regexp_query_translated = utf8_deaccent($regexp_query);

                $like_sql = "id=" . intval($search_id[1]) . " OR bibtex LIKE " . $like_query . " ESCAPE '\'";
                $regexp_sql = "id=" . intval($search_id[1]) . " OR regexp_match(bibtex, '$regexp_query', $case2)";

            } else {

                $like_sql = "id=" . intval($search_id[1]) . " OR bibtex LIKE " . $like_query . " ESCAPE '\'";
                $regexp_sql = "id=" . intval($search_id[1]) . " OR regexp_match(bibtex, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $search_id_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $search_id_regexp[] = '(' . $like_sql . ')';
            }
        }

        $search_string[] = join(' OR ', $search_id_regexp);
    }

    #######################################################################

    if (count($search_string) > 1) {
        $search_string = join(') AND (', $search_string);
    } elseif (count($search_string) == 1) {
        $search_string = join('', $search_string);
    }
    $search_string = '(' . $search_string . ')';

    $sql = "$in $rating_search $type_search $category_search $search_string";

    $global_strings[] = $sql;
}

##########################notes#####################################
if (!empty($_GET['notes']) && ($_GET['searchtype'] == 'notes' || $_GET['searchtype'] == 'global')) {

    $notes_array = array($_GET['notes']);
    if ($_GET['notes_separator'] == 'AND' || $_GET['notes_separator'] == 'OR')
        $notes_array = explode(' ', $_GET['notes']);


    while ($notes = each($notes_array)) {

        $like_query = str_replace("\\", "\\\\", $notes[1]);
        $like_query = str_replace("%", "\%", $like_query);
        $like_query = str_replace("_", "\_", $like_query);
        $like_query = str_replace("<*>", "%", $like_query);
        $like_query = str_replace("<?>", "_", $like_query);

        $regexp_query = addcslashes($notes[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
        $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
        $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

        $like_query = $dbHandle->quote("%$like_query%");
        $regexp_query = str_replace("'", "''", $regexp_query);

        $like_sql = "search_strip_tags(notes) LIKE $like_query ESCAPE '\'";
        $regexp_sql = "regexp_match(search_strip_tags(notes), '$regexp_query', $case2)";

        if ($whole_words == 1) {
            $notes_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
        } else {
            $notes_regexp[] = '(' . $like_sql . ')';
        }
    }

    if ($_GET['notes_separator'] == 'AND')
        $search_string = join(' AND ', $notes_regexp);
    if ($_GET['notes_separator'] == 'OR')
        $search_string = join(' OR ', $notes_regexp);
    if ($_GET['notes_separator'] == 'PHRASE')
        $search_string = join('', $notes_regexp);

    $notes_in = str_replace("id IN", "fileID IN", $in);
    $notes_category_search = str_replace("id IN", "fileID IN", $category_search);

    $dbHandle->sqliteCreateFunction('search_strip_tags', 'sqlite_strip_tags', 1);

    $notes_query = "SELECT fileID FROM notes WHERE $notes_in userID=" . intval($_SESSION['user_id']) . " AND $notes_category_search $search_string";

    $sql = "$rating_search $type_search id IN ($notes_query)";

    $global_strings[] = $sql;
}

##########################PDF notes#####################################
if (!empty($_GET['pdfnotes']) && ($_GET['searchtype'] == 'pdfnotes' || $_GET['searchtype'] == 'global')) {

    $pdfnotes_array = array($_GET['pdfnotes']);
    if ($_GET['pdfnotes_separator'] == 'AND' || $_GET['pdfnotes_separator'] == 'OR')
        $pdfnotes_array = explode(' ', $_GET['pdfnotes']);

    while ($pdfnotes = each($pdfnotes_array)) {

        $like_query = str_replace("\\", "\\\\", $pdfnotes[1]);
        $like_query = str_replace("%", "\%", $like_query);
        $like_query = str_replace("_", "\_", $like_query);
        $like_query = str_replace("<*>", "%", $like_query);
        $like_query = str_replace("<?>", "_", $like_query);

        $regexp_query = addcslashes($pdfnotes[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
        $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
        $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

        $like_query = $dbHandle->quote("%$like_query%");
        $regexp_query = str_replace("'", "''", $regexp_query);

        $like_sql = "annotation LIKE $like_query ESCAPE '\'";
        $regexp_sql = "regexp_match(annotation, '$regexp_query', $case2)";

        if ($whole_words == 1) {
            $pdfnotes_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
        } else {
            $pdfnotes_regexp[] = '(' . $like_sql . ')';
        }
    }

    if ($_GET['pdfnotes_separator'] == 'AND')
        $search_string = join(' AND ', $pdfnotes_regexp);
    if ($_GET['pdfnotes_separator'] == 'OR')
        $search_string = join(' OR ', $pdfnotes_regexp);
    if ($_GET['pdfnotes_separator'] == 'PHRASE')
        $search_string = join('', $pdfnotes_regexp);

    $pdfnotes_query = "SELECT filename FROM annotations WHERE userID=" . intval($_SESSION['user_id']) . " AND $search_string";

    $sql = "$in $rating_search $type_search $category_search file IN ($pdfnotes_query)";

    $global_strings[] = $sql;
}

##########################fulltext#####################################
if (!empty($_GET['fulltext']) && ($_GET['searchtype'] == 'pdf' || $_GET['searchtype'] == 'global')) {

    $fulltext_array = array($_GET['fulltext']);
    if ($_GET['fulltext_separator'] == 'AND' || $_GET['fulltext_separator'] == 'OR') {
        $fulltext_array = explode(' ', $_GET['fulltext']);
    }

//INDEX
    #if ($_GET['fulltext_separator'] == 'AND') $search_string = join (' ', $fulltext_array);

    while ($fulltext = each($fulltext_array)) {

        $like_query = str_replace("\\", "\\\\", $fulltext[1]);
        $like_query = str_replace("%", "\%", $like_query);
        $like_query = str_replace("_", "\_", $like_query);
        $like_query = str_replace("<*>", "%", $like_query);
        $like_query = str_replace("<?>", "_", $like_query);

        $regexp_query = addcslashes($fulltext[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
        $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
        $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

        $like_query = $dbHandle->quote("%$like_query%");
        $regexp_query = str_replace("'", "''", $regexp_query);

        $translation = utf8_deaccent($like_query);

        if ($translation != $like_query) {

            $like_query_translated = $translation;
            $regexp_query_translated = utf8_deaccent($regexp_query);

            $like_sql = "full_text LIKE $like_query ESCAPE '\' OR full_text LIKE $like_query_translated ESCAPE '\'";
            $regexp_sql = "regexp_match(full_text, '$regexp_query', $case2) OR regexp_match(full_text, '$regexp_query_translated', $case2)";
        } else {

            $like_sql = "full_text LIKE $like_query ESCAPE '\'";
            $regexp_sql = "regexp_match(full_text, '$regexp_query', $case2)";
        }

        if ($whole_words == 1) {
            $fulltext_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
        } else {
            $fulltext_regexp[] = '(' . $like_sql . ')';
        }
    }

    if ($_GET['fulltext_separator'] == 'AND')
        $search_string = join(' AND ', $fulltext_regexp);
    if ($_GET['fulltext_separator'] == 'OR')
        $search_string = join(' OR ', $fulltext_regexp);
    if ($_GET['fulltext_separator'] == 'PHRASE')
        $search_string = join('', $fulltext_regexp);

    $dbHandle->sqliteCreateFunction('regexp_match', 'sqlite_regexp', 3);

    if ($case == 1)
        $dbHandle->exec("PRAGMA case_sensitive_like = 1");

    $fulltext_query = "SELECT fileID FROM fulltextdatabase.full_text WHERE $search_string";

    $sql = "$in $rating_search $type_search $category_search id IN ($fulltext_query)";

    $global_strings[] = $sql;
}

// Global search.
if (!empty($_GET['searchtype']) && $_GET['searchtype'] == 'global' && !empty($global_strings)) {
    $sql = join(") OR (", $global_strings);
    $sql = "($sql)";
}

if (!isset($_GET['searchtype'])) {

    include_once 'data.php';
    include_once 'functions.php';

    function search_row($field) {

        print '

<table style="width:100%">
 <tr>
 <td style="width:60%">
 <input type="text" style="width:99.5%" name="' . $field . '" value="' . (isset($_SESSION['session_' . $field]) ? htmlspecialchars($_SESSION['session_' . $field]) : '') . '">
 </td>
  <td class="select_span" style="width:14%;padding-left:6px">
   <input type="radio" name="' . $field . '_separator" value="AND" style="display:none" ' . ((isset($_SESSION['session_' . $field . '_separator']) && $_SESSION['session_' . $field . '_separator'] == 'AND') ? 'checked' : '') . '>
   <i class="fa fa-circle' . ((isset($_SESSION['session_' . $field . '_separator']) && $_SESSION['session_' . $field . '_separator'] == 'AND') ? '' : '-o') . '"></i>
      AND
  </td>
  <td class="select_span" style="width:11%">
   <input type="radio" name="' . $field . '_separator" value="OR" style="display:none" ' . ((isset($_SESSION['session_' . $field . '_separator']) && $_SESSION['session_' . $field . '_separator'] == 'OR') ? 'checked' : '') . '>
   <i class="fa fa-circle' . ((isset($_SESSION['session_' . $field . '_separator']) && $_SESSION['session_' . $field . '_separator'] == 'OR') ? '' : '-o') . '">
   </i> OR
  </td>
  <td class="select_span" style="width:15%">
   <input type="radio" name="' . $field . '_separator" value="PHRASE" style="display:none" ' . ((!isset($_SESSION['session_' . $field . '_separator']) || $_SESSION['session_' . $field . '_separator'] == 'PHRASE') ? 'checked' : '') . '>
   <i class="fa fa-circle' . ((!isset($_SESSION['session_' . $field . '_separator']) || $_SESSION['session_' . $field . '_separator'] == 'PHRASE') ? '' : '-o') . '">
   </i> phrase
  </td>
 </tr>
</table>';
    }
    ?>
    <form action="search.php" method="GET" id="advancedsearchform">
        <input type="hidden" name="searchtype" value="metadata">
        <input type="hidden" name="searchmode" value="advanced">
        <table style="width:99.5%">
            <tr>
                <td style="width:60%">
                  <div id="advancedsearchtabs">
                    <input type="radio" id="advtab-search-ref" name="radio" checked>
                    <label for="advtab-search-ref">References</label>
                    <input type="radio" id="advtab-search-pdf" name="radio">
                    <label for="advtab-search-pdf">PDFs</label>
                    <input type="radio" id="advtab-search-pdfnotes" name="radio">
                    <label for="advtab-search-pdfnotes">PDF Notes</label>
                    <input type="radio" id="advtab-search-notes" name="radio">
                    <label for="advtab-search-notes">Rich-Text Notes</label>
                  </div>
                </td>
                <td style="width:40%">
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
                            <td style="text-align:right;width:40%">
                                <input type="text" id="advanced-filter" value="" placeholder="Filter" style="margin-left:auto;margin-right:2px;width:100%">
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table class="threed" style="width:60%;float:left">
            <tr class="refrow">
                <td class="threed" style="width:8em">
                    Anywhere:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('anywhere'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Author/Editor:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('authors'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Title:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('title'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Title/Abstract:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('abstract'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Keywords:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('keywords'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Journal:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('journal'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Secondary title:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('secondary_title'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Tertiary title:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('tertiary_title'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Affiliation:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('affiliation'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    <?php print (!empty($_SESSION['custom1'])) ? $_SESSION['custom1'] : 'Custom 1' ?>:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('custom1'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    <?php print (!empty($_SESSION['custom2'])) ? $_SESSION['custom2'] : 'Custom 2' ?>:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('custom2'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    <?php print (!empty($_SESSION['custom3'])) ? $_SESSION['custom3'] : 'Custom 3' ?>:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('custom3'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    <?php print (!empty($_SESSION['custom4'])) ? $_SESSION['custom4'] : 'Custom 4' ?>:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('custom4'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Year:
                </td>
                <td class="threed">
                    <table style="width:100%">
                        <tr>
                            <td style="width:60%">
                                <input type="text" style="width:99.5%" name="year" value="<?php print isset($_SESSION['session_year']) ? htmlspecialchars($_SESSION['session_year']) : ''; ?>">
                            </td>
                            <td style="padding-left:6px">
                                <i class="fa fa-circle"></i> OR
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    ID/Citation Key:
                </td>
                <td class="threed">
                    <table style="width:100%">
                        <tr>
                            <td style="width:60%">
                                <input type="text" style="width:99.5%" name="search_id" value="<?php print isset($_SESSION['session_search_id']) ? htmlspecialchars($_SESSION['session_search_id']) : ''; ?>">
                            </td>
                            <td style="padding-left:6px">
                                <i class="fa fa-circle"></i> OR
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr id="fulltextrow" style="display:none">
                <td class="threed" style="width:8em">
                    Full text:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('fulltext'); ?>
                </td>
            </tr>
            <tr id="pdfnotesrow" style="display:none">
                <td class="threed" style="width:8em">
                    PDF Notes:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('pdfnotes'); ?>
                </td>
            </tr>
            <tr id="notesrow" style="display:none">
                <td class="threed" style="width:8em">
                    Rich-text Notes:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('notes'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <input type="submit" style="position:absolute;left:-999px;top:0;height:1px">
                </td>
            </tr>
        </table>
        <table cellspacing=0 class="threed" style="width:40%">
            <tr>
                <td class="threed" colspan=2>
                    <div style="height:22.3em;overflow:auto;border: 1px solid rgba(0,0,0,0.15);background-color:#FFF">
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
                <td class="threed" style="width:4em">
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
                <td class="threed" style="width:4em">
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
    </form>
    <?php
}
?>