<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

database_connect(IL_DATABASE_PATH, 'library');

if (isset($_GET['project'])) {
    $result = $dbHandle->query("SELECT id,title,abstract,addition_date FROM library
        WHERE id IN (SELECT fileID FROM projectsfiles WHERE projectID=".intval($_GET['project']).") ORDER BY id DESC LIMIT 100");
    $result2 = $dbHandle->query("SELECT project FROM projects WHERE  projectID=".intval($_GET['project']));
    $project_name = $result2->fetchColumn();
    $result2 = null;
} else {
    $result = $dbHandle->query("SELECT id,title,abstract,addition_date FROM library ORDER BY id DESC LIMIT 100");
}

$dbHandle = null;

header("Content-Type: application/rss+xml; charset=UTF-8");

$rssfeed = '<?xml version="1.0" encoding="UTF-8"?>';
$rssfeed .= '<rss version="2.0">';
$rssfeed .= '<channel>';

if (!empty($project_name)) {
    $rssfeed .= '<title>I, Librarian project "'.htmlspecialchars($project_name).'" RSS feed</title>';
} else {
    $rssfeed .= '<title>I, Librarian RSS feed</title>';
}

$rssfeed .= '<link>' . IL_URL . '</link>';
$rssfeed .= '<description>New articles in I, Librarian</description>';
$rssfeed .= '<language>en-us</language>'.PHP_EOL;

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    
    extract($row);
    $rssfeed .= '<item>';
    $rssfeed .= '<title>' . htmlspecialchars($title) . '</title>';
    $rssfeed .= '<description>' . htmlspecialchars($abstract) . '</description>';
    $rssfeed .= '<link>' . IL_URL . 'stable.php?id='.$id.'</link>';
    $rssfeed .= '<pubDate>' . date("D, d M Y H:i:s O", strtotime($addition_date)) . '</pubDate>';
    $rssfeed .= '<guid>' . IL_URL . 'stable.php?id=' . $id . '</guid>';
    $rssfeed .= '</item>'.PHP_EOL;
}

$rssfeed .= '</channel>';
$rssfeed .= '</rss>';

echo $rssfeed;
?>
