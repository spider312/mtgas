<?php
include_once 'lib.php' ;
include_once '../../includes/deck.php' ;
$ddate = '2013-01-29' ;
$date = param($_GET, 'date', $ddate) ;
if ( ! ereg('([0-9]{4})-([0-9]{2})-([0-9]{2})', $date) )
	$date = $ddate ;
$name = param($_GET, 'name', '') ;
ini_set ('max_execution_time', 0); 
ini_set ('memory_limit', '256M'); 
$card_connection = card_connect() ;
// All decks from all sealed events
$q = "SELECT
	`tournament`.`id`,
	`tournament`.`name`,
	`tournament`.`creation_date`,
	`tournament`.`data`
FROM
	`tournament`
WHERE
	`tournament`.`min_players` > 1
	AND  (
		`tournament`.`type` = 'sealed'
		OR `tournament`.`type` = 'draft'
	)
	AND `tournament`.`creation_date` > '$date'
" ;
if ( $name != '' )
	$q .= "	AND `tournament`.`name` LIKE '%$name%'
" ;
$q .= " ;" ;
$t = query_as_array($q) ;
echo 'Query : '.count($t)."\n" ;
// Parse each
$start = microtime(true) ;
$cards = array() ;
$parsed = 0 ;
echo '<pre>' ;
$starter_won = $starter_lost = 0 ;
foreach ( $t as $d ) {
	echo $d->creation_date.' - '.'<a href="/tournament/?id='.$d->id.'">'.$d->name.'</a> : ' ;
	$data = json_decode($d->data) ;
	if ( ! property_exists($data, 'score') ) {
		echo "Unparsed : no score\n" ;
		continue ;
	}
	foreach ( $data->results as $i => $round ) {
		//echo "\n\tParsing round #".$i ;
		foreach ( $round as $game ) {
			//echo "\n\t\tParsing game #".$game->id ;
			$a = query_as_array("SELECT * FROM `action` WHERE `action`.`game` = '".$game->id."'") ;
			$starter = '' ;
			foreach ( $a as $action )
				switch ( $action->type ) {
					case 'choose' :
						$param = json_decode($action->param) ;
						$starter = $param->player ;
						if ( $starter == 'game.creator' )
							$starter = $game->creator_id ;
						elseif ( $starter == 'game.joiner' )
							$starter = $game->joiner_id ;
						break ;
					case 'psync' :
						$param = json_decode($action->param) ;
						if ( isset($param->attrs->score) ) {
							if ( $starter == '' ) {
								$starter_lost++ ;
								//die("\n\t\t\tLoser while no starter : ".$action->sender) ;
							} else {
								if ( $starter == $action->sender )
									//echo "\n\t\t\tStarter lost" ;
									$starter_lost++ ;
								else
									//echo "\n\t\t\tStarter won" ;
									$starter_won++ ;
							}
						}
						break ;
				}
		}
	}
	$g = query_as_array("SELECT * FROM `registration` WHERE `registration`.`tournament_id` = '".$d->id."'") ;
	foreach ( $g as $r ) {
		if ( ! property_exists($data->score, $r->player_id) ) {
			echo "Unparsed : no score for ".$g->nick."\n" ;
			continue ;
		}
		if ( ! property_exists($data->score->{$r->player_id}, 'gamepoints') ) {
			echo "Unparsed : no gamepoints for ".$r->nick."\n" ;
			continue ;
		}
		$deck = deck2arr($r->deck, true) ;
		$rounds_number = 1 ;
		if ( $data->rounds_number > $rounds_number )
			$rounds_number = $data->rounds_number ;
		$score = $data->score->{$r->player_id}->gamepoints/(6*$rounds_number) ; // 3 gamepoints per game win : 6 gamepoint = win, 3 gamepoints = lose 2-1, 0 gamepoints = lose 2-0
		foreach ( $deck->main as $id => $card ) // Played cards
			update_score($card->id, 1, $score) ;
		foreach ( $deck->side as $id => $card ) // Not played cards
			update_score($card->id) ;
		$parsed++ ;
	}
	//echo "Parsed #$parsed\n" ;
	echo "\n" ;
}
echo "\n</pre>".'Parse ('.$parsed.') : '.(microtime(true)-$start).'s'."\n" ;
echo "<br>Starter won $starter_won , Starter lost $starter_lost\n" ;
// Table will be filled, start by empty it
query('TRUNCATE TABLE `mtg`.`pick` ; ', 'truncate', $card_connection) ;
// Insert
foreach ( $cards as $id => $card ) {
	query("
		INSERT INTO
			`pick` (
				`card_id`, 
				`sealed_open`,
				`sealed_play`, 
				`sealed_score`
			)
		VALUES (
			'$id',
			'".$card->opened."',
			'".$card->played."',
			'".$card->scored."'
		)
	; ", 'insert', $card_connection) ;
}
// Lib
function update_score($card_id, $played=0, $score=0) {
	global $cards ;
	if ( ! array_key_exists($card_id, $cards) ) {
		$cards[$card_id] = new simple_object() ;
		$cards[$card_id]->opened = 1 ;
		$cards[$card_id]->played = $played ;
		$cards[$card_id]->scored = $score ;
	} else {
		$cards[$card_id]->opened += 1 ;
		$cards[$card_id]->played += $played ;
		$cards[$card_id]->scored += $score ;
	}
}
die('end'."\n") ;
?>
