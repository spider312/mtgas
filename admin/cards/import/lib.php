<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../lib.php' ;
$base_image_dir = substr(`bash -c "echo ~"`, 0, -1).'/img/' ;
// Cache management

function file_get_contents_utf8($fn) {
	$opts = array(
	'http' => array(
	    'method'=>"GET",
	    'header'=>"Content-Type: text/html; charset=utf-8"
	)
	);
	$context = stream_context_create($opts);
	$result = @file_get_contents($fn,false,$context);
	return $result;
} 
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
// Diff ( http://compsci.ca/v3/viewtopic.php?p=142539 )
function diff($old, $new){
	$maxlen = 0 ;
	foreach($old as $oindex => $ovalue){
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex){
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
				$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen){
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}       
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}
function htmlDiff($old, $new){
	$ret = '' ;
	$diff = diff(explode(' ', $old), explode(' ', $new));
	foreach($diff as $k){
		if(is_array($k))
			$ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
				(!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
		else $ret .= $k . ' ';
	}
	return $ret;
}
function string_detail_disp($str) {
	$result = '<pre>' ;
	for ( $i=0 ; $i < strlen($str) ; $i++ )
		$result .= '<span title="'.ord($str[$i]).'">'.$str[$i].'</span>' ;
	$result .= '</pre>' ;
	return $result ;
}
function string_detail($str) {
	$result = '' ;
	for ( $i=0 ; $i < strlen($str) ; $i++ )
		$result .= '['.$str[$i].'] : '.ord($str[$i])."\n" ;
	return $result ;
}
// Classes
class ImportExtension {
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
	function import($apply=false) {
		$result = array() ;
		foreach ( $this->cards as $card )
			$result[] = $card->import($apply) ;
		return $result ;
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
		$this->text = card_text_sanitize($text) ;
		$this->addimage($url) ;
	}
	function addimage($url) { // For double faced cards
		$this->images[] = $url ;
	}
	function addtext($add) { // For all multiple cards (split, flip, double face)
		$this->text .= "\n".card_text_sanitize($add) ;
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
	function import($apply=false) {
		global $mysql_connection ;
		$res = query("SELECT * FROM card WHERE `name` = '".mysql_real_escape_string($this->name)."' ; ") ;
		$arr = mysql_fetch_array($res) ;
		$upd = array() ;
		$found = mysql_num_rows($res) ;
		if ( $found ) { // Card found in DB, update
			// Check if fields need updating
			foreach ( array('cost', 'types', 'text') as $f) {
				if ( $arr[$f] != $this->$f ) {
					$upd[$f] = $arr[$f] ; // Mark filed as updated, saving old value for returning
					$arr[$f] = $this->$f ; // Override current DB record copy for attrs compiling
				}
			}
			// Update if needed
			if ( $apply && count($upd) > 0 ) {
				$updates = array() ;
				foreach ( $upd as $field => $update ) {
					$updates[] = "`$field` = '".mysql_real_escape_string($this->$field)."'" ;
					if ( $field == 'text' ) // Compile text if changing
						$updates[] = "`attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."'" ;
				}
				$q = query("UPDATE `card` SET ".implode(', ', $updates)." WHERE `id` = '".$arr['id']."' ;") ;
			}
		} else {
			$arr = array( // Needed for attrs compiling
				'name' => $this->name,
				'cost' => $this->cost,
				'types' => $this->types,
				'text' => $this->text,
			) ;
			query("INSERT INTO `mtg`.`card`
			(`name` ,`cost` ,`types` ,`text`, `attrs`)
			VALUES ('".mysql_real_escape_string($this->name)."', '".$this->cost."', '".$this->types."', '".
			mysql_real_escape_string($this->text)."', '".mysql_escape_string(json_encode(new attrs($arr)))."');") ;
			//$card_id = mysql_insert_id($mysql_connection) ;
		}
		return array('card' => $this, 'found' => $found, 'updates' => $upd) ;
	}
}
?>
