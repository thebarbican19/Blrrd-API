<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);

if ($passed_method == 'GET') {
	$passed_postid = $_GET['postid'];
	$passed_limit = $_GET['limit'];
	$passed_pagenation = $_GET['pangnation'];
	
	if (empty($passed_limit)) $passed_limit = 20;
	if (empty($passed_pagenation)) $passed_pagenation = 0;

	if (empty($passed_postid)) $time_injection = mysqli_query($database_connect, "SELECT * FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key WHERE `upload_owner` LIKE '$authorized_user'");
	else $time_injection = mysqli_query($database_connect, "SELECT * FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key WHERE `time_post` LIKE '$passed_postid' AND `upload_owner` LIKE '$authorized_user'");
	
	$time_injection .= ' ORDER BY `time_added` DESC LIMIT $passed_pagenation, $passed_limit';
	$time_query = mysqli_query($database_connect, $time_injection);
	$time_items_count = mysqli_num_rows($time_query);
	while($row = mysqli_fetch_array($time_query)) {	
		$time_output[] = array("postid" => $row['time_post'], "userid" => $row['time_user'], "timestamp" => $row['time_added'], "seconds" => (int)$row['time_seconds']);
		
	}
	
	if (count($time_output) == 0) $time_output = array();		
	
	$json_status = 'returned ' . count($time_output) . ' notifications';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $time_output);
	echo json_encode($json_output);
	exit;

}
else if ($passed_method == 'POST') {
	$passed_postid = $passed_data['postid'];
	$passed_time = (int)$passed_data['seconds'];
	
	if (empty($passed_postid)) {
		$json_status = 'post id parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else if ($passed_time <= 0) {
		$json_status = 'secs parameter missing or less than 1';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$post_query = mysqli_query($database_connect, "SELECT * FROM `uploads` WHERE `upload_key` LIKE '$passed_postid' LIMIT 0, 1");
		$post_exists = mysqli_num_rows($post_query);
		if ($post_exists == 1)	{
			$post_data = mysqli_fetch_assoc($post_query);
			$post_removed = (int)$post_data['upload_removed'];
			$post_user = $post_data['upload_user'];
			if ($post_removed == 1) {
				$json_status = 'post does not exist';
				$json_output[] = array('status' => $json_status, 'error_code' => 409);
				echo json_encode($json_output);
				exit;
				
			}
			else if ($post_user == $authorized_user) {
				$json_status = 'you cannot give time to your own post';
				$json_output[] = array('status' => $json_status, 'error_code' => 401);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$time_added = date('Y-m-d H:i:s');
				$time_post = mysqli_query($database_connect, "INSERT INTO `time` (`time_id`, `time_added`, `time_post`, `time_user`, `time_seconds`) VALUES (NULL, '$time_added', '$passed_postid', '$authorized_user', '$passed_time');");
				if ($time_post)	{	
					$json_status = $passed_time . ' seconds added to post';
					$json_output[] = array('status' => $json_status, 'error_code' => 200);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'time could not be added - ' . mysqli_error($time_post);
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
					
				}
				
			}
					
		}
		else {
			$json_status = 'post does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => 409);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>