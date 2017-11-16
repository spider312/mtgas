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
		// This header is the only one read on client side, send only that one after correcting it
		if ( preg_match('/Content-Disposition: attachment; filename="?(.*)"?/', $header, $matches) ) {
			$filename = $matches[1] ; // Extract filename
			$filename = str_replace("\r", '', $filename) ; // Remove unexpected newlines (Firefox doesn't like it)
			$header = 'Content-Disposition: attachment; filename="'.$filename.'"' ; // Rebuild header (Chrome doesn't like filename not enclosed in quotation marks)
			header($header) ;
		}
	}

	// Return response
	die($response) ;
}
?>
