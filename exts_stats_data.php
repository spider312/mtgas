<?php
ini_set('memory_limit', '1024M');
require_once 'includes/db.php';
require_once 'includes/lib.php';

$period = intval(param($_GET, 'period', 100)) ;

$tournaments = $db->select('
SELECT
	DATEDIFF(CURDATE(), `creation_date`) AS `age`,
	`data`
FROM
	`tournament`
WHERE
	DATE_SUB(CURDATE(), INTERVAL '.$period.' DAY) <= `creation_date`
ORDER BY
	`age`
');

$exts = array() ;
$total_boosters = 0 ;
foreach( $tournaments as $tournament ) {
	$json = json_decode($tournament->data);
	if ( isset($json->boosters) ) {
		$age = intval($tournament->age) ;
		foreach ( $json->boosters as $ext ) {
			if ( ! isset($exts[$ext]) ) {
				$obj = new stdClass() ;
				$obj->label = $ext ;
				$obj->data = array() ; //_fill(0, $period, 0) ;
				$exts[$ext] = $obj ;
			}
			$myExt =& $exts[$ext]->data ;
			if ( ! isset($myExt[$age]) ) {
				$myExt[$age] = 0 ;
			}
			$myExt[$age]++ ;
			$total_boosters++ ;
		}
	}
}
foreach( $exts as $ext ) {
	$oldArr = $ext->data ;
	$newArr = array() ;
	foreach ( $oldArr as $day => $value ) {
		if ( $value !== 0 ) {
			array_push($newArr, array($day, $value)) ;
		}
	}
	$ext->data = $newArr ;
}

die(json_encode(array_values($exts))) ;
