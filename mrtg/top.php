<?php
$dir = '../ranking' ;
$periods = array('week', 'month') ;

foreach ( $periods as $period ) {
	$path = __DIR__ . '/' . $dir . '/' . $period . '.json' ;
	$json = json_decode(file_get_contents($path)) ;
	echo count((array)$json)."\n" ;
}
