<pre><?php
include_once 'lib.php' ;
$commit = true ; // Commit changes or display data
$nb = 0 ;
// Last added cards are managed first, in order not to wait before a die
$query = query('SELECT * FROM card ORDER BY `id` DESC') ;
while ( $arr = mysql_fetch_array($query) ) {
	$arr['text'] = card_text_sanitize($arr['text']) ;
	$attrs_obj = new attrs($arr) ;
	$attrs = json_encode($attrs_obj) ;
	if ( $arr['attrs'] != $attrs ) {
		$nb++ ;
		echo '<hr>'.$arr['name'] ;
		echo '<pre>-'.print_r(obj_diff(json_decode($arr['attrs']), $attrs_obj), true).'</pre>';
		echo '<pre>+'.print_r(obj_diff($attrs_obj, json_decode($arr['attrs'])), true).'</pre>';
	}
	if ( $commit ) {
		query("UPDATE
			card
		SET
			`attrs` = '".mysql_escape_string($attrs)."'
			, `text` = '".mysql_escape_string($arr['text'])."'
		WHERE
			`id` = '".$arr['id']."'
		; ") ;
	} else {
		if ( isset($attrs_obj->animate) ) {
			echo $arr['name']."\n" ;
			foreach ( $attrs_obj->animate as $animate ) {
				unset($animate->pow) ;
				unset($animate->tou) ;
				unset($animate->eot) ;
				unset($animate->cost) ;
				unset($animate->types) ;
				unset($animate->subtypes) ;
				unset($animate->color) ;
				print_r($animate) ;
			}
		}
	}
}
die($nb.' updates') ;
?>
