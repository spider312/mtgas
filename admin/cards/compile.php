<?php
include_once 'lib.php' ;
$query = query('SELECT * FROM card ORDER BY `id` DESC') ; // Last added cards are managed first, in order not to wait before a die
while ( $arr = mysql_fetch_array($query) ) {
	//$pieces = explode("\n", $arr['text']) ;
	$pieces = mb_split('\n|  ', $arr['text']) ;
	foreach ( $pieces as $piece )
		$piece = trim($piece) ;
	$arr['text'] = implode("\n", $pieces) ;
	query("UPDATE
		card
	SET
		`attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."'
		, `text` = '".mysql_escape_string($arr['text'])."'
	WHERE
		`id` = '".$arr['id']."'
	; ") ;
}
die('end') ;
?>
