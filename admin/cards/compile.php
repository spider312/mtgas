<?php
include_once 'lib.php' ;
$query = query('SELECT * FROM card ORDER BY `id` DESC') ; // Last added cards are managed first, in order not to wait before a die
while ( $arr = mysql_fetch_array($query) ) {
	// $arr needed for attrs parsing
	$arr['text'] = card_text_sanitize($arr['text']) ;
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
