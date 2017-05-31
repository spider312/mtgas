<?php
ini_set('memory_limit', '1024M');
require_once '../../mogg/includes/db.php';
require_once '../includes/lib.php';

$period = intval(param($_GET, 'period', 100)) ;
$period = min($period, 1000) ;
$nb = intval(param($_GET, 'nb', 5)) - 1 ;
$nbOther = 5 ; // Number of "others" to display in others's label
$nbOther = intval(param($_GET, 'nbother', $nbOther)) ;
$percent = param($_GET, 'percent', false) ;

function needUpdate($file) {
	global $use_cache ;
	if ( ! $use_cache ) {
		return true ;
	}
	if ( ! file_exists($file) ) {
		return true ;
	}
	$format = 'Y-m-d' ; // Format used to compare date param and today's date (e.g. Y-m-d to detect day change)
	return ( date($format, filemtime($file)) !== date($format) ) ;
}

$raw_cache_file = 'exts_stats/'.$period.'.json' ;
$cache_file = 'exts_stats/'.$period.'_'.$nb.($percent?'_percent':'').'.json' ;
$use_cache = true ;

if ( ! needUpdate($cache_file) ) {
	die(@file_get_contents($cache_file));
}

if ( ! needUpdate($raw_cache_file) ) {
	$tournaments = json_decode(@file_get_contents($raw_cache_file)) ;
} else {	
	// Get raw data
	$db->debug = false;
	$tournaments = $db->select('
	SELECT
		DATEDIFF(CURDATE(), `creation_date`) AS `age`,
		`data`
	FROM
		`tournament`
	WHERE
		DATE(`creation_date`) < CURDATE() /* Dont select todays tournament as day is not finished thus not comparable with others */
		AND
		DATEDIFF(CURDATE(), `creation_date`) <= '.$period.'
	ORDER BY
		`age`
	');
	@file_put_contents($raw_cache_file, json_encode($tournaments)) ;
}

// Order data by ext and by date
$defaultAge = intval(time() / 86400) ;
$exts = array() ; // Detailed counter by ext then by age
$allExts = array() ; // Counter of opened boosters by ext
$allAges = array() ; // Counter of opened boosters by age
foreach( $tournaments as $tournament ) {
	$json = json_decode($tournament->data);
	if ( isset($json->boosters) ) {
		$age = intval($tournament->age) ;
		foreach ( $json->boosters as $ext ) {
			// Detailed data
			if ( ! isset($exts[$ext]) ) {
				$exts[$ext] = array() ;
			}
			$myExt =& $exts[$ext] ;
			if ( ! isset($myExt[$age]) ) {
				$myExt[$age] = 0 ;
			}
			$myExt[$age]++ ;
			// Counter by ext
			if ( !isset($allExts[$ext]) ) {
				$allExts[$ext] = 0 ;
			}
			$allExts[$ext]++ ;
			// Counter by age
			if ( !isset($allAges[$age]) ) {
				$allAges[$age] = 0 ;
			}
			$allAges[$age]++ ;
		}
	}
}
// Get top extensions
asort($allExts) ;
$topExts = array_keys(array_reverse($allExts));

function getData($label, $data) {
	global $defaultAge, $percent, $allAges ;
	$obj = new stdClass() ;
	$obj->label = $label;
	$obj->data = array();
	foreach( $data as $age => $value ) {
		if ( $percent ) {
			$value /= .01 * $allAges[$age] ;
		}
		array_push($obj->data, array($defaultAge - $age, $value));
	}
	return $obj ;
}

// Create results for top extensions
$result = array() ;
$nbTop = min(count($topExts)-1, $nb);
for ( $i = 0 ; $i < $nbTop ; $i++ ) {
	$label = $topExts[$i] ;
	$fullLabel = $label ;
	//$fullLabel .= ' (' . $allExts[$label] . ')' ;
	array_push($result, getData($label, $exts[$label]));
	unset($exts[$label]) ;
}

// Merge extensions left
$otherNames = array();
$otherData = array();
foreach ( $exts as $ext => $data ) {
	if ( count($otherNames) < $nbOther ) {
		array_push($otherNames, $ext) ;
	}
	foreach ( $data as $age => $value ) {
		if ( isset($otherData[$age]) ) {
			$otherData[$age] += $value ;
		} else {
			$otherData[$age] = $value ;
		}
	}
}
if ( count($exts) > count($otherNames) ) {
	array_push($otherNames, '...') ;
}
$str = implode(', ', $otherNames) ;
array_push($result, getData('Other (' . $str . ')', $otherData)) ;

@file_put_contents($cache_file, json_encode($result)) ;

die(json_encode($result)) ;
