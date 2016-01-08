<?php

include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (!empty($_GET['file'])) {

    $userID = intval($_SESSION['user_id']);
    $fileID = intval($_GET['file']);

    database_connect(IL_DATABASE_PATH, 'history');

    $dbHandle->exec("CREATE TABLE IF NOT EXISTS usersfiles (
                    id INTEGER PRIMARY KEY,
                    userID INTEGER NOT NULL DEFAULT '',
                    fileID INTEGER NOT NULL DEFAULT '',
                    viewed INTEGER NOT NULL DEFAULT '',
                    UNIQUE(userID,fileID)
                    )");

    $dbHandle->beginTransaction();
    $dbHandle->exec("DELETE FROM usersfiles WHERE userID=$userID AND fileID=$fileID");
    $dbHandle->exec("INSERT INTO usersfiles (userID,fileID,viewed) VALUES ($userID,$fileID," . time() . ")");
    $dbHandle->commit();
    $dbHandle = null;
}

if (!empty($_GET['filename']) && !empty($_GET['page'])) {
    
    if (substr($_GET['filename'], 0, 4) == 'lib_') die();

    database_connect(IL_DATABASE_PATH, 'history');

    $dbHandle->exec("CREATE TABLE IF NOT EXISTS bookmarks (
                    id INTEGER PRIMARY KEY,
                    userID INTEGER NOT NULL DEFAULT '',
                    file TEXT NOT NULL DEFAULT '',
                    page INTEGER NOT NULL DEFAULT 1,
                    UNIQUE(userID,file)
                    )");
    
    $userID = intval($_SESSION['user_id']);
    $filename = $dbHandle->quote($_GET['filename']);
    $page = intval($_GET['page']);

    $dbHandle->beginTransaction();
    $dbHandle->exec("DELETE FROM bookmarks WHERE userID=" . $userID . " AND file=" . $filename);
    if ($page > 1)
        $dbHandle->exec("INSERT INTO bookmarks (userID,file,page) VALUES (" . $userID . "," . $filename . "," . $page .")");
    $dbHandle->commit();
    $dbHandle = null;
}
?>