<?php
// MV Constants
$base_url = 'https://www.magic-ville.com/' ;
$base_path = 'fr/' ;
$mana_url = '/fr/graph/manas/big_png/' ;
$rarity_url = 'graph/rarity/carte' ;
$rarities = array(4 => 'M', 5 => 'S', 10 => 'R', 20 => 'U', 30 => 'C', 40 => 'L') ;
$imported_extratxt = array('Story Spotlight', 'Spotlight', 'Extended-Art Frame', 'Showcase Frame', 'Double Masters Prerelease Promo', 'Borderless', 'Buy-a-Box', 'Promo Pack') ;
$not_imported_extratxt = array('Jumpstart pack') ;

// Importer
$import_url = $base_url.$base_path.'set_cards?setcode='.$ext_source.'&lang=eng' ;
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
if ( preg_match('#<div class=S14>&mdash;(?<cards>\d*) cartes</div>#', $html, $matches) )
	$nbcards = $matches['cards'] ;
else
	die('<a href="'.$import_url.'">No card number found') ;
	// Set data
$importer->setext($code, $title, $nbcards) ;

$nb_token_expected = 0 ;
$nb_token_found = 0 ;

// Cards
$card_dom = new DOMDocument;
/* Dunno why, this method is for page displayed in browser, but not page downloaded
	// First pass : get multiple link for a card
$card_links = $xpath->query("//a[starts-with(@id, 'c_t_')]");
$cards_href = array() ;
for ( $i = 0 ; $i < $card_links->length ; $i++ ) {
	$card_link = $card_links->item($i) ;
	$card_link_other = $xpath->query(".//a[@class='und']", $card_link->parentNode) ; // Search multiple links
	if ( $card_link_other->length === 0 ) { // No other links found
		array_push($cards_href, $card_link->getAttribute('href')) ; // Add base link
	} else { // Other links found
		for ( $j = 0 ; $j < $card_link_other->length ; $j++ ) { // Add them instead of base link
			$card_other_link = $card_link_other->item($j) ;
			array_push($cards_href, $card_other_link->getAttribute('href')) ;
		}
	}
}
*/
$card_links = $xpath->query("//a[starts-with(@id, 'c_t_')]");
$cards_href = array() ;
for ( $i = 0 ; $i < $card_links->length ; $i++ ) {
	$card_href = $card_links->item($i)->getAttribute('href') ;
	if ( ! in_array($card_href, $cards_href) ) {
		array_push($cards_href, $card_href) ;
	}
}
	// Second pass : parse those cards
