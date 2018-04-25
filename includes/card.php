<?php
// === [ DATABASE ] ============================================================
function card_search($get, $connec=null) {
	$data = new stdClass() ;
	if ( isset($get['page']) ) {
		$data->page = intval($get['page']) ;
		unset($get['page']) ;
	} else
		$data->page = 1 ;
	if ( isset($get['limit']) ) {
		$data->limit = intval($get['limit']) ;
		unset($get['limit']) ;
	} else
		$data->limit = 30 ;
	$firstresult = ($data->page-1)*$data->limit ;
	$get['name'] = card_name_sanitize($get['name']) ;
	// Start to build query
	$select = '`card`.*' ;
	$from = '`card`' ;
	$where = '' ;
	$order = '`card`.`name`' ;
	// Special fields that modify query instead of just beeing searched
	if ( isset($get['ext']) && ( $get['ext'] != '' ) ) {
		$select .= ', `extension`.`se`' ;
		$from .= 'INNER JOIN `card_ext` ON `card`.`id` = `card_ext`.`card` INNER JOIN `extension` ON `card_ext`.`ext` = `extension`.`id`' ;
		$where .= ' AND ( `extension`.`se` LIKE \'%'.$get['ext'].'%\' OR `extension`.`sea` LIKE \'%'.$get['ext'].'%\' ) AND ( `card_ext`.`nbpics` > 0 )' ;
		unset($get['ext']) ;
	}
	if ( isset($get['lang']) ) {
		if ( $get['lang'] != 'en' ) {
			$data->lang = $get['lang'] ;
			$select .= ', `cardname`.`card_name` ' ;
			$from .= ' LEFT JOIN `cardname` ON `card`.`id` = `cardname`.`card_id`' ;
			$where .= ' AND `cardname`.`lang` = "'.$get['lang'].'"' ;
			$where .= ' AND `cardname`.`card_name` LIKE "%'.$get['name'].'%"' ;
			unset($get['name']) ; // Don't search that name in card table, it has been searched in lang table
			$order = '`cardname`.`card_name`' ;
		}
		unset($get['lang']) ;
	}
	// Normal fields
	$where .= get2where($get, 'LIKE', '%', '%', 'card') ;
	// Parts acquired, merge and query
	$query = "SELECT $select FROM $from WHERE 1 $where ORDER BY $order";
	$result = query($query, 'Card listing', $connec) ;
	$data->num_rows = mysql_num_rows($result) ;
	$data->cards = array() ;
	while ( ( $firstresult > 0 ) && ( $obj = mysql_fetch_object($result) ) )
		$firstresult-- ;
	while ( ( $obj = mysql_fetch_object($result) ) && ( count($data->cards) < $data->limit ) ) {
		$query = "SELECT extension.id, extension.se, extension.name, card_ext.nbpics
				FROM card_ext, extension
				WHERE
					card_ext.nbpics != 0 AND
					card_ext.card = '".$obj->id."' AND
					card_ext.ext = extension.id
				ORDER BY extension.priority DESC" ;
		$result2 = query($query, 'Card\' extension', $connec) ;
		$obj->ext = array() ;
		while ( $obj_ext = mysql_fetch_object($result2) )
			$obj->ext[] = $obj_ext ;
		$data->cards[] = $obj ;
	}
	return $data ;
}
function get2where($get, $comp, $prefix, $suffix, $table='') {
	$where = '' ;
	foreach ( $get as $i => $val ) {
		if ( ( $i != 'submit' ) && ( $val != '' ) ) {
			$where .= 'AND ' ;
			if ( $table != '' )
				$where .= '`'.$table.'`.' ;
			$where .= '`'.$i . '` '.$comp.' \''.$prefix . mysql_real_escape_string($val) . $suffix . '\' ' ;
		}
	}
	return $where ;
}
// === [ LIB ] =================================================================
function card_name_sanitize($name) {
	// Base
	$name = trim($name) ;
	$name = html_entity_decode($name) ;
	// Non working global tryouts
	//$name = iconv('UTF-8', 'US-ASCII//TRANSLIT', $name) ;
	//$name =  normalizer_normalize($name, Normalizer::FORM_D) ;
	// MV
	$name = str_replace(chr(146), "'", $name) ; // Strange apostrophe
	$name = str_replace(chr(194), "", $name) ; // Added before an apostrophe
	$name = str_replace(chr(198), 'AE', $name) ;
	$name = str_replace(chr(246), 'o', $name) ;
	$name = str_replace(chr(251), 'u', $name) ; // û from Lim-Dul's Vault
	// MCI
	$name = str_replace(array('â', 'á', 'à'), 'a', $name) ;
	$name = str_replace('é', 'e', $name) ;
	$name = str_replace('í', 'i', $name) ;
	$name = str_replace('ö', 'o', $name) ;
	$name = str_replace(array('ú', 'û'), 'u', $name) ;
	$name = str_replace('Æ', 'AE', $name) ;
	//$name = str_replace("'", '‘', $name) ;
	$name = str_replace(array('“', '”'), '"', $name) ;
	// Lotus Noir
	$name = preg_replace('/\s*\/+\s*/', ' / ', $name) ; // corrects "a // b", "a/b"
	// Scryfall / Unstable
	$name = str_replace(' | ', ' ', $name) ;
	$name = str_replace('"', '', $name) ;
	// Special trick for scryfall/magic-ville unstable's "rumor of my death"
	$name = str_replace(' .', '.', $name) ;
	return $name ;
}
function card_text_sanitize($text) {
	$text = trim($text) ;
	$text = preg_replace('/ ?\([^)]*\([^()]*?\)[^(]*\)/', '', $text) ; // UGLY workaround for parenthesis contained in parenthesis on MV
	$text = preg_replace('/ ?\(.*?\)/', '', $text) ; // Remove helper texts
	$text = preg_replace('/ *— */', ' - ', $text) ;
	$text = str_replace('–', '-', $text) ;
	$text = str_replace(chr(151), '-', $text) ;
	$text = str_replace(chr(149), '*', $text) ;
	$text = str_replace("\r\n", "\n", $text) ; // Keep only one type of carriage return
	$text = str_replace(chr(147), '"', $text) ;
	$text = str_replace(chr(148), '"', $text) ;
	//$text = card_name_sanitize($text) ; // Same card name in card text than in card
	$text = html_entity_decode($text) ;
	$pieces = mb_split('\n|  ', $text) ; // Trim each line
	foreach ( $pieces as $i => $piece ) {
		$pieces[$i] = trim($pieces[$i]) ;
		$pieces[$i] = trim($pieces[$i], '.') ;
	}
	$pieces = array_filter($pieces, 'card_text_filter') ;
	$text = implode("\n", $pieces) ;
	return $text ;
}
function card_text_filter($text) {
	return ( $text !== '' ) ;
}
function firstline($string='') {
	$pos = strpos($string, "\n") ;
	if ( $pos === false )
		return $string ;
	else
		return substr($string, 0, $pos) ;
}
function msg($var) {
	if ( is_string($var) )
		echo $var ;
	else
		echo '<pre>'.print_r($var, true).'</pre>' ;
	//echo '<br>' ; // Why ?
	echo "\n" ;
}
function variable($var) {
	$result = gettype($var).' : ' ;
	if ( gettype($var) == 'boolean' ) {
		if ( $var )
			$result .= 'true' ;
		else
			$result .= 'false' ;
	} else
		$result .= $var ;
	return $result ;
}
function card_img_by_name($name, $nbpic=1, $nbpics=1) { // Trim characters forbidden in file names from a card name in order to return its file name
	$name = str_replace(' / ', '', $name) ; // Fire / Ice -> FireIce
	$name = str_replace(' // ', '', $name) ; // Idem with escaping
	$name = str_replace(':', '', $name) ; // Circle of protection: Black -> Circle of protection Black
	$name = str_replace('"', '', $name) ; // Kongming, "Sleeping Dragon"
	if ( $nbpics > 1 )
		$name .= $nbpic ;
	return $name.'.full.jpg' ;
}
function card_name_by_img($img) {
	return str_replace('.full.jpg', '', $img) ;
}
function isint($txt) {
	return ( intval($txt).'' == $txt.'' ) ;
}
function cost_explode($cost) {
	$hybrids = array(
		'Q' => 'BG', 
		'A' => 'GW', 
		'P' => 'RW', 
		'V' => 'UB', 
		'L' => 'RG', 
		'I' => 'UR', 
		'O' => 'WB', 
		'K' => 'BR', 
		'S' => 'GU', 
		'D' => 'WU', 
	) ;
	$result = array() ;
	for ( $i = 0 ; $i < strlen($cost) ; $i++ ) { // First loop letter to letter
		if ( $cost[$i] != '{' ) {
			$ccost = $cost[$i] ;
			if ( isint($cost[$i]) && ( $i < strlen($cost)-1 ) && isint($cost[$i+1]) ) {
				$ccost .= $cost[$i+1] ;
				$i++ ;
			} else if ( array_key_exists($ccost, $hybrids) )
				$ccost = $hybrids[$ccost] ;
		} else {
			$ccost = '' ;
			for ( $j = $i+1 ; $j < strlen($cost) ; $j++ ) // Second loop searches }
				if ( $cost[$j] == '}' )
					break ;
				else
					if ( $cost[$j] != '/' )
						$ccost .= $cost[$j] ; // Add to "current mana"
			$i = $j ; // First loop continues from where second loop stoped
		}
		array_push($result, $ccost) ;
	}
	return $result ;
}
// Class parsing card data
class attrs {
	function add_color($colors) {
		for ( $i = 0 ; $i < strlen($colors) ; $i++ ) {
			$color = strtoupper($colors[$i]) ;
			if ( isint($color) ) // Hybrid colored / colorless, ignore colorless part
				continue ;
			if (
				( $color == 'X' ) // X in cost isn't a color
				|| ( $color == 'P' ) // Phyrexian mana
				|| ( $color == 'E' ) // Colorless mana
			)
				continue ;
			if ( strpos($this->color, $color) === false )
				$this->color .= $color ;
		}
	}
	function parsemana($manas) { // Recieve mana array and adds its data to color and converted cost (card mana cost, split)
		foreach ( $manas as $mana ) { // mana symbols
			if ( isint($mana) ) // Is a number
				$this->converted_cost += intval($mana) ;
			else { // Is a mana
				if ( ! in_array($mana, array('X', 'Y', 'Z') ) ) { // X is worth 0 and no color, Y and Z only are in the ultimate nightmare ...

					$this->add_color($mana) ;
					if ( isint($mana[0]) ) // Hybrid colorless/colored
						$this->converted_cost += intval($mana[0]) ;
					else
						$this->converted_cost++ ;
				}
			}
		}
	}
	function addmana($color) {
		if ( ! property_exists($this, 'provide') )
			$this->provide = array() ;
		if ( ! in_array($color, $this->provide) )
			$this->provide[] = $color ;
	}
	function __construct($arr = null) {
		if ( $arr != null ) {
			// Cost
			if ( array_key_exists('cost', $arr) ) {
				// Explode mana cost
				$this->manas = cost_explode($arr['cost']) ;
				// Compute color and converted cost
				$this->color = '' ;
				$this->converted_cost = 0 ;
				$this->parsemana($this->manas) ;
				// No color found, consider as colorless
				if ( $this->color == '' )
					$this->color = 'X' ;
				// Search if color is given in card text
				$colornames = array(
					'X' => 'colorless', 
					'W' => 'white',
					'U' => 'blue',
					'B' => 'black',
					'R' => 'red',
					'G' => 'green',
					'WUBRG' => 'all colors'
				) ;
				if ( preg_match('`'.$arr['name'].' is ('.implode('|', $colornames).')`', $arr['text'], $matches) ) {
					if ( $i = array_search($matches[1], $colornames) )
						$this->color = $i ;
				}
				// Sort colors
				global $allcolorscode ;
				$this->color_index = -1 ;
				for ( $i = 0 ; $i < count($allcolorscode) ; $i++ ) // Search right order in hardcoded list
					if ( count(array_diff(str_split($this->color), str_split($allcolorscode[$i]))) == 0 ) {
						$this->color_index = $i ;
						$this->color = $allcolorscode[$this->color_index] ;
						break ;
					}
				//if ( $this->color_index < 0 )
					//die('Color index error for ['.$this->color.'] '.$arr['name']) ;
			} else
				die('No cost in array : '.$arr['name']) ;
			// Types
			if ( array_key_exists('types', $arr) ) {
				manage_types($arr['types'], $this) ;
			} else
				die('No type in array : '.$arr['name']) ;
			// Text
			if ( array_key_exists('text', $arr) ) {
				$arr['text'] = str_replace('  ', "\n", $arr['text']) ; // That was wanted by spoiler writter
				// Transform
				$pieces = explode("\n-----\n", $arr['text']) ;
				if ( count($pieces) > 1 ) { // Card is a transform
					manage_all_text($arr['name'], $pieces[0], $this) ; // Manage "day"
					// Then manage "night", 3+ lines : name, color/types, text (all other lines, such as "pow/tou \n other effects" for creats)
					$transform = new stdClass() ;
					$matches = explode("\n", $pieces[1]) ;
					if ( count($matches) > 0 )
						$transform->name = stripslashes(array_shift($matches)) ;
					if ( count($matches) > 0 ) {
						$t = array_shift($matches) ;
						$reg = '/\%(\S+)? (.*)/s' ;
						$transform->color = 'X' ;
						if ( preg_match($reg, $t, $matches_t) ) {
							if ( $matches_t[1] !== '' ) {
								$transform->color = $matches_t[1] ;
							}
							$t = $matches_t[2] ;
						}
						manage_types($t, $transform) ;
					} else
						echo 'No color/type for transformed '.$arr['name'].'('.$transform->name.')<br>' ;
					if ( count($matches) > 0 ) {
						manage_all_text($transform->name, implode("\n", $matches), $transform) ;
					} else
						echo 'No text for transformed '.$arr['name'].'('.$transform->name.')<br>' ;
					$this->transformed_attrs = $transform ;
				}
				else {
					// Split / Flip
					$pieces = explode("\n----\n", $arr['text']) ;
					if ( count($pieces) > 1 ) {
						manage_all_text($arr['name'], $pieces[0], $this) ; // Manage "main" part
						$matches = explode("\n", $pieces[1]) ;
						if ( strpos($arr['name'], '/') === false ) { // No "/" in name, it's a flip
							$flip = new stdClass() ;
							$flip->name = array_shift($matches) ;
							if ( count($matches) > 0 )
								manage_types(array_shift($matches), $flip) ;
							$this->flip_attrs = $flip ;
							manage_all_text($arr['name'], implode("\n", $matches), $flip) ;
						} else { // "/" in name, it's a split
							$split = new stdClass() ;
							if ( count($matches) > 0 )
								$split->manas = cost_explode(array_shift($matches)) ;
							if ( count($matches) > 0 )
								manage_types(array_shift($matches), $split) ;
							$this->split = $split ;
							manage_all_text($arr['name'], implode("\n", $matches), $this) ;
							// Apply colors to initial card
							$this->parsemana($split->manas) ;
							// Apply aftermath
							if ( property_exists($split, 'aftermath') ) {
								$this->aftermath = $split->aftermath ;
							}
						}
					} else
						manage_all_text($arr['name'], $arr['text'], $this) ;
				}
			} else
				die('No text in array : '.$arr['name']) ;
		}
	}
}
// Parsing lib
function text2number($text, $xval=0) { // By default, X worth 0 (like in CC) but is overridable, as sometimes it's senseless (X tokens X/X)
	switch ( $text ) {
		case 'X' : 
		case '*' : 
			return $xval ;
		case 'one' :
		case 'a' :
		case 'an' :
			return 1 ;
		case 'two' :
			return 2 ;
		case 'three' :
			return 3 ;
		case 'four' :
			return 4 ;
		case 'five' :
			return 5 ;
		case 'six' :
			return 6 ;
		case 'seven' :
			return 7 ;
		case 'eight' :
			return 8 ;
		case 'nine' :
			return 9 ;
		case 'ten' :
			return 10 ;
		case 'eleven' :
			return 11 ;
		case 'twelve' :
			return 12 ;
		case 'thirteen' :
			return 13 ;
		default :
			return intval($text) ;
	}
}
function manage_types($type, $target) {
	global $cardtypes, $permtypes ;
	$type = strtolower($type) ;
	$target->types = array() ;
	$target->subtypes = array() ;
	if ( preg_match('/(.*) - (.*)/', $type, $matches) ) {
		$type = $matches[1] ;
		if ( count($matches[2]) > 0 )
			$target->subtypes = explode(' ', $matches[2]) ;
	}
	$target->permanent = false ;
	foreach ( explode(' ', $type) as $type ) {
		if ( array_search($type, $cardtypes) !== false ) {
			$target->types[] = $type ;
			if ( array_search($type, $permtypes) !== false )
				$target->permanent = true ;
		} else
			$target->supertypes[] = $type ;
	}
}
function color_compare($a, $b) {
	global $colorscode ;
	return array_search($a, $colorscode) - array_search($b, $colorscode) ;
}
$colors = array('X' => 'colorless', 'W' => 'white', 'U' => 'blue', 'B' => 'black', 'R' => 'red', 'G' => 'green') ;
$basic_lands = array('W' => 'plains', 'U' => 'island', 'B' => 'swamp', 'R' => 'mountain', 'G' => 'forest') ;
$colorscode = array_keys($colors) ; // For ordering
$allcolorscode = array('', 'X', 'W', 'U', 'B', 'R', 'G', 'WU','WB','UB','UR','BR','BG','RG','RW','GW','GU','WUB','UBR','BRG','RGW','GWU','WBR','URG','BGW','RWU','GUB','WUBR','UBRG','BRGW','RGWU','GWUB','WUBRG') ;
$cardtypes = array('artifact', 'creature', 'enchantment', 'instant', 'land', 'planeswalker', 'sorcery', 'tribal') ;
$permtypes = array('artifact', 'creature', 'enchantment', 'land', 'planeswalker') ;
$spelltypes = array('instant', 'sorcery') ;
$creat_attrs = array( 'double strike', 'lifelink', 'vigilance', 'infect', 'trample', 'exalted', 'battle cry', 'cascade', 'changeling');
// General conditions considerations
$conds = array() ; // List conditions
$conds['battlefield'] = 'you control' ;
$conds['!battlefield'] = 'your opponents control' ;
$conds['battlefields'] = 'on the battlefield' ;
//$conds['counter'] = 'counters on it' ;
$conds['graveyard'] = 'in your graveyard' ;
$conds['graveyards'] = 'in all graveyards' ;
// Regex schemes
$manacost = '[{}%0-9WUBRG]+' ;
$boost = '[+-][0-9XY]+' ;
$boosts = '(?<pow>'.$boost.')\/(?<tou>'.$boost.')' ;
function manacost($str) { // Simplify mana cost, removing $ { } from various syntax
	return str_replace(array('%', '{', '}'), '', $str) ;
}
// Structured text parsing
function parse_creature($name, $text_lines, $target) { // Creatures : pow/tou
	$pt = '[\d\*\+\-\.\^]*' ; // Numerics, *, + for *+1, - for *-1, . for unhinged half pow/tou points, ^ S.N.O.T.
	$txt = trim($text_lines[0]) ;
	if ( preg_match('/^(?<pow>'.$pt.')\/(?<tou>'.$pt.')$/', $txt, $matches) ) {
		$target->pow = intval($matches['pow']) ;
		$target->thou = intval($matches['tou']) ;
		array_shift($text_lines) ;
	} else {
		msg('powthou error for '.$name.' : ['.$txt.']') ;
		$target->pow = 0 ;
		$target->thou = 0 ;
	}
	return $text_lines ;
}
function parse_planeswalker($name, $pieces, $target) {// Planeswalkers : loyalty counters, steps, emblems
	if ( preg_match('/^\%?(\d+)\#?$/', $pieces[0], $matches) ) {
		$target->counter = intval($matches[1]) ;
		array_shift($pieces) ;
	} else {
		msg('loyalty counter error for '.$name.' : ['.$pieces[0].']') ;
		$target->counter = 0 ;
	}
	// Steps
	$target->steps = array() ;
	foreach ( $pieces as $piece )
		if (
			preg_match('/\[([+-]?[\dX]+)\]/', $piece, $matches) // Spoilers with [+1]
			|| preg_match('/\|([+-]?[\dX]+)\|/', $piece, $matches) // Spoilers with |+1|
			|| preg_match('/([+-]?[\dX]+): /', $piece, $matches) // Spoilers with +1: (mci)
		) {
			if ( ! in_array($matches[1], $target->steps) ) // Not adding multiple times the same item
				$target->steps[] = $matches[1] ;
			// Emblem
			if ( preg_match('/[You get|Target opponent gets] an emblem with "(.*)"(.*)$/', $piece, $matches) ) {
				$token = new stdClass() ;
				$token->nb = 1 ;
				$token->name = 'Emblem.'.$target->subtypes[0] ;
				$token->attrs = new stdClass() ;
				$token->attrs->types = array('emblem') ;
				$token->attrs->subtypes = array() ;
				manage_text($name, $matches[1], $token->attrs) ;
				$target->tokens[] = $token ;
				manage_text($name, $matches[2], $target) ;
			} else
				manage_text($name, $piece, $target) ;
		}
	return array() ;
	//return $pieces ;
}
function manage_all_text($name, $text, $target) {
	$text_lines = mb_split('\n|  ', $text) ;
	if ( array_search('creature', $target->types) !== false )
		$text_lines = parse_creature($name, $text_lines, $target) ;
	if ( array_search('planeswalker', $target->types) !== false )
		$text_lines = parse_planeswalker($name, $text_lines, $target) ;
	$target->text = $text_lines ; // Save text while parsing for lines requiring access to other lines (lines are parsed individually)
	foreach ( $text_lines as $text_line ) {
		$text_line = trim($text_line, ' .') ;
		manage_text($name, $text_line, $target) ;
	}
	unset($target->text);
}
// Reads 1 "line" of text and adds to target attributes parsed inside
function manage_text($name, $text, $target) { 
	// Various types
	global $manacost, $boost, $boosts, $cardtypes, $colors ;
	// Workarounds
	$text = trim($text) ;
	$text = str_replace('—', '-', $text) ; // Causes bugs in regex parsing
	$text = str_replace('comes into play', 'enters the battlefield', $text) ; // Old style CIP
	$text = preg_replace('/\(.*?\)/', '', $text) ; // Remove reminder texts as they can interfere in parsing (vanishing reminder has text for upkeep trigger for exemple)
	// Card attributes
		// In hand
	if ( preg_match('/Cycling ('.$manacost.')/', $text, $matches) )
		$target->cycling = manacost($matches[1]) ;
	if ( preg_match('/Morph ('.$manacost.')/', $text, $matches) )
		$target->morph = manacost($matches[1]) ;
	if ( preg_match('/Megamorph ('.$manacost.')/', $text, $matches) ) {
		$target->morph = manacost($matches[1]) ;
		$target->megamorph = true ;
	}
	if ( preg_match('/Suspend (\d+)\s*-\s*('.$manacost.')/', $text, $matches) ) {
		$target->suspend = intval($matches[1]) ;
		$target->suspend_cost = manacost($matches[2]) ;
	}
	if ( preg_match('/Forecast - ('.$manacost.')/', $text, $matches) )
		$target->forecast = manacost($matches[1]) ;
		// In grave
	if ( preg_match('/Flashback ('.$manacost.')/', $text, $matches) )
		$target->flashback = manacost($matches[1]) ;
	if ( stripos($text, 'Retrace') !== false )
		$target->retrace = true ;
	if ( preg_match('/Dredge (\d+)/', $text, $matches) )
		$target->dredge = intval($matches[1]) ;
	if ( preg_match('/Scavenge ('.$manacost.')/', $text, $matches) )
		$target->scavenge = manacost($matches[1]) ;
	if ( preg_match('/Embalm ('.$manacost.')/', $text, $matches) )
		$target->embalm = manacost($matches[1]) ;
	if ( preg_match('/Aftermath/', $text, $matches) ) {
		$target->aftermath = implode($target->split->manas);
	}
	if ( preg_match('/Eternalize ('.$manacost.')/', $text, $matches) ) {
		$target->eternalize = manacost($matches[1]) ;
	}
	// Permanents attributes
	if ( preg_match('/Vanishing (\d+)/', $text, $matches) ) {
		$target->vanishing = true ;
		$target->counter = intval($matches[1]) ;
	}
	if ( preg_match('/Fading (\d+)/', $text, $matches) ) {
		$target->fading = true ;
		$target->counter = intval($matches[1]) ;
	}
	if ( preg_match('/Echo ('.$manacost.')/', $text, $matches) )
		$target->echo = manacost($matches[1]) ;
	if ( preg_match('/Modular (\d+)/', $text, $matches) ) {
		if ( ! property_exists($target, 'counter') ) {
			$target->counter = 0 ;
		}
		$target->counter += intval($matches[1]) ;
		$target->note = '+1/+1' ;
	}
	if ( preg_match('/Graft (\d+)/', $text, $matches) ) {
		if ( ! property_exists($target, 'counter') ) {
			$target->counter = 0 ;
		}
		$target->counter += intval($matches[1]) ;
		$target->note = '+1/+1' ;
	}
		// Hideaway
	if ( stripos($text, 'Hideaway') !== false )
		$target->tapped = true ;
	// Spell effect
	if ( stripos($text, 'Manifest') !== false )
		$target->manifester = true ;
	// Devoid
	if ( stripos($text, 'Devoid') !== false ) {
		global $allcolorscode ;
		$target->color = $allcolorscode[1] ;
	}
	if ( $text === 'Ascend' ) {
		$token = new stdClass() ;
		$token->nb = 1 ;
		$token->attrs = new stdClass() ;
		$token->attrs->types[] = 'Emblem' ;
		$token->name = 'City\'s Blessing' ;
		$target->tokens[] = $token ;
		return;
	}
	// Without keyword
		// Untap
	if ( stripos($text, $name.' doesn\'t untap during your untap step') !== false )
		$target->no_untap = true ;
	// Add mana
	global $basic_lands ;
	if ( property_exists($target, 'subtypes') ) // Basic land types in subtypes
		foreach ( $basic_lands as $color => $basic_land )
			if ( in_array($basic_land, $target->subtypes) )
				$target->addmana($color) ;
	if ( preg_match('/^(?<beforecost>.*")?(?<cost>.*?): Add (?<manas>.*?) to your mana pool/', $text, $matches) ) {
		$cost = explode(', ', $matches['cost']) ;
		$idx = array_search('{T}', $cost) ;
		if ( $idx > -1 ) // {
			array_splice($cost, $idx, 1) ;
		if ( $matches['manas'] == 'one mana of any color' ) {
			if ( method_exists($target, 'addmana') ) {
				$target->addmana('W') ;
				$target->addmana('U') ;
				$target->addmana('B') ;
				$target->addmana('R') ;
				$target->addmana('G') ;
			}
		} else {
			$manas = preg_split('/( or )|(, )/', $matches['manas']) ;
			foreach ( $manas as $mana )
				if ( preg_match_all('/\{(.*?)\}/', $mana, $matches) )
					for ( $i = 1 ; $i < count($matches) ; $i++ )
						foreach ( $matches[$i] as $color )
							if ( method_exists($target, 'addmana') )
								$target->addmana($color) ;
		}
		//}
	}
	// CIP
	if ( preg_match('/'.addcslashes($name, '\'"\\/' ).' enters the battlefield (or (?<alt>[^,]*),)?(?<act>.*)/', $text, $matches) ) {
		/* Alternate trigger ('or attacks', 'or leaves play' ...)
		if ( $matches['alt'] != '' )
			echo $name.' : '.$matches['alt']."\n" ;
		*/
		$txts = explode(' and ', $matches['act']) ;
		foreach ( $txts as $txt ) {
			$txt = trim($txt, '.') ;
			if ( preg_match('/^tapped ?(.*)/', $txt, $matches) ) {
				$target->tapped = true ;
				if ( $matches[1] != '' ) {
					$words = explode(' ', $matches[1]) ;
					switch ( $words[0] ) {
						case 'unless' : 
							unset($target->tapped) ; // A condition will replace hard tapped
							if ( $matches[1] == 'unless you control two or fewer other lands' ) {
								$target->ciptc = 'this.zone.player.controls({"types": "land"})>3' ;
							} elseif ( $matches[1] == 'unless you control two or more basic lands' ) {
								$target->ciptc = '(this.zone.player.controls({"supertypes": "basic"})<2)' ;
							} elseif ( preg_match('/^unless you control an? (.*) or an? (.*)$/', $matches[1], $matches ) ) {
								$target->ciptc = '(this.zone.player.controls({"subtypes": "'.strtolower($matches[1]).'"})==0)' ;
								$target->ciptc .= '&&(this.zone.player.controls({"subtypes": "'.strtolower($matches[2]).'"})==0)' ;
							} else // Unmanaged
								echo $name.' : '.$words[0].' : '.$matches[1]."\n" ;
							break ;
						case 'with' : // Don't display message because it will be managed later
							$txt = trim($matches[1]) ;
							break ;
						default : // Unmanaged
							echo $name.' : '.$words[0].' : '.$matches[1]."\n" ;
					}
				}
			}
			if ( 
				( $txt == 'does not untap during its controller\'s untap phase' )
				|| ( $txt == 'doesn\'t untap during its controller\'s untap phase' )
				|| ( $txt == 'doesn\'t untap during your untap step' )
			)
				$target->no_untap = true ;
			if ( preg_match('/^with (.*) (.*) counters? on it(.*)/', $txt, $matches) ) {
				$target->counter = text2number($matches[1]) ; ;
				if ( $matches[2] != 'charge' ) // Basic counter type, no use to let the user know. +1/-1 will be removed later while parsing pow/tou
					$target->note = $matches[2] ;
			}
		}
	}
	// Type-specific
		// Planeswalkers are managed in "all lines"
		// Creatures : pow, thou, lifelink ...
	global $conds ; 
	if ( is_array($target->types) && array_search('creature', $target->types) !== false ) {
		if ( isset($target->note) && is_string($target->note) && preg_match('/^'.$boosts.'$/', $target->note, $matches) ) {
			unset($target->note) ;
			$target->pow += $target->counter * intval($matches['pow']) ;
			$target->thou += $target->counter * intval($matches['tou']) ;
		}
		// Conditionnal pow and tou (*/*)
		if ( preg_match('/(.*)'.$name.'.{0,3} power (?<both>.*) equal to the number of (?<next>.*)/', $text, $m) ) {
			if ( preg_match('/^(?<type>.*) named (?<name>.*) (?<cond>'.implode('|', $conds).')/', $m['next'], $matches)
				|| preg_match('/^(?<type>.*) (?<cond>'.implode('|', $conds).')/', $m['next'], $matches) ) {
				$target->powtoucond = new stdClass() ;
				$target->powtoucond->what = 'cards' ;
				$target->powtoucond->from = array_search($matches['cond'], $conds) ;
				if ( array_key_exists('name', $matches) )
					$target->powtoucond->cond = 'name='.$matches['name'] ;
				else
					switch ( $matches['type'] ) {
						case 'cards' :
							break ;
						case 'card types among cards' : // Tarmogoyf
							$target->powtoucond->what = 'types' ;
							break ;
						case 'snow permanents' :
							$target->powtoucond->cond = 'stype=snow' ;
							break ;
						case 'creatures' :
						case 'lands' :
						case 'artifacts' :
							$target->powtoucond->cond = 'type='.substr($matches['type'], 0, -1) ;
							break ;
						case 'creature cards' :
						case 'land cards' :
						case 'artifact cards' :
						case 'sorcery cards' :
							$target->powtoucond->cond = 'type='.substr($matches['type'], 0, -6) ;
							break ;
						case 'instant and sorcery cards' :
							$target->powtoucond->cond = 'type=instant|type=sorcery';
							break ;
						// zombies on the battlefield plus the number of zombie card
						// red creatures
						// green mana symbols in the mana costs of permanent
						// basic land types among land
						// untapped artifacts, creatures, and land
						// creatures named plague rat
						// other rat
						// face-down creature
						default : // Let's consider default is creature types (basic land types as considered as ones)
							$type = substr(strtolower($matches['type']), 0, -1) ;
							if ( $type == 'elve' )
								$type = 'elf' ;
							$target->powtoucond->cond = 'ctype='.$type ;
					}
			}
		}
		// Conditionnal mono boost (+1/+2 as long as ...)
		if (
			( preg_match('/'.$name.' gets '.$boosts.' as long as (?<what>.*)/', $text, $matches ) ) 
			|| ( preg_match('/As long as (?<what>.*), '.$name.' gets '.$boosts.'( and (?<attrs>.*)?)/', $text, $matches ) )
		) { // Single
			$what = strtolower($matches['what']) ;
			if ( preg_match('/(?<who>.*?) controls? (?<what>.*)/', $what, $m) ) {
				$powtoucond = null ;
				$pieces = explode(', ', $m['what']);
				if ( count($pieces) > 1 ) { // Tek : multiple conditions are present in only one sentence, take only first ATM (architecture only manages one pow tou cond at a time)
					$m['what'] = $pieces[0] ;
				}
				switch ( $m['who'] ) {
					case 'you' :
						switch ( true ) {
							// Very standard case
							case preg_match('/(?<who>an?) (?<what>.*)/', $m['what'], $mm) :
							case preg_match('/(?<who>another) (?<what>.*)/', $m['what'], $mm) :
								$powtoucond = new stdClass() ;
								$powtoucond->what = 'card' ;
								$powtoucond->pow = intval($matches['pow']) ;
								$powtoucond->thou = intval($matches['tou']) ;
								$powtoucond->from = 'battlefield' ;
								if ( array_search($mm['what'], $cardtypes)!== false  )
									$powtoucond->cond = 'type='.$mm['what'] ;
								else
									$powtoucond->cond = 'ctype='.$mm['what'] ;
								$powtoucond->other = ($mm['who'] === 'another');
								break ;
							// Unmanageable
							case ( $m['what'] === 'three or more artifacts' ): // Impossible to detect the number of cards satisfying condition
							case ( $m['what'] === 'eight or more lands' ):
							case ( $m['what'] === 'no untapped lands' ): // Impossible to detect tapped lands nor their absence
							case ( $m['what'] === 'your commander' ): // Impossible to detect a commander card nor wether it's on the battlefield (it's stored there by many users)
								break;
							default :
								msg("No pow/tou condition found for $name : $text") ;
						}
						break ;
					case 'an opponent' : // Unmanaged, just there to avoid error message
					case 'no opponent' :
					case 'any player' :
						break ;
					default:
						msg($name.' : '.$m['who'].' -> '.$m['what']) ;
				}
				if ( $powtoucond !== null ) {
					if ( isset($matches['attrs']) ) {
						apply_all_creat_attrs($powtoucond, $matches['attrs']) ;
					}
					$target->powtoucond = $powtoucond ;
					return;
				}
			} /*else
				msg(' * '.$name.' : '.$matches['pow'].'/'.$matches['tou'].' : '.$matches['what']) ;*/
		}
		// Conditionnal poly boost (+1/+1 for each ...)
		if ( preg_match('/'.$name.' gets '.$boosts.' for each (?<what>.*)/', $text, $matches ) )
			conditionnal_poly_boost($target, $matches, $matches['what']) ;
	}
	// Attach/Equip-boost
	if ( preg_match('/(Equipped|Enchanted) (creature|permanent) gets '.$boosts.'(?<after>.*)/', $text, $matches) ) {
		if ( strpos($matches['after'], 'until end of turn') === FALSE ) { // Umezawa's Jitte
			if ( preg_match('/for each (?<what>.*)/', $matches['after'], $matches_after) ) {
				conditionnal_poly_boost($target, $matches, $matches_after['what']) ;
			} else {
				if ( ! isset($target->bonus) )
					$target->bonus = new stdClass() ;
				$target->bonus->pow = intval($matches['pow']) ;
				$target->bonus->tou = intval($matches['tou']) ;
				apply_all_creat_attrs($target->bonus, $matches['after']) ;
				return;
			}
		}
	}
	if ( preg_match('/(Equipped|Enchanted) (creature|permanent) doesn\'t untap during its controller\'s untap step/', $text, $matches) ) {
		if ( ! isset($target->bonus) )
			$target->bonus = new stdClass() ;
		$target->bonus->no_untap = true ;
	}
	// Living weapon
	if ( strpos($text, 'Living weapon') !== false )
		$target->living_weapon = true ;
	// Token creation
	$colreg = implode('|', $colors) ;
	if ( preg_match_all('/(?<number>\w+) (?<tapped>tapped )?((?<pow>\d*|X|\*+)\/(?<tou>\d*|X|\*+) )?(?<color>'.$colreg.') (and (?<color2>'.$colreg.') )?(?<types>[\w| ]+ creature) tokens?(?<attrs> with .*)?/', $text, $all_matches, PREG_SET_ORDER) ) {
	// Godsire, Hazezon Tamar
	//|| preg_match_all('/(?<number>\w+) (?<pow>\d*)\/(?<tou>\d*) (?<types>[\w| ]+ creature) tokens? that[\'s| are] (?<color>'.$colreg.'), (?<color2>'.$colreg.'), and (?<color3>'.$colreg.')/', $text, $all_matches, PREG_SET_ORDER)
		foreach ( $all_matches as $matches ) {
			$token = new stdClass() ;
			$token->nb = text2number($matches['number']) ;
			if ( $token->nb < 1 )
				$token->nb = 1 ; // Put at least 1 token
			$token->attrs = new stdClass() ;
			// Token name -> types / subtypes -> name
			$types = explode(' ', $matches['types']) ;
			$nameparts = array() ; // Get case sensitive names parts before they get lowercased as subtypes
			foreach ( $types as $type )
				if ( array_search($type, $cardtypes) !== false ) // Additionnal card types (artifact, enchant ...) are parsed as subtypes, filter them
					$token->attrs->types[] = strtolower($type) ;
				else {
					$token->attrs->subtypes[] = strtolower($type) ;
					$nameparts[] = $type ;
				}
			$token->name = implode($nameparts, ' ') ; // Recompose token name from its subtypes
			// Other attrs
			$token->attrs->pow = text2number($matches['pow'], 0) ; // 0 for image
			$token->attrs->thou = text2number($matches['tou'], 0) ;
			$token->attrs->color = array_search($matches['color'], $colors) . array_search($matches['color2'], $colors) ;
			if ( $matches['tapped'] !== '' ) {
				$token->attrs->tapped = true ;
			}
			if ( isset($matches['attrs']) ) {
				apply_all_creat_attrs($token->attrs, $matches['attrs']) ;
			}
			$target->tokens[] = $token ;
			return;
		}
	}
	// Investigate / Clues
	if ( preg_match('/[I|i]nvestigate/', $text, $matches) ) {
		$token = new stdClass() ;
		$token->nb = 1 ;
		$token->attrs = new stdClass() ;
		$token->attrs->types[] = "artifact" ;
		$token->name = "Clue" ;
		$target->tokens[] = $token ;
	}
	// Treasures
	if ( preg_match('#[C|c]reates? (?<number>\w+) (?<color>'.$colreg.') (?<name>.*) artifact tokens?(.*They have| with) "(?<text>.*)"#', $text, $match) ) {
		$token = new stdClass() ;
		$token->nb = text2number($match['number'], 1) ;
		$token->attrs = new stdClass() ;
		$token->attrs->types[] = "artifact" ;
		$name = $match['name'] ;
		$token->name = $name ;
		manage_text($name, $match['text'], $token->attrs) ;
		$target->tokens[] = $token ;
		return;
	}
	// The Monarch
	if ( preg_match('/the monarch/', $text, $matches) ) {
		$token = new stdClass() ;
		$token->nb = 1 ;
		$token->attrs = new stdClass() ;
		$token->attrs->types[] = "conspiracy" ;
		$token->name = "The Monarch" ;
		$target->tokens[] = $token ;
	}
	if ( preg_match('/ get( that many)? (?<energy>(\{E\})+)/', $text, $matches) ) { // "You get", "you get", "[...] and get"
		$token = new stdClass() ;
		$token->nb = 1 ;
		$token->attrs = new stdClass() ;
		$token->attrs->types[] = "conspiracy" ;
		$token->attrs->counter = intval((strlen($matches["energy"]))/3); // Energy reserve comes with the number of energy couters this card provides
		$token->name = "Energy Reserve" ;
		$target->tokens[] = $token ;
	}
	// Distinct activated from static abilities
	/*$parts = preg_split('/\s*:\s*'.'/', $text) ;
	if ( count($parts) == 2 ) {
		$cost = $parts[0] ;
		$text = $parts[1] ;
		if ( ! isset($target->activated) )
			$target->activated = new Simple_object() ;
		$target = $target->activated ;
	}*/
	// All creatures booster (crusade like)
	$debug = 0;
	if (
		preg_match_all('#^(?<cost>.*[\:-] )?(?<precond>.*[,\.] )?(?<cond>.*?)? get (?<pow>'.$boost.')\/(?<tou>'.$boost.')(?<attrs>.*)?#', strtolower($text), $matches, PREG_SET_ORDER)
		|| preg_match_all('#(?<cond>.*? you control) have (?<attrs>.*)#', strtolower($text), $matches, PREG_SET_ORDER) 
	) {
		foreach ( $matches as $match ) {
			if ( $debug ) print_r($match) ;
			$cond = trim($match['cond']) ;
			$cond = str_replace('may have ', '', $cond);
			// Cards / effetcs not manageable
			$badConds = array('they each', 'you noted') ;
			foreach ( $badConds as $badCond ) {
				if ( strpos($cond, $badCond) !== false ) {
					if ( $debug ) echo "Cond : $badCond\n" ;
					continue 2;
				}
			}
			$badPreconds = array('you noted', 'target creature');
			if ( isset($match['precond']) ) {
				foreach ( $badPreconds as $badPrecond ) {
					if ( strpos($match['precond'], $badPrecond) !== false ) {
						if ( $debug ) echo "Precond : $badPrecond\n" ;
						continue 2;
					}
				}
			}
			$boost_bf = boost_bf($match) ;
			if ( strpos($cond, 'other') > -1 ) {
				$boost_bf->self = false ;
				$cond = str_replace('other', '', $cond) ;
			}
			if ( strpos($cond, strtolower($name)) > -1 ) {
				$boost_bf->self = true;
				$cond = str_replace(strtolower($name), '', $cond) ;
			}
			// Whose creatures are affected
			if ( strpos($cond, 'you control') > -1 ) { // Yours
				$boost_bf->control = 1 ;
				$cond = str_replace('you control', '', $cond) ;
			}
			if ( strpos($cond, 'your opponents control') > -1 ) { // Your opponent's
				$boost_bf->control = -1 ;
				$cond = str_replace('your opponents control', '', $cond) ;
			}
			// Conditions (creature type, color ...)
			$cond = trim($cond); // In case it's needed because of previous actions
			$tokens = explode(' ', $cond);
			if ( $debug ) print_r($tokens);
			$type_cond = array() ;
			foreach ( $tokens as $i => $token ) {
					$ci = array_search($token, $colors) ;
					// Color selector
					if ( $ci !== false ) {
						$boost_bf->cond = "color=$ci" ;
					} else {
						switch ( $token ) {
							case '':
								break;
							// Class
							case 'tokens':
								$type_cond[] = "class=token" ;
								break ;
							case 'nontoken':
								$type_cond[] = "class=card" ;
								break ;
							// Supertype
							case 'legendary' :
							case 'commander' : // Let's consider it as a supertype for now (changeling should not been boosted)
								$type_cond[] = "stype=$token" ;
								break ;
							// Card type, not creature type
							case 'instant' : // Soulfire Grand Master
							case 'sorcery' :
							case 'artifact' :
							case 'land' :
								$type_cond[] = "type=$token" ;
								break ;
							// Special case for attacking and blocking
							case 'attacking':
							case 'blocking':
								if ( $target->permanent ) { // Instants/sorceries should still be enabled by default
									$boost_bf->enabled = false; // Let player manage it manually on permanents
								}
								// In this case, only one player may be affected, try to guess it based on power boost
								if ( $boost_bf->control == 0 ) {
									if ( 2*$boost_bf->pow + $boost_bf->tou < 0 ) {
										$boost_bf->control = -1 ;
									} else {
										$boost_bf->control = 1 ;
									}
								}
								break;
							// Words to ignore, too generic or unreproductible clientside
							case 'you':
							case 'and':
							case 'all':
							case 'creature':
							case 'creatures':
							case 'those': // A condition was probably specified before this boost
							case 'they':
							case 'gets':
							case '+2/+2':
							case 'instead':
							case 'spells':
							case '*': // Bug in modal spells
								break ;
							// Words that indicate a condition too specific to be managed, a generic boost_bf will be added
							case 'counters':
								$type_cond = array() ; // Also cancels previous conditions
								break 2 ;
							case 'defending':
								$boost_bf->control = -1;
								$type_cond = array() ; // Also cancels previous conditions
								break 2 ;
							case 'enchanted':
								if ( ( $i+1 < count($tokens)) && ( $tokens[$i+1] === 'player' ) ) { // If the spell target a player, consider self
									$boost_bf->control = 1;
									$type_cond = array() ; // Also cancels previous conditions
									break 2 ;
								} else {
									break 3 ;
								}
							// Words that indicate no boost_bf shall be created
							case 'target':
								if ( $tokens[$i+1] === 'player' ) { // If the spell target a player, consider self
									$type_cond = array() ; // Also cancels previous conditions
									break 2 ;
								} else { // This probably catched a trigger, we're in a state based rule management
									break 3 ;
								}
							case 'with': // Maybe a little bit strong
							case 'without':
							/*
							case 'flying': // Conditions unmanageable client side and too specific for a boost_bf
							case 'flanking':
							case 'shadow':
							case 'infect':
							case 'greater':
							*/
							case 'named':
							case 'face-down':
							case 'type':
							case 'chosen': // Depending on a choice, can't be managed by boost_bf
							case 'choice':
							case 'chose':
							case '-': // Line is a keyword, it probably contains many words and complex conditions
							case 'multicolored': // Impossible to detect
							case 'long': // "As long as" = probably a bad cond
								break 3 ;
							// Default case : it's a creature type
							default :
								// Non-
								if ( substr($token, 0, 4) === 'non-') {
									$token = substr($token, 4);
									break 3; // For now, those cards are managed by manually adding 2 boost BF
								}
								// Plurals - Specific
								switch ( $token ) {
									case 'dwarves': 
										$token = 'dwarf';
										break ;
								}
								// Plurals - Generic
								if ( substr($token, -1) === 's' ) {
									$token = substr($token, 0, -1) ;
								}
								$type_cond[] = "ctype=$token" ;
						}
					}
			}
			if ( count($type_cond) > 0 ) {
				$boost_bf->cond = implode('|', $type_cond) ;
			}
			$eot = false ;
			if ( array_key_exists('attrs', $match) ) {
				if ( strpos($match['attrs'], 'for each') ) {
					continue;
				}
				$eot = ( strpos($match['attrs'], 'until end of turn') !== false ) ;
				$applied_attrs = apply_all_creat_attrs($boost_bf, $match['attrs']) ;
			}
			$boost_bf->eot = $eot ;
			if ( $boost_bf->enabled ) { // Not already changed from its default value
				if ( isset($target->permanent) && ( $target->permanent === false ) ) { // Spells (ex: Overrun)
					$boost_bf->enabled = true; // enabled by default
				} else {
					// Not a card type (emblem) or permanent
					$boost_bf->enabled = ! $boost_bf->eot ; // boost_bf_eot are activated (ex: Garruk), then should not been enabled by default
				}
			}
			if ( ( $boost_bf->pow !== 0 ) || ( $boost_bf->tou !== 0 ) || ( $applied_attrs > 0 ) ) { // Avoids boost_bf that only adds an unmanaged attrs such as haste
				$target->boost_bf[] = $boost_bf ;
				return;
			}
		}
	}
	if ( preg_match("#Each other creature you control that's a (?<type1>.*) or (?<type2>.*) gets (?<pow>$boost)\/(?<tou>$boost)#", $text, $match) ) {
		$boost_bf = boost_bf($match) ;
		$boost_bf->self = false ;
		$boost_bf->control = 1 ;
		$boost_bf->cond = strtolower('ctype='.$match['type1'].'|ctype='.$match['type2']) ;
		$target->boost_bf[] = $boost_bf ;
	}
	// Animate
	if ( preg_match('/((?<cost>.*)\s*:\s*)?(?<eot>Until end of turn, )?'.addcslashes($name, '/').' (.* it )?becomes an? (?<pow>\d)\/(?<tou>\d) (?<rest>.*)/', $text, $matches) ) {
		$animated = new stdClass() ;
		if ( $matches['cost'] != '' )
			$animated->cost = manacost($matches['cost']) ;
		$animated->pow = intval($matches['pow']) ;
		$animated->tou = intval($matches['tou']) ;
		if ( $matches['eot'] != '' )
			$animated->eot = true ;
		$rest = $matches['rest'] ;
		if ( $m = string_cut($rest, 'until end of turn') ) {
			$animated->eot = true ;
			$rest = $m['before'] ; // $m['after'] contains "it's still a land", special conditions or rules
		}
		if ( $m = string_cut($rest, 'creature') ) { // TODO : better parsing (color are lowercase then creatrue types are upercase then 'artifact'
			// Color, types, subtypes
			$ct = explode(' ', $m['before']) ;
			foreach ( $ct as $cot ) {
				if ( ( $cot == 'and' ) || ( $cot == '' ) )
					continue ;
				if ( $cot == 'artifact' ) {
					$animated->types[] = $cot ;
					continue ;
				}
				$i = array_search($cot, $colors) ;
				if ( $i !== false ) {
					if ( ! property_exists($animated, 'color') ) {
						$animated->color = '' ;
					}
					$animated->color .= $i ;
					continue ;
				}
				$animated->subtypes[] = strtolower($cot) ;
			}
			// Creature attributes
			$rest = $m['after'] ;
			if ( $ch = string_cut($rest, 'all creature types ') ) {
				$animated->changeling = true ;
				$rest = $ch['before'].$ch['after'] ;
			}
			apply_all_creat_attrs($animated, $m['after']) ;
			$target->animate[] = $animated ;
			return;
		}// else // Figure of destiny (is already a creature)
			//echo '['.$rest.']<br>' ;
	}
	if ( preg_match('/Crew \d/', $text, $matches) ) {
		$pt = "\d+" ;
		if ( preg_match('/^(?<pow>'.$pt.')\/(?<tou>'.$pt.')$/', $target->text[0], $matches) ) {
			$animated = new stdClass() ;
			$animated->cost = $text ;
			$animated->eot = true ;
			$animated->pow = intval($matches['pow']) ;
			$animated->tou = intval($matches['tou']) ;
			$target->animate[] = $animated ;
		} else {
			msg('powthou error for "Crew" '.$name.' : ['.$target->text[0].']') ;
		}
	}
	// After all rules that may detect those keywords are managed and have cut this function's execution
		// Upkeep trigger - TODO : apply this on a target such as creat attrs, in order this work via bonus/token/powtoucond/whatever
	if ( preg_match('/^(?<keyword>.*? - )?At the beginning of (?<step>.*?)( or (?<alt>.*?))?, (?<action>.*)/', $text, $matches) ) {
		$words = explode(' ', $matches['step']) ;
		// Filter words useless for parsing
		$words = array_filter($words, function($k) {
			$filter = array('step', 'phase', 'precombat', 'next') ;
			return ! in_array($k, $filter) ;
		}) ;
		$player = null ; // Self, opponent, both
		$step = '' ;
		switch ( $words[0] ) { // Read first word and try to guess player
			case 'each' :
				array_shift($words) ;
				switch ( $words[0] ) {
					case "opponent's" :
						$player = -1 ;
						array_shift($words) ;
						break ;
					case "player's" :
						$player = 0 ;
						array_shift($words) ;
						break ;
					case "other" :
						$player = -1 ;
						array_shift($words) ;
						if ( $words[0] == "player's" )
							array_shift($words) ;
						break ;
					case "of" :
						array_shift($words) ;
						$sent = implode(' ', $words) ;
						if ( $sent == 'that player\'s upkeeps' ) {
							$player = -1 ;
							$step = 'upkeep' ;
						} else if ( $sent == 'your main phases' ) {
							$player = 1 ;
							$step = 'main' ;
						}
					default : 
						$player = 0 ;
						//echo 'Unknown step : '.$name.' : '.implode(' ', $words)."\n" ;
				}
				if ( $step == '' ) {
					if ( $words[0] == 'first' ) {
						continue ;
					}
					if ( count($words) == 1 ) {
						$step = $words[0] ;
					} else {
						msg('"At the begining of each" multiple words left : '.$name.' : '.implode(' ', $words).' / '.$matches['step']) ;
					}
				}
				break ;
			case 'your' :
				$player = 1 ;
				array_shift($words) ;
				if ( $words[0] == 'first' ) {
					continue ;
				}
				if ( count($words) == 1 ) {
					$step = $words[0] ;
				} else {
					//msg('"At the begining of your" multiple words left : '.$name.' : '.implode(' ', $words).' / '.$matches['step']) ;
				}
				break ;
			case 'the' :
				array_shift($words) ;
				if ( $words[0] == 'first' )
					continue ;
				else if ( $matches['step'] == 'the chosen player\'s upkeep' ) {
					$player = -1 ;
					$step = 'upkeep' ;
				} else if ( count($words) == 1 ) {
					$step = $words[0] ;
					$player = 1 ;
				} else if ( $words[1] == 'of' ) {
					$step = $words[0] ;
					$player = 2 ;
				} else 
					echo 'Unknown step : '.$name.' : '.$matches['step']."\n" ;
				break ;
			case 'combat' :
				$step = 'combat' ;
				switch ( $matches['step'] ) {
					case 'combat on your turn':
						$player = 1 ;
						break ;
					case 'combat on each opponent\'s turn':
						$player = 0 ;
						break ;
					default:
						echo 'Unknown step : '.$name.' : '.$matches['step']."\n" ;
				}
				break ;
			case 'enchanted' :
				$player = -1 ;
				$step = 'upkeep' ;
				break ;
			default : 
				echo 'Unknown step : '.$name.' : '.$matches['step']."\n" ;
		}
		if ( ! in_array($step, array('upkeep', 'draw', 'main', 'combat', 'end', '')) )
			echo "Unknown step : $name - $step $player\n" ;
	}
	if ( preg_match('/At the beginning of your( next)? upkeep, (.*)/', $text, $matches) ) {
		$target->trigger_upkeep = stripslashes($matches[2]) ;
		return;
	}
	if ( preg_match('/At the beginning of the upkeep of (\w*) (\w*)\'s controller, (.*)/', $text, $matches) ) {
		if ( ! isset($target->bonus) )
			$target->bonus = new stdClass() ;
		$target->bonus->trigger_upkeep = stripslashes($matches[3]) ;
		return;
	}
		// Creature attributes after trigger as a trigger may contain one of those keyword that should not been added to card
	apply_all_creat_attrs($target, $text) ;
}
function string_cut($string, $cut) {
	$i = strpos($string, $cut) ;
	if ( $i === false )
		return false ;
	return array('before' => substr($string, 0, $i), 'after' => substr($string, $i+strlen($cut))) ;
}
function apply_creat_attrs($text, $attr, $target) {
	$attr_name = str_replace(' ', '_', $attr) ; // For attrs with a space in their name, such as "first strike"
	if ( stripos($text, $attr) !== false ) {
		$target->$attr_name = true ;
		return true ;
	}
	return false ;
}
function apply_all_creat_attrs($target, $text) {
	global $creat_attrs ;
	$result = 0 ;
	foreach ( $creat_attrs as $creat_attr ) {
		if ( apply_creat_attrs($text, $creat_attr, $target) ) {
			$result++ ;
		}
	}
	return $result;
}
function conditionnal_poly_boost($target, $matches, $text) { // Parses text after 'foreach'
	global $conds, $cardtypes ;
	if ( preg_match('/(?<other>other )?(?<what>.*)( card)? (?<where>'.implode('|', $conds).')( named (?<name>.*))?/', $text, $m) ) {
		$target->powtoucond = new stdClass() ;
		$target->powtoucond->what = 'cards' ;
		$target->powtoucond->pow = intval($matches['pow']) ;
		$target->powtoucond->thou = intval($matches['tou']) ;
		$target->powtoucond->from = array_search($m['where'], $conds) ;
		$what = str_replace(' card', '', strtolower($m['what'])) ;
		if ( array_search($what, $cardtypes) !== false )
			$target->powtoucond->cond = 'type='.$what ;
		else
			$target->powtoucond->cond = 'ctype='.$what ;
		if ( array_key_exists('name', $m) )
			$target->powtoucond->cond = 'name='.$m['name'] ;
		if ( array_key_exists('other', $m) && ( $m['other'] == 'other ') )
			$target->powtoucond->other = true ;
	} //else 
		//msg($name.' : '.$matches['pow'].'/'.$matches['tou'].' : '.$matches['what']) ;
}
function boost_bf($match) {
	$boost_bf = new stdClass() ;
	// Amount boosted ($match is passed instead of pow/tou because the mechanism may be used for non pow/tou boost)
	$boost_bf->pow = 0 ;
	$boost_bf->tou = 0 ;
	if ( isset($match['pow']) ) {
		$boost_bf->pow = intval($match['pow']) ;
	}
	if ( isset($match['tou']) ) {
		$boost_bf->tou = intval($match['tou']) ;
	}
	// Shall it affect self (invert "other")
	$boost_bf->self = true ;
	// Whose creatures are affected
	$boost_bf->control = 0 ; // Default : No "control" indication : crusade, lord of atlantis ...
	$boost_bf->eot = false ; // Permanent by default
	$boost_bf->enabled = true; // Enabled by default
	return $boost_bf ;
}
?>
