<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_limit = (int)$_GET['limit'];
$passed_pagenation = (int)$_GET['pagnation'];
$passed_type = $_GET['type'];

if (empty($passed_limit)) $passed_limit = 40;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;
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
		$report_query = mysqli_query($database_connect, "SELECT `report_item` FROM `report` WHERE `report_user` LIKE '$authorized_user';");
		$report_count = mysqli_num_rows($report_query);
		if ($report_count > 0) {
			$report_injection = "AND (";
			while($row = mysqli_fetch_array($report_query)) {	
				$report_injection .= "`upload_key` NOT LIKE '" . $row['report_item'] . "' OR ";
				
			}
			
			if (strpos($report_injection, 'OR') !== false) $report_injection = substr($report_injection, 0, strlen($report_injection) - 4);
			$report_injection .= ")";
			
		}
		
		if ($passed_type == "following") {
			$follower_query = mysqli_query($database_connect, "SELECT `follow_user`, `follow_owner` FROM `follow` WHERE `follow_owner` LIKE '$authorized_user'");
			$follower_count = mysqli_num_rows($follower_query);
			if ($follower_count > 0) {
				$follower_injection = "AND (";
				while($row = mysqli_fetch_array($follower_query)) {	
					$follower_injection .= "`upload_owner` LIKE '" . $row['follow_user'] . "' OR ";
					
				}
				
				$follower_injection .= " `upload_owner` LIKE '" . $authorized_user . "') ";
				
				$timeline_injection = "SELECT `user_key`, `user_avatar`, `user_name`, `user_lastactive`, `user_public`, `user_promoted`, `upload_userstagged`, `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, `upload_latitude`, `upload_longitude`, `upload_locshare`, SUM(time.time_seconds) AS upload_time FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' $follower_injection $report_injection GROUP BY uploads.upload_key ORDER BY `upload_timestamp` DESC";
				
			}
			else {
				$json_status = 'you have not followed anybody yet';
				$json_output[] = array('status' => $json_status, 'error_code' => 404);
				echo json_encode($json_output);
				exit;
				
			}
			
						
		}
		else if ($passed_type == "trending") {
			$timeline_injection = "SELECT `user_key`, `user_avatar`, `user_name`, `user_lastactive`, `user_public`, `user_promoted`, `upload_userstagged`, `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, `upload_latitude`, `upload_longitude`, `upload_locshare`, SUM(time.time_seconds) AS upload_time FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' $follower_injection $report_injection GROUP BY uploads.upload_key HAVING `upload_time` >= 60 ORDER BY `upload_timestamp` DESC, `upload_time` DESC";
			
		}
		
		$timeline_injection .= " LIMIT $passed_pagenation, $passed_limit";
		$timeline_query = mysqli_query($database_connect, $timeline_injection);
		$timeline_items = mysqli_num_rows($timeline_query);
		while($row = mysqli_fetch_array($timeline_query)) {
			$timeline_user = array("userid" => (string)$row['user_key'], 
								 "avatar" => (string)$row['user_avatar'],
								 "username" => (string)$row['user_name'],
								 "lastactive" => (string)$row['user_lastactive'],
								 "public" => (bool)$row['user_public'], 
								 "promoted" => (bool)$row['user_promoted']);	
			if (empty($row['upload_timezone'])) $timeline_timezone = "+0000";
			else $timeline_timezone = $row['upload_timezone'];
			$timeline_mentioned = explode(",", $row['upload_userstagged']);
			$timeline_timestamp = $row['upload_timestamp'] . " " . $timeline_timezone;
			$timeline_data = array("timestamp" => (string)$timeline_timestamp, 
									   "postid" => (string)$row['upload_key'], 
									   "caption" => (string)$row['upload_caption'], 
									   "imageurl" => (string)$row['upload_file'], 
									   "channel" => (string)$row['upload_channel'], 
									   "user" => $timeline_user, 
									   "mentioned" => $timeline_mentioned,  
									   "seconds" => (int)$row['upload_time']);
									   
									   
			if ((int)$row['upload_locshare'] == 1 && (float)$row['upload_latitude'] != 0 && (float)$row['upload_longitude'] != 0) {
				$timeline_location = array("latitude" => (float)$row['upload_latitude'] ,"longitude" => (float)$row['upload_longitude']);
				$timeline_append = array("location" => $timeline_location);
				$timeline_output[] = array_merge($timeline_data, $timeline_append);
				
				
			}
			else $timeline_output[] = array_merge($timeline_data);
			
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