<?php
if ( array_key_exists('name', $_GET) ) {
	include '../lib.php' ;
	include '../includes/db.php' ;
	include '../includes/card.php' ;
	$connec = card_connect() ;
	$query = query("SELECT * FROM card WHERE `name`='".mysql_real_escape_string($_GET['name'])."'", 'Card search', $connec) ;
	if ( $card = mysql_fetch_object($query) )
		$id = $card->id ;
	else
		die('{}') ;
	$query = query("SELECT extension.id, extension.se, extension.name, card_ext.nbpics FROM card_ext, extension WHERE card_ext.card = '$id' AND card_ext.ext = extension.id ORDER BY extension.priority DESC", 'Card\' extension', $connec) ;
	$ext = array() ;
	while ( $obj = mysql_fetch_object($query) )
		$ext[] = $obj ;
	$card->ext = $ext ;
	die(json_encode($card)) ;
} else
	die('No ID in param') ;
?>
