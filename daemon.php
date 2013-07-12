<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'tournament/lib.php' ;
include $dir.'/includes/deck.php' ;
include $dir.'/includes/card.php' ;
include 'includes/ranking.php' ;
include 'includes/ts3.php' ;

/*
include 'includes/varspool-php-websocket/server/lib/SplClassLoader.php' ;

$classLoader = new SplClassLoader('WebSocket', 'includes/varspool-php-websocket/server/lib');
$classLoader->register();

// Websocket server
$server = new \WebSocket\Server(0, 1337);
// Origin checking is supported
$server->setCheckOrigin(true) ;
$server->setAllowedOrigin('dev.mogg.fr') ;
// As is basic rate limiting
$server->setMaxClients(100) ;
$server->setMaxConnectionsPerIp(3) ;
$server->setMaxRequestsPerMinute(1000) ;
$server->registerApplication('server', \WebSocket\Application\Mogg::getInstance()) ;
$server->run() ;
*/

//$seed = hexdec(substr( md5( microtime() ), -8 ) ) & 0x7fffffff ; // Hexmaster
$seed = (double)microtime()*1000000  ; // PHP comments
mt_srand($seed) ;

$card_connection = card_connect() ;

$day = 0 ;

while ( sleep($daemon_delay) !== FALSE ) {
	if ( $day != date('j') ) { // Day change
		ranking_to_file('ranking/week.json', 'WEEK') ;
		ranking_to_file('ranking/month.json', 'MONTH') ;
		ranking_to_file('ranking/year.json', 'YEAR') ;
		$day = date('j') ;
	}
// === [ SINGLE GAMES ] ===
	// Creator hasn't been on index for $timeout seconds, cancel
	query("UPDATE `round` SET `status` = '0' WHERE `status` = '1' AND `last_update_date` < NOW() - $timeout ; ") ;
	if ( $log && ( mysql_affected_rows() > 0 ) )
		echo mysql_affected_rows().' pending games canceled for timeout ('.$timeout."s)\n" ;

	// No joiner after 10 minutes
	query("UPDATE `round` SET `status` = '0' WHERE `status` = '1' AND TIMESTAMPDIFF(MINUTE, `creation_date`, NOW()) > 10 ; ") ;
	if ( $log && ( mysql_affected_rows() > 0 ) )
		echo mysql_affected_rows().' pending games canceled for timeout ('.$timeout."s)\n" ;

// === [ TOURNAMENTS ] ===
	// Remove not-pending-anymore tournaments
	query("UPDATE `tournament` SET `status` = '0' WHERE `status` = '1' AND TIMESTAMPDIFF(SECOND, `creation_date`, NOW()) > $tournament_timeout ; ") ;
	if ( $log && ( mysql_affected_rows() > 0 ) )
		echo mysql_affected_rows().' pending tournaments canceled for timeout ('.$tournament_timeout." sec)\n" ;

	// Unregister timeouted players
	$query = query("SELECT `id`, (NOW() - `creation_date`) as age FROM `tournament` WHERE `status` = '1' ORDER BY `id` ASC") ;
	while ( $row = mysql_fetch_object($query) ) {
		query("DELETE FROM `registration` WHERE `tournament_id` = '".$row->id."' AND TIMESTAMPDIFF(SECOND, `update`, NOW()) > $timeout ; ") ;
		if ( $log && ( mysql_affected_rows() > 0 ) )
			echo mysql_affected_rows().' registrations canceled for timeout ('.$timeout." sec)\n" ;
	}

	// Remove unplayered tournaments
	$query = query("
	SELECT
		`tournament`.`id`
	FROM
		`tournament`
	WHERE
		`tournament`.`status` = '1'
	;") ;
	while ( $tournament = mysql_fetch_object($query) ) {
		$registrated = query("SELECT * FROM `registration` WHERE `tournament_id` = '".$tournament->id."' ; ") ;
		if ( mysql_num_rows($registrated) == 0 )
			query("UPDATE `tournament` SET `status` = '0' WHERE `tournament`.`id` = '".$tournament->id."' ; ") ;
	}

	// Start redirecting tournaments with enough players
	$query = query("SELECT * FROM `tournament` WHERE `status` = '1' ; ") ; // Pending tournaments
	while ( $tournament = mysql_fetch_object($query) ) {
		// All players
		$players_query = query("SELECT `player_id`, `nick` FROM `registration` WHERE `registration`.`tournament_id` = '".$tournament->id."' ; ") ;
		if ( mysql_num_rows($players_query) >= $tournament->min_players ) { // Enough players
			// Give random numbers to players
			$players = array() ;
			while ( $player = mysql_fetch_object($players_query) )
				$players[] = $player ;
			shuffle($players) ;
			foreach ( $players as $i => $player ) {
				$player->order = $i ;
				query("UPDATE `registration`
					SET `order` = '$i'
					WHERE `tournament_id` = '".$tournament->id."'
						AND `player_id` = '".$player->player_id."'") ;
			}
			$data = json_decode($tournament->data) ;
			$data->players = $players ;
			query("UPDATE `tournament`
			SET
				`status` = 2,
				`update_date` = NOW(),
				`due_time` = NOW(), 
				`data` = '".mysql_real_escape_string(json_encode($data))."'
			WHERE
				`id` = '".$tournament->id."' ; ") ;
			tournament_log($tournament->id, '', 'players', '') ;
		}
	}

	// Start tournament with all players redirected
	$query = query("SELECT * FROM `tournament` WHERE `status` = '2' ; ") ; // Starting tournaments
	while ( $tournament = mysql_fetch_object($query) ) {
		$players_query = query("SELECT `status` FROM `registration` WHERE `registration`.`tournament_id` = '".$tournament->id."' ; ") ; // All players
		$pass = true ;
		while ( $pass && ( $player = mysql_fetch_object($players_query) ) )
			if ( $player->status != 1 )
				continue 2 ;
		$data = json_decode($tournament->data) ;
		$players = $data->players ;
		// TS3
		ts3_co() ;
		$cid = ts3_chan('Tournament '.$tournament->id, $tournament->name) ; // Create chan
		ts3_invite($players, $cid) ; // Move each tournament's player to chan
		ts3_disco() ;
		// Unicity initialization
		$cards = null ; // No unicity by default
		if ( ( property_exists($data, 'boosters') ) && count($data->boosters) > 0 ) {
			$uniqs = array('OMC', 'CUB', 'CUBL', 'CUBS') ; // Extensions that will trigger unicity (move inside booster creation func ?)
			foreach ( $uniqs as $uniq )
				if ( in_array($uniq, $data->boosters) )
					$cards = array() ;
		}
		// Start first stage for tournament depending on type
		switch ( $tournament->type ) {
			case 'draft' :
				// Delete previous booster in case it's needed
				query("DELETE FROM `booster` WHERE `tournament` = '".$tournament->id."' ;") ;
				$nb = mysql_matched_rows() ;
				if ( $nb != 1 )
					echo "$nb boosters cleaned during draft #".$tournament->id."'s initialisation \n" ;
				$tournament->round = 1 ;
				$number = 0 ;
				foreach ( $data->boosters as $booster ) {
					$number++ ;
					foreach ( $players as $player ) {
						$content = booster_as_array_with_ext($booster, $cards) ;
						$object = new simple_object() ;
						$object->ext = $content->ext ;
						$object->cards = array() ;
						foreach ( $content->cards as $card )
							$object->cards[] = $card->name ;
						query("INSERT INTO `booster` (`content`, `tournament`, `player`, `number`) VALUES
							('".mysql_real_escape_string(json_encode($object))."',
							'".$tournament->id."',
							'".$player->order."', 
							'".$number."')
						;") ;
					}
				}
				query("UPDATE `tournament` SET
					`status` = '3',
					`round` = '".$tournament->round."',
					`update_date` = NOW(),
					`due_time` = TIMESTAMPADD(SECOND, ".draft_time().", NOW()),
					`data` = '".mysql_real_escape_string(json_encode($data))."'
				WHERE `id` = '".$tournament->id."' ; ") ;
				tournament_log($tournament->id, '', 'draft', '') ;
				break ;
			case 'sealed' :
				if ( $data->clone_sealed ) { // Each player has the same deck
					query("UPDATE `registration`
						SET
							`deck` = '".pool_open($data->boosters, mysql_real_escape_string($tournament->name), $cards)."' 
						WHERE
							`tournament_id` = '".$tournament->id."'
						;") ;
				} else {
					foreach ( $players as $player ) {
						query("UPDATE `registration`
							SET
							
							`deck` = '".pool_open($data->boosters, mysql_real_escape_string($tournament->name), $cards)."' 
							WHERE
								`tournament_id` = '".$tournament->id."'
								AND `player_id` = '".$player->player_id."'
							;") ;
					}
				}
				query("UPDATE `tournament` SET
					`status` = '4',
					`round` = '0',
					`update_date` = NOW(),
					`due_time` = TIMESTAMPADD(SECOND, $build_duration, NOW()),
					`data` = '".mysql_real_escape_string(json_encode($data))."'
				WHERE `id` = '".$tournament->id."' ; ") ;
				tournament_log($tournament->id, '', 'build', '') ;
				break ;
			default : // All other cases : constructed
				tournament_start($tournament) ;
		}
	}

	// Do all drafting user checked "ready" ?
	$query = query("SELECT * FROM `tournament` WHERE `status` = '3' AND `due_time` > NOW() ; ") ;
	while ( $tournament = mysql_fetch_object($query) ) {
		$players = tournament_playing_players($tournament) ;
		foreach ( $players as $player )
			if ( $player->ready != 1 ) // If a player isn't ready
				continue 2 ; // Skip tournament (some players aren't ready)
		// Here all players are ready
		query("UPDATE `tournament` SET `due_time` = TIMESTAMPADD(SECOND, -1, NOW()) WHERE `id` = '".$tournament->id."' ;") ; // Change tournament's due time, to get it managed by next step
	}

	// Rotate boosters in drafts
	$query = query("SELECT * FROM `tournament` WHERE `status` = '3' AND `due_time` < NOW() ; ") ;
	while ( $tournament = mysql_fetch_object($query) ) {
		$players_query = query("SELECT * FROM `registration`, `booster`
		WHERE
			`registration`.`tournament_id` = '".$tournament->id."' AND
			`booster`.`tournament` = '".$tournament->id."' AND
			`booster`.`player` = `registration`.`order` AND
			`booster`.`number` = '".$tournament->round."'
		ORDER BY `registration`.`order`; ") ; // All players from the events and their current boosters
		$content = new Simple_object() ;
		while ( $player = mysql_fetch_object($players_query) ) {
			// Defining pick
			$content = json_decode($player->content) ; // Decode JSON from MySQL
			$line = intval($player->pick) ; // Autopicks are < 0
			if ( $line < 0 ) // Autopick
				$line = - $line ;
			else {
				// Update db
			}
			if ( ( $line < 1 ) || ( $line > count($content->cards) ) ) // Pick not found
				$line = rand(1, count($content->cards)) ; // Defining one randomly
			$line-- ; // pick is an index between 1 and card nb
			// Update booster
			$splice = array_splice($content->cards, $line, 1) ;
			query("UPDATE `booster`
			SET
				`content` = '".mysql_real_escape_string(json_encode($content))."'
				, `pick` = ''
				, `destination` = 'side'
			WHERE
				`tournament` = '".$tournament->id."'
				AND `player` = '".$player->order."'
				AND `number` = '".$tournament->round."'
			;") ; // Remove pick from booster
			// Adding to player's pool
			if ( count($splice) == 1 ) {
				$line = $splice[0] ;
				$ex = explode('/', $line) ; // Transformable
				if ( count($ex) > 1 )
					$line = $ex[0] ; // Store only head face
				// Parse deck as an object, add it to destination, stringify
				$deck = deck2arr($player->deck) ;
				$card = card2obj($card_connection, $line, $content->ext) ;
				if ( $player->destination == 'main')
					array_push($deck->main, $card) ;
				else
					array_push($deck->side, $card) ;
				query("UPDATE `registration` SET `deck` = '".mysql_real_escape_string(obj2deck($deck))."', `ready` = 0
					WHERE `tournament_id` = '".$tournament->id."' AND `player_id` = '".$player->player_id."' ;") ;
			} else
				echo count($splice)." cards spliced\n" ;
		}
		$data = json_decode($tournament->data) ;
		if ( property_exists($content, 'cards') && count($content->cards) > 0 ) { // There are cards left in boosters, pass them
			$min_order = 0 ; // If players are numbered from 1 to n, add this to the "0 ... n-1" default orders
			$nb_players = mysql_num_rows($players_query) ;
			if ( $nb_players > 1 ) { // Only rotate if there are several players
				$even_round = ($tournament->round & 1) ; // Defining direction the booster switch will take
				// We have to use a loop, as just adding 1 to index would result in having 2 boosters with the same index
				if ( $even_round ) { // Second, fourth, etc. booster
					$source = $nb_players + $min_order ; // From last
					$step = -1 ; // Decrease to first
				} else { // First and third boosters
					$source = $min_order ; // From first
					$step = 1 ; // Increase to last
					switch_booster($source, $nb_players + $min_order, $tournament) ; // Using $nb_players as switch 
				}
				for ( $i = 0 ; $i < $nb_players ; $i++ ) {
					$dest = $source + $step ;
					switch_booster($dest, $source, $tournament) ;
					$source = $dest ;
				}
				if ( $even_round )
					switch_booster($nb_players, $min_order, $tournament) ; // Used 0 as switch
			}
			$delay = draft_time(count($content->cards), $tournament->round == count($data->boosters)) ;
		} else {
			$tournament->round++ ;
			if ( $row = mysql_fetch_array(query("SELECT * FROM `booster` WHERE `tournament` = '".$tournament->id."' AND `number` = '".$tournament->round."' ;") ) ) // Some boosters left to draft
				$delay = draft_time() ;
			else { // No booster left to draft
				$tournament->status++ ; // Shift to build mode
				$tournament->round = 0 ;
				query("DELETE FROM `booster` WHERE `tournament` = '".$tournament->id."' ;") ; // Clean boosters
				$delay = $build_duration ;
				query("UPDATE `registration` SET
					`deck` = CONCAT(`deck`, '".add_side_lands()."')
				WHERE `tournament_id` = '".$tournament->id."' ;") ;
				tournament_log($tournament->id, '', 'build', '') ;
			}
		}
		query("UPDATE `tournament` SET
			`status` = '".$tournament->status."',
			`due_time` = TIMESTAMPADD(SECOND, $delay, NOW()), 
			`round` = '".$tournament->round."', 
			`data` = '".mysql_real_escape_string(json_encode($data))."'
		WHERE `id` = '".$tournament->id."' ;") ;
	}

	// Do all building user checked "ready" ?
	$query = query("SELECT * FROM `tournament` WHERE `status` = '4' AND `due_time` > NOW() ; ") ;
	while ( $tournament = mysql_fetch_object($query) ) {
		$players = tournament_playing_players($tournament) ;
		foreach ( $players as $player )
			if ( $player->ready != 1 ) // If a player isn't ready
				continue 2 ; // Skip tournament (some players aren't ready)
		// Here all players are ready : Change tournament's due time, to get it managed by next step
		query("UPDATE `tournament` SET `due_time` = TIMESTAMPADD(SECOND, -1, NOW()) WHERE `id` = '".$tournament->id."' ;") ;
	}

	// Start limited tournaments when build time is over
	$query = query("SELECT * FROM `tournament` WHERE `status` = '4' AND `due_time` < NOW() ; ") ;
	while ( $tournament = mysql_fetch_object($query) )
		tournament_start($tournament) ;

	// Start next round when previous one ended
	$query = query("SELECT * FROM `tournament` WHERE `status` = '5' AND `due_time` < NOW() ; ") ;
	while ( $tournament = mysql_fetch_object($query) )
		round_start($tournament) ;

	// If all matches are 2-*, launch next round
	$query = query("SELECT * FROM `tournament` WHERE `status` = '5' AND `due_time` >= NOW() ; ") ;
	while ( $tournament = mysql_fetch_object($query) ) {
		$matches_query = query("SELECT * FROM `round`
			WHERE
				`tournament` = '".$tournament->id."'
				AND `round` = '".$tournament->round."'
				AND `creator_score` < 2
				AND `joiner_score` < 2
		; ") ; // Untermined matches
		if ( mysql_num_rows($matches_query) == 0 ) // No unterminated matches
			round_start($tournament) ;
	}
}
/**/
?>
