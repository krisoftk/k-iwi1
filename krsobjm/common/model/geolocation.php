<?php
class ModelCommonGeolocation extends Model {
	
	public function ip_api($address) {
		$curl = curl_init('http://ip-api.com/json/' . $address);
			
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			
		$response = curl_exec($curl);
			
		curl_close($curl);
			
		$risk_score = 0;
			
		if ($response) {
			
		}
	}
}