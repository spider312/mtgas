<?php
include '../lib.php' ;
include '../includes/db.php' ;

$player_id = param_or_die($_GET, 'player_id') ;
$data = new simple_object() ;

// Keep-alive self-created games
query("UPDATE `round` SET `last_update_date` = CURRENT_TIMESTAMP WHERE `status` = '1' AND `creator_id` = '$player_id' ;") ;

// List pending games
$query = "SELECT
		*,
		TIMESTAMPDIFF(SECOND, `creation_date`, NOW()) as age,
		TIMESTAMPDIFF(SECOND, `last_update_date`, NOW()) as inactivity
	FROM `round`
	WHERE `status` = '1'
	ORDER BY `id` ASC" ;
$data->games = query_as_array($query) ;

// Is a self-created joined game waiting for you
$query = query("SELECT id FROM `round` WHERE `status` = '2' AND `creator_id` = '$player_id' ORDER BY `id` ASC") ;
if ( $row = mysql_fetch_object($query) )
	$data->game_redirect[] = $row->id ;

// List runing games
$query = "SELECT
		*,
		TIMESTAMPDIFF(SECOND, `creation_date`, NOW()) as age,
		TIMESTAMPDIFF(SECOND, `last_update_date`, NOW()) as inactivity
	FROM `round`
	WHERE `joiner_id` != `creator_id` AND `status` = '3' AND `tournament` = '0' AND TIMESTAMPDIFF(SECOND, `last_update_date`, NOW()) < 600
	ORDER BY `id` ASC" ;
$data->runing_games = query_as_array($query) ;


die(json_encode($data)) ;
?>
