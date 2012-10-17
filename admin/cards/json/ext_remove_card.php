<?php
if ( array_key_exists('ext', $_GET) && array_key_exists('card', $_GET) ) {
	include '../../../lib.php' ;
	include '../../../includes/db.php' ;
	include '../../../includes/card.php' ;
	$connec = card_connect() ;
	$ext = param_or_die($_GET, 'ext') ;
	$card = param_or_die($_GET, 'card') ;
	query("DELETE FROM `card_ext` WHERE `ext` = '$ext' AND `card` = '$card'", 'Card unlinking', $connec) ;
	die('{"nb": "'.mysql_affected_rows().'"}') ;
} else
	die("{'msg' : 'No ID in param'}") ;
?>
