<?php

if (!empty($_GET['anywhere']) && $_GET['searchtype'] == 'metadata') {

    $anywhere_array = array($_GET['anywhere']);
    if ($_GET['anywhere_separator'] == 'AND' || $_GET['anywhere_separator'] == 'OR')
        $anywhere_array = explode(' ', $_GET['anywhere']);

    while ($anywhere = each($anywhere_array)) {

        $like_query_esc = str_replace("\\", "\\\\", $anywhere[1]);
        $like_query_esc = str_replace("%", "\%", $like_query_esc);
        $like_query_esc = str_replace("_", "\_", $like_query_esc);
        $like_query_esc = str_replace("<*>", "%", $like_query_esc);
        $like_query_esc = str_replace("<?>", "_", $like_query_esc);
        
        $author_like_query = $dbHandle->quote("%L:\"$like_query_esc%");
        $like_query = $dbHandle->quote("%$like_query_esc%");
        
        $translation = utf8_deaccent($like_query_esc);

        if ($translation != $like_query_esc) {
            $author_like_query_translated = $dbHandle->quote("%L:\"$translation%");
            $like_query_translated = $dbHandle->quote("%$translation%");
            $like_sql = "authors LIKE $author_like_query ESCAPE '\' OR journal LIKE $like_query ESCAPE '\' OR secondary_title LIKE $like_query ESCAPE '\' OR affiliation LIKE $like_query ESCAPE '\' OR title LIKE $like_query ESCAPE '\' OR abstract LIKE $like_query ESCAPE '\' OR year LIKE $like_query ESCAPE '\' OR id=" . intval($anywhere[1]) . " OR keywords LIKE $like_query ESCAPE '\' OR
						authors_ascii LIKE $author_like_query_translated ESCAPE '\' OR title_ascii LIKE $like_query_translated ESCAPE '\' OR abstract_ascii LIKE $like_query_translated ESCAPE '\'";
        } else {
            $like_sql = "authors_ascii LIKE $author_like_query ESCAPE '\' OR journal LIKE $like_query ESCAPE '\' OR secondary_title LIKE $like_query ESCAPE '\' OR affiliation LIKE $like_query ESCAPE '\' OR title_ascii LIKE $like_query ESCAPE '\' OR abstract_ascii LIKE $like_query ESCAPE '\' OR year LIKE $like_query ESCAPE '\' OR id=" . intval($anywhere[1]) . " OR keywords LIKE $like_query ESCAPE '\'";
        }
        $anywhere_regexp[] = '(' . $like_sql . ')';
    }

    if ($_GET['anywhere_separator'] == 'AND')
        $search_string = join(' AND ', $anywhere_regexp);
    if ($_GET['anywhere_separator'] == 'OR')
        $search_string = join(' OR ', $anywhere_regexp);
    if ($_GET['anywhere_separator'] == 'PHRASE')
        $search_string = join('', $anywhere_regexp);
}
?>