<?php
error_reporting(0);
ini_set('date.timezone', 'Asia/Jakarta');
require('lib/bmkg.php');

$method = isset($_GET['method'])?$_GET['method']:"last_earthquake";

$bmkg = new BMKG("file_get_contents","xml");

if($method == "list_earthquake_5plus"){
	$data = $bmkg->list_earthquake_5plus();
} else if($method == "last_earthquake_5plus"){
	$data = $bmkg->last_earthquake_5plus();
} else if($method == "list_earthquake"){
	$data = $bmkg->list_earthquake();
} else if($method == "last_tsunami"){
	$data = $bmkg->last_tsunami();
} else {
	$data = $bmkg->last_earthquake();
}

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
?>