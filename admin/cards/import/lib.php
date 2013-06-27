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
	public $code = '' ;
	public $name = '' ;
	public $nbcards = 0 ;
	public $url = '' ;
	public $cards = array() ;
	public $tokens = array() ;
	function __construct() {
	}
	function __destruct() {
	}
	function init($url) {
		$this->url = $url ;
	}
	function setext($code, $name, $cards) {
		$this->code = $code ;
		$this->name = $name ;
		$this->nbcards = intval($cards) ;
		echo 'Extension detected : <a href="'.$this->url.'" target="_blank">'."$code - $name</a>\n" ;
	}
	function addcard($card_url, $rarity, $name, $cost, $types, $text, $url, $multiverseid='') {
		$name = card_name_sanitize($name) ;
		$text = card_text_sanitize($text) ;
		foreach ( $this->cards as $card )
			if ( $card->name == $name ) {
				$log = '' ;
				foreach ( array('rarity', 'name', 'cost', 'types', 'text'/*, 'multiverseid'*/) as $val )
					if ( $$val != $card->{$val} )
						$log .= ' - '.$val.' : '.$card->{$val}.' -> '.$$val."\n" ;
				if ( $log != '' ) // Shouldn't happen
					echo 'Card already parsed with different data : '.$name."\n".$log."\n" ;
				$card->nbimages++ ;
				$card->addimage($url) ;
				return $card ;
			}
		$card = new ImportCard($card_url, $rarity, $name, $cost, $types, $text, $url, $multiverseid) ;
		$this->cards[] = $card ;
		return $card ;
	}
	function addtoken($type, $pow, $tou, $url) {
		$this->tokens[] = array($type, $pow, $tou, $url) ;
	}
	function validate() {
		// Cards
		$nbcards = 0 ;
		foreach ( $this->cards as $card )
			if ( $card->name == 'Brothers Yamazaki' ) // 2 images but 1 "physical" card
				$nbcards++ ;
			else
				$nbcards += $card->nbimages ;
		if ( $nbcards == $this->nbcards )
			echo 'Card number OK' ;
		else {
			echo $nbcards.' card images found despite '.$this->nbcards.' expected'."\n" ;
			return false ;
		}
		return true ;
	}
	function import($ext='', $apply=false) {
		$ext = strtoupper($ext) ;
		$result = array() ;
		$ext_id = 0 ;
		// Extension in DB
		if ( $ext == '' ) 
			echo 'No ext given'."\n" ;
		else {
			$ext = strtoupper($ext) ;
			$res = mysql_fetch_object(query("SELECT * FROM extension WHERE `se` = '$ext' ; ")) ; // First search in SE
			if ( ! $res ) // Take another chance with sea
				$res = mysql_fetch_object(query("SELECT * FROM extension WHERE `sea` = '$ext' ; ")) ;	
			if ( ! $res ) {
				echo 'Extension ['.$ext.'] not found'."\n" ;
				if ( $apply ) {
					$query = query("INSERT INTO extension (`se`, `name`) VALUE
						 ('$ext', '".mysql_real_escape_string($matches[0]['ext'])."')") ;
					$ext_id = mysql_insert_id() ;
					echo 'Created'."\n" ;
				}
			} else {
				$ext_id = $res->id ;
				$code = $res->se ;
				if ( $code != $res->sea )
					$code .= '/'.$res->sea ;
				echo 'Extension found : '.$code.' - '.$res->name."\n" ;
				echo mysql_num_rows(query("SELECT * FROM `card_ext` WHERE `ext` = '$ext_id'")).' cards linked to extension'."\n" ;
				if ( $apply) {
					query("DELETE FROM `card_ext` WHERE `ext` = '$ext_id'") ;
					echo mysql_affected_rows().' cards unlinked from '.$ext."\n" ;
				}
			}
		}
		if ( ( intval($ext_id) == 0 ) && $apply ) {
			$apply = false ;
			echo "Changes won't be applied\n" ;
		}
		// Cards
		foreach ( $this->cards as $card ) {
			$card = $card->import($apply) ;
			$card['action'] = 'nothing' ;
			$card_id = $card['id'] ;
			$card_obj = $card['card'] ;
			$query = query("SELECT * FROM card_ext WHERE `card` = '$card_id' AND `ext` = '$ext_id' ;") ;
			if ( $res = mysql_fetch_object($query) ) {
				if ( $apply ) {
					query("UPDATE card_ext SET
						`rarity` = '".$card_obj->rarity."', `nbpics` = '".$card_obj->nbimages."',
						`multiverseid` = '".$card_obj->multiverseid."'
						WHERE `card` = $card_id AND `ext` = $ext_id ;") ;
					$card['action'] = 'updated' ;
				} else
					$card['action'] = 'to update' ;
			} else {
				if ( $apply ) {
					query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`, `multiverseid`)
						VALUES ('$card_id', '$ext_id', '".$card_obj->rarity."',
						'".$card_obj->nbimages."', '".$card_obj->multiverseid."') ;") ;
					$card['action'] = 'inserted' ;
				}else
					$card['action'] = 'to insert' ;
			}
			$result[] = $card ;
		}
		return $result ;
	}
}
class ImportCard {
	public $url = 'Uninitialized' ;
	public $rarity = 'N' ;
	public $name = 'Uninitialized' ;
	public $cost = 'Uninitialized' ;
	public $types = 'Uninitialized' ;
	public $text = 'Uninitialized' ;
	public $multiverseid = -1 ;
	public $nbimages = 0 ; // Different images in current extension
	public $images = array() ; // Each image in current extension
	public $langs = array() ;
	function __construct($card_url, $rarity, $name, $cost, $types, $text, $url, $multiverseid=0) {
		$this->url = $card_url ;
		$this->rarity = $rarity ;
		$this->name = $name ;
		$this->cost = $cost ;
		$this->types = $types ;
		$this->text = $text ;
		$this->nbimages = 1 ;
		$this->addimage($url) ;
		$this->multiverseid = intval($multiverseid) ;
	}
	// Dual
	function split($name, $cost, $types, $text) {
		$this->name .= ' / '.$name ;
		$this->addtext("----\n$cost\n$types\n$text") ;
	}
	function flip($name, $types, $text) {
		$this->addtext("----\n$name\n$types\n$text") ;
	}
	function transform($name, $ci, $types, $text, $url) {
		$this->addtext("-----\n$name\n%$ci $types\n$text") ;
		$this->addimage($url) ;
	}
	// Linked data
	function addimage($url) { // For double faced cards
		foreach ( $this->images as $image )
			if ( $url == $image ) {
				echo "Image already added for ".$this->name ;
				return false ;
			}
		$this->images[] = $url ;
		return true ;
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
	// Import
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
