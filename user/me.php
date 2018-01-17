<?php

include '../lib/auth.php';
include '../lib/stats.php';
include '../lib/push.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_type = $passed_data['type'];
$passed_value = $passed_data['value'];

if ($authuser_type == "admin" && !empty($passed_data['user'])) $user_key = $passed_data['user'];
else $user_key = $authuser_key;

if ($passed_method == 'GET') {
	$user_stats = user_stats($user_key);			
	$user_output = array("key" => $user_key, "username" => $authorized_username, "email" => $authorized_email, "type" => $authorized_type, "lastactive" => $authorized_lastactive, "signup" => $authorized_signupdate, "stats" => $user_stats, "public" => $autorized_userpublic);
											
	$json_status = 'user data returned';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'user' => $user_output);
	echo json_encode($json_output);
	exit;
						
}
elseif ($passed_method == 'PUT') {
	$allowed_types = array('status' ,'email', 'password', 'avatar', 'language', 'device');
	$allowed_statuses = array('active', 'inactive');
	if (empty($passed_type)) {
		$json_status = 'type parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else if (!in_array($passed_type, $allowed_types)) {
		$json_status = 'type parameter is invalid';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else if (empty($passed_value)) {
		$json_status = 'value parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if ($passed_type == "status") {
			if (!in_array($passed_value, $allowed_statuses)) {
				$json_status = 'status is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else $update_injection = "`user_status` = '" . $passed_value . "'";
					
		}
		elseif ($passed_type == "email") {
			if (filter_var($passed_value, FILTER_VALIDATE_EMAIL) === false) {
				$json_status = 'email is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else $update_injection = "`user_email` = '" . $passed_value . "'";
						
		}
		elseif ($passed_type == "password") {
			if (strlen($passed_value) < 5) {
				$json_status = 'password is too short';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$update_encrypted = password_hash($passed_value ,PASSWORD_BCRYPT);
				$update_injection = "`user_password` = '" . $update_encrypted . "'";	
				
			}
						
		}
		elseif ($passed_type == "device") {
			if (strlen($passed_value) != 64) {
				$json_status = 'device token is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				submit_device($passed_value);
				$update_injection = "`user_device` = '" . $passed_value . "'";	
				
			}	
							
		}
		elseif ($passed_type == "avatar") {
			if (strlen($passed_value) < 10) {
				$json_status = 'avatar invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else $update_injection = "`user_avatar` = '" . $passed_value . "'";	
						
		}
		elseif ($passed_type == "language") {
			if (strlen($passed_value) != 2) {
				$json_status = 'langauge code invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else $update_injection = "`user_language` = '" . $passed_value . "'";	
						
		}
		
		$update_post = mysqli_query($database_connect, "UPDATE `users` SET $update_injection WHERE `user_key` LIKE '$authorized_user';");
		if ($update_post) {
			$json_status = 'user ' . $passed_type . ' was sucsessfully updated';
			$json_output[] = array('status' => $json_status, 'error_code' => 200);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'user ' . $passed_type . ' could not be updated - ' . mysqli_error($database_connect);
			$json_output[] = array('status' => $json_status, 'error_code' => 400);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
else if ($passed_method == 'DELETE') {
	$token_destroy = mysqli_query($database_connect, "DELETE FROM `access` WHERE `access_token` LIKE '$session_bearer';");
	if ($token_destroy) {
		header('HTTP/1.1 200 SUCSESSFUL');
								
		$json_status = 'user logged out';
		$json_output[] = array('status' => $json_status, 'error_code' => 200);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$json_status = 'user not logged out - ' . mysqli_error($database_connect);
		$json_output[] = array('status' => $json_status, 'error_code' => 400);
		echo json_encode($json_output);
		exit;
		
	}
	
}
else {
	$json_status = $passed_method . ' menthods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}
