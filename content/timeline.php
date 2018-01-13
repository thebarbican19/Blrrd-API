<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_limit = (int)$_GET['limit'];
$passed_pagenation = (int)$_GET['pangnation'];
$passed_type = $_GET['type'];

if (empty($passed_limit)) $passed_limit = 40;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_allowd_types = array("following", "trending");

if ($passed_method == 'GET') {
	if (empty($passed_type)) {
		$json_status = 'timeline type parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else if (!in_array($passed_type, $passed_allowd_types)) {
		$json_status = 'timeline type is not allowed';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if ($passed_type == "following") {
			$follower_query = mysqli_query($database_connect, "SELECT `follow_user`, `follow_owner` FROM `follow` WHERE `follow_owner` LIKE '$authorized_user'");
			$follower_count = mysqli_num_rows($follower_query);
			if ($follower_count > 0) {
				$follower_injection = "AND (";
				while($row = mysqli_fetch_array($follower_query)) {	
					$follower_injection .= "`upload_owner` LIKE '" . $row['follow_user'] . "' OR ";
					
				}
				
				$follower_injection = substr($follower_injection, 0, strlen($follower_injection) - 4);
				$follower_injection .= ") ";
				
				$timeline_injection = "SELECT `user_key`, `user_avatar`, `user_name`, `user_lastactive`, `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, SUM(time.time_seconds) AS upload_time FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' $follower_injection GROUP BY upload_key ORDER BY `upload_timestamp` DESC";
				
			}
			else {
				$json_status = 'you have not followed anybody yet';
				$json_output[] = array('status' => $json_status, 'error_code' => 404);
				echo json_encode($json_output);
				exit;
				
			}
			
						
		}
		else if ($passed_type == "trending") {
			$timeline_injection = "SELECT `user_key`, `user_avatar`, `user_name`, `user_lastactive`, `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, SUM(time.time_seconds) AS upload_time FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' $follower_injection GROUP BY upload_key ORDER BY `upload_time` DESC, `upload_timestamp` DESC";
			
		}
		
		$timeline_injection .= " LIMIT $passed_pagenation, $passed_limit";
		$timeline_query = mysqli_query($database_connect, $timeline_injection);
		$timeline_items = mysqli_num_rows($timeline_query);
		while($row = mysqli_fetch_array($timeline_query)) {
			$timeline_user = array("userid" => (string)$row['user_key'], 
								 "avatar" => (string)$row['user_avatar'],
								 "username" => (string)$row['user_name'],
								 "lastactive" => $row['user_lastactive']);	
			$timeline_timestamp = $row['upload_timestamp'] . " " . $row['upload_timezone'];
			$timeline_output[] = array("timestamp" => $timeline_timestamp, 
									   "postid" => (string)$row['upload_key'], 
									   "caption" => (string)$row['upload_caption'], 
									   "imageurl" => (string)$row['upload_file'], 
									   "channel" => (string)$row['upload_channel'], 
									   "user" => $timeline_user, 
									   "seconds" => (int)$row['upload_time']);
			
		
		}
		
		if (count($timeline_output) == 0) $timeline_output = array();		
		
		
		header('HTTP/1.1 200 SUCSESSFUL');
		
		$json_status = 'returned ' . count($timeline_output) . ' posts';
		$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $timeline_output);
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