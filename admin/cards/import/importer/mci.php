<?php
// Init
$list_url = 'http://magiccards.info/'.$ext_source.'/en.html' ;
$list_regex = '#<tr class="(even|odd)">
\s*<td align="right">(?<id>\d*[ab]?)</td>
\s*<td><a href="(?<url>/'.$ext_source.'/en/\d*a?b?\.html)">(?<name>.*)</a></td>
\s*<td>(?<type>.*?)</td>
\s*<td>(?<cost>.*?)</td>
\s*<td>(?<rarity>.*?)</td>
\s*<td>(?<artist>.*?)</td>
\s*<td><img src="http://magiccards.info/images/en.gif" alt="English" width="16" height="11" class="flag2">(?<ext>.*)</td>
\s*</tr>#' ;
$card_regex = '#<p>(?<typescost>.*)</p>\s*<p class="ctext"><b>(?<text>.*)</b></p>.*http\://gatherer.wizards.com/Pages/Card/Details.aspx\?multiverseid=(?<multiverseid>\d*)#s' ;
$lang_regex = '#<img src="http://magiccards.info/images/(?<code>.{2}).gif" alt="(?<lang>.{1,100})" \n\s*width="16" height="11" class="flag2"> \n\s*<a href="(?<url>.{1,200})">(?<name>.{1,100})</a><br>#' ;
function card_image_url($match, $code='en') {
	global $ext_source ;
	return 'http://magiccards.info/scans/'.$code.'/'.strtolower($ext_source).'/'.$match['id'].'.jpg' ;
}

// Get
$html = cache_get($list_url, 'cache/'.$source.'_'.$ext_source, $verbose) ;
$nb = preg_match_all($list_regex, $html, $matches, PREG_SET_ORDER) ;
if ( $nb < 1)
        die('URL '.$list_url.' does not seem to be a valid MCI card list : '.count($matches)) ;

// Parse
$lastcard = null ;
foreach ( $matches as $match ) {
	// Parse card itself
	$html = cache_get(dirname($list_url).'/..'.$match['url'], 'cache/'.str_replace('/', '_', $match['url']), $verbose) ;
	$nb = preg_match($card_regex, substr($html, 0, 10240), $card_matches) ;
	// Base checks
	if ( $nb < 1 ) {
		echo 'Unparsable : <textarea>'.$html.'</textarea><br>' ;
		continue ;
	}
	//$multiverseid = intval($card_matches['multiverseid']) ;
	// Name
	$name = $match['name'] ;
	// Double cards : recompute name, mark as being second part (in which case card will be added, not replaced)
	$doubleface = false ;
	$split = false ;
	if ( 	preg_match('/(.*) \((\1)\/(.*)\)/', $name, $name_matches)
		|| preg_match('/(.*) \((.*)\/(\1)\)/', $name, $name_matches) ) {
		$split = true ;
		$name = $name_matches[2] . ' / ' . $name_matches[3] ;
	}
	// Types / cost
	$typescost = $card_matches['typescost'] ;
	if ( preg_match('#(?<types>.*)(, \n(?<cost>.*))#', $typescost, $typescost_matches) ) {
		$types = $typescost_matches['types'] ;
		$cost = trim($typescost_matches['cost']) ;
	} else { // No 'cost' in 'types + cost', it's a land
		if ( preg_match('#(?<types>.*)\n#', $typescost, $land_matches) )
			$types = $land_matches['types'] ;
		else
			$types = $typescost ; // 'typescost' only contains 'type'
		$cost = '' ; // 'cost' is empty
	}
	$types = str_replace('—', '-', $types) ; 
	// Cost
	if ( preg_match('/(?<cost>.*) \((?<cc>\d*)\)/', $cost, $cost_matches) )
		$cost = $cost_matches['cost'] ;
	// Text
	$text = str_replace('<br><br>', "\n", $card_matches['text']) ; // Un-HTML-ise text
	$text = trim($text) ;
		// Type-specific
			// Creature
	if ( preg_match('/(?<types>.*) (?<pow>[^\s]*)\/(?<tou>[^\s]*)/', $types, $types_matches) ) {
		$types = $types_matches['types'] ;
		$text = $types_matches['pow'].'/'.$types_matches['tou']."\n".$text ;
	}
			// Planeswalker
	if ( preg_match('/(?<types>.*) \(Loyalty: (?<loyalty>\d)\)/', $types, $types_matches) ) {
		$types = $types_matches['types'] ;
		$text = $types_matches['loyalty']."\n".$text ;
	}
	// Second part of dual cards (all cards having multiple lines on mci)
	$secondpart = ( intval($match['id']).'b' == $match['id'] ) ;
	if ( $secondpart ) {
		if ( preg_match('/\(Color Indicator: (?<color>.{1,100})\)/', $html, $colors_matches) ) { // Double Face card
			// Don't work for chalice of life/death, as it has no color indicator
			$doubleface = true ;
			$ci = '%' ;
			foreach ( explode(' ', $colors_matches['color']) as $color )
				if ( ( $c = array_search(strtolower($color), $colors) ) !== false )
					$ci .= $c ;
			$add = "\n-----\n$name\n$ci $types\n$text" ;
			$lastcard->addimage(card_image_url($match)) ;
		} else { 
			if ( $split ) // Split card
				$add = "\n----\n$cost\n$types\n$text" ;
			else // Flip card
				$add = "\n----\n$name\n$types\n$text" ;
		}
		$lastcard->addtext($add) ; // Assume that last parsed card was the first part of the same card
	} else { // "normal" cards (1 line on mci) or first part of dual card
		$rarity = substr($match['rarity'], 0, 1) ;
		if ( preg_match('/\(Color Indicator: (?<color>.{1,100})\)/', $html, $colors_matches) ) // Cards with no casting cost (Ancestral Vision)
			$text = $name.' is '.strtolower(implode(' and ', explode(' ', $colors_matches['color'])))."\n".$text ;
		$lastcard = $importer->addcard($rarity, $name, $cost, $types, $text, card_image_url($match)) ;
	}
	// Lang
	$nb = preg_match_all($lang_regex, $html, $matches_lang, PREG_SET_ORDER) ;
	foreach ( $matches_lang as $lang )
		if ( array_search($lang['code'], array('de', 'fr', 'it', 'es', 'pt')) !== false ) { // Expected charset
			$code = $lang['code'] ;
			$lname = $lang['name'] ;
			$url = card_image_url($match, $code) ;
			if ( ! $secondpart )
				$lastcard->setlang($code, $lname, $url) ;
			else {
				if ( ! $doubleface )
					$url = null ;
				$lastcard->addlang($code, $lname, $url) ;
			}
		}
}
?>
