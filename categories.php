<?php
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

##########	category updating	##########

    if (isset($_GET['form_sent'])) {

        $ids = array();

        $file_id = intval($_GET['file']);

        $_GET['category2'] = preg_replace('/\s{2,}/', '', $_GET['category2']);
        $_GET['category2'] = preg_replace('/^\s$/', '', $_GET['category2']);
        $_GET['category2'] = array_filter($_GET['category2']);

        ####### record new category into categories, if not exists #########

        if (!empty($_GET['category2'])) {

            database_connect(IL_DATABASE_PATH, 'library');

            $query = "INSERT INTO categories (category) VALUES (:category)";
            $stmt = $dbHandle->prepare($query);
            $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

            $dbHandle->beginTransaction();

            while (list($key, $new_category) = each($_GET['category2'])) {
                $new_category_quoted = $dbHandle->quote($new_category);
                $result = $dbHandle->query("SELECT categoryID FROM categories WHERE category=$new_category_quoted");
                $exists = $result->fetchColumn();
                $result = null;
                $ids[] = $exists;
                if (empty($exists)) {
                    $stmt->execute();
                    $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM categories");
                    $ids[] = $last_id->fetchColumn();
                    $last_id = null;
                }
            }

            $dbHandle->commit();
            $stmt = null;
            $dbHandle = null;
        }

        ####### record new relations into filescategories #########

        database_connect(IL_DATABASE_PATH, 'library');

        $categories = array();

        if (!empty($_GET['category']) || !empty($ids)) {
            $categories = array_merge((array) $_GET['category'], (array) $ids);
            $categories = array_filter(array_unique($categories));
        }

        $query = "INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES (:fileid,:categoryid)";

        $stmt = $dbHandle->prepare($query);
        $stmt->bindParam(':fileid', $file_id);
        $stmt->bindParam(':categoryid', $category_id);

        $dbHandle->beginTransaction();
        $dbHandle->exec("DELETE FROM filescategories WHERE fileID=" . $file_id);
        while (list($key, $category_id) = each($categories)) {
            if (!empty($file_id))
                $stmt->execute();
        }
        $dbHandle->commit();
        $stmt = null;
        $dbHandle = null;

        die('OK');
    }

##########	read reference data, categories	##########

    database_connect(IL_DATABASE_PATH, 'library');

    $file_query = $dbHandle->quote($_GET['file']);

    $record = $dbHandle->query("SELECT title,abstract FROM library WHERE id=$file_query LIMIT 1");
    $result = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
    $record_categories = $dbHandle->query("SELECT filescategories.categoryID as categoryID
	FROM filescategories INNER JOIN categories ON filescategories.categoryID=categories.categoryID
	WHERE fileID=$file_query");

    $dbHandle = null;

    $paper = $record->fetch(PDO::FETCH_ASSOC);

    $cat_all = array();

    while ($categories = $result->fetch(PDO::FETCH_ASSOC)) {
        $cat_all["$categories[categoryID]"] = $categories['category'];
    }

    $cat_paper = $record_categories->fetchAll(PDO::FETCH_COLUMN);

    while (list($cat_key, $cat_name) = each($cat_all)) {
        if (stristr("$paper[title] $paper[abstract]", $cat_name)) {

            $checkbox = '<span data-catid="' . $cat_key . '" style="cursor: pointer">';

            if (in_array($cat_key, $cat_paper)) {
                $checkbox .= '<i class="fa fa-check-square"></i> ';
            } else {
                $checkbox .= '<i class="fa fa-square-o"></i> ';
            }
            $suggested_categories[] = $checkbox . htmlspecialchars($cat_name) . '</span><br>';
        }
    }

    reset($cat_all);
    ?>
    <form id="categoriesform" action="categories.php" method="GET">
        <input type="hidden" name="form_sent">
        <input type="hidden" name="file" value="<?php print htmlspecialchars($_GET['file']) ?>">
        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;height:100%;margin-top: 0px">
            <tr>
                <td class="alternating_row" style="width: 190px;padding: 5px">
                    <input type="submit" value="Save" style="display:none">
                    <button id="newcatbutton" style="margin:0"><i class="fa fa-save"></i> Save</button><br>
                    <b>Add to new categories:</b><br>
                    <input type="text" size="25" name="category2[]" value="" style="width:190px"><br>
                    <input type="text" size="25" name="category2[]" value="" style="width:190px"><br>
                    <input type="text" size="25" name="category2[]" value="" style="width:190px"><br>
                    <input type="text" size="25" name="category2[]" value="" style="width:190px"><br>
                    <input type="text" size="25" name="category2[]" value="" style="width:190px"><br>
                    <br>
                    <b>Suggestions:</b><br>
                    <span id="suggestions">
                        <?php if (!empty($suggested_categories)) print implode('<div style="clear:both"></div>', $suggested_categories); ?>
                    </span>
                </td>
                <td>
                    <div class="alternating_row">
                        <input type="text" id="filtercategories" value="" placeholder="Filter categories" style="width:300px;margin:0.75em 0">
                    </div>
                    <table class="categorieslist" style="width:49%;float:left;padding:2px">
                        <?php
                        $i = 1;
                        $newdiv = null;
                        $totalcount = count($cat_all);

                        while (list($cat_key, $cat_name) = each($cat_all)) {
                            $cat_selected = null;
                            if ($i > (1 + $totalcount / 2) && !$newdiv) {
                                print '</table><table class="categorieslist" style="width:49%;float:right;padding:2px">';
                                $newdiv = true;
                            }

                            if (isset($cat_paper) && is_numeric(array_search($cat_key, $cat_paper)))
                                $cat_selected = true;
                            print PHP_EOL . '<tr><td data-catid="' . $cat_key . '" class="select_span';
                            if ($cat_selected)
                                print ' alternating_row';
                            print '">';
                            print '<input type="checkbox" name="category[]" value="' . htmlspecialchars($cat_key) . '"';
                            if ($cat_selected)
                                print ' checked';
                            print ' style="display:none">';
                            print '&nbsp;<i class="fa fa-';
                            print ($cat_selected) ? 'check-square' : 'square-o';
                            print '"></i> ' . htmlspecialchars($cat_name) . '</td></tr>';

                            $i = $i + 1;
                        }
                        ?>
                    </table>
                </td>
            </tr>
        </table>
    </form>
    <?php
} else {
    print 'Super User or User permissions required.';
}
?>