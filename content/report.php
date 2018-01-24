<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_message = mysqli_real_escape_string($database_connect, strip_tags($passed_data['message']));
$passed_item = mysqli_real_escape_string($database_connect, strip_tags($passed_data['item']));
$passed_id = mysqli_real_escape_string($database_connect, strip_tags($passed_data['reportid']));
$passed_limit = (int)$_GET['limit'];
$passed_pagenation = (int)$_GET['pagnation'];

if (empty($passed_limit)) $passed_limit = 50;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET' && $authorized_type == "admin") {
	$report_query = mysqli_query($database_connect, "SELECT `report_id`, `report_reason`, `user_key`, `user_avatar`, `user_name`, `upload_key`, `upload_caption`, `upload_file` FROM `report` LEFT JOIN users on report.report_user LIKE users.user_key LEFT JOIN uploads on report.report_item LIKE uploads.upload_key ORDER BY `report_id` DESC LIMIT $passed_pagenation, $passed_limit");
	while($row = mysqli_fetch_array($report_query)) {	
		$report_post = array("postid" => $row['upload_key'], "file" => $row['upload_file'], "caption" => $row['upload_caption']);
		$report_user = array("userid" => $row['user_key'], "avatar" => $row['user_avatar'], "username" => $row['user_name']);
		$report_output[] = array("upload" => $report_post, "reason" => $row['report_reason'], "reportedby" => $report_user, "id" => $row['report_id']);
		
	}
	
	if (count($report_output) == 0) $report_output = array();
	
	$json_status = 'returned ' . count($report_output) . ' reported items';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $report_output);
	echo json_encode($json_output);
	exit;
	
}
else if ($passed_method == 'POST') {
	if (empty($passed_item)) {
		$json_status = 'post id parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else if (empty($passed_message)) {
		$json_status = 'message parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$report_query = mysqli_query($database_connect, "SELECT * FROM `report` WHERE `report_item` LIKE '$passed_item' AND `report_user` LIKE '$authorized_user' LIMIT 0, 1");
		$report_count = mysqli_num_rows($report_query);
		if ($report_count == 0) {
			$report_query = mysqli_query($database_connect, "INSERT INTO `report` (`report_id`, `report_item`, `report_user`, `report_reason`) VALUES (NULL, '$passed_item', '$authorized_user', '$passed_message');");
			if ($report_query) {
				$json_status = 'post has been reported sucsessfully';
				$json_output[] = array('status' => $json_status, 'error_code' => 200);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'report could not be created - ' . mysqli_error($database_connect);
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
						
			}
			
		}
		else {
			$json_status = 'post has already been reported';
			$json_output[] = array('status' => $json_status, 'error_code' => 409);
			echo json_encode($json_output);
			exit;
				
		}
		
	}

}
else if ($passed_method == 'DELETE' && $authorized_type == "admin") {
	if (empty($passed_id)) {
		$json_status = 'report id parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$report_query = mysqli_query($database_connect, "SELECT * FROM `report` WHERE `report_item` LIKE '$passed_item' AND `report_user` LIKE '$authorized_user' LIMIT 0, 1");
		$report_count = mysqli_num_rows($report_query);
		if ($report_count == 1) {
			$report_delete = mysqli_query($database_connect ,"DELETE FROM `report` WHERE `report_id` = '$passed_id';");
			if ($report_delete) {
				$json_status = 'report has been removed';
				$json_output[] = array('status' => $json_status, 'error_code' => 200);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'report could not be deleted - ' . mysqli_error($database_connect);
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
						
			}
		}
		else {
			$json_status = 'post has already been removed';
			$json_output[] = array('status' => $json_status, 'error_code' => 409);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
			
}
else {
	$json_status = $passed_method . ' menthods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>