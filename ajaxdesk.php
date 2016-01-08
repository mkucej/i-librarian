<?php

include_once 'data.php';
include_once 'functions.php';

if (isset($_GET['file']) && isset($_GET['project']) && isset($_SESSION['auth'])) {

    database_connect(IL_DATABASE_PATH, 'library');
    
    $id_query = $dbHandle->quote($_GET['project']);
    $file_query = $dbHandle->quote($_GET['file']);
    
    $dbHandle->beginTransaction();
    
    $result = $dbHandle->query("SELECT rowid FROM projectsfiles WHERE projectID=$id_query AND fileID=$file_query LIMIT 1");
    $rowid = $result->fetchColumn();
    $result = null;

    if (empty($rowid)) {
        
        $result = $dbHandle->query("SELECT count(*) from projectsfiles WHERE projectID=" . $id_query);
        $count = $result->fetchColumn();
        $result = null;
        if ($count >= 100000) {
            $dbHandle->rollBack();
            echo 'Error! Desk project can hold up to 100,000 items.';
            die();
        }
        $result = $dbHandle->query("SELECT COUNT(*) FROM library WHERE id=$file_query");
        $exists = $result->fetchColumn();
        $result = null;
        if ($exists == 1) {
            $update = $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES ($id_query,$file_query)");
            if ($update)
                echo 'added';
        } else {
            $dbHandle->rollBack();
            echo 'Error! This item does not exist anymore.';
        }
    } else {

        $update = $dbHandle->exec("DELETE FROM projectsfiles WHERE rowid=$rowid");
        if ($update)
            echo 'removed';
    }

    $dbHandle->commit();
    $dbHandle = null;
    die();
}

if (isset($_GET['adduser']) && isset($_GET['userID']) && isset($_GET['projectID'])) {

    database_connect(IL_USER_DATABASE_PATH, 'users');
    $user_query = $dbHandle->quote($_GET['userID']);
    $result = $dbHandle->query("SELECT count(*) FROM users WHERE userID=$user_query");
    $exists = $result->fetchColumn();
    $dbHandle = null;
    if ($exists == 1) {
        database_connect(IL_DATABASE_PATH, 'library');
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

    database_connect(IL_DATABASE_PATH, 'library');
    $user_query = $dbHandle->quote($_GET['userID']);
    $project_query = $dbHandle->quote($_GET['projectID']);
    $dbHandle->exec("DELETE FROM projectsusers WHERE projectID=$project_query AND userID=$user_query");
    $dbHandle = null;

    echo 'done';
    die();
}

if (isset($_GET['create']) && !empty($_GET['project'])) {

    database_connect(IL_DATABASE_PATH, 'library');

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

    database_connect(IL_DATABASE_PATH, 'library');
    $id_query = $dbHandle->quote($_GET['id']);
    $query = $dbHandle->quote($_GET['project']);
    $dbHandle->exec("UPDATE projects SET project=$query WHERE projectID=$id_query");
    $dbHandle = null;

    echo 'done';
    die();
}

if (isset($_GET['delete']) && isset($_GET['id'])) {

    database_connect(IL_DATABASE_PATH, 'library');
    $query = $dbHandle->quote($_GET['id']);
    $dbHandle->beginTransaction();
    $dbHandle->exec("DELETE FROM projects WHERE projectID=$query");
    $dbHandle->exec("DELETE FROM projectsfiles WHERE projectID=$query");
    $dbHandle->exec("DELETE FROM projectsusers WHERE projectID=$query");
    $dbHandle->commit();
    $dbHandle = null;

    if (is_writable(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'project' . intval($_GET['id']) . '.sq3'))
        unlink(IL_DATABASE_PATH . DIRECTORY_SEPARATOR . 'project' . intval($_GET['id']) . '.sq3');

    echo 'done';
    die();
}

if (isset($_GET['empty']) && isset($_GET['id'])) {

    database_connect(IL_DATABASE_PATH, 'library');
    $query = $dbHandle->quote($_GET['id']);
    $dbHandle->exec("DELETE FROM projectsfiles WHERE projectID=$query");
    $dbHandle = null;

    echo 'done';
    die();
}

if (isset($_GET['active']) && isset($_GET['projectID'])) {

    database_connect(IL_DATABASE_PATH, 'library');
    $id_query = $dbHandle->quote($_GET['projectID']);
    $active_query = $dbHandle->quote($_GET['active']);
    $dbHandle->exec("UPDATE projects SET active=" . $active_query . " WHERE projectID=" . $id_query);
    $dbHandle = null;

    echo 'done';
    die();
}
?>
