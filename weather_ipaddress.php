<?php
error_reporting(0);
ini_set('date.timezone', 'Asia/Jakarta');
require_once 'vendor/autoload.php';
require_once 'lib/bmkg.php';
require_once 'includes/globalFunction.php';
use GeoIp2\Database\Reader;

//$ip_address = get_client_ip();
$ip_address = "103.10.170.149";
$reader = new Reader('GeoLite2-City.mmdb');
$record = $reader->city($ip_address);

$country = isset($record->country->name)?$record->country->name:"";

if(!empty($country) && $country == "Indonesia"){
	$city = isset($record->raw['subdivisions'][0]['names']['de'])?$record->raw['subdivisions'][0]['names']['de']:"";
	
	if(!empty($city)){
		$bmkg = new BMKG("file_get_contents","xml");
		$list = $bmkg->province_list();
		$key = array_search($city, array_column($list, 'province'));
		
		if (!is_bool($key)) {
			$province_id = intval($key+1);
			$area_id = isset($list[$province_id]['data_city'][0]['area_id'])?$list[$province_id]['data_city'][0]['area_id']:"";
			
			$data = $bmkg->weather($province_id, $area_id, $ip_address);
		}
	}
}

header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
?>