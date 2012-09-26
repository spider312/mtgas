<?php
// Update game and return it, for create.php check its status
if ( array_key_exists('id', $_GET) ) {
	include_once '../lib.php' ;
	include_once '../includes/db.php' ;
	$id = $_GET['id'] ;
	$row = mysql_fetch_object(query("SELECT * FROM `round` WHERE `id` = '$id'")) ;
	if ( ! $row )
		die('') ;
	else {
		$row->id = intval($row->id) ;
		$row->status = intval($row->status) ;
		query("UPDATE `round` SET `last_update_date` = CURRENT_TIMESTAMP WHERE `id` = '$id'") ;
		die(json_encode($row)) ;
	}
}
?>
