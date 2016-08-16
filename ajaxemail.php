<?php

// BACK END TO E-MAIL BUTTON

include_once 'data.php';
include_once 'functions.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    database_connect(IL_DATABASE_PATH, 'library');
    $id_query = $dbHandle->quote($_GET['id']);
    $result = $dbHandle->query("SELECT title,abstract,doi FROM library WHERE id=" . $id_query);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $result = null;
    $dbHandle = null;

    if (!is_array($row))
        die('Error!<br>Item does not exist.');

    extract($row);

    die('mailto:?subject=Paper in I, Librarian&body=' . wordwrap($title, 65, '%0A', true) . '%0A%0A'
            . wordwrap(substr($abstract, 0, 500), 65, '%0A', true)
            . (strlen($abstract) > 500 ? '...' : '') . '%0A%0A'
            . (!empty($doi) ? 'Publisher link:%0Ahttps://dx.doi.org/' . $doi . '%0A%0A' : '')
            . 'I, Librarian link:%0A' . IL_URL . '?id=' . $_GET['id'] . '%0A%0A');
}
?>