<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_limit = (int)$_GET['limit'];
$passed_pagenation = (int)$_GET['pagnation'];

if (empty($passed_limit)) $passed_limit = 40;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	$timeline_injection = "SELECT `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, SUM(time.time_seconds) AS upload_time FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post WHERE `upload_removed` = '0' AND `upload_owner` LIKE '$authorized_user' GROUP BY upload_key ORDER BY `upload_timestamp` DESC LIMIT $passed_pagenation, $passed_limit";
	$timeline_query = mysqli_query($database_connect, $timeline_injection);
	$timeline_items = mysqli_num_rows($timeline_query);
	while($row = mysqli_fetch_array($timeline_query)) {
		$timeline_user = array("userid" => (string)$authorized_user, 
							   "avatar" => (string)$authorized_avatar,
							   "username" => (string)$authorized_username,
							   "lastactive" => (string)$authorized_lastactive);	
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
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>