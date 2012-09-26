<?php
// Cancel a game
if ( array_key_exists('id', $_GET) ) {
	include('../lib.php') ;
	$id = $_GET['id'] ;
	$row = mysql_fetch_object(query("SELECT * FROM `round` WHERE `id` = '$id'")) ;
	if ( ! $row )
		die('') ;
	else {
		$row->id = intval($row->id) ;
		$row->status = intval($row->status) ;
		query("UPDATE `round` SET `status` = 0 WHERE `id` = '$id'") ;
		die(json_encode($row)) ;
	}
}
?>
