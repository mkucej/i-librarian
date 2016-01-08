<?php
include_once 'data.php';
session_write_close();

$log = IL_TEMP_PATH . DIRECTORY_SEPARATOR . md5($_GET['user']).'-librarian-import.log';

if(!file_exists($log) || !is_readable($log)) die();
$string = file_get_contents($log);
die($string);
?>