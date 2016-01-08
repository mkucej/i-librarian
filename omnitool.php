<?php
include_once 'data.php';
include_once 'functions.php';

if (!empty($_POST['omnitool'])) {

    database_connect(IL_DATABASE_PATH, 'library');
    $user_query = $dbHandle->quote($_SESSION['user_id']);

    $quoted_path = $dbHandle->quote(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'history.sq3');
    $dbHandle->exec("ATTACH DATABASE $quoted_path as history");
    
    if ($_POST['omnitool'] == '5' || $_POST['omnitool'] == '6') {
        
        attach_clipboard($dbHandle);
    }

    $dbHandle->beginTransaction();

    // SAVE TO SHELF
    if ($_POST['omnitool'] == '1') {

        $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) SELECT $user_query, itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id DESC");
        $dbHandle->exec("DELETE FROM shelves WHERE fileID NOT IN (SELECT id from library)");

    }

    // REMOVE FROM SHELF
    if ($_POST['omnitool'] == '2') {

        $dbHandle->exec("DELETE FROM shelves WHERE fileID IN (SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id) AND userID=$user_query");
    }

    // SAVE TO PROJECT
    if ($_POST['omnitool'] == '3' && !empty($_POST['project3'])) {

        $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) SELECT " . intval($_POST['project3']) . ", itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id DESC");
        $dbHandle->exec("DELETE FROM projectsfiles WHERE fileID NOT IN (SELECT id from library)");

    }

    // REMOVE FROM PROJECT
    if ($_POST['omnitool'] == '4' && !empty($_POST['project4'])) {

        $dbHandle->exec("DELETE FROM projectsfiles WHERE projectID=" . intval($_POST['project4']) . " AND fileID IN (SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id)");
        
    }

    // SAVE TO CLIPBOARD
    if ($_POST['omnitool'] == '5') {
        
        $dbHandle->exec("INSERT OR IGNORE INTO clipboard.files (id) SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id DESC");
        
    }

    // REMOVE FROM CLIPBOARD
    if ($_POST['omnitool'] == '6') {
        
        $dbHandle->exec("DELETE FROM clipboard.files WHERE id IN (SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id)");
        
    }

    // SAVE TO CATEGORIES INCLUDING NEW ONES
    if ($_POST['omnitool'] == '7' && !empty($_POST['category2'])) {

        $_POST['category2'] = preg_replace('/\s{2,}/', '', $_POST['category2']);
        $_POST['category2'] = preg_replace('/^\s$/', '', $_POST['category2']);
        $_POST['category2'] = array_filter($_POST['category2']);

        $query = "INSERT INTO categories (category) VALUES (:category)";
        $stmt = $dbHandle->prepare($query);
        $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

        while (list($key, $new_category) = each($_POST['category2'])) {
            $new_category_quoted = $dbHandle->quote($new_category);
            $result = $dbHandle->query("SELECT categoryID FROM categories WHERE category=$new_category_quoted");
            $exists = $result->fetchColumn();
            $result = null;
            if (empty($exists)) {
                $stmt->execute();
                $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM categories");
                $last_insert_rowid = $last_id->fetchColumn();
                $last_id = null;
                $dbHandle->exec("INSERT OR IGNORE INTO filescategories (fileID,categoryID) SELECT itemID, " . intval($last_insert_rowid) . " FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id DESC");
            }
        }
        $dbHandle->exec("DELETE FROM filescategories WHERE fileID NOT IN (SELECT id from library)");
    }

    // SAVE TO EXISTING CATEGORIES
    if ($_POST['omnitool'] == '7' && !empty($_POST['category'])) {
        
        while (list(, $cat) = each($_POST['category'])) {

            $dbHandle->exec("INSERT OR IGNORE INTO filescategories (fileID,categoryID) SELECT itemID, " . intval($cat) . " FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id DESC");
        }
        $dbHandle->exec("DELETE FROM filescategories WHERE fileID NOT IN (SELECT id from library)");
    }

    // REMOVE FROM CATEGORIES
    if ($_POST['omnitool'] == '9' && !empty($_POST['category'])) {
        
        while (list(, $cat) = each($_POST['category'])) {

            $dbHandle->exec("DELETE FROM filescategories WHERE categoryID=" . intval($cat) . " AND fileID IN (SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` ORDER BY id)");
        }
    }

    $dbHandle->commit();

    // DELETE ITEMS
    if ($_POST['omnitool'] == '8' && isset($_SESSION['permissions']) && $_SESSION['permissions'] == 'A') {

        $result = $dbHandle->query("SELECT itemID FROM history.`" . $_SESSION['display_files'] . "` LIMIT 10000");
        $delete_files = $result->fetchAll(PDO::FETCH_COLUMN);
        delete_record($dbHandle, $delete_files);
    }

    $dbHandle = null;
} elseif (isset($_SESSION['auth'])) {
    ?>

    <div>
        <table class="threed" cellspacing=0 style="width:100%">
            <tr>
                <td class="threed select_span omnitooltd" style="width:32%;line-height:22px">
                    <input type="radio" style="display:none" name="omnitool" value="1">
                    &nbsp;<i class="fa fa-circle-o"></i>
                    Save to Shelf
                </td>
                <td class="threed select_span omnitooltd" style="width:32%;line-height:22px">
                    <input type="radio" style="display:none" name="omnitool" value="5">
                    &nbsp;<i class="fa fa-circle-o"></i>
                    Save to Clipboard
                </td>
                <td class="threed select_span omnitooltd" style="width:36%;line-height:22px">
                    <input type="radio" style="display:none" name="omnitool" value="3">
                    <span style="position:relative;top:4px">
                        &nbsp;<i class="fa fa-circle-o"></i>
                        Save to
                    </span>
                    <div style="float:right;position:relative;top:2px">
                        <select name="project3" style="width:150px">
                            <?php
                            database_connect(IL_DATABASE_PATH, 'library');
                            $desktop_projects = array();
                            $desktop_projects = read_desktop($dbHandle);

                            while (list(, $value) = each($desktop_projects)) {
                                print '<option value="' . $value['projectID'] . '">' . htmlspecialchars($value['project']) . '</option>';
                            }
                            reset($desktop_projects);
                            ?>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="threed select_span omnitooltd" style="line-height:22px">
                    <input type="radio" style="display:none" name="omnitool" value="2">
                    &nbsp;<i class="fa fa-circle-o"></i>
                    Remove from Shelf
                </td>
                <td class="threed select_span omnitooltd" style="line-height:22px">
                    <input type="radio" style="display:none" name="omnitool" value="6">
                    &nbsp;<i class="fa fa-circle-o"></i>
                    Remove from Clipboard
                </td>
                <td class="threed select_span omnitooltd" style="line-height:22px">
                    <input type="radio" style="display:none" name="omnitool" value="4">
                    <span style="position:relative;top:4px">
                        &nbsp;<i class="fa fa-circle-o"></i>
                        Remove from
                    </span>
                    <div style="float:right;position:relative;top:2px">
                        <select name="project4" style="width:150px">
                            <?php
                            while (list(, $value) = each($desktop_projects)) {
                                print '<option value="' . $value['projectID'] . '">' . htmlspecialchars($value['project']) . '</option>';
                            }
                            reset($desktop_projects);
                            ?>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="threed select_span omnitooltd" colspan=1  style="padding:8px 4px">
                    <input type="radio" style="display:none" name="omnitool" value="7">
                    &nbsp;<i class="fa fa-circle-o"></i>
                    Add to Categories:
                </td>
                <td class="threed select_span omnitooltd" colspan=2>
                    <input type="radio" style="display:none" name="omnitool" value="9">
                    &nbsp;<i class="fa fa-circle-o"></i>
                    Remove from Categories:
                </td>
            </tr>
            <tr>
                <td class="threed" style="padding:4px" colspan=3>
                    <div style="overflow:auto;height:240px;background-color:#fff;border:1px solid #C5C6C9;margin-left:auto">
                        <form action="display.php" id="omnitoolcategories">
                            <table cellspacing=0 style="width: 99.5%">
                                <tr>
                                    <td style="width: 33.2%;padding:2px">
                                        <input type="text" name="category2[]" value="" style="width:99.5%" placeholder="Enter new category">
                                    </td>
                                    <td style="width: 33.2%;padding:2px">
                                        <input type="text" name="category2[]" value="" style="width:99.5%" placeholder="Enter new category">
                                    </td>
                                    <td style="width: 33.2%;padding:2px">
                                        <input type="text" name="category2[]" value="" style="width:99.5%" placeholder="Enter new category">
                                    </td>
                                </tr>
                            </table>
                            <table cellspacing=0 style="float:left;width: 33.2%;padding:2px">
                                <?php
                                $category_string = null;

                                $result3 = $dbHandle->query("SELECT count(*) FROM categories");
                                $totalcount = $result3->fetchColumn();
                                $result3 = null;

                                $i = 1;
                                $isdiv = null;
                                $isdiv2 = null;
                                $result3 = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
                                while ($category = $result3->fetch(PDO::FETCH_ASSOC)) {
                                    $cat_all[$category['categoryID']] = $category['category'];
                                    if ($i > 1 && $i > ($totalcount / 3) && !$isdiv) {
                                        print '</table><table cellspacing=0 style="width: 33.2%;float: left;padding:2px">';
                                        $isdiv = true;
                                    }
                                    if ($i > 2 && $i > (2 * $totalcount / 3) && !$isdiv2) {
                                        print '</table><table cellspacing=0 style="width: 33.2%;float: left;padding:2px">';
                                        $isdiv2 = true;
                                    }
                                    print PHP_EOL . '<tr><td class="select_span">';
                                    print "<input type=\"checkbox\" name=\"category[]\" value=\"" . htmlspecialchars($category['categoryID']) . "\"";
                                    print " style=\"display:none\">&nbsp;<i class=\"fa fa-square-o\"></i> " . htmlspecialchars($category['category']) . "</td></tr>";
                                    $i = $i + 1;
                                }
                                $result3 = null;
                                ?>
                            </table>
                        </form>
                    </div>
                </td>
            </tr>
            <?php
            if ($_SESSION['permissions'] == 'A') {
                ?>
                <tr>
                    <td class="select_span omnitooltd" id="lock" style="padding-top:10px">
                        &nbsp;<i class="fa fa-lock"></i>
                        unlock
                    </td>
                    <td class="omnitooltd" colspan=2>
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td class="omnitooltd ui-state-disabled" colspan=2>
                        <input type="checkbox" style="display:none" name="omnitool" value="8" disabled>
                        &nbsp;<i class="fa fa-square-o"></i>
                        <span class="ui-state-error-text fa fa-exclamation-triangle"></span>
                        Permanently delete from Library
                    </td>
                    <td class="omnitooltd">
                        &nbsp;
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>

    <?php
}
?>