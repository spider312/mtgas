<?php
function game_create($game_name, $creator_nick, $creator_id, $creator_avatar, $creator_deck, $joiner_nick='', $joiner_id='', $joiner_avatar='', $joiner_deck='', $tournament=0, $round=0) {
	// Create game first to get its id
	$creator_score = 0 ;
	if ( $creator_id == $joiner_id ) // Goldfish
		$status = 3 ;
	else {
		if ( $tournament == 0 )
			$status = 1 ;
		else
			$status = 4 ;
	}
	query("INSERT INTO `round` (
		`status`,
		`creation_date`,
		`last_update_date`,
		`name`,
		`creator_nick`,
		`creator_id`,
		`creator_avatar`,
		`creator_deck`,
		`creator_score`,
		`joiner_nick`,
		`joiner_id`,
		`joiner_avatar`,
		`joiner_deck`,
		`tournament`,
		`round`
	) VALUES (
		'$status',
		NOW(),
		NOW(),
		'".addslashes($game_name)."',
		'$creator_nick',
		'$creator_id',
		'$creator_avatar',
		'$creator_deck',
		'$creator_score',
		'$joiner_nick',
		'$joiner_id',
		'$joiner_avatar',
		'$joiner_deck',
		'$tournament',
		'$round'
	) ;") ;
	$id = mysql_insert_id() ;
	if ( $id == 0 ) {
		$a = mysql_fetch_array(query("SELECT `id` FROM `round` ORDER BY `id` DESC LIMIT 0, 1 ;")) ;
		$id = $a['id'] ;
	}
	// Parse both decks
	parse_deck($id, 'game.creator', $creator_id, $creator_deck) ;
	if ( parse_deck($id, 'game.joiner', $joiner_id, $joiner_deck) ) // No joiner deck, we're in a duel, toss will be done on join
		game_toss($id) ;
	return $id ;
}
function game_toss($id) {
	$starter = mt_rand(0,1) ;
	switch ( $starter ) {
		case 0 : 
			$starter = 'game.creator' ;
			break ;
		case 1 : 
			$starter = 'game.joiner' ;
			break ;
		default :
			die('Starter errored value : '.$starter) ;
	}
	query("INSERT INTO `action` (
		`game`,
		`sender`,
		`local_index`,
		`recieved`,
		`type`,
		`param`
	) VALUES (
		'$id',
		'',
		'0',
		'0',
		'toss',
		'{\"player\":\"$starter\"}'
	);") ;

}
// Pure deck parsing (txt -> mysql)
function parse_deck($game, $player, $player_id, $deck) {
	$cards = deck2arr($deck) ;
	if ( count($cards->main) > 0 )
		parse_cards($game, $cards->main, $player.'.library', $player_id) ;
	if ( count($cards->side) > 0 )
		parse_cards($game, $cards->side, $player.'.sideboard', $player_id) ;
	return ( ( count($cards->main) + count($cards->side) ) > 0) ;
}
function parse_cards($game, $cards, $zone, $player_id) {
	foreach ( $cards as $card ) {
		$card->zone = $zone ;
		$escaped = addcslashes(json_encode($card), "'\"") ;
		//$escaped = addcslashes(json_encode($card), "'") ;
		query("INSERT INTO `action` (
			`game`,
			`sender`,
			`local_index`,
			`recieved`,
			`type`,
			`param`
		) VALUES(
			'$game',
			'',
			".time().",
			'0',
			'card',
			'$escaped'
		);") ;
	}
}
// Create deckfile from object deck
function obj2deck($obj) {
	$deck = "// Main deck\n" ;
	$deck .= arr2deck($obj->main) ;
	$deck .= "\n// Sideboard\n" ;
	$deck .= arr2deck($obj->side, 'SB:') ;
	return $deck ;
}
function arr2deck($arr, $prefix='   ') {
	$deck = '' ;
	while ( count($arr) > 0 ) {
		$spliced = array_splice($arr, 0, 1) ; // Extract a card
		$curval = $spliced[0] ;
		$nb = 1 ;
		$i = 0 ;
		while ( $i < count($arr) ) { // Search for other exemplaries
			$value = $arr[$i] ;
			if ( ( $value->ext == $curval->ext ) && ( $value->name == $curval->name ) ) {
				array_splice($arr, $i, 1) ; // Extract them
				$nb++ ; // And count
			} else
				$i++ ;
		}
		$deck .= $prefix.' '.$nb.' ['.$curval->ext.'] '.$curval->name."\n" ;
	}

	return $deck ;
}
// Parse deckfile into object deck
function deck2arr($deck, $nolands=false) {
	$result = object() ;
	$result->main = array() ;
	$result->side = array() ;
	$reg_comment = '/\/\/(.*)/' ;
	$reg_empty = "/^\n$/" ;
	$reg_card_mwd = '/(\d+)\s*\[(.*)\]\s*\b(.+)\b/' ;
	$reg_card_apr = '/(\d+)\s*\b(.+)\b/' ;
	$reg_side = '/^SB:(.*)$/' ;
	$card_connection = card_connect() ;
	$lines = explode("\n", $deck) ; // Cut file content in lines
	foreach ( $lines as $key => $value ) { // Parse lines one by one
		$side = false ; // By default, card goes maindeck
		$card = null ;
		if ( preg_match($reg_side, $value, $matches) ) { // Line indicates card goes in sideboard
			$value = $matches[1] ;
			$side = true ;
		}
		if ( preg_match($reg_comment, $value, $matches) ) { // Comment line in file
		} elseif ( preg_match($reg_empty, $value, $matches) ) { // Empty line in file
		} elseif ( preg_match($reg_card_mwd, $value, $matches) ) { // Card in MWS file format
			list($line, $nb, $ext, $name) = $matches ;
			$card = card2obj($card_connection, $name, $ext) ;
		} elseif ( preg_match($reg_card_apr, $value, $matches) ) { // Card in aprentice file format
			list($line, $nb, $name) = $matches ;
			$card = card2obj($card_connection, $name) ;
		}
		if ( $card != null ) {
			if ( $nolands && ( $card->rarity == 'L' ) )
				continue ;
			for ( $i = 0 ; $i < $nb ; $i++ ) {
				$clone = clone $card ; 
				if ( $side )
					$result->side[] = $clone ;
				else
					$result->main[] = $clone ;
			}
		}
	}
	return $result ;
}
// Parse card from deck file line
function card2obj($conn, $name, $ext='') {
	global $cards_cache ;
	$idx = '['.$ext.']'.$name ;
	if ( ! isset($cards_cache) )
		$cards_cache = array() ;
	else
		if ( array_key_exists($idx, $cards_cache) )
			return $cards_cache[$idx] ;
	if ( preg_match('/(.*) \((\d)/', $name, $matches) ) { // Extract number after card name, as it's the image ID for that extension
		$name = $matches[1] ; // Remove it from name
		$pic_num = intval($matches[2]) ;
	}
	$name = mysql_real_escape_string($name) ;
	$query = query("SELECT * FROM `card` WHERE `name` LIKE '$name'", 'Card get', $conn) ; // Get card from DB
	if ( $card = mysql_fetch_object($query) )
		$card->attrs = json_decode($card->attrs) ; // Decode compiled attrs from DB
	else // Card not in database, skip
		return null ;
	// Extension
	$query_b = query("SELECT * FROM `card_ext`, `extension` WHERE
		`card_ext`.`card` = '".$card->id."' AND
		`card_ext`.`ext` = '".ext_id($ext, $conn)."' AND
		`card_ext`.`ext` = `extension`.`id` AND
		`card_ext`.`nbpics` > 0
	ORDER BY `extension`.`priority` DESC, `extension`.`release_date` DESC", 'Card\'s extension', $conn) ;
	if ( mysql_num_rows($query_b) == 0 ) { // Card not existing for this extension
		$query_b = query("SELECT * FROM `card_ext`, `extension` WHERE
			`card_ext`.`card` = '".$card->id."' AND
			`extension`.`id` = `card_ext`.`ext` AND
			`card_ext`.`nbpics` > 0
		ORDER BY `extension`.`priority` DESC, `extension`.`release_date` DESC", 'Card\'s extensions', $conn) ; // All extensions for this card
	}
	if ( $ext_row = mysql_fetch_object($query_b) ) { // At least a result found in one of the queries
		$card->ext = $ext_row->se ;
		$card->rarity = $ext_row->rarity ;
	} else
		return null ; // Some info from extension are required (ext itself and image num required for image displaying)
	// Pic num (requires card and extension)
	if ( $ext_row->nbpics > 1 ) // This card has many pics in that extension
		if ( isset($pic_num) ) { // One was in name passed in param
			if ( $pic_num <= $ext_row->nbpics ) // It's consistent
				$card->attrs->nb = $pic_num ; // Set it
			else // It's not consistent (e.g. 4 despite there are only 3 pics defined in extension)
				$card->attrs->nb = $ext_row->nbpics ; // Set pic num to last
		} else // No pic num in "deckfile", but card in extension has many pictures
			$card->attrs->nb = 1 ; // Set pic num to first
	// Merge attrs and fixed_attrs
	if ( $card->fixed_attrs != '' ) {
		$fixed_attrs = json_decode($card->fixed_attrs) ;
		if ( $fixed_attrs != null )
			foreach($fixed_attrs as $k => $v)
				$card->attrs->$k = $v ; // Overwrites array attrs such as tokens
	}
	unset($card->fixed_attrs) ;
	unset($card->text) ; // Don't send card text, as it's a pain to escape (JSON parser don't like some chars) and useless
	$cards_cache[$idx] = $card ;
	return $card ;
}
?>
