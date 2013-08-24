<?php
function ranking_to_file($file='ranking/week.json', $period='WEEK', $plength=1) {
	if ( ! is_dir('ranking') )
		mkdir('ranking') ;
	$fh = fopen($file, 'w') or die('can\'t open file') ;
	$players = ranking($period, $plength) ;
	fwrite($fh, json_encode($players));
	fclose($fh);
}
function mingames_by_period($period='') {
	$period = strtoupper($period) ;
	switch ( $period ) {
		case 'WEEK' :
			return 2 ;
		case 'MONTH' :
			return 10 ;
		case 'YEAR' :
			return 50 ;
		default :
			return 0 ;
	}
}
function ranking($period='WEEK', $plength=1) {
	$players = array() ;
	$r = query_as_array("SELECT
		`creator_id`, `creator_nick`, `creator_avatar`, `creator_score`,
		`joiner_id`,`joiner_nick`, `joiner_avatar`, `joiner_score`
	FROM `round`
	WHERE
		`creator_id` != `joiner_id`
		AND `joiner_id` != ''
		AND `creation_date` > TIMESTAMPADD($period, -$plength, NOW())
	;") ;
	foreach ( $r as $round ) {
		manage_round($players, $round, 'creator', 'joiner') ;
		manage_round($players, $round, 'joiner', 'creator') ;
	}
	$min_games = mingames_by_period($period) ;
	$players_e = array() ;
	foreach ( $players as $key => $player ) {
		if ( $player->matches > $min_games )
			$players_e[$key] = $player ;
	}
	return $players_e ;
}
function manage_round(&$players, $round, $player, $other) {
	$pid = $round->{$player.'_id'} ;
	if ( $pid == '' ) // BYE
		return false ;
	if ( ! array_key_exists($pid, $players) ) {
		$p = new simple_object() ;
		$p->matches = 0 ;
		$p->score = 0 ;
		$players[$pid] = $p ;
	}
	// Update infos
	$players[$pid]->nick = $round->{$player.'_nick'} ;
	$players[$pid]->avatar = $round->{$player.'_avatar'} ;
	$players[$pid]->matches++ ;
	$score = $round->{$player.'_score'} - $round->{$other.'_score'} ;
	if ( $score == 0 )
		$players[$pid]->score++ ;
	else if ( $score > 0 )
		$players[$pid]->score += 3 ;
	return true ;
}
function sort_matches($a, $b) {
	$result = $b->matches - $a->matches ;
	if ( $result != 0 )
		$result = $result / abs($result) ;
	return $result ;
}
function sort_score($a, $b) {
	$result = $b->score - $a->score ;
	if ( $result != 0 )
		$result = $result / abs($result) ;
	return $result ;
}
function sort_ratio($a, $b) {
	$result = ( $b->score / $b->matches ) - ( $a->score / $a->matches );
	if ( $result != 0 )
		$result = $result / abs($result) ;
	return $result ;
}
