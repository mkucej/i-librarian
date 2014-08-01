<?php
include_once 'data.php';
include_once '../functions.php';
session_write_close();

database_connect($database_path, 'library');

$id_query = $dbHandle->quote($_SESSION['user_id']);

$result = $dbHandle->query("SELECT DISTINCT projects.projectID as projectID,project
        FROM projects LEFT JOIN projectsusers ON projects.projectID=projectsusers.projectID
        WHERE (projects.userID=$id_query OR projectsusers.userID=$id_query) AND projects.active='1' ORDER BY project COLLATE NOCASE ASC");
$projects = $result->fetchAll(PDO::FETCH_ASSOC);
?>
<ul data-role="listview" data-inset="true" style="margin:0">
    <?php
    foreach ($projects as $project) {
        
            print '<li><a href="#" class="open-project" data-id="project-' . $project['projectID'] . '" data-project="project-' . $project['project'] . '" style="font-size:0.8em">' . $project['project'] . '</a></li>';
    }
    ?>
</ul>