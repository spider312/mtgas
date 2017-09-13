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

// Parse results
foreach ( $data as $card ) {
	// Modify some data to fit mogg database
		// Rarity
	$rarity = $card->rarity ; // Full word
	$rarity = substr($rarity, 0, 1) ;
	$rarity = strtoupper($rarity) ;
		// Cost
	$cost = $card->mana_cost ;
	$cost = str_replace('/', '', $cost) ; // Remove / from hybrids
	$cost = preg_replace('/{(.)}/', '\1', $cost) ; // Transform non-hybrid (1 char length) mana to mana letter
		// Types
	$types = $card->type_line ;
	$types = str_replace('—', '-', $types) ; // Historical more-standard char
		// Build text
	$text = isset($card->oracle_text) ? $card->oracle_text : '' ; // Vanilla creatures doesn't have this field
	$text = str_replace('•', '*', $text) ; // Historical more-standard char
	$text = preg_replace('#{(.)/(.)}#', '{\1\2}', $text) ; // Transform hybrid {A/B} mana to mogg format {AB}
	if ( isset($card->power) && isset($card->toughness) ) { // Add pow/tou for creatures
		$text = $card->power . '/'.$card->toughness."\n".$text ;
	}
	// Add card
	$card = $importer->addcard($card->scryfall_uri, $rarity, $card->name, $cost, $types, $text, $card->image_uri) ;
	if ( ! $card ) {
		$importer->adderror('Card not added', $card->scryfall_uri) ;
		continue ;
	}
}

?>
