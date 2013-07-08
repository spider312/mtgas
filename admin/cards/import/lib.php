<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../lib.php' ;
$homedir = substr(`bash -c "echo ~"`, 0, -1) ;
$base_image_dir = $homedir.'/img/' ;

// Cache management
function cache_get($url, $cache_file, $verbose = true, $cache_life=3600) {
	$message = '' ;
	clearstatcache() ;
	if ( file_exists($cache_file) && ( time() - filemtime($cache_file) <= $cache_life ) ) {
		$message .= '[use cache]' ;
		$content = @file_get_contents($cache_file) ;
	} else {
		$message .= '[update cache : ' ;
		if ( ( $content = @file_get_contents($url) ) !== FALSE ) {
			if ( $content === false )
				$message .= 'not downloadable ('.$url.')' ;
			elseif ( ( $size = @file_put_contents($cache_file, $content) ) === FALSE )
				$message .= 'not updatable ('.$cache_file.')' ;
			else
				$message .= 'updated ('.human_filesize($size).')' ;
		}
		$message .= ']' ;
	}
	if ( $verbose )
		echo $message ;
	/*if ( $content === false )
		die('[no content : '.$url.' -> '.$cache_file.']') ;*/
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
	public $errors = array() ;
	function __construct() {
	}
	function __destruct() {
	}
	// Extensioçn related funcs
	function init($url) {
		$this->url = $url ;
	}
	function setext($code, $name, $cards) {
		$this->code = $code ;
		$this->dbcode = $code ;
		$this->name = $name ;
		$this->nbcards = intval($cards) ;
		echo 'Extension detected : <a href="'.$this->url.'" target="_blank">'."$code - $name</a>\n" ;
	}
	// Card/token parsing
	function addcard($card_url, $rarity, $name, $cost, $types, $text, $url, $multiverseid='') {
		// Name checking
		$name = card_name_sanitize($name) ;
		if ( $name == '' ) {
			$this->errors['Empty name'][] = $card_url ;
			return null ;
		}
		foreach ( $this->cards as $card )
			if ( $card->name == $name ) {
				$log = '' ;
				foreach ( array('rarity', 'name', 'cost', 'types', 'text'/*, 'multiverseid'*/) as $val )
					if ( $$val != $card->{$val} )
						$log .= ' - '.$val.' : '.$card->{$val}.' -> '.$$val."\n" ;
				if ( $log != '' ) // Shouldn't happen
					echo 'Card already parsed with different data : '.$name."\n".$log."\n" ;
				if ( $card->addimage($url) )
					$card->nbimages++ ;
				return $card ;
			}
		$text = card_text_sanitize($text) ;
		$types = preg_replace('#\s+#', ' ', $types) ;
		$card = new ImportCard($card_url, $rarity, $name, $cost, $types, $text, $url, $multiverseid) ;
		$this->cards[] = $card ;
		return $card ;
	}
	function addtoken($card_url, $type, $pow, $tou, $image_url) {
		$this->tokens[] = array('card_url' => $card_url, 'type' => $type, 'pow' => $pow, 'tou' => $tou, 'image_url' => $image_url) ;
	}
	// Importing
	function validate() {
		// Errors
		if ( count($this->errors) > 0 ) {
			echo "Errors : \n" ;
			foreach ( $this->errors as $i => $error )
				echo ' - '.$i.' : '.count($error)."\n" ;
		} else
			echo "No errors during parsing\n" ;

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
			if ( $this->nbcards == $nbcards + count($this->tokens) ) 
				echo 'Card number wrongly reported as card nb + token nb, accepted'."\n" ; // Magic-ville sometimes do that
			else {
				echo $nbcards.' card images found despite '.$this->nbcards.' expected'."\n" ;
				return false ;
			}
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
						 ('$ext', '".mysql_real_escape_string($this->name)."')") ;
					$ext_id = mysql_insert_id() ;
					echo 'Created'."\n" ;
				}
			} else {
				$ext_id = $res->id ;
				$code = $res->se ;
				$this->dbcode = $code ;
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
	function download() {
		// Dirs
		global $homedir, $base_image_dir ;
		$dir = $base_image_dir.'HIRES/'.$this->dbcode.'/' ;
		if ( ! rmkdir($dir) )
			return false ;
		$tkdir = $base_image_dir.'HIRES/TK/'.$this->dbcode.'/' ;
		if ( ! rmkdir($tkdir) )
			return false ;
		$oldumask = umask(022) ;
		// Card images
		echo count($this->cards).' cards to download'."\n" ;
		foreach ( $this->cards as $card ) {
			echo $card->name.' : ' ;
			foreach ( $card->images as $i => $image ) {
				$path = $dir.$card->name.((count($card->images) > 1)?($i+1):'').'.full.jpg' ;
				cache_get($image, $path, true) ;
			}
			echo "\n" ;
		}
		// Token images
		echo "\n".count($this->tokens).' tokens to download'."\n" ;
		foreach ( $this->tokens as $token ) {
			echo $token['type'].' : ' ;
			$name = $token['type'] ;
			if ( preg_match('/Emblem (.*)/', $name, $matches) ) // Token is an emblem
				foreach ( $this->cards as $card ) // Search which planeswalker it is for
					if ( $card->name == $matches[1] ) {
						$attrs = $card->attrs() ;
						$name = 'Emblem.'.$attrs->subtypes[0] ;
					}
			$path = $tkdir.$name.((($token['pow']!='')||($token['tou']!=''))?'.'.$token['pow'].'.'.$token['tou']:'').'.jpg' ;
			echo $path ;
			cache_get($token['image_url'], $path, true) ;
			echo "\n" ;
		}
		// Thumbnailing
		/*
		$oldumask = umask(0022) ;
		shell_exec($homedir.'/bin/thumb '.$this->dbcode) ;
		shell_exec($homedir.'/bin/thumb TK/'.$this->dbcode) ;
		umask($oldumask) ;
		*/
		umask($oldumask) ;
		echo 'Finished (think about thumbnailing)' ;
		return true ;
	}
}
function rmkdir($dir) {
	if ( file_exists($dir) )
		return true ;
	else {
		$oldumask = umask(0) ;
		$result = mkdir($dir, 0755, true) ;
		umask($oldumask) ;
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
				echo 'Image already added for '.$this->name."\n" ; // Triggered in MV for coloured artifacts, as they are in art + color
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
	function attrs() {
		$arr = array('name' => $this->name, 'cost' => $this->cost, // Needed for attrs
			'types' => $this->types, 'text' => $this->text) ;
		return new attrs($arr) ;
	}
	function json_attrs() {
		return json_encode($this->attrs()) ;
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
						$updates[] = "`attrs` = '".mysql_escape_string($this->json_attrs())."'" ;
				}
				query("UPDATE `card` SET ".implode(', ', $updates)." WHERE `id` = '".$arr['id']."' ;") ;
			}
		} else { // Card not found in DB, insert
			if ( $apply ) {
				query("INSERT INTO `mtg`.`card` (`name` ,`cost` ,`types` ,`text`, `attrs`)
				VALUES ('".mysql_real_escape_string($this->name)."', '".$this->cost."', '".$this->types."', '".
				mysql_real_escape_string($this->text)."', '".mysql_escape_string($this->json_attrs())."');") ;
				$card_id = mysql_insert_id($mysql_connection) ; // Returned for linking
			} else
				$card_id = -1 ;
		}
		return array('id' => $card_id, 'card' => $this, 'found' => $found, 'updates' => $upd) ;
	}
}
?>
