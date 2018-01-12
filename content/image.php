<?php

require '../lib/auth.php';
require '../lib/vendor/autoload.php';

use Aws\S3\S3Client;

header('Content-Type: application/json');

$passed_image = $_GET['id'];
$passed_size = $_GET['size'];
$passed_format = $_GET['format'];

$passed_format_types = array("image", "json");

if ($passed_method == 'GET') {
	if (empty($passed_image)) {
		$json_status = 'image id parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else if (!empty($passed_format) && !in_array($passed_format, $passed_format_types)) {
		$json_status = 'format type must be image or json';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	
	if (empty($passed_format) || $passed_format == "image") {
		
		
		
	}
	else {
		
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}	

?>