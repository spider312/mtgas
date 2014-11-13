<?php
include_once 'lib.php' ;
$url = param_or_die($_GET, 'url') ;
$html = cache_get($url, 'cache/mtgo_cube', false) ;
$regex = '@<td><a class="nodec" keyName="name" keyValue="(?<key>.*?)" onmouseover="OpenTip\(event, this\)" onclick="autoCardWindow\(this\)" href="javascript:void\(\)">(?<name>.*?)</a>(?<bonus>.*?)</td>@' ;
if ( preg_match_all($regex, $html, $cards, PREG_SET_ORDER) < 1 )
	die('No card found') ;
header('Content-Disposition: attachment; filename=OMC.txt') ;
foreach ( $cards as $card )
	echo str_replace('//', ' / ', $card['name'])."\n" ;
?>
