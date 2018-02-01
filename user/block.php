<?php

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);

if ($passed_method == 'POST') {
	$json_status = 'user blocked';
	$json_output[] = array('status' => $json_status, 'error_code' => 200);
	echo json_encode($json_output);
	exit;
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>

orrivo2018