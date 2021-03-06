<?php
$baseURL = 'https://api.scryfall.com/' ; // API URL
$basePath = 'cache/'.$source.'_'.$ext_source ; // Local cache file path
$imgPriorities = array('border_crop', 'large', 'normal') ; // border_crop ?

// Get set data
$setURL = $baseURL . 'sets/' . $ext_source ;
$importer->init($setURL) ;
$json = cache_get($setURL, $basePath, $verbose, false, $importer->cachetime) ;
$set = json_decode($json) ;
if ( $set === null ) {
	die("<a href=\"$setURL\">Unparsable JSON</a> : $json") ;
}
$importer->setext($set->code, $set->name, $set->card_count) ;

// Get cards pages
if ( $importer->type === 'all' ) {
	$pageURL = $set->search_uri ; // URL for first page, next page URL will be contained in result
} else {
	$booster = ( $importer->type === 'pwdecks' ) ? 'not' : 'is' ;
	$pageURL = 'https://api.scryfall.com/cards/search?q=set:'.$set->code.'&'.$booster.':booster&unique=prints' ; // First page URL has to be generated in order to contain selectors for "booster like" list
}
$data = get_cards($pageURL, $basePath, array()) ;

// Tokens
$tkURL = $baseURL . 'sets/t' . $ext_source ;
$tkPath = $basePath.'_tk' ;
$json = cache_get($tkURL, $tkPath, $verbose, false, $importer->cachetime) ;
$tk = json_decode($json) ;
if ( $tk !== null ) {
	$data = get_cards($tk->search_uri, $tkPath, $data) ;
} else {
	//die("<a href=\"$tkURL\">Unparsable JSON</a> : $json") ;
}

// Parse results
foreach ( $data as $card ) {
	if ( ( $importer->type === 'preview' ) && ( $card->rarity !== 'mythic' ) && ( $card->rarity !== 'rare') ) {
		continue ;
	}
	// URI
	$uri = $card->uri ;
	// Rarity
	$rarity = $card->rarity ; // Full word
	$rarity = substr($rarity, 0, 1) ;
	$rarity = strtoupper($rarity) ;
	// Faces
	$recto = $card ;
	$verso = null ;
	$imageFace = $card ; // Face hosting image, distinction between flip & transform
	// Manage layout
	switch ( $card->layout ) {
		case 'adventure' :
			$recto = $card->card_faces[0] ;
			break ;
		case 'augment' :
			if ( preg_match('/Augment (?<cost>.*) \(/', $card->oracle_text, $matches) ) {
				$card->mana_cost = $matches['cost'] ;
				$card->oracle_text .= "\n" . 'Enchanted creature gets ' . $card->power . '/' . $card->toughness ;
				$card->power = intval($card->power) ;
				$card->toughness = intval($card->toughness) ;
			} else {
				echo "Invalid syntax for {$card->name}" ;
			}
		case 'host' :
		case 'saga' :
		case 'leveler' :
		case 'normal' :
			if ( substr($card->type_line, 0, 5) === 'Basic' ) {
				$rarity = 'L' ;
			}
			break ;
		case 'transform' :
		case 'modal_dfc' :
			$imageFace = $card->card_faces[0] ; // Recto hosts main image for transform
		case 'flip' :
		case 'split' :
			$recto = $card->card_faces[0] ;
			$verso = $card->card_faces[1] ;
			$verso->color_identity = $card->color_identity ;
			break ;
		case 'token' :
			$power = property_exists($card, 'power') ? $card->power : 1 ; // As of M21, piratre has bug about pow/tou
			$toughness = property_exists($card, 'toughness') ? $card->toughness : 1 ;
			$importer->addtoken($uri, $card->name, $power, $toughness, get_image($card)) ;
			continue 2 ;
		case 'emblem' :
			$importer->addtoken($uri, $card->name, null, null, get_image($card)) ;
			continue 2 ;
		default :
			$importer->adderror('Unmanaged layout : '.$card->layout, $card->scryfall_uri) ;
			continue 2 ;
	}
	if ( property_exists($card, 'promo_types') ) {
		continue ;
	}
	// Name
	$name = $recto->name ;
	// Cost
	$cost = $recto->mana_cost ;
	$cost = str_replace('/', '', $cost) ; // Remove / from hybrids
	$cost = preg_replace('/{(.)}/', '\1', $cost) ; // Transform non-hybrid (1 char length) mana to mana letter
	// Types
	$types = $recto->type_line ;
	$types = str_replace('—', '-', $types) ; // Historical more-standard char
	// Image
	if ( ! property_exists($imageFace, 'image_uris') ) {
		$importer->adderror('No image', $card->scryfall_uri) ;
		continue;
	}
	$imgURI = get_image($imageFace) ;
	// Last minute management of importer type
	if ( $importer->type === 'preview' ) {
		$rarity = 'L' ;
		$imgURI = null ;
	}
	// Add card
	$imported = $importer->addcard($uri, $rarity, $name, $cost, $types, card2text($recto), $imgURI) ;
	if ( $imported === null ) {
		$importer->adderror('Card not added', $recto->scryfall_uri) ;
		continue ;
	}
	// Manage multi-faces layout
	if ( $verso !== null ) {
		$color = '' ;
		$nbcolors = count($verso->color_identity) ;
		if ( $nbcolors > 0 ) {
			$color = $verso->color_identity[count($verso->color_identity)-1] ;
		}
		switch ( $card->layout ) {
			case 'split' :
				$cost = str_replace(array('{', '}'), '', $verso->mana_cost) ; // Transform cost from icon representation to textual representation
				$imported->split($verso->name, $cost, $verso->type_line, card2text($verso)) ;
				break ;
			case 'flip' :
				$imported->flip($verso->name, $verso->type_line, card2text($verso)) ;
				break ;
			case 'transform' :
			case 'modal_dfc' :
				$versoImgURI = ( $importer->type === 'preview' ) ? null : $verso->image_uris->border_crop ;
				$imported->transform($verso->name, $color, $verso->type_line, card2text($verso), get_image($verso)) ;
				break ;
			default :
				die('Unknown verso layout : '.$card->layout) ;
		}
	}
}

