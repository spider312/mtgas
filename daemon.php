<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'tournament/lib.php' ;
include $dir.'/includes/deck.php' ;
include $dir.'/includes/card.php' ;
include 'includes/ranking.php' ;
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
//log($seed) ;
mt_srand($seed) ;

$day = 0 ;

while ( sleep($daemon_delay) !== FALSE ) {
	if ( $day != date('j') ) { // Day change
		ranking_to_file('ranking/week.json', 'WEEK') ;
		ranking_to_file('ranking/month.json', 'MONTH') ;
		ranking_to_file('ranking/year.json', 'YEAR') ;
		$day = date('j') ;
	}
// === [ SINGLE GAMES ] ===
	// Remove not-pending-anymore games
	query("UPDATE `round` SET `status` = '0' WHERE `status` = '1' AND `last_update_date` < NOW() - $timeout ; ") ;
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
		// Start first stage for tournament depending on type
		switch ( $tournament->type ) {
			case 'draft' :
				$booster = array_shift($data->boosters) ;
				$tournament->round = 1 ;
				foreach ( $players as $player )
					booster_open($tournament, $player, $booster) ; // Must be done after updating tournament, 'cuz it takes infos in it (round)
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
				if ( array_search('CUB', $data->boosters) !== FALSE ) { // Singleton
					$card_connection = card_connect() ;
					$cards = query_as_array('	SELECT
						`card`.`name`,
						`card`.`attrs`,
						`card_ext`.`nbpics`,
						`card_ext`.`rarity`,
						`extension`.`se`
					FROM
						`card`,
						`card_ext`,
						`extension`
					WHERE
						`card`.`id` = `card_ext`.`card`
						AND `extension`.`id` = `card_ext`.`ext`
					'."AND `extension`.`se` = 'CUB' ; ", 'Get cards in Cube (modified)', $card_connection) ;
				} else if ( array_search('OMC', $data->boosters) !== FALSE ) {
					$card_connection = card_connect() ;
					$cards = query_as_array('	SELECT
						`card`.`name`,
						`card`.`attrs`,
						`card_ext`.`nbpics`,
						`card_ext`.`rarity`,
						`extension`.`se`
					FROM
						`card`,
						`card_ext`,
						`extension`
					WHERE
						`card`.`id` = `card_ext`.`card`
						AND `extension`.`id` = `card_ext`.`ext`
					'."AND `extension`.`se` = 'OMC' ; ", 'Get cards in Cube (original)', $card_connection) ;
				} else
					$cards = null ;
				if ( $data->clone_sealed ) { // Each player has the same deck
					query("UPDATE `registration`
						SET
							`deck` = '".pool_open($data->boosters, mysql_real_escape_string($tournament->name), &$cards)."' 
						WHERE
							`tournament_id` = '".$tournament->id."'
						;") ;
				} else {
					foreach ( $players as $player ) {
						query("UPDATE `registration`
							SET
							
							`deck` = '".pool_open($data->boosters, mysql_real_escape_string($tournament->name), &$cards)."' 
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
			`booster`.`player` = `registration`.`order`
		ORDER BY `registration`.`order`; ") ; // All players from the events and their current boosters
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
			WHERE
				`tournament` = '".$tournament->id."'
				AND `player` = '".$player->order."'
			;") ; // Remove pick from booster
			// Adding to player's pool
			if ( count($splice) == 1 ) {
				$line = $splice[0] ;
				$ex = explode('/', $line) ; // Transformable
				if ( count($ex) > 1 )
					$line = $ex[0] ;
				// Store only head face
				$pick = 'SB: 1 ['.$content->ext.'] '.mysql_real_escape_string($line)."\n" ; // Converting to mwDeck format
				query("UPDATE `registration` SET `deck` = CONCAT(`deck`, '$pick'), `ready` = 0
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
			if ( ( count($content->cards) == 1 ) && ( count($data->boosters) == 0) ) // Last pick from last booster
				$delay = 1 ;
			else
				$delay = draft_time(count($content->cards), $tournament->round) ;
		} else {
			$tournament->round++ ;
			if ( count($data->boosters) > 0 ) { // Some boosters left to draft
				$booster = array_shift($data->boosters) ;
				$players_query = query("SELECT * FROM `registration` WHERE `registration`.`tournament_id` = '".$tournament->id."'
				ORDER BY `registration`.`order`;") ; // All players from the events
				while ( $player = mysql_fetch_object($players_query) )
					booster_open($tournament, $player, $booster) ; // Open a new booster
				$delay = draft_time() ;
			} else { // No booster left to draft
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
