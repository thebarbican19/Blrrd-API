<?php

function user_stats() {
	global $authorized_user;
	
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
		
	$time_query = mysqli_query($database_connect ,"");
	$time_count = mysqli_num_rows($time_query);
	
	return $time_count;
	
}

function user_total_followers($user) {
	global $database_connect;
		
	$follower_query = mysqli_query($database_connect ,"SELECT * FROM `follow` WHERE `follow_user` LIKE '$user'");
	$follower_count = mysqli_num_rows($follower_query);
	
	return $follower_count;
	
}

?>
