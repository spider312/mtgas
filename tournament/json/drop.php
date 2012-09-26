<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
include '../lib.php' ;
$data = new simple_object() ;
$id = intval(param_or_die($_GET, 'id')) ;
$reg = registration_get($id) ;
if ( $reg == null )
	$data->msg = 'Unable to find a registration' ;
else {
	$q = query("UPDATE `registration` SET 
		`status` = '7' 
	WHERE
		`tournament_id` = '".$id."'
		AND `player_id` = '".$reg->player_id."' ") ;
	if ( mysql_affected_rows() == 1 )
		$data->msg = 'Successfully dropped' ;
	else
		$data->msg = 'Error during drop : '.mysql_num_rows().' rows affected' ;
}
die(json_encode($data)) ;
?>
