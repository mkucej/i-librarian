<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

database_connect($database_path, 'library');

$table1 = $dbHandle->exec("CREATE TABLE IF NOT EXISTS yellowmarkers
                 (id INTEGER PRIMARY KEY,
                  userID INTEGER NOT NULL,
                  filename TEXT NOT NULL,
                  page INTEGER NOT NULL,
                  top TEXT NOT NULL,
                  left TEXT NOT NULL,
                  width TEXT NOT NULL,
                  UNIQUE (userID,filename,page,top,left))");

$table2 = $dbHandle->exec("CREATE TABLE IF NOT EXISTS annotations
                 (id INTEGER PRIMARY KEY,
                  userID INTEGER NOT NULL,
                  filename TEXT NOT NULL,
                  page INTEGER NOT NULL,
                  top TEXT NOT NULL,
                  left TEXT NOT NULL,
                  annotation TEXT NOT NULL,
                  UNIQUE (userID,filename,page,top,left))");

if (!empty($_GET['delete'])) {
    
    if (!empty($_GET['dbid'])) {

        if ($_GET['type'] == 'yellowmarker') {

            //DELETE YELLOW MARKER
            $dbHandle->exec("DELETE FROM yellowmarkers WHERE id=".intval($_GET['dbid']));

        } elseif ($_GET['type'] == 'annotation') {

            //DELETE ANNOTATION
            $dbHandle->exec("DELETE FROM annotations WHERE id=".intval($_GET['dbid']));
        }
    }
    
    if ($_GET['delete'] == 'all') {
        
        $filename = preg_replace('/0-9\.pdf/', '', $_GET['filename']);
        
        if ($_GET['type'] == 'yellowmarker') {

            //DELETE ALL MARKERS
            $dbHandle->exec("DELETE FROM yellowmarkers
                WHERE userID=".intval($_SESSION['user_id'])." AND filename='".$filename."'");

        } elseif ($_GET['type'] == 'annotation') {

            //DELETE ALL ANNOTATIONS
            $dbHandle->exec("DELETE FROM annotations
                WHERE userID=".intval($_SESSION['user_id'])." AND filename='".$filename."'");

        } elseif ($_GET['type'] == 'all') {

            $dbHandle->beginTransaction();
            //DELETE ALL MARKERS
            $dbHandle->exec("DELETE FROM yellowmarkers
                WHERE userID=".intval($_SESSION['user_id'])." AND filename='".$filename."'");
            //DELETE ALL ANNOTATIONS
            $dbHandle->exec("DELETE FROM annotations
                WHERE userID=".intval($_SESSION['user_id'])." AND filename='".$filename."'");
            $dbHandle->commit();

        }
    }
    
    die('OK');

} elseif (!empty($_GET['dbid']) && !empty($_GET['edit']) && isset($_GET['annotation'])) {

    //EDIT ANNOTATION TEXT
    $annotation = $dbHandle->quote($_GET['annotation']);
    $update = $dbHandle->exec("UPDATE annotations SET annotation=$annotation WHERE id=".intval($_GET['dbid']));
    if ($update) die('OK');

} elseif(!empty($_SESSION['user_id']) && !empty($_GET['filename']) && !empty($_GET['page'])
         && !empty($_GET['top']) && !empty($_GET['left']) && !empty($_GET['save'])) {

    $last_id = '';
    $userid = $dbHandle->quote($_SESSION['user_id']);
    $filename = $dbHandle->quote($_GET['filename']);
    $page = $dbHandle->quote($_GET['page']);
    $top = $dbHandle->quote($_GET['top']);
    $left = $dbHandle->quote($_GET['left']);

    if ($_GET['type'] == 'yellowmarker' && !empty($_GET['width'])) {

        //SAVE YELLOW MARKER
        $width = $dbHandle->quote($_GET['width']);
        $dbHandle->beginTransaction();
        $update = $dbHandle->exec("INSERT OR IGNORE INTO yellowmarkers (userID,filename,page,top,left,width) VALUES ($userid,$filename,$page,$top,$left,$width)");

    } elseif ($_GET['type'] == 'annotation') {

        //SAVE ANNOTATION
        $annotation = '';
        if (!empty($_GET['annotation'])) $annotation = $_GET['annotation'];
        $annotation = $dbHandle->quote($annotation);
        $dbHandle->beginTransaction();
        $update = $dbHandle->exec("INSERT OR IGNORE INTO annotations (userID,filename,page,top,left,annotation) VALUES ($userid,$filename,$page,$top,$left,$annotation)");
    }

    $last_id = $dbHandle->lastInsertId();
    $update = null;
    $last_insert = null;
    $dbHandle->commit();

    die($last_id);

} elseif(!empty($_SESSION['user_id']) && !empty($_GET['filename']) && !empty($_GET['page']) && !empty($_GET['fetch'])) {

    $userid = $dbHandle->quote($_SESSION['user_id']);
    $filename = $dbHandle->quote($_GET['filename']);
    $page = $dbHandle->quote($_GET['page']);

    if ($_GET['type'] == 'yellowmarker') {

        //READ YELLOW MARKERS
        $result = $dbHandle->query("SELECT id,top,left,width FROM yellowmarkers WHERE filename=$filename AND page=$page AND userID=$userid");

    } elseif ($_GET['type'] == 'annotation') {

        if ($_GET['page'] == 'all') {
            if (isset($_GET['user']) && $_GET['user'] == 'all') {
                //READ ALL ANNOTATIONS ALL USERS
                $result = $dbHandle->query("SELECT id,top,left,annotation,page FROM annotations
                                            WHERE filename=$filename
                                            ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");
            } else {
                //READ ALL ANNOTATIONS ONE USER
                $result = $dbHandle->query("SELECT id,top,left,annotation,page FROM annotations
                                            WHERE filename=$filename AND userID=$userid
                                            ORDER BY CAST(page AS INTEGER) ASC, CAST(top AS INTEGER) ASC");
            }
        } else {
            //READ ANNOTATIONS FOR ONE PAGE
            $result = $dbHandle->query("SELECT id,top,left,annotation FROM annotations
                                        WHERE filename=$filename AND page=$page AND userID=$userid");
        }
    }

    $annotations = $result -> fetchAll(PDO::FETCH_ASSOC);
    while (list($key, $value) = each($annotations)) {
        if (isset($value['annotation'])) $annotations[$key]['annotation'] = htmlspecialchars($value['annotation']);
    }
    die(json_encode($annotations));

} elseif(!empty($_SESSION['user_id']) && !empty($_GET['filename']) && !empty($_GET['page']) && !empty($_GET['fetchothers'])) {

    $userid = $dbHandle->quote($_SESSION['user_id']);
    $filename = $dbHandle->quote($_GET['filename']);
    $page = $dbHandle->quote($_GET['page']);

    if ($_GET['type'] == 'yellowmarker') {

        //READ OTHERS' YELLOW MARKERS
        $result = $dbHandle->query("SELECT id,userID,top,left,width FROM yellowmarkers WHERE filename=$filename AND page=$page AND userID!=$userid");

    } elseif ($_GET['type'] == 'annotation') {

        //READ OTHERS' ANNOTATIONS
        $result = $dbHandle->query("SELECT id,userID,top,left,annotation FROM annotations WHERE filename=$filename AND page=$page AND userID!=$userid");
    }

    $annotations = $result -> fetchAll(PDO::FETCH_ASSOC);
    while (list($key, $value) = each($annotations)) {
        if (isset($value['annotation'])) $annotations[$key]['annotation'] = htmlspecialchars($value['annotation']);
        if (isset($value['userID'])) $annotations[$key]['user'] = get_username($dbHandle, $usersdatabase_path, $value['userID']);
    }
    die(json_encode($annotations));
}
?>