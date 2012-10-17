<?php
if ( array_key_exists('ext', $_GET) && array_key_exists('card', $_GET) ) {
	include '../../../lib.php' ;
	include '../../../includes/db.php' ;
	include '../../../includes/card.php' ;
	$connec = card_connect() ;
	$ext = param_or_die($_GET, 'ext') ;
	$card = param_or_die($_GET, 'card') ;
	$nbpics = param($_GET, 'nbpics', 1) ;
	$rarity = param($_GET, 'rarity', 'C') ;
	query("UPDATE `card_ext` SET `nbpics` = '$nbpics', `rarity` = '$rarity' WHERE `card`=$card AND `ext`=$ext ; ", 'Card link updating', $connec) ;
	die('{"nb": "'.mysql_affected_rows().'"}') ;
} else
	die("{'msg' : 'No ID in param'}") ;
?>
