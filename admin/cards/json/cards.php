<?php
if ( array_key_exists('ext', $_GET) ) {
	include '../../../lib.php' ;
	include '../../../includes/db.php' ;
	include '../../../includes/card.php' ;
	$connec = card_connect() ;
	$ext = param_or_die($_GET, 'ext') ;
	die(json_encode(query_as_array("SELECT * FROM card_ext, card  WHERE `card_ext`.`ext` = '$ext' AND `card`.`id` = `card_ext`.`card` ORDER BY `card`.`name`", 'Card list', $connec))) ;
} else
	die("{'msg' : 'No ext in param'}") ;
?>
