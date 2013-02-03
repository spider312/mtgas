<?php
#!/bin/php

$mv_ext_name = 'gtc' ; // Magic-ville's one !
$mogg_ext_name = 'GTC' ;
$nbcards = 249 ;
$nbtokens = 8 ;

$basecardurl = 'http://www.magic-ville.com/fr/carte?'.$mv_ext_name ;
$baseimgurl = 'http://www.magic-ville.com/pics/big/'.$mv_ext_name.'/' ;

function human_filesize($bytes, $decimals = 2) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
function get_card($i, $token = false) { // Get the HTML file for card $i, then get and rename image according to card name
	global $mv_ext_name, $mogg_ext_name, $basecardurl, $baseimgurl ;
	$si = str_pad($i, 3, '0', STR_PAD_LEFT);
	echo '<li>'.$i.' : ' ;
	// Try to get HTML file from cache
	$url = $basecardurl.$si ;
	$cache_file = 'cache/'.$mv_ext_name.$si ;
	if ( file_exists($cache_file) ) {
		echo 'use cache : ' ;
		$html = @file_get_contents($cache_file) ;
	} else {
		echo 'upd cache : ' ;
		$html = @file_get_contents($url) ;
		if ( $html === FALSE ) {
			echo "page not found\n" ;
			return FALSE ;
		} else {
			$size = @file_put_contents($cache_file, $html) ;
			if ( $size === FALSE )
				echo 'NOT updated' ;
			else
				echo 'updated ('.human_filesize($size).')' ;
		}
	}
	if ( $html === FALSE ) {
		echo "cache not found\n" ;
		return FALSE;
	}
	// Search card name
	preg_match_all("/\<div class=S16\>(.*)\<\/div\>/", $html, $matches, PREG_SET_ORDER) ;
	if ( count($matches) == 0 ) {
		echo "not a card\n" ;
		return FALSE ;
	}
	$lastmatch = $matches[count($matches)-1] ;
	$basedestfilename = trim($lastmatch[1]) ;
	// Create destination file name (and dir)
	if ( preg_match('/\<b\>(\d+)\<\/b\>/', $html, $matchesnb) )
		$basedestfilename .= $matchesnb[1] ;
	$nbpic = 0 ;
	do {
		$disp = '' ;
		if ( $nbpic > 0 )
			$disp = ''.$nbpic ;
		if ( $token )
			$destfilename = 'TK/'.$mogg_ext_name.'/'.$basedestfilename.$disp.'.1.1.jpg' ;
		else
			$destfilename = $mogg_ext_name.'/'.$basedestfilename.$disp.'.full.jpg' ;
		$nbpic++ ;
	} while ( is_file($destfilename) ) ;
	echo $destfilename.' : ' ;
	// Get the file
	if ( ! is_file($destfilename) ) {
		$content = @file_get_contents($baseimgurl.$si.'.jpg') ;
		if ( $content === FALSE )
			echo 'image not found' ;
		else {
			$size = @file_put_contents($destfilename, $content) ;
			if ( $size === FALSE )
				echo 'NOT updated' ;
			else
				echo 'updated ('.human_filesize($size).')' ;
		}
	} else
		echo 'existing' ;
	echo "</li>\n" ;
	return TRUE ;
}
// Get cards
if ( ! is_dir($mogg_ext_name) )
	mkdir($mogg_ext_name) ;
echo '<ul>' ;
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
