<?php
include '../../lib.php' ;
include '../../includes/db.php' ;

$player_id = param_or_die($_GET, 'player_id') ;
$data = new stdClass() ;

// List registered tournaments
$delay = param_or_die($_GET, 'tournaments_delay') ;
$query = "
SELECT
	`tournament`.`id`, 
	`tournament`.`data`,
	`tournament`.`creation_date`,
	`tournament`.`type`,
	`tournament`.`name`,
	`tournament`.`min_players`,
	`tournament`.`status`
FROM
	`registration`, `tournament`
WHERE
	`registration`.`player_id` = '$player_id' AND
	`registration`.`tournament_id` = `tournament`.`id`" ;
if ( $delay != '' )
	$query .= " AND `date` > TIMESTAMPADD($delay, -1, NOW())" ;
$query .= " ;" ;
$data->suscribed_tournaments = query_as_array($query) ;

die(json_encode($data)) ;
?>
