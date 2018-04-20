<?php

if ($_SERVER['HTTP_HOST'] == "localhost:8888") $database_connect = mysqli_connect('localhost', 'root', 'root'); //localhost
else $database_connect = mysqli_connect('localhost', 'root', 'Blrrd2017**'); //production

if (!$database_connect) { 
	header('HTTP/ 400 HOST ERROR', true, 400);
		
	$json_status = 'host not connected';
    $json_output[] = array('status' => $json_status, 'error_code' => '302');
	echo json_encode($json_output);
	exit;
	
} 

$database_table = mysqli_select_db($database_connect, "blrrd");
if (!$database_table) { 
	header('HTTP/ 400 DATABASE ERROR', true, 400);
			
	$json_status = 'database table not found';
    $json_output[] = array('status' => $json_status, 'error_code' => '302');
	echo json_encode($json_output);
	exit;
	
}

$session_headers = $_SERVER;
$session_ip = $_SERVER['REMOTE_ADDR'];
$session_url =  $_SERVER["SERVER_NAME"] . reset(explode('?', $_SERVER["REQUEST_URI"]));
$session_page = str_replace(".php", "", basename($session_url));
if (!empty($session_headers["HTTP_BLBEARER"])) $session_bearer = $session_headers["HTTP_BLBEARER"];
else if (!empty($_GET['tok'])) $session_bearer = $_GET['tok'];
$session_language = $session_headers["HTTP_BLLANGUAGE"];
$session_appversion = (float)$session_headers["HTTP_BLAPPVERSION"];
$session_backgroundrequest = (bool)$session_headers["HTTP_BLBACKGROUNDRQST"];
$session_devicename = (string)$session_headers["HTTP_BLDEVICENAME"];
if (!empty($session_headers["HTTP_BLTIMEZONE"])) $session_timezone = (string)$session_headers["HTTP_BLTIMEZONE"];
else $session_timezone = "+0000";
$session_method = $_SERVER['REQUEST_METHOD'];
$session_auth_exclude = array("login", "signup", "reset");

if (!in_array($session_page, $session_auth_exclude)) {
	if (empty($session_bearer)) {	
		header('HTTP/1.1 401 UNAUTHORIZED');

		$json_status = 'bearer token was not passed';
		$json_output[] = array('status' => $json_status, 'error_code' => 401);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$bearer_date = date('Y-m-d H:i:s');
		$bearer_injection =  "SELECT * FROM `access` LEFT JOIN users on access.access_user LIKE users.user_key WHERE `access_expiry` > '$bearer_date' AND `access_token` LIKE '$session_bearer' LIMIT 0, 1";
		$bearer_query = mysqli_query($database_connect, $bearer_injection);
		$bearer_isvalid = mysqli_num_rows($bearer_query);
		if ($bearer_isvalid == 0) {
			header('HTTP/1.1 401 UNAUTHORIZED');

			$json_status = 'bearer token invalid';
			$json_output[] = array('status' => $json_status, 'error_code' => 401);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$authorized_data = mysqli_fetch_assoc($bearer_query);
			$authorized_user = $authorized_data['user_key'];
			$authorized_username = $authorized_data['user_name'];
			$authorized_email = $authorized_data['user_email'];		
			$authorized_phone = $authorized_data['user_phone'];		
			$authorized_displayname = $authorized_data['user_fullname'];				
			$authorized_token = $authorized_data['access_token'];
			$authorized_type = $authorized_data['user_type'];
			$authorized_avatar = $authorized_data['user_avatar'];
			$authorized_signupdate = $authorized_data['user_signup'];
			$authorized_lastactive = $authorized_data['user_lastactive'];	
			$autorized_updated = date('Y-m-d H:i:s');	
			$autorized_userpublic = (bool)$authorized_data['user_public'];		
			$autorized_userpromoted = (bool)$authorized_data['user_promoted'];		
				
			if ($session_backgroundrequest == false) {
				$authuser_update = mysqli_query($database_connect, "UPDATE `users` SET `user_lastactive` = '$autorized_updated', `user_language` = '$session_language' WHERE `user_key` LIKE '$authorized_user';");
				
			}
					
		}
		
	}
	
}

?>