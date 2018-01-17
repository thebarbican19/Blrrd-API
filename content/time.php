<?php

include '../lib/auth.php';
include '../lib/push.php';
include '../lib/stats.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);

if ($passed_method == 'GET') {
	$passed_postid = $_GET['postid'];
	$passed_limit = $_GET['limit'];
	$passed_pagenation = $_GET['pagnation'];
	
	if (empty($passed_limit)) $passed_limit = 20;
	if (empty($passed_pagenation)) $passed_pagenation = 0;

	if (empty($passed_postid)) $time_injection = "SELECT `time_post`, `time_user`, `time_added`, `time_seconds`, `upload_file`, `user_key`, `user_name`, `user_lastactive`, `user_avatar` FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key LEFT JOIN users on users.user_key LIKE uploads.upload_owner WHERE `upload_owner` LIKE '$authorized_user'";
	else if (!empty($passed_postid)) $time_injection = "SELECT `time_post`, `time_user`, `time_added`, `time_seconds`, `upload_file`, `user_key`, `user_name`, `user_lastactive`, `user_avatar` FROM `time` LEFT JOIN uploads on time.time_post LIKE uploads.upload_key LEFT JOIN users on users.user_key LIKE uploads.upload_owner WHERE `time_post` LIKE '$passed_postid' AND `upload_owner` LIKE '$authorized_user'";
	
	$time_injection .= " ORDER BY `time_added` DESC LIMIT $passed_pagenation, $passed_limit";
	$time_query = mysqli_query($database_connect, $time_injection);
	$time_items_count = mysqli_num_rows($time_query);
	while($row = mysqli_fetch_array($time_query)) {	
		$time_user = array("userid" => (string)$row['user_key'], 
								 "avatar" => (string)$row['user_avatar'],
								 "username" => (string)$row['user_name'],
								 "lastactive" => $row['user_lastactive']);	
		$time_output[] = array("postid" => $row['time_post'], "user" => $time_user, "timestamp" => $row['time_added'], "seconds" => (int)$row['time_seconds'], "imageurl" => $row['upload_file']);
		
	}
	
	if (count($time_output) == 0) $time_output = array();		
	
	header('HTTP/1.1 200 SUCSESSFUL');
							
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
		$post_query = mysqli_query($database_connect, "SELECT `upload_key`, `upload_file`, `upload_owner` FROM `uploads` WHERE `upload_key` LIKE '$passed_postid' LIMIT 0, 1");
		$post_exists = mysqli_num_rows($post_query);
		if ($post_exists == 1)	{
			$post_data = mysqli_fetch_assoc($post_query);
			$post_removed = (int)$post_data['upload_removed'];
			$post_user = $post_data['upload_owner'];
			$post_file = $post_data['upload_file'];	
			$post_image = "http://52.59.224.79/api/content/image.php?id=" . $post_file . "&tok=" . $session_bearer;
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
				$time_total_before = user_posts_time($passed_postid);				
				$time_added = date('Y-m-d H:i:s');
				$time_post = mysqli_query($database_connect, "INSERT INTO `time` (`time_id`, `time_added`, `time_post`, `time_user`, `time_seconds`) VALUES (NULL, '$time_added', '$passed_postid', '$authorized_user', '$passed_time');");
				if ($time_post)	{	
					header('HTTP/1.1 200 SUCSESSFUL');
					
					$post_total_after = user_posts_time($passed_postid);
					if ($post_total_after > 180 && $time_total_before < 180) {
						$push_user = $post_data['upload_owner'];
						$push_payload = array();
						$push_title = "🔥 Hot Stuff!";
						$push_body =  "Your post was just promoted to the hot feed!";
						
					}
					else {
						$push_user = $post_data['upload_owner'];
						$push_payload = array();
						$push_title = "⏳ You got time!";
						if ($passed_time == 1) $push_body = $authorized_username . " viewed your post for " . $passed_time . " second";
						else $push_body =  $authorized_username . " viewed your post for " . $passed_time . " seconds";
						
					}
					
					$push_payload = array("mutableContent" => true, "attachment-url" => $post_image);
					$push_output = sent_push_to_user($push_user, $push_payload, $push_title, $push_body);
				
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