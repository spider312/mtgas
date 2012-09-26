<?php
// Returns an object representation of the game passed in param, containing an array representation of actions (only new or all, depending on 'recieved' param
include('../lib.php') ;
include('../includes/db.php') ;
$game = param_or_die($_GET, 'game') ;
//$recieved = intval(param($_GET, 'recieved', -1)) ; // Done here for 'or die'
$from = intval(param($_GET, 'from', -1)) ;

// Get game info (to display timeleft, or in case game status has changed
$return = query_oneshot("
	SELECT
		`tournament`,
		`status`,
		`last_update_date`,
		TIMESTAMPDIFF(SECOND, `creation_date`, NOW()) as age,
		`creator_id`, 
		TIMESTAMPDIFF(SECOND, `creator_lastacti`, NOW()) as creator_lag,
		`joiner_id`, 
		TIMESTAMPDIFF(SECOND, `joiner_lastacti`, NOW()) as joiner_lag
	FROM `round`
	WHERE `id` = '$game' ; ") ;
if ( ! $return )
	die("{'msg': 'Unable to get game #$game'}") ;
// Get opponnent's lag
if ( $player_id == $return->creator_id ) {
	$myacti = 'creator_lastacti' ;
	$return->opponent_lag = $return->joiner_lag ;
} elseif( $player_id == $return->joiner_id ) {
	$myacti = 'joiner_lastacti' ;
	$return->opponent_lag = $return->creator_lag ;
} else { // Spectactors get sum of players lag
	$myacti = '' ;
	$return->opponent_lag = $return->joiner_lag + $return->creator_lag ;
}

// Get tournament info if needed
if ( $return->tournament != '0' ) {
	if ( $tournament = query_oneshot("SELECT TIMESTAMPDIFF(SECOND, NOW(), `due_time`) as timeleft FROM `tournament` WHERE `id` = '".$return->tournament."' ; ") )
		$return->timeleft = $tournament->timeleft ;
	else
		die("{'msg': 'Unable to get tournament #".$return->tournament."'}") ;
}
// Get required actions
$where = '' ;
if ( $from > 0 ) { // Update : get only new actions from opponent
	$where = "AND `sender` != '".$player_id."' AND `id` > '$from'" ;
} // Else : Initial : get every action
$return->actions = query_as_array("SELECT * FROM `action` WHERE `game` = '$game' $where ORDER BY `id` ASC ; ") ;
foreach ( $return->actions as $key => $action )
	foreach ( $action as $k => $a )
		$return->actions[$key]->$k = str_replace("\'", "'", $a) ; // JSON doesn't want single quotes to be escaped

// Update round
if ( $myacti != '' )
	query("UPDATE `round` SET `$myacti` = NOW() WHERE `id` = '$game' ; ") ;

die(json_encode($return)) ;
?>
