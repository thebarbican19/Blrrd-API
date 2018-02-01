<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);

if ($passed_method == 'GET') {
	$posts_injection = "SELECT `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_caption` , `upload_file`, `upload_channel`, SUM(time.time_seconds) AS upload_time FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post WHERE `upload_removed` = '0' AND `upload_owner` LIKE '$passed_userid' GROUP BY upload_key ORDER BY `upload_timestamp` DESC LIMIT $passed_pagenation, $passed_limit";
	$posts_query = mysqli_query($database_connect, $posts_injection);
	while($row = mysqli_fetch_array($posts_query)) {
		$posts_timestamp = $row['upload_timestamp'] . " " . $row['upload_timezone'];
		$posts_output[] = array("timestamp" => $posts_timestamp, 
								   "postid" => (string)$row['upload_key'], 
								   "caption" => (string)$row['upload_caption'], 
								   "imageurl" => (string)$row['upload_file'], 
								   "channel" => (string)$row['upload_channel'], 
								   "seconds" => (int)$row['upload_time']);
		
	
	}
	
	if (count($posts_output) == 0) $posts_output = array();		
	
	header('HTTP/1.1 200 SUCSESSFUL');
	
	$json_status = 'returned ' . count($posts_output) . ' posts and ';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'posts' => $posts_output);
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