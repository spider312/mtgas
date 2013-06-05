<?php
include_once 'lib.php' ;
// Get IDs
if ( $res = query_oneshot("SELECT `id` FROM `extension` WHERE `se` = 'CUB' ; ") )
	$cub_id = $res->id ;
if ( $res = query_oneshot("SELECT `id` FROM `extension` WHERE `se` = 'CUBL' ; ") )
	$cubl_id = $res->id ;
if ( $res = query_oneshot("SELECT `id` FROM `extension` WHERE `se` = 'CUBS' ; ") )
	$cubs_id = $res->id ;
// Cleanup
if ( query("DELETE FROM `card_ext` WHERE `ext` = '$cubl_id' ; ") )
	echo mysql_affected_rows().' cards removed from CUBL<br>' ;
if ( query("DELETE FROM `card_ext` WHERE `ext` = '$cubs_id' ; ") )
	echo mysql_affected_rows().' cards removed from CUBS<br>' ;
// Get
if ( $cards = query_as_array("SELECT `card`, `rarity` FROM `card_ext` WHERE `ext` = '$cub_id' ; ") )
	echo count($cards).' cards in CUB to dispatch<br>' ;

// Dispatch
$err = 0 ;
$cubl_nb = 0 ;
$cubs_nb = 0 ;
//while ( $card = mysql_fetch_object($cards) ) {
foreach ( $cards as $card )
	switch ( $card->rarity ) {
		case 'U' :
			if ( query("INSERT INTO `mtg`.`card_ext` (`card`, `ext`, `rarity`, `nbpics`) VALUES ('".$card->card."', '".$cubl_id."', 'C', '0');") )
				$cubl_nb++ ;
			else
				$err++ ;
			break ;
		case 'C' :
			if ( query("INSERT INTO `mtg`.`card_ext` (`card`, `ext`, `rarity`, `nbpics`) VALUES ('".$card->card."', '".$cubs_id."', 'C', '0');") )
				$cubs_nb++ ;
			else
				$err++ ;
			break ;
		default : 
			echo 'Unmanaged rarity : '.$card->rarity.'<br>' ;
	}
echo $cubl_nb.' cards added to CUBL<br>' ;
echo $cubs_nb.' cards added to CUBS<br>' ;
echo $err.' errors' ;
?>
