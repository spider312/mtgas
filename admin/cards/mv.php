<?php
#!/bin/php
include_once '../../config.php' ;
include_once 'lib.php' ;
include_once '../../includes/lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'import.php' ;

// Args
if ( isset($argv) && ( count($argv) > 1 ) ) { // CLI
	if ( count($argv) > 1 )
		$ext = $argv[1] ;
	else
		die($argv[0].' [extension code]') ;
	if ( count($argv) > 2 )
		$ext_mci = $argv[2] ;
	else
		$ext_mci = $ext ;
	if ( ( count($argv) > 3 ) && $argv[3] )
		$apply = $argv[3] ;
	else
		$apply = false ;
} else { // Web
	$ext = param_or_die($_GET, 'ext') ;
	$ext_mci = param($_GET, 'ext_mci', $ext) ;
	$apply = param($_GET, 'apply', false) ;
}

$mv_ext_name = $ext_mci ; // Magic-ville's one !
$mogg_ext_name = $ext ;
$nbcards = 229 ;
$nbtokens = 16 ;

$basecardurl = 'http://www.magic-ville.com/fr/carte?'.$mv_ext_name ;
$baseimgurl = 'http://www.magic-ville.com/pics/big/'.$mv_ext_name.'/' ;

function get_card($i, $token = false) { // Get the HTML file for card $i, then get and rename image according to card name
	global $mv_ext_name, $mogg_ext_name, $basecardurl, $baseimgurl, $apply, $ext_id ;
	$si = str_pad($i, 3, '0', STR_PAD_LEFT);
	echo '<li>'.$i.' : ' ;
	// Try to get HTML file from cache
	$html = cache_get($basecardurl.$si, 'cache/'.$mv_ext_name.$si) ;
	//$html = preg_replace('/\s{1,}/', ' ', $html) ; // No excessive whitespaces
	$html = preg_replace('#<td class=W12 align=center>\s*(.?\d*)</td>#', '[$1]', $html) ; // Planeswalkers steps
	// TODO : Costs in text are images, removed during parsing
	$html = str_replace("\n", '', $html) ; // Required for regex parsing
	// Cost
	$cost = '' ;
	if ( preg_match_all('#<img  height=25 src=graph/manas/big/(?<mana>.)\.gif>#',  $html, $matches, PREG_SET_ORDER) > 0 )
		foreach ( $matches as $match )
			$cost .= $match['mana'] ;
	// Name, type and text
	$nb = preg_match_all('#<div class=S16>(?<name>.{1,100})</div>\s*<div class=G12 style="padding-top:4px;padding-bottom:.px;">(?<type>.{1,100})</div>\s*<div align=center>\s*<div id=EngShort.*?'.'>(?<text>.*?)</div>#', $html, $matches, PREG_SET_ORDER) ;
	if ( $nb < 1 ) {
		echo 'regex failed' ;
		return false ;
	}
	// P/T
	if ( preg_match_all('#<div align=right class=G14 style="padding-right:4px;">(?<pt>\d*/\d*)</div>#', $html, $matches_pt, PREG_SET_ORDER) > 0 ) {
		$pt = $matches_pt[0]['pt'] ;
		$text = "$pt\n" ;
	} elseif ( preg_match_all('#<div class=S11 align=right>Loyalty : (?<loyalty>\d*)</div>#', $html, $matches_loyalty, PREG_SET_ORDER) > 0 ) 
		$text = $matches_loyalty[0]['loyalty']."\n" ;
	else
		$text = '' ;
	$text .= trim(strip_tags($matches[0]['text'])) ;
	$name = card_name_sanitize($matches[0]['name']) ;
	$basedestfilename = $name ;
	$type = trim($matches[0]['type']) ;
	if ( $nb == 2 ) { // Second part
		$secondname = card_name_sanitize($matches[1]['name']) ;
		$name .= ' / '.$secondname ;
		$basedestfilename .= $secondname ;
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
	/*/
	if ( $text != '' )
		echo '<pre>'.$text.'</pre>' ;
	else
		echo '<hr>' ;
	for ( $i = 0 ; $i < strlen($name) ; $i++ )
		echo $name[$i].' : '.ord($name[$i]).'<br>' ;
	/**/
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
			else
				echo '!'.$rid.'!' ;
		} else
			echo '!!' ;
	}
	echo "$rarity $name ($cost".(isset($altcost)?"/$altcost":'').") $type".(isset($pt)?" [$pt]":'') ;
	// DB
	if ( $apply ) {
		if ( ! $token ) {
			$card_id = card_import($name, $cost, $type, $text) ;
			$multiverseid = '' ;
			query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`, `multiverseid`) VALUES ('$card_id', '$ext_id', '$rarity', '1', '$multiverseid') ;") ;
		}
		// Create destination file name (and dir)
		if ( preg_match('/\<b\>(\d+)\<\/b\>/', $html, $matchesnb) )
			$basedestfilename .= $matchesnb[1] ;
		$disp = '' ;
		/*
		$nbpic = 0 ;
		do {
			$disp = '' ;
			if ( $nbpic > 0 )
				$disp = ''.$nbpic ;
				*/
			if ( $token )
				$destfilename = 'TK/'.$mogg_ext_name.'/'.$basedestfilename.$disp.'.1.1.jpg' ;
			else
				$destfilename = $mogg_ext_name.'/'.$basedestfilename.$disp.'.full.jpg' ;
				/*
			$nbpic++ ;
		} while ( is_file($destfilename) ) ;
		*/
		echo $destfilename.' : ' ;
		// Get the file
		if ( ! is_file($destfilename) ) {
			$content = @file_get_contents($baseimgurl.$si.'.jpg') ;
			if ( $content === false )
				echo 'image '.$baseimgurl.$si.'not found' ;
			else {
				$size = @file_put_contents($destfilename, $content) ;
				if ( $size === false )
					echo 'NOT updated' ;
				else
					echo 'updated ('.human_filesize($size).')' ;
			}
		} else
			echo 'existing' ;
	}
	echo "</li>\n" ;
	return true ;
}
$ext = strtoupper($ext) ;
$query = query("SELECT * FROM extension WHERE `se` = '$ext' OR `sea` = '$ext' ; ") ;
if ( $res = mysql_fetch_object($query) ) {
	$ext_id = $res->id ;
	if ( $apply) {
		query("DELETE FROM `card_ext` WHERE `ext` = '$ext_id'") ;
		echo '  <p>'.mysql_affected_rows().' cards unlinked from '.$ext."</p>\n\n" ;
	}
} else {
	$query = query("INSERT INTO extension (`se`, `name`) VALUES ('$ext', '".mysql_real_escape_string($matches[0]['ext'])."')") ;
	echo '<p>Extension not existing, creating</p>' ;
	$ext_id = mysql_insert_id() ;
}

// Get cards
if ( ! is_dir($mogg_ext_name) )
	mkdir($mogg_ext_name) ;
echo "<ul>\n" ;
for ( $i = 1 ; $i <= $nbcards ; $i++ )
	get_card($i, false) ;
echo '</ul>' ;
// Get tokens
if ( $nbtokens > 0 ) {
	if ( ! is_dir('TK') )
		mkdir('TK') ;
	if ( ! is_dir('TK/'.$mogg_ext_name) )
		mkdir('TK/'.$mogg_ext_name) ;
	for ( $i = $nbcards ; $i <= $nbcards + $nbtokens ; $i++ )
		get_card($i, true) ;
}
?>
