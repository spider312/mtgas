<?php
// Called by user-events in game to send actions to other client
include '../lib.php' ;
include '../includes/db.php' ;
include '../tournament/lib.php' ;
$source = $_POST ;
$game = param_or_die($source, 'game') ;
$type = param_or_die($source, 'type') ;
$local_index = param_or_die($source, 'local_index') ;
$param = param_or_die($source, 'param') ;
$data = object() ;

$match = query_oneshot("SELECT * FROM `round` WHERE `round`.`id` = '$game' ; ") ;
if ( 
	( $player_id != $match->creator_id ) && ( $player_id != $match->joiner_id ) // Sender is nor creator nor joiner, he may not send action
	&& ( $type != 'spectactor' ) && ( $type != 'text' ) // Unless action is spectactor or text
) {
	//$data->msg = 'As a spectactor, you are not allowed to send actions' ;
	die(json_encode($data)) ;
}

// Manage action, in case it implies more than a simple action (modifying round for example)
switch ( $type ) {
	case 'psync' : // Synchronize player's attributes, used to update score
		// Decode action params
		$action_param = json_decode(stripslashes($param)) ;
		// Get concerned player
		if ( $action_param->player == 'game.creator' ) {
			$field = 'creator_score' ;
			$field_id = 'creator_id' ;
		} else if ( $action_param->player == 'game.joiner' ) {
			$field = 'joiner_score' ;
			$field_id = 'joiner_id' ;
		} else // Concerned player isn't known as creator nor joiner, bug
			break ;
		// Get new score in params
		if ( isset($action_param->attrs->score) )
			$score = $action_param->attrs->score ;
		else
			break ;
		// Update score in round
		$id = intval($match->tournament) ;
		if ( $id == 0 ) // Not in a tournament, always update
			query("UPDATE `round` set `$field` = '$score' WHERE `round`.`id` = '$game' ; ") ;
		else {
			if ( ( $score <= 2 ) && ( intval($match->creator_score) + intval($match->joiner_score) < 3 ) ) { // New score <= 2 and round not already 2-1+
				// Will allow a 2-0 round to update at 2-1 for tournament
				query("UPDATE `round` set `$field` = '$score' WHERE `round`.`id` = '$game' ; ") ;
				$tournament = query_oneshot("SELECT * FROM `tournament` WHERE `id` = '$id'") ;
				if ( $tournament != null ) {
					if ( $score == 2 ) { // First player to set his score to 2 : mark both players as "finished playing"
						tournament_log($id, $match->$field_id, 'win', '') ;
						query("UPDATE `registration` SET `ready` = '1' WHERE `tournament_id` = '$id' AND `player_id` = '".$match->creator_id."' ;") ;
						query("UPDATE `registration` SET `ready` = '1' WHERE `tournament_id` = '$id' AND `player_id` = '".$match->joiner_id."' ;") ;
						$players_playing = query_as_array("SELECT * FROM `registration` WHERE `tournament_id` = '$id' AND `ready` != '1'") ;
						$nbplaying = count($players_playing) ;
						if ( $nbplaying > 0 )
							$data->msg = $nbplaying.' players are still playing, you may continue playing, or go on tournament page to see matches in progress' ;
						else
							$data->newround = true ;
					} //else
						//tournament_log($id, $tournament->$field_id, 'win_game', '') ;
				}
			}
		}
		break ;
	default :
		// Nothing
}
// Store in DB
$result = query("INSERT INTO `action` (`game`, `sender`, `local_index`, `type`, `param`, `recieved`) VALUES ('$game', '$player_id', '$local_index', '$type', '".mysql_real_escape_string($param)."', '0');") ;
$data->id = mysql_insert_id() ;
$data->type = $type ;
$data->param = json_decode($param) ;

die(json_encode($data)) ;
?>
