<?php
if ( array_key_exists('id', $_GET) ) {
	include_once 'lib.php' ;
	$id = param_or_die($_GET, 'id') ;
	$status = param_or_die($_GET, 'status') ;
	$due_time = param_or_die($_GET, 'due_time') ;
	$updates = array() ;
	if ( $status != '' )
		$updates[] = "`status` = '".mysql_real_escape_string($status)."'" ;
	if ( $due_time != '' )
		$updates[] = "`due_time` = '".mysql_real_escape_string($due_time)."'" ;
	$q = "UPDATE 
		`tournament`
	SET
		".implode(', ', $updates)."
	WHERE
		`id` = '$id'
	; " ;
	$query = query($q, 'Tournament update') ;
	die('{"nb": '.mysql_affected_rows().'}') ;
} else
	die('{"msg" : "No ID in param"}') ;
?>
