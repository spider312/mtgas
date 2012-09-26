<?php
include '../lib.php' ;
include '../includes/db.php' ;

$player_id = param_or_die($_GET, 'player_id') ;
$data = new simple_object() ;

// List past games
$delay = param_or_die($_GET, 'games_delay') ;
$query ="SELECT
		*,
		TIMESTAMPDIFF(SECOND, `creation_date`, NOW()) as age
	FROM `round`
	WHERE
		( `creator_id` = '$player_id'
		OR `joiner_id` = '$player_id' ) 
		AND `creator_id` != `joiner_id`
		AND `status` != '0'
		AND `status` != '1' 
		AND `tournament` = '0' " ;
if ( $delay != '' )
	$query .= " AND `creation_date` > TIMESTAMPADD($delay, -1, NOW()) " ;
$query .="ORDER BY `id` ASC" ;
$data->suscribed_games = query_as_array($query) ;

die(json_encode($data)) ;
?>
