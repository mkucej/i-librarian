<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (!empty($_GET['file'])) {
    
    $userID = intval($_SESSION['user_id']);
    $fileID = intval($_GET['file']);

    database_connect($database_path, 'history');
    
    $dbHandle->exec("CREATE TABLE IF NOT EXISTS usersfiles (
                    id INTEGER PRIMARY KEY,
                    userID INTEGER NOT NULL DEFAULT '',
                    fileID INTEGER NOT NULL DEFAULT '',
                    viewed INTEGER NOT NULL DEFAULT '',
                    UNIQUE(userID,fileID)
                    )");
    
    $dbHandle->beginTransaction();
    $dbHandle->exec("DELETE FROM usersfiles WHERE userID=$userID AND fileID=$fileID");
    $dbHandle->exec("INSERT INTO usersfiles (userID,fileID,viewed) VALUES ($userID,$fileID,".time().")");
    $dbHandle->commit();
    $dbHandle = null;
}
?>