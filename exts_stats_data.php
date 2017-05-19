<?php
ini_set('memory_limit', '1024M');
require_once 'includes/db.php';
require_once 'includes/lib.php';

$period = intval(param($_GET, 'period', 100)) ;
$period = min($period, 365) ;
$nb = intval(param($_GET, 'nb', 5)) - 1 ;

// Get raw data
$db->debug = false;
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

// Order data by ext and by date
$exts = array() ;
$allExts = array() ;
foreach( $tournaments as $tournament ) {
	$json = json_decode($tournament->data);
	if ( isset($json->boosters) ) {
		$age = - intval($tournament->age) ;
		foreach ( $json->boosters as $ext ) {
			if ( ! isset($exts[$ext]) ) {
				$exts[$ext] = array() ;
			}
			$myExt =& $exts[$ext] ;
			if ( ! isset($myExt[$age]) ) {
				$myExt[$age] = 0 ;
			}
			$myExt[$age]++ ;
			if ( !isset($allExts[$ext]) ) {
				$allExts[$ext] = 0 ;
			}
			$allExts[$ext]++ ;
		}
	}
}
// Get top extensions
asort($allExts) ;
$topExts = array_keys(array_reverse($allExts));

function getData($label, $data) {
	$obj = new stdClass() ;
	$obj->label = $label;
	$obj->data = array();
	foreach( $data as $age => $value ) {
		array_push($obj->data, array($age, $value));
	}
	return $obj ;
}

// Create results for top extensions
$result = array() ;
$nbTop = min(count($topExts)-1, $nb);
for ( $i = 0 ; $i < $nbTop ; $i++ ) {
	$label = $topExts[$i] ;
	array_push($result, getData($label, $exts[$label]));
	unset($exts[$label]) ;
}

// Merge extensions left
$otherNames = array();
$otherData = array();
foreach ( $exts as $ext => $data ) {
	//if ( count($otherNames) < 5 ) {
		array_push($otherNames, $ext) ;
	//}
	foreach ( $data as $age => $value ) {
		if ( isset($otherData[$age]) ) {
			$otherData[$age] += $value ;
		} else {
			$otherData[$age] = $value ;
		}
	}
}
$str = implode(', ', $otherNames) ;
/*
if ( count($exts) > count($otherNames) ) {
	$str .= ', ...' ;
}
*/
array_push($result, getData('Other (' . $str . ')', $otherData));

die(json_encode($result)) ;
