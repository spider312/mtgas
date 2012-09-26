<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../includes/card.php' ;
$ext_se = param($_GET, 'ext', '') ;
$connec = card_connect() ;
// Defining extension
if ( $ext = mysql_fetch_object(query("SELECT * FROM `extension` WHERE `se` = '$ext_se' ;", 'Search ext '.$ext_se, $connec)) ) { // If found, the one in param
} else {
	if ( $ext = mysql_fetch_object(query("SELECT * FROM `extension` ORDER BY `priority` LIMIT 0, 1 ;", 'All ext search', $connec)) ) { // Or the first in priority order
	} else
		die('{}') ;
}
// Searching lands
$result = array() ;
$query = query("SELECT * FROM `card`, `card_ext` WHERE `card_ext`.`card` = `card`.`id` AND `card_ext`.`ext` = '".$ext->id."' AND `card_ext`.`rarity` = 'L' ;", 'Card search', $connec) ;
while ( $card = mysql_fetch_object($query) ) {
	$card->ext = $ext->se ;
	$nbpics = intval($card->nbpics) ;
	$card->attrs = json_decode($card->attrs) ;
	if ( $nbpics > 1 )
		$card->attrs->nb = 1 ;
	$result[] = $card ;
}
die(json_encode($result)) ;
?>
