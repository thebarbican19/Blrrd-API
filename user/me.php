<?php

include '../lib/auth.php';
include '../lib/stats.php';
include '../lib/push.php';
include '../lib/email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_type = mysqli_real_escape_string($database_connect, $passed_data['type']);
$passed_value = mysqli_real_escape_string($database_connect, $passed_data['value']);

if ($authorized_type == "admin" && !empty($passed_data['user'])) $user_key = $passed_data['user'];
else $user_key = $authorized_user;

if ($passed_method == 'GET') {
	$user_stats = user_stats($user_key);			
	$user_output = array("key" => $authorized_user, "username" => $authorized_username, "email" => $authorized_email, "type" => $authorized_type, "lastactive" => $authorized_lastactive, "signup" => $authorized_signupdate, "stats" => $user_stats, "public" => $autorized_userpublic, "promoted" => $autorized_userpromoted, "phonenumber" => $authorized_phone, "displayname" => $authorized_displayname);
											
	$json_status = 'user data returned';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'user' => $user_output);
	echo json_encode($json_output);
	exit;
						
}
elseif ($passed_method == 'PUT') {
	$allowed_types = array('status' ,'email', 'password', 'avatar', 'language', 'device', 'promote', 'phone', 'dob', 'fullname', 'gender', 'instagram', 'website');
	$allowed_statuses = array('active', 'inactive');
	if (!empty($session_devicename)) $email_device = "(" . $session_devicename . ") ";
	else $email_device = "";
	
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
			else {
				$email_subject = "Email Updated";
				$email_recipient = $authorized_email;
				$email_body .= "Your email has been updated to <strong>" . $passed_value . "</strong>";
				$email_body .= "<p>This request was made via the <strong>Blrrd iOS app " . $email_device . "</strong> on <strong>" . date('d M Y') . " at " . date('H:i') . "</strong>, if you did not make this request or if this email is unexpected please reply to this email.";	
				
				$update_email = email_user($email_subject, $email_body, $email_recipient, NULL);
				$update_injection = "`user_email` = '" . $passed_value . "'";
								
			}
			
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
		elseif ($passed_type == "promote") {
			if ($authorized_type != "admin") {
				$json_status = 'user does not have the privileges to perform this action';
				$json_output[] = array('status' => $json_status, 'error_code' => 401);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$user_query = mysqli_query($database_connect, "SELECT `user_name`, `user_email` FROM `users` WHERE `user_key` LIKE '$user_key'");
				$user_data = mysqli_fetch_assoc($user_query);
				$user_email = $user_data['user_email'];
				$user_name = $user_data['user_name'];
									
				/*
				$push_user = $post_data['upload_owner'];
				$push_payload = array();
				$push_title = "👑 You just got verifyed!";
				$push_body = "Your account has just been verifyed by the Blrrd team. Congratulations!";
				$push_payload = array();
				$push_output = sent_push_to_user($user_key, $push_payload, $push_title, $push_body);
				*/
				
				$update_email = email_subscribe_mailinglist("verifyed", $user_email, $user_name);	
				$update_injection = "`user_promoted` = '1'";	
								
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
		elseif ($passed_type == "phone") {
			if (strlen($passed_value) < 5) {
				$json_status = 'phone number is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				if (strlen($authorized_phone) > 2) {
					$email_subject = "New Phone Numer Updated";
					$email_text = "Your phone number has been updated to ";
					
				}
				else {
					$email_subject = "New Phone Numer Added";
					$email_text = "A new phone number has been added to your Blrrd account ";
										
				}
				
				$email_recipient = $authorized_email;
				$email_body .= $email_text . "<strong>" . $passed_value . "</strong>";
				$email_body .= "<p>This request was made via the <strong>Blrrd iOS app " . $email_device . "</strong> on <strong>" . date('d M Y') . " at " . date('H:i') . "</strong>, if you did not make this request or if this email is unexpected please reply to this email.";	
				
				$update_email = email_user($email_subject, $email_body, $email_recipient, NULL);
				$update_injection = "`user_phone` = '" . $passed_value . "'";	
				
			}	
							
		}
		elseif ($passed_type == "dob") {
			if (date('Y-m-d', strtotime($passed_value)) != $passed_value) {
				$json_status = 'dob number is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$update_injection = "`user_dob` = '" . $passed_value . "'";	
				
			}	
							
		}
		elseif ($passed_type == "gender") {
			if ($passed_value != "1" && $passed_value != "2") {
				$json_status = 'gender number is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$update_injection = "`user_gender` = '" . (int)$passed_value . "'";	
				
			}	
							
		}
		elseif ($passed_type == "fullname") {
			if (strlen($passed_value) < 5) {
				$json_status = 'fullname is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$update_injection = "`user_fullname` = '" . $passed_value . "'";	
				
			}	
							
		}
		elseif ($passed_type == "instagram") {
			if (strlen($passed_value) < 5) {
				$json_status = 'instagram is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$update_injection = "`user_instagram` = '" . $passed_value . "'";	
				
			}	
							
		}
		elseif ($passed_type == "website") {
			if (substr($passed_value, 0, 4) != "http") {
				$json_status = 'website is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => 422 ,'website' => substr($passed_value, 0, 4));
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$update_injection = "`user_website` = '" . $passed_value . "'";	
				
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
		
		$update_post = mysqli_query($database_connect, "UPDATE `users` SET $update_injection WHERE `user_key` LIKE '$user_key';");
		if ($update_post) {
			$json_status = 'user ' . $passed_type . ' was sucsessfully updated to ' . $passed_value;
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
