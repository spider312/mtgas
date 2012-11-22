<?php
if ( array_key_exists('ext_id', $_GET) ) {
	include '../../../lib.php' ;
	include '../../../includes/db.php' ;
	include '../../../includes/card.php' ;
	$connec = card_connect() ;
	$ext_id = param_or_die($_GET, 'ext_id') ;
	$priority = param_or_die($_GET, 'priority') ;
	$release_date = param_or_die($_GET, 'release_date') ;
	$bloc = param_or_die($_GET, 'bloc') ;
	$updates = array() ;
	if ( intval($priority).'' == $priority )
		$updates[] = "`priority` = '$priority'" ;
	if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $release_date, $matches) )
		$updates[] = "`release_date` = '$release_date'" ;
	if ( intval($priority).'' == $priority )
		$updates[] = "`bloc` = '$bloc'" ;
	$q = "UPDATE 
		`extension`
	SET
		".implode(', ', $updates)."
	WHERE
		`id` = '$ext_id'
	; " ;
	$query = query($q, 'Card update', $connec) ;
	die('{"nb": "'.mysql_affected_rows().'"}') ;
} else
	die("{'msg' : 'No ID in param'}") ;
?>
