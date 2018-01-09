<?

$database_connect = mysqli_connect('localhost', 'root', 'root'); 
if (!$database_connect) { 
	header('HTTP/ 400 HOST ERROR', true, 400);
		
	$json_status = 'host not connected';
    $json_output[] = array('status' => $json_status, 'error_code' => '302');
	echo json_encode($json_output);
	exit;
	
} 

$database_table = mysqli_select_db($database_connect, "blrrd");
if (!$database_table) { 
	header('HTTP/ 400 DATABASE ERROR', true, 400);
			
	$json_status = 'database table not found';
    $json_output[] = array('status' => $json_status, 'error_code' => '302');
	echo json_encode($json_output);
	exit;
	
}

$session_headers = $_SERVER;
$session_ip = $_SERVER['REMOTE_ADDR'];
$session_url =  $_SERVER["SERVER_NAME"] . reset(explode('?', $_SERVER["REQUEST_URI"]));
$session_page = str_replace(".php", "", basename($session_url));
$session_bearer = $session_headers["HTTP_BLBEARER"];
$session_method = $_SERVER['REQUEST_METHOD'];
$session_auth_exclude = array("login", "signup", "passencrypt");

if (!in_array($session_page, $session_auth_exclude) || isset($session_bearer)) {
	if (empty($session_bearer)) {	
		header('HTTP/1.1 401 UNAUTHORIZED');

		$json_status = 'bearer token was not passed';
		$json_output[] = array('status' => $json_status, 'error_code' => 401);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$bearer_date = date('Y-m-d H:i:s');
		//$bearer_injection =  "SELECT * FROM `access` LEFT JOIN users on access.access_user LIKE users.user_key WHERE `access_expiry` > '$bearer_date' AND `access_token` LIKE '$session_bearer' LIMIT 0, 1";
		$bearer_injection =  "SELECT * FROM `access`  WHERE `access_expiry` > '$bearer_date' AND `access_token` LIKE '$session_bearer' LIMIT 0, 1";		
		$bearer_query = mysqli_query($database_connect, $bearer_injection);
		$bearer_isvalid = mysqli_num_rows($bearer_query);
		if ($bearer_isvalid == 0) {
			header('HTTP/1.1 401 UNAUTHORIZED');

			$json_status = 'bearer token invalid';
			$json_output[] = array('status' => $json_status, 'error_code' => 401);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$authorized_data = mysqli_fetch_assoc($bearer_query);
			$authorized_user = $authorized_data['user_key'];
			$authorized_username = $authorized_data['user_name'];
			$authorized_token = $authorized_data['access_token'];
				
			$authuser_update = mysqli_query($database_connect, "UPDATE `users` SET `user_lastactive` = CURRENT_TIMESTAMP WHERE `user_key` LIKE '$authorized_user';");
					
		}
		
	}
	
}

?>