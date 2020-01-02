<?php
error_reporting(0);
ini_set('date.timezone', 'Asia/Jakarta');
require('lib/bmkg.php');

$province_id = substr(trim(strip_tags(@$_GET['province_id'])), 0, 2);
$area_id = substr(trim(strip_tags(@$_GET['area_id'])), 0, 6);
$bmkg = new BMKG("file_get_contents","xml");
$data = $bmkg->weather($province_id, $area_id);

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
?>