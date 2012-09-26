<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
include '../lib.php' ;
$id = intval(param_or_die($_GET, 'id')) ;
$last_id = intval(param($_GET, 'last_id', 0)) ; // Last log id
function player_rank_compare($p1, $p2) {
	return $p1->rank - $p2->rank ;
}
if ( $tournament = mysql_fetch_object(query("SELECT *,
		NOW() as now,
		TIMESTAMPDIFF(SECOND, `update_date`, NOW()) as age,
		TIMESTAMPDIFF(SECOND, NOW(), `due_time`) as timeleft
	FROM `tournament` WHERE `id` = '$id' ;")) ) {
	// Tournament's players
	$tournament->players = query_as_array("SELECT `player_id`, `nick`, `avatar`, `status`, `order`, `ready`, `deck`
		FROM `registration` WHERE `tournament_id` = '$id' ORDER BY `order` ; ") ;
	// Sort players by rank
	$data = json_decode($tournament->data) ;
	if ( isset($data->score) ) {
		foreach ( $tournament->players as $i => $player ) {
			$pid = $player->player_id ;
			$player->rank = $data->score->$pid->rank ;
		}
		usort($tournament->players, 'player_rank_compare') ;
	}
	// Current round
	if ( $tournament->status == 5 ) {
		$r = intval($tournament->round) ;
		$tournament->current_round = query_as_array("SELECT `id`, `creator_nick`, `creator_id`, `creator_score`, `joiner_nick`, `joiner_id`, `joiner_score`
			FROM `round` WHERE `tournament` = '$id' AND `round` = '$r' ; ") ;
	}
} else {
	$tournament = new simple_object() ;
	$tournament->players = array() ;
	die(json_encode($tournament)) ;
}
$key = 'cache_tournament_'.$id ;
if ( array_key_exists($key, $_SESSION) && ( param_or_die($_GET, 'firsttime') != 'true' ) ) // Cache exists & also exists on client
	$res = obj_diff($tournament, $_SESSION[$key]) ; // Returns only difference between cache and current object
else
	$res = $tournament ; // Returns current object
$_SESSION[$key] = $tournament ; // Update cache
// Log
$q = "SELECT * FROM `tournament_log` WHERE `tournament_id` = '$id'" ;
if ( $last_id != 0 )
	$q .= " AND `id` > '$last_id'" ;
$res->log = query_as_array($q) ;
die(json_encode($res)) ;
?>
