<?php
if ( array_key_exists('url', $_GET) ) {
	// Download file
	$curl = curl_init($_GET['url']);
	curl_setopt($curl, CURLOPT_HEADER, true) ; // Get with headers
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true) ; // Don't display, will be parsed, computed, then returned
	$resp = curl_exec($curl) ;

	// Split headers from response
	list($headers, $response) = explode("\r\n\r\n", $resp, 2) ;

	// Return headers
	$headers = explode("\n", $headers);
	foreach($headers as $i => $header) {
		//if ( stripos($header, 'Transfer-Encoding') === false ) { // This header crashes import from mtgtop8
		if ( stripos($header, 'Content-Disposition') !== false ) { // This header is the only one read on client side
			header($header) ;
		}
	}

	// Return response
	die($response) ;
}
?>
