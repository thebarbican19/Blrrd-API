<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_emails = explode(",", $_GET['emails']);

if (empty($passed_limit)) $passed_limit = 40;
if (empty($passed_pagenation)) $passed_pagenation = 0;

if ($passed_method == 'GET') {
	if (count($passed_emails) == 0) {
		$time_expiry = date('Y-m-d H:i:s', strtotime('-50 days'));
		$time_query = mysqli_query($database_connect, "SELECT `time_added`, `time_post`, `time_user`, `time_seconds`, `upload_key`, `upload_owner` FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key WHERE `time_added` > '$time_expiry' WHERE `time_seconds` > '0' GROUP BY `time_post` ORDER BY `time_seconds` DESC LIMIT $passed_pagenation, $passed_limit");
		while($row = mysql_fetch_array($time_query)) {	
			$user_keys[] = $row['upload_owner'];
			
		}
		
		if (count($user_keys) > 0) {
			$user_injection = " AND (";
			foreach ($user_keys as $user) {
				$user_injection .= "`user_key` LIKE '$user' OR ";
				
			}
			
			$user_injection = ")";
			
		}
		
	}
	else {
		$user_injection = " AND (";
		foreach ($passed_emails as $email) {
			$user_injection .= "`user_email` LIKE '$email' OR ";
			
		}
		
		$user_injection = ")";
		
	}
	
	$user_query  =  mysqli_query($database_connect, "SELECT `user_key`, `user_handle`, `user_avatar`, `user_lastactive` FROM `users` WHERE `user_key` LIKE '$user' $user_injection LIMIT 0, 1");
	$user_count = mysqli_num_rows($user_query);
	while($row = mysqli_fetch_array($user_query)) {	
		$user_output[] = array("userid" => $user_data['user_key'], "avatar" => $user_data['user_avatar'], "username" => $user_data['user_handle'], "lastactive" => $user_data['user_lastactive']);
			
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