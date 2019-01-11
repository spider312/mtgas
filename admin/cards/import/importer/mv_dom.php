<?php
// MV Constants
$base_url = 'https://www.magic-ville.com/fr/' ;
$mana_url = 'graph/manas/big_png/' ;
$rarity_url = 'graph/rarity/carte' ;
$rarities = array(4 => 'M', 10 => 'R', 20 => 'U', 30 => 'C', 40 => 'L') ;

// Importer
$import_url = $base_url.'set_cards.php?setcode='.$ext_source.'&lang=eng' ;
$importer->init($import_url) ;
$ext_path = 'cache/'.$source.'_'.$ext_source ;
$html = cache_get($importer->url, $ext_path, $verbose, false, $importer->cachetime) ; // Update && limit cache life time for this file
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

$nb_token_expected = 0 ;
$nb_token_found = 0 ;

// Cards
$card_links = $xpath->query("//a[starts-with(@id, 'c_t_')]");
$card_dom = new DOMDocument;
for ( $i = 0 ; $i < $card_links->length ; $i++ ) {
	$frimg = null ;
	$trimg = 'void' ;
	$frtrimg = null ;
	// Link
	$card_link = $card_links->item($i) ;
	$href = $card_link->getAttribute('href') ;
	$href = $base_url.$href ;
	// Download
	$path = $ext_path . '_' . $i ;
	$card_html = cache_get($href, $path, $verbose, false, $importer->cachetime) ;
	if ( empty($card_html) || ! $card_dom->loadHTML($card_html) ) {
		$importer->adderror('Downloaded content unparsable : '.$card_html, $href) ;
		continue;
	}
	$card_xpath = new DOMXpath($card_dom);
// Parsing
// Required for tokens
	// Number
	$number_node = $card_xpath->query("//input[@name='num']") ;
	$number = intval($number_node->item(0)->getAttribute('value')) ;
	// Name (+fr translation)
	$name_nodes =  $card_xpath->query("//div[@class='S16']") ;
	$us_idx = 1 ;
	switch ( $name_nodes->length ) {
		case 2 : // Normal card
			$frname = trim($name_nodes->item(0)->nodeValue) ;
			$name = trim($name_nodes->item(1)->nodeValue) ;
			break ;
		case 4 : // Split cards
			$us_idx = $name_nodes->length - 2 ; // English items are after french sun and moon items
			$frname = trim($name_nodes->item(0)->nodeValue).' / '.trim($name_nodes->item(1)->nodeValue) ;
			$name = trim($name_nodes->item(2)->nodeValue) ;
			break ;
		default:
			$importer->adderror('Name nodes : '.$name_nodes->length, $href);
			continue 2;
	}
	$name = card_name_sanitize($name) ;
	if ( $name == '' ) {
		$importer->adderror('No name', $href) ;
		continue;
	}
	if ( $name === 'Punch Card' ) {
		$importer->adderror('Punch card ignored', $href) ;
		continue;
	}
	// PT
	$pt_nodes = $card_xpath->query("//div[@class='G14']") ;
	$pt = '' ;
	$trpt = '' ;
	switch ( $pt_nodes->length) {
		case 0: // Split
			break;
		case 2: // Has 1 PT (normal)
			$pt = $pt_nodes->item(1)->nodeValue ;
			break ;
		// Has 2 PT (transform)
		case 3: // French version miss moon data
		case 4: // Normal
		case 5: // ???
			$pt = $pt_nodes->item($pt_nodes->length-2)->nodeValue ;
			$trpt = $pt_nodes->item($pt_nodes->length-1)->nodeValue ;
			break ;
		default:
			$importer->adderror("Unmanaged PT nodes number : {$pt_nodes->length} $name\n", $href) ;
	}
	// Img
	$img_node = $card_xpath->query("//td[@width=325]/img") ;
	if ( $img_node->length === 0 ) {
		$img_node = $card_xpath->query("//td[@width=314]/img") ; // Split card
		if ( $img_node->length === 0 ) {
			$importer->adderror('No Image', $href) ;
			continue;
		}
		$src = $img_node->item(0)->getAttribute('src') ;
		$src = str_replace('pfou', 'big', $src) ;
		$src = str_replace('s.jpg', '.jpg', $src) ;
		$img = $base_url.$src ;
		$frimg = str_replace($code, $code.'FR', $img) ;
	} else {
		$img = $base_url.$img_node->item(0)->getAttribute('src') ;
		if ( strpos($img, 'FR') ) {
			$frimg = $img ;
			$img = str_replace('FR', '', $img) ;
		} else {
			$importer->adderror('No FR Image', $href) ;
		}
	}
	// Rarity (before token detection because it may cause a rarity change)
	$rarity_node = $card_xpath->query("//img[contains(@src, '$rarity_url')]") ;
	if ( $rarity_node->length <= 0 ) {
		$importer->adderror('Rarity icon not found', $href) ;
		$rarity = 'C' ;
	} else {
		$rarity_id = preg_replace("#$rarity_url|(\..*)#", '', $rarity_node->item(0)->getAttribute('src')) ;
		$rarity = $rarities[$rarity_id] ;
		if ( $importer->type === 'preview' ) {
			if ( ( $rarity === 'R' ) || ( $rarity === 'M' ) ) { 
				$rarity = 'L' ; // To appear differently in builder
				$img = null ;
			} else {
				continue ;
			}
		}
	}
// Token
	$form_node = $card_xpath->query("//form[@action='carte.php']/ancestor::table/following-sibling::div") ; // HTML Element following table containing card navigator
	$myel = $form_node->item(0);
	/*
	if ( count($importer->cards) >= $nbcards ) { // On-demand enabling of importer nb cards limit, for when tokens are detected as cards
		//$importer->addtoken($href, $name, $pow, $tou, $img) ; // Commented because at this point we didn't extract pow/tou yet
		continue ;
	}
	*/
	if ( ! $myel->hasAttributes() ) { // div after table is "Autorisations en Tournois" and has class S14
		$extratxt = $myel->nodeValue;
		if ( preg_match('#^(?<txt>.*?)(?<nb>\d*)/(?<tot>\d*)$#', $extratxt, $matches) ) { // Extract counters from extratxt
			$extratxt = trim($matches['txt']) ;
			$nbt = intval($matches['tot']) ;
		} else {
			$nbt = -1 ;
		}
		switch ( $extratxt ) {
			case '' : // extratxt is only a counter, card is a token
				if ( $importer->type !== 'main' ) { continue 2 ; }
				$nb_token_found++ ;
				if ( $nb_token_expected === 0 ) { // First time
					$nb_token_expected = $nbt ;
				}
				if ( $nbt != $nb_token_expected ) {
					$importer->adderror('Expected number of tokens difference : '.$nb_token_expected.' -> '.$nbt, $href) ;
				}
				$pow = '' ; $tou = '' ;
				if ( preg_match('#(?<pow>\d*)/(?<tou>\d*)#', $pt, $matches) ) {
					$pow = intval($matches['pow']) ;
					$tou = intval($matches['tou']) ;
				}
				$importer->addtoken($href, $name, $pow, $tou, $img) ;
				continue 2 ;

			case 'Story Spotlight': // Normal cards included in extension
				if ( $importer->type !== 'main' ) { continue 2 ; }
				$importer->adderror('Additionnal text (normal import) : '.$extratxt, $href) ;
				break;

			default: // Only import PW Decks cards in PW Decks context
				if ( ( $importer->type !== 'pwdecks' ) || ( strpos($extratxt, 'Planeswalker Deck') < 0 ) ) {
					$importer->adderror('Additionnal text (not imported) : '.$extratxt, $href) ;
					continue 2 ;
				}
		}
	} else {
		//echo "$name {$token_number_node->length}\n" ;
		if ( $importer->type === 'pwdecks' ) { continue ; } // PW Decks imports only PW Decks cards
	}
	$exceptions = [] ; // Cards wrongly declared as nontoken on source during "hot" import
	if ( array_search($number, $exceptions) !== false ) {
		$nb_token_found++ ;
		$importer->addtoken($href, $name, $pow, $tou, $img) ;
		continue;
	}
// Required for cards
	// Cost
	$second_cost = '';
	$cost_container_nodes = $card_xpath->query("//img[contains(@src, '$mana_url')]/..") ;
	if ( $cost_container_nodes->length === 0 ) {
		$cost = '' ;
	} else {
		if ( $cost_container_nodes->length > 1 ) {
			$second_cost = mv_dom_node2cost($cost_container_nodes[1]);
		}
		$cost = mv_dom_node2cost($cost_container_nodes[0]);
	}
	// Types
	$type_nodes = $card_xpath->query("//div[@class='G12']") ;
	$second_types = '' ;
	switch ( $type_nodes->length ) {
		case 2 :
			$types = $type_nodes->item(1)->nodeValue ;
			break ;
		case 4 :
			$types = $type_nodes->item(2)->nodeValue ;
			$second_types = $type_nodes->item(3)->nodeValue ;
			break ;
		default : 
			$importer->adderror('Types nodes '.$type_nodes->length, $href) ;
			continue 2 ;
	}
	$types = trim($types) ;
	$types = str_replace(chr(194).chr(151), '-', $types) ;
	if ( strpos($types, 'Gate') !== false ) // DGM Gates must be considered as a land in DB
		$rarity = 'L' ;
	if ( ( strpos($types, 'Land') === false ) && ( $cost === '' ) ) {
		$importer->adderror('Warning : Card is not a land but have no cost', $href) ;
	}
	// Text
	$text_nodes = $card_xpath->query("//div[@class='S12' or @class='S11']") ; //div[@id='EngShort']
	// ->C14N() : Export as HTML to get images and transform them into mtg cost tags
	// Workaround for missing french transformed data
	$text_nodes_length = $text_nodes->length ;
	/*if ( $text_nodes->length == 5 ) { // Instead of 6
		$types_arr = explode(' - ', $types) ;
		if ( $types_arr[0] !== 'Planeswalker' ) {
			echo "Workarounded $name - $text_nodes_length\n" ;
			$text_nodes_length = 6 ;
		}
	}*/
	$second_text = '' ;
	$idx = 2 ;
	switch ( $text_nodes_length ) {
		case 6 : // Split / Aftermath + Transform ?
			$second_text = $text_nodes->item($text_nodes->length-2)->C14N() ;
			$second_text = mv2txt($second_text) ;
			$idx = 3 ;
		case 3 : // Normal case
			$text = $text_nodes->item($idx)->C14N() ;
			$text = mv2txt($text) ;
			$text = str_replace(chr(194), '', $text) ; // Strange char appearing before - and * in modal and keywords
			$text = str_replace(chr(160), ' ', $text) ; // Repair bug due to correction above
			// Specific planeswalker parsing
			$match = 'Loyalty : ' ;
			if ( substr($pt, 0, strlen($match)) === $match ) {
				// Loyalty
				$pt = substr($pt, strlen($match)) ;
				if ( ! is_numeric($pt) ) { $pt = '0' ; } // Integer expected - X loyalty
				// HTML -> C14N produced some strange chars, convert to expected text
				$text = str_replace(chr(10).chr(9).chr(9).chr(9).chr(32).chr(32), ': ', $text); // String between loyalty count and effect
				$text = str_replace(chr(10).chr(9).chr(9).chr(9).chr(10), "\n", $text); // End of effect
			}
			if ( $pt != '' ) {
				$text = $pt."\n".$text ;
			}
			break ;
		case 5 : // Planeswalker
			if ( $second_types !== '' ) {
				$text = mv2txt($text_nodes->item(2)->C14N()) ;
				$second_text = mv2txt($text_nodes->item(4)->C14N());
			} else {
				$text = mv_planeswalker($text_nodes, 3) ;
			}
			break ;
		case 8 : // Double face, moon is a planeswalker
			if ( $pt == '' ) { // Sun is a planeswalker (ISD Garruk, SOI Arlinn Kord)
				$text = mv_planeswalker($text_nodes, 4) ;
				$second_text = mv_planeswalker($text_nodes, 6);
			} else { // Sun is a creature (ORI Planeswalkers)
				$text = $text_nodes->item(4)->C14N() ;
				$text = $pt."\n".mv2txt($text) ;
				$second_text = mv_planeswalker($text_nodes, 5);
			}
			break ;
		default :
			echo "Unmanaged number of texts : {$text_nodes->length} - $name\n" ;
	}
	//--
	// Card import
	$card = $importer->addcard($href, $rarity, $name, $cost, $types, $text, $img) ;
	if ( ! $card ) {
		$importer->adderror('Card not added', $href) ;
		continue ;
	}
	// Translation
	$card->addlang('fr', $frname, $frimg) ;
	// Second part
	switch ( $name_nodes->length ) {
		case '4' : // Split / Transform
			$second_name = card_name_sanitize($name_nodes->item(3)->nodeValue) ;
			// Search back image
			$xpath = $card_xpath->query("//div[@id='CardScanBack']//img") ;
			if ( $xpath->length === 0 ) { // No back image found : it's a split
				$card->split($second_name, $second_cost, $second_types, $second_text) ;
			} else { // Back image found : it's a transform
				// Search back img and FR back img URL
				$frtrimg = null ;
				$trimg = $xpath->item(0)->getAttribute('src') ;
				if ( strpos($trimg, 'FR') ) {
					$frtrimg = $trimg ;
					$trimg = str_replace('FR', '', $trimg) ;
				}
				$card->transform(
					$second_name,
					'', // For now (XLN), no need for a color, let's wait it's needed to think about it (sample code below)
					$second_types,
					$second_text,
					$base_url.$trimg
				) ;
				if ( $frtrimg != null ) {
					$card->addlangimg('fr', $base_url.$frtrimg) ;
				}
			}
			break ;
	}
	// Moon
	/*
	if ( $name_nodes->length > 2 ) {
		$idx = $name_nodes->length - 1 ;
		// Color
		$trcolor = '' ;
		$node = $trtypes_node->firstChild ;
		while( $node->nodeName == 'img' ) {
			$src = $node->getAttribute('src') ;
			if ( preg_match("#graph/manas/l(.)\.gif#", $src, $matches) ) {
				$trcolor .= $matches[1] ;
			} else {
				echo "Not a valid mana icon URL : $src\n";
			}
			$node = $node->nextSibling ;
		}
		// Text & PT : managed in sun face text & PT managements
		if ( $trpt != '' )
			$second_text = $trpt."\n".$second_text ;
	}
	*/
}

//$importer->nbcards -= $nb_token_found ;
if ( $nb_token_expected !== $nb_token_found ) {
	echo "$nb_token_found tokens found despite $nb_token_expected expected\n" ;
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
function mv_dom_node2cost($node) {
	global $card_xpath, $mana_url ;
	$cost_nodes = $card_xpath->query("img[contains(@src, '$mana_url')]", $node, false) ;
	$cost = '' ;
	for ( $j = 0 ; $j < $cost_nodes->length ; $j++ ) {
		$item = $cost_nodes->item($j) ;
		$mana = preg_replace("#$mana_url|(\..*)#", '', $item->getAttribute('src')) ;
		if (
			( $mana[0] === 'P' ) // Repair phyrexian mana
			|| ( $mana === 'WG' ) // And some other colors
			|| ( $mana === 'UG' )
		) { 
			$mana = substr($mana, 1) . $mana[0] ; 
		}
		if ( $mana === 'WR' ) {
			$mana = 'RW' ;
		}
		if ( strlen($mana) > 1 ) { // Group multiple mana symbols (hybrid, phyrexian)
			$mana = '{'.$mana.'}' ;
		}
		$cost .= $mana ;
	}
	return $cost;
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
