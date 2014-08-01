<?php
include_once 'data.php';
include_once 'functions.php';

if(isset($_GET['rating']) && isset($_GET['file'])) {

	database_connect($database_path, 'library');

	$query = "UPDATE library SET rating=:rating WHERE id=:id";

	$stmt = $dbHandle->prepare($query);

	$stmt->bindParam(':id', $_GET['file']);
	$stmt->bindParam(':rating', $_GET['rating']);

	$stmt->execute();
}
?>