<?php
// Constants
$base_url = 'https://www.mtgsalvation.com/spoilers/' ;
$code_source = preg_replace('/-(.*)/', '', $ext_source) ;
$nbperpage = 80 ;
function import_url($page=1) {
	global $code_source, $nbperpage ;
	$import_url = 'https://www.mtgsalvation.com/spoilers/filter?SetID='.$code_source.'&Page='.$page.'&CardsPerRequest='.$nbperpage ;
	//$import_url .= '&Color=&Type=&IncludeUnconfirmed=true&CardID=&equals=false&clone=[object+Object]' ;
	return $import_url ;
}

// Importer
$import_url = $base_url.$ext_source ;
$importer->init($import_url) ;
$ext_path = 'cache/'.$source.'_'.$ext_source ;
$html = cache_get($importer->url, $ext_path, $verbose, true, 300) ; // Update && limit cache life time for this file

// DOM parsing
libxml_use_internal_errors(true) ; // No warnings during parsing
$dom = new DOMDocument ;
$dom->loadHTML($html) ;
$xpath = new DOMXpath($dom) ;

// Extension data
	// Title -> name
$title_node = $xpath->query("//title") ;
$title = preg_replace('/ - (.*)/', '', $title_node->item(0)->nodeValue) ;

	// Cards nb
$h2 = $xpath->query('//h2')->item(0)->nodeValue ;
if ( preg_match('#(?<revealed>\d*)/(?<total>\d*)#', $h2, $matches) ) {
	//$nbcards = $matches['revealed'] ;
	$nbcards = $matches['total'] ;
} else {
	$nbcards = 0 ;
}
	// Set data
$importer->setext($ext_local, $title, $nbcards) ;

// Get pages
$page = 0 ;
$continue = true ;
while ( $continue ) {
	$path = $ext_path . '_' . $page . '_' . $nbperpage ;
	$url = import_url($page++) ;
	$html = cache_get($url, $path, $verbose) ;
	if ( $html === '' ) break ;
	$cards_dom = new DOMDocument ;
	$cards_dom->loadHTML($html) ;
	$cards_xpath = new DOMXpath($cards_dom) ;
	$cards_nodes = $cards_xpath->query('/html/body/div') ;
	for ( $i = 0 ; $i < $cards_nodes->length ; $i++ ) {
		$card_node = $cards_nodes->item($i) ;
		// SubDocument parsing (relative XPath don't seem to work)
		$card_dom = new DOMDocument ;
		$card_dom->loadHTML($card_node->C14N()) ;
		$card_xpath = new DOMXpath($card_dom) ;
		// Link + Name
		$name_node = $card_xpath->query('//h2/a')->item(0) ;
		$href = $name_node->attributes->getNamedItem('href')->nodeValue ;
		$name = $name_node->nodeValue ;
		// Cost
		$cost_nodes =  $card_xpath->query('//ul/span') ;
		$cost = '' ;
		for ( $j = 0 ; $j < $cost_nodes->length ; $j++ ) {
			$mana = $cost_nodes->item($j)->nodeValue ;
			$cost .= ( $mana === 'C' ) ? 'E' : $mana ;
		}
		// Types
		$types = $card_xpath->query('//span[@class="t-spoiler-type j-search-html"]')->item(0)->nodeValue ;
		// Rarity
		$rarity_className = $card_xpath->query('//span[@class="t-spoiler-rarity"]/span')->item(0)->attributes->getNamedItem('class')->nodeValue ;
		$rarity_words = explode('-', $rarity_className) ;
		$rarity = $rarity_words[count($rarity_words)-1] ;
		$rarity = strtoupper(substr($rarity, 0, 1)) ;
		// Text
			// Base
		$text_nodes = $card_xpath->query('//div[@class="t-spoiler-ability"]/input') ;
		if ( $text_nodes->length > 0 ) {
			$text_html = $text_nodes->item(0)->attributes->getNamedItem('value')->nodeValue ; 
		} else {
			$text_html = '' ;
		}
		$text = preg_replace('#(<i>.*?</i>)#', '', $text_html) ; // Filter helpers
			// PT / Loyalty
		$pt_nodes = $card_xpath->query('//span[@class="t-spoiler-stat"]') ;
		if ( $pt_nodes->length > 0 ) {
			$text = $pt_nodes->item(0)->nodeValue . "\n" . $text ;
		}
		$text = preg_replace("# +#", " ", $text) ;
		$text = preg_replace("#\n\s*?\n#", "\n", $text) ;
		$text = str_replace('{c}', '{E}', $text) ;
		$text = str_replace('{C}', '{E}', $text) ;
		$text = str_replace('Ã¢ÂÂ', '-', $text) ;
		// Image
		$img = $card_xpath->query('//div[@class="spoiler-card-img"]/a/img')->item(0)->attributes->getNamedItem('src')->nodeValue ;
		$card = $importer->addcard($href, $rarity, $name, $cost, $types, $text, $img) ;
	}
}
?>
