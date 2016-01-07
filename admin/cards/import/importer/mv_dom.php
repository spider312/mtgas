<?php
// MV Constants
$base_url = 'http://www.magic-ville.com/fr/' ;
$mana_url = 'graph/manas/big_png/' ;
$rarity_url = 'graph/rarity/carte' ;
$rarities = array(4 => 'M', 10 => 'R', 20 => 'U', 30 => 'C', 40 => 'L') ;

// Importer
$import_url = $base_url.'set_cards.php?setcode='.$ext_source.'&lang=eng' ;
$importer->init($import_url) ;
$ext_path = 'cache/'.$source.'_'.$ext_source ;
$html = cache_get($importer->url, $ext_path, $verbose) ;

// DOM parsing
libxml_use_internal_errors(true) ; // No warnings during parsing
$dom = new DOMDocument ;
$dom->loadHTML($html) ;
$xpath = new DOMXpath($dom) ;

// Extension data
	// Title -> name
$title_node = $xpath->query("//title") ;
$title = preg_replace('/ - (.*)/', '', $title_node->item(0)->nodeValue) ;
	// Code
$code_node = $xpath->query("//img[contains(@src, 'bigsetlogos')]");
$code = preg_replace('#(.*/)|(\..*)#', '', $code_node->item(0)->getAttribute('src')) ;
	// Cards nb
if ( preg_match('#<div class=G14>&mdash; (?<cards>\d*) cartes</div>#', $html, $matches) )
	$nbcards = $matches['cards'] ;
else
	die('<a href="'.$import_url.'">No card number found') ;
	// Set data
$importer->setext($code, $title, $nbcards) ;

