<?php
function cache_get($url, $cache_file) {
	if ( file_exists($cache_file) ) {
		echo '[use cache]' ;
		$content = @file_get_contents($cache_file) ;
	} else {
		echo '[update cache : ' ;
		if ( ( $content = @file_get_contents($url) ) !== FALSE ) {
			if ( ( $size = @file_put_contents($cache_file, $content) ) === FALSE )
				echo 'NOT updated' ;
			else
				echo 'updated ('.human_filesize($size).')' ;
		}
		echo ']' ;
	}
	if ( $content === false )
		echo '[no content]' ;
	return $content ;
}
function card_import($name, $cost, $types, $text) {
	global $mysql_connection ;
	if ( $arr = card_get($name) ) {
		$card_id = $arr['id'] ;
		$updates = array() ;
		if ( $arr['cost'] != $cost )
			$updates[] = "`cost` = '$cost'" ;
		if ( $arr['types'] != $types )
			$updates[] = "`types` = '".mysql_real_escape_string($types)."'" ;
		if ( trim($arr['text']) != $text )
			$updates[] = "`text` = '".mysql_real_escape_string($text)."'" ;
		$arr = array(
			'name' => $name,
			'cost' => $cost,
			'types' => $types,
			'text' => $text,
		) ;
		if ( count($updates) > 0 )
			$q = query("UPDATE `card` SET ".implode(', ', $updates).", `attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."' WHERE `id` = $card_id ;") ;
		else
			$q = query("UPDATE `card` SET `attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."' WHERE `id` = $card_id ;") ;
	} else {
		$arr = array(
			'name' => $name,
			'cost' => $cost,
			'types' => $types,
			'text' => $text,
		) ;
		query("INSERT INTO `mtg`.`card`
		(`name` ,`cost` ,`types` ,`text`, `attrs`)
		VALUES ('".mysql_real_escape_string($name)."', '$cost', '$types', '".mysql_real_escape_string($text)."', '".mysql_escape_string(json_encode(new attrs($arr)))."');") ;
		$card_id = mysql_insert_id($mysql_connection) ;
	}
	return $card_id ;
}
function card_get($name) {
	global $mysql_connection ;
	$qs = query("SELECT * FROM card WHERE `name` = '".mysql_real_escape_string($name)."' ; ") ;
	return mysql_fetch_array($qs) ;
}
function card_name_sanitize($name) {
	$name = str_replace(chr(146), "'", $name) ;
	$name = str_replace('&AElig;', "AE", $name) ;
	$name = trim($name) ;
	return $name ;
}
?>
