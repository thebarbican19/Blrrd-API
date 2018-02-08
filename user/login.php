<?php

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/stats.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_email = mysqli_real_escape_string($database_connect, $passed_data['email']);
$passed_password = mysqli_real_escape_string($database_connect, $passed_data['password']);
$passed_encryptpassword = password_hash($passed_password ,PASSWORD_BCRYPT);

if ($passed_method == 'POST') {
	if (empty($passed_email)) {
		$json_status = 'email parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (filter_var($passed_email, FILTER_VALIDATE_EMAIL) === false) {
		$json_status = 'email is invalid';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}	
	elseif (empty($passed_password)) {
		$json_status = 'password parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$user_query = mysqli_query($database_connect ,"SELECT * FROM `users` WHERE `user_status` LIKE 'active' AND `user_email` LIKE '$passed_email' LIMIT 0, 1");
		$user_exists = mysqli_num_rows($user_query);
		if ($user_exists == 1) {
			$user_data = mysqli_fetch_assoc($user_query);	
			$user_password = $user_data['user_password'];
			if (password_verify($passed_password ,$user_password)) {
				$user_key = $user_data['user_key'];
				$user_name = $user_data['user_name'];
				$user_email = $user_data['user_email'];
				$user_type = $user_data['user_type'];
				$user_avatar = $user_data['user_avatar'];
				$user_language = $user_data['user_language'];
				$user_lastactive = $user_data['user_lastactive'];
				$user_signup = $user_data['user_signup'];
				$user_public = (bool)$user_data['user_public'];	
				$user_promoted = (bool)$user_data['user_promoted'];	
				$user_phone = $user_data['user_phone'];
				$user_display = $user_data['user_fullname'];	
				$user_stats = user_stats($user_key);
										
				$bearer_token = "at_" . generate_key();	
				$bearer_expiry = date('Y-m-d H:i:s', strtotime('+100 days'));
				$bearer_timestamp = date('Y-m-d H:i:s');
				$bearer_output = array("expiry" => $bearer_expiry, "token" => $bearer_token);
				$bearer_injection = "INSERT INTO `access` (`access_id`, `access_created`, `access_expiry`, `access_token`, `access_user`) VALUES (NULL, '$bearer_timestamp', '$bearer_expiry', '$bearer_token', '$user_key');";
				$bearer_create = mysqli_query($database_connect, $bearer_injection);
				if ($bearer_create)	 {
					header('HTTP/1.1 200 SUCSESSFUL');
											
					$user_output = array("key" => $user_key, "username" => $user_name, "email" => $user_email, "type" => $user_type, "lastactive" => date("Y-m-d H:i:s"), "signup" => $user_signup, "auth" => $bearer_output, "stats" => $user_stats, "public" => $user_public, "promoted" => $user_promoted, "phonenumber" => $user_phone, "displayname" => $user_display);
						
					$json_status = 'user logged in';
					$json_output[] = array('status' => $json_status, 'error_code' => 200, 'user' => $user_output);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'access token not be created - ' . mysqli_error($bearer_create);
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'password incorrect';
				$json_output[] = array('status' => $json_status, 'error_code' => 401);
				echo json_encode($json_output);
				exit;
				
			}
							
		}
		else {
			$json_status = 'user does not exist with the email ' . $passed_email;
			$json_output[] = array('status' => $json_status, 'error_code' => 350);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
	
}
elseif ($passed_method == 'DELETE') {
	$destroy_token = mysqli_query($database_grado_connect ,"DELETE FROM `access` WHERE `access_token` LIKE '$session_bearer';");
	if ($destroy_token) {
		header('HTTP/1.1 200 SUCSESSFUL');
								
		$json_status = 'access token destroyed';
		$json_output[] = array('status' => $json_status, 'error_code' => 200);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$json_status = 'access token not destroyed - ' . mysql_error();
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

?>