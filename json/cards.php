<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../includes/card.php' ;
$results = card_search($_GET, card_connect()) ;
$json = json_encode($results) ;
if ( $json === false ) {
	foreach ($results->cards as $card) {
		$json = json_encode($card) ;
		if ( $json === false ) {
			echo json_last_error_msg() ;
			print_r($card) ;
		}
	}
}
header('Content-Type: application/json');
die($json) ;
?>
