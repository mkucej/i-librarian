<?php

include_once 'data.php';
include_once 'functions.php';

// DELETE DESK CACHE
$clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR . 'desk_files', GLOB_NOSORT);
if (is_array($clean_files)) {
    foreach ($clean_files as $clean_file) {
        if (is_file($clean_file) && is_writable($clean_file))
            @unlink($clean_file);
    }
}

if (isset($_GET['file']) && isset($_GET['project']) && isset($_SESSION['auth'])) {

    database_connect($database_path, 'library');
    $id_query = $dbHandle->quote($_GET['project']);
    $file_query = $dbHandle->quote($_GET['file']);
    $result = $dbHandle->query("SELECT rowid FROM projectsfiles WHERE projectID=$id_query AND fileID=$file_query LIMIT 1");
    $rowid = $result->fetchColumn();
    $result = null;

    if (empty($rowid)) {
        $dbHandle->beginTransaction();
        $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
        $exists = $result->fetchColumn();
        $result = null;
        if ($exists == 1) {
            $update = $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES ($id_query,$file_query)");
            $dbHandle->commit();
            if ($update)
                echo 'added';
        } else {
            $dbHandle->rollBack();
            echo 'Error! This item does not exist anymore.';
        }
    } else {

        $update = $dbHandle->exec("DELETE FROM projectsfiles WHERE rowid=$rowid");
        if (isset($_GET['displayedproject']) && isset($_GET['selection']) && $_GET['selection'] == 'desk' && $_GET['project'] == $_GET['displayedproject']) {

            $export_files = read_export_files(0);
            unset($export_files[array_search($_GET['file'], $export_files)]);
            $export_files = array_values($export_files);
            save_export_files($export_files);
        }
        if ($update)
            echo 'removed';
    }

    $dbHandle = null;
    die();
}

if (isset($_GET['adduser']) && isset($_GET['userID']) && isset($_GET['projectID'])) {

    database_connect($usersdatabase_path, 'users');
    $user_query = $dbHandle->quote($_GET['userID']);
    $result = $dbHandle->query("SELECT count(*) FROM users WHERE userID=$user_query");
    $exists = $result->fetchColumn();
    $dbHandle = null;
    if ($exists == 1) {
        database_connect($database_path, 'library');
        $user_query = $dbHandle->quote($_GET['userID']);
        $project_query = $dbHandle->quote($_GET['projectID']);
        $dbHandle->exec("INSERT INTO projectsusers (projectID,userID) VALUES ($project_query,$user_query)");
        $dbHandle = null;
        echo 'done';
    } else {
        echo 'Error! This user does not exists.';
    }
    die();
}

if (isset($_GET['removeuser']) && isset($_GET['userID']) && isset($_GET['projectID'])) {

    database_connect($database_path, 'library');
    $user_query = $dbHandle->quote($_GET['userID']);
    $project_query = $dbHandle->quote($_GET['projectID']);
    $dbHandle->exec("DELETE FROM projectsusers WHERE projectID=$project_query AND userID=$user_query");
    $dbHandle = null;

    echo 'done';
    die();
}

if (isset($_GET['create']) && !empty($_GET['project'])) {

    database_connect($database_path, 'library');

    $stmt = $dbHandle->prepare("INSERT INTO projects (userID, project, active) VALUES (:userID, :project, :active)");

    $stmt->bindParam(':userID', $userID);
    $stmt->bindParam(':project', $project);
    $stmt->bindParam(':active', $active);

    $userID = $_SESSION['user_id'];
    $project = $_GET['project'];
    $active = '1';

    $insert = $stmt->execute();
    $dbHandle = null;

    echo 'done';
    die();
}

if (isset($_GET['rename']) && !empty($_GET['project']) && isset($_GET['id'])) {

    database_connect($database_path, 'library');
    $id_query = $dbHandle->quote($_GET['id']);
    $query = $dbHandle->quote($_GET['project']);
    $dbHandle->exec("UPDATE projects SET project=$query WHERE projectID=$id_query");
    $dbHandle = null;

    echo 'done';
    die();
}

if (isset($_GET['delete']) && isset($_GET['id'])) {

    database_connect($database_path, 'library');
    $query = $dbHandle->quote($_GET['id']);
    $dbHandle->beginTransaction();
    $dbHandle->exec("DELETE FROM projects WHERE projectID=$query");
    $dbHandle->exec("DELETE FROM projectsfiles WHERE projectID=$query");
    $dbHandle->exec("DELETE FROM projectsusers WHERE projectID=$query");
    $dbHandle->commit();
    $dbHandle = null;

    if (is_writable($database_path . 'project' . intval($_GET['id']) . '.sq3'))
        unlink($database_path . 'project' . intval($_GET['id']) . '.sq3');

    echo 'done';
    die();
}

if (isset($_GET['empty']) && isset($_GET['id'])) {

    database_connect($database_path, 'library');
    $query = $dbHandle->quote($_GET['id']);
    $dbHandle->exec("DELETE FROM projectsfiles WHERE projectID=$query");
    $dbHandle = null;

    echo 'done';
    die();
}

if (isset($_GET['active']) && isset($_GET['projectID'])) {

    database_connect($database_path, 'library');
    $id_query = $dbHandle->quote($_GET['projectID']);
    $active_query = $dbHandle->quote($_GET['active']);
    $dbHandle->exec("UPDATE projects SET active=" . $active_query . " WHERE projectID=" . $id_query);
    $dbHandle = null;

    echo 'done';
    die();
}
?>
