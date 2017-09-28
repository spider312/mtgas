<?php
function ranking_to_file($file='ranking/week.json', $period='WEEK', $plength=1) {
	if ( ! is_dir('ranking') )
		mkdir('ranking') ;
	global $malformed ;
	$malformed = array() ;
	$players = ranking($period, $plength) ;
	if ( count($malformed) > 0 ) {
		echo "$file malformed : \n" ;
		foreach ( $malformed as $nick ) {
			echo " - $nick\n" ;
		}
	}
	$json = json_encode($players) ;
	if ( $json === false ) {
		echo "$file\n".count($players)."\n".json_verbose_error()."\n-\n" ;
		foreach ( $players as $player ) {
			$ljson = json_encode($player) ;
			if ( $ljson === false ) {
				print_r($player) ;
			}
		}
		return false ;
	}
	$fh = fopen($file, 'w') or die('can\'t open file') ;
	fwrite($fh, $json);
	fclose($fh);
}
function mingames_by_period($period='') {
	$period = strtoupper($period) ;
	switch ( $period ) {
		case 'WEEK' :
			return 5 ;
		case 'MONTH' :
			return 20 ;
		case 'YEAR' :
			return 100 ;
		default :
			return 0 ;
	}
}
function ranking($period='WEEK', $plength=1) {
	global $db ;
	// Cache aliases
	$aliases = array() ;
	$reverse_aliases = array() ;
	$a = $db->select("SELECT * FROM player_alias") ;
	foreach ( $a as $alias ) {
		$aliases[$alias->player_id] = $alias->alias ;
		if ( ! array_key_exists($alias->alias, $reverse_aliases) )
			$reverse_aliases[$alias->alias] = array();
		array_push($reverse_aliases[$alias->alias], $alias->player_id);
	}
	// Get round data
	$r = $db->select("SELECT
		`creator_id`, `creator_nick`, `creator_avatar`, `creator_score`,
		`joiner_id`,`joiner_nick`, `joiner_avatar`, `joiner_score`
	FROM `round`
	WHERE
		`creator_id` != `joiner_id`
		AND `joiner_id` != ''
		AND `creation_date` > TIMESTAMPADD($period, -$plength, NOW())
	;") ;
	// Manage rounds
	$players = array() ;
	foreach ( $r as $round ) {
		manage_round($players, $aliases, $round, 'creator') ;
		manage_round($players, $aliases, $round, 'joiner') ;
	}
	// Filter players not having enough games
	$min_games = mingames_by_period($period) ;
	$players_e = array() ;
	foreach ( $players as $key => $player ) {
		if ( $player->matches > $min_games ) {
			$evaluation = $db->select("SELECT COUNT(*) AS `nb`, SUM(`rating`) AS `sum` FROM `evaluation` WHERE `to` = '$key' AND `date` > TIMESTAMPADD($period, -$plength, NOW())");
			if ( count($evaluation) === 0 ) {
				$player->eval_nb = 0 ;
				$player->eval_sum = 0 ;
			} else {
				$player->eval_nb = $evaluation[0]->nb ;
				$player->eval_sum = $evaluation[0]->sum ;
			}
			$players_e[$key] = $player ;
		}
	}
	// Add alias data
	foreach ( $players_e as $key => $player ) {
		if ( array_key_exists($key, $reverse_aliases) )
			$player->alias = $reverse_aliases[$key] ;
	}
	return $players_e ;
}
function manage_round(&$players, $aliases, $round, $player) {
	$pid = $round->{$player.'_id'} ;
	// Ignore BYE
	if ( $pid == '' )
		return false ;
	// Alias
	if ( array_key_exists($pid, $aliases) )
		$pid = $aliases[$pid] ;
	// Entry creation
	if ( ! array_key_exists($pid, $players) ) {
		$p = new stdClass() ;
		$p->matches = 0 ;
		$p->score = 0 ;
		$players[$pid] = $p ;
	}
	// Check nick JSONability
	$nick = $round->{$player.'_nick'} ;
	if ( json_encode($nick) === false ) {
		global $malformed ;
		if ( array_search($nick, $malformed) === false ) {
			array_push($malformed, $nick) ;
		}
		return false ;
	}
	// Update infos
	$players[$pid]->nick = $nick ;
	$players[$pid]->avatar = $round->{$player.'_avatar'} ;
	$players[$pid]->matches++ ;
	$other = ( $player == 'creator' ) ? 'joiner' : 'creator' ;
	$score = $round->{$player.'_score'} - $round->{$other.'_score'} ;
	if ( $score == 0 )
		$players[$pid]->score++ ;
	else if ( $score > 0 )
		$players[$pid]->score += 3 ;
	return true ;
}
