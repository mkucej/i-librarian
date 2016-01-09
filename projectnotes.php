<?php

include_once 'data.php';
include_once 'functions.php';
include_once 'index.inc.php';

echo '<body style="padding:1em 2em">';

if (empty($_GET['projectID']) || !is_numeric($_GET['projectID'])) {
    displayError('No project ID provided.');
} else {
    $projectID = intval($_GET['projectID']);
}

$dbHandle = database_connect(IL_DATABASE_PATH, 'library');

$quoted_path = $dbHandle->quote(IL_USER_DATABASE_PATH . DIRECTORY_SEPARATOR . 'users.sq3');

$dbHandle->exec("ATTACH DATABASE $quoted_path AS userdatabase");

// Check if the user is in this project.
$stmt = $dbHandle->prepare("SELECT projects.project as p"
        . " FROM projects LEFT OUTER JOIN projectsusers ON projectsusers.projectID=projects.projectID"
        . " WHERE projects.projectID=:projectID AND (projectsusers.userID=:userID OR projects.userID=:userID)");

$stmt->bindParam(':userID', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindParam(':projectID', $projectID, PDO::PARAM_INT);

$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($result['p'])) {
    
    displayError('You are not authorized to see this project.');
} else {
    
    echo "<h2>Note compilation for project: \"" . htmlspecialchars($result['p']) . "\"</h2>";
}

// Get all files.
$stmt2 = $dbHandle->prepare("SELECT id, file, title FROM library"
        . " WHERE id IN (SELECT fileID from projectsfiles WHERE projectID=:projectID)");

$stmt2->bindParam(':projectID', $projectID, PDO::PARAM_INT);

// Get all notes.
$stmt3 = $dbHandle->prepare("SELECT notes, userdatabase.users.username AS username"
        . " FROM notes JOIN userdatabase.users ON userdatabase.users.userID=notes.userID"
        . " WHERE fileID=:fileID");

$stmt3->bindParam(':fileID', $id, PDO::PARAM_INT);

$stmt4 = $dbHandle->prepare("SELECT annotation, page, userdatabase.users.username AS username"
        . " FROM annotations JOIN userdatabase.users ON userdatabase.users.userID=annotations.userID"
        . " WHERE filename=:filename ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");

$stmt4->bindParam(':filename', $file, PDO::PARAM_STR);

$dbHandle->beginTransaction();

$stmt2->execute();

while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {

    extract($row);

    $stmt3->execute();
    $stmt4->execute();

    $rows2 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    $rows3 = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows2) + count($rows3) > 0) {

        echo "<h3 class=\"alternating_row\" style=\"padding:0.5em 0.75em\"><a href=\"stable.php?id=$id\" target=\"_blank\">$title</a></h3>\n";

        foreach ($rows2 as $row2) {

            extract($row2);

            echo "<div style=\"border:1px solid rgba(0,0,0,0.15);padding: 1em;margin-bottom:1em\"><b>$username:</b><br>\n";
            echo "$notes</div>\n";
        }

        foreach ($rows3 as $row3) {

            extract($row3);

            echo "<div style=\"border:1px solid rgba(0,0,0,0.15);padding: 1em;margin-bottom:1em\"><b>$username:</b><br>\n";
            echo "$annotation</div>\n";
        }
    }
}

$dbHandle->commit();

echo '</body></html>';




