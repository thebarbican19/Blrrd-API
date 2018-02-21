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
		
	$posts_query = mysqli_query($database_connect ,"SELECT * FROM `uploads` WHERE `upload_owner` LIKE '$user' AND `upload_removed` LIKE '0'");
	$posts_count = mysqli_num_rows($posts_query);
	
	return $posts_count;
	
}

function user_total_time($user) {
	global $database_connect;
		
	
	$time_injection = "SELECT `upload_key`, `upload_owner`, SUM(time.time_seconds) AS upload_time FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post WHERE `upload_owner` LIKE '$user' AND `upload_removed` LIKE '0' GROUP BY upload_key";
	$time_query = mysqli_query($database_connect ,$time_injection);
	$time_count = mysqli_num_rows($time_query);
	$time_total = 0;
	while($row = mysqli_fetch_array($time_query)) {
		$time_total += (int)$row['upload_time'];
		
	}
				
	return $time_total;
	
}

function user_total_followers($user) {
	global $database_connect;
		
	$follower_query = mysqli_query($database_connect ,"SELECT * FROM `follow` WHERE `follow_user` LIKE '$user'");
	$follower_count = mysqli_num_rows($follower_query);
	
	return $follower_count;
	
}

function user_posts_time($post) {
	global $database_connect;
		
	$posts_query = mysqli_query($database_connect ,"SELECT upload_key, SUM(time.time_seconds) AS upload_score FROM `uploads` LEFT JOIN time on uploads.upload_key LIKE time.time_post LEFT JOIN users on uploads.upload_owner LIKE users.user_key WHERE `upload_key` LIKE '$post' GROUP BY upload_key");
	$posts_data = mysqli_fetch_assoc($posts_query);	
	
	return (int)$posts_data['upload_score'];
	
}

function user_following($user) {
	global $database_connect;
	global $authorized_user;
	
	$follower_query = mysqli_query($database_connect ,"SELECT * FROM `follow` WHERE `follow_user` LIKE '$user' AND `follow_owner` LIKE '$authorized_user'");
	$follower_count = mysqli_num_rows($follower_query);
	
	if ($follower_count == 1) return true;
	else return false;
	
}

function user_follows($user) {
	global $database_connect;
	global $authorized_user;
	
	$follower_query = mysqli_query($database_connect ,"SELECT * FROM `follow` WHERE `follow_user` LIKE '$authorized_user' AND `follow_owner` LIKE '$user'");
	$follower_count = mysqli_num_rows($follower_query);
	
	if ($follower_count == 1) return true;
	else return false;
	
}


?>
