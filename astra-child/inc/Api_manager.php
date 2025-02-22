<?php

defined('ABSPATH') || exit;

class Api_manager{
	private $order_manager;
	private $user_manager;
	private $product;
	
	public function __construct(Order $order_manager, User $user_manager, Product $product){
		$this->order_manager = $order_manager;
		$this->user_manager = $user_manager;
		$this->product = $product;
	}
	
	public function getter($orderid, $userid){
		$url = WSDL_BOT_URL_GET;
		$data = array(
			"mode" => 1,
			"order_number" => $orderid,
			"message" => array(
				"message_id" => 12345,
				"from" => array(
					"id" => $userid,
					"is_bot" => false,
					"first_name" => WSDL_BOT_FM,
					"last_name" => WSDL_BOT_LM,
					"language_code" => "ru"
				)
			)
		);

		$jsonData = json_encode($data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, WSDL_BOT_PASS);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($jsonData)
		));

		$response = curl_exec($ch);

		if(curl_errno($ch)) {
			echo 'Ошибка cURL: ' . curl_error($ch);
			exit;
		} else {
			$decoded_response = json_decode($response, true);
			if ($decoded_response === null && json_last_error() !== JSON_ERROR_NONE) {
				echo "Ошибка декодирования JSON: " . json_last_error_msg() . "\n";
				echo "Сырой ответ:\n" . $response;
			}
		}

		curl_close($ch);
		
		printt(['got data: ', $decoded_response]);
		return $decoded_response;
	}
	
	public function sender($data_sample, $reqtype, $email, $isdemo, $orderid, $userid) {
		$mode = 2;
		
		if (!$isdemo){
			$order = $this->order_manager->get_order();
		} else if ($this->product->slugs['typ']==0){
			$mode = 3;
		}
		$data = array(
				"mode" => $mode,
				"request_type" => $reqtype,
				"order_number" => $orderid,
				"lang" => "ru",
				"email" => "$email",
				"partner" => null,
				"message" => array(
				  "message_id" => 12345,
				  "from" => array(
					"id" => $userid,
					"is_bot" => false,
					"first_name" => WSDL_BOT_FM,
					"last_name" => WSDL_BOT_LM,
					"language_code" => "ru"
				  ),
				  "chat" => array(
					"id" => $userid,
					"first_name" => WSDL_BOT_FM,
					"last_name" => WSDL_BOT_LM,
					"type" => "private"
				  ),
				  "date" => 1636507200,
				  "data_sample" => $data_sample
				)
			  );
			  
			  printt(['sended: ', $data]);
			  $jsonData = json_encode($data, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES);

			  $ch = curl_init();
			  curl_setopt($ch, CURLOPT_URL, WSDL_BOT_URL);
			  curl_setopt($ch, CURLOPT_POST, 1);
			  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
			  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic ' . base64_encode(WSDL_BOT_PASS)));
			  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			  
			  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

			  $server_output = curl_exec($ch);

			  curl_close($ch);
				if ($server_output === false) {
					$error = curl_error($ch);
					echo "<script>console.error(" . json_encode('Ошибка: ' . $error) . ");</script>";
					//exit;
					return false;
				} else {
					if (curl_getinfo($ch, CURLINFO_HTTP_CODE)!= 200){
						$info = curl_getinfo($ch);
						
						curl_close($ch);
						echo "<script>console.log(" . json_encode('Ответ сервера (код ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . '): ' . $server_output) . ");</script>";
						return false;
					}
					echo "<script>console.log(" . json_encode('Ответ сервера (код ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . '): ' . $server_output) . ");</script>";
					//exit;
				}
			return true;
	}
}