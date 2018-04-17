<?php

include '../lib/auth.php';
include '../lib/stats.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_limit = (int)$_GET['limit'];
$passed_pagenation = (int)$_GET['pagnation'];
$passed_search = mysqli_real_escape_string($database_connect, $_GET['search']);
$passed_emails = mysqli_real_escape_string($database_connect, $_GET['emails']);
$passed_emails_array = explode(",", $passed_emails);
$passed_socials = mysqli_real_escape_string($database_connect, $_GET['socials']);
$passed_socials_array = explode(",", $passed_socials);

if (empty($passed_limit)) $passed_limit = 55;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	if ((count($passed_emails_array) == 0 || empty($passed_emails)) && (count($passed_socials_array) == 0 || empty($passed_socials)) && empty($passed_search)) {
		$time_expiry = date('Y-m-d H:i:s', strtotime('-50 days'));
		$time_injection = "SELECT `user_key`, SUM(time.time_seconds) AS upload_time FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' $follower_injection  GROUP BY upload_owner ORDER BY `user_promoted` DESC, `upload_time` ASC, `user_signup` DESC";
		$time_query = mysqli_query($database_connect, $time_injection);
		while($row = mysqli_fetch_array($time_query)) {	
			$user_keys[] = $row['user_key'];
			
		}
		
		if (count($user_keys) > 0) {
			$user_injection = "AND (";
			foreach ($user_keys as $user) {
				$user_injection .= "`user_key` LIKE '$user' OR ";
				
			}
			
			if (strpos($user_injection, 'OR') !== false) $user_injection = substr($user_injection, 0, strlen($user_injection) - 4);
			$user_injection .= ")";
			
		}
		
	}
	else if (!empty($passed_search)) {
		$user_injection = "AND (`user_email` LIKE '$passed_search' OR `user_name` LIKE '%$passed_search%' OR `user_fullname` LIKE '%$passed_search%')";
		
	}
	else if (count($passed_emails_array) > 0 && !empty($passed_emails)) {
		$user_injection = "AND (";
		foreach ($passed_emails_array as $email) {
			if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
				$user_injection .= "`user_email` LIKE '$email' OR ";
				
				
			}
			else if (substr($email, 0, 1) == "+") {
				$user_injection .= "`user_phone` LIKE '$email' OR ";
				
			}
			
		}
		
		if (strpos($user_injection, 'OR') !== false) $user_injection = substr($user_injection, 0, strlen($user_injection) - 4);
		$user_injection .= ") ";
		
	}
	else if (count($passed_socials_array) > 0 && !empty($passed_socials)) {
		$user_injection = "AND (";
		foreach ($passed_socials_array as $handle) {
			$user_injection .= "`user_instagram` LIKE '$handle' OR ";
							
		}
		
		if (strpos($user_injection, 'OR') !== false) $user_injection = substr($user_injection, 0, strlen($user_injection) - 4);
		$user_injection .= ") ";
		
	}
	
	$user_injection = "SELECT `user_key`, `user_name`, `user_avatar`, `user_lastactive`, `user_email`, `user_phone`, `user_fullname`, `user_instagram`, `user_public`, `user_promoted` FROM `users` WHERE `user_status` LIKE 'active' AND `user_key` NOT LIKE '$authorized_user' $user_injection LIMIT $passed_pagenation, $passed_limit";
	$user_query  =  mysqli_query($database_connect, $user_injection);
	$user_count = mysqli_num_rows($user_query);
	while($row = mysqli_fetch_array($user_query)) {	
		$user_data = array("userid" => $row['user_key'], 
						   "avatar" => $row['user_avatar'], 
						   "username" => $row['user_name'], 
						   "displayname" => $row['user_fullname'],   
						   "following" => user_following($row['user_key']),
						   "follows" => user_follows($row['user_key']),   
						   "lastactive" => $row['user_lastactive'],
						   "promoted" => (bool)$row['user_promoted'],
						   "public" => (bool)$row['user_public']);
									   
		if (count($passed_emails_array) > 0 && !empty($passed_emails)) {
			$user_append = array("email" => $row['user_email'], "phone" => $row['user_phone']);
			$user_output[] = array_merge($user_data, $user_append);
			
		}
		else if (count($passed_socials_array) > 0 && !empty($passed_socials)) {
			$user_append = array("instagram" => $row['user_instagram']);
			$user_output[] = array_merge($user_data, $user_append);
			
		}	
		else {
			$user_output[] = array_merge($user_data);
		
		}		
			
	}
	
	if (count($user_output) == 0) $user_output = array();		
			
	$json_status = count($user_output) . " users returned";
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $user_output);
	echo json_encode($json_output);
	exit;
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>