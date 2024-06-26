<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_limit = (int)$_GET['limit'];
$passed_pagenation = (int)$_GET['pagnation'];
$passed_userid = $_GET['userid'];

if (empty($passed_limit)) $passed_limit = 40;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	if (!empty($passed_userid)) {
		$follow_injection = "SELECT `user_key` FROM `follow` LEFT JOIN users on follow.follow_user LIKE users.user_key WHERE `follow_owner` LIKE '$authorized_user' AND `follow_user` LIKE '$passed_userid' AND `user_status` LIKE 'active' LIMIT 0, 1";
		$follow_query = mysqli_query($database_connect, $follow_injection);
		$follow_exists = mysqli_num_rows($follow_query);
		if ($follow_exists != 1) {
			$json_status = 'you are not following this person';
			$json_output[] = array('status' => $json_status, 'error_code' => 401);
			echo json_encode($json_output);
			exit;
			
		}
				
			
	}
	else $passed_userid = $authorized_user;
	
	$timeline_injection = "SELECT `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, `user_key`, `user_name`, `user_avatar`, `user_lastactive`, `user_public`, `user_promoted`, `upload_latitude`, `upload_longitude`, `upload_locshare`, SUM(time.time_seconds) AS upload_time FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_removed` = '0' AND `upload_owner` LIKE '$passed_userid' GROUP BY upload_key ORDER BY `upload_timestamp` DESC LIMIT $passed_pagenation, $passed_limit";
	$timeline_query = mysqli_query($database_connect, $timeline_injection);
	$timeline_items = mysqli_num_rows($timeline_query);
	while($row = mysqli_fetch_array($timeline_query)) {
		$comment_item = (string)$row['upload_key'];
		$comment_output = array();
		$comment_query = mysqli_query($database_connect, "SELECT `comment_key`, `comment_content`, `user_avatar` FROM `comments` LEFT JOIN users on comments.comment_user LIKE users.user_key WHERE `comment_post` LIKE '$comment_item' ORDER BY `comment_timestamp` ASC LIMIT 0, 3;");
		while($comment = mysqli_fetch_array($comment_query)) {
			$comment_key = (string)$comment['comment_key'];
			$comment_content = (string)$comment['comment_content'];
			$comment_avatar = (string)$comment['user_avatar'];
			$comment_output[] = array("key" => $comment_key, "comment" => $comment_content, "avatar" => $comment_avatar);
			
		}
		
		if (count($comment_output) == 0) $comment_output = array();	
			
		$timeline_user = array("userid" => (string)$row['user_key'], 
							   "avatar" => (string)$row['user_avatar'],
							   "username" => (string)$row['user_name'],
							   "lastactive" => $row['user_lastactive'],
							   "public" => (bool)$row['user_public'],
							   "promoted" => (bool)$row['user_promoted']);	
		if (empty($row['upload_timezone'])) $timeline_timezone = "+0000";
		else $timeline_timezone = $row['upload_timezone'];
		$timeline_mentioned = explode(",", $row['upload_userstagged']);		
		$timeline_timestamp = $row['upload_timestamp'] . " " . $timeline_timezone;
		$timeline_data = array("timestamp" => $timeline_timestamp, 
								   "postid" => (string)$row['upload_key'], 
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