// Cards
$card_links = $xpath->query("//a[starts-with(@id, 'c_t_')]");
$card_dom = new DOMDocument;
for ( $i = 0 ; $i < $card_links->length ; $i++ ) {
	$frimg = null ;
	$trtext = 'Uninitialized' ;
	$trpt = '' ;
	$trimg = 'void' ;
	$frtrimg = null ;
	// Link
	$card_link = $card_links->item($i) ;
	$href = $card_link->getAttribute('href') ;
	$href = $base_url.$href ;
	// Download
	$path = $ext_path . '_' . $i ;
	$card_html = cache_get($href, $path, $verbose) ;
	$card_dom->loadHTML($card_html);
	$card_xpath = new DOMXpath($card_dom);
// Parsing
// Required for tokens
	// Number
	$number_node = $card_xpath->query("//input[@name='num']") ;
	$number = intval($number_node->item(0)->getAttribute('value')) ;
	// Name (+fr translation)
	$name_nodes =  $card_xpath->query("//div[@class='S16']") ;
	$fr_idx = 0 ; // French items are before english items
	if ( $name_nodes->length > 2 ) // Double face
		$us_idx = 2 ; // English items are after french sun and moon items
	else // Simple face
		$us_idx = 1 ; // English items are right after french items
	$frname = trim($name_nodes->item($fr_idx)->nodeValue) ;
	//$frname = html_entity_decode($frname, ENT_COMPAT, 'UTF-8') ;
	//$frname = card_name_sanitize($frname) ;
	$name = trim($name_nodes->item($us_idx)->nodeValue) ;
	$name = card_name_sanitize($name) ;
	if ( $name == '' ) {
		$importer->adderror('No name', $href) ;
		continue;
	}
	// PT
	$pt_nodes = $card_xpath->query("//div[@class='G14']") ;
	switch ( $pt_nodes->length) {
		case 2: // Has 1 PT (normal)
			$pt = $pt_nodes->item(1)->nodeValue ;
			break ;
		case 4: // Has 2 PT (transform)
			$pt = $pt_nodes->item(2)->nodeValue ;
			$trpt = $pt_nodes->item(3)->nodeValue ;
			break ;
		default:
			echo "Unmanaged PT nodes number : {$pt_nodes->length} $name\n" ;
	}
	// Img
	$img_node = $card_xpath->query("//td[@width=325]/img") ;
	$img = $base_url.$img_node->item(0)->getAttribute('src') ;
	if ( strpos($img, 'FR') ) {
		$frimg = $img ;
		$img = str_replace('FR', '', $img) ;
	}
// Token
	$token_number_node = $card_xpath->query("//tr[@height=460]/td[@width='37%']/div") ;
	if ( ( $number > $importer->nbcards ) || ( $token_number_node->length == 4 ) || ( $token_number_node->length == 7 ) ) {
		$pow = 0 ; $tou = 0 ;
		if ( preg_match('#(?<pow>\d*)/(?<tou>\d*)#', $pt, $matches) ) {
			$pow = intval($matches['pow']) ;
			$tou = intval($matches['tou']) ;
		}
		$importer->addtoken($href, $name, $pow, $tou, $img) ;
		continue ;
	}
// Required for cards
	// Cost
	$cost_nodes = $card_xpath->query("//img[contains(@src, '$mana_url')]") ;
	$cost = '' ;
	for ( $j = 0 ; $j < $cost_nodes->length ; $j++ ) {
		$cost .= preg_replace("#$mana_url|(\..*)#", '', $cost_nodes->item($j)->getAttribute('src')) ;
	}
	// Types
	$type_nodes = $card_xpath->query("//div[@class='G12']") ;
	$types = $type_nodes->item($us_idx)->nodeValue ;
	$types = trim($types) ;
	$types = str_replace(chr(194).chr(151), '-', $types) ;
	// Text
	$text_nodes = $card_xpath->query("//div[@class='S12' or @class='S11']") ; //div[@id='EngShort']
	// ->C14N() : Export as HTML to get images and transform them into mtg cost tags
	switch ( $text_nodes->length ) {
		case 6 : // Transform
			$trtext = $text_nodes->item(4)->C14N() ;
			$trtext = mv2txt($trtext) ;
		case 3 : // Normal case
			$text = $text_nodes->item($us_idx+1)->C14N() ;
			$text = mv2txt($text) ;
			$text = str_replace(chr(194), '', $text) ; // Strange char appearing before - and * in modal and keywords
			$text = str_replace(chr(160), ' ', $text) ; // Repair bug due to correction above
			if ( $pt != '' )
				$text = $pt."\n".$text ;
			break ;
		case 5 : // Planeswalker
			$text = mv_planeswalker($text_nodes, 3) ;
			break ;
		case 8 : // Double face, moon is a planeswalker
			if ( $pt == '' ) { // Sun is a planeswalker (ISD Garruk)
				$text = mv_planeswalker($text_nodes, 4) ;
				$trtext = mv_planeswalker($text_nodes, 6);
			} else { // Sun is a creature
				$text = $text_nodes->item(4)->C14N() ;
				$text = $pt."\n".mv2txt($text) ;
				$trtext = mv_planeswalker($text_nodes, 5);
			}
			break ;
		default :
			echo "Unmanaged number of texts : {$text_nodes->length} - $name\n" ;
	}
	// Rarity
	$rarity_node = $card_xpath->query("//img[contains(@src, '$rarity_url')]") ;
	if ( $rarity_node->length <= 0 ) {
		$importer->adderror('Rarity icon not found', $href) ;
		$rarity = 'C' ;
	} else {
		$rarity_id = preg_replace("#$rarity_url|(\..*)#", '', $rarity_node->item(0)->getAttribute('src')) ;
		$rarity = $rarities[$rarity_id] ;
	}
	if ( strpos($types, 'Gate') !== false ) // DGM Gates must be considered as a land in DB
		$rarity = 'L' ;
	// Card import
	$card = $importer->addcard($href, $rarity, $name, $cost, $types, $text, $img) ;
	if ( ! $card ) {
		$importer->adderror('Card not added', $href) ;
		continue ;
	}
	// Translation
	$card->addlang('fr', $frname, $frimg) ;
	// Moon
	if ( $name_nodes->length > 3 ) {
		// Name
		$trname = trim($name_nodes->item(3)->nodeValue) ;
		// Types
		$trtypes_node = $type_nodes->item(3) ;
		$trtypes = trim($trtypes_node->nodeValue) ;
		$trtypes = str_replace(chr(194).chr(151), '-', $trtypes) ;
		// Color
		$trcolor = '' ;
		$node = $trtypes_node->firstChild ;
		do {
			$src = $node->getAttribute('src') ;
			$trcolor .= preg_replace("#graph/manas/l|(\..*)#", '', $src) ;
			$node = $node->nextSibling ;
		} while( $node->nodeName == 'img' ) ;
		// Text & PT : managed in sun face text & PT managements
		if ( $trpt != '' )
			$trtext = $trpt."\n".$trtext ;
		// Image
		if ( preg_match('#if \(bflst==1\) \{document\["BIGflippic"\].src="(.*?)";bflst=2;\}#', $card_html, $matches) )
			$trimg = $matches[1] ;
		if ( strpos($trimg, 'FR') ) {
			$frtrimg = $trimg ;
			$trimg = str_replace('FR', '', $trimg) ;
		}
		// Import
		$card->transform(
			card_name_sanitize($trname)
			, $trcolor
			, $trtypes
			, $trtext
			, $base_url.$trimg
		) ;
		if ( $frtrimg != null )
			$card->addlangimg('fr', $frtrimg) ;
	}
}
function mv_planeswalker($text_nodes, $text_idx) {
	$text = $text_nodes->item($text_idx)->C14N() ;
	$text = mv2txt($text)."\n" ;
	if ( preg_match_all('/\s*([+|-]?\d*)\s*(.*?)\n/', $text, $matches, PREG_SET_ORDER) ) {
		$text = '' ;
		foreach ( $matches as $match )
			$text .= $match[1].': '.$match[2]."\n" ;
	}
	$loyalty = $text_nodes->item($text_idx+1)->C14N() ;
	if ( preg_match('#Loyalty : (\d*)#', $loyalty, $matches) ) {
		$loyalty = $matches[1] ;
		$text = $loyalty."\n".$text ;
	}
	return $text ;
}
/*
// Init
$reg_flip = '#<div class=S16>(?<name>[^<>]*?)</div><div class=G12 style="padding-top:4px;padding-bottom:3px;">(?<type>[^<>]*?)</div><div style="display:block;" class=S12 align=justify>(?<text>.*?)</div>#s' ;

// Parse cards
foreach ( $matches_list as $match ) { //
	if ( preg_match('#<div class=S11 align=right>Loyalty : (?<loyalty>\d)</div>#', $html, $matches_loyalty) ) {
		$text = $matches_loyalty['loyalty']."\n" ;
	}

	// Second part
	if ( $card != null ) { // Card is not a token
		// Split
		if ( $nb == 2 )
			$card->split(
				card_name_sanitize($matches[1]['name']),
				mv2cost($matches[1]['cost']),
				$type, mv2txt($matches[1]['text'])
			) ;
		// Flip
		if ( preg_match_all($reg_flip, $html, $matches_flip, PREG_SET_ORDER) > 0 ) {
			$match_flip = $matches_flip[1] ; // 0 is french version
			$text = mv2txt($match_flip['text']) ;
			if ( count($matches_pt) > 1 ) // Multiple P/T found, first is day, other are night
				$text = $matches_pt[1]['pow'].'/'.$matches_pt[1]['tou']."\n".$text ;
			$card->flip($match_flip['name'], $match_flip['type'], $text) ;
		}
		// Face down
			$text = mv2txt($match_moon['text']) ;
		}
	}
}
*/
?>
