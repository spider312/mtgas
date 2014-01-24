<?php // Lib for limited in daemon
function booster_r_or_m($cards) {
	global $proba_m ;
	if ( ! array_key_exists('M', $cards) || count($cards['M']) == 0 ) { // No mythics
		if ( ( ! array_key_exists('R', $cards) ) || ( count($cards['R']) == 0 ) ) // And no rares
			return 'S' ; // TSB
		else
			return 'R' ;
	}
	if ( ! array_key_exists('R', $cards) || count($cards['R']) == 0 ) // No rares
		return 'M' ;
	// Rares and Mythics
	if ( rand(1, $proba_m) == 1 )
		return 'M' ;
	return 'R' ;
}
function property_or($obj, $property, $value=0) {
	if ( property_exists($obj, $property) )
		return $obj->$property ;
	else
		return $value ;
}
function booster_as_array_with_ext($oext=null, &$upool=null) {
	$ext = $oext->se ;
	// Get data from DB Booster
	$data = JSON_decode($oext->data) ;
	if ( isset($data->uniq) && intval($data->uniq) == 1 )
		$ext_cards = &$upool ;
	else
		$ucards = null ;
	$c = property_or($data, 'c', 0) ;
	$nb_u = property_or($data, 'u', 0) ;
	$nb_r = property_or($data, 'r', 0) ;
	$nb_l = property_or($data, 'l', 0) ;
	// Prepare result
	$object = new simple_object() ;
	$object->ext = $ext ;
	$result = array() ;
	global /*$nb_c, $nb_u, $nb_r, $nb_l, */$proba_foil, $card_connection ;
	$tr_ext = ( $ext == 'ISD' ) ||  ( $ext == 'DKA' ) ; // Virtual extensions (CUB, ALL) doesn't want 1 transform per booster, just DKA & ISD
	$cards = array() ; // Array of rarity, each rarity being an array of cards
	$cardsf = array() ; // Array of all extension's cards, for foils
	$cardst = array() ; // Array of all transformed cards
	// Get possible cards list
	$common_query = '	SELECT
		`card`.`name`,
		`card`.`attrs`,
		`card_ext`.`nbpics`,
		`card_ext`.`rarity`,
		`extension`.`se`
	FROM
		`card`,
		`card_ext`,
		`extension`
	WHERE
		`card`.`id` = `card_ext`.`card`
		AND `extension`.`id` = `card_ext`.`ext`
	' ; // Part of the query in common in each case (tables, fields, join)
	if ( $ext == 'ALL' ) // All cards from all ext that are core set or blocs (no special cards such as ung, promo, dual deck, FTV)
		$query = query($common_query.'AND `extension`.`bloc` >= 0 ; ', 'Get all cards', $card_connection) ;
	else // All cards in extension in param
		$query = query($common_query."AND `extension`.`se` = '$ext' ; ", 'Get cards in extension', $card_connection) ;
	if ( mysql_num_rows($query) < 1 ) {
		echo 'No card found in '.$ext ;
		return null ;
	}
	// Dispatch those cards in various list (rarity filtered, foils, transforms)
	while ( $row = mysql_fetch_object($query) ) {
		$attrs = json_decode($row->attrs) ;
		if ( isset($attrs->transformed_attrs) && $tr_ext )
			$a =& $cardst ;
		else
			$a =& $cards ;
		if ( ! array_key_exists($row->rarity, $a) )
			$a[$row->rarity] = array() ;
		$a[$row->rarity][] = $row ; // One copy in basic rarity filtered or transform array, depending on ext
		$cardsf[] = $row ; // One other copy for foils
	}
	// Commons are managed at the end, as foils, timeshifted and transform must be managed before
	// uncommons
	if ( array_key_exists('U', $cards) ) {
		for ( $i = 0 ; $i < $nb_u ; $i++ )
			$result[] = rand_card($cards['U'], $ext_cards) ;
	} else
		$c += $nb_u ;
	// rare or mythic
	if ( array_key_exists('R', $cards) ) {
		for ( $i = 0 ; $i < $nb_r ; $i++ )
			$result[] = rand_card($cards[booster_r_or_m($cards)], $ext_cards) ;
	} else
		$c += $nb_r ;
	// 1 timeshifted (for TSP)
	if ( ( $ext == 'TSP') && ( array_key_exists('S', $cards) ) ) {
		$c-- ;
		$card = rand_card($cards['S'], $ext_cards) ;
		$card->ext = 'TSB' ;
		$result[] = $card ;
	} 
	// 1 transformable (for ISD/DKA)
	if ( $tr_ext ) {
		$c-- ;
		$r = '' ;
		$n = rand(1, 14) ; // Transform rarity
		if ( $n > 13 ) // Rare or Mythic
			$r = booster_r_or_m($cardst) ;
		elseif ( $n > 10 ) // Unco
			$r = 'U' ;
		else // Common
			$r = 'C' ;
		$result[] = rand_card($cardst[$r], $ext_cards) ;
	}
	// 0-1 foil
	if ( ( rand(1, $proba_foil) == 1 ) && isset($data->uniq) && ( intval($data->uniq) != 0) ) { // CUB don't want foils, they break unicity
		$c-- ;
		$result[] = rand_card($cardsf, $ext_cards) ; // Uses it's own card list, so won't be removed from 'normal' cards lists
	}
	// land
	if ( array_key_exists('L', $cards) ) // 2nd extension from block doesn't have lands
		for ( $i = 0 ; $i < $nb_l ; $i++ )
			$result[] = rand_card($cards['L'], $ext_cards) ;
	// commons (after all other exceptions have been managed)
	if ( array_key_exists('C', $cards) && ( count($cards['C']) >= $c ) )
		for ( $i = 0 ; $i < $c ; $i++ )
			array_unshift($result, rand_card($cards['C'], $ext_cards)) ; // put on begining of booster
	else
		echo 'Not enough commons leftin ext '.$ext." ($c/".count($cards['C']).")\n" ;
	// Final copy
	$object->cards = array() ;
	foreach ( $result as $card )
		$object->cards[] = $card ;
	return $object ;
}
function rand_card(&$arr, &$addto=null) { // param by adrress, returned card will be added to card list
	if ( ! is_array($arr) ) {
		echo "rand_card called without a card array\n" ;
		return null ;
	}
	// Card search
	do {
		if ( count($arr) < 1 ) {
			echo "No cards left in random array\n" ;
			return null ;
		}
		$cards = array_splice($arr, mt_rand(0, count($arr)-1), 1) ;
		$card = $cards[0] ;
		if ( $addto === null ) // No unicity
			break ; // End of search
		else { // Unicity
			if ( ! in_array($card->name, $addto) ) {
				array_push($addto, $card->name) ;
				break ;
			} // else continue
		}
	} while ( true ) ;
	// Data preparation
	$name = $card->name ;
	if ( $card->nbpics > 1 ) // If multiple pics
		$name .= ' ('.rand(1, $card->nbpics).')' ; // Random pic
	$object = new simple_object() ; // Return as object
	$object->name = $name ;
	$object->ext = $card->se ;
	return $object ;
}
// Sealed specific functions
function pool_open($boosters, $name='', &$cards=null, $exts) {
	$margin = 'SB:' ; // Before build, cards are in sb
	$pool = '// Sealed pool for tournament '.$name."\n" ;
	foreach ( $boosters as $i => $ext ) {
		if ( intval($exts[$ext]->uniq) == 1 )
			$ucards = &$cards ;
		else
			$ucards = null ;
		$booster = booster_as_array_with_ext($exts[$ext], &$ucards) ;
		$pool .= mysql_real_escape_string("// Cards from booster ".($i+1)." ($ext)\n") ;
		foreach ( $booster->cards as $card ) {
			if ( isset($card->ext) )
				$xt = $card->ext ;
			else {
				echo "Card has no ext (ext = $ext)\n" ;
				$xt = 'EXT' ;
			}
			if ( isset($card->name) )
				$name = $card->name ;
			else {
				echo "Card has no name (ext = $ext)\n" ;
				$name = 'Unknown card name' ;
			}
			$pool .= mysql_real_escape_string($margin.' 1 ['.$xt.'] '.$name."\n") ;
		}
	}
	$pool .= add_side_lands() ;
	return $pool ;
}
function add_side_lands($ext='UNH', $nb=20) {
	return "// Lands in side
SB: $nb [$ext] Forest
SB: $nb [$ext] Island
SB: $nb [$ext] Mountain
SB: $nb [$ext] Plains
SB: $nb [$ext] Swamp" ;
}
// Draft specific functions
function draft_time($cards=15, $lastround=false) {
	global $draft_base_time, $draft_time_per_card, $draft_lastpick_time ;
	if ( ( $cards < 2 ) && ( ! $lastround ) ) // 1 card left and not last booster
		$result = $draft_lastpick_time ; // lastpick_time applied
	else
		$result = $draft_base_time + ( $draft_time_per_card * $cards ) ;
	return $result ;
}
function switch_booster($source, $dest, $tournament) {
	$query = "UPDATE
	`booster`
SET
	`player` = '$dest'
WHERE
	`tournament` = '".$tournament->id."'
	AND `player` = '$source'
	AND `number` = '".$tournament->round."'
;" ;
	query($query) ;
	$nb = mysql_matched_rows() ;
	if ( $nb != 1 )
		echo "$nb results affected by query $query\n" ;
}
function mysql_matched_rows() {
	$mi = mysql_info() ;
	$words = explode(' ', $mi) ;
	if ( count($words) > 3 )
		return $words[2] ;
	//echo "No row nb in mysql info : $mi\n" ;
	return 1 ;
}
?>
