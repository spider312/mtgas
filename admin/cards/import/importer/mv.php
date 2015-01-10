<?php
// Init
$importer->init('http://www.magic-ville.com/fr/set_cards.php?setcode='.$ext_source.'&lang=eng') ;
$ext_regex_parts = array() ;
$ext_regex_parts[] = '<title>(?<name>.*?) - magic-ville.com</title>' ;
$ext_regex_parts[] = '<img src="graph/bigsetlogos/(?<code>.*?)\.(gif|png)">' ;
$ext_regex_parts[] = '<div>(?<cards>\d*) cartes, sortie en (?<month>.*?) (?<year>\d*)</div>' ;
//'#<title>(?<name>.*?) - magic-ville.com</title>.*<img src="graph/bigsetlogos/(?<code>.*?)\.(gif|png)">.*<div>(?<cards>\d*) cartes, sortie en (?<month>.*?) (?<year>\d*)</div>#s'
$ext_regex = '#'.implode('.*', $ext_regex_parts).'#s' ;
//die(htmlspecialchars($ext_regex)) ;
$list_regex = '#<img src="graph/rarity/(?<rarity>.*?)\.gif"><a (class=und)? href=(?<url>carte\.php\?ref=.*?)>(?<name>.*?)</a>#' ;
$reg_flip = '#<div class=S16>(?<name>[^<>]*?)</div><div class=G12 style="padding-top:4px;padding-bottom:3px;">(?<type>[^<>]*?)</div><div style="display:block;" class=S12 align=justify>(?<text>.*?)</div>#s' ;
$reg_moon = '#<div class=S16><img src=graph/moteur/moon.png> (?<name>.*)</div><div class=G12 style="padding-top:4px;padding-bottom:3px;">(?<colors><img .*>) (?<type>.*)</div><div style="display:block;" class=S12 align=justify>(?<text>.*)</div><div align=right class=G14 style="padding-right:4px;">(?<pt>(?<pow>[^\s]*)/(?<tou>[^\s]*))?</div>#Us' ;
$rarities = array(4 => 'M', 10 => 'R', 20 => 'U', 30 => 'C', 40 => 'L') ;

// Lib
function card_image_url($url) {
	return 'http://www.magic-ville.com/pics/big/'.$url.'.jpg' ;
}

// Get
$html = cache_get($importer->url, 'cache/'.$source.'_'.$ext_source, $verbose) ;

// Parse extension data
$nb = preg_match_all($ext_regex, $html, $matches_ext, PREG_SET_ORDER) ;
if ( $nb != 1)
        die('URL '.$importer->url.' does not seem to be a valid MCI extension page : '.count($matches_ext).'<br>'.$html) ;
$extcode = $matches_ext[0]['code'] ;
$importer->setext($extcode, $matches_ext[0]['name'], $matches_ext[0]['cards']) ;

// Parse cards
$nb = preg_match_all($list_regex, $html, $matches_list, PREG_SET_ORDER) ;
if ( $nb < 1)
        die('URL '.$importer->url.' does not seem to be a valid MCI card list : '.count($matches)) ;

foreach ( $matches_list as $match ) { //
	if ( preg_match('#carte\.php\?ref=(?<ext>.*?)(?<id>\d{3})#', $match['url'], $matches_url) ) {
		$mv_ext_name = $matches_url['ext'] ;
		$mv_card_id = $matches_url['id'] ;
	} else
		die('Unparsable URL '.$match['url']) ;
	// Parse card itself
	$url = 'http://www.magic-ville.com/fr/'.$match['url'] ;
	$path = 'cache/'.str_replace('/', '_', $url) ;
	$html = cache_get($url, $path, $verbose) ;
	// Name, cost, type, text and image
	$info_regex = '' ;
	$info_regex .= 'value="(?<number>\d*?)".*?';
	$info_regex .= '<div style=".*?" align=right>(?<cost>\<img.*?'.'\>)?</div>.*?' ; // Cost
	$info_regex .= '<div class=S16>(?<frname>.*?)</div>.*?' ; // French name
	$info_regex .= '<div class=S16>(<img src=graph/moteur/sun.png> )?(?<name>[^<>]*?)</div>
\s*<div class=G12 style="padding-top:4px;padding-bottom:\dpx;">(?<type>[^<>]*?)</div>
\s*<div align=center>
\s*<div id=EngShort style="display:block;" class=S1\d align=justify>(?<text>.*?)</div>(
.*?<div align=right class=G14 style="padding-right:\dpx;">(?<pt>(?<pow>[^\s]*)/(?<tou>[^\s]*))?</div>)?' ;
	$info_regex .= '.*<img src=scan\?(?<imgurl>.*?)></td></tr>' ;
	$nb = preg_match_all("#$info_regex#s", $html, $matches, PREG_SET_ORDER) ;
	if ( $nb < 1 ) {
		echo '<a href="'.$url.'" target="_blank">regex failed</a> -> '.$path."\n" ;
		die($html);
		continue ;
	}
	// Text
		// Type specific
	$text = '' ;
			// Creature
	if ( isset($matches[0]['pt']) )
		$text = $matches[0]['pt']."\n" ;
			// Planeswalker
	if ( preg_match('#<div class=S11 align=right>Loyalty : (?<loyalty>\d)</div>#', $html, $matches_loyalty) ) {
		$text = $matches_loyalty['loyalty']."\n" ;
		$matches[0]['text'] = preg_replace('@\s*<tr .*?'.'>\s*<td valign=middle width=36><table width=30 bgcolor=#000000 cellpadding=2 cellspacing=0 align=center><tr><td class=W12 align=center>\s*(.*?)</td></tr></table></td>\s*<td class=S11 align=justify>(.*?)</td>\s*</tr>@', '$1: $2'."\n", $matches[0]['text']) ; // Planeswalkers steps
	}
	$text .= mv2txt($matches[0]['text']) ;
	// Name
	$name = card_name_sanitize($matches[0]['name']) ;
	$card = null ;
	$type = trim($matches[0]['type']) ;
	$cost = mv2cost($matches[0]['cost']) ;
	if ( intval($matches[0]['number']) > $importer->nbcards ) {
		$pow = '' ;
		$tou = '' ;
		if ( isset($matches[0]['pow']) )
			$pow = $matches[0]['pow'] ;
		if ( isset($matches[0]['tou']) )
			$tou = $matches[0]['tou'] ;
		$importer->addtoken($url, $name, $pow, $tou, card_image_url($mv_ext_name.'/'.$mv_card_id)) ;
	} else {
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
				$importer->adderror('Rarity icon not found', $url) ;
			}
		}
		// Image (scan-lowres / hires)
		//$img_url = card_image_url($mv_ext_name.'/'.$mv_card_id) ; // Default Hires but not always managed in early imports
		$img_url = 'http://www.magic-ville.com/fr/scan?'.$matches[0]['imgurl'] ;
		/*
		if ( preg_match('#<img src=..(?<img>/pics/big/.*?.jpg)>#', $html, $match_img) )
			$img_url = 'http://www.magic-ville.com/'.$match_img['img'] ;
		else {
			if ( preg_match('#background:url\(scan\?(?<scan>.*)\)#', $html, $match_img) )
				$img_url = 'http://www.magic-ville.com/fr/scan?'.$match_img['scan'] ;
		}*/
		$card = $importer->addcard($url, $rarity, $name, $cost, $type, $text, $img_url) ;
		$card->addlang('fr', html_entity_decode(trim($matches[0]['frname']), ENT_COMPAT, 'UTF-8')) ;
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
