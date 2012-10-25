<?php
include '../../lib.php' ;
include '../../includes/db.php' ;

$player_id = param_or_die($_GET, 'player_id') ;
$data = new simple_object() ;

// Pending tournaments
$data->tournaments = array() ;
$query = query("SELECT
		*,
		TIMESTAMPDIFF(SECOND, `creation_date`, NOW()) as age
	FROM `tournament`
	WHERE `status` = '1'
	ORDER BY `id` ASC") ;
while ( $row = mysql_fetch_object($query) ) {
	// List this tournament's registered players
	$row->players = array() ;
	$query_players = query("SELECT
			*,
			TIMESTAMPDIFF(SECOND, `date`, NOW()) as age,
			TIMESTAMPDIFF(SECOND, `update`, NOW()) as inactivity
		FROM `registration`
		WHERE `tournament_id`='".$row->id."'
	;") ;
	while ( $player = mysql_fetch_object($query_players) ) {
		if ( $player->player_id == $player_id ) // Update self registration
			query('UPDATE `registration`
			SET `update` = NOW()
			WHERE
				`tournament_id` = "'.$row->id.'"
				AND `player_id` = "'.$player->player_id.'"
			;') ;
		$row->players[] = $player ;
	}
	// Add tournament object to data returned
	$data->tournaments[] = $row ;
}

// Running tournaments
$data->tournaments_running = query_as_array("SELECT
		*,
		TIMESTAMPDIFF(SECOND, `creation_date`, NOW()) as age
	FROM `tournament`
	WHERE
		`status` > '1'
		AND `status` < '6'
		ORDER BY `id` ASC") ;
foreach ( $data->tournaments_running as $k => $t )
	$t->players = query_as_array("SELECT * FROM `registration` WHERE `tournament_id`='".$t->id."'") ;

// Is a tournament waiting for you ?
$query = query("SELECT id FROM `registration`, `tournament` WHERE
	`registration`.`player_id` = '$player_id' AND
	`registration`.`tournament_id` = `tournament`.`id` AND
	`tournament`.`status` = 2 ; ") ;
if ( $row = mysql_fetch_object($query) )
	$data->tournament_redirect = $row->id ;

die(json_encode($data)) ;
?>
