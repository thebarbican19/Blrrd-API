<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];

if ($passed_method == 'GET') {
	$notice_query = mysqli_query($database_connect, "SELECT * FROM `notices` LEFT JOIN users AS createdby on notices.notice_user LIKE createdby.user_key LEFT JOIN users AS approvedby on notices.notice_approved  LIKE approvedby.user_key LIMIT 0, 1");
	$notice_data = mysqli_fetch_assoc($notice_query);
	$notice_approved = $notice_data['user_name'];
	
	
	$notification_query = mysqli_query($database_connect, "SELECT * FROM `notifications` WHERE `notification_owner` LIKE '$authorized_user' ORDER BY `notification_timestamp` DESC LIMIT 0, 50");
	while($notification = mysqli_fetch_array($notification_query)) {	
		$notifcation_type = (string)$notification['notification_type'];
		$notifcation_message = (string)$notification['notifcation_message'];
		$notifcation_data = (string)$notification['notification_data'];
		$notifcation_viewed = (bool)$notification['notification_viewed'];
		if (empty($notification['notification_timezone'])) $notication_timezone = "+0000";
		else $notication_timezone = $notification['notification_timezone'];
		$notication_timezone = $notification['notification_timezone'];
		$notication_timestamp = $notification['notification_timestamp'] . " " . $notication_timezone;
		$notification_output[] = array("type" => $notifcation_type, "message" => $notifcation_message, "data" => $notifcation_data, "timestamp" => $notication_timestamp, "viewed" => $notifcation_viewed);
		
	}
	
	if (count($notification_output) == 0) $notification_output = array();	
	
	$json_status =  count($notification_output) . ' notifications returned';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $notification_output);
	echo json_encode($json_output);
	exit;
	
	
}
else if ($passed_method == 'PUT') {
	$notification_query = mysqli_query($database_connect, "SELECT `notification_id` FROM `notifications` WHERE `notification_owner` LIKE '$authorized_user' AND `notification_viewed` = '0' LIMIT 0, 50");
	while($notification = mysqli_fetch_array($notification_query)) {	
		$notification_id = $notification['notification_id'];
		$notification_update = mysqli_query($database_connect, "UPDATE `notifications` SET `notification_viewed` = '1' WHERE `notification_id` = $notification_id;");
		$notification_output[] = $notification_id;
		
	}
	
	if (count($notification_output) > 0)  {
		$json_status = count($notification_id) . ' notifications have bee marked as viewed';
		$json_output[] = array('status' => $json_status, 'error_code' => 200);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$json_status = 'no notifications to be updated';
		$json_output[] = array('status' => $json_status, 'error_code' => 200);
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