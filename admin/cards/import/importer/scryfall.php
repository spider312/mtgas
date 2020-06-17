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
	$pageURL = 'https://api.scryfall.com/cards/search?q=e:'.$set->code.'+'.$booster.'=booster&unique=prints' ; // First page URL has to be generated in order to contain selectors for "booster like" list
}
$data = array() ;
$page = 0 ;
do {
	// Fetch page
	$json = cache_get($pageURL, $basePath . '_p' . ($page++), $verbose, false, $importer->cachetime) ;
	if ( strlen($json) === 0 ) {
		die('Empty response '.$pageURL) ;
	}
	// Parse page
	$list = json_decode($json) ;
	$data = array_merge($data, $list->data) ;
	// Prepare next page fetch
	if ( isset($list->next_page) ) {
		$pageURL = $list->next_page ;
	} else {
		break ;
	}
} while ( $list->has_more ) ;

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
			$imageFace = $card->card_faces[0] ; // Recto hosts main image for transform
		case 'flip' :
			$recto = $card->card_faces[0] ;
			$verso = $card->card_faces[1] ;
			$verso->color_identity = $card->color_identity ;
			break ;
		case 'split' :
			$recto = $card->card_faces[0] ;
			$verso = $card->card_faces[1] ;
			$verso->color_identity = $card->color_identity ;
			break ;
		case 'token' :
			$importer->addtoken($uri, $card->name, $card->power, $card->toughness, $imgURI) ;
			continue 2 ;
		default :
			$importer->adderror('Unmanaged layout : '.$card->layout, $card->scryfall_uri) ;
			continue 2 ;
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
				$versoImgURI = ( $importer->type === 'preview' ) ? null : $verso->image_uris->border_crop ;
				$imported->transform($verso->name, $color, $verso->type_line, card2text($verso), $versoImgURI) ;
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
	if ( isset($card->power) && isset($card->toughness) ) { // Add pow/tou for creatures
		$text = $card->power . '/'.$card->toughness."\n".$text ;
	}
	if ( isset($card->loyalty) ) { // Add loyalty info for planeswalkers
		$text = $card->loyalty . "\n" . $text ;
	}
	return $text ;
}

?>
