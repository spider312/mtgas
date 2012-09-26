<?php
if ( array_key_exists('card_id', $_GET) ) {
	include '../../../lib.php' ;
	include '../../../includes/db.php' ;
	include '../../../includes/card.php' ;
	$connec = card_connect() ;
	$card_id = param_or_die($_GET, 'card_id') ;
	$fixed_attrs = param_or_die($_GET, 'fixed_attrs') ;
	$card = array() ;
	$card['name'] = param_or_die($_GET, 'card_name') ;
	$card['cost'] = param_or_die($_GET, 'cost') ;
	$card['types'] = param_or_die($_GET, 'types') ;
	$card['text'] = param_or_die($_GET, 'text') ;
	$card['text'] = stripslashes($card['text']) ;
	$attrs = new attrs($card) ;
	$attrs = JSON_encode($attrs) ;
	//die(mysql_real_escape_string($card['text'])) ;
	$query = query("UPDATE 
		`card`
	SET
		`cost` = '".$card['cost']."', 
		`name` = '".mysql_real_escape_string($card['name'])."', 
		`types` = '".$card['types']."', 
		`text` = '".mysql_real_escape_string($card['text'])."', 
		`attrs` = '".mysql_real_escape_string($attrs)."',
		`fixed_attrs` = '".mysql_real_escape_string($fixed_attrs)."' 
	WHERE
		`id` = $card_id
	; ", 'Card update', $connec) ;
	die('{"nb": "'.mysql_affected_rows().'"}') ;
} else
	die("{'msg' : 'No ID in param'}") ;
?>
