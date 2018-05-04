<?php

include '../lib/auth.php';
include '../lib/push.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_limit = (int)$_GET['limit'];
$passed_pagenation = (int)$_GET['pagnation'];
$passed_userid = $_GET['userid'];
$passed_alert = (bool)$_GET['alert'];

if (empty($passed_limit)) $passed_limit = 40;
if (empty($passed_pagenation)) $passed_pagenation = 0;
	
$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	$friendship_query = mysqli_query($database_connect, "SELECT * FROM `follow` LEFT JOIN users on follow.follow_user LIKE users.user_key WHERE `follow_owner` LIKE '$authorized_user' ORDER BY `follow_timestamp` DESC LIMIT $passed_pagenation, $passed_limit");
	$friendship_count = mysqli_num_rows($friendship_query);
	while($row = mysqli_fetch_array($friendship_query)) {
		$friendship_avatar = "https://ovatar.io/" . $row['user_email'];
		$friendship_username = $row['user_name'];
		$friendship_lastactive = $row['user_lastactive'];
		$friendship_userid = $row['user_key'];
		$friendship_userpublic = (bool)$row['user_public'];	
		$friendship_userpromoted = (bool)$row['user_promoted'];	
		$friendship_notifications = (bool)$row['follow_notifications'];	
		$friendship_user = array("userid" => $friendship_userid, 
								 "avatar" => $friendship_avatar, 
								 "username" => $friendship_username, 
								 "lastactive" => $friendship_lastactive,
								 "public" => $friendship_userpublic, 
								 "promoted" => $friendship_userpromoted,
								 "alerts" => $friendship_notifications);
		$friendship_output[] = array("timestamp" => $row['follow_timestamp'], "user" => $friendship_user);
		
	}
	
	if (count($friendship_output) == 0) $friendship_output = array();		
	
	$json_status = 'returned ' . $friendship_count . ' friends';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $friendship_output);
	echo json_encode($json_output);
	exit;	
	
	
}
else if ($passed_method == 'POST') {
	if (empty($passed_userid)) {
		$json_status = 'user id parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$user_query = mysqli_query($database_connect, "SELECT * FROM `users` WHERE `user_key` LIKE '$passed_userid' AND `user_status` LIKE 'active' LIMIT 0, 1");
		$user_exists = mysqli_num_rows($user_query);
		if ($user_exists == 1) {
			$follow_query = mysqli_query($database_connect, "SELECT * FROM `follow` WHERE `follow_user` LIKE '$passed_userid' AND `follow_owner` LIKE '$authorized_user'");
			$follow_exists = mysqli_num_rows($follow_query);
			if ($follow_exists == 0) {
				$friendship_timestamp = date('Y-m-d H:i:s');
				$friendship_create = mysqli_query($database_connect, "INSERT INTO `follow` (`follow_id`, `follow_timestamp`, `follow_user`, `follow_owner`, `follow_notifications`) VALUES (NULL, '$friendship_timestamp', '$passed_userid', '$authorized_user', '0');");
				if ($friendship_create) {
					header('HTTP/1.1 200 SUCSESSFUL');
							
					$notification_title = "*" . $authorized_username . "* just followed you";						
					$notification_user = $passed_userid;				
					$notification_body =  "Open Blrrd to see their profile, who knows you might find something you like.";
					$notification_output = add_notifcation($notification_title, $notification_body, "", $notification_user, "follow", $passed_userid, true);
					
					$json_status = 'user followed!';
					$json_output[] = array('status' => $json_status, 'error_code' => 200);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'user could not be followed at this time - ' . mysqli_error($database_connect);
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
					
				}
						
				
			}
			else {
				$json_status = 'you are already following this user';
				$json_output[] = array('status' => $json_status, 'error_code' => 200);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'user does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => 409);
			echo json_encode($json_output);
			exit;
				
		}
		
	}
	
}
else if ($passed_method == 'PUT') {
	if (empty($passed_userid)) {
		$json_status = 'user id parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$update_query = mysqli_query($database_connect, "UPDATE `follow` SET `follow_notifications` = '$passed_alert' WHERE `follow_owner` LIKE '$authorized_user' AND `follow_user` LIKE '$passed_userid';");
		if ($update_query) {
			header('HTTP/1.1 200 SUCSESSFUL');
									
			if ($passed_alert) $alert_status = "enabled";
			else $alert_status = "disabled";
			
			$json_status = 'alerts ' . $alert_status . ' for user';
			$json_output[] = array('status' => $json_status, 'error_code' => 200);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'alerts could not be updated at this time - ' . mysqli_error($update_query);
			$json_output[] = array('status' => $json_status, 'error_code' => 400);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
else if ($passed_method == 'DELETE') {
	$follow_query = mysqli_query($database_connect, "SELECT * FROM `follow` WHERE `follow_user` LIKE '$passed_userid' AND `follow_owner` LIKE '$authorized_user'");
	$follow_exists = mysqli_num_rows($follow_query);
	$follow_data = mysqli_fetch_assoc($follow_query);
	$follow_id = $follow_data['follow_id'];
	
	if ($follow_exists == 0) {
		$json_status = 'you are not following this user';
		$json_output[] = array('status' => $json_status, 'error_code' => 403, 'user' => $passed_userid);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$friendship_destroy = mysqli_query($database_connect, "DELETE FROM `follow` WHERE `follow_id` = '$follow_id';");
		if ($friendship_destroy) {
			header('HTTP/1.1 200 SUCSESSFUL');
									
			$json_status = 'user unfollowed!';
			$json_output[] = array('status' => $json_status, 'error_code' => 200);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'user could not be unfollowed at this time - ' . mysqli_error($friendship_destroy);
			$json_output[] = array('status' => $json_status, 'error_code' => 400);
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