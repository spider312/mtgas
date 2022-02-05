<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../lib.php' ;
// Cache management
function cache_get($url, $cache_file, $verbose = true, $noDownloadSmaller=false, $cache_life=43200/*12*3600*/) {
	$message = '' ;
	$content = '' ;
	clearstatcache() ;
	if ( $url == '' )
		$message .= '[empty url]' ;
	else if (
		file_exists($cache_file)
		&& (
			( $cache_life < 0 )
			|| ( time() - filemtime($cache_file) <= $cache_life )
		)
	) {
		$message .= '[use cache]' ;
		$content = @file_get_contents($cache_file) ;
	} else {
		$message .= '[update cache : ' ;
		if ( $noDownloadSmaller && file_exists($cache_file) && ( curl_get_file_size($url) <= filesize($cache_file) ) ) {
			$message .= 'cache file is already bigger' ;
			$content = @file_get_contents($cache_file) ;
		} else if ( ( $content = curl_download($url) ) !== false ) { // @file_get_contents($url)
			rmkdir(dirname($cache_file)) ;
			if ( ( $size = @file_put_contents($cache_file, $content) ) === false )
				$message .= 'not updatable ('.$cache_file.')' ;
			else
				$message .= 'updated ('.human_filesize($size).')' ;
			if ( strpos($url, 'http://www.magic-ville.com') !== false )
				time_nanosleep(0, 500000000) ; // Avoid anti-leech mechanism on MV (30 queries every 15 sec)
		} else
			$message .= 'not downloadable ('.$url.')' ;
		$message .= ']' ;
	}
	if ( $verbose )
		echo $message ;
	return $content ;
}
function curl_init_custom($url, $verbose = false) {
	global $curl;
	if ( isset($curl) ) {
		if ( $verbose )
			echo '[use curl instance]' ;
		curl_setopt($curl, CURLOPT_URL, $url) ;
	} else {
		if ( $verbose )
			echo '[create curl instance]' ;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'curl@mogg');
	}
	return $curl ;
}
function curl_get_file_size($url) {
	// Assume failure.
	$result = -1;
	$curl = curl_init_custom($url);
	// Issue a HEAD request and follow any redirects.
	curl_setopt($curl, CURLOPT_NOBODY, true);
	curl_setopt($curl, CURLOPT_HEADER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	$data = curl_exec($curl);
	//curl_close($curl);
	if( $data ) {
		if( preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches) ) {
			$status = (int)$matches[1];
			// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
			if ( ( $status == 200 || ($status > 300 && $status <= 308) )
			&& preg_match("/Content-Length: (\d+)/", $data, $matches) ) 
				$result = (int)$matches[1];
		}
	}
	curl_setopt($curl, CURLOPT_NOBODY, false);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
	return $result;
}
function curl_download($url) {
	$curl = curl_init_custom($url);
	$content = curl_exec($curl);
	$info = curl_getinfo($curl) ;
	if ( $info['http_code'] >= 400 )
		return false ;
	return $content ;
}
function rmkdir($dir) { // mkdir recursively without umask bug
	if ( file_exists($dir) )
		return true ;
	else {
		$oldumask = umask(0) ;
		$result = mkdir($dir, 0755, true) ;
		umask($oldumask) ;
		return $result ;
	}
}
// Common between MagicVille and MythicSpoiler
function mv2txt($tmp) {
	// $tmp was reformated by DOM parser + C14N()
	//echo htmlentities($tmp)."\n<hr>\n";
	// HTML (costs, CR) parsing before strip_tags
	$tmp = preg_replace('#<img .*?alt="%(.+?)".*?'.'>#', '{$1}', $tmp) ; // Images are mana icons
	$tmp = preg_replace('#<div style="height:5px;"></div>#', "\n", $tmp) ; // Div are cariage returns
	$tmp = preg_replace('@<br></br>(\S)@', "\n".'\1', $tmp) ; // 2 br not followed by a CR : add a CR
	$tmp = str_replace('&#xD;', '', $tmp) ; // \r cleanup
	$tmp = str_replace("\n\n", "\n", $tmp) ; // No need for 2 consecutive \n
	$tmp = str_replace(chr(194).chr(160), ' ', $tmp) ;
	$tmp = strip_tags($tmp) ; // Purify
	$tmp = trim($tmp) ; // Cleanup
	return $tmp ;
}
function mv2cost($tmp) {
	$cost = '' ;
	if ( isset($tmp) && preg_match_all('#<img  height=25 src=graph/manas/big/(?<mana>.{1,2})\.gif>#', $tmp, $matches_cost, PREG_SET_ORDER) > 0 ) {
		foreach ( $matches_cost as $match_cost ) {
			$mana = $match_cost['mana'] ;
			if ( ( strlen($mana) > 1 ) && !is_numeric($mana) ) { // Manage '2W' (hybrid mana), 'PW' (phyrexian manna) but not '15' (hight cost eldrazi)
				$splmana = str_split($mana) ;
				// ATM, in db and theme images, PM is stored as MP, and it doesn't have a /
				if ( $splmana[0] == 'P' ) {
					$glue = '' ;
					$splmana = array_reverse($splmana) ;
				} else
					$glue = '/' ;

				$cost .= '{'.implode($glue, $splmana).'}' ;
			} else
				$cost .= $mana ;
		}
	}
	return $cost ;
}
// Debug
function strdebug($str, $index=false) {
	//$arr = preg_split('/(?<!^)(?!$)/u', $str ); 
	//$arr = str_split($str) ;
	$arr = $str ;
	$indexes = '' ;
	$letters = '' ;
	$ords = '' ;
	for ( $i=0 ; $i < strlen($arr) ; $i++ ) {
		$indexes .= '<td>'.$i.'</td>' ;
		$letters .= '<td>'.$arr[$i].'</td>' ;
		$ords .= '<td>'.ord($arr[$i]).'</td>' ;
	}
	$result = '<table style="background-color: white">' ;
	if ( $index )
		$result .= "<tr>$indexes</tr>" ;
	$result .= "<tr>$letters</tr><tr>$ords</tr></table>\n" ;
	return $result ;
}
function string_detail_disp($str) {
	$result = '<pre>' ;
	for ( $i=0 ; $i < strlen($str) ; $i++ )
		$result .= '<span title="'.ord($str[$i]).'">'.$str[$i].'</span>' ;
	$result .= '</pre>' ;
	return $result ;
}
function tokenpath($token, $name='') {
	if ( $name == '' )
		$name = $token['type'] ;
	return $name.((($token['pow']!=='')||($token['tou']!==''))?'.'.$token['pow'].'.'.$token['tou']:'').'.jpg' ;
}
// Classes
class Importer {
	public $code = '' ;
	public $name = '' ;
	public $nbcards = 0 ;
	public $url = '' ;
	public $cards = array() ;
	public $tokens = array() ;
	public $errors = array() ;
	public $type = '' ;
	public $cachetime = -1 ;
	function __construct($type, $cachetime) {
		$this->type = $type ;
		$this->cachetime = $cachetime ;
	}
	function __destruct() {
	}
	// Extension related funcs
	function init($url) {
		$this->url = $url ;
	}
	function setext($code, $name, $cards) {
		$this->code = $code ;
		$this->dbcode = strtoupper($code) ;
		$this->name = $name ;
		$this->nbcards = intval($cards) ;
		echo 'Extension detected : <a href="'.$this->url.'" target="_blank">'."$code - $name</a>\n" ;
	}
	function adderror($err, $url) {
		$this->errors[$err][] = $url ;
		return false ;
	}
	// Card/token parsing
	function addcard($card_url, $rarity, $oldname, $cost, $types, $text, $url, $multiverseid='') {
		// Name checking
		$name = card_name_sanitize($oldname) ;
		$text = card_text_sanitize($text) ;
		$types = preg_replace('#\s+#', ' ', $types) ;
		$types = str_replace(chr(194).chr(160), ' ', $types) ;
		$cost = strtoupper($cost) ;
		if ( $name == '' )
			return $this->adderror('Empty name', $card_url) ;
		// Searching in already imported cards
		foreach ( $this->cards as $card ) {
			if ( ( $card->name == $name ) || ( $card->url === $card_url ) ) {
				// Inform if card already imported is not identical
				$differentData = false ;
				//$log = '' ;
				foreach ( array('rarity', 'name', 'cost', 'types', 'text'/*, 'multiverseid'*/) as $val ) {
					if ( trim($$val) != $card->{$val} ) {
						$differentData = true ;
						//$log .= ' - '.$val.' : '.$card->{$val}.' -> '.$$val."\n" ;
						//$card->{$val} = $$val ;
					}
				}
				if ( $differentData ) {
					$this->adderror('Card already parsed with different data', $card_url) ;
				}
				// Add image URL anyway (Unstable alternative pics)
				if ( ( $url !== null) && $card->addimage($url) ) {
					$card->nbimages++ ;
				}
				$card->addurl($card_url) ;
				return $card ;
			}
		}
		// Else it's a new card
		$card = new ImportCard($this, $card_url, $rarity, $name, $cost, $types, $text, $url, $multiverseid) ;
		$this->cards[] = $card ;
		return $card ;
	}
	function search($name) {
		foreach ( $this->cards as $card )
			if ( $card->name == $name )
				return $card ;
		return null ;
	}
	function addtoken($card_url, $type, $pow, $tou, $image_url) {
		$this->tokens[] = array('card_url' => $card_url, 'type' => $type, 'pow' => $pow, 'tou' => $tou, 'image_url' => $image_url) ;
	}
	// Importing
	function nbimages() {
		$nbcards = 0 ;
		foreach ( $this->cards as $card )
			if ( $card->name == 'Brothers Yamazaki' ) // 2 images but 1 "physical" card
				$nbcards++ ;
			else
				$nbcards += $card->nbimages ;
		return $nbcards ;
	}
	function validate() {
		// Errors
		if ( count($this->errors) > 0 ) {
			echo "Errors : \n" ;
			foreach ( $this->errors as $i => $error ) {
				echo ' - '.$i.' : '.count($error).' : ' ;
				foreach ( $error as $j => $url )
					if ( is_string($url) )
						echo '<a href="'.$url.'">'.$j.'</a> ' ;
					else
						echo '<a href="'.$url->url.'">'.$url->name.'</a> ' ;
				echo "\n" ;
			}
		} else
			echo "No errors during parsing\n" ;

		// Cards
		$nbcards = $this->nbimages() ;
		if ( $nbcards == $this->nbcards )
			echo 'Card number OK' ;
		else {
			if ( $this->nbcards == $nbcards + count($this->tokens) ) 
				echo 'Card number wrongly reported as card nb + token nb, accepted'."\n" ; // Magic-ville sometimes do that
			else {
				echo $nbcards.' card images found despite '.$this->nbcards.' expected'."\n" ;
				//return false ;
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
			$origext = $ext ; // Save for getting release date & bloc
			switch ( $this->type ) {
				case 'main' : // Classical set (bought in form of boosters
					$data = '{"c":10, "u":3, "r":1, "keywords": {}}' ;
					break ;
				case 'preview' : // Extension containing only rares & mythics with "land" rarity to add one rare like in previews
					$data = '{"l":1}' ;
					$ext .= 'P' ;
					$this->name .= ' - Previews' ;
					break ;
				case 'pwdecks' : // Other cards from extension not present in boosters (planeswalkers decks and other kind of products like that)
					$data = '{}' ;
					$ext .= 'PW' ;
					$this->name .= ' - Planeswalker Decks' ;
					break ;
				case 'all' : // Sets that does not follow this logic such as preconstructed
					$data = '{}' ;
					break ;
				default:
					die("Incorrect import type : ".$this->type) ;
			}
			$res = mysql_fetch_object(query("SELECT * FROM extension WHERE `se` = '$ext' ; ")) ; // First search in SE
			if ( ! $res ) // Take another chance with sea
				$res = mysql_fetch_object(query("SELECT * FROM extension WHERE `sea` = '$ext' ; ")) ;	
			if ( ! $res ) {
				$release_date = date('Y-m-d') ; // Let's make as if import is done on release day, it's better than 0000-00-00
				if ( $origext !== $ext ) { // Base other imports on main one
					if ( $origres = mysql_fetch_object(query("SELECT * FROM extension WHERE `se` = '$origext' ; ")) ) {
						$release_date = $origres->release_date ;
					}
				}
				echo 'Extension ['.$ext.'] not found'."\n" ;
				if ( $apply ) {
					$query = query("INSERT INTO extension (`se`, `name`, `release_date`, `data`) VALUE ('$ext', '".mysql_real_escape_string($this->name)."', '$release_date', '$data')") ;
					$ext_id = mysql_insert_id() ;
					echo 'Created'."\n" ;
				}
			} else {
				$ext_id = $res->id ;
				$code = $res->se ;
				$this->dbcode = strtoupper($code) ;
				echo 'Extension found : <a href="../extension.php?ext='.$code.'">'.$code.' - '.$res->name."</a>\n" ;
				if ( $apply) {
					query("DELETE FROM `card_ext` WHERE `ext` = '$ext_id'") ;
					echo mysql_affected_rows().' cards unlinked from '.$ext."\n" ;
				} else
					echo mysql_num_rows(query("SELECT * FROM `card_ext` WHERE `ext` = '$ext_id'")).' cards linked to extension'."\n" ;
			}
		}
		// Extension not found, stop import
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
				// Check which fields need updating
				$upd = array() ; // Field names for report
				$updates = array() ; // String query generation
				$card_obj->nbpics = $card_obj->nbimages ; // Workaround in order import card obj has the same name as in DB
				foreach ( array('rarity', 'nbpics', 'multiverseid') as $field ) {
					if ( $res->$field != $card_obj->$field ) {
						switch ( $field ) {
							/*case 'rarity' :
								if ( $res->$field == 'S' )
									continue 2 ;
								break ;*/
							case 'multiverseid' :
								if ( ( $card_obj->$field == '' )
									|| ( $card_obj->$field == '0' ) ) // Don't set multiverseID 0
									continue 2 ;
								break ;
						}
						$upd[$field] = $res->$field ; // Mark filed as updated, saving old value for returning
						$updates[] = "`$field` = '".mysql_real_escape_string($card_obj->$field)."'" ;
					}
				}
				if ( count($updates) == 0 ) {
					$card['action'] = 'up to date' ;
				} else {
					$card['updates'] = array_merge($upd, $card['updates']) ;
					if ( $apply ) { // Actually, we do never enter that, as when $apply, links are all deleted at the begining of import
						query("UPDATE card_ext SET ".implode(', ', $updates)." WHERE `card` = $card_id AND `ext` = $ext_id ;") ;
						$card['action'] = 'updated' ;
					} else
						$card['action'] = 'to update' ;
				}
			} else {
				if ( $apply ) {
					query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`, `multiverseid`)
						VALUES ('$card_id', '$ext_id', '".$card_obj->rarity."',
						'".$card_obj->nbimages."', '".$card_obj->multiverseid."') ;") ;
					$card['action'] = 'inserted' ;
				} else
					$card['action'] = 'to insert' ;
			}
			// Languages
			$card['langs'] = array() ;
			foreach ( $card_obj->langs as $code => $lang ) {
				if ( array_key_exists('name', $lang) )
					$localname = $lang['name'] ;
				else
					continue ; // Un-expected charsets
				$lang = array() ;
				$lang['to'] = $localname ;
				$query = query("SELECT *
					FROM `cardname`
					WHERE `card_id` = '$card_id' AND `lang` = '$code' ;") ;
				if ( $res = mysql_fetch_object($query) ) {
					if ( $res->card_name != $localname ) {
						$lang['action'] = 'update' ;
						$lang['from'] = $res->card_name ;
						if ( $apply ) {
							$query = query("UPDATE `mtg`.`cardname`
							SET `card_name` = '".mysql_real_escape_string($localname)."'
							WHERE `cardname`.`card_id` =$card_id AND `cardname`.`lang` = '$code';") ;
							if ( ! $query )
								die('Lang not inserted') ;
						}
					} else {
						$lang['action'] = 'none' ;
					}
				} else {
					$lang['action'] = 'insert' ;
					if ( $apply ) {
						$query = query("INSERT INTO `mtg`.`cardname`
							(`card_id`, `lang` ,`card_name`) VALUES
							('$card_id', '$code', '".mysql_real_escape_string($localname)."');") ;
						if ( ! $query )
							die('Lang not inserted') ;
					}
				}
				$card['langs'][$code] = $lang ;
			}
			$result[] = $card ;
		}
		echo "\n" ;
		return $result ;
	}
	function download() {
		// Dirs
		$begin = microtime(true) ;
		$verbose = true ;
		$update = true ;
		global $base_image_dir ;
		$dir = $base_image_dir.'HIRES/'.$this->dbcode.'/' ;
		if ( ! rmkdir($dir) )
			return false ;
		$tkdir = $base_image_dir.'HIRES/TK/'.$this->dbcode.'/' ;
		if ( ! rmkdir($tkdir) )
			return false ;
		$oldumask = umask(022) ;
		// Card images
		echo count($this->cards).' cards to download to '.$dir."\n" ;
		foreach ( $this->cards as $card ) {
			echo $card->name.' : ' ;
			if ( $card->secondname != '' ) {
				$nbi = count($card->images) ;
				if ( $nbi == 2 ) {
					$path = $dir.card_img_by_name($card->name, 1, 1) ;
					cache_get($card->images[0], $path, $verbose, $update, $this->cachetime) ;
					$path = $dir.card_img_by_name($card->secondname, 1, 1) ;
					cache_get($card->images[1], $path, $verbose, $update, $this->cachetime) ;
				} else if ( $nbi > 2 ) {
					$i = 0 ;
					$irecto = 0 ;
					while ( $i < $nbi ) {
						$i++ ; $i++ ;
						$irecto++ ;
						$path = $dir.card_img_by_name($card->name, $irecto, $nbi) ;
						cache_get($card->images[0], $path, $verbose, $update, $this->cachetime) ;
						$path = $dir.card_img_by_name($card->secondname, $irecto, $nbi) ;
						cache_get($card->images[1], $path, $verbose, $update, $this->cachetime) ;
					}
				} else 
					die('2 images expected for tranfsorm') ;
				echo "\n" ;
				// Languages
				foreach ( $card->langs as $lang => $images ) {
					$nbimages = count($images['images']) ;
					/*if ( $nbimages !== 2 ) {
						echo "$nbimages instead of 2 expected" ;
						continue ;
					}*/
					$langdir = $base_image_dir.strtoupper($lang).'/'.$this->dbcode.'/' ;
					echo " - $lang : " ;
					$path = $langdir.card_img_by_name($card->name, 1, 1) ;
					$image = $images['images'][0] ;
					cache_get($image, $path, $verbose, $update, $this->cachetime) ;
					$path = $langdir.card_img_by_name($card->secondname, 1, 1) ;
					$image = $images['images'][1] ;
					cache_get($image, $path, $verbose, $update, $this->cachetime) ;
					echo "\n" ;
				}
			} else {
				foreach ( $card->images as $i => $image ) {
					$path = $dir.card_img_by_name($card->name, $i+1, count($card->images)) ;
					cache_get($image, $path, $verbose, $update, $this->cachetime) ;
				}
				echo "\n" ;
				// Languages
				foreach ( $card->langs as $lang => $images ) {
					if ( !array_key_exists('images', $images) || (count($images['images']) < 1 ) ) {
						echo "No card images for language $lang\n" ;
						continue ;
					}
					echo " - $lang : " ;
					$langdir = $base_image_dir.strtoupper($lang).'/'.$this->dbcode.'/' ;
					foreach ( $images['images'] as $i => $image ) {
						$path = $langdir.card_img_by_name($card->name, $i+1, count($card->images)) ;
						cache_get($image, $path, $verbose, $update, $this->cachetime) ;
					}
					echo "\n" ;
				}
			}
		}
		// Token images
		echo "\n".count($this->tokens).' tokens to download to '.$tkdir."\n" ;
		foreach ( $this->tokens as $token ) {
			echo $token['type'].' : ' ;
			$name = $token['type'] ;
			// Manage multiple tokens with the same name
			$same = array_filter($this->tokens, function($tk) use ($token) { return tokenpath($tk) == tokenpath($token) ; }) ;
			if ( count($same) > 1 ) {
				if ( ! isset($multiple) )        $multiple = array() ;
				if ( ! isset($multiple[$name]) ) $multiple[$name] = 1 ;
				else                             $multiple[$name]++ ;
				$name .= $multiple[$name] ;
			}
			// Token is an emblem
			if ( preg_match('/Emblem (.*)/', $name, $matches) ) {
				$emblemname = trim($matches[1]) ; // May be just the type or the full name
				$found = false ;
				// Searching for card/face in order to determine emblem name
				foreach ( $this->cards as $card ) { // Search which planeswalker it is for
					$attrs = $card->attrs() ;
					// Search in recto
					if (
						( strtolower($emblemname) === strtolower($card->name) ) // Search in name
						|| ( isset($attrs->subtypes) && ( count($attrs->subtypes) > 0 ) && ( $attrs->subtypes[0] == strtolower($emblemname) ) ) // Search in subtype
					) {
						$found = true ;
						$name = 'Emblem.'.$attrs->subtypes[0] ;
						break ;
					}
					// Search in verso
					if (
						property_exists($attrs, 'transformed_attrs')
						// As final emblem name is planeswalker subtype, only check versos with a subtype (e.g. don't check sorcery versos)
						&& ( property_exists($attrs->transformed_attrs, 'subtypes') ) 
						&& ( count($attrs->transformed_attrs->subtypes) > 0 )
					) {
						if (
							( strtolower($attrs->transformed_attrs->name) == strtolower($emblemname) ) // Search in name
							|| ( $attrs->transformed_attrs->subtypes[0] == strtolower($emblemname) ) // Search in subtype
						 ) {
							$found = true ;
							$name = 'Emblem.'.$attrs->transformed_attrs->subtypes[0] ;
							break ;
						}
					}
				}
				if ( !$found ) { // No planeswalker found, don't DL
					echo "Planeswalker not found for emblem \"$emblemname\"\n" ;
					continue ;
				}
			}
			cache_get($token['image_url'], $tkdir.tokenpath($token, $name), $verbose, $update, $this->cachetime) ;
			echo "\n" ;
		}
		umask($oldumask) ;
		echo "\n".'Finished in '.(microtime(true)-$begin).' (think about thumbnailing)' ;
		return true ;
	}
}

class ImportCard {
	public $ext = null ;
	public $url = 'Uninitialized' ;
	public $urls = array() ;
	public $rarity = 'N' ;
	public $name = 'Uninitialized' ;
	public $cost = 'Uninitialized' ;
	public $types = 'Uninitialized' ;
	public $text = 'Uninitialized' ;
	public $multiverseid = -1 ;
	public $nbimages = 0 ; // Different images in current extension
	public $images = array() ; // Each image in current extension
	public $langs = array() ;
	public $secondname = '' ; // Second image name for transform
	public $seconded = false ;
	function __construct($ext, $card_url, $rarity, $name, $cost, $types, $text, $url, $multiverseid=0) {
		$this->ext = $ext ;
		$this->url = $card_url ;
		$this->urls[] = $card_url ;
		$this->rarity = $rarity ;
		$this->name = $name ;
		$this->cost = $cost ;
		$this->types = $types ;
		$this->text = trim($text) ;
		if ( $url !== null ) {
			$this->nbimages = 1 ;
			$this->addimage($url) ;
		}
		$this->multiverseid = intval($multiverseid) ;
	}
	// Dual
	function split($name, $cost, $types, $text) {
		if ( $this->seconded ) {
			return;
		} else {
			$this->seconded = true ;
		}
		$pos = strpos($this->name, $name) ;
		if ( $pos !== false ) {
			$this->ext->adderror('Split : Trying to re-add second face', $this) ;
			return false ;
		}
		$this->name .= ' / '.$name ;
		$this->addtext("----\n$cost\n$types\n$text") ;
	}
	function flip($name, $types, $text) {
		if ( $this->seconded ) {
			return;
		} else {
			$this->seconded = true ;
		}
		$this->addtext("----\n$name\n$types\n$text") ;
	}
	function transform($name, $ci, $types, $text, $url) {
		$this->addimage($url) ;
		if ( $this->seconded ) {
			return;
		} else {
			$this->seconded = true ;
		}
		$this->secondname = $name ;
		$this->addtext("-----\n$name\n%$ci $types\n$text") ;
	}
	function addtext($add) {
		$this->text .= "\n".card_text_sanitize($add) ;
	}
	// Linked data
	function addurl($url) {
		foreach ( $this->urls as $card_url )
			if ( $url == $card_url )
				return $this->ext->adderror('URL already added', $this) ;
		$this->urls[] = $url ;
		return true ;

	}
	function addimage($url) { // For double faced cards
		foreach ( $this->images as $image )
			if ( $url == $image )
				return $this->ext->adderror('Image already added', $this) ; // Triggered in MV for coloured artifacts, as they are in art + color
		$this->images[] = $url ;
		return true ;
	}
	function setlang($code, $name, $url=null) { // Add language data for current card, overwriting all data for that card/lang
		$name = ucfirst($name) ;
		if ( ! isset($this->langs[$code]) )
			$this->langs[$code] = array() ;
		$this->langs[$code]['name'] = $name ;
		$this->addlangimg($code, $url) ;
	}
	function addlang($code, $name, $url=null) { // Add data to current language data (name append for dual cards, URL image for cards with multiple images)
		$name = ucfirst($name) ;
		if ( isset($this->langs[$code]) ) {
			if ( $name != $this->langs[$code]['name'] )
				$this->langs[$code]['name'] .= ' / '.$name ;
			$this->addlangimg($code, $url) ;
		} else
			$this->setlang($code, $name, $url) ;
	}
	function addlangimg($code, $url=null) { // Add language image for current card, overwriting all data for that card/lang
		if ( ( $url == null ) || ( $url == '' ) ) {
			return false ;
		}
		if ( $code == 'en' ) { // Some splits on MCI
			return $this->addimage($url) ;
		}
		if ( isset($this->langs[$code]['images']) ) {
			$langimages = $this->langs[$code]['images'] ;
		} else {
			$langimages =array() ;
			$this->langs[$code]['images'] = $langimages ;
		}
		foreach ( $langimages as $img_url )
			if ( $url == $img_url )
				return $this->ext->adderror('Language image already added', $this) ;
		$this->langs[$code]['images'][] = $url ;
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
			foreach ( array('name', 'cost', 'types', 'text') as $f) {
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
				VALUES ('".mysql_real_escape_string($this->name)."', '".$this->cost."', '".mysql_real_escape_string($this->types)."', '".
				mysql_real_escape_string($this->text)."', '".mysql_escape_string($this->json_attrs())."');") ;
				$card_id = mysql_insert_id($mysql_connection) ; // Returned for linking
			} else
				$card_id = -1 ;
		}
		return array('id' => $card_id, 'card' => $this, 'found' => $found, 'updates' => $upd) ;
	}
}
?>
