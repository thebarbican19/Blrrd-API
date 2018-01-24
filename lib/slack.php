<?

function post_slack($message, $channel, $attachement) { 
	public $authorized_avatar;
	
	$payload = "payload=" . json_encode(array("channel" =>  "#{#C88CT9DC7}", "attachments" => $attachement, "response_type" => "in_channel", "text" => $message, "fallback" => $message, "icon_url" => $authorized_avatar));
		
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://hooks.slack.com/services/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");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	
	curl_close($ch);
	
}

?>
