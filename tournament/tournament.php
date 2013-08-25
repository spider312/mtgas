<?php // Include file for managing tournament evolution in daemon
function rounds_number($players_nb) {
	for ( $rounds_nb = 1 ; $rounds_nb < 12 ; $rounds_nb++ ) {
		$min = pow( 2, ( $rounds_nb - 1 ) ) + 1 ;
		$max = pow( 2, $rounds_nb ) ;
		if ( ( $players_nb >= $min ) && ( $players_nb <= $max ) )
			return $rounds_nb ;
	}
	return 0 ;
}
function tournament_start($tournament) {
	$players = tournament_playing_players($tournament) ;
	sleep(2) ; // "Not saved deck" bug resolution
	$data = json_decode($tournament->data) ;
	$duration = $data->rounds_duration ;
	if ( ! is_numeric($data->rounds_number) )
		$data->rounds_number = rounds_number(count($players)) ;
	$data->rounds_number = max($data->rounds_number, rounds_number(count($players))) ; // Set at least the number of rounds implied by players
	// Update tournament
	$tournament->status = 5 ;
	$tournament->round = 1 ;
	query("UPDATE `tournament` SET
		`status` = '".$tournament->status."',
		`round` = '".$tournament->round."',
		`update_date` = NOW(),
		`due_time` = TIMESTAMPADD(MINUTE, $duration, NOW()),
		`data` = '".mysql_real_escape_string(json_encode($data))."'
	WHERE `id` = '".$tournament->id."' ; ") ;
	// Update registrations
	query("UPDATE `registration` SET `ready` = '0' WHERE `tournament_id` = '".$tournament->id."' ; ") ; // Unmark as "finished"
	tournament_log($tournament->id, '', 'start', '') ;
	// Start first round
	shuffle($players) ;
	round_start_games($tournament, $players) ;
	return $tournament ;
}
function round_start($tournament) { // Called on round end, first round is in tournament_start
	// Update results cache
	$data = json_decode($tournament->data) ;
	$duration = $data->rounds_duration ;
	$round = $tournament->round ;
	$data->results->$round = query_as_array("SELECT `id`, `creator_id`, `creator_score`, `joiner_id`, `joiner_score` FROM `round`
		WHERE `tournament` = '".$tournament->id."' AND `round` = '".$tournament->round."' ; ") ;
	// End games linked to this round
	query("UPDATE `round` SET `status` = '7' WHERE `tournament` = '".$tournament->id."' AND `round` = '".$tournament->round."' ; ") ;
	// Define players scores and tie breakers
	$players = tournament_all_players($tournament) ;
	foreach ( $players as $player ) { // First loop to update all players scores
		$player->score = player_score($data->results, $player) ;
		$player_id = $player->player_id ;
		$data->score->$player_id = $player->score ; // Update score cache
	}
	foreach ( $players as $player ) // Players scores are up to date, update opponent's scores (tie breakers)
		opponent_match_win($data->results, $player, $players, $data->score) ;
	// Rank players
	usort($players, 'players_end_compare') ;
	// Update rank cache
	foreach ( $players as $i => $player ) {
		$id = $player->player_id ;
		$data->score->$id->rank = $i+1 ;
	}
	$tournament->data = $data ;
	// Update incremented round
	$tournament->round++ ;
	if ( $tournament->round > $data->rounds_number ) { // All rounds played, end tourament
		$duration = 0 ;
		$tournament->status = 6 ;
		query("UPDATE `registration` SET `status` = '5' WHERE `tournament_id` = '".$tournament->id."' AND `status` < 7 ; ") ; // End registrations
		tournament_log($tournament->id, $players[0]->player_id, 'end') ;
	} else {
		// Change registrations status for redirection
		query("UPDATE `registration` SET `status` = '1', `ready` = '0' WHERE `tournament_id` = '".$tournament->id."' AND `status` < 7 ; ") ; 
		tournament_log($tournament->id, '', 'round', $tournament->round) ;
	}
	query("UPDATE `tournament` SET
		`status` = '".$tournament->status."',
		`round` = '".$tournament->round."',
		`update_date` = NOW(),
		`due_time` = TIMESTAMPADD(MINUTE, $duration, NOW()),
		`data` = '".mysql_real_escape_string(json_encode($tournament->data))."'
	WHERE `id` = '".$tournament->id."' ; ") ;
	if ( $tournament->status == 5 ) { // Start games for new round
		$i = 0 ; // Security in case no solution can be found in 100 random tries
		$nb = pow(count($players), 2) ;
		global $matchset ;
		$matchset = '' ;
		do {
			shuffle($players) ; // Randomize a bit
			usort($players, 'players_score_compare') ; // Sort players by score, players with the same score will encounter
			$matchset .= "Try $i : " ;
			foreach ( $players as $player )
				$matchset .= ' '.$player->nick.' ('.$player->score->matchpoints.') ' ;
			$matchset .= "\n" ;
		} while ( ( ++$i < $nb ) && ( ! round_create_matches($tournament, $players) )  ) ;
		$matchset .= "Validated matchset : " ;
		foreach ( $players as $player )
			$matchset .= ' '.$player->nick.' ('.$player->score->matchpoints.')' ;
		$matchset .= "\n" ;
		round_start_games($tournament, $players) ; // Create games
		if ( $i >= $nb )
			die($matchset.'Tournament '.$tournament->id.' : no matchset found after '.$i.' tries'."\n") ; // Debug sent by mail
	} else
		ranking_to_file('ranking/week.json', 'WEEK') ;
	return $tournament ;
}
function round_same_previous($tournament, $players) { // Verifies all future round's matches are all different from all previous matches
	global $matchset ;
	$my_players = array_merge($players) ; // Clone players, in order to pop them from clone
	while ( count($my_players) > 1 ) {
		$creator = array_shift($my_players) ;
		$joiner = array_shift($my_players) ;
		if ( game_same_previous($creator, $joiner, $tournament->data->results) )
			return true ;
	}
	return false ; // No same matches
}
function game_same_previous($creator, $joiner, $results) {
	global $matchset ;
	foreach ( $results as $round_nb => $round ) { // Each past rounds
		foreach ( $round as $table_nb => $match ) { // Each matches from round
			if (
				( ( $match->creator_id == $creator->player_id ) && ( $match->joiner_id == $joiner->player_id ) ) // Same match
				|| ( ( $match->creator_id == $joiner->player_id ) && ( $match->joiner_id == $creator->player_id ) ) // Reversed match
			) {
				$matchset .= $creator->nick." already encountered ".$joiner->nick." during round $round_nb (table $table_nb)\n" ;
				return true ; // One same match encountered, stop search
			}
		}
	}
	$matchset .= ' - '.$creator->nick." Vs ".$joiner->nick."\n" ;
}
function round_create_matches($tournament, &$players) { // Modifies players list in order to avoid 2 consecutives players to have already encountered each other during previous round. Returns if it succed
	global $matchset ;
	$my_players = array_merge($players) ; // Clone players, in order to pop them from clone
	$new_players = array() ; // Will replace $players
	while ( count($my_players) > 0 ) {
		$creator = array_shift($my_players) ;
		$joiner = null ;
		if ( count($my_players) == 0 ) { // 1 Player left : bye
			$new_players[] = $creator ;
			$players = $new_players ; // Override players list with new one in right order
			return true ;
		}
		foreach ( $my_players as $i => $player )
			if ( ! game_same_previous($creator, $player, $tournament->data->results) ) {
				$spl = array_splice($my_players, $i, 1) ;
				$joiner = $spl[0] ;
				$new_players[] = $creator ;
				$new_players[] = $joiner ;
				//$matchset .= ' - '.$creator->nick." Vs ".$joiner->nick."\n" ;
				break ; // Joiner found, stop browsing players left
			}// else
				//$matchset .= ' - '.$creator->nick." Already encountered ".$player->nick."\n" ;
		if ( $joiner == null ) {
			$matchset .= $creator->nick." can't find an opponent\n" ;
			return false ; // It fails to find 
		}
	}
	$players = $new_players ; // Override players list with new one in right order
	return true ;
}
function round_start_games($tournament, $players) {
	$table = 0 ;
	// TS3
	ts3_co() ;
	$cid = ts3_chan('Tournament '.$tournament->id, $tournament->name) ; // Get tournament channel
	$crid = 0 ; // By default, don't create round channel (in case there are no players)
	while ( count($players) > 1 ) {
		$creator = array_shift($players) ;
		$joiner = array_shift($players) ;
		$id = game_create($tournament->type.' '.addslashes($tournament->name).' : Round '.$tournament->round.' Table '.++$table
			, $creator->nick, $creator->player_id, $creator->avatar, addslashes($creator->deck)
			, $joiner->nick, $joiner->player_id, $joiner->avatar, addslashes($joiner->deck)
			, $tournament->id, $tournament->round) ;
		// TS3
		if ( $crid == 0 ) // Create round channel
			$crid = ts3_chan('Round '.$tournament->round, $tournament->name, $cid) ;
		$ctid = ts3_chan('Table '.$table, $tournament->name, $crid) ; // Create table subchannel
		ts3_invite(array($creator, $joiner), $ctid, true) ;
	}
	// Bye if needed
	if ( count($players) == 1 ) {
		$bye = array_shift($players) ;
		$id = game_create($tournament->type.' '.addslashes($tournament->name).' : Round '.$tournament->round.' Bye'
			, $bye->nick, $bye->player_id, $bye->avatar, addslashes($bye->deck)
			, 'BYE', '', '', '',
			$tournament->id, $tournament->round) ;
		query("UPDATE `round` SET `creator_score` = 2 WHERE `id` = '$id' ; ") ; // Game won
		query("UPDATE `registration` SET 
			`status` = '6', 
			`ready` = '1'
		WHERE
			`tournament_id` = '".$tournament->id."'
			AND `player_id` = '".$bye->player_id."' ") ; // Player BYing
		ts3_invite(array($bye), $cid) ;
	}
	ts3_disco() ;
}
// Scoring (Cf http://community.wizards.com/wiki/Tournament_Organizer's_Handbook:_Section_C )
function players_score_compare($player1, $player2) { // Sent to usort to compare scores from 2 players, for pairings (no tie breakers)
	return $player2->score->matchpoints - $player1->score->matchpoints ;
}
function players_end_compare($player1, $player2) { // Sent to usort to compare scores from 2 players, for ranking (with tie breakers)
	$result = 0 ;
	// Tie breakers
	if ( $player1->score->matchpoints != $player2->score->matchpoints )
		$result = $player2->score->matchpoints - $player1->score->matchpoints ;
	else {
		if ( 
			! property_exists($player1->score, 'opponentmatchwinpct')
			|| ! property_exists($player2->score, 'opponentmatchwinpct')
			|| ! property_exists($player1->score, 'opponentgamewinpct')
			|| ! property_exists($player2->score, 'opponentgamewinpct')
		)
			return 0 ; // Computed after each round, may crash if called before first round end
		if ( $player1->score->opponentmatchwinpct != $player2->score->opponentmatchwinpct )
			$result = $player2->score->opponentmatchwinpct - $player1->score->opponentmatchwinpct ;
		else
			$result = $player2->score->opponentgamewinpct - $player1->score->opponentgamewinpct ;
	}
	if ( $result != 0 )
		$result = $result / abs($result) ; // Return -1, 0 or 1
	return $result ;
}
function player_score($results, $player) { // Compute score for that player
	$score = object() ;
	$score->matchplayed = 0 ;
	$score->matchpoints = 0 ;
	$score->gameplayed = 0 ;
	$score->gamepoints = 0 ;
	foreach ( $results as $round ) {
		foreach ( $round as $match ) {
			if ( $player->player_id == $match->creator_id ){ // If player was 'creator'
				$player_score = $match->creator_score ;
				$opponent_score = $match->joiner_score ;
			} else if ($player->player_id == $match->joiner_id ) { // If player was 'joiner'
				$player_score = $match->joiner_score ;
				$opponent_score = $match->creator_score ;
			} else // Player didn't participate in this match
				continue ; // Go next match
			// Match wins
			$score->matchplayed++ ;
			if ( $player_score > $opponent_score ) // Player won
				$score->matchpoints += 3 ;
			else if ( $player_score == $opponent_score ) // Player tied
				$score->matchpoints += 1 ;
			// Game wins
			if ( $player_score + $opponent_score > 0 )
				$score->gameplayed += $player_score + $opponent_score ;
			else
				$score->gameplayed++ ; // At least 1 game per round
			$score->gamepoints += 3 * $player_score ; // No management for game draw
		}
	}
	// Percentages
		// Match win
	if ( $score->matchplayed == 0 )
		$score->matchwinpct = 0 ;
	else
		$score->matchwinpct = max(1/3, $score->matchpoints/(3*$score->matchplayed)) ;
		// Game win
	if ( $score->gameplayed == 0 )
		$score->gamewinpct = 0 ;
	else
		$score->gamewinpct = max(1/3, $score->gamepoints/(3*$score->gameplayed)) ;
	return $score ;
}
function opponent_match_win($results, $player, $players, $scores) {
	$player_id = $player->player_id ;
	$score = $scores->$player_id ;
	// Get a list of player's opponents
	$opponents = array() ;
	foreach ( $results as $round_nb => $round )
		foreach ( $round as $match )
			if ( $match->joiner_id != '' ) { // If match isn't player's bye
				if ( $player_id == $match->creator_id ) // If player was 'creator'
					$opponents[$round_nb] = $match->joiner_id ; // 'joiner' was its opponent
				else if ( $player_id == $match->joiner_id) // If player was 'joiner'
					$opponents[$round_nb] = $match->creator_id ; // 'creator' was its opponent
			}
	$matchwin = 0 ;
	$gamewin = 0 ;
	$nb = 0 ;
	foreach ( $players as $player )
		if ( in_array($player->player_id, $opponents) ) {
			$nb++ ;
			$matchwin += $player->score->matchwinpct ;
			$gamewin += $player->score->gamewinpct ;
		}
	if ( $nb != 0 ) {
		$score->opponentmatchwinpct = $matchwin / $nb ;
		$score->opponentgamewinpct = $gamewin / $nb ;
		// Copy data from scores to players cuz they're needed for players_end_compare
		$player->opponentmatchwinpct = $score->opponentmatchwinpct ;
		$player->opponentgamewinpct = $score->opponentgamewinpct ;
	}
	return $score ;
}
?>
