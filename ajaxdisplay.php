<?php
include_once 'data.php';

if (in_array($_GET['value'], array('brief','summary','abstract','icons'))) {
    $_SESSION['display'] = $_GET['value'];
} elseif (in_array($_GET['value'], array('id','year','journal','rating','title'))) {
    $_SESSION['orderby'] = $_GET['value'];
} elseif (in_array($_GET['value'], array('5','10','15','20','50','100'))) {
    $_SESSION['limit'] = $_GET['value'];
} else {
    echo 'Error! Invalid value.';
}
?>