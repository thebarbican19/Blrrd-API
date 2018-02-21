<?php

$instagram_client = "401dfb1b5bc748f19794cd8322ffcbc7";
$instagram_redirect = "http://52.59.224.79/api/services/instagram.php";
$instagram_secret = "558759806e6c4d8f871ed396b4fbb75d";
$instagram_parameters = array('grant_type' => 'authorization_code', 'client_id' => $instagram_client, 'client_secret' => $instagram_secret, 'redirect_uri' => $instagram_redirect, 'code' => $_GET['code']);
$instagram_url = 'https://api.instagram.com/oauth/access_token';

$redirect_context = stream_context_create(array('http' => array('method' => 'POST', 'content' => http_build_query($instagram_parameters))));
$redirect_response = file_get_contents($instagram_url, false, $redirect_context);
$returned_data = json_decode($redirect_response);
$returned_token = $returned_data->access_token;
$returned_username = $returned_data->user->username;
$returned_identifyer = $returned_data->user->id;
$returned_profile = $returned_data->user->profile_picture;
$redirect_url = "Location: blrrd://instagram?tok=" . $returned_token . "&key=" . $returned_identifyer . "&username=" . $returned_username;
	
include '../../index.php';

if (isset($returned_token)) {
	header($redirect_url);
	exit();
	
}

?>