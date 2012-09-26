<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
include '../../includes/card.php' ;
include '../../includes/deck.php' ;
include '../lib.php' ;
$id = param_or_die($_POST, 'id') ;
$deck = param_or_die($_POST, 'deck') ;
//$deck = stripslashes($deck) ;
$new_deck = json_decode($deck) ;
if ( $new_deck == NULL ) {
	die('{"msg": "Unable to parse modified deck, try to refresh"}') ;
}
$mwdeck = obj2deck($new_deck) ;

// Get previous deck
$registration = query_oneshot("SELECT `deck` FROM `registration` WHERE `tournament_id` = '$id' AND `player_id` = '$player_id' ; ") ;
$previous_deck = deck2arr($registration->deck) ;

// Extract lands (from both previous and next deck)
function extractlands($card) {
	return ( $card->rarity != 'L' ) ;
}
$previous_deck->main = array_filter($previous_deck->main, 'extractlands') ;
$previous_deck->side = array_filter($previous_deck->side, 'extractlands') ;
$new_deck->main = array_filter($new_deck->main, 'extractlands') ;
$new_deck->side = array_filter($new_deck->side, 'extractlands') ;
// Remove all cards present in both deck (hoping both will be empty on end)
function removefromnew($card) {
	global $new_deck ;
	foreach ( $new_deck->main as $id => $new_card ) {
		if ( $card->id == $new_card->id ) {
			array_splice($new_deck->main, $id, 1) ;
			return false ;
		}
	}
	foreach ( $new_deck->side as $id => $new_card ) {
		if ( $card->id == $new_card->id ) {
			array_splice($new_deck->side, $id, 1) ;
			return false ;
		}
	}
	return true ;
}
$previous_deck->main = array_filter($previous_deck->main, 'removefromnew') ;
$previous_deck->side = array_filter($previous_deck->side, 'removefromnew') ;
// Checking if both are empty
function debugdeck($deck) {
	$txt = '' ;
	foreach ( $deck->main as $id => $card )
		$txt .= ' - '.$card->name.'\n' ;
	foreach ( $deck->side as $id => $card )
		$txt .= ' - '.$card->name.'\n' ;
	return $txt ;
}
$missing = count($previous_deck->main) + count($previous_deck->side) ;
$added = count($new_deck->main) + count($new_deck->side) ;
$msg = '' ;
if ( $missing > 0 )
	$msg .= 'Missing cards : \n'.debugdeck($previous_deck) ;
if ( $added > 0 )
	$msg .= 'Added cards : \n'.debugdeck($new_deck) ;
if ( $msg != '' )
	die('{"msg": "Some errors detected : \n'.$msg.'You may have to refresh and lose all unsaved modifications"}') ;

// All were OK, update
query("UPDATE `registration` SET `deck` = '".mysql_real_escape_string($mwdeck)."' WHERE `tournament_id` = '$id' AND `player_id` = '$player_id' ; ") ;
tournament_log($id, $player_id, 'save') ;

// Return deck as mw file in order to save it in list if asked
$mwdeck = str_replace("\n", '\n', $mwdeck) ;
die(json_encode('{"nb": '.mysql_affected_rows().', "deck": "'.$mwdeck.'"}')) ;
?>