for ($i = 0 ; $i < count($cards_href) ; $i++ ) {
	$frimg = null ;
	$trimg = 'void' ;
	$frtrimg = null ;
	// Link
	$href = $cards_href[$i] ;
	if ( substr($href, 0, 1) !== '/' ) { // href is relative
		$href = $base_path . $href ;
	}
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
	$second_name = '' ;
	switch ( $name_nodes->length ) {
		case 2 : // Normal card
			$frname = trim($name_nodes->item(0)->nodeValue) ;
			$name = trim($name_nodes->item(1)->nodeValue) ;
			break ;
		case 4 : // Split cards
			$us_idx = $name_nodes->length - 2 ; // English items are after french sun and moon items
			$frname = trim($name_nodes->item(0)->nodeValue).' / '.trim($name_nodes->item(1)->nodeValue) ;
			$name = trim($name_nodes->item(2)->nodeValue) ;
			$second_name = card_name_sanitize($name_nodes->item(3)->nodeValue) ;
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
	$pt_nodes = $card_xpath->query("//div[@class='G14' and string-length(text()) > 0]") ;
	$pt = '' ;
	$trpt = '' ;
	switch ( $pt_nodes->length ) {
		case 0: // Split
			break;
		case 2: // Has 1 PT (normal)
			$pt = loyaltize($pt_nodes->item(1)->nodeValue) ;
			break ;
		// Has 2 PT (transform)
		case 3: // French version miss moon data
		case 4: // Normal
		case 5: // ???
			$pt = loyaltize($pt_nodes->item($pt_nodes->length-2)->nodeValue) ;
			$trpt = loyaltize($pt_nodes->item($pt_nodes->length-1)->nodeValue) ;
			break ;
		default:
			$importer->adderror("Unmanaged PT nodes number : {$pt_nodes->length} $name\n", $href) ;
	}
	$pow = '' ; $tou = '' ;
	if ( preg_match('#(?<pow>\d*)/(?<tou>\d*)#', $pt, $matches) ) {
		$pow = intval($matches['pow']) ;
		$tou = intval($matches['tou']) ;
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
		if ( strpos($img, 'FR') !== false ) {
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
		if ( array_key_exists($rarity_id, $rarities) ) {
			$rarity = $rarities[$rarity_id] ;
		} else {
			$importer->adderror('Rarity index not existing : '.$rarity_id, $href) ;
			$rarity = 'C' ;
		}
	}
	if ( $importer->type === 'preview' ) {
		if ( ( $rarity === 'R' ) || ( $rarity === 'M' ) ) { 
			$rarity = 'L' ; // To appear differently in builder
			$img = null ;
		} else {
			continue ;
		}
	}
// Token
	$form_node = $card_xpath->query("//form[@action='carte.php']/ancestor::table/following-sibling::div") ; // HTML Element following table containing card navigator
	$myel = $form_node->item(0);

	if (
		! $myel->hasAttributes()
//		|| ( count($importer->cards) >= $nbcards )
	) {
		$extratxt = $myel->nodeValue;
		if (
			preg_match('#^(?<txt>.*?)(?<nb>\d*)/(?<tot>\d*)$#', $extratxt, $matches)
			|| preg_match('#^(?<txt>.*?)(?<nb>\d*)$#', $extratxt, $matches)
		) { // Extract counters from extratxt
			$extratxt = trim($matches['txt']) ;
			if ( array_key_exists('tot', $matches) ) {
				$nbt = intval($matches['tot']) ;
			}
		} else {
			$nbt = -1 ;
		}
		if (
			( $extratxt === '' )
			|| ( $extratxt === 'Autorisations en Tournois' )
		) { // extratxt is only a counter, card is a token
			if ( $importer->type !== 'main' ) { continue ; }
			$nb_token_found++ ;
			if ( $nb_token_expected === 0 ) { // First time
				$nb_token_expected = $nbt ;
			}
			if ( $nbt != $nb_token_expected ) {
				$importer->adderror('Expected number of tokens difference : '.$nb_token_expected.' -> '.$nbt, $href) ;
			}
			$importer->addtoken($href, $name, $pow, $tou, $img) ;
			continue ;
		} else {
			//if (  array_search($extratxt, $not_imported_extratxt) !== true) {
			if (  array_search($extratxt, $imported_extratxt) !== false) {
				if ( ( $importer->type !== 'main' ) && ( $importer->type !== 'preview' ) ) { continue ; }
				$importer->adderror('Additionnal text (normal import) : '.$extratxt, $href) ;
			} else { // Only import PW Decks cards in PW Decks context
				if ( ( ( $importer->type !== 'pwdecks' ) && ( $importer->type !== 'all' ) ) || ( strpos($extratxt, 'Planeswalker Deck') < 0 ) ) {
					$importer->adderror('Additionnal text (not imported) : '.$extratxt, $href) ;
					continue ;
				}
			}
		}
	} else {
		//echo "$name {$token_number_node->length}\n" ;
		if ( $importer->type === 'pwdecks' ) { continue ; } // PW Decks imports only PW Decks cards
	}
	/* * /
	if ( ( ( $importer->type === 'preview' ) || ( $importer->type === 'main' ) ) && ( $number > $nbcards ) ) {
		$importer->adderror('Card number too high', $href) ;
		continue ;
	}
	/* */
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
			//$second_types = card_text_sanitize($second_types) ;
			break ;
		default : 
			$importer->adderror('Types nodes '.$type_nodes->length, $href) ;
			continue 2 ;
	}
	$types = trim($types) ;
	$types = str_replace(chr(194).chr(151), '-', $types) ;
	$types = str_replace(chr(194).chr(150), '-', $types) ;
	if ( ( strpos($types, 'Gate') !== false ) && ( strpos($name, 'Guildgate') !== false ) ) { // In all Ravnica extensions, Guildgates (but no other gates) appear as land in boosters
		$rarity = 'L' ;
	}
	if ( strpos($types, 'Theme Card') !== false ) {
		continue ; // Ignore theme cards in import
	}
	// Card is a land but have no cost : normal for suspend
	/**/
	if ( ( strpos($types, 'Land') === false ) && ( $cost === '' ) ) {
		$importer->adderror('Warning : Card is not a land but have no cost', $href) ;
		$importer->addtoken($href, $name, $pow, $tou, $img) ;
		continue ;
	}
	/**/
	// Text
	$text_nodes = $card_xpath->query("//div[@id='EngShort']") ;
	$text = mv2txt($text_nodes->item(0)->C14N()) ;
	if ( $pt != '' ) {
		$text = $pt."\n".$text ;
	}
	$text = str_replace(chr(10).chr(9).chr(9).chr(9).chr(32).chr(32), ': ', $text); // String between loyalty count and effect
	$text = str_replace(chr(10).chr(9).chr(9).chr(9).chr(10), "\n", $text); // End of effect
	switch ( $text_nodes->length ) {
		case 1 :
			break ;
		case 2 :
			$second_text = $trpt."\n".mv2txt($text_nodes->item(1)->C14N()) ;
			$second_text = str_replace(chr(10).chr(9).chr(9).chr(9).chr(32).chr(32), ': ', $second_text); // String between loyalty count and effect
			break ;
		default :
			echo "Unmanaged number of texts : {$text_nodes->length} - $name\n" ;
	}
	// Card import
	$card = $importer->addcard($href, $rarity, $name, $cost, $types, $text, $img) ;
	if ( ! $card ) {
		$importer->adderror('Card not added', $href) ;
		continue ;
	}
	// Translation
	$card->addlang('fr', $frname, $frimg) ;
	// Second part
	if ( ( $second_name !== '' ) && ( strpos($second_types, 'Adventure') === false ) ) { // Split / Transform but not adventures that are not manageable ATM
		// Search back image
		$xpath = $card_xpath->query("//div[@id='CardScanBack']//img") ;
		if ( $xpath->length === 0 ) { // No back image found : it's a split
			$card->split($second_name, $second_cost, $second_types, $second_text) ;
		} else { // Back image found : it's a transform
			// Search back img and FR back img URL
			$frtrimg = null ;
			if ( $importer->type === 'preview' ) {
				$trimg = null ;
			} else {
				$trimg = $xpath->item(0)->getAttribute('src') ;
			}
			if ( strpos($trimg, 'FR') !== false) {
				$frtrimg = $trimg ;
				$trimg = str_replace('FR', '', $trimg) ;
			}
			if ( $trimg != null ) {
				$trimg = $base_url.$trimg ;
			}
			$card->transform(
				$second_name,
				'', // For now (XLN), no need for a color, let's wait it's needed to think about it (sample code below)
				$second_types,
				$second_text,
				$trimg
			) ;
			if ( $frtrimg != null ) {
				$card->addlangimg('fr', $base_url.$frtrimg) ;
			}
		}
	}
}

//$importer->nbcards -= $nb_token_found ;
if ( $nb_token_expected !== $nb_token_found ) {
	echo "$nb_token_found tokens found despite $nb_token_expected expected\n" ;
}

function loyaltize($pt) {
	if ( preg_match('#Loyalty : (\d*)#', $pt, $matches) ) {
		$pt = $matches[1] ;
	}
	return $pt ;
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
		if ( $mana === 'C' ) { // Correct colorless mana to use MTGAS standard code "E"
			$mana = 'E' ;
		}
		if ( strlen($mana) > 1 ) { // Group multiple mana symbols (hybrid, phyrexian)
			$mana = '{'.$mana.'}' ;
		}
		$cost .= $mana ;
	}
	return $cost;
}
?>
