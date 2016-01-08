<?php

include_once 'data.php';
include_once 'functions.php';

if (isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {

    if (!empty($_POST['submit'])) {

        if (!isset($_POST['ids']))
            $_POST['ids'] = array();

        echo '<div class="details alternating_row" style="font-weight:bold">Number of Deleted Duplicates:</div>'
        . '<div style="padding:4px 8px">' . count($_POST['ids']) . '</div>'
        . '<div style="padding:0 8px"><h3>Done.</h3></div>';

        // DELETE ITEMS
        database_connect(IL_DATABASE_PATH, 'library');
        delete_record($dbHandle, $_POST['ids']);
        $dbHandle = null;
    } elseif (isset($_GET['find_duplicates']) && $_GET['find_duplicates'] == 'similar') {

        echo '<div class="details alternating_row" style="font-weight:bold">Possible Duplicates:</div>'
        . '<div style="padding:4px 8px"><input type="checkbox" style="visibility:hidden">'
        . '<span style="display:inline-block;margin-left:1em;width:4em">ID</span><span style="margin-left:1em">Title</span></div>';

        echo '<form action="duplicates.php" method="POST">';

        $duplicates_array = array();

        database_connect(IL_DATABASE_PATH, 'library');

        $dbHandle->exec("PRAGMA cache_size = 200000");
        $dbHandle->exec("PRAGMA temp_store = MEMORY");
        $dbHandle->exec("PRAGMA synchronous = OFF");

        $dbHandle->exec("CREATE TEMPORARY TABLE search_result (id INTEGER PRIMARY KEY,title)");
        $dbHandle->exec("INSERT INTO search_result SELECT id,title FROM library");
        $result = $dbHandle->query("SELECT id,title FROM search_result ORDER BY id ASC");

        $i = 0;

        while ($title = $result->fetch(PDO::FETCH_ASSOC)) {

            $title_query = $dbHandle->quote(substr($title['title'], 0, -1) . '%');
            $id_result = $dbHandle->query("SELECT id,title FROM search_result WHERE id > $title[id] AND lower(title) LIKE lower($title_query) LIMIT 1");

            $id_result = $id_result->fetch(PDO::FETCH_ASSOC);

            if (!empty($id_result['id'])) {
                $duplicates_array[$i][$title['id']] = $title['title'];
                $duplicates_array[$i][$id_result['id']] = $id_result['title'];
                $i = $i + 1;
            }
        }

        $dbHandle = null;

        while (list($key, $duplicate_pair) = each($duplicates_array)) {

            ksort($duplicate_pair);

            $id1 = key($duplicate_pair);
            $title1 = current($duplicate_pair);

            next($duplicate_pair);

            $id2 = key($duplicate_pair);
            $title2 = current($duplicate_pair);

            $duplicate_pair = array();

            echo '<div style="padding:4px 8px"><input type="checkbox" name="ids[]" value="' . $id1 . '">'
            . '<a href="stable.php?id=' . $id1 . '" target="_blank" style="display:inline-block;margin-left:1em;width:4em">'
            . $id1 . '</a><span style="margin-left:1em">' . $title1 . '</span></div>';

            echo '<div style="padding:4px 8px"><input type="checkbox" name="ids[]" value="' . $id2 . '">'
            . '<a href="stable.php?id=' . $id2 . '" target="_blank" style="display:inline-block;margin-left:1em;width:4em">'
            . $id2 . '</a><span style="margin-left:1em">' . $title2 . '</span></div>';
        }

        if (count($duplicates_array) > 0) {
            echo '<div style="padding:8px 8px"><input type="submit" name="submit" value="Delete selected"><br>'
            . '<b>Warning! This action cannot be undone. Inspect the items you want to delete for supplementary files and important notes.</b></div>';
        } else {
            echo '<div style="padding:4px 8px">No duplicates found.</div>';
        }
        echo '</form>';
    } elseif (isset($_GET['find_duplicates']) && $_GET['find_duplicates'] == 'identical') {

        echo '<div class="details alternating_row" style="font-weight:bold">Possible Duplicates:</div>'
        . '<div style="padding:4px 8px"><input type="checkbox" style="visibility:hidden">'
        . '<span style="display:inline-block;margin-left:1em;width:4em">ID</span><span style="margin-left:1em">Title</span></div>';

        echo '<form action="duplicates.php" method="POST">';

        database_connect(IL_DATABASE_PATH, 'library');
        $result = $dbHandle->query("SELECT id,title FROM library WHERE lower(title) IN (SELECT lower(title) FROM library GROUP BY lower(title) HAVING count(*) > 1) ORDER BY title ASC");
        $dbHandle = null;

        $is_duplicate = false;

        while ($row = $result->fetch(PDO::FETCH_NAMED)) {
            $is_duplicate = true;
            echo '<div style="padding:4px 8px"><input type="checkbox" name="ids[]" value="' . $row['id'] . '">'
            . '<a href="stable.php?id=' . $row['id'] . '" target="_blank" style="display:inline-block;margin-left:1em;width:4em">'
            . $row['id'] . '</a><span style="margin-left:1em">' . $row['title'] . '</span></div>';
        }

        if ($is_duplicate) {
            echo '<div style="padding:8px 8px"><input type="submit" name="submit" value="Delete selected"><br>'
            . '<b>Warning! This action cannot be undone. Inspect the items you want to delete for supplementary files and important notes.</b></div>';
        } else {
            echo '<div style="padding:4px 8px">No duplicates found.</div>';
        }
        echo '</form>';
    } elseif (isset($_GET['find_duplicates']) && $_GET['find_duplicates'] == 'hash') {
        
        ini_set('max_execution_time', 900);

        echo '<div class="details alternating_row" style="font-weight:bold">Possible Duplicates:</div>'
        . '<div style="padding:4px 8px"><input type="checkbox" style="visibility:hidden">'
        . '<span style="display:inline-block;margin-left:1em;width:4em">ID</span><span style="margin-left:1em">File hash</span></div>';

        echo '<form action="duplicates.php" method="POST">';

        database_connect(IL_DATABASE_PATH, 'library');

        //CALCULATE MD5 HASH FOR EACH PDF IF EMPTY
        $result = $dbHandle->query("SELECT id,file,filehash FROM library");

        $hashes = array();
        while ($row = $result->fetch(PDO::FETCH_NAMED)) {
            if (empty($row['filehash']) && is_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($row['file']) . DIRECTORY_SEPARATOR . $row['file'])) {
                $hashes[$row['id']] = md5_file(IL_PDF_PATH . DIRECTORY_SEPARATOR . get_subfolder($row['file']) . DIRECTORY_SEPARATOR . $row['file']);
            }
        }
        
        //SAVE NEW HASHES
        $dbHandle->beginTransaction();

        while (list($id, $hash) = each($hashes)) {
            $hash = $dbHandle->quote($hash);
            $id = $dbHandle->quote($id);
            $dbHandle->exec("UPDATE library SET filehash=" . $hash . " WHERE id=" . $id);
        }

        $dbHandle->commit();

        $result = null;
        $row = null;

        $result = $dbHandle->query("SELECT id,filehash FROM library"
                . " WHERE filehash IN (SELECT filehash FROM library WHERE filehash!='' GROUP BY filehash HAVING count(*) > 1)"
                . " ORDER BY filehash");
        $dbHandle = null;

        $is_duplicate = false;

        while ($row = $result->fetch(PDO::FETCH_NAMED)) {
            $is_duplicate = true;
            echo '<div style="padding:4px 8px"><input type="checkbox" name="ids[]" value="' . $row['id'] . '">'
            . '<a href="stable.php?id=' . $row['id'] . '" target="_blank" style="display:inline-block;margin-left:1em;width:4em">'
            . $row['id'] . '</a><span style="margin-left:1em">' . $row['filehash'] . '</span></div>';
        }

        if ($is_duplicate) {
            echo '<div style="padding:8px 8px"><input type="submit" name="submit" value="Delete selected"><br>'
            . '<b>Warning! This action cannot be undone. Inspect the items you want to delete for supplementary files and important notes.</b></div>';
        } else {
            echo '<div style="padding:4px 8px">No duplicates found.</div>';
        }
        echo '</form>';
    }
} else {
    echo 'Super user or User permissions required.';
}
?>