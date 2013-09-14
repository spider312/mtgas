<?php
// Init
$importer->init('http://mythicspoiler.com/'.$ext_source.'/index.html') ; // Sort by color
//$importer->init('http://mythicspoiler.com/'.$ext_source.'/numbercrunch.html') ; // Sort by number, does not seem up to date
$list_regex = '#<a href="cards/(?<url>.*?).html"><img width="200" align="left" hspace="0" jace src="cards/(?<img>.*?).jpg"></a>#' ;
//$list_regex = '#[<a href="cards/(?<name>.*).html"><img width="200" align="left" hspace="0" jace src="cards/(?<img>.*).jpg"></a>|<a href="cards/(?<name>.*).html"><img align="left" hspace="0" width="200" src="cards/(?<img>.*).jpg"></a>]#' ;

// Get
$html = cache_get($importer->url, 'cache/'.$source.'_'.$ext_source, $verbose) ;

// Parse extension data
$nb = preg_match_all('#<li><a href="index.html"> (?<name>.*?) (?<spoiled>.*?)/(?<cards>.*?)</a>#', $html, $matches_ext, PREG_SET_ORDER) ;
if ( $nb != 1)
        die('URL '.$importer->url.' does not seem to be a valid Mythic Spoiler card list : '.count($matches_ext).' info') ;
$importer->setext($ext_source, $matches_ext[0]['name'], $matches_ext[0]['cards']) ;

// Parse cards
$nb = preg_match_all($list_regex, $html, $matches_list, PREG_SET_ORDER) ;
if ( $nb < 1)
        die('URL '.$importer->url.' does not seem to be a valid Mythic Spoiler card list : '.count($matches_list).' cards') ;

foreach ( $matches_list as $match ) {
	// Parse card itself
	$url = 'http://mythicspoiler.com/'.$ext_source.'/'.$match['url'].'.html' ;
	$path = 'cache/'.str_replace('/', '_', $url) ;
	$html = cache_get($url, $path, $verbose) ;
	// Name, cost, type and text
/*	$nb = preg_match('#<!--CARD NAME-->
(?<name>.*?)

</font></b></td></tr><tr><td colspan="2" valign="top">

<!--MANA COST-->
(?<cost>.*?)

</td></tr><tr><td colspan="2" valign="top">

<!--TYPE-->
(?<type>.*?)

</td></tr><tr><td colspan="2" valign="top">

<!--CARD TEXT-->
(?<text>.*?)
</td></tr><tr><td colspan="2" valign="top" ><i>
.*
(<!--P/T-->
(?<pt>\n*'.'/\n*))?#', $html, $match_card) ;
*/
	$nb = preg_match('#<!--CARD NAME-->
(?<name>.*)

#', $html, $match_card) ;
	if ( $nb < 1 ) {
		echo '<a href="'.$url.'" target="_blank">regex failed</a> -> '.$path."\n" ;
		return false ;
	}

	// Text
		// Type specific
	$text = '' ;
			// Creature
	if ( isset($matches[0]['pt']) )
		$text = $matches[0]['pt']."\n" ;
			// Planeswalker
	elseif ( preg_match_all('#<div class=S11 align=right>Loyalty : (?<loyalty>\d*)</div>#', $html, $matches_loyalty, PREG_SET_ORDER) > 0 ) {
		$text = $matches_loyalty[0]['loyalty']."\n" ;
		$matches[0]['text'] = preg_replace('@\s*<tr .*?'.'>\s*<td valign=middle width=36><table width=30 bgcolor=#000000 cellpadding=2 cellspacing=0 align=center><tr><td class=W12 align=center>\s*(.*?)</td></tr></table></td>\s*<td class=S11 align=justify>(.*?)</td>\s*</tr>@', '$1: $2'."\n", $matches[0]['text']) ; // Planeswalkers steps
	}
	$text .= mv2txt($matches[0]['text']) ;
	// Name
	$name = card_name_sanitize($matches[0]['name']) ;
	$card = null ;
	if ( ! preg_match('#<div>\d{1,2}/\d{1,2}</div>#', $html) ) { // Tokens are numbered
		$type = trim($matches[0]['type']) ;
		// Rarity
		$rarity = '' ;
		if ( strpos($type, 'Gate') != false ) // DGM Gates must be considered as a land in DB
			$rarity = 'L' ;
		else {
			if ( preg_match_all('#<img src=graph/rarity/carte(\d{1,2}).gif( border=0)?'.'>#', $html, $matches_rarity, PREG_SET_ORDER) > 0 ) {
				$rid = intval($matches_rarity[0][1]) ;
				if ( isset($rarities[$rid]) )
					$rarity = $rarities[$rid] ;
				else
					echo 'Unknown rarity ID : "'.$rid.'"' ;
			} else {
				if ( array_search($name, array('Forest', 'Island', 'Mountain', 'Plains', 'Swamp')) > -1 )
					$rarity = 'L' ;
				else
					$rarity = 'C' ; // In doubt
				$importer->errors['Rarity icon not found'][] = $url ;
			}
		}
		$card = $importer->addcard($url, $rarity, $name, mv2cost($matches[0]['cost']), $type, $text, card_image_url($mv_ext_name.'/'.$mv_card_id)) ;
	} else {
		$pow = '' ;
		$tou = '' ;
		if ( isset($matches[0]['pow']) )
			$pow = $matches[0]['pow'] ;
		if ( isset($matches[0]['tou']) )
			$tou = $matches[0]['tou'] ;
		$importer->addtoken($url, $name, $pow, $tou, card_image_url($mv_ext_name.'/'.$mv_card_id)) ;
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
		$nbmoon = preg_match_all($reg_moon, $html, $matches_moon, PREG_SET_ORDER) ;
		if ( $nbmoon > 0 ) {
			$match_moon = $matches_moon[1] ; // 0 is french version
			$nbcolor = preg_match_all('#<img  height=10 src=graph/manas/l(?<color>.).gif>#',
				$match_moon['colors'], $matches_colors, PREG_SET_ORDER) ;
			$ci = '' ;
			if ( $nbcolor > 0 )
				foreach ( $matches_colors as $color )
					$ci .=  $color['color'] ;
			else
				echo 'Color not found<br>' ;
			$text = mv2txt($match_moon['text']) ;
			if ( isset($matches_moon[1]['pt']) )
				$text = $matches_moon[1]['pow'].'/'.$matches_moon[1]['tou']."\n".$text ;
			$card->transform(card_name_sanitize($match_moon['name']), $ci, $match_moon['type'], $text, card_image_url($mv_ext_name.'/'.$mv_card_id.'f')) ;
		}
	}
}
?>
