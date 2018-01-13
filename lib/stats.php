<?php

function user_stats($user) {
	if (empty($user)) global $authorized_user;
	else $authorized_user = $user;
	
	$user_posts_count = user_posts($authorized_user);
	$user_total_time = user_total_time($authorized_user);
	$user_total_followers = user_total_followers($authorized_user);
			
	return array("posts" => (int)$user_posts_count, "totaltime" => (int)$user_total_time, "followers" => (int)$user_total_followers);
	
}

function user_posts($user) {
	global $database_connect;
		
	$posts_query = mysqli_query($database_connect ,"SELECT * FROM `uploads` WHERE `upload_owner` LIKE '$user'");
	$posts_count = mysqli_num_rows($posts_query);
	
	return $posts_count;
	
}

function user_total_time($user) {
	global $database_connect;
		
	$time_query = mysqli_query($database_connect ,"SELECT `upload_key`, `upload_owner`, SUM(time.time_seconds) AS upload_time FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post WHERE `upload_owner` LIKE '$user' GROUP BY upload_key");
	$time_count = mysqli_num_rows($time_query);
	$time_total = 0;
	while($row = mysqli_fetch_array($time_query)) {
		$upload_time =+ $row['upload_time'];
		
	}
				
	return $upload_time;
	
}

function user_total_followers($user) {
	global $database_connect;
		
	$follower_query = mysqli_query($database_connect ,"SELECT * FROM `follow` WHERE `follow_user` LIKE '$user'");
	$follower_count = mysqli_num_rows($follower_query);
	
	return $follower_count;
	
}

function user_following($user) {
	global $database_connect;
	global $authorized_user;
	
	$follower_query = mysqli_query($database_connect ,"SELECT * FROM `follow` WHERE `follow_user` LIKE '$user' AND `follow_owner` LIKE '$authorized_user'");
	$follower_count = mysqli_num_rows($follower_query);
	
	if ($follower_count == 1) return true;
	else return false;
	
}

?>
