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
				$str = "Dawn";
				break;
			case 6:
				$str = "Morning";
				break;
			case 12:
				$str = "Daylight";
				break;
			case 18:
				$str = "Night";
				break;
			case 24:
				$str = "Dawn";
				break;
			case 30:
				$str = "Morning";
				break;
			case 36:
				$str = "Daylight";
				break;
			case 42:
				$str = "Night";
			case 48:
				$str = "Dawn";
				break;
			case 54:
				$str = "Morning";
				break;
			case 60:
				$str = "Daylight";
				break;
			case 66:
				$str = "Night";
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
	
	private function province_list($province_id = ""){
		$list = array(
				"01"=>array("Aceh","/DigitalForecast-Aceh.xml"),								
				"02"=>array("Bali","/DigitalForecast-Bali.xml"),								
				"03"=>array("Bangka Belitung","/DigitalForecast-BangkaBelitung.xml"),								
				"04"=>array("Banten","/DigitalForecast-Banten.xml"),								
				"05"=>array("Bengkulu","/DigitalForecast-Bengkulu.xml"),								
				"06"=>array("DI Yogyakarta","/DigitalForecast-DIYogyakarta.xml"),								
				"07"=>array("DKI Jakarta","/DigitalForecast-DKIJakarta.xml"),								
				"08"=>array("Gorontalo","/DigitalForecast-Gorontalo.xml"),								
				"09"=>array("Jambi","/DigitalForecast-Jambi.xml"),								
				"10"=>array("Jawa Barat","/DigitalForecast-JawaBarat.xml"),								
				"11"=>array("Jawa Tengah","/DigitalForecast-JawaTengah.xml"),								
				"12"=>array("Jawa Timur","/DigitalForecast-JawaTimur.xml"),								
				"13"=>array("Kalimantan Barat","/DigitalForecast-KalimantanBarat.xml"),								
				"14"=>array("Kalimantan Selatan","/DigitalForecast-KalimantanSelatan.xml"),								
				"15"=>array("Kalimantan Tengah","/DigitalForecast-KalimantanTengah.xml"),								
				"16"=>array("Kalimantan Timur","/DigitalForecast-KalimantanTimur.xml"),								
				"17"=>array("Kalimantan Utara","/DigitalForecast-KalimantanUtara.xml"),								
				"18"=>array("Kepulauan Riau","/DigitalForecast-KepulauanRiau.xml"),								
				"19"=>array("Lampung","/DigitalForecast-Lampung.xml"),								
				"20"=>array("Maluku","/DigitalForecast-Maluku.xml"),								
				"21"=>array("Maluku Utara","/DigitalForecast-MalukuUtara.xml"),								
				"22"=>array("Nusa Tenggara Barat","/DigitalForecast-NusaTenggaraBarat.xml"),								
				"23"=>array("Nusa Tenggara Timur","/DigitalForecast-NusaTenggaraTimur.xml"),								
				"24"=>array("Papua","/DigitalForecast-Papua.xml"),								
				"25"=>array("Papua Barat","/DigitalForecast-PapuaBarat.xml"),								
				"26"=>array("Riau","/DigitalForecast-Riau.xml"),								
				"27"=>array("Sulawesi Barat","/DigitalForecast-SulawesiBarat.xml"),								
				"28"=>array("Sulawesi Selatan","/DigitalForecast-SulawesiSelatan.xml"),								
				"29"=>array("Sulawesi Tengah","/DigitalForecast-SulawesiTengah.xml"),								
				"30"=>array("Sulawesi Tenggara","/DigitalForecast-SulawesiTenggara.xml"),								
				"31"=>array("Sulawesi Utara","/DigitalForecast-SulawesiUtara.xml"),								
				"32"=>array("Sumatera Barat","/DigitalForecast-SumateraBarat.xml"),								
				"33"=>array("Sumatera Selatan","/DigitalForecast-SumateraSelatan.xml"),								
				"34"=>array("Sumatera Utara","/DigitalForecast-SumateraUtara.xml"),								
				"35"=>array("Indonesia","/DigitalForecast-Indonesia.xml")							  
				);
		
		$ret = array();
		if(!empty($province_id)){
			$ret = $list[$province_id];
		} else {
			$ret = $list["35"];
		}

		return $ret;
	}
	
	public function weather($province_id = "", $selected_city = ""){
		$provice = $this->province_list($province_id);
		$remote_url = $this->base_url_data_weather.$provice[1];
		
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
									$index++;
								}
							}
							
							if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="tmin"){
								$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();
								
								foreach($timeranges as $k => $timerange){
									$date = substr($timerange['@attributes']['datetime'],0,-4);
									$celcius = isset($timerange['value'][0])?intval($timerange['value'][0]):0;
									$fahrenheit = isset($timerange['value'][1])?intval($timerange['value'][1]):0;
									
									$arrData[$key]['temperature_min_celcius'][$date] = $celcius;
									$arrData[$key]['temperature_min_fahrenheit'][$date] = $fahrenheit;
								}
							}
							
							if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="tmax"){
								$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();

								foreach($timeranges as $k => $timerange){
									$date = substr($timerange['@attributes']['datetime'],0,-4);
									$celcius = isset($timerange['value'][0])?intval($timerange['value'][0]):0;
									$fahrenheit = isset($timerange['value'][1])?intval($timerange['value'][1]):0;
									
									$arrData[$key]['temperature_max_celcius'][$date] = $celcius;
									$arrData[$key]['temperature_max_fahrenheit'][$date] = $fahrenheit;
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
									$index++;
								}
							}
							
							if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="humin"){
								$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();
								
								foreach($timeranges as $k => $timerange){
									$date = substr($timerange['@attributes']['datetime'],0,-4);
									$value = isset($timerange['value'])?intval($timerange['value']):0;
									
									$arrData[$key]['humidity_min'][$date] = $value;
								}
							}
							
							if(isset($parameter['@attributes']['id']) && $parameter['@attributes']['id']=="humax"){
								$timeranges = isset($parameter['timerange'])?$parameter['timerange']:array();
								
								foreach($timeranges as $k => $timerange){
									$date = substr($timerange['@attributes']['datetime'],0,-4);
									$value = isset($timerange['value'])?intval($timerange['value']):0;
									
									$arrData[$key]['humidity_max'][$date] = $value;
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
									$knot = isset($timerange['value'][0])?$timerange['value'][0]:0;
									$mph = isset($timerange['value'][1])?$timerange['value'][1]:0;
									$kph = isset($timerange['value'][2])?$timerange['value'][2]:0;
									
									$arrData[$key]['wind_speed'][$date][$index]['knot'] = $knot;
									$arrData[$key]['wind_speed'][$date][$index]['mph'] = $mph;
									$arrData[$key]['wind_speed'][$date][$index]['kph'] = $kph;
									$arrData[$key]['wind_speed'][$date][$index]['greetings'] = $this->value_greeting($h);
									$arrData[$key]['wind_speed'][$date][$index]['highlight'] = $this->value_setnow($date,$h);
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
									$index++;
								}
							}
						}
					}
				}
			}
			
			$result['data'] = $arrData;
		}

		return $result;
    }
	
	private function latlon()
    {
		$location = array(
            "Banda_Aceh" => array(
				"id"=>"01",
                "lat" => 5.5482904,
                "lon" => 95.3237559
            ),
            "Medan" => array(
				"id"=>"34",
                "lat" => 3.5951956,
                "lon" => 98.6722227
            ),
            "Pekanbaru" => array(
				"id"=>"26",
                "lat" => 0.5070677,
                "lon" => 101.4477793
            ),
            "Batam" => array(
				"id"=>"18",
                "lat" => 1.0456264,
                "lon" => 104.0304535
            ),
            "Padang" => array(
				"id"=>"32",
                "lat" => -0.9470832,
                "lon" => 100.417181
            ),
            "Jambi" => array(
				"id"=>"09",
                "lat" => -1.6101229,
                "lon" => 103.6131203
            ),
            "Palembang" => array(
				"id"=>"33",
                "lat" => -2.9760735,
                "lon" => 104.7754307
            ),
            "Pangkal_Pinang" => array(
				"id"=>"03",
                "lat" => -2.1316266,
                "lon" => 106.1169299
            ),
            "Bengkulu" => array(
				"id"=>"05",
                "lat" => -3.7928451,
                "lon" => 102.2607641
            ),
            "Bandar_Lampung" => array(
				"id"=>"19",
                "lat" => -5.3971396,
                "lon" => 105.2667887
            ),
            "Pontianak" => array(
				"id"=>"13",
                "lat" => -0.0263303,
                "lon" => 109.3425039
            ),
            "Samarinda" => array(
				"id"=>"16",
                "lat" => -0.4948232,
                "lon" => 117.1436154
            ),
            "Palangkaraya" => array(
				"id"=>"15",
                "lat" => -2.2161048,
                "lon" => 113.913977
            ),
            "Banjarmasin" => array(
				"id"=>"14",
                "lat" => -3.3186067,
                "lon" => 114.5943784
            ),
            "Manado" => array(
				"id"=>"31",
                "lat" => 1.4748305,
                "lon" => 124.8420794
            ),
            "Gorontalo" => array(
				"id"=>"08",
                "lat" => 0.5435442,
                "lon" => 123.0567693
            ),
            "Palu" => array(
				"id"=>"29",
                "lat" => -0.9002915,
                "lon" => 119.8779987
            ),
            "Kendari" => array(
				"id"=>"30",
                "lat" => -3.9984597,
                "lon" => 122.5129742
            ),
            "Makassar" => array(
				"id"=>"28",
                "lat" => -5.1476651,
                "lon" => 119.4327314
            ),
            "Majene" => array(
				"id"=>"27",
                "lat" => -3.0297251,
                "lon" => 118.9062794
            ),
            "Ternate" => array(
				"id"=>"21",
                "lat" => 0.7898868,
                "lon" => 127.3753792
            ),
            "Ambon" => array(
				"id"=>"20",
                "lat" => -3.6553932,
                "lon" => 128.1907723
            ),
            "Jayapura" => array(
				"id"=>"24",
                "lat" => -2.5916025,
                "lon" => 140.6689995
            ),
            "Sorong" => array(
				"id"=>"25",
                "lat" => -0.8819986,
                "lon" => 131.2954834
            ),
            "Biak" => array(
				"id"=>"24",
                "lat" => -1.0381022,
                "lon" => 135.9800848
            ),
            "Manokwari" => array(
				"id"=>"25",
                "lat" => -0.8614531,
                "lon" => 134.0620421
            ),
            "Merauke" => array(
				"id"=>"24",
                "lat" => -8.4991117,
                "lon" => 140.4049814
            ),
            "Kupang" => array(
				"id"=>"23",
                "lat" => -10.1771997,
                "lon" => 123.6070329
            ),
            "Sumbawa_Besar" => array(
				"id"=>"22",
                "lat" => -8.504043,
                "lon" => 117.428497
            ),
            "Mataram" => array(
				"id"=>"22",
                "lat" => -8.5769951,
                "lon" => 116.1004894
            ),
            "Denpasar" => array(
				"id"=>"02",
                "lat" => -8.6704582,
                "lon" => 115.2126293
            ),
            "Jakarta" => array(
				"id"=>"07",
                "lat" => -6.2087634,
                "lon" => 106.845599
            ),
            "Jakarta_Pusat" => array(
				"id"=>"07",
                "lat" => -6.2087634,
                "lon" => 106.845599
            ),
            "Serang" => array(
				"id"=>"04",
                "lat" => -6.1103661,
                "lon" => 106.1639749
            ),
            "Bandung" => array(
				"id"=>"10",
                "lat" => -6.9174639,
                "lon" => 107.6191228
            ),
            "Semarang" => array(
				"id"=>"11",
                "lat" => -7.0051453,
                "lon" => 110.4381254
            ),
            "Yogyakarta" => array(
				"id"=>"06",
                "lat" => -7.7955798,
                "lon" => 110.3694896
            ),
            "Surabaya" => array(
				"id"=>"12",
                "lat" => -7.2574719,
                "lon" => 112.7520883
            )
        );
		return $location;
    }
	
	public function weather_html(){
		$remote_url = "https://www.bmkg.go.id/cuaca/prakiraan-cuaca.bmkg";
		
		$result = array();
		$data = $this->remote_data($remote_url);
		$location = $this->latlon();
		$arrData = array();
		
		if(!$data){
			$result['status']     = "error";
			$result['message']    = "offline";
			$result['timestamp']  = time();
		} else {
			$html                 = str_get_html($data);
			
			if(stripos($html, 'TabPaneCuaca1') === false && stripos($html, 'TabPaneCuaca2') === false && stripos($html, 'TabPaneCuaca3') === false){
				$result['status']     = "error";
				$result['message']    = "invalid_data";
				$result['timestamp']  = time();
			} else {
				$result['status']     = "success";
				$result['timestamp']  = time();
				
				$buff                 = array();
				$table                = array();
				$idx                  = 0;
				$index                = 0;
				$arrHtml 			  = array();
				
				$buff[$idx] = $html->find('div[id="TabPaneCuaca1"]', 0);
				if(isset($buff[$idx])){
					$divCuacaFlexs = $buff[$idx]->find('div[class="cuaca-flex"]');
					
					foreach($divCuacaFlexs as $a => $cuacaflex){
						$divCaraouselBlockTables = $cuacaflex->find('div[class="carousel-block-table"]');
						
						foreach($divCaraouselBlockTables as $b => $divTables){
							$table[$idx] = $divTables;
							$idx++;
						}
						
						$arrHtml[$index] = $table;
						$index++;
					}
				}
				
				$buff[$idx] = $html->find('div[id="TabPaneCuaca2"]', 0);
				if(isset($buff[$idx])){
					$divCuacaFlexs = $buff[$idx]->find('div[class="cuaca-flex"]');
					
					foreach($divCuacaFlexs as $a => $cuacaflex){
						$divCaraouselBlockTables = $cuacaflex->find('div[class="carousel-block-table"]');
						
						foreach($divCaraouselBlockTables as $b => $divTables){
							$table[$idx] = $divTables;
							$idx++;
						}
						
						$arrHtml[$index] = $table;
						$index++;
					}
				}
				
				$buff[$idx] = $html->find('div[id="TabPaneCuaca3"]', 0);
				if(isset($buff[$idx])){
					$divCuacaFlexs = $buff[$idx]->find('div[class="cuaca-flex"]');
					
					foreach($divCuacaFlexs as $a => $cuacaflex){
						$divCaraouselBlockTables = $cuacaflex->find('div[class="carousel-block-table"]');
						
						foreach($divCaraouselBlockTables as $b => $divTables){
							$table[$idx] = $divTables;
							$idx++;
						}
						
						$arrHtml[$index] = $table;
						$index++;
					}
				} 
				
				$arrData = array();
				
				if(count($arrHtml) > 0){
					$province = "";
					$city = "";
					$content = $html->find('div[class="content"]', 0);
					if(isset($content)){
						$strProvinceArea = $html->find('h4[class="margin-bottom-30"]', 0);
						if(!empty($strProvinceArea)){
							$strProvinceArea = html_entity_decode(strip_tags($strProvinceArea));
							$arrProvinceArea = explode("-",$strProvinceArea);
							
							$city = trim($arrProvinceArea[0]);
							$province = trim($arrProvinceArea[1]);
						}	
					}
					
					$arrData['province'] = $province;
					$arrData['city'] = $city;
					
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
									$time = trim(str_replace("WIB","",$strTime));
								}
								
								$weather = "";
								if(!empty($strWeather)){
									$weather = html_entity_decode(strip_tags($strWeather));
								}
								
								$temperature = "";
								if(!empty($strTemperature)){
									$strTemperature = html_entity_decode(strip_tags($strTemperature));
									$temperature = trim(str_replace("°C","",$strTemperature));
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
								
								$arrData[$arrDate[$a]]['time'][$b] = $time;
								$arrData[$arrDate[$a]]['temperature'][$b] = $temperature;
								$arrData[$arrDate[$a]]['weather'][$b] = $weather;
								$arrData[$arrDate[$a]]['wind_speed'][$b] = $wind_speed;
								$arrData[$arrDate[$a]]['wind_direction'][$b] = $wind_direction;
								$arrData[$arrDate[$a]]['humidity'][$b] = $humidity;
							}	
						}
					}
				}
				
				
				/* if($index > 0){
					$province = "";
					$city = "";
					$content = $html->find('div[class="content"]', 0);
					if(isset($content)){
						$strProvinceArea = $html->find('h4[class="margin-bottom-30"]', 0);
						if(!empty($strProvinceArea)){
							$strProvinceArea = html_entity_decode(strip_tags($strProvinceArea));
							$arrProvinceArea = explode("-",$strProvinceArea);
							
							$city = trim($arrProvinceArea[0]);
							$province = trim($arrProvinceArea[1]);
						}	
					}
					
					$arrData['province'] = $province;
					$arrData['city'] = $city;
					
					$arrDate = array();
					for($i=0; $i<$index; $i++){
						array_push($arrDate,  date('Y-m-d', strtotime('+'.$i.' day')));
					}
					
					foreach($table as $k => $val){
						if(isset($val)){
							$strTime = $val->find('h2[class="kota"]', 0);
							$strWeather = $val->find('p', 0);
							$strTemperature = $val->find('h2[class="heading-md"]', 0);
							$strHumidity = $val->find('p', 1);
							$strWind = $val->find('p', 2);
							
							$time = "";
							if(!empty($strTime)){
								$strTime = html_entity_decode(strip_tags($strTime));
								$time = trim(str_replace("WIB","",$strTime));
							}
							
							$weather = "";
							if(!empty($strWeather)){
								$weather = html_entity_decode(strip_tags($strWeather));
							}
							
							$temperature = "";
							if(!empty($strTemperature)){
								$strTemperature = html_entity_decode(strip_tags($strTemperature));
								$temperature = trim(str_replace("°C","",$strTemperature));
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
							
							$arrData['temperature'] = $temperature;
							$arrData['weather'] = $weather;
							$arrData['wind_speed'] = $wind_speed;
							$arrData['wind_direction'] = $wind_direction;
							$arrData['humidity'] = $humidity;
						} 
					}
				} */
				
				echo "<pre>";
				print_r($arrData);
				echo "</pre>";
				
				/* echo "<hr>";
				
				$tab2 = $html->find('div[id="TabPaneCuaca2"]', 0);
				if(isset($tab2)){
					$table2 = $tab2->find('div[class="cuaca-flex"]', 0);
					echo $table2;
				}
				
				$tab3 = $html->find('div[id="TabPaneCuaca3"]', 0);
				if(isset($tab3)){
					$table3 = $tab3->find('div[class="cuaca-flex"]', 0);
					echo $table3;
				} */
			}	
			
			return $result;
		}	
    }
}

?>