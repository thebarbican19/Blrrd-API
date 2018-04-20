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
	
	$follower_query = mysqli_query($database_connect, "SELECT `follow_user`, `follow_owner` FROM `follow` WHERE `follow_owner` LIKE '$authorized_user'");
	$follower_count = mysqli_num_rows($follower_query);
	if ($follower_count > 0) {
		$follower_injection = "AND (";
		while($row = mysqli_fetch_array($follower_query)) {	
			$follower_injection .= "`upload_owner` LIKE '" . $row['follow_user'] . "' OR ";
			
		}
		
		$follower_limit = $passed_limit - 5;
		$follower_pagenation = $passed_pagenation * $follower_limit;		
		$follower_injection .= " `upload_owner` LIKE '" . $authorized_user . "') ";
		$follower_timeline = mysqli_query($database_connect, "SELECT `user_key`, `user_avatar`, `user_name`, `user_lastactive`, `user_public`, `user_promoted`, `upload_userstagged`, `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, `upload_latitude`, `upload_longitude`, `upload_locshare`, SUM(time.time_seconds) AS upload_time FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' $follower_injection $report_injection GROUP BY uploads.upload_key ORDER BY `upload_timestamp` DESC LIMIT $follower_pagenation, $follower_limit");
		while($item = mysqli_fetch_array($follower_timeline, MYSQLI_ASSOC)) {	
			$timeline_type = array("upload_timeline" => "followers");
			$timeline_content[] = array_merge($item, $timeline_type);
			
		}	
		
	}

	$trending_limit = $passed_limit - count($timeline_content);
	$trending_pagenation = $passed_pagenation * $trending_limit;
	$trending_timeline = mysqli_query($database_connect, "SELECT `user_key`, `user_avatar`, `user_name`, `user_lastactive`, `user_public`, `user_promoted`, `upload_userstagged`, `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, `upload_latitude`, `upload_longitude`, `upload_locshare`, SUM(time.time_seconds) AS upload_time FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' $follower_injection $report_injection GROUP BY uploads.upload_key HAVING `upload_time` >= 60 ORDER BY `upload_timestamp` DESC, `upload_time` DESC LIMIT $trending_pagenation, $trending_limit");
	while($item = mysqli_fetch_array($trending_timeline, MYSQLI_ASSOC)) {	
		$timeline_type = array("upload_timeline" => "trending");
		$trending_content[] = array_merge($item, $timeline_type);
					
	}	
	
	$trending_counter = 0;
	$timeline_added_counter = 0;
	foreach ($timeline_content as $item) {
		$trending_counter += 1;
		if ($trending_counter % $trending_limit == 1) {
			$trending_item = $trending_content[$timeline_added_counter];
			$trending_item_key = $trending_item['upload_key'];
			if (isset($trending_item_key) && $timeline_added_counter < count($trending_content)) {
				$timeline_content[] = array_splice($timeline_content, $trending_counter, 0, array($trending_item));
				$timeline_added[] = $trending_item_key;
				$timeline_added_counter += 1;
				
			}

		}
		
	}
	
	foreach ($timeline_content as $row) {
		$comment_item = (string)$row['upload_key'];
		$comment_output = array();
		$comment_query = mysqli_query($database_connect, "SELECT `comment_key`, `comment_content`, `user_key`, `user_name`, `user_avatar` `user_promoted` FROM `comments` LEFT JOIN users on comments.comment_user LIKE users.user_key WHERE `comment_post` LIKE '$comment_item' ORDER BY `comment_timestamp` ASC LIMIT 0, 3;");
		while($comment = mysqli_fetch_array($comment_query)) {
			$comment_key = (string)$comment['comment_key'];
			$comment_content = (string)$comment['comment_content'];
			$comment_user_avatar = (string)$comment['user_avatar'];
			$comment_user_key = (string)$comment['user_key'];
			$comment_user_name = (string)$comment['user_name'];
			$comment_user_verifyed = (bool)$comment['user_promoted'];	
			$comment_output[] = array("commentid" => $comment_key, "comment" => $comment_content, "avatar" => $comment_user_avatar, "userid" => $comment_user_key, "handle" => $comment_user_name, "verifyed" => $comment_user_verifyed);
			
		}
		
		if (count($comment_output) == 0) $comment_output = array();	
	
		if (!empty($row['user_key'] && !in_array($row['upload_key'], $timeline_added))) {
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
			$timeline_key = (string)$row['upload_key'];
			$timeline_data = array("timestamp" => (string)$timeline_timestamp, 
								   "timeline" => (string)$row['upload_timeline'], 
								   "postid" => $timeline_key, 
								   "caption" => (string)$row['upload_caption'], 
								   "imageurl" => (string)$row['upload_file'], 
								   "channel" => (string)$row['upload_channel'], 
								   "user" => $timeline_user, 
								   "mentioned" => $timeline_mentioned,  
								   "seconds" => (int)$row['upload_time'],
								   "comments" => $comment_output);   
									   				   
			if ((int)$row['upload_locshare'] == 1 && (float)$row['upload_latitude'] != 0 && (float)$row['upload_longitude'] != 0) {
				$timeline_location = array("latitude" => (float)$row['upload_latitude'] ,"longitude" => (float)$row['upload_longitude']);
				$timeline_append = array("location" => $timeline_location);
				$timeline_output[] = array_merge($timeline_data, $timeline_append);
				
				
			}
			else $timeline_output[] = array_merge($timeline_data);
			
			$timeline_added[] = $timeline_key;
						
		}
		
	}
	
	if (count($timeline_output) == 0) $timeline_output = array();		
	
	header('HTTP/1.1 200 SUCSESSFUL');
	
	$json_status = 'returned ' . count($timeline_output) . ' posts';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $timeline_output);
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