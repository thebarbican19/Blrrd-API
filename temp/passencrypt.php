<?php 

include '../lib/auth.php';
include '../lib/keygen.php';

$user_query = mysqli_query($database_connect, "SELECT * FROM `users` WHERE `user_name` LIKE '% %' LIMIT 0, 1000");
while($row = mysqli_fetch_array($user_query)) {	
	$passed_encryptpassword = password_hash($row['user_password'] ,PASSWORD_BCRYPT);
	$passed_user = $row['user_name'];
	$passed_newkey = "user_" . generate_key();
	echo 'user ' . $row['user_name'] . ' password ' . $row['user_password'] . ' new password ' . $passed_encryptpassword . '<p>';

	//$user_update = mysqli_query($database_connect, "UPDATE `users` SET `user_key` = '$passed_newkey' WHERE `user_name` LIKE '$passed_user';");
	if ($user_update) {
		echo 'updated<p><p>';
		
	}
	
	
}

?>