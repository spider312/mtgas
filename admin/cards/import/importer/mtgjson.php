<?php
// Init
$importer->init('http://mtgjson.com/json/'.strtoupper($ext_source).'-x.json') ;
$json = cache_get($importer->url, 'cache/'.$source.'_'.$ext_source, $verbose) ;
$json = JSON_decode($json) ;
// Just count cards to initialize importer
$nbcards = 0 ;
foreach ( $json->cards as $card )
	switch ( $card->layout ) {
		case 'normal' :
			$nbcards++ ;
			break ;
		case 'split' : // 2 lines in source for 1 card in mogg DB
		case 'double-faced' :
			$nbcards += 0.5 ;
			break ;
		case 'token' :
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
$transforms = array() ;
foreach ( $json->cards as $card ) {
	// Rarity
	$rarity = substr($card->rarity, 0, 1) ;
	if ( $card->rarity == 'Basic Land' )
		$rarity = 'L' ;
	if ( isset($card->starter) )
		$rarity = 'S' ;
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
		// Split, transform have 2 cards, not following in card list, not even in right order, delay their parsing
		case 'split' :
			if ( $card->name == $card->names[1] ) { // Second part
				$card->cost = $cost ;
				$card->type = $type ;
				$card->text = $text ;
				$splits[] = $card ; // Manage it after having added all cards
			}
			break ;
		case 'double-faced':
			if ( $card->name == $card->names[1] ) { // Second part
				$color = '' ;
				foreach ( $card->colors as $c ) $color .= ( $c == 'Blue' ) ? 'U' : substr($c, 0, 1) ; 
				if ( $color == '' ) $color = 'X' ;
				$card->color = $color ;
				$card->type = $type ;
				$card->text = $text ;
				$transforms[] = $card ; // Manage it after having added all cards
			}
			break ;
		case 'token' :
			//$importer->addtoken($url, $name, $pow, $tou, card_image_url($mv_ext_name.'/'.$mv_card_id)) ;
			continue 2 ; // Don't parse as only interest is img ans MTGJSON does'nt prodide it
		default :
			echo 'Unknown layout : '.$card->layout."\n" ;
	}
	if ( ( isset($card->names) ) && ( $card->name != $card->names[0] ) ) // Not first part
		continue ; // Card has been managed first time, don't re-add it
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

foreach ( $transforms as $card ) {
	$transform = $importer->search($card->names[0]) ;
	if ( $transform == null )
		echo "Transform can't find {$card->names[0]}\n" ;
	else {
		$transform->transform($card->names[1], $card->color, $card->type, $card->text, 'http://mtgimage.com/set/'.strtoupper($ext_source).'/'.$card->imageName.'.jpg') ;
		if ( property_exists($card, 'foreignNames') )
			foreach ( $card->foreignNames as $fname )
				if ( ! array_key_exists($fname->language, $langcode) )
					echo "{$fname->language} not found" ;
				else if ( $langcode[$fname->language] != '' )
					$transform->addlang($langcode[$fname->language], $fname->name) ;
	}
}




