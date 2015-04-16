<?php
// Init
$importer->init('http://mtgjson.com/json/'.strtoupper($ext_source).'-x.json') ;
$json = cache_get($importer->url, 'cache/'.$source.'_'.$ext_source, $verbose) ;
$json = JSON_decode($json) ;
$nbcards = 0 ;
foreach ( $json->cards as $card )
	switch ( $card->layout ) {
		case 'normal' :
			$nbcards++ ;
			break ;
		case 'split' :
			$nbcards += 0.5 ;
			break ;
		case 'token' :
			//print_r($card) ;
			//$importer->addtoken($url, $name, $pow, $tou, card_image_url($mv_ext_name.'/'.$mv_card_id)) ;
			break ;
		default :
			d('Unknown layout : '.$card->layout) ;
	}
$importer->setext($json->code, $json->name, $nbcards) ;
$lastcard = null ;
$langcode = array(
	'German' =>'de', 'French' => 'fr', 'Italian' => 'it', 'Spanish' => 'es', 'Portuguese' => '', 'Portuguese (Brazil)' => 'pt', // Allowed
	'Russian' => '','Japanese' => '','Chinese Traditional' => '','Chinese Simplified' => '', 'Korean' => '' // Forbidden (charset)
) ;
$splits = array() ;
foreach ( $json->cards as $card ) {
	// Rarity
	$rarity = substr($card->rarity, 0, 1) ;
	if ( $card->rarity == 'Basic Land' )
		$rarity = 'L' ;
	// Cost
	if ( property_exists($card, 'manaCost') )
		$cost = preg_replace('/\{(.)\}/', "$1", $card->manaCost) ;
	else
		$cost = '' ;
	// Types
	$type = str_replace('—', '-', $card->type) ;
	// Text
	if ( ! property_exists($card, 'text') )
		$text = '' ;
	else
		$text = str_replace(
			array("\n\n", '—', '−'),
			array("\n", '-', '-'),
			$card->text) ;
	// Creatures
	if ( property_exists($card, 'power') && property_exists($card, 'toughness') )
		$text = $card->power.'/'.$card->toughness."\n".$text ;
	// Planeswalkers
	if ( property_exists($card, 'loyalty') )
		$text = $card->loyalty."\n".$text ;
	// Action depending on layout
	switch ( $card->layout ) {
		case 'normal' :
			break ;
		case 'split' :
			// First part will have its card created as usual
			if ( $card->name != $card->names[0] ) { // This is not a first part
				$card->cost = $cost ;
				$card->type = $type ;
				$card->text = $text ;
				$splits[] = $card ; // Manage it after having added all cards
				continue 2 ;
			}
			break ;
		case 'token' :
			continue 2 ;
		default :
			d('Unknown layout : '.$card->layout) ;
	}
	// All
	$lastcard = $importer->addcard(
		$importer->url,
		$rarity,
		$card->name,
		$cost,
		$type,
		$text,
		'http://mtgimage.com/set/'.strtoupper($ext_source).'/'.$card->imageName.'.jpg',
		$card->multiverseid) ;
	if ( property_exists($card, 'foreignNames') )
		foreach ( $card->foreignNames as $fname )
			if ( ! array_key_exists($fname->language, $langcode) )
				echo "{$fname->language} not found" ;
			elseif ( $langcode[$fname->language] != '' )
				$lastcard->setlang($langcode[$fname->language], $fname->name) ;
}
foreach ( $splits as $card ) {
	$split = $importer->search($card->names[0]) ;
	if ( $split == null )
		echo "Split can't find {$card->names[0]}\n" ;
	else {
		$split->split($card->names[1], $card->cost, $card->type, $card->text) ;
		if ( property_exists($card, 'foreignNames') )
			foreach ( $card->foreignNames as $fname )
				if ( ! array_key_exists($fname->language, $langcode) )
					echo "{$fname->language} not found" ;
				else if ( $langcode[$fname->language] != '' )
					$split->addlang($langcode[$fname->language], $fname->name) ;
	}
}