function card2text($card) {
	$text = isset($card->oracle_text) ? $card->oracle_text : '' ; // Vanilla creatures doesn't have this field
	$text = str_replace('−', '-', $text) ; // Historical more-standard char
	$text = str_replace('•', '*', $text) ; // Historical more-standard char
	$text = preg_replace('#{(.)/(.)}#', '{\1\2}', $text) ; // Transform hybrid {A/B} mana to mogg format {AB}
	if ( property_exists($card, 'color_indicator') ) {
		global $colors ;
		$colors_name = '' ;
		foreach ( $card->color_indicator as $color ) {
			if ( array_key_exists($color, $colors) ) {
				$color_name = $colors[$color] ;
			} else {
				$color_name = 'unknown' ;
			}
			if ( $colors_name !== '' ) {
				$colors_name .= ' and ' ;
			}
			$colors_name .= $color_name ;
		}
		$text = $card->name.' is '.$colors_name."\n".$text ;
	}
	if ( isset($card->power) && isset($card->toughness) ) { // Add pow/tou for creatures
		$text = $card->power . '/'.$card->toughness."\n".$text ;
	}
	if ( isset($card->loyalty) ) { // Add loyalty info for planeswalkers
		$text = $card->loyalty . "\n" . $text ;
	}
	return $text ;
}

function get_cards($URL, $basePath, $data) {
	global $verbose, $importer ;
	$page = 0 ;
	do {
		// Fetch page
		$json = cache_get($URL, $basePath . '_p' . ($page++), $verbose, false, $importer->cachetime) ;
		if ( strlen($json) === 0 ) {
			die('Empty response '.$pageURL) ;
		}
		// Parse page
		$list = json_decode($json) ;
		$data = array_merge($data, $list->data) ;
		// Prepare next page fetch
		if ( isset($list->next_page) ) {
			$URL = $list->next_page ;
		} else {
			break ;
		}
	} while ( $list->has_more ) ;
	return $data ;
}

function get_image($imageFace) {
	global $imgPriorities, $importer ;
	$imgURI = null ;
	foreach ( $imgPriorities as $imgType ) {
		if ( property_exists($imageFace->image_uris, $imgType ) ) {
			$imgURI = $imageFace->image_uris->{$imgType} ;
			break ;
		}
	}
	if ( $imgURI === null ) {
		$importer->adderror('No image type '.implode(', ', $imgPriorities), $card->scryfall_uri) ;
	}
	return $imgURI ;
}

?>
