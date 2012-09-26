<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once 'lib.php' ;
// Get all extensions
$ext = array() ;
$query = query('SELECT id, se FROM extension ORDER BY id ASC ;') ;
echo '<ul>' ;
while ( $arr = mysql_fetch_array($query) ) {
	$ext[$arr['id']] = $arr['se'] ;
	$query_b = query('SELECT * FROM card_ext WHERE ext = '.$arr['id'].' ; ') ;
	if ( mysql_num_rows($query_b) < 1 )
		echo '<li>Ext '.$arr['se'].' ('.$arr['id'].') has no card</li>' ;
}
echo '</ul>' ;

// Get all cards
$card = array() ;
$query = query('SELECT id, name FROM card ORDER BY id ASC ;') ;
while ( $arr = mysql_fetch_array($query) ) {
	$card[$arr['id']] = $arr['name'] ;
	$query_b = query('SELECT * FROM card_ext WHERE card = '.$arr['id'].' ; ') ;
	if ( mysql_num_rows($query_b) < 1 )
		echo '<li>Card '.$arr['name'].' ('.$arr['id'].') is in no extension</li>' ;
}

// Check links
$card_ext = array() ;
echo '<ul>' ;
$query = query('SELECT card, ext FROM card_ext ;') ;
while ( $arr = mysql_fetch_array($query) ) {
	if ( ! array_key_exists($arr['ext'], $ext) )
		echo '<li>Ext '.$arr['ext'].' does not exist</li>' ;
	if ( ! array_key_exists($arr['card'], $card) )
		echo '<li>Card '.$arr['card'].' does not exist</li>' ;
}
echo '</ul>' ;
?>
