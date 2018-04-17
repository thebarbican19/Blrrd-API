<?php

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/push.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_comment = mysqli_real_escape_string($database_connect, $_GET['comment']);
$passed_seconds = (int)$passed_data['seconds'];
$passed_key = $_GET['key'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];

if ($passed_method == 'GET') $passed_item = $_GET['item'];
elseif ($passed_method == 'POST') $passed_item = $passed_data['item'];

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;
if (empty($passed_type)) $passed_type = "public";

$passed_pagenation = $passed_pagenation * $passed_limit;
$passed_allowed_types = array("public", "private");

if ($passed_method == 'GET') {
	if (empty($passed_item)) {
		$json_status = 'item parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$comment_query = mysqli_query($database_connect, "SELECT * FROM `comments` LEFT JOIN users on comments.comment_user LIKE users.user_key WHERE `comment_post` LIKE '$passed_item' ORDER BY `comment_timestamp` ASC LIMIT $passed_pagenation, $passed_limit;");
		$comment_count = mysqli_num_rows($comment_query);
		while($row = mysqli_fetch_array($comment_query)) {
			$comment_key = (string)$row['comment_key'];
			$comment_content = (string)$row['comment_content'];
			$comment_type = (string)$row['comment_type'];	
			if (empty($row['upload_timezone'])) $comment_timezone = "+0000";
			else $comment_timezone = $row['upload_timezone'];
			$comment_timestamp = $row['comment_timestamp'] . " " . $comment_timezone;	
			$comment_user_key = (string)$row['comment_user'];
			$comment_user_status = (string)$row['user_status'];				
			$comment_user_name = (string)$row['user_name'];
			$comment_user_avatar = (string)$row['user_avatar'];
			$comment_user_fullname = (string)$row['user_fullname'];
							
			if ($comment_user_status == "active") $comment_user_output = $comment_user_output = array("key" => $comment_user_key, "handle" => $comment_user_name, "name" => $comment_user_fullname, "avatar" => $comment_user_avatar);
			else $comment_user_output = array("key" => $comment_user_key, "handle" => "unknown", "name" => "Unknown User", "avatar" => "", "timestamp" => $comment_timestamp);
		
			if ($comment_type == "private" && isset($authuser_key) && ($comment_user == $authuser_key || $stream_owner == $authuser_key)) $comment_show = true;
			elseif ($comment_type == "public") $comment_show = true;
			else $comment_show = false;
			
			if ($comment_show == true) $comment_output[] = array("key" => $comment_key, "type" => $comment_type, "user" => $comment_user_output, "comment" => $comment_content, "timestamp" => $comment_timestamp);
			
		}
		
		if (count($comment_output) == 0) $comment_output = array();	
		
		$json_status = count($comment_output) . ' comments returned';
		$json_output[] = array('status' => $json_status, 'error_code' => 200, 'comments' => $comment_output);
		echo json_encode($json_output);
		exit;
		
	}
						
}
else if ($passed_method == 'POST') {
	if (empty($passed_comment)) {
		$json_status = 'comment parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (strlen($passed_comment) < 1)	{
		$json_status = 'comment is too short, limit is 1 characters';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (strlen($passed_comment) > 300)	{
		$json_status = 'comment is too long, limit is 300 characters';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (empty($passed_item)) {
		$json_status = 'item parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$push_message = strip_tags($passed_comment);
		$passed_comment = strip_tags($passed_comment);
		$passed_comment = str_replace("'", "\'", $passed_comment);
		$passed_comment = str_replace("\n", " ", $passed_comment);
		$passed_comment = preg_replace('/\s+/', ' ',$passed_comment);
		
		preg_match_all("/(@\w+)/", $passed_comment, $user_mentions);
		
		$item_query = mysqli_query($database_connect, "SELECT `upload_key`, `upload_owner`, `upload_file` FROM `uploads` WHERE `upload_key` LIKE '$passed_item' AND `upload_removed` = 0 LIMIT 0, 1");
		$item_exists = mysqli_num_rows($item_query);
		$item_data = mysqli_fetch_assoc($item_query);
		$item_owner = $item_data['upload_owner'];
		$item_file = $item_data['upload_file'];
		if ($item_exists == 1) {
			$existing_query = mysqli_query($database_connect, "SELECT * FROM `comments` WHERE `comment_user` LIKE '$authuser_key' AND `comment_message` LIKE '$passed_comment' LIMIT 0, 1");
			$existing_count = mysqli_num_rows($existing_query);
			if ($existing_count == 0) {
				$comment_key = "com_" . generate_key();
				$comment_timestamp = date('Y-m-d H:i:s');
				$comment_user_output = array("key" => $authuser_key, "username" => $authuser_username, "avatar" => $authuser_avatar, "timestamp" => $comment_timestamp);
				$comment_output = array("key" => $comment_key, "type" =>  $passed_type, "user" => $comment_user_output, "comment" => $passed_comment);
				$comment_post = mysqli_query($database_connect, "INSERT INTO `comments` (`comment_id`, `comment_key`, `comment_timestamp`, `comment_timezone`, `comment_user`, `comment_post`, `comment_second`, `comment_content`, `comment_type`) VALUES (NULL, '$comment_key', CURRENT_TIMESTAMP, '$session_timezone', '$authorized_user', '$passed_item', '$passed_seconds', '$passed_comment', '$passed_type');");
				if ($comment_post) {
					foreach ($user_mentions as $user_nickname) {
						$mention_nickname = str_replace("@", "", $user_nickname);
						$mention_query = mysqli_query($database_connect, "SELECT `user_name`, FROM `users` WHERE `user_status` LIKE 'active' AND `user_name` LIKE '$mention_nickname'");
						$mention_exists = mysqli_num_rows($mention_query);
						if ($mention_exists == 1) {
							$mention_data = mysql_fetch_assoc($mention_query);
							$mention_user = $mention_data['user_name'];
							
							$push_title = "👋 " . $authuser_username . " mentioned you in a comment";
							$push_body =  $passed_comment;
							$push_payload = array("mutableContent" => true, "attachment-url" => $item_file);
							$push_output = sent_push_to_user($item_owner, $push_payload, $push_title, $push_body);
						
						}
						
					}
					
					$push_title = "👫 " . $authuser_username . " commented on your post";
					$push_body =  $passed_comment;
					$push_payload = array("mutableContent" => true, "attachment-url" => $item_file);
					$push_output = sent_push_to_user($item_owner, $push_payload, $push_title, $push_body);
						
					header('HTTP/1.1 200 SUCSESSFUL');				
					
					$json_status = 'comment posted';
					$json_output[] = array('status' => $json_status, 'error_code' => 200);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'comment could not be posted - ' . mysqli_error($database_connect);
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'comment was already posted';
				$json_output[] = array('status' => $json_status, 'error_code' => 409);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'item does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => 409);
			echo json_encode($json_output);
			exit;
			
		}
	
	}
	
}
else if ($passed_method == 'DELETE') {
	if (empty($passed_key)) {
		$json_status = 'comment key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {		
		$existing_query = mysqli_query($database_connect, "SELECT * FROM `comments` WHERE `comment_key` LIKE '$passed_key' LIMIT 0, 1");
		$existing_output = mysqli_fetch_assoc($existing_query);
		$exiting_owner = $existing_output['comment_user'];
		if ($exiting_owner == $authorized_user || $authorized_type == "admin" && !empty($exiting_owner)) {		
			$comment_delete = mysqli_query($database_connect, "DELETE FROM `comments` WHERE `comment_key` LIKE '$passed_key';");
			if ($comment_delete) {
				$json_status = 'comment deleted';
				$json_output[] = array('status' => $json_status, 'error_code' => 200);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'comment could not be deleted - ' . mysqli_error($database_connect);
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'content does not exist or you do not have permission to delete it';
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