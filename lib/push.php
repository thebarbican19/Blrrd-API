<?php

function submit_device($token) {
	global $authorized_user;
	global $authorized_username;
	
	$push_url = 'https://api.pushbots.com/deviceToken';
	$push_content = array('platform' => '0', 'token' => $token, 'alias' => $authorized_username);
	$push_return = pushbots($push_url, $push_content, "PUT");
	
	return $push_return;
	
}

function sent_push_to_user($user, $payload, $title, $body) {
	global $database_connect;	
	
	$push_user_query = mysqli_query($database_connect, "SELECT `user_name` FROM `users` WHERE `user_key` LIKE '$user' LIMIT 0, 1");
	$push_user_data = mysqli_fetch_assoc($push_user_query);
	$push_user_name = $push_user_data['user_name'];
	if (!is_null($push_user_name)) {
		$push_message = array('body' => $body, 'title' => $title);
		$push_url = 'https://api.pushbots.com/push/all';
		$push_content = array('platform' => '0', 'alias' => $push_user_name, 'payload' => $payload, 'sound' => 'grado_alert.wav' , 'msg' => $push_message, 'mutable-content' => '1');
		$push_return = 	pushbots($push_url, $push_content, "POST");
		
	}
	else $push_return = array("type" => "error", "status" => "user could not be found");
		
	return $push_return;
		
}

function pushbots($api, $data, $method) {
	global $session_debugmode;
	
	$curl_appid = "5a5a6091a5d10304d650b176";
	$curl_secret = "608a645a3cbce98fb6a9a1752e6c135e";
	$curl_data = json_encode($data);
	$curl_headers = array('X-PUSHBOTS-APPID:' . $curl_appid, 'X-PUSHBOTS-SECRET:' . $curl_secret, 'Content-Type: application/json', 'Content-Length: ' . strlen($curl_data));
	$curl_pushbots = curl_init();
	
 	curl_setopt($curl_pushbots, CURLOPT_CONNECTTIMEOUT, 0); 
 	curl_setopt($curl_pushbots, CURLOPT_CONNECTTIMEOUT, 0); 
    curl_setopt($curl_pushbots, CURLOPT_TIMEOUT, 0); 
    curl_setopt($curl_pushbots, CURLOPT_RETURNTRANSFER, TRUE); 
	curl_setopt($curl_pushbots, CURLOPT_HTTPHEADER, $curl_headers);
	curl_setopt($curl_pushbots, CURLOPT_HEADER, FALSE); 
	curl_setopt($curl_pushbots, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl_pushbots, CURLOPT_SSL_VERIFYHOST, 2);		
	if ($method == "POST") {
		 curl_setopt($curl_pushbots, CURLOPT_POST, TRUE); 
		 curl_setopt($curl_pushbots, CURLOPT_POSTFIELDS, $curl_data); 
		 
	}
	elseif ($method == "PUT") {
		curl_setopt($curl_pushbots, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl_pushbots, CURLOPT_POSTFIELDS, $curl_data);
		
	}
	curl_setopt($curl_pushbots, CURLINFO_HEADER_OUT, TRUE); 
	curl_setopt($curl_pushbots, CURLOPT_URL, $api); 
	
	$curl_output = curl_exec($curl_pushbots);
	$curl_response = curl_getinfo($curl_pushbots, CURLINFO_HTTP_CODE); 
	curl_close($curl_pushbots);
		
	return array("status" =>  $curl_response, "data" => $data, 'headers' => $curl_headers, 'output' => $curl_output, 'debug' => $session_debugmode);
	
	
}
