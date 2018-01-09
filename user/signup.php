<?php

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/stats.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_email = $passed_data['email'];
$passed_password = $passed_data['password'];
$passed_encryptpassword = password_hash($passed_password ,PASSWORD_BCRYPT);
$passed_username = $passed_data['username'];
$passed_type = $passed_data['type'];

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
	elseif (empty($passed_username)) {
		$json_status = 'username parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$email_query = mysqli_query($database_connect, "SELECT * FROM `users` WHERE `user_email` LIKE '$passed_email' LIMIT 0, 1");
		$email_exists = mysqli_num_rows($email_query);
		$email_output = mysqli_fetch_assoc($email_query);
		$email_status = $email_output['user_status'];		
		$email_key = $email_output['user_key'];		
		if ($email_status == "active") {
			$json_status = 'user with email already exists in our records';
			$json_output[] = array('status' => $json_status, 'error_code' => 409);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$username_query = mysqli_query($database_connect, "SELECT * FROM `users` WHERE `user_name` LIKE '$passed_username' LIMIT 0, 1");
			$username_exists = mysqli_num_rows($username_query);
			if ($username_exists) {
				$json_status = 'username is taken by another user';
				$json_output[] = array('status' => $json_status, 'error_code' => 409);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$user_key = "user_" . generate_key();
				//$user_create = mysqli_query($database_connect, "INSERT INTO `users` (`user_id`, `user_key`, `user_signup`, `user_lastactive`, `user_status`, `user_type`, `user_email`, `user_password`, `user_name`, `user_actualname`, `user_slack`, `user_customer`, `user_avatar`, `user_device`, `user_city`, `user_country`, `user_language`) VALUES (NULL, '$user_key', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 'active', '$passed_type', '$passed_email', '$passed_encryptpassword', '$passed_username', '', '$passed_slack', '', '$user_ovatar', '$passed_device', '', '$session_country', '$session_language');");
				
				if ($user_create) {
					$bearer_token = "at_" . generate_key();	
					$bearer_expiry = date('Y-m-d H:i:s', strtotime('+100 days'));
					$bearer_output = array("expiry" => $bearer_expiry, "token" => $bearer_token, 'scope' => explode(",", $bearer_scope));
					$bearer_create = mysqli_query($database_connect, "INSERT INTO `access` (`access_id`, `access_created`, `access_expiry`, `access_token`, `access_user`, `access_app`, `access_scope`) VALUES (NULL, CURRENT_TIMESTAMP, '$bearer_expiry', '$bearer_token', '$user_key', '$session_application', '$bearer_scope');");
					if ($bearer_create) {
						$user_stats = user_stats();
						$user_output = array("key" => $user_key, "username" => $passed_username, "email" => $passed_email, "type" => $passed_type, "ovatar" => $user_ovatar, "langauge" => $session_language, "lastactive" => date("Y-m-d H:i:s"), "signup" => date("Y-m-d H:i:s"), "auth" => $bearer_output, "stats" => $user_stats);
									
						$friendship_blrrdid = "blrdapp";
						$friendship_create = mysqli_query($database_connect, "INSERT INTO `follow` (`follow_id`, `follow_timestamp`, `follow_user`, `follow_owner`) VALUES (NULL, '$friendship_timestamp', '$friendship_blrrdid', '$authorized_user');");
		
						$json_status = $passed_email . ' created';
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
					$json_status = 'user could not be created - ' . mysqli_error($user_create);
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
					
				}
								
			}
			
		}
		
		$existing_query = mysqli_query($database_connect, "SELECT * FROM `users` WHERE (`user_email` LIKE '$passed_email' OR `user_name` LIKE '$passed_username') LIMIT 0, 1");
		$existing_count = mysqli_num_rows($existing_query);
		$existing_output = mysqli_fetch_assoc($existing_query);
		if ($existing_count == 1) {
			if ($existing_output['user_email'] == $passed_email) {
				$json_status = 'user with email already exists in our records';
				$json_output[] = array('status' => $json_status, 'error_code' => 409);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'user with username already exists in our records, try logging in';
				$json_output[] = array('status' => $json_status, 'error_code' => 409);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		
	}
	
}
else if ($passed_method == 'DELETE') {
	if (in_array("admin", $authentication_scope)) {
		if (empty($session_bearer)) {
			$json_status = 'bearer token was not passed';
			$json_output[] = array('status' => $json_status, 'error_code' => 401);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$user_remove = mysqli_query($database_connect, "UPDATE `users` SET `user_status` = 'deactivated' WHERE `user_key` LIKE '$authuser_key';");
			if ($user_remove) {
				$json_status = 'user deactivated';
				$json_output[] = array('status' => $json_status, 'error_code' => 200);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'user could not be deactivated - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		
	}
	else {
		$json_status = 'bearer token does not have the privileges to perform this request';
		$json_output[] = array('status' => $json_status, 'error_code' => 401);
		echo json_encode($json_output);
		exit;
		
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>