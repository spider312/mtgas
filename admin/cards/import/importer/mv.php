<?php
// Init
$list_url = 'http://www.magic-ville.com/fr/set_cards.php?setcode='.$ext_source.'&lang=eng' ;
$list_regex = '#<img src="graph/rarity/(?<rarity>.*?)\.gif"><a (class=und)? href=(?<url>carte\.php\?ref=.*?)>(?<name>.*?)</a>#' ;

// Get
$html = cache_get($list_url, 'cache/'.$source.'_'.$ext_source, $verbose) ;
$nb = preg_match_all($list_regex, $html, $matches_list, PREG_SET_ORDER) ;
if ( $nb < 1)
        die('URL '.$list_url.' does not seem to be a valid MCI card list : '.count($matches)) ;

// Parse
foreach ( $matches_list as $match ) { //
	if ( preg_match('#carte\.php\?ref=(?<ext>.*?)(?<id>\d{3})#', $match['url'], $matches_url) ) {
		$mv_ext_name = $matches_url['ext'] ;
		$mv_card_id = $matches_url['id'] ;
	} else
		die('Unparsable URL '.$match['url']) ;
	// Parse card itself
	$url='http://www.magic-ville.com/fr/'.$match['url'] ;
	$path = 'cache/'.str_replace('/', '_', $match['url']) ;
	$html = cache_get($url, $path, $verbose) ;
	// Cost
	$cost = '' ;
	if ( preg_match_all('#<img  height=25 src=graph/manas/big/(?<mana>.{1,2})\.gif>#',  $html, $matches_cost, PREG_SET_ORDER) > 0 )
		foreach ( $matches_cost as $match_cost )
			if ( strlen($match_cost['mana']) == 1 )
				$cost .= $match_cost['mana'] ;
			else
				$cost .= '{'.implode('/', str_split($match_cost['mana'])).'}' ;
	// Name, type and text
	$nb = preg_match_all('#<div style=".*></div>
\s*<div class=S16>(?<name>.*?)</div>
\s*<div class=G12 style="padding-top:4px;padding-bottom:.px;">(?<type>.*)</div>
\s*<div align=center>
\s*<div id=EngShort style="display:block;" class=S1. align=justify>(?<text>.*?)</div>#s', $html, $matches, PREG_SET_ORDER) ;
	if ( $nb < 1 ) {
		echo '<a href="'.$url.'" target="_blank">regex failed</a> -> '.$path ;
		return false ;
	}
	// Text
		// Type specific
	$text = '' ;
	$pow = '' ;
	$tou = '' ;
			// Creature
	if ( preg_match_all('#<div align=right class=G14 style="padding-right:4px;">(?<pt>(?<pow>[^\s]*)/(?<tou>[^\s]*))</div>#', $html, $matches_pt, PREG_SET_ORDER) > 0 ) {
		$pt = $matches_pt[0]['pt'] ;
		$pow = $matches_pt[0]['pow'] ;
		$tou = $matches_pt[0]['tou'] ;
		$text = "$pt\n" ;
			// Planeswalker
	} elseif ( preg_match_all('#<div class=S11 align=right>Loyalty : (?<loyalty>\d*)</div>#', $html, $matches_loyalty, PREG_SET_ORDER) > 0 ) {
		$text = $matches_loyalty[0]['loyalty']."\n" ;
		$matches[0]['text'] = preg_replace('@\s*<tr .*?'.'>\s*<td valign=middle width=36><table width=30 bgcolor=#000000 cellpadding=2 cellspacing=0 align=center><tr><td class=W12 align=center>\s*(.*?)</td></tr></table></td>\s*<td class=S11 align=justify>(.*?)</td>\s*</tr>@', '$1: $2'."\n", $matches[0]['text']) ; // Planeswalkers steps
	}
	$tmp = $matches[0]['text'] ;
	// Cost parsing before strip_tags
	$tmp = preg_replace('#<img style="vertical-align:-20%;" src=graph/manas_c/(.)(.).gif alt="%\1\2">#', '{$1/$2}', $tmp) ; // Hybrid
	$tmp = preg_replace('#<img style="vertical-align:-20%;" src=graph/manas_c/(.).gif alt="%\1">#', '{$1}', $tmp) ; // Normal
	$tmp = str_replace(' : ', ': ', $tmp) ;
	$text .= trim(strip_tags($tmp)) ;
	// Name
	$name = card_name_sanitize($matches[0]['name']) ;
	$type = trim($matches[0]['type']) ;
	// Second part
	if ( $nb == 2 ) {
		$secondname = card_name_sanitize($matches[1]['name']) ;
		$name .= ' / '.$secondname ;
		if ( preg_match('/(?<first>.{1,5})(?<second>\d.*)/', $cost, $matches_cost) ) { // Cut cost by integer
			$cost = $matches_cost['first'] ;
			$altcost = $matches_cost['second'] ;
		} else { // Cut cost equaly (works with every card in DGM)
			$cut = round(strlen($cost)/2) ;
			$altcost =  substr($cost, 0, $cut) ;
			$cost = substr($cost, $cut) ;
		}
		$text .= "\n----\n$altcost\n$type\n".trim(strip_tags($matches[1]['text'])) ;
	}
	$token = false ;
	// Rarity
	$rarity = '' ;
	if ( strpos($name, 'Guildgate') != false )
		$rarity = 'L' ;
	else {
		$rarities = array(4 => 'M', 10 => 'R', 20 => 'U', 30 => 'C') ;
		if ( preg_match_all('#<img src=graph/rarity/carte(\d{1,2}).gif( border=0)?'.'>#', $html, $matches_rarity, PREG_SET_ORDER) > 0 ) {
			$rid = intval($matches_rarity[0][1]) ;
			if ( isset($rarities[$rid]) )
				$rarity = $rarities[$rid] ;
			else {
				echo '?'.$rid.'?' ;
				$token = true ;
			}
		} else
			$token = true ;
	}
	$url = 'http://www.magic-ville.com/pics/big/'.$mv_ext_name.'/'.$mv_card_id.'.jpg' ;
	if ( ! $token )
		$importer->addcard($rarity, $name, $cost, $type, $text, $url) ;
	else
		$importer->addtoken($name, $pow, $tou, $url) ;
}
?>
