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
// Debug
function string_detail($str) {
	$result = '' ;
	for ( $i=0 ; $i < strlen($str) ; $i++ )
		$result .= '['.$str[$i].'] : '.ord($str[$i])."\n" ;
	return $result ;
}
function string_detail_disp($str) {
	$result = '<pre>' ;
	for ( $i=0 ; $i < strlen($str) ; $i++ )
		$result .= '<span title="'.ord($str[$i]).'">'.$str[$i].'</span>' ;
	$result .= '</pre>' ;
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
		$name = card_name_sanitize($name) ;
		$text = card_text_sanitize($text) ;
		$nbimages = 1 ;
		foreach ( $this->cards as $card )
			if ( $card->name == $name ) {
				if ( ( $rarity == $card->rarity ) && ( $name == $card->name ) && ( $cost == $card->cost ) &&
					 ( $types == $card->types ) && ( $text == $card->text ) )
					$card->addimage($url) ;
				else {
					echo 'Card already parsed with different data : '.$name.'<br>' ;
					foreach ( array('rarity', 'name', 'cost', 'types', 'text') as $val ) {
						if ( $$val != $card->{$val} )
							echo ' - '.$val.' : '.$card->{$val}.' -> '.$$val."<br>" ;
					}
				}
				return $card ;
			}
		$card = new ImportCard($rarity, $name, $cost, $types, $text, $url) ;
		$this->cards[] = $card ;
		return $card ;
	}
	function addtoken($type, $pow, $tou, $url) {
		$this->tokens[] = array($type, $pow, $tou, $url) ;
	}
	function import($ext='', $apply=false) {
		$result = array() ;
		// Extension in DB
		$ext = strtoupper($ext) ;
		$res = mysql_fetch_object(query("SELECT * FROM extension WHERE `se` = '$ext' ; ")) ; // First search in SE
		if ( ! $res ) // Take another chance with sea
			$res = mysql_fetch_object(query("SELECT * FROM extension WHERE `sea` = '$ext' ; ")) ;	
		if ( ! $res ) {
			echo 'Extension '.$ext.' not found' ;
			if ( $apply ) {
				echo 'Would create it' ;
				/*
				$query = query("INSERT INTO extension (`se`, `name`) VALUES ('$ext', '".mysql_real_escape_string($matches[0]['ext'])."')") ;
				echo '<p>Extension not existing, creating</p>' ;
				$ext_id = mysql_insert_id() ;
				*/
			}
		} else {
			$ext_id = $res->id ;
			echo 'Extension found : '.$ext_id.' : '.$res->name ;
			if ( $apply) {
				echo 'Would delete cards' ;
				/*
				query("DELETE FROM `card_ext` WHERE `ext` = '$ext_id'") ;
				echo '  <p>'.mysql_affected_rows().' cards unlinked from '.$ext."</p>\n\n" ;
				*/
			}
		}
		// Cards
		foreach ( $this->cards as $card ) {
			$card = $card->import($apply) ;
			$result[] = $card ;
			if ( $apply ) {
			}
		}
		return $result ;
	}
}
class ImportCard {
	public $rarity = 'N' ;
	public $name = 'Uninitialized' ;
	public $cost = 'Uninitialized' ;
	public $types = 'Uninitialized' ;
	public $text = 'Uninitialized' ;
	public $nbimages = 0 ; // Different images in current extension
	public $images = array() ; // Each image in current extension
	public $langs = array() ;
	function __construct($rarity, $name, $cost, $types, $text, $url) {
		$this->rarity = $rarity ;
		$this->name = $name ;
		$this->cost = $cost ;
		$this->types = $types ;
		$this->text = $text ;
		$nbimages = 1 ;
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
		$upd = array() ; // Return a list of updates if needed
		$found = mysql_num_rows($res) ;
		if ( $found ) { // Card found in DB, update
			$card_id = $arr['id'] ; // Return for linking
			// Check which fields need updating
			foreach ( array('cost', 'types', 'text') as $f) {
				if ( $arr[$f] != $this->$f ) {
					$upd[$f] = $arr[$f] ; // Mark filed as updated, saving old value for returning
					$arr[$f] = $this->$f ; // Override current DB record copy for attrs compiling
				}
			}
			// Update if needed
			if ( $apply && count($upd) > 0 ) {
				$updates = array() ; // String query generation
				foreach ( $upd as $field => $update ) {
					$updates[] = "`$field` = '".mysql_real_escape_string($this->$field)."'" ;
					if ( $field == 'text' ) // Compile text if changing
						$updates[] = "`attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."'" ;
				}
				query("UPDATE `card` SET ".implode(', ', $updates)." WHERE `id` = '".$arr['id']."' ;") ;
			}
		} else { // Card not found in DB, insert
			if ( $apply ) {
				$arr = array('name' => $this->name, 'cost' => $this->cost, // Needed for attrs
					'types' => $this->types, 'text' => $this->text) ;
				query("INSERT INTO `mtg`.`card` (`name` ,`cost` ,`types` ,`text`, `attrs`)
				VALUES ('".mysql_real_escape_string($this->name)."', '".$this->cost."', '".$this->types."', '".
				mysql_real_escape_string($this->text)."', '".mysql_escape_string(json_encode(new attrs($arr)))."');") ;
				$card_id = mysql_insert_id($mysql_connection) ; // Returned for linking
			} else
				$card_id = -1 ;
		}
		return array('id' => $card_id, 'card' => $this, 'found' => $found, 'updates' => $upd) ;
	}
}
?>
