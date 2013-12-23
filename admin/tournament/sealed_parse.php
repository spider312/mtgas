<?php
include_once 'lib.php' ;
include_once '../../includes/deck.php' ;
ini_set ('max_execution_time', 0); 
ini_set ('memory_limit', '256M'); 

$name = param_or_die($_GET, 'name') ;
$format = param($_GET, 'format', '') ;
$date = param($_GET, 'date', '') ;
$exts = param($_GET, 'exts', '') ;
$mask = param($_GET, 'mask', '') ;
$imask = param($_GET, 'imask', '') ;
if ( ! ereg('([0-9]{4})-([0-9]{2})-([0-9]{2})', $date) )
	$date = '' ;

$folder = '../../stats/' ;
$file = $folder.$name ;

$exts = explode(',', $exts) ;
foreach ( $exts as $i => $ext )
	$exts[$i] = trim($ext) ;

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
	`tournament`.`min_players` > 1" ;
/*	AND  (
		`tournament`.`type` = 'sealed'
		OR `tournament`.`type` = 'draft'
	)" ;*/
echo '<ul>' ;
if ( $date != '' ) {
	$q .= " AND `tournament`.`creation_date` > '$date'" ;
	echo "<li>Selection by date : $date</li>" ;
}
if ( $format != '' ) {
	$q .= " AND `tournament`.`type` = '$format'" ;
	echo "<li>Selection by format : $format</li>" ;
}
if ( count($exts) > 0 )
	echo "<li>Selection by extensions : ".implode(', ', $exts)."</li>" ;
if ( $mask != '' ) {
	$q .= "	AND `tournament`.`name` LIKE '%$mask%'" ;
	echo "<li>Selection by name mask : $mask</li>" ;
}
if ( $imask != '' ) {
	$q .= "	AND `tournament`.`name` NOT LIKE '%$imask%'" ;
	echo "<li>Selection by name ignore mask : $imask</li>" ;
}
echo '</ul>' ;
$q .= " ;" ;
$t = query_as_array($q) ;
echo 'Query results : '.count($t)."\n" ;
// Parse each
$start = microtime(true) ;
$json_parse = 0 ;
$deck_parse = 0 ;
$cards = array() ;
$tournaments = array() ;
$parsed = 0 ;
echo '<pre>' ;
$starter_won = $starter_lost = 0 ;
foreach ( $t as $d ) {
	echo $d->creation_date.' - '.'<a href="/tournament/?id='.$d->id.'">'.$d->name.'</a> : ' ;
	$json_start = microtime(true) ;
	$data = json_decode($d->data) ;
	$json_parse += ( microtime(true) - $json_start ) ;
	if ( ! property_exists($data, 'score') ) {
		echo "Unparsed : no score\n" ;
		continue ;
	}
	if ( ( count($exts) > 0 ) && ( count(array_intersect($data->boosters, $exts)) == 0 ) ) {
		echo "Containing no wanted booster\n" ;
		continue ;
	}
	// Get winner
	foreach ( $data->results as $i => $round ) {
		foreach ( $round as $game ) {
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
							if ( $starter == '' )
								$starter_lost++ ;
							else {
								if ( $starter == $action->sender )
									$starter_lost++ ;
								else
									$starter_won++ ;
							}
						}
						break ;
				}
		}
	}
	$g = query_as_array("SELECT * FROM `registration` WHERE `registration`.`tournament_id` = '".$d->id."'") ;
	$lparsed = 0 ;
	foreach ( $g as $r ) {
		if ( ! property_exists($data->score, $r->player_id) ) {
			echo "Unparsed : no score for ".$g->nick."\n" ;
			continue ;
		}
		if ( ! property_exists($data->score->{$r->player_id}, 'gamepoints') ) {
			echo "Unparsed : no gamepoints for ".$r->nick."\n" ;
			continue ;
		}
		$tournaments[$d->id] = $d->name ;
		$deck_start = microtime(true) ;
		$deck = deck2arr($r->deck, true) ;
		$deck_parse += ( microtime(true) - $deck_start ) ;
		$rounds_number = 1 ;
		if ( $data->rounds_number > $rounds_number )
			$rounds_number = $data->rounds_number ;
		$score = $data->score->{$r->player_id}->gamepoints/3 ; // 3 gamepoints per game win
		foreach ( $deck->main as $id => $card ) // Played cards
			if ( ( count($exts) == 0 ) || ( count(array_intersect($card->exts, $exts)) > 0 ) )
				update_score($card->id, $card->rarity, $rounds_number, $score) ;
		foreach ( $deck->side as $id => $card ) // Not played cards
			if ( ( count($exts) == 0 ) || ( count(array_intersect($card->exts, $exts)) > 0 ) )
				update_score($card->id, $card->rarity) ;
		$lparsed++ ;
	}
	$parsed += $lparsed ;
	echo "Parsed $lparsed decks\n" ;
}
echo "\n</pre>Parsed $parsed decks" ;
echo "<ul><li>Total time : ".(microtime(true)-$start).'</li>' ;
echo "<li>Tournament data JSON decoding : $json_parse</li>" ;
echo "<li>Decks parsing (cards/extensions searching): $deck_parse</li></ul>" ;
$result = new Simple_Object() ;
if ( $date != '' )
	$result->date = $date ;
if ( $format != '' )
	$result->format = $format ;
if ( count($exts) > 0 )
	$result->exts = $exts ;
if ( $mask != '' )
	$result->mask = $mask ;
if ( $imask != '' )
	$result->imask = $imask ;
$result->cards = $cards ;
$result->tournaments = $tournaments ;
$result->starter_won = $starter_won ;
$result->starter_lost = $starter_lost ;
if ( ! is_dir($folder) )
	mkdir($folder) ;
$fh = fopen($file, 'w') or die('can\'t open file') ;
fwrite($fh, json_encode($result));
fclose($fh);

// Lib
function update_score($card_id, $rarity='S', $played=0, $score=0) {
	global $cards ;
	if ( ! array_key_exists($card_id, $cards) ) {
		$cards[$card_id] = new simple_object() ;
		$cards[$card_id]->opened = 1 ;
		$cards[$card_id]->played = $played ;
		$cards[$card_id]->scored = $score ;
		$cards[$card_id]->rarity = array($rarity => 1) ;
	} else {
		$cards[$card_id]->opened += 1 ;
		$cards[$card_id]->played += $played ;
		$cards[$card_id]->scored += $score ;
		if ( ! array_key_exists($rarity, $cards[$card_id]->rarity) )
			$cards[$card_id]->rarity[$rarity] = 1 ;
		else
			$cards[$card_id]->rarity[$rarity]++ ;
	}
}
die('end'."\n") ;
?>
