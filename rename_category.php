<?php
include_once 'data.php';

if (isset($_SESSION['auth']) && isset($_SESSION['permissions']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

    include_once 'functions.php';

    database_connect(IL_DATABASE_PATH, 'library');

    if (!empty($_GET['old_category']))
        $old_category_query = $dbHandle->quote($_GET['old_category']);

    if (!empty($_GET['add_category'])) {

        $categories = array();
        $categories = array_values(array_filter($_GET['new_category']));

        $dbHandle->beginTransaction();
        foreach($categories as $category) {
            $new_category_query = $dbHandle->quote($category);
            $result = $dbHandle->query("SELECT count(*) FROM categories WHERE category=".$new_category_query);
            $count = $result->fetchColumn();
            $result = null;
            if ($count === '1') {
                $dbHandle->rollBack();
                die('Error! This category already exists.');
            }
            if ($count === '0')
                $dbHandle->exec("INSERT INTO categories (category) VALUES ($new_category_query)");
            $count = null;
        }
        $dbHandle->commit();
        get_db_error($dbHandle, basename(__FILE__), __LINE__);
        die();
    }

    if (!empty($_GET['change_category']) && !empty($_GET['new_category']) && !empty($_GET['old_category'])) {

        $new_category_query = $dbHandle->quote($_GET['new_category']);
        $exec = $dbHandle->exec("UPDATE categories SET category=$new_category_query WHERE categoryID=$old_category_query");
        if ($exec == 1)
            die('OK');
        die('Error! Database update unsuccessful.');
    }

    if (!empty($_GET['delete_category']) && !empty($_GET['old_category'])) {

        $dbHandle->beginTransaction();
        $dbHandle->exec("DELETE FROM filescategories WHERE categoryID=$old_category_query");
        $dbHandle->exec("DELETE FROM categories WHERE categoryID=$old_category_query");
        $dbHandle->commit();
        get_db_error($dbHandle, basename(__FILE__), __LINE__);
        die('OK');
    }

    $stmt = $dbHandle->prepare("SELECT categories.categoryID,categories.category,count(filescategories.categoryID)
        FROM categories LEFT OUTER JOIN filescategories
        ON filescategories.categoryID=categories.categoryID
        GROUP BY categories.categoryID
        ORDER BY category COLLATE NOCASE");
    ?>
    <form action="rename_category.php" method="GET">
        <input type="hidden" name="add_category" value="1">
        <table style="float:left;width:40%">
            <tr>
                <td class="details alternating_row">
                    <b>Add categories:</b>
                </td>
            </tr>
            <?php
            for ($i = 1; $i <= 10; $i++) {
                ?>
                <tr>
                    <td style="padding:3px">
                        <input type="text" size="30" name="new_category[]" style="width:95%">
                    </td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <td style="padding:3px">
                    <button><i class="fa fa-save"></i> Save</button>
                </td>
            </tr>
        </table>
    </form>
    <table style="width:60%">
        <tr>
            <td class="details alternating_row" colspan="3"><b>Edit categories</b></td>
        </tr>
        <?php
        $stmt->execute();
        while ($category = $stmt->fetch(PDO::FETCH_NUM)) {
            ?>
            <tr>
                <td style="padding:4px" <?php if ($category[2] == 0) echo 'class="ui-state-active"'; ?>>
                    <span class="ui-state-default deletebutton" style="padding:1px 3px"><i class="fa fa-trash-o"></i> Delete</span>
                    <span class="ui-state-default renamebutton" style="padding:1px 3px"><i class="fa fa-pencil"></i> Rename</span>
                    <?php
                    print '<input type="text" class="editcategory" style="width:60%;padding:0 4px" data-id="' . htmlspecialchars($category[0]) . '" '
                            . 'data-content="' . htmlspecialchars($category[1]) . '" value="'. htmlspecialchars($category[1]).'"> Items: '. $category[2];
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
<br>
    <?php
} else {
    print 'Super User or User permissions required.';
}
?>