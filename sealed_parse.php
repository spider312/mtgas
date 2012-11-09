<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/card.php' ;
include 'includes/deck.php' ;
$ddate = '2012-11-06' ;
$date = param($_GET, 'date', $ddate) ;
if ( ! ereg('([0-9]{4})-([0-9]{2})-([0-9]{2})', $date) )
	$date = $ddate ;
$name = param($_GET, 'name', '') ;
ini_set ('max_execution_time', 0); 
ini_set ('memory_limit', '256M'); 
$card_connection = card_connect() ;
// All decks from all sealed events
$q = "SELECT
	`registration`.`player_id`,
	`registration`.`deck`,
	`tournament`.`id`,
	`tournament`.`name`,
	`tournament`.`creation_date`,
	`tournament`.`data`
FROM
	`registration`, `tournament`
WHERE
	`registration`.`tournament_id` = `tournament`.`id`
	AND `tournament`.`min_players` > 1
	AND `tournament`.`type` = 'sealed'
	AND `tournament`.`creation_date` > '$date'
" ;
if ( $name != '' )
	$q .= "	AND `tournament`.`name` LIKE '%$name%'
" ;
$q .= " ;" ;
$r = query_as_array($q) ;
echo 'Query : '.count($r)."\n" ;
// Parse each
$start = microtime(true) ;
$cards = array() ;
$i = 0 ;
echo '<pre>' ;
foreach ( $r as $d ) {
	print_r($d) ;
	$data = json_decode($d->data) ;
	if ( property_exists($data, 'score') && property_exists($data->score, $d->player_id) && property_exists($data->score->{$d->player_id}, 'gamepoints') ) {
		$deck = deck2arr($d->deck, true) ;
		$score = $data->score->{$d->player_id}->gamepoints/6 ; // 3 gamepoints per game win : 6 gamepoint = win, 3 gamepoints = lose 2-1, 0 gamepoints = lose 2-0
		foreach ( $deck->main as $id => $card ) // Played cards
			update_score($card->id, 1, $score) ;
		foreach ( $deck->side as $id => $card ) // Not played cards
			update_score($card->id) ;
		$i++ ;
		echo $i." : ".$d->creation_date." - ".$d->name."\n" ;
	} else
		echo 'Unparsed : '.$d->id."\n" ;
}
echo "\n</pre>".'Parse ('.$i.') : '.(microtime(true)-$start).'s'."\n" ;
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
