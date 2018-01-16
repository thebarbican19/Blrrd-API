<?php

require '../lib/auth.php';
require '../lib/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
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
	
	
	$client_key = "AKIAIQNEGJPW4HZ64K5A";
	$client_secret = "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";
	$client_bucket = "blrrd-images";	
	$client = S3Client::factory(array('credentials' => array('key' => $client_key, 'secret' => $client_secret), 'version' => 'latest', 'region'  => 'eu-central-1'));
		
	if ($passed_format == "image" || empty($passed_format)) {
		$object_url = $client->getObjectUrl($client_bucket, $passed_image);
		$object_content = file_get_contents($object_url);
		$object_open = finfo_open();
		$object_type = "Content-Type: " . finfo_buffer($file_open, $object_content, FILEINFO_MIME_TYPE);
		
		header($object_type);
		
		echo $object_content;
		
	}
	else {
		if ($authorized_type == "admin") {
			$object_output = $client->getObject(array('Bucket' => $client_bucket, 'Key' => $passed_image));
			//print_r($object_output['Metadata']);
			//print_r($object_output);
					
		}
		else {
			header('HTTP/1.1 401 UNAUTHORIZED');

			$json_status = 'you do not have permission to access this endpoint';
			$json_output[] = array('status' => $json_status, 'error_code' => 401);
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