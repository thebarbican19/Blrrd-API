<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);

if ($passed_method == 'GET') {
	if ($authorized_type != "admin") {
		$json_status = 'user does not have the privileges to perform this action';
		$json_output[] = array('status' => $json_status, 'error_code' => 401);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$stats_signups_total = user_signups_total();
		$stats_signups_today = user_signups_today();
		$stats_time_today = time_total_today();
		$stats_posts_today = posts_posted_today();
		$stats_active_today = user_active_today();
		$stats_active_total = user_active_total();
				
		$stats_output = array("signups_today" => $stats_signups_today, "signups_total" => $stats_signups_total, "active_today" => $stats_active_today, "active_total" => $stats_active_total, "posts_today" => $stats_posts_today, "time_viewed_today" => $stats_time_today);
		
		$json_status = 'stats returned';
		$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $stats_output);
		echo json_encode($json_output);
		exit;
		
	}
	
	
}
else {
	$json_status = $passed_method . ' menthods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

function user_active_today() {
	global $database_connect;
	
	$active_startdate = date('Y-m-d') . " 00:00:01";
	$active_enddate = date('Y-m-d H:m:s', strtotime($active_startdate . '+1 day'));
	$active_today = mysqli_query($database_connect ,"SELECT * FROM `users` WHERE `user_lastactive` >= '$active_startdate' AND `user_lastactive` <= '$active_enddate' AND `user_status` LIKE 'active'");
	$active_today_count = mysqli_num_rows($active_today);
	
	return (int)$active_today_count;
	
}

function user_signups_today() {
	global $database_connect;
	
	$signups_startdate = date('Y-m-d') . " 00:00:01";
	$signups_enddate = date('Y-m-d H:m:s', strtotime($signups_startdate . '+1 day'));
	$signups_today = mysqli_query($database_connect ,"SELECT * FROM `users` WHERE `user_signup` >= '$signups_startdate' AND `user_signup` <= '$signups_enddate' AND `user_status` LIKE 'active'");
	$signups_today_count = mysqli_num_rows($signups_today);
	
	return (int)$signups_today_count;
	
}

function user_active_total() {
	global $database_connect;
	
	$total_startdate = date('Y-m-d H:m:s', strtotime(date('Y-m-d H:m:s') . '-15 day'));
	$total_enddate = date('Y-m-d H:m:s');
	$total_query = mysqli_query($database_connect ,"SELECT * FROM `users` WHERE `user_lastactive` >= '$total_startdate' AND `user_lastactive` <= '$total_enddate' AND `user_status` LIKE 'active'");
	$total_count = mysqli_num_rows($total_query);
	
	return (int)$total_count;
	
}

function user_signups_total() {
	global $database_connect;
	
	$signups_startdate = date('Y-m-d') . " 00:00:01";
	$signups_enddate = date('Y-m-d H:m:s', strtotime($signups_startdate . '+1 day'));
	$signups_today = mysqli_query($database_connect ,"SELECT * FROM `users` WHERE `user_status` LIKE 'active'");
	$signups_today_count = mysqli_num_rows($signups_today);
	
	return (int)$signups_today_count;
	
}

function posts_posted_today() {
	global $database_connect;
	
	$posts_startdate = date('Y-m-d') . " 00:00:01";
	$posts_enddate = date('Y-m-d H:m:s', strtotime($posts_startdate . '+1 day'));
	$posts_today = mysqli_query($database_connect ,"SELECT * FROM `uploads` WHERE `upload_timestamp` >= '$posts_startdate' AND `upload_timestamp` <= '$posts_enddate' AND `upload_removed` LIKE '0'");
	$posts_today_count = mysqli_num_rows($posts_today);
	
	return (int)$posts_today_count;
	//return 
	
}

function time_total_today() {
	global $database_connect;
	
	$time_startdate = date('Y-m-d') . " 00:00:01";
	$time_enddate = date('Y-m-d H:m:s', strtotime($timestamp_start . '+1 day'));
	$time_today = mysqli_query($database_connect ,"SELECT SUM(time.time_seconds) AS time_total FROM `time` WHERE `time_added` >= '$time_startdate' AND `time_added` <= '$time_enddate' GROUP BY time_user");
	$time_today_seconds = 0;
	while($row = mysqli_fetch_array($time_today)) {	
		$time_today_seconds += $row['time_total'];
		
		
	}
	
	return (int)$time_today_seconds;
	
}
