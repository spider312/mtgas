<pre><?php
include_once 'lib.php' ;
$query = query('SELECT * FROM card ORDER BY `id` DESC') ; // Last added cards are managed first, in order not to wait before a die
while ( $arr = mysql_fetch_array($query) ) {
	$arr['text'] = card_text_sanitize($arr['text']) ;
	$attrs = json_encode(new attrs($arr)) ;
	if ( $arr['attrs'] != $attrs ) {
		echo '<hr>'.$arr['name'] ;
		echo '<pre>-'.print_r(obj_diff(json_decode($arr['attrs']), json_decode($attrs)), true).'</pre>';
		echo '<pre>+'.print_r(obj_diff(json_decode($attrs), json_decode($arr['attrs'])), true).'</pre>';
	}
	query("UPDATE
		card
	SET
		`attrs` = '".mysql_escape_string($attrs)."'
		, `text` = '".mysql_escape_string($arr['text'])."'
	WHERE
		`id` = '".$arr['id']."'
	; ") ;
}
die('end') ;
?>
