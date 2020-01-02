<?php
require('simple_html_dom.php');

class BMKG{
	private $tools = '';
	private $method = '';
	private $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:52.0.1) Gecko/20100101 Firefox/52.0.1';
	private $base_url_data_weather = "http://data.bmkg.go.id/datamkg/MEWS/DigitalForecast";
	
	public function __construct($tools="curl",$method="xml"){
		$this->tools = $tools;
		$this->method = $method;
	}
	
	private function remote_data($url){
		if($this->method == "xml"){
			if($this->tools == "curl"){
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_REFERER, $url);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_TIMEOUT, 3);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
				$result = curl_exec($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				if($http_code == 200){
					return $result;
				} else {
					return false;
				}	
			} else {
				$opts = array('http' =>
							array(
								'method'  => 'GET',
								'timeout' => 3,
								"header" => "Content-type: text/xml \r\n",
								'user_agent'  => $this->user_agent
							)
						);

				$context = stream_context_create($opts);
				$result = @file_get_contents($url, false, $context);
				if($result === FALSE){
					return false;
				} else {
					return $result;
				}
			}
		} else {
			if($this->tools == "curl"){
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_REFERER, $url);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_TIMEOUT, 3);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				$result = curl_exec($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if($http_code == 200){
					return utf8_decode($result);
				} else {
					return false;
				}
			} else {
				$opts = array('http' =>
							array(
								'method'  => 'GET',
								'timeout' => 3,
								"header" => "Content-type: text/html \r\n",
								'user_agent'  => $this->user_agent
							)
						);

				$context = stream_context_create($opts);
				$result = @file_get_contents($url, false, $context);

				if($result === FALSE){
					return false;
				} else {
					return utf8_decode($result);
				} 
			}	
		}
        
    }
	
	public function list_earthquake_5plus(){
		$result = array();
		$data = $this->remote_data("http://data.bmkg.go.id/gempaterkini.xml");
		$arrData = array();

		if(!$data){
			$result['status']     = "error";
			$result['message']    = "offline";
			$result['timestamp']  = time();
		} else {
			$result['status']     = "success";
			$result['timestamp']  = time();
			
			$xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
			$arrResult = json_decode( json_encode($xml),TRUE);
			
			$arrGempa = isset($arrResult['gempa'])?$arrResult['gempa']:array();
			if(count($arrGempa) > 0){
				foreach($arrGempa as $key => $gempa){
					$dt = $gempa['Tanggal'];
					$tm = str_replace("WIB","",$gempa['Jam']);
					$dttm = date("Y-m-d H:i:s", strtotime($dt." ".$tm));
					
					$arrData[$key]['time'] = $dttm;
					$lintang = isset($gempa['Lintang'])?$gempa['Lintang']:"";
					$bujur = isset($gempa['Bujur'])?$gempa['Bujur']:"";
					$arrData[$key]['position'] = $lintang." ".$bujur;
					$arrCoordinates = isset($gempa['point']['coordinates'])?explode(",",$gempa['point']['coordinates']):array();
					if(count($arrCoordinates) > 0){
						$arrData[$key]['lat'] = isset($arrCoordinates[1])?$arrCoordinates[1]:"";
						$arrData[$key]['lon'] = isset($arrCoordinates[0])?$arrCoordinates[0]:"";
					}
					$arrData[$key]['magnitude'] = isset($gempa['Magnitude'])?trim(str_replace("sr","",strtolower($gempa['Magnitude']))):"0";
					$arrData[$key]['depth'] = isset($gempa['Kedalaman'])?trim(str_replace("km","",strtolower($gempa['Kedalaman']))):"0";
					$arrData[$key]['area'] = isset($gempa['Wilayah'])?$gempa['Wilayah']:"";
				}
			}
			
			$result['data']     = $arrData;
		}
		
		return $result;
	}	
	
	public function list_earthquake(){
		$result = array();
		$data = $this->remote_data("http://data.bmkg.go.id/gempadirasakan.xml");
		$arrData = array();

		if(!$data){
			$result['status']     = "error";
			$result['message']    = "offline";
			$result['timestamp']  = time();
		} else {
			$result['status']     = "success";
			$result['timestamp']  = time();
			
			$xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
			$arrResult = json_decode( json_encode($xml),TRUE);
			
			$arrGempa = isset($arrResult['Gempa'])?$arrResult['Gempa']:array();
			if(count($arrGempa) > 0){
				foreach($arrGempa as $key => $gempa){
					$dt1 = str_replace("-"," ",$gempa['Tanggal']);
					$dt2 = trim(str_replace("WIB","",$dt1));
					$dt3 = str_replace("/","-",$dt2);
					$dttm = date("Y-m-d H:i:s", strtotime($dt3));
					
					$arrData[$key]['time'] = $dttm;
					$arrData[$key]['position'] = isset($gempa['Posisi'])?$gempa['Posisi']:"";
					$arrCoordinates = isset($gempa['point']['coordinates'])?explode(",",$gempa['point']['coordinates']):array();
					if(count($arrCoordinates) > 0){
						$arrData[$key]['lat'] = isset($arrCoordinates[0])?trim($arrCoordinates[0]):"";
						$arrData[$key]['lon'] = isset($arrCoordinates[1])?trim($arrCoordinates[1]):"";
					}
					$arrData[$key]['magnitude'] = isset($gempa['Magnitude'])?trim(str_replace("sr","",strtolower($gempa['Magnitude']))):"0";
					$arrData[$key]['depth'] = isset($gempa['Kedalaman'])?trim(str_replace("km","",strtolower($gempa['Kedalaman']))):"0";
					$arrData[$key]['area'] = isset($gempa['Dirasakan'])?str_replace("	"," ",$gempa['Dirasakan']):"";
					$arrData[$key]['description'] = isset($gempa['Keterangan'])?$gempa['Keterangan']:"";
				}
			}
			
			$result['data']     = $arrData;
		}
		
		return $result;
	}
	
	public function last_tsunami(){
		$result = array();
		$data = $this->remote_data("http://data.bmkg.go.id/lasttsunami.xml");
		$arrData = array();
		
		if(!$data){
			$result['status']     = "error";
			$result['message']    = "offline";
			$result['timestamp']  = time();
		} else {
			$result['status']     = "success";
			$result['timestamp']  = time(); 
			
			$xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
			$arrResult = json_decode( json_encode($xml),TRUE);
			
			$area = isset($arrResult['Gempa']['Area'])?$arrResult['Gempa']['Area']:"";
			$gempa = isset($arrResult['Gempa'])?$arrResult['Gempa']:array();
			
			if(!empty($area)){
				$dt = $gempa['Tanggal'];
				$tm = str_replace("WIB","",$gempa['Jam']);
				$dttm = date("Y-m-d H:i:s", strtotime($dt." ".$tm));
				
				$arrData['time'] = $dttm;
				$arrData['lat'] = isset($gempa['Lintang'])?$gempa['Lintang']:"";
				$arrData['lon'] = isset($gempa['Bujur'])?$gempa['Bujur']:"";
				$arrData['magnitude'] = isset($gempa['Magnitude'])?trim(str_replace("sr","",strtolower($gempa['Magnitude']))):"0";
				$arrData['depth'] = isset($gempa['Kedalaman'])?trim(str_replace("km","",strtolower($gempa['Kedalaman']))):"0";
				$arrData['link_detail'] = isset($gempa['Linkdetail'])?$gempa['Linkdetail']:"";
				$arrData['area'] = $area;
			}
			
			$result['data']     = $arrData;
		}	
		
		return $result;
	}
	
	public function last_earthquake_5plus(){
		$result = array();
		$data = $this->remote_data("http://data.bmkg.go.id/autogempa.xml");
		$arrData = array();
		
		if(!$data){
			$result['status']     = "error";
			$result['message']    = "offline";
			$result['timestamp']  = time();
		} else {
			$result['status']     = "success";
			$result['timestamp']  = time(); 
			
			$xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
			$arrResult = json_decode( json_encode($xml),TRUE);
			
			$wilayah1 = isset($arrResult['gempa']['Wilayah1'])?$arrResult['gempa']['Wilayah1']:"";
			$gempa = isset($arrResult['gempa'])?$arrResult['gempa']:array();
			
			if(!empty($wilayah1)){
				$dt = $gempa['Tanggal'];
				$tm = str_replace("WIB","",$gempa['Jam']);
				$dttm = date("Y-m-d H:i:s", strtotime($dt." ".$tm));
				
				
				$arrData['time'] = $dttm;
				$lintang = isset($gempa['Lintang'])?$gempa['Lintang']:"";
				$bujur = isset($gempa['Bujur'])?$gempa['Bujur']:"";
				$arrData['position'] = $lintang." ".$bujur;
				$arrCoordinates = isset($gempa['point']['coordinates'])?explode(",",$gempa['point']['coordinates']):array();
				if(count($arrCoordinates) > 0){
					$arrData['lat'] = isset($arrCoordinates[1])?$arrCoordinates[1]:"";
					$arrData['lon'] = isset($arrCoordinates[0])?$arrCoordinates[0]:"";
				}
				$arrData['magnitude'] = isset($gempa['Magnitude'])?trim(str_replace("sr","",strtolower($gempa['Magnitude']))):"0";
				$arrData['depth'] = isset($gempa['Kedalaman'])?trim(str_replace("km","",strtolower($gempa['Kedalaman']))):"0";
				$arrData['potency'] = isset($gempa['Potensi'])?$gempa['Potensi']:"";
				$arrData['mag_image'] = 'http://data.bmkg.go.id/eqmap.gif';
				
				$arrData['region'][] = $wilayah1;
				$wilayah2 = isset($gempa['Wilayah2'])?$gempa['Wilayah2']:"";
				if(!empty($wilayah2)){
					$arrData['region'][] = $wilayah2;
				}
				
				$wilayah3 = isset($gempa['Wilayah3'])?$gempa['Wilayah3']:"";
				if(!empty($wilayah3)){
					$arrData['region'][] = $wilayah3;
				}
				
				$wilayah4 = isset($gempa['Wilayah4'])?$gempa['Wilayah4']:"";
				if(!empty($wilayah4)){
					$arrData['region'][] = $wilayah4;
				}
				
				$wilayah5 = isset($gempa['Wilayah5'])?$gempa['Wilayah5']:"";
				if(!empty($wilayah5)){
					$arrData['region'][] = $wilayah5;
				}
				
				$wilayah2 = isset($gempa['Wilayah1'])?$gempa['Wilayah2']:"";
				if(!empty($wilayah2)){
					$arrData['region'][] = $wilayah2;
				}
			}
			
			$result['data']     = $arrData;
		}	
		
		return $result;
	}
	
	public function last_earthquake(){
		$result = array();
		$data = $this->remote_data("http://data.bmkg.go.id/gempadirasakan.xml");
		$arrData = array();
		
		if(!$data){
			$result['status']     = "error";
			$result['message']    = "offline";
			$result['timestamp']  = time();
		} else {
			$result['status']     = "success";
			$result['timestamp']  = time();
			
			$xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
			$arrResult = json_decode( json_encode($xml),TRUE);
			
			$arrGempa = isset($arrResult['Gempa'])?$arrResult['Gempa']:array();
			if(count($arrGempa) > 0){
				foreach($arrGempa as $key => $gempa){
					if($key > 0) break;
					
					$dt1 = str_replace("-"," ",$gempa['Tanggal']);
					$dt2 = trim(str_replace("WIB","",$dt1));
					$dt3 = str_replace("/","-",$dt2);
					$dttm = date("Y-m-d H:i:s", strtotime($dt3));
					
					$arrData['time'] = $dttm;
					$arrData['position'] = isset($gempa['Posisi'])?$gempa['Posisi']:"";
					$arrCoordinates = isset($gempa['point']['coordinates'])?explode(",",$gempa['point']['coordinates']):array();
					if(count($arrCoordinates) > 0){
						$arrData['lat'] = isset($arrCoordinates[0])?trim($arrCoordinates[0]):"";
						$arrData['lon'] = isset($arrCoordinates[1])?trim($arrCoordinates[1]):"";
					}
					$arrData['magnitude'] = isset($gempa['Magnitude'])?trim(str_replace("sr","",strtolower($gempa['Magnitude']))):"0";
					$arrData['depth'] = isset($gempa['Kedalaman'])?trim(str_replace("km","",strtolower($gempa['Kedalaman']))):"0";
					$arrData['area'] = isset($gempa['Dirasakan'])?str_replace("	"," ",$gempa['Dirasakan']):"";
					$arrData['description'] = isset($gempa['Keterangan'])?$gempa['Keterangan']:"";
					
					$data2 		 = $this->remote_data('https://inatews.bmkg.go.id/?');
					$html        = str_get_html($data2);
					$link2       = $html->find('a[class="link2"]', 0);
					$map_images	 = isset($link2->href)?$link2->href:"";
					if(!empty($map_images)){
						$arrData['mag_image'] = $map_images;
					}
				}
			}
			
			$result['data'] = $arrData;
		}
		
		return $result;
	}
	
	private function value_weather($value){
		$str = "";
		switch ($value) {
			case 0:
				$str = "Cerah";
				break;
			case 1:
				$str = "Cerah Berawan";
				break;
			case 2:
				$str = "Cerah Berawan";
				break;
			case 3:
				$str = "Berawan";
				break;
			case 4:
				$str = "Berawan Tebal";
				break;
			case 5:
				$str = "Udara Kabur";
				break;
			case 10:
				$str = "Udara Kabur";
				break;
			case 45:
				$str = "Kabut";
				break;
			case 60:
				$str = "Hujan Ringan";
				break;
			case 61:
				$str = "Hujan Sedang";
				break;
			case 63:
				$str = "Hujan Lebat";
				break;
			case 80:
				$str = "Hujan Lokal";
				break;
			case 95:
				$str = "Hujan Petir";
				break;
			case 97:
				$str = "Hujan Petir";
				break;
			case 100:
				$str = "Cerah";
				break;
			case 101:
				$str = "Cerah Berawan";
				break;
			case 102:
				$str = "Cerah Berawan";
				break;
			case 103:
				$str = "Berawan";
				break;
			case 104:
				$str = "Berawan Tebal";
				break;
		}
		
		return $str;
	}
	
	private function value_wind_direction($value){
		$str = "";
		switch ($value) {
			case "N":
				$str = "Utara";
				break;
			case "NNE":
				$str = "Utara-Timur Laut";
				break;
			case "NE":
				$str = "Timur Laut";
				break;
			case "ENE":
				$str = "Timur-Timur Laut";
				break;
			case "E":
				$str = "Timur";
				break;
			case "ESE":
				$str = "Timur-Tenggara";
				break;
			case "SE":
				$str = "Tenggara";
				break;
			case "SSE":
				$str = "Selatan-Tenggara";
				break;
			case "S":
				$str = "Selatan";
				break;
			case "SSW":
				$str = "Selatan-Barat Daya";
				break;
			case "SW":
				$str = "Barat daya";
				break;
			case "WSW":
				$str = "Barat-Barat daya";
				break;
			case "W":
				$str = "Barat";
				break;
			case "WNW":
				$str = "Barat-Barat Laut";
				break;
			case "NW":
				$str = "Barat laut";
				break;
			case "NNW":
				$str = "Utara-Barat Laut";
				break;
			case "VARIABLE":
				$str = "Berubah-ubah";
				break;
		}
		
		return $str;
	}
	
	private function value_greeting($value){
		$str = "";
		switch ($value) {
			case 0:
				$str = "Dini Hari";
				break;
			case 1:
				$str = "Dini Hari";
				break;
			case 4:
				$str = "Dini Hari";
				break;
			case 6:
				$str = "Pagi";
				break;
			case 7:
				$str = "Pagi";
				break;
			case 10:
				$str = "Pagi";
				break;
			case 12:
				$str = "Siang";
				break;
			case 13:
				$str = "Siang";
				break;
			case 16:
				$str = "Sore";
				break;
			case 18:
				$str = "Malam";
				break;
			case 19:
				$str = "Malam";
				break;
			case 22:
				$str = "Malam";
				break;
			case 24:
				$str = "Dini Hari";
				break;
			case 30:
				$str = "Pagi";
				break;
			case 36:
				$str = "Siang";
				break;
			case 42:
				$str = "Malam";
			case 48:
				$str = "Dini Hari";
				break;
			case 54:
				$str = "Pagi";
				break;
			case 60:
				$str = "Siang";
				break;
			case 66:
				$str = "Malam";
				break;
		}
		
		return $str;
	}
	
	private function value_setnow($date, $h){
		$dateNow	 	= intval(date('Ymd'));
		$hourNow	 	= intval(date('H'));	
		$val = 0;
		
		if($dateNow == $date){
			if( ($hourNow >=0 && $hourNow < 6) && ($h >=0 && $h < 6) ){
				$val = 1;
			} else if( ($hourNow >=6 && $hourNow < 12) && ($h >=6 && $h < 12) ){
				$val = 1;
			} else if( ($hourNow >=12 && $hourNow < 18) && ($h >=12 && $h < 18) ){
				$val = 1;
			} else if( ($hourNow >=18 && $hourNow <= 24) && ($h >=18 && $h < 24) ){
				$val = 1;
			}
		}
		
		return $val;
	}

	private function value_hconvert($h){
		$h	 		= intval($h);
		$newh	 	= 0;
		
		if($h < 24){
			$newh = $h;
		} else if($h > 23 && $h < 48){
			
			$newh = $h-24;
		} else if($h > 47 && $h < 71){
			$newh = $h-48;
		}

		return $newh;
	}
	
	public function province_list($province_id = ""){
		$list = array(
				"01"=>array("province"=>"Aceh","xml"=>"/DigitalForecast-Aceh.xml",
							"data_city"=>array(
										array("area_id"=>"501409","city"=>"Aceh Barat"),
										array("area_id"=>"501400","city"=>"Aceh Barat Daya"),
										array("area_id"=>"501404","city"=>"Aceh Besar"),
										array("area_id"=>"501401","city"=>"Aceh Jaya"),
										array("area_id"=>"501417","city"=>"Aceh Selatan"),
										array("area_id"=>"501414","city"=>"Aceh Singkil"),
										array("area_id"=>"501403","city"=>"Aceh Tamiang"),
										array("area_id"=>"501416","city"=>"Aceh Tengah"),
										array("area_id"=>"501405","city"=>"Aceh Tenggara"),
										array("area_id"=>"501402","city"=>"Aceh Timur"),
										array("area_id"=>"501408","city"=>"Aceh Utara"),
										array("area_id"=>"501408","city"=>"Aceh Utara"),
										array("area_id"=>"501397","city"=>"Banda Aceh"),
										array("area_id"=>"501412","city"=>"Bener Meriah"),
										array("area_id"=>"501398","city"=>"Bireun"),
										array("area_id"=>"501399","city"=>"Gayo Lues"),
										array("area_id"=>"501406","city"=>"Langsa"),
										array("area_id"=>"501407","city"=>"Lhokseumawe"),
										array("area_id"=>"501415","city"=>"Nagan Raya"),
										array("area_id"=>"501411","city"=>"Pidie"),
										array("area_id"=>"501605","city"=>"Pidie Jaya"),
										array("area_id"=>"501410","city"=>"Sabang"),
										array("area_id"=>"501413","city"=>"Simeulue"),
										array("area_id"=>"501606","city"=>"Subulussalam"),
									)
						   ),
				"02"=>array("province"=>"Bali","xml"=>"/DigitalForecast-Bali.xml",
							"data_city"=>array(
										array("area_id"=>"501162","city"=>"Amplapura"),
										array("area_id"=>"501163","city"=>"Bangli"),
										array("area_id"=>"501164","city"=>"Denpasar"),
										array("area_id"=>"501165","city"=>"Gianyar"),
										array("area_id"=>"501166","city"=>"Mengwi"),
										array("area_id"=>"501167","city"=>"Negara"),
										array("area_id"=>"501168","city"=>"Semarapura"),
										array("area_id"=>"501169","city"=>"Singaraja"),
										array("area_id"=>"501170","city"=>"Tabanan"),
									)
						   ),							
				"03"=>array("province"=>"Bangka Belitung","xml"=>"/DigitalForecast-BangkaBelitung.xml",
							"data_city"=>array(
										array("area_id"=>"5002247","city"=>"Jebus"),
										array("area_id"=>"501362","city"=>"Koba"),
										array("area_id"=>"501363","city"=>"Manggar"),
										array("area_id"=>"501364","city"=>"Mentok"),
										array("area_id"=>"501365","city"=>"Mengwi"),
										array("area_id"=>"501167","city"=>"Pangkal Pinang"),
										array("area_id"=>"5002249","city"=>"Selat Nasik"),
										array("area_id"=>"501366","city"=>"Sungai Liat"),
										array("area_id"=>"5002248","city"=>"Sungai Selan"),
										array("area_id"=>"501367","city"=>"Tanjung Pandan"),
										array("area_id"=>"501368","city"=>"Toboali"),
									)
						   ),								
				"04"=>array("province"=>"Banten","xml"=>"/DigitalForecast-Banten.xml",
							"data_city"=>array(
										array("area_id"=>"5008861","city"=>"Anyer"),
										array("area_id"=>"5008798","city"=>"Bayah"),
										array("area_id"=>"5002244","city"=>"Binuangen"),
										array("area_id"=>"5002208","city"=>"Bojonegara"),
										array("area_id"=>"5002209","city"=>"Carita"),
										array("area_id"=>"501171","city"=>"Cilegon"),
										array("area_id"=>"5002249","city"=>"Selat Nasik"),
										array("area_id"=>"501597","city"=>"Ciruas"),
										array("area_id"=>"5002205","city"=>"Gunung Kencana"),
										array("area_id"=>"5002203","city"=>"Labuan"),
										array("area_id"=>"5002207","city"=>"Lebak"),
										array("area_id"=>"5002206","city"=>"Malingping"),
										array("area_id"=>"5002201","city"=>"Merak"),
										array("area_id"=>"501172","city"=>"Pandeglang"),
										array("area_id"=>"501172","city"=>"Pandeglang"),
										array("area_id"=>"501173","city"=>"Rangkasbitung"),
										array("area_id"=>"501174","city"=>"Serang"),
										array("area_id"=>"5002333","city"=>"Tangerang"),
										array("area_id"=>"501176","city"=>"Tigaraksa"),
										array("area_id"=>"501176","city"=>"Tigaraksa"),
										array("area_id"=>"5002204","city"=>"Ujung Kulon"),
									)
						   ),								
				"05"=>array("province"=>"Bengkulu","xml"=>"/DigitalForecast-Bengkulu.xml",
							"data_city"=>array(
										array("area_id"=>"501178","city"=>"Bengkulu"),
										array("area_id"=>"501182","city"=>"Bengkulu Selatan"),
										array("area_id"=>"5002220","city"=>"Bengkulu Tengah"),
										array("area_id"=>"501177","city"=>"Bengkulu Utara"),
										array("area_id"=>"501179","city"=>"Kaur"),
										array("area_id"=>"501181","city"=>"Kepahiang"),
										array("area_id"=>"501185","city"=>"Lebong"),
										array("area_id"=>"501183","city"=>"Mukomuko"),
										array("area_id"=>"501180","city"=>"Rejang Lebong"),
										array("area_id"=>"501184","city"=>"Seluma"),
									)
						   ),								
				"06"=>array("province"=>"DI Yogyakarta","xml"=>"/DigitalForecast-DIYogyakarta.xml",
							"data_city"=>array(
										array("area_id"=>"501186","city"=>"Bantul"),
										array("area_id"=>"501187","city"=>"Sleman"),
										array("area_id"=>"501188","city"=>"Wates"),
										array("area_id"=>"501189","city"=>"Gunung Kidul"),
										array("area_id"=>"501190","city"=>"Yogyakarta"),
									)
						   ),								
				"07"=>array("province"=>"DKI Jakarta","xml"=>"/DigitalForecast-DKIJakarta.xml",
							"data_city"=>array(
										array("area_id"=>"501191","city"=>"Jakarta Timur"),
										array("area_id"=>"501192","city"=>"Jakarta Barat"),
										array("area_id"=>"501193","city"=>"Jakarta Selatan"),
										array("area_id"=>"501194","city"=>"Kepulauan Seribu"),
										array("area_id"=>"501195","city"=>"Jakarta Pusat"),
										array("area_id"=>"501196","city"=>"Jakarta Utara"),
									)
						   ),								
				"08"=>array("province"=>"Gorontalo","xml"=>"/DigitalForecast-Gorontalo.xml",
							"data_city"=>array(
										array("area_id"=>"501197","city"=>"Gorontalo"),
										array("area_id"=>"501598","city"=>"Kwandang"),
										array("area_id"=>"501198","city"=>"Limboto"),
										array("area_id"=>"501199","city"=>"Marisa"),
										array("area_id"=>"501200","city"=>"Suwawa"),
										array("area_id"=>"501201","city"=>"Tilamuta"),
									)
						   ),								
				"09"=>array("province"=>"Jambi","xml"=>"/DigitalForecast-Jambi.xml",
							"data_city"=>array(
										array("area_id"=>"501202","city"=>"Bangko"),
										array("area_id"=>"501203","city"=>"Bulian"),
										array("area_id"=>"501204","city"=>"Bungo"),
										array("area_id"=>"501205","city"=>"Jambi"),
										array("area_id"=>"5002260","city"=>"Siluak"),
										array("area_id"=>"501207","city"=>"Kuala Tungkal"),
										array("area_id"=>"501208","city"=>"Sabak"),
										array("area_id"=>"501210","city"=>"Sakernan"),
										array("area_id"=>"501209","city"=>"Sarolangun"),
										array("area_id"=>"501206","city"=>"Sungai Penuh"),
										array("area_id"=>"501211","city"=>"Tebo"),
									)
						   ),								
				"10"=>array("province"=>"Jawa Barat","xml"=>"/DigitalForecast-JawaBarat.xml",
							"data_city"=>array(
										array("area_id"=>"501212","city"=>"Bandung"),
										array("area_id"=>"501213","city"=>"Banjar"),
										array("area_id"=>"5002228","city"=>"Bekasi"),
										array("area_id"=>"501216","city"=>"Ciamis"),
										array("area_id"=>"501217","city"=>"Cianjur"),
										array("area_id"=>"501218","city"=>"Cibinong"),
										array("area_id"=>"501219","city"=>"Cikarang"),
										array("area_id"=>"501220","city"=>"Cimahi"),
										array("area_id"=>"5002287","city"=>"Cipanas"),
										array("area_id"=>"501221","city"=>"Cirebon"),
										array("area_id"=>"5002286","city"=>"Cisarua"),
										array("area_id"=>"5002229","city"=>"Depok"),
										array("area_id"=>"5002288","city"=>"Gadog"),
										array("area_id"=>"501224","city"=>"Garut"),
										array("area_id"=>"501225","city"=>"Indramayu"),
										array("area_id"=>"501226","city"=>"Karawang"),
										array("area_id"=>"5002227","city"=>"Kota Bogor"),
										array("area_id"=>"501227","city"=>"Kuningan"),
										array("area_id"=>"501599","city"=>"Lembang"),
										array("area_id"=>"501228","city"=>"Majalengka"),
										array("area_id"=>"5002252","city"=>"Parigi"),
										array("area_id"=>"501229","city"=>"Pelabuhan Ratu"),
										array("area_id"=>"501230","city"=>"Purwakarta"),
										array("area_id"=>"501231","city"=>"Singaparna"),
										array("area_id"=>"501232","city"=>"Soreang"),
										array("area_id"=>"501233","city"=>"Subang"),
										array("area_id"=>"501222","city"=>"Sukabumi"),
										array("area_id"=>"501234","city"=>"Sumber"),
										array("area_id"=>"501234","city"=>"Sumber"),
										array("area_id"=>"501235","city"=>"Sumedang"),
										array("area_id"=>"501236","city"=>"Tasikmalaya"),
									)
						   ),								
				"11"=>array("province"=>"Jawa Tengah","xml"=>"/DigitalForecast-JawaTengah.xml",
							"data_city"=>array(
										array("area_id"=>"501237","city"=>"Banjarnegara"),
										array("area_id"=>"501238","city"=>"Batang"),
										array("area_id"=>"501239","city"=>"Blora"),
										array("area_id"=>"501240","city"=>"Boyolali"),
										array("area_id"=>"501241","city"=>"Brebes"),
										array("area_id"=>"501242","city"=>"Cilacap"),
										array("area_id"=>"501243","city"=>"Demak"),
										array("area_id"=>"501244","city"=>"Jepara"),
										array("area_id"=>"501245","city"=>"Kajen"),
										array("area_id"=>"501246","city"=>"Karanganyar"),
										array("area_id"=>"501247","city"=>"Kebumen"),
										array("area_id"=>"501248","city"=>"Kendal"),
										array("area_id"=>"501249","city"=>"Klaten"),
										array("area_id"=>"501250","city"=>"Kudus"),
										array("area_id"=>"501251","city"=>"Magelang"),
										array("area_id"=>"501252","city"=>"Mungkid"),
										array("area_id"=>"501253","city"=>"Pati"),
										array("area_id"=>"501254","city"=>"Pekalongan"),
										array("area_id"=>"501255","city"=>"Pemalang"),
										array("area_id"=>"501256","city"=>"Purbalingga"),
										array("area_id"=>"501257","city"=>"Purwodadi"),
										array("area_id"=>"501258","city"=>"Purwokerto"),
										array("area_id"=>"501259","city"=>"Purworejo"),
										array("area_id"=>"501260","city"=>"Rembang"),
										array("area_id"=>"501261","city"=>"Salatiga"),
										array("area_id"=>"501262","city"=>"Semarang"),
										array("area_id"=>"501263","city"=>"Slawi"),
										array("area_id"=>"501264","city"=>"Sragen"),
										array("area_id"=>"501265","city"=>"Sukoharjo"),
										array("area_id"=>"501266","city"=>"Surakarta"),
										array("area_id"=>"501267","city"=>"Tegal"),
										array("area_id"=>"501268","city"=>"Temanggung"),
										array("area_id"=>"501269","city"=>"Ungaran"),
										array("area_id"=>"501270","city"=>"Wonogiri"),
										array("area_id"=>"501271","city"=>"Wonosobo"),
									)
						   ),								
				"12"=>array("province"=>"Jawa Timur","xml"=>"/DigitalForecast-JawaTimur.xml",
							"data_city"=>array(
										array("area_id"=>"501272","city"=>"Bangkalan"),
										array("area_id"=>"501273","city"=>"Banyuwangi"),
										array("area_id"=>"501274","city"=>"Batu"),
										array("area_id"=>"501277","city"=>"Bojonegoro"),
										array("area_id"=>"501278","city"=>"Bondowoso"),
										array("area_id"=>"501279","city"=>"Gresik"),
										array("area_id"=>"501280","city"=>"Jember"),
										array("area_id"=>"501281","city"=>"Jombang"),
										array("area_id"=>"5002271","city"=>"Blitar"),
										array("area_id"=>"5002268","city"=>"Kediri"),
										array("area_id"=>"501288","city"=>"Madiun"),
										array("area_id"=>"501284","city"=>"Malang"),
										array("area_id"=>"5002269","city"=>"Mojokerto"),
										array("area_id"=>"5002272","city"=>"Pasuruan"),
										array("area_id"=>"5002270","city"=>"Probolinggo"),
										array("area_id"=>"501275","city"=>"Blitar"),
										array("area_id"=>"501282","city"=>"Kota Kediri"),
										array("area_id"=>"501287","city"=>"Kota Madiun"),
										array("area_id"=>"501290","city"=>"Kota Malang"),
										array("area_id"=>"501291","city"=>"Kota Mojokerto"),
										array("area_id"=>"501297","city"=>"Kota Pasuruan"),
										array("area_id"=>"501300","city"=>"Kota Probolinggo"),
										array("area_id"=>"501285","city"=>"Lamongan"),
										array("area_id"=>"501286","city"=>"Lumajang"),
										array("area_id"=>"501289","city"=>"Magetan"),
										array("area_id"=>"501293","city"=>"Nganjuk"),
										array("area_id"=>"501294","city"=>"Ngawi"),
										array("area_id"=>"501295","city"=>"Pacitan"),
										array("area_id"=>"501296","city"=>"Pamekasan"),
										array("area_id"=>"501299","city"=>"Ponorogo"),
										array("area_id"=>"501302","city"=>"Sampang"),
										array("area_id"=>"501303","city"=>"Sidoarjo"),
										array("area_id"=>"501304","city"=>"Situbondo"),
										array("area_id"=>"501305","city"=>"Sumenep"),
										array("area_id"=>"501306","city"=>"Surabaya"),
										array("area_id"=>"501307","city"=>"Trenggalek"),
										array("area_id"=>"501308","city"=>"Tuban"),
										array("area_id"=>"501309","city"=>"Tulungagung"),
									)
						   ),								
				"13"=>array("province"=>"Kalimantan Barat","xml"=>"/DigitalForecast-KalimantanBarat.xml"),								
				"14"=>array("province"=>"Kalimantan Selatan","xml"=>"/DigitalForecast-KalimantanSelatan.xml"),								
				"15"=>array("province"=>"Kalimantan Tengah","xml"=>"/DigitalForecast-KalimantanTengah.xml"),								
				"16"=>array("province"=>"Kalimantan Timur","xml"=>"/DigitalForecast-KalimantanTimur.xml"),								
				"17"=>array("province"=>"Kalimantan Utara","xml"=>"/DigitalForecast-KalimantanUtara.xml"),								
				"18"=>array("province"=>"Kepulauan Riau","xml"=>"/DigitalForecast-KepulauanRiau.xml"),								
				"19"=>array("province"=>"Lampung","xml"=>"/DigitalForecast-Lampung.xml"),								
				"20"=>array("province"=>"Maluku","xml"=>"/DigitalForecast-Maluku.xml"),								
				"21"=>array("province"=>"Maluku Utara","xml"=>"/DigitalForecast-MalukuUtara.xml"),								
				"22"=>array("province"=>"Nusa Tenggara Barat","xml"=>"/DigitalForecast-NusaTenggaraBarat.xml"),								
				"23"=>array("province"=>"Nusa Tenggara Timur","xml"=>"/DigitalForecast-NusaTenggaraTimur.xml"),								
				"24"=>array("province"=>"Papua","xml"=>"/DigitalForecast-Papua.xml"),								
				"25"=>array("province"=>"Papua Barat","xml"=>"/DigitalForecast-PapuaBarat.xml"),								
				"26"=>array("province"=>"Riau","xml"=>"/DigitalForecast-Riau.xml"),								
				"27"=>array("province"=>"Sulawesi Barat","xml"=>"/DigitalForecast-SulawesiBarat.xml"),								
				"28"=>array("province"=>"Sulawesi Selatan","xml"=>"/DigitalForecast-SulawesiSelatan.xml"),								
				"29"=>array("province"=>"Sulawesi Tengah","xml"=>"/DigitalForecast-SulawesiTengah.xml"),								
				"30"=>array("province"=>"Sulawesi Tenggara","xml"=>"/DigitalForecast-SulawesiTenggara.xml"),								
				"31"=>array("province"=>"Sulawesi Utara","xml"=>"/DigitalForecast-SulawesiUtara.xml"),								
				"32"=>array("province"=>"Sumatera Barat","xml"=>"/DigitalForecast-SumateraBarat.xml"),								
				"33"=>array("province"=>"Sumatera Selatan","xml"=>"/DigitalForecast-SumateraSelatan.xml"),								
				"34"=>array("province"=>"Sumatera Utara","xml"=>"/DigitalForecast-SumateraUtara.xml"),														  
				);
		
		$ret = array();
		if(!empty($province_id)){
			$ret = $list[$province_id];
		} else {
			$ret = $list;
		}

		return $ret;
	}	
	
	public function weather($province_id = "", $area_id = ""){
		$arrProvince = $this->province_list($province_id);
		
		$xmlPath = isset($arrProvince['xml'])?$arrProvince['xml']:"";
		if(empty($xmlPath)){
			$xmlPath = $arrProvince['07']['xml'];
		}
		
		$remote_url = $this->base_url_data_weather.$xmlPath;
		
		$result = array();
		$data = $this->remote_data($remote_url);
		$arrData = array();
		
		if(!$data){
			$result['status']     = "error";
			$result['message']    = "offline";
			$result['timestamp']  = time();
		} else {
			$result['status']     = "success";
			$result['timestamp']  = time();
			
			$xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
			$arrResult = json_decode( json_encode($xml),TRUE);
			
			$issue 				= isset($arrResult['forecast']['issue'])?$arrResult['forecast']['issue']:array();
			$areas 				= isset($arrResult['forecast']['area'])?$arrResult['forecast']['area']:array();
			
			$result['updated']  = isset($issue['timestamp'])?$issue['timestamp']:"";
			
			if(count($areas) > 0){
				foreach($areas as $key => $area){
					$id 	= isset($area['@attributes']['id'])?$area['@attributes']['id']:"";
					
					if($area_id == $id || empty($area_id)){
						$arrData[$key]['area_id'] 	= isset($area['@attributes']['id'])?$area['@attributes']['id']:"";
						$arrData[$key]['province'] 	= isset($area['@attributes']['domain'])?$area['@attributes']['domain']:"";
						$arrData[$key]['city'] 		= isset($area['name'][0])?$area['name'][0]:"";
						
						$arrParameter 	= isset($area['parameter'])?$area['parameter']:array();

						if(count($arrParameter) > 0){
							foreach($arrParameter as $idx => $parameter){
								/* echo "<pre>";
								print_r($parameter);
								echo "</pre>"; */

								if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="weather"){
									$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();
									
									$index = 0;
									foreach($timeranges as $k => $timerange){
										$datetime = $timerange['@attributes']['datetime'];
										$date = substr($datetime,0,-4);
										$datetmp = "";
										if($k > 0){
											$datetmp = substr($timeranges[$k-1]['@attributes']['datetime'],0,-4);
										}
										
										if($date != $datetmp){
											$index = 0;
										}

										$h = intval($timerange['@attributes']['h']);
										$value = isset($timerange['value'])?intval($timerange['value']):0;

										$arrData[$key]['weather'][$date][$index]['value'] = $value;
										$arrData[$key]['weather'][$date][$index]['desc'] = $this->value_weather($value);
										$arrData[$key]['weather'][$date][$index]['greetings'] = $this->value_greeting($h);
										$arrData[$key]['weather'][$date][$index]['highlight'] = $this->value_setnow($date,$h);
										$arrData[$key]['weather'][$date][$index]['hour'] = $this->value_hconvert($h);
										$index++;
									}
								}
								
								if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="t"){
									$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();
									
									$index = 0;
									foreach($timeranges as $k => $timerange){
										$datetime = $timerange['@attributes']['datetime'];
										$date = substr($datetime,0,-4);
										$datetmp = "";
										if($k > 0){
											$datetmp = substr($timeranges[$k-1]['@attributes']['datetime'],0,-4);
										}
										
										if($date != $datetmp){
											$index = 0;
										}
										
										$h = intval($timerange['@attributes']['h']);
										$celcius = isset($timerange['value'][0])?intval($timerange['value'][0]):0;
										$fahrenheit = isset($timerange['value'][1])?intval($timerange['value'][1]):0;
										
										$arrData[$key]['temperature'][$date][$index]['celcius'] = $celcius;
										$arrData[$key]['temperature'][$date][$index]['fahrenheit'] = $fahrenheit;
										$arrData[$key]['temperature'][$date][$index]['greetings'] = $this->value_greeting($h);
										$arrData[$key]['temperature'][$date][$index]['highlight'] = $this->value_setnow($date,$h);
										$arrData[$key]['temperature'][$date][$index]['hour'] = $this->value_hconvert($h);
										$index++;
									}
								}
								
								if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="hu"){
									$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();
									
									$index = 0;
									foreach($timeranges as $k => $timerange){
										$datetime = $timerange['@attributes']['datetime'];
										$date = substr($datetime,0,-4);
										$datetmp = "";
										if($k > 0){
											$datetmp = substr($timeranges[$k-1]['@attributes']['datetime'],0,-4);
										}
										
										if($date != $datetmp){
											$index = 0;
										}
										
										$h = intval($timerange['@attributes']['h']);
										$value = isset($timerange['value'])?intval($timerange['value']):0;
										
										$arrData[$key]['humidity'][$date][$index]['value'] = $value;
										$arrData[$key]['humidity'][$date][$index]['greetings'] = $this->value_greeting($h);
										$arrData[$key]['humidity'][$date][$index]['highlight'] = $this->value_setnow($date,$h);
										$arrData[$key]['humidity'][$date][$index]['hour'] = $this->value_hconvert($h);
										$index++;
									}
								}
								
								if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="ws"){
									$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();
									
									$index = 0;
									foreach($timeranges as $k => $timerange){
										$datetime = $timerange['@attributes']['datetime'];
										$date = substr($datetime,0,-4);
										$datetmp = "";
										if($k > 0){
											$datetmp = substr($timeranges[$k-1]['@attributes']['datetime'],0,-4);
										}
										
										if($date != $datetmp){
											$index = 0;
										}
										
										$h = intval($timerange['@attributes']['h']);
										$kph = isset($timerange['value'][2])?$timerange['value'][2]:0;
										
										$arrData[$key]['wind_speed'][$date][$index]['kph'] = $kph;
										$arrData[$key]['wind_speed'][$date][$index]['greetings'] = $this->value_greeting($h);
										$arrData[$key]['wind_speed'][$date][$index]['highlight'] = $this->value_setnow($date,$h);
										$arrData[$key]['wind_speed'][$date][$index]['hour'] = $this->value_hconvert($h);
										$index++;
									}
								}
								
								if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="wd"){
									$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();
									
									$index = 0;
									foreach($timeranges as $k => $timerange){
										$datetime = $timerange['@attributes']['datetime'];
										$date = substr($datetime,0,-4);
										$datetmp = "";
										if($k > 0){
											$datetmp = substr($timeranges[$k-1]['@attributes']['datetime'],0,-4);
										}
										
										if($date != $datetmp){
											$index = 0;
										}
										
										$h = intval($timerange['@attributes']['h']);
										$value = isset($timerange['value'][1])?$timerange['value'][1]:0;
										
										$arrData[$key]['wind_direction'][$date][$index]['value'] = $value;
										$arrData[$key]['wind_direction'][$date][$index]['desc'] = $this->value_wind_direction($value);
										$arrData[$key]['wind_direction'][$date][$index]['greetings'] = $this->value_greeting($h);
										$arrData[$key]['wind_direction'][$date][$index]['highlight'] = $this->value_setnow($date,$h);
										$arrData[$key]['wind_direction'][$date][$index]['hour'] = $this->value_hconvert($h);
										$index++;
									}
								}
							}
						}
					}

					
					/* echo "<pre>";
					print_r($arrData);
					echo "</pre>"; */
				}
			}
			
			$result['data'] = array_values($arrData);
		}

		return $result;
    }
	
	public function weather_html($province_id = "", $area_id = ""){
		$remote_url = "https://www.bmkg.go.id/cuaca/prakiraan-cuaca.bmkg";
		$id = "";
		
		$result = array();
		$data = $this->remote_data($remote_url);
		$arrData = array();

		if(empty($province_id)){
			$province_id = "07";
		}
		$arrProvince = $this->province_list($province_id);
		if(count($arrProvince)>0){
			$arrCity = isset($arrProvince['data_city'])?$arrProvince['data_city']:array();
			
			if(!empty($area_id)){
				if(array_search($area_id, array_column($arrCity, 'area_id')) !== false) {
					$keyAreaId = array_search($area_id, array_column($arrCity, 'area_id'));
					$arrCity = array($arrCity[$keyAreaId]);
				}	
			}
			
			if(count($arrCity)>0){
				foreach($arrCity as $keyCity => $valCity){
					$id = isset($valCity['area_id'])?$valCity['area_id']:"";
					if(!empty($id)){
						$remote_area_url 	= $remote_url."?AreaID=".$id."";
						$data 				= $this->remote_data($remote_area_url);
						$html               = str_get_html($data);

						if(stripos($html, 'TabPaneCuaca1') === false && stripos($html, 'TabPaneCuaca2') === false && stripos($html, 'TabPaneCuaca3') === false){
							$result['status']     = "error";
							$result['message']    = "offline";
							$result['timestamp']  = time();
							break;
						} else {
							$buff                 = array();
							$table                = array();
							$idx                  = 0;
							$index                = 0;
							$arrHtml 			  = array();
							
							$buff[$idx] = $html->find('div[id="TabPaneCuaca1"]', 0);
							if(isset($buff[$idx])){
								$divCuacaFlexs = $buff[$idx]->find('div[class="cuaca-flex"]');
								
								foreach($divCuacaFlexs as $y => $cuacaflex){
									$divCaraouselBlockTables = $cuacaflex->find('div[class="carousel-block-table"]');
									
									foreach($divCaraouselBlockTables as $z => $divTables){
										$table[$z] = $divTables;
										$idx++;
									}
									
									$arrHtml[$index] = $table;
									$index++;
								}
							}
							
							$buff[$idx] = $html->find('div[id="TabPaneCuaca2"]', 0);
							if(isset($buff[$idx])){
								$divCuacaFlexs = $buff[$idx]->find('div[class="cuaca-flex"]');
								
								foreach($divCuacaFlexs as $y => $cuacaflex){
									$divCaraouselBlockTables = $cuacaflex->find('div[class="carousel-block-table"]');
									
									foreach($divCaraouselBlockTables as $z => $divTables){
										$table[$z] = $divTables;
										$idx++;
									}
									
									$arrHtml[$index] = $table;
									$index++;
								}
							}
							
							$buff[$idx] = $html->find('div[id="TabPaneCuaca3"]', 0);
							if(isset($buff[$idx])){
								$divCuacaFlexs = $buff[$idx]->find('div[class="cuaca-flex"]');
								
								foreach($divCuacaFlexs as $y => $cuacaflex){
									$divCaraouselBlockTables = $cuacaflex->find('div[class="carousel-block-table"]');
									
									foreach($divCaraouselBlockTables as $z => $divTables){
										$table[$z] = $divTables;
										$idx++;
									}
									
									$arrHtml[$index] = $table;
									$index++;
								}
							}
							
							if(count($arrHtml) > 0){
								$province = "";
								$city = "";
								$content = $html->find('div[class="content"]', 0);
								if(isset($content)){
									$strCity = $content->find('h2', 0);
									if(!empty($strCity)){
										$strCity = html_entity_decode(strip_tags($strCity));
										
										$city = trim(str_replace("Kabupaten","", $strCity));
									}
									
									$strProvince = $content->find('h4[class="margin-bottom-30"]', 0);
									if(!empty($strProvince)){
										$strProvince = html_entity_decode(strip_tags($strProvince));
										$arrProvince = explode("-",$strProvince);

										if(count($arrProvince) > 1){
											$city = trim(str_replace("Kabupaten","", $arrProvince[0]));
											$province = trim(str_replace("Provinsi","", $arrProvince[1]));
										} else {
											$province = trim(str_replace("Provinsi","", $strProvince));
										}
									}	
								}

								$arrData[$keyCity]['area_id'] = $id;
								$arrData[$keyCity]['province'] = $province;
								$arrData[$keyCity]['city'] = $city;
								
								$arrDate = array();
								for($i=0; $i<$index; $i++){
									array_push($arrDate,  date('Y-m-d', strtotime('+'.$i.' day')));
								}
								
								foreach($arrHtml as $a => $arrDiv){
									foreach($arrDiv as $b => $val){
										if(isset($val)){
											$strTime = $val->find('h2[class="kota"]', 0);
											$strWeather = $val->find('p', 0);
											$strTemperature = $val->find('h2[class="heading-md"]', 0);
											$strHumidity = $val->find('p', 1);
											$strWind = $val->find('p', 2);
											
											$time = "";
											if(!empty($strTime)){
												$strTime = html_entity_decode(strip_tags($strTime));
												$time = intval(trim(str_replace("WIB","",$strTime)));
											}
											
											$weather = "";
											if(!empty($strWeather)){
												$weather = html_entity_decode(strip_tags($strWeather));
											}
											
											$celcius = "";
											if(!empty($strTemperature)){
												$strTemperature = html_entity_decode(strip_tags($strTemperature));
												$celcius = trim(str_replace("°C","",$strTemperature));
											}
											
											$humidity = "";
											if(!empty($strHumidity)){
												$strHumidity = html_entity_decode(strip_tags($strHumidity));
												$humidity = trim(str_replace("%","",$strHumidity));
											}
											
											$wind_speed = "";
											$wind_direction = "";
											if(!empty($strWind)){
												$strWind = strip_tags($strWind);
												$arrWind = explode("km/jam",$strWind);
												
												$wind_speed = trim($arrWind[0]);
												$wind_direction = trim($arrWind[1]);
											}
											
											$arrData[$keyCity]['humidity'][$arrDate[$a]][$b]['value'] = $humidity;
											$arrData[$keyCity]['humidity'][$arrDate[$a]][$b]['greetings'] = $this->value_greeting($time);
											$arrData[$keyCity]['humidity'][$arrDate[$a]][$b]['hour'] = $time;
											
											$arrData[$keyCity]['temperature'][$arrDate[$a]][$b]['celcius'] = $celcius;
											$arrData[$keyCity]['temperature'][$arrDate[$a]][$b]['greetings'] = $this->value_greeting($time);
											$arrData[$keyCity]['temperature'][$arrDate[$a]][$b]['hour'] = $time;
											
											$arrData[$keyCity]['weather'][$arrDate[$a]][$b]['desc'] = $weather;
											$arrData[$keyCity]['weather'][$arrDate[$a]][$b]['greetings'] = $this->value_greeting($time);
											$arrData[$keyCity]['weather'][$arrDate[$a]][$b]['hour'] = $time;
											
											$arrData[$keyCity]['wind_direction'][$arrDate[$a]][$b]['desc'] = $wind_direction;
											$arrData[$keyCity]['wind_direction'][$arrDate[$a]][$b]['greetings'] = $this->value_greeting($time);
											$arrData[$keyCity]['wind_direction'][$arrDate[$a]][$b]['hour'] = $time;
											
											$arrData[$keyCity]['wind_speed'][$arrDate[$a]][$b]['kph'] = $wind_speed;
											$arrData[$keyCity]['wind_speed'][$arrDate[$a]][$b]['greetings'] = $this->value_greeting($time);
											$arrData[$keyCity]['wind_speed'][$arrDate[$a]][$b]['hour'] = $time;
										}	
									}
								}
							}
						}
					}
				}
				
				/* echo "<pre>";
				print_r($arrData);
				echo "</pre>"; */
				
				$result['status']     = "success";
				$result['timestamp']  = time();
				$result['updated']    = date("YmdHis");
				$result['data'] = array_values($arrData);
			}
		}		
		
		
		return $result; 
    }
}

?>