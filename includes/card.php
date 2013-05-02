<?php
// === [ DATABASE ] ============================================================
function card_search($get, $connec=null) {
	if ( isset($get['page']) ) {
		$page = intval($get['page']) ;
		unset($get['page']) ;
	} else
		$page = 1 ;
	if ( isset($get['limit']) ) {
		$limit = intval($get['limit']) ;
		unset($get['limit']) ;
	} else
		$limit = 30 ;
	$from = ($page-1)*$limit ;
	$mode = 'exact' ;
	$select = 'SELECT * FROM `card` ' ;
	$order = ' ORDER BY `card`.`name`' ;
	$result = query($select.get2where($get, 'LIKE', '', '').$order, 'Card listing', $connec) ; // First, search exactly
	if ( mysql_num_rows($result) == 0 ) { // No results
		$mode = 'begin' ;
		$result = query($select.get2where($get, 'LIKE', '', '%').$order, 'Card listing', $connec) ; // Search by begin of word
		if ( mysql_num_rows($result) == 0 ) { // No results
			$mode = 'whole' ;
			$result = query($select.get2where($get, 'LIKE', '%', '%').$order, 'Card listing', $connec) ; // Search part of word
		}
	}
	$data = new simple_object() ;
	$data->mode = $mode ;
	$data->num_rows = mysql_num_rows($result) ;
	$data->page = $page ;
	$data->limit = $limit ;
	$data->cards = array() ;
	while ( ( $from > 0 ) && ( $obj = mysql_fetch_object($result) ) )
		$from-- ;
	while ( ( $obj = mysql_fetch_object($result) ) && ( count($data->cards) < $limit ) ) {
		$query = query("SELECT extension.id, extension.se, extension.name, card_ext.nbpics FROM card_ext, extension WHERE card_ext.nbpics != 0 AND card_ext.card = '".$obj->id."' AND card_ext.ext = extension.id ORDER BY extension.priority DESC", 'Card\' extension', $connec) ;
		$obj->ext = array() ;
		while ( $obj_ext = mysql_fetch_object($query) )
			$obj->ext[] = $obj_ext ;
		$data->cards[] = $obj ;
	}
	return $data ;
}
function ext_id($se='', $conn=null) {
	$query = query("SELECT id FROM extension WHERE `se` = '$se'", 'ext2id', $conn) ;
	if ( mysql_num_rows($query) == 1 ) {
		if ( $arr = mysql_fetch_array($query) ) 
			return $arr['id'] ;
		else
			return -1 ;
	} else 
		return -2 ;
}
function ext_se($id=-1, $conn=null) {
	$query = query("SELECT * FROM extension WHERE `id` = '$id'", 'id2ext', $conn) ;
	if ( mysql_num_rows($query) == 1 ) {
		if ( $arr = mysql_fetch_array($query) ) 
			return $arr['se'] ;
		else
			return '' ;
	} else 
		return '' ;

}
function card_id($name, $conn=null) {
	$name = mysql_real_escape_string($name) ;
	$query = query("SELECT id FROM card WHERE `name` = '$name'", 'cardname2id', $conn) ;
	if ( mysql_num_rows($query) == 1 ) {
		if ( $arr = mysql_fetch_array($query) )
			return $arr['id'] ;
		else
			return -1 ;
	} else 
		return -2 ;
}
// === [ LIB ] =================================================================
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
	echo "<br>\n" ;
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
			$color = $colors[$i] ;
			if ( isint($color) ) // Hybrid colored / colorless, ignore colorless part
				continue ;
			if ( $color == 'X' ) // X in cost isn't a color
				continue ;
			if ( $color == 'P' ) // Phyrexian mana
				continue ;
			if ( strpos($this->color, $color) === false )
				$this->color .= $color ;
		}
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
				foreach ( $this->manas as $mana ) { // mana symbols
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
				if ( $this->color_index < 0 )
					die('Color index error for ['.$this->color.'] '.$arr['name']) ;
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
					$transform = new simple_object() ;
					$matches = explode("\n", $pieces[1]) ;
					if ( count($matches) > 0 )
						$transform->name = stripslashes(array_shift($matches)) ;
					if ( count($matches) > 0 ) {
						$t = array_shift($matches) ;
						$reg = '/\%(\S+) (.*)/s' ;
						$transform->color = 'X' ;
						if ( preg_match($reg, $t, $matches_t) ) {
							$transform->color = $matches_t[1] ;
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
					// Dual / Flip
					$pieces = explode("\n----\n", $arr['text']) ;
					if ( count($pieces) > 1 ) {
						manage_all_text($arr['name'], $pieces[0], $this) ;
						$matches = explode("\n", $pieces[1]) ;
						if ( strpos($arr['name'], '/') === false ) { // No "/" in name, it's a flip
							$flip = new simple_object() ;
							if ( count($matches) > 0 )
								manage_types(array_shift($matches), $flip) ;
							$this->flip = $flip ;
							manage_all_text($arr['name'], implode("\n", $matches), $flip) ;
						} else { // "/" in name, it's a dual
							$dual = new simple_object() ;
							if ( count($matches) > 0 )
								$dual->manas = cost_explode(array_shift($matches)) ;
							if ( count($matches) > 0 )
								manage_types(array_shift($matches), $dual) ;
							$this->dual = $dual ;
							manage_all_text($arr['name'], implode("\n", $matches), $this) ;
							// Apply colors to initial card
							foreach ( $dual->manas as $mana ) // mana symbols
								if ( ! isint($mana) ) // Is a mana
									if ( $mana != 'X' ) // X is worth 0 and no color
										$this->add_color($mana) ;

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
	global $cardtypes ;
	$type = strtolower($type) ;
	$target->types = array() ;
	if ( preg_match('/(.*) - (.*)/', $type, $matches) ) {
		$type = $matches[1] ;
		if ( count($matches[2]) > 0 )
			$target->subtypes = explode(' ', $matches[2]) ;
	}
	foreach ( explode(' ', $type) as $type ) {
		if ( array_search($type, $cardtypes) !== false )
			$target->types[] = $type ;
		else
			$target->supertypes[] = $type ;
	}
}
function color_compare($a, $b) {
	global $colorscode ;
	return array_search($a, $colorscode) - array_search($b, $colorscode) ;
}
$colors = array('X' => 'colorless', 'W' => 'white', 'U' => 'blue', 'B' => 'black', 'R' => 'red', 'G' => 'green') ;
$colorscode = array_keys($colors) ; // For ordering
$allcolorscode = array('', 'X', 'W', 'U', 'B', 'R', 'G', 'WU','WB','UB','UR','BR','BG','RG','RW','GW','GU','WUB','UBR','BRG','RGW','GWU','WBR','URG','BGW','RWU','GUB','WUBR','UBRG','BRGW','RGWU','GWUB','WUBRG') ;
$cardtypes = array('artifact', 'creature', 'enchantment', 'instant', 'land', 'planeswalker', 'sorcery', 'tribal') ;
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
	$pt = '[\d\*\+\-\.]*' ; // Numerics, *, + for *+1, - for *-1, . for unhinged half pow/tou points
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
			if ( preg_match('/You get an emblem with /', $piece, $matches) ) {
				$token = new simple_object() ;
				$token->nb = 1 ;
				$token->name = 'Emblem.'.$target->subtypes[0] ;
				$token->attrs = new simple_object() ;
				$token->attrs->types = array('emblem') ;
				manage_text($name, $piece, $token->attrs) ;
				$target->tokens[] = $token ;
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
	foreach ( $text_lines as $text_line ) {
		$text_line = trim($text_line, ' .') ;
		manage_text($name, $text_line, $target) ;
	}
}
function manage_text($name, $text, $target) {
	// Various types
	global $manacost, $boost, $boosts, $cardtypes, $colors ;
	// Workarounds
	$text = str_replace('comes into play', 'enters the battlefield', $text) ; // Old style CIP
	$text = preg_replace('/\(.*\)/', '', $text) ; // Remove reminder texts as they can interfere in parsing (vanishing reminder has text for upkeep trigger for exemple)
	// Card attributes
		// In hand
	if ( preg_match('/Cycling ('.$manacost.')/', $text, $matches) )
		$target->cycling = manacost($matches[1]) ;
	if ( preg_match('/Morph ('.$manacost.')/', $text, $matches) )
		$target->morph = manacost($matches[1]) ;
	if ( preg_match('/Suspend (\d+)-('.$manacost.')/', $text, $matches) ) {
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
	// Permanents attributes
	if ( preg_match('/Vanishing (\d+)/', $text, $matches) )
		$target->vanishing = intval($matches[1]) ;
	if ( preg_match('/Fading (\d+)/', $text, $matches) )
		$target->fading = intval($matches[1]) ;
	if ( preg_match('/Echo ('.$manacost.')/', $text, $matches) )
		$target->echo = manacost($matches[1]) ;
	if ( preg_match('/Modular (\d+)/', $text, $matches) ) {
		$target->counter += intval($matches[1]) ;
		$target->note = '+1/+1' ;
	}
	if ( preg_match('/Graft (\d+)/', $text, $matches) ) {
		$target->counter += intval($matches[1]) ;
		$target->note = '+1/+1' ;
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
							if ( $matches[1] == 'unless you control two or fewer other lands' )
								$target->ciptc = 'this.controler.controls({"types": "land"})>3' ;
							elseif ( preg_match('/^unless you control an? (.*) or an? (.*)$/', $matches[1], $matches ) ) {
								$target->ciptc = '(this.controler.controls({"subtypes": "'.strtolower($matches[1]).'"})==0)' ;
								$target->ciptc .= '&&(this.controler.controls({"subtypes": "'.strtolower($matches[2]).'"})==0)' ;
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
			} /*else
				echo ' - '.$name.' : '.$txt.'<br>' ;*/
		}
	}
		// Hideaway
	if ( stripos($text, 'Hideaway') !== false )
		$target->tapped = true ;
		// Untap
	if ( stripos($text, $name.' doesn\'t untap during your untap step') !== false )
		$target->no_untap = true ;
		// Upkeep trigger
	if ( preg_match('/At the beginning of your upkeep, (.*)/', $text, $matches) )
		$target->trigger_upkeep = stripslashes($matches[1]) ;
	// Creature attributes (permanent attributes for exalt)
	global $creat_attrs ;
	foreach ( $creat_attrs as $creat_attr )
		apply_creat_attrs($text, $creat_attr, $target) ;
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
				$target->powtoucond = new simple_object() ;
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
				//msg($name.' : '.$target->powtoucond->what.' '.$target->powtoucond->from.' : '.$target->powtoucond->cond) ;
			}
		}
		// Conditionnal mono boost (+1/+2 as long as ...)
		if ( preg_match('/'.$name.' gets '.$boosts.' as long as (?<what>.*)/', $text, $matches ) ) { // Single
			$what = strtolower($matches['what']) ;
			if ( $what == 'seven or more cards are in your graveyard' ) {
			} elseif ( preg_match('/(?<who>.*) controls? (?<what>.*)/', $what, $m) ) {
				switch ( $m['who'] ) {
					case 'you' : // Kird ape
						$target->powtoucond = new simple_object() ;
						$target->powtoucond->what = 'card' ;
						$target->powtoucond->pow = intval($matches['pow']) ;
						$target->powtoucond->thou = intval($matches['tou']) ;
						$target->powtoucond->from = 'battlefield' ;
						if ( preg_match('/an? (.*)/', $m['what'], $mm) ) {
							if ( array_search($mm[1], $cardtypes)!= false  )
								$target->powtoucond->cond = 'type='.$mm[1] ;
							else
								$target->powtoucond->cond = 'ctype='.$mm[1] ;
						}/* else
							msg($name.' : '.$m['what']) ;*/
						break ;
					case 'an opponent' :
					case 'no opponent' :
						break ;
					default:
						//msg($name.' : '.$m['who'].' -> '.$m['what']) ;
				}
			//} elseif ( preg_match('/(?<who>.*) [has|have] (?<what>.*)/', $what, $m) ) {
			} /*else
				msg(' * '.$name.' : '.$matches['pow'].'/'.$matches['tou'].' : '.$matches['what']) ;*/
		}
		// Conditionnal poly boost (+1/+1 for each ...)
		if ( preg_match('/'.$name.' gets '.$boosts.' for each (?<what>.*)/', $text, $matches ) )
			conditionnal_poly_boost($target, $matches, $matches['what']) ;
	}
	// Attach/Equip-boost
	if ( preg_match('/(Equipped|Enchanted) creature gets '.$boosts.'(?<after>.*)/', $text, $matches) ) {
		if ( strpos($matches['after'], 'until end of turn') === FALSE ) { // Umezawa's Jitte
			if ( preg_match('/for each (?<what>.*)/', $matches['after'], $matches_after) ) {
				conditionnal_poly_boost($target, $matches, $matches_after['what']) ;
			} else {
				if ( ! isset($target->bonus) )
					$target->bonus = new simple_object() ;
				$target->bonus->pow = intval($matches['pow']) ;
				$target->bonus->tou = intval($matches['tou']) ;
				global $creat_attrs ;
				foreach ( $creat_attrs as $creat_attr ) // Also parse keywords such as vigilance, lifelink ...
					apply_creat_attrs($matches[4], $creat_attr, $target->bonus) ;
			}
		}
	}
	if ( preg_match('/(Equipped|Enchanted) creature doesn\'t untap during its controller\'s untap step/', $text, $matches) ) {
		if ( ! isset($target->bonus) )
			$target->bonus = new simple_object() ;
		$target->bonus->no_untap = true ;
	}
	// Living weapon
	if ( strpos($text, 'Living weapon') !== false )
		$target->living_weapon = true ;
	// Token creation
	$last_working_reg = '/(?<number>\w+) (?<pow>\d|X|\*+)\/(?<tou>\d|X|\*+) (?<color>\w+)( and (?<color2>\w+))* (?<name>[\w| ]+) creature token/' ;
	$test_reg = '/(?<number>\w+) [legendary ]?(?<pow>\d|X|\*+)\/(?<tou>\d|X|\*+) (<color>.*) creature token/' ;
	if ( preg_match_all($last_working_reg, $text, $all_matches, PREG_SET_ORDER) ) {
		foreach ( $all_matches as $matches ) {
			$token = new simple_object() ;
			$token->nb = text2number($matches['number'], 1) ; // Override X value with 1 to put at least 1 token
			$token->attrs = new simple_object() ;
			$token->attrs->types = array('creature') ;
			$name = $matches['name'] ;
			$pos = strpos($name, ' artifact') ;
			if ( $pos > -1 ) {
				$token->attrs->types[] = 'artifact' ;
				$name = substr($name, 0, $pos) ;
			}
			$token->name = $name ;
			$token->attrs->pow = text2number($matches['pow'], 0) ; // 0 for image
			$token->attrs->thou = text2number($matches['tou'], 0) ;
			$token->attrs->color = array_search($matches['color'], $colors) . array_search($matches['color2'], $colors) ;
			$token->attrs->subtypes = explode(' ', strtolower($token->name)) ;
			$target->tokens[] = $token ;
		}
	}
	// All creatures booster (crusade like)
	if ( preg_match_all('/(?<other>other )?(?<cond>\w*? )?creature(?<token> token)?s (?<control>you control )?get (?<pow>'.$boost.')\/(?<tou>'.$boost.')(?<attrs> and .*)?/', strtolower($text), $matches, PREG_SET_ORDER) ) {
		foreach ( $matches as $match ) {
			$boost_bf = new simple_object() ;
			$boost_bf->self = ( $match['other'] != 'other ' );
			$boost_bf->control = ( $match['control'] == 'you control ' ) ;
			$boost_bf->pow = intval($match['pow']) ;
			$boost_bf->tou = intval($match['tou']) ;
			if ( $match['token'] != '' )
				$boost_bf->cond = 'class=token' ;
			$cond = trim($match['cond']) ;
			if ( $cond != '' ) {
				$ci = array_search($cond, $colors) ;
				if ( $ci !== false )
					$boost_bf->cond = "color=$ci" ;
				else {
					$types = explode(' and ', $cond) ;
					foreach ( $types as $i => $type ) {
						if ( $type == 'artifact' )
							$types[$i] = "type=$type" ;
						else
							$types[$i] = "ctype=$type" ;
					}
					$boost_bf->cond = implode('|', $types) ;
				}
			}
			if ( array_key_exists('attrs', $match) ) {
				global $creat_attrs ;
				foreach ( $creat_attrs as $creat_attr )
					apply_creat_attrs($match['attrs'], $creat_attr, $boost_bf) ;
				//msg($name.' : '.print_r($boost_bf, true)) ;
			}
			$target->boost_bf[] = $boost_bf ;
		}
	}
	// Animate
	if ( preg_match('/((?<cost>.*)\s*:\s*)?(?<eot>Until end of turn, )?'.addcslashes($name, '/').' (.* it )?becomes an? (?<pow>\d)\/(?<tou>\d) (?<rest>.*)/', $text, $matches) ) {
		$animated = new simple_object() ;
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
			global $creat_attrs ;
			foreach ( $creat_attrs as $creat_attr )
				apply_creat_attrs($m['after'], $creat_attr, $animated) ;
			$target->animate[] = $animated ;
		}// else // Figure of destiny (is already a creature)
			//echo '['.$rest.']<br>' ;
	}
}
function string_cut($string, $cut) {
	$i = strpos($string, $cut) ;
	if ( $i === false )
		return false ;
	return array('before' => substr($string, 0, $i), 'after' => substr($string, $i+strlen($cut))) ;
}
function apply_creat_attrs($text, $attr, $target) {
	$attr_name = str_replace(' ', '_', $attr) ; // For attrs with a space in their name, such as "first strike"
	if ( stripos($text, $attr) !== false )
		$target->$attr_name = true ;
}
function conditionnal_poly_boost($target, $matches, $text) { // Parses text after 'foreach'
	global $conds, $cardtypes ;
	if ( preg_match('/(?<other>other )?(?<what>.*)( card)? (?<where>'.implode('|', $conds).')( named (?<name>.*))?/', $text, $m) ) {
		$target->powtoucond = new simple_object() ;
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
?>
