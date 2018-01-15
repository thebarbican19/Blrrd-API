<?php

include '../lib/auth.php';
include '../lib/stats.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_emails = strip_tags($_GET['emails']); 
$passed_search = strip_tags($_GET['search']); 
$passed_emails_array = explode(",", $passed_emails);

if (empty($passed_limit)) $passed_limit = 55;
if (empty($passed_pagenation)) $passed_pagenation = 0;

if ($passed_method == 'GET') {
	if ((count($passed_emails_array) == 0 || empty($passed_emails)) && empty($passed_search)) {
		$time_expiry = date('Y-m-d H:i:s', strtotime('-50 days'));
		$time_injection = "SELECT `user_key`, SUM(time.time_seconds) AS upload_time FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' $follower_injection GROUP BY upload_owner ORDER BY `upload_time` DESC, `upload_timestamp` DESC";
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
		$user_injection = "AND (`user_email` LIKE '$passed_search' OR `user_name` LIKE '%$passed_search%')";
		
	}
	else {
		$user_injection = "AND (";
		foreach ($passed_emails as $email) {
			if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
				$user_injection .= "`user_email` LIKE '$email' OR ";
				
				
			}
			
		}
		
		if (strpos($user_injection, 'OR') !== false) $user_injection = substr($user_injection, 0, strlen($user_injection) - 4);
		$user_injection .= ")";
		
	}
	
	$user_injection = "SELECT `user_key`, `user_name`, `user_avatar`, `user_lastactive`, `user_email` FROM `users` WHERE `user_status` LIKE 'active' $user_injection LIMIT $passed_pagenation, $passed_limit";
	$user_query  =  mysqli_query($database_connect, $user_injection);
	$user_count = mysqli_num_rows($user_query);
	while($row = mysqli_fetch_array($user_query)) {	
		$user_data = array("userid" => $row['user_key'], 
						   "avatar" => $row['user_avatar'], 
						   "username" => $row['user_name'], 
						   "following" => user_following($row['user_key']),
						   "lastactive" => $row['user_lastactive']);
			
		if (count($passed_emails_array) > 0 && !empty($passed_emails)) {
			$user_append = array("email" => $row['user_email']);
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