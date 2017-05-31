<?php
ini_set('memory_limit', '1024M');
//require_once '../../mogg/includes/db.php';
require_once '../includes/db.php';
require_once '../includes/lib.php';

$player_id = param_or_die($_GET, 'player_id');
$delay = param_or_die($_GET, 'delay');

$query = "SELECT
	`rating`,
	COUNT(`rating`) AS `nb`
FROM
	`evaluation`
WHERE
	`to` = '$player_id'" ;

if ( $delay != '' ) {
	$query .= " AND `date` > TIMESTAMPADD($delay, -1, NOW()) " ;
}

$query .= "GROUP BY `rating`" ;

$rawRatings = $db->select($query) ;

$result = array() ;
foreach ( $rawRatings as $rating ) {
	$line = new stdClass();
	$line->nb = intval($rating->nb) ;
	$line->rating = intval($rating->rating) ;
	array_push($result, $line) ;
}

die(json_encode($result));
?>
