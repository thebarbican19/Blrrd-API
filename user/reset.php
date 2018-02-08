<?php

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_email = mysqli_real_escape_string($database_connect, $passed_data['email']);

if ($passed_method == 'POST') {
	if (empty($passed_email)) {
		$json_status = 'email parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$user_query = mysqli_query($database_connect, "SELECT * FROM  `users` WHERE  `user_status` LIKE  'active' AND  `user_email` LIKE  '$passed_email' LIMIT 0, 1");
		$user_exists = mysqli_num_rows($user_query);
		$user_data = mysqli_fetch_assoc($user_query);
		$user_key = $user_data['user_key'];
		$user_name = $user_data['user_name'];
		if ($user_exists == 1) {
			$user_password = generate_password();
			$user_passwordencripted = password_hash($user_password ,PASSWORD_BCRYPT);
			$user_update = mysqli_query($database_connect, "UPDATE `users` SET `user_password` = '$user_passwordencripted' WHERE `user_email` LIKE '$passed_email';");
			if (!empty($session_devicename)) $user_device = "(" . $session_devicename . ") ";
			else $user_device = "";
			
			if ($user_update) {
				$email_subject = "Password Reset";
				$email_body .= "Your password has been reset, here is your new password (you can click it to auto login if your on iOS)";
				$email_body .= "<p><center>";
				$email_body .= "<a href='blrrd://login?newpass=" . $user_password . "' class='action'>" . $user_password . "</a>";
				$email_body .= "</center>";				
				$email_body .= "<p>You can change this to something more memorable by navigating to <strong>Settings</strong> > <strong>User Password</strong> in the app.";
				$email_body .= "<p>This request was made via the <strong>Blrrd iOS app " . $user_device . "</strong> on <strong>" . date('d M Y') . " at " . date('H:i') . "</strong>, if you did not make this request or if this email is unexpected please reply to this email.";	
							
				$email_push = email_user($email_subject, $email_body, $passed_email, NULL);
				
				$json_status = $email_push['status'];
				$json_output[] = array('status' => $json_status, 'error_code' => (int)$email_push['error_code']);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'Password could not be reset - ' . mysqli_error($database_connect);
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'User does not exist with the email ' . $passed_email;
			$json_output[] = array('status' => $json_status, 'error_code' => 350);
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