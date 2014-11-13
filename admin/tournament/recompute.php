<?php
include_once 'lib.php' ;
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../../tournament/tournament.php' ;
function tournament_all_players($tournament) {
	$players = array() ;
	$players_query = query("SELECT *
	FROM `registration`
	WHERE
		`tournament_id` = '".$tournament->id."'
	ORDER BY `order` ASC ; ") ;
	while ( $row = mysql_fetch_object($players_query) )
		$players[] = $row ;
	return $players ;
}
html_head(
	'Admin > Tournament > Recompute',
	array(
		'style.css'
		, 'admin.css'
	),
	array(
	) 
) ;
?>

 <body>
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Tournaments recompute</h1>
   <pre>
<?php
$tid = param($_GET, 'id', -1) ;
if ( $tid == -1 )
	$t = query_as_array("SELECT `id`, `data` FROM `tournament` ; ") ;
else
	$t = query_as_array("SELECT `id`, `data` FROM `tournament` WHERE `id` = '$tid' ; ") ;
foreach ( $t as $tournament ) {
	$players = tournament_all_players($tournament) ;
	if ( count($players) < 1 )
		continue ;
	echo $tournament->id.' : '.count($players).' players' ;
	$round = 1 ;
	$data = json_decode($tournament->data) ;
	unset($data->results) ; // Will be recomputed
	$data->results = NULL ;
	// Re-compute rounds data
	do {
		$rdata = query_as_array("SELECT `id`, `creator_id`, `creator_score`, `joiner_id`, `joiner_score` FROM `round`
			WHERE `tournament` = '".$tournament->id."' AND `round` = '$round' ; ") ;
		if ( count($rdata) > 0 )
			$data->results->$round = $rdata ;
		$round++ ;
	} while ( count($rdata) > 0 ) ;
	echo ', '.count($data->results).' rounds' ;
	if ( count($data->results) < 1 ) { // Can't compute scores without rounds
		echo ' : aborting<br>'."\n" ;
		continue ;
	}
	unset($data->score) ;
	// Define players scores and tie breakers
	foreach ( $players as $player ) { // First loop to update all players scores
		$player->score = player_score($data->results, $player) ;
		$player_id = $player->player_id ;
		$data->score->$player_id = $player->score ; // Update score cache
	}
	foreach ( $players as $player ) { // Players scores are up to date, update opponent's scores (tie breakers)
		opponent_match_win($data->results, $player, $players, $data->score) ;
	}
	// Rank players
	usort($players, 'players_end_compare') ;
	foreach ( $players as $i => $player ) { // Update rank cache
		$id = $player->player_id ;
		$data->score->$id->rank = $i + 1 ;
	}
	query("UPDATE `tournament` SET
		`data` = '".mysql_real_escape_string(json_encode($data))."'
	WHERE `id` = '".$tournament->id."' ; ") ;
	echo ' : recomputed<br>'."\n" ;
}
?>
  </pre>
  </div>
 </body>
</html>
