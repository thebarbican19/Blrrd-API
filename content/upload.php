<?php

require '../lib/auth.php';
require '../lib/push.php';
require '../lib/keygen.php';
require '../lib/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_caption = strip_tags($passed_data['caption']);
$passed_caption = mysqli_real_escape_string($database_connect, $passed_caption);
$passed_file = $passed_data['file'];
$passed_id = $passed_data['postid'];
$passed_location = explode(",", $passed_data['latlng']);
$passed_latitude = (float)$passed_location[0];
$passed_longitude = (float)$passed_location[1];
	
if ($passed_method == 'POST') {
	$file_data = base64_decode($passed_file);
	$file_size = strlen($file_data);
	$file_open = finfo_open();
	$file_type = finfo_buffer($file_open, $file_data, FILEINFO_MIME_TYPE);
	$file_extension = strtolower(end(explode("/", $file_type)));
	$file_gen = "img" . generate_key() . "." . md5($authorized_user);
	$file_tempdir = "../temp/" . $file_gen . "." . $file_extension;
	
	$files_allowed = array("jpeg", "jpg", "png", "gif");

	if (empty($passed_caption) || strlen($passed_caption) < 1) {
		$json_status = 'caption too short';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else if (strlen($passed_caption) > 300) {
		$json_status = 'caption too long';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else if ($file_size < 1024) {
		$json_status = 'image is too small';
		$json_output[] = array('status' => $json_status, 'error_code' => 301);
		echo json_encode($json_output);
		exit;
		
	}
	elseif ($file_size > 1024*4000) {
		$json_status = 'image size exceeds limit of 2mb';
		$json_output[] = array('status' => $json_status, 'error_code' => 301);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (!in_array($file_extension, $files_allowed)) {
		$json_status = $file_extension . ' type is not supported';
		$json_output[] = array('status' => $json_status, 'error_code' => 301);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$client_key = "AKIAIQNEGJPW4HZ64K5A";
		$client_secret = "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";
		$client_bucket = "blrrd-images";	
		$client = S3Client::factory(array('credentials' => array('key' => $client_key, 'secret' => $client_secret), 'version' => 'latest', 'region'  => 'eu-central-1'));

		if (file_put_contents($file_tempdir, $file_data)) {	
			$image_data = fopen($file_tempdir);
			$image_exif = exif_read_data($file_tempdir, 0, true);
			$image_orenation = $image_exif['IFD0']['Orientation'];
			$image_device = $image_exif['IFD0']['Model'];
			$image_camsoftware = $image_exif['IFD0']['Software'];
			$image_cammake = $image_exif['IFD0']['Make'];
			if (count($passed_location) > 1) {
				$image_longitude = $passed_longitude;
				$image_latitude = $passed_latitude;
				
			}
			else {
				$image_location = $image_exif["GPS"];
				$image_longitude = gpscoordinates($image_location["GPSLongitude"], $image_location['GPSLongitudeRef']);
				$image_latitude = gpscoordinates($image_location["GPSLatitude"], $image_location['GPSLatitudeRef']);
				
			}
			
			$image_metadata = array("User" => $authuser_key, "Caption" => $passed_caption, "Latitude" => $image_latitude, "Longitude" => $image_longitude);			
			$image_upload = $client->putObject(array(
			    'Bucket'       => $client_bucket,
			    'Key'          => $file_gen,
			    'SourceFile'   => $file_tempdir,
			    'ContentType'  => $file_type,
			    'ACL'          => 'public-read',
			    'Metadata'     => $image_metadata
					
			));
		
			if ($image_upload) {
				if (unlink($file_tempdir)) {
					preg_match_all("/(#\w+)/", $passed_caption, $image_tags);
					preg_match_all("/(@\w+)/", $passed_caption, $image_users);
												
					if (count(reset($image_tags)) > 0) $image_tags_formatted .= implode(",", str_replace("#", "", reset($image_tags))) . ",";				
					if (!empty($image_device)) $image_tags_formatted .= $image_device . ",";
					if (!empty($image_software)) $image_tags_formatted .= $image_software . ",";
					if (!empty($image_cammake)) $image_tags_formatted .= $image_cammake . ",";	
					$image_tags_formatted = substr($image_tags_formatted, 0, strlen($image_tags_formatted) - 1);
					$image_tags_formatted = strtolower(str_replace(" ", "", $image_tags_formatted));
					$image_file = end(explode("/", $image_upload['ObjectURL']));
					$image_key = "img_" . generate_key();
					$image_timestamp = date('Y-m-d H:i:s');
					$image_timezone = $session_timezone;
					
					
					if (count(reset($image_users)) > 0) {
						$tagged_user = str_replace("@", "", reset($image_users));
						foreach ($tagged_user as $user) {
							$tagged_user_query = mysqli_query($database_connect, "SELECT `user_key` FROM `users` WHERE `user_name` LIKE '$user' LIMIT 0, 1");
							$tagged_user_data = mysqli_fetch_assoc($tagged_user_query);
							$tagged_user_key[] = $tagged_user_data['user_key'];
							
							$push_user = $tagged_user_data['user_key'];
							$push_payload = array();
							$push_title = "👋 You were tagged by @" . $authorized_username;
							$push_body =  "'" . str_replace("@", "", $passed_caption) . "'";
							$push_payload = array("mutableContent" => true, "attachment-url" => $post_image);
							$push_output = sent_push_to_user($push_user, $push_payload, $push_title, $push_body);
							
						}
						
					}
					
					$image_users_tagged = implode(",", $tagged_user_key);
					$image_store = mysqli_query($database_connect, "INSERT INTO `uploads` (`upload_id`, `upload_timestamp`, `upload_timezone`, `upload_key`, `upload_owner`, `upload_file`, `upload_caption`,`upload_url`, `upload_tags`, `upload_latitude`, `upload_longitude`, `upload_place`, `upload_userstagged`, `upload_device`, `upload_channel`, `upload_removed`) VALUES (NULL, '$image_timestamp', '$image_timezone', '$image_key', '$authorized_user', '$image_file', '$passed_caption', '', '$image_tags_formatted', '$image_latitude', '$image_longitude', '', '$image_users_tagged', '$image_device', '', '0');");
					$image_output = array("key" => $image_key, "caption" => $passed_caption);
					if ($image_store) {
						if ($image_longitude != 0.000 && $image_latitude != 0.000) {
							$authuser_update = mysqli_query($database_connect, "UPDATE `users` SET `user_latitude` = '$image_latitude', `user_longitude` = '$image_longitude' WHERE `user_key` LIKE '$authorized_user';");
																	
						}
						
						header('HTTP/1.1 200 SUCSESSFUL');
								
						$json_status = 'image uploaded';
						$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $image_output);
						echo json_encode($json_output);
						exit;
						
					}
					else {
						$json_status = 'image could not be stored in database - ' . mysqli_error($database_connect);
						$json_output[] = array('status' => $json_status, 'error_code' => 400);
						echo json_encode($json_output);
						exit;
					
					}
					
				}
				else {
					$json_status = 'image could not be removed';
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
						
				}
					
			}
			else {
				$json_status = 'image could not be uploaded';
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'image could not be uploaded';
			$json_output[] = array('status' => $json_status, 'error_code' => 400, 'image' => $file_tempdir, 'fileput' => $upload);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
else if ($passed_method == 'DELETE') {
	if (empty($passed_id)) {
		$json_status = 'post id parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if ($authorized_type == "admin") {
			$image_injection = "SELECT `upload_key` FROM `uploads` WHERE `upload_key` LIKE '$passed_id' LIMIT 0, 1";
					
		}
		else {
			$image_injection = "SELECT `upload_key` FROM `uploads` WHERE `upload_key` LIKE '$passed_id' AND `upload_owner` LIKE '$authorized_user' LIMIT 0, 1";
			
		}	
	
		$image_query = mysqli_query($database_connect, $image_injection);
		$image_exists = mysqli_num_rows($image_query);
		$image_data = mysqli_fetch_assoc($image_query);
		$image_key = $image_data['upload_key'];
		if ($image_exists == 1)	{
			$image_update = mysqli_query($database_connect, "UPDATE `uploads` SET `upload_removed` = '1' WHERE `upload_key` LIKE '$image_key';");
			if ($image_update) {
				header('HTTP/1.1 200 SUCSESSFUL');
						
				$json_status = 'image deleted';
				$json_output[] = array('status' => $json_status, 'error_code' => 200);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'image could not be deleted - ' . mysqli_error($image_update);
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
			
			}
			
		}
		else {
			$json_status = 'image does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => 409, 'sql' => $image_injection);
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

function gpscoordinates($coordinates, $reference) {
    $degrees = count($coordinates) > 0 ? gpstonumber($coordinates[0]):0;
    $minutes = count($coordinates) > 1 ? gpstonumber($coordinates[1]):0;
    $seconds = count($coordinates) > 2 ? gpstonumber($coordinates[2]):0;

    $flip = ($reference == 'W' or $reference == 'S') ? -1 : 1;

    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);

}

function gpstonumber($coordPart) {
    $parts = explode('/', $coordPart);

    if (count($parts) <= 0) return 0;

    if (count($parts) == 1) return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
	
}

?>