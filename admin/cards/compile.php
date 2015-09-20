<pre><?php
include_once 'lib.php' ;
// Apply ?
$apply = param($_GET, 'apply', false) ;
if ( $apply !== false )
	$apply = true ;
// Loop
$query = query('SELECT * FROM card ORDER BY `id` DESC') ; // Last added cards are managed first, in order not to wait before a die
$nb = 0 ;
while ( $arr = mysql_fetch_array($query) ) {
	$arr['text'] = card_text_sanitize($arr['text']) ;
	$attrs_obj = new attrs($arr) ;
	$attrs = json_encode($attrs_obj) ;
	if ( $arr['attrs'] != $attrs ) {
		$nb++ ;
		echo '<hr>'.$arr['name'] ;
		echo '<pre>-'.print_r(obj_diff(json_decode($arr['attrs']), $attrs_obj), true).'</pre>';
		echo '<pre>+'.print_r(obj_diff($attrs_obj, json_decode($arr['attrs'])), true).'</pre>';
		if ( $apply ) {
			query("UPDATE
				card
			SET
				`attrs` = '".mysql_escape_string($attrs)."'
				, `text` = '".mysql_escape_string($arr['text'])."'
			WHERE
				`id` = '".$arr['id']."'
			; ") ;
		}
	}
}
die($nb.' updates <a href="?apply=1">apply</a>') ;
?>
