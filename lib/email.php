<?php

function email_mailgun_connect($url, $data, $method) {
	$ch_url = "https://api.mailgun.net/v3/" . $url;
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_URL, $ch_url);
	curl_setopt($ch, CURLOPT_USERPWD, "api:key-e1eefc8469695fbd042ce153b5a00369");
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$result = curl_exec($ch);
	if(curl_errno($ch)) $result = 'Curl error: ' . curl_error($ch);
	curl_close($ch);
		
	return json_decode($result);
	
}

function email_user($email_subject, $email_body, $email_address, $email_sender) { 
	if ($_SERVER['HTTP_HOST'] == "localhost:8888") $database_connect = mysqli_connect('localhost', 'root', 'root'); //localhost
	else $database_connect = mysqli_connect('localhost', 'root', 'Blrrd2017**'); //production
	$database_table = mysqli_select_db($database_connect, "blrrd");	
	
	if (empty($email_sender)) $email_injection = "SELECT * FROM `users` WHERE `user_name` LIKE 'thebarbican' LIMIT 0, 1";
	else $email_injection = "SELECT * FROM `users` WHERE `user_name` LIKE '$email_sender' OR `user_key` LIKE '$email_sender' LIMIT 0, 1";
		
	$email_query = mysqli_query($database_connect, $email_injection);	
	$email_user = mysqli_fetch_assoc($email_query);	
	$email_username = $email_user['user_fullname'];
	$email_userfirstname = reset(explode(" ", $email_username));
	$email_sender = $email_userfirstname . ' from Blrrd <info@blrrd.co>';
	$email_userhandle = $email_user['user_name'];
	$email_avatar = $email_user['user_avatar'];
	if (empty($email_avatar)) $email_avatar = "http://52.59.224.79/website/assets/avatarplaceholder.png";
	
	if (!empty($email_address) || filter_var($email_address, FILTER_VALIDATE_EMAIL) !== false) {
		$email_formatted .= "<link rel='stylesheet' href='http://52.59.224.79/website/style/style.css' type='text/css' charset='utf-8'/>";
		$email_formatted .= "<html>";
		$email_formatted .= "<body>";
			$email_formatted .= "<div class='header' align='center'>";
				$email_formatted .= "<a href='' target='_blank' alt='blrrd.co'><img src='http://52.59.224.79/website/assets/blrrdlogo.png' class='logo'></a>";
			$email_formatted .= "</div>";
			$email_formatted .= "<div class='container'>";
			$email_formatted .= $email_body;
			$email_formatted .= "<p><p>";
			$email_formatted .= "King regards,<br>";
			$email_formatted .= "<div style='margin-left:8px; float:left;'>";	
				$email_formatted .= "<div style='width:46px; float:left;'>";	
					$email_formatted .= "<img src='" . $email_avatar . "' style='width:36px; border-radius:20px;'>";
				$email_formatted .= "</div>";			
				$email_formatted .= "<div style='width:235px; font-size:13px; float:left;'>";	
					$email_formatted .= "<strong style='letter-spacing:-0.5;'>" . $email_username . "</strong>";
					$email_formatted .= "<br/><a href='blrrd://user?handle=" . $email_userhandle . "' style='color:rgba(255, 255, 255, 0.7); font-size:9px; text-decoration: none;'>@" . $email_userhandle . "</a><p>";
				$email_formatted .= "</div>";
			$email_formatted .= "</div>";	
			$email_formatted .= "<p><p><div class='footer' align='center'>";
			$email_formatted .= "This is an automated message sent via Blrrd. For more information about Blrrd please check out our <a href='http://blrrd.co/' target='blank'>website</a>";
			$email_formatted .= "</div>";
		$email_formatted .= "</body>";	
		$email_formatted .= "</html>";
		
		$email_data = array();
		$email_data['from'] = $email_sender;
		$email_data['to'] = $email_address;;
		$email_data['h:Reply-To'] = 'info@blrrd.co';
		$email_data['subject'] = $email_subject;
		$email_data['html'] = $email_formatted;
		
		$email_output = email_mailgun_connect("mg.blrrd.co/messages", $email_data, "POST");
		
		if ($email_output->message == "Queued. Thank you.") $json_output = array("status" => $email_output->message, 'error_code' => 200);
		else $json_output = array("status" => $email_output->message, 'error_code' => 400, 'data' => $email_data);
		
		return $json_output;
		
	}
	else return array("status" => "Recipient was invalid", 'error_code' => 403);
	
}

function email_new_mailinglist($mailing_list, $channel_name) {
	$email_data = array();
	$email_data['address'] = $channel_name . "<" . $channel_key . "@mg.blrrd.co>";
	$email_data['name'] = $channel_name;
	$email_data['description'] = "Local Mailing list for " . $channel_name . " (" . $channel_key . ")";
	
 	return email_mailgun_connect("lists", $email_data, "POST");
	
}


function email_delete_mailinglist($mailing_list) {
 	return email_mailgun_connect("lists/" . $channel_name . "@mg.gradoapp.com", array(), "DELETE");
	
}

function email_subscribe_mailinglist($mailing_list, $subscriber_email, $subscriber_username) {
	$email_data = array();
	$email_data['address'] = $subscriber_email;
	$email_data['name'] = $subscriber_username;
	$email_data['subscribed'] = "yes";
	
  	return email_mailgun_connect("lists/" . $mailing_list . "@mg.blrrd.co/members", $email_data, "POST");
	
}

function email_unsubscribe_mailinglist($mailing_list, $subscriber_email) {
	$email_data = array();
	$email_data['subscribed'] = "no";
	
  	return email_mailgun_connect("lists/" . $mailing_list . "@mg.blrrd.co/members/" . $subscriber_email, $email_data, "PUT");
	
}

?>