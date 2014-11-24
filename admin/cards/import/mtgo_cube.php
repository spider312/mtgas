<?php
// http://magic.wizards.com/en/MTGO/articles/archive/magic-online-legacy-cube-card-list
include_once 'lib.php' ;
$url = param_or_die($_GET, 'url') ;
$html = cache_get($url, 'cache/mtgo_cube', false) ;
$regex = '@<a href=".*?" class="autocard-link" data-image-url=".*?">(?<name>.*?)</a>@' ;
if ( preg_match_all($regex, $html, $cards, PREG_SET_ORDER) < 1 )
	die('No card found') ;
header('Content-Disposition: attachment; filename=OMC.txt') ;
foreach ( $cards as $card ) {
	$name = $card['name'] ;
	$name = html_entity_decode($name) ; // Far &amp; Away
	$name = str_replace('&',  '/', $name) ;
	echo $name."\n" ;
}
?>
