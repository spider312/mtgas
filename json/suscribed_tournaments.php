<?php
include '../lib.php' ;
include '../includes/db.php' ;

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
foreach ( $data->suscribed_tournaments as $i => $tournament ) {
	// Players
	$query = "SELECT `player_id`, `nick` FROM `registration`
		WHERE `registration`.`tournament_id` = {$tournament->id}" ;
	$tournament->players = query_as_array($query) ;
	// Results
	$query = "SELECT `creator_id`, `creator_score`, `joiner_id`, `joiner_score`, `round`
		FROM `round`
		WHERE `tournament` = {$tournament->id} ORDER BY `round` ASC" ;
	$matches = query_as_array($query) ;
	$results = array() ;
	foreach ( $matches as $match ) {
		if ( ! $results[$match->round] )
			$results[$match->round] = array() ;
		array_push($results[$match->round], $match) ;
	}
	$tournament->results = $results ;
}
die(json_encode($data)) ;
?>