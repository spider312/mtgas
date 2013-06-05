<?php
include_once '../../../includes/lib.php' ;
include_once '../../../includes/card.php' ; // Some globals
$base_image_dir = substr(`bash -c "echo ~"`, 0, -1).'/img/' ;
function cache_get($url, $cache_file, $verbose = true) {
	if ( file_exists($cache_file) ) {
		if ( $verbose )
			echo '[use cache]' ;
		$content = @file_get_contents($cache_file) ;
	} else {
		if ( $verbose )
			echo '[update cache : ' ;
		if ( ( $content = @file_get_contents($url) ) !== FALSE ) {
			if ( ( $size = @file_put_contents($cache_file, $content) ) === FALSE ) {
				if ( $verbose )
					echo 'NOT updated' ;
			} else {
				if ( $verbose )
					echo 'updated ('.human_filesize($size).')' ;
			}
		}
		if ( $verbose )
			echo ']' ;
	}
	if ( $content === false )
		die('[no content : '.$url.' -> '.$cache_file.']') ;
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
	// Base
	$name = trim($name) ;
	$name = html_entity_decode($name) ;
	// Non working global tryouts
	//$name = iconv('UTF-8', 'US-ASCII//TRANSLIT', $name) ;
	//$name =  normalizer_normalize($name, Normalizer::FORM_D) ;
	// MV
	$name = str_replace(chr(146), "'", $name) ; // Strange apostrophe
	$name = str_replace(chr(198), 'AE', $name) ;
	$name = str_replace(chr(246), 'o', $name) ;
	// MCI
	$name = str_replace('á', 'a', $name) ;
	$name = str_replace('é', 'e', $name) ;
	$name = str_replace('í', 'i', $name) ;
	$name = str_replace('ö', 'o', $name) ;
	$name = str_replace('ú', 'u', $name) ;
	$name = str_replace('û', 'u', $name) ;
	$name = str_replace('Æ', 'AE', $name) ;
	return $name ;
}

class Importrer {
	public $cards = array() ;
	public $tokens = array() ;
	function __construct() {
	}
	function __destruct() {
	}
	function addcard($rarity, $name, $cost, $types, $text, $url) { // + attrs fixed_attrs
		foreach ( $this->cards as $card )
			if ( $card->name == $name ) {
				echo 'Card already parsed : '.$name.'<br>' ;
				return $card ;
			}
		$card = new ImportCard($rarity, $name, $cost, $types, $text, $url) ;
		$this->cards[] = $card ;
		return $card ;
	}
	function addtoken($type, $pow, $tou, $url) {
		$this->tokens[] = array($type, $pow, $tou, $url) ;
	}
}
class ImportCard {
	public $rarity = 'N' ;
	public $name = 'Uninitialized' ;
	public $cost = 'Uninitialized' ;
	public $types = 'Uninitialized' ;
	public $text = 'Uninitialized' ;
	public $images = array() ;
	public $langs = array() ;
	function __construct($rarity, $name, $cost, $types, $text, $url) {
		$this->rarity = $rarity ;
		$this->name = card_name_sanitize($name) ;
		$this->cost = $cost ;
		$this->types = $types ;
		$this->text = $text ;
		$this->addimage($url) ;
	}
	function addimage($url) { // For double faced cards
		$this->images[] = $url ;
	}
	function addtext($add) { // For all multiple cards (split, flip, double face)
		$this->text .= $add ;
	}
	function setlang($code, $name, $url) {
		$this->langs[$code] = array('name' => $name, 'images' => array($url)) ;
	}
	function addlang($code, $name, $url=null) {
		if ( isset($this->langs[$code]) ) {
			$this->langs[$code]['name'] .= '/'.$name ;
			if ( $url != null )
				$this->langs[$code]['images'][] = $url ;
		} else
			$this->setlang($code, $name, $url) ;
	}
}
?>
