<?php 

include '../lib/auth.php';
include '../lib/keygen.php';

$content_query = mysqli_query($database_connect, "SELECT * FROM `users` LIMIT 0, 1000");
while($row = mysqli_fetch_array($content_query)) {		
	$user_id = (int)$row['user_id'];
	
	//$view_timestamp_format = date(format)
		
	//if (!empty($content_key)) {
		//$update_content = mysqli_query($database_connect, "UPDATE `users` SET `user_type` = 'user' WHERE `user_id` = $user_id;");
		if ($update_content) {
			echo 'updated<p><p>';
			
		}
		else {
			echo 'not updated<p><p>';
						
		}
		
		
	//}
	
	/*
	if (!empty($user_name)) {
		$upload_newkey = "user_" . generate_key();
		//$update_content = mysqli_query($database_connect, "UPDATE `uploads` SET `upload_key` = '$upload_newkey' WHERE `publicpath` LIKE '$upload_path';");
		if ($update_content) {
			echo 'updated<p><p>';
			
		}
		else {
			echo 'not updated<p><p>';
						
		}
		
	}
	else {
		//$update_content = mysqli_query($database_connect, "DELETE FROM `uploads` WHERE `publicpath` LIKE '$upload_path';");
		if ($update_content) {
			echo 'deleted<p><p>';
			
		}	
	}
	*/
	
	
	
}

?>