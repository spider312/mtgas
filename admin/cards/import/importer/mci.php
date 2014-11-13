<?php
// Init
$importer->init('http://magiccards.info/'.$ext_source.'/en.html') ;
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
function get_split($name) {
	$split = false ;
	if ( 	preg_match('/(?<splitname>.*) \((?<fullname>(\1)\/(?<otherpart>.*))\)/', $name, $name_matches)
		|| preg_match('/(?<splitname>.*) \((?<fullname>(?<otherpart>.*)\/(\1))\)/', $name, $name_matches) ) {
		$split = true ;
		$name = $name_matches['splitname'] ;
		//$name = $name_matches['fullname'] ;
		//$name = $name_matches['splitname'].' / '.$name_matches['otherpart'] ; // IVG not having second lines for splits
	}
	return $split ;
}

// Get
$html = cache_get($importer->url, 'cache/'.$source.'_'.$ext_source, $verbose) ;

// Parse extension data
$nb = preg_match_all('|<h1>(?<name>.*?) <small style="color: #aaa;">(?<code>.*?)/en</small></h1>|', $html, $matches_ext, PREG_SET_ORDER) ;
if ( $nb != 1)
        die('URL '.$importer->url.' does not seem to be a valid MCI card list : '.count($matches_ext)) ;

// Parse cards
$nb = preg_match_all($list_regex, $html, $matches, PREG_SET_ORDER) ;
if ( $nb < 1)
        die('URL '.$importer->url.' does not seem to be a valid MCI card list : '.count($matches)) ;
// Last card ID is the card nb (as split/flip/dual cards are divided in)
$importer->setext($matches_ext[0]['code'], $matches_ext[0]['name'], $matches[count($matches)-1]['id']) ;

$lastcard = null ;
foreach ( $matches as $match ) {
	// Parse card itself
	$card_url = dirname($importer->url).'/..'.$match['url'] ;
	$html = cache_get($card_url, 'cache/'.str_replace('/', '_', $match['url']), $verbose) ;
	$nb = preg_match($card_regex, substr($html, 0, 10240), $card_matches) ;
	// Base checks
	if ( $nb < 1 ) {
		echo '<a href="'.$card_url.'">Unparsable</a> : <textarea>'.$html.'</textarea><br>' ;
		continue ;
	}
	// Name
	$name = $match['name'] ;
	// Double cards : recompute name, mark as being second part (in which case card will be added, not replaced)
	$doubleface = false ;
	$split = get_split(&$name) ;
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
		// Colors are inverted in MCI
	$from = array('WR', 'WG', 'UG') ;
	$to = array_map('strrev', $from) ;
	$cost = str_replace($from, $to, $cost) ;
	// Text
	$text = str_replace('<br><br>', "\n", $card_matches['text']) ; // Un-HTML-ise text
	$text = str_replace('−', '-', $text) ; // UTF minus used in planeswalkers
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
	$secondpart = $secondpart && ( $name != $lastcard->name ) ; // Brothers Yamazaki
	if ( $secondpart ) {
		if ( preg_match('/\(Color Indicator: (?<color>.{1,100})\)/', $html, $colors_matches) ) { // Double Face card
			// Don't work for chalice of life/death, as it has no color indicator
			$doubleface = true ;
			$ci = '' ;
			foreach ( explode(' ', $colors_matches['color']) as $color )
				if ( ( $c = array_search(strtolower($color), $colors) ) !== false )
					$ci .= $c ;
			$lastcard->transform($name, $ci, $types, $text, card_image_url($match)) ;
		} else { 
			if ( $split ) // Split card
				$lastcard->split($name, $cost, $types, $text) ;
			else { // Flip card
				$lastcard->flip($name, $types, $text) ;
			}
		}
	} else { // "normal" cards (1 line on mci) or first part of split card
		$rarity = substr($match['rarity'], 0, 1) ;
		if ( preg_match('/\(Color Indicator: (?<color>.{1,100})\)/', $html, $colors_matches) ) // Cards with no casting cost (Ancestral Vision)
			$text = $name.' is '.strtolower(implode(' and ', explode(' ', $colors_matches['color']))).".\n".$text ;
		$lastcard = $importer->addcard($card_url, $rarity, $name, $cost, $types, $text, card_image_url($match), $card_matches['multiverseid']) ;
	}
	// Lang
	$nb = preg_match_all($lang_regex, $html, $matches_lang, PREG_SET_ORDER) ;
	foreach ( $matches_lang as $lang ) {
		$code = $lang['code'] ;
		$url = card_image_url($match, $code) ;
		if ( array_search($lang['code'], array('de', 'fr', 'it', 'es', 'pt')) !== false ) { // Expected charset
			$lname = $lang['name'] ;
			get_split(&$lname) ;
			if ( ! $secondpart )
				$lastcard->setlang($code, $lname, $url) ;
			else {
				if ( ! $doubleface )
					$url = null ;
				if ( $split )
					$lastcard->addlang($code, $lname, $url) ;
				else
					$lastcard->addlangimg($code, $url) ;
			}
		} else
			$lastcard->addlangimg($code, $url) ;
	}
}
?>
