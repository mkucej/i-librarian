<?php
include_once 'data.php';

if (isset($_GET['limit'])) {
	settype($_GET['limit'], "integer");
	$_SESSION['limit'] = $_GET['limit'];
}
if (isset($_GET['orderby']) && ctype_alpha($_GET['orderby'])) $_SESSION['orderby'] = $_GET['orderby'];
if (isset($_GET['display']) && ctype_alpha($_GET['display'])) $_SESSION['display'] = $_GET['display'];

?>