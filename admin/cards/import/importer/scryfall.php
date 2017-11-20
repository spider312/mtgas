<?php
$baseURL = 'https://api.scryfall.com/' ; // API URL
$basePath = 'cache/'.$source.'_'.$ext_source ; // Local cache file path

// Get set data
$setURL = $baseURL . 'sets/' . $ext_source ;
$importer->init($setURL) ;
$json = cache_get($setURL, $basePath, $verbose, false, $importer->cachetime) ;
$set = json_decode($json) ;
$importer->setext($set->code, $set->name, $set->card_count) ;

// Get cards pages
$pageURL = $set->search_uri ; // URL for first page, next page URL will be contained in result
$data = array() ;
$page = 0 ;
do {
	// Fetch page
	$json = cache_get($pageURL, $basePath . '_p' . ($page++), $verbose, false, $importer->cachetime) ;
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

$imgPriorities = array('large', 'normal') ; // border_crop ?
// Parse results
foreach ( $data as $card ) {
	// URI
	$uri = $card->uri ;
	// Rarity
	$rarity = $card->rarity ; // Full word
	$rarity = substr($rarity, 0, 1) ;
	$rarity = strtoupper($rarity) ;
	$verso = null ;
	// Img
	if ( ! property_exists($card, 'image_uris') ) {
		$importer->adderror('No image', $card->scryfall_uri) ;
		continue;
	}
	$imgURI = null ;
	foreach ( $imgPriorities as $imgType ) {
		if ( property_exists($card->image_uris, $imgType ) ) {
			$imgURI = $card->image_uris->{$imgType} ;
			break ;
		}
	}
	if ( $imgURI === null ) {
		$importer->adderror('No image type '.implode(', ', $imgPriorities), $card->scryfall_uri) ;
	}
	// Manage layout
	switch ( $card->layout ) {
		case 'normal' :
			break ;
		case 'transform' :
			$verso = $card->card_faces[1] ;
			$verso->color_identity = $card->color_identity ;
			$card = $card->card_faces[0] ;
			break ;
		case 'token' :
			$importer->addtoken($uri, $card->name, $card->power, $card->toughness, $imgURI) ;
			continue 2 ;
		default :
			$importer->adderror('Unmanaged layout : '.$card->layout, $card->scryfall_uri) ;
			continue 2 ;
	}
	// Cost
	$cost = $card->mana_cost ;
	$cost = str_replace('/', '', $cost) ; // Remove / from hybrids
	$cost = preg_replace('/{(.)}/', '\1', $cost) ; // Transform non-hybrid (1 char length) mana to mana letter
	// Types
	$types = $card->type_line ;
	$types = str_replace('—', '-', $types) ; // Historical more-standard char
	// Add card
	$imported = $importer->addcard($uri, $rarity, $card->name, $cost, $types, card2text($card), $imgURI) ;
	if ( $imported === null ) {
		$importer->adderror('Card not added', $card->scryfall_uri) ;
		continue ;
	}
	// Manage multi-faces layout
	if ( $verso !== null ) {
		$color = '' ;
		$nbcolors = count($verso->color_identity) ;
		if ( $nbcolors > 0 ) {
			$color = $verso->color_identity[count($verso->color_identity)-1] ;
		}
		$imported->transform($verso->name, $color, $verso->type_line, card2text($verso), $verso->image_uris->border_crop) ;
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
