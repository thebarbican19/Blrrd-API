<?php

include '../lib/auth.php';

header('Content-Type: application/json');

if (empty($_GET['type'])) $passed_type = "users";
else $passed_type = $_GET['type'];

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_type_allowed = array("users", "posts");

if ($passed_method == 'GET') {
	if ($passed_type == "users" || empty($passed_type)) {
		$followers_query = $leader_query = mysqli_query($database_connect, "SELECT * FROM `follow` WHERE `follow_owner` LIKE '$authorized_user'");
		$followers_injection .= "OR ";
		while($row = mysqli_fetch_array($followers_query)) {
			$followers_user = $row['follow_user'];
			$followers_injection .= "`upload_owner` LIKE '$followers_user' OR ";
			
		}
	
		if (strpos($followers_injection, 'OR') !== false) $followers_injection = substr($followers_injection, 0, strlen($followers_injection) - 4);
		
		$leader_injections = "SELECT user_key, user_avatar, user_name, SUM(time.time_seconds) AS user_score FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_owner` LIKE '$authorized_user' $followers_injection GROUP BY upload_owner";
		$leader_query = mysqli_query($database_connect, $leader_injections);
		while($row = mysqli_fetch_array($leader_query)) {
			$leader_key = $row['user_key'];
			$leader_user = $row['user_name'];
			$leader_avatar = $row['user_avatar'];
			$leader_score = (int)$row['user_score'];
			$leader_output[] = array("key" => $leader_key, "user" => $leader_user, "avatar" => $leader_avatar, "score" => $leader_score);
				
		}
				
	}
	else {
		$leader_injections = "SELECT upload_key, upload_file, SUM(time.time_seconds) AS upload_score FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_owner` LIKE '$authorized_user' GROUP BY upload_key";
		$leader_query = mysqli_query($database_connect, $leader_injections);
		while($row = mysqli_fetch_array($leader_query)) {
			$leader_key = $row['upload_key'];
			$leader_file = $row['upload_file'];
			$leader_score = (int)$row['upload_score'];
			$leader_output[] = array("key" => $leader_key, "file" => $leader_file, "score" => $leader_score);
				
		}	
		
	}
	
	
	
	if (count($leader_output) == 0) $leader_output[] = array();
	
	$json_status = 'returned ' . $passed_type . ' scores';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $leader_output);
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