<?php
include_once 'lib.php' ;
include_once '../../includes/deck.php' ;
ini_set ('max_execution_time', 0); 
ini_set ('memory_limit', '256M'); 

$name = param_or_die($_GET, 'name') ;
$mask = param($_GET, 'mask', '') ;
$imask = param($_GET, 'imask', '') ;
$date = param($_GET, 'date', '') ;
if ( ! ereg('([0-9]{4})-([0-9]{2})-([0-9]{2})', $date) )
	$date = '' ;

$folder = '../../stats/' ;
$file = $folder.$name ;

if ( ( $mask == '' ) && ( $date == '' ) ) { // Existing report and no date/mask, read them from report
	if ( file_exists($file) ) {
		$report = json_decode(file_get_contents($file)) ;
		if ( $report == null )
			die('Unable to decode report') ;
		if ( isset($report->date) )
			$date = $report->date ;
		if ( isset($report->mask) )
			$mask = $report->mask ;
		if ( isset($report->imask) )
			$imask = $report->imask ;
	} else
		die('You must enter a name or date mask in order to generate a new report') ;
}

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
	)" ;
if ( $date != '' ) {
	$q .= " AND `tournament`.`creation_date` > '$date'" ;
	echo "Selection by date : $date\n" ;
}
if ( $mask != '' ) {
	$q .= "	AND `tournament`.`name` LIKE '%$mask%'" ;
	echo "Selection by name mask : $mask\n" ;
}
if ( $imask != '' ) {
	$q .= "	AND `tournament`.`name` NOT LIKE '%$imask%'" ;
	echo "Selection by name ignore mask : $imask\n" ;
}
$q .= " ;" ;
$t = query_as_array($q) ;
echo 'Query : '.count($t)."\n" ;
// Parse each
$start = microtime(true) ;
$cards = array() ;
$tournaments = array() ;
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
		$tournaments[$d->id] = $d->name ;
		$deck = deck2arr($r->deck, true) ;
		$rounds_number = 1 ;
		if ( $data->rounds_number > $rounds_number )
			$rounds_number = $data->rounds_number ;
		$score = $data->score->{$r->player_id}->gamepoints/3 ; // 3 gamepoints per game win
		foreach ( $deck->main as $id => $card ) // Played cards
			update_score($card->id, $card->rarity, $rounds_number, $score) ;
		foreach ( $deck->side as $id => $card ) // Not played cards
			update_score($card->id, $card->rarity) ;
		$parsed++ ;
	}
	//echo "Parsed #$parsed\n" ;
	echo "\n" ;
}
echo "\n</pre>".'Parse ('.$parsed.') : '.(microtime(true)-$start).'s'."\n" ;
$result = new Simple_Object() ;
if ( $date != '' )
	$result->date = $date ;
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
