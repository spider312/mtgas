<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'lib.php' ;

$query = query("SELECT * FROM extension WHERE `se` = 'TSB' ; ") ;
$arr = mysql_fetch_array($query) ;
$query2 = query("SELECT * FROM extension WHERE `se` = 'TSP' ; ") ;
$arr2 = mysql_fetch_array($query2) ;
if ( $arr && $arr2 ) {
	echo $arr['id'].'<br>' ;
	$query3 = query('SELECT * FROM card_ext WHERE `ext` = '.$arr['id'].' ; ') ;
	while ( $arr3 = mysql_fetch_array($query3) ) {
		query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`) VALUES ('".$arr3['card']."', '".$arr2['id']."', 'S', '0') ;") ;
		echo $arr3['card'].' : '.$arr3['ext'].'<br>' ;

	}
	die() ;
}
?>