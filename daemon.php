<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'tournament/lib.php' ;
include 'tournament/tournament.php' ;
include 'tournament/limited.php' ;
include $dir.'/includes/card.php' ;
include 'includes/ranking.php' ;
include 'includes/ts3.php' ;

$card_connection = card_connect() ;

$day = 0 ;

while ( sleep($daemon_delay) !== FALSE ) {

	// Random seeder
	//$seed = hexdec(substr( md5( microtime() ), -8 ) ) & 0x7fffffff ; // Hexmaster
	$seed = (double)microtime()*1000000  ; // PHP comments
	mt_srand($seed) ;

	// Day change : stats
	if ( $day != date('j') ) {
		ranking_to_file('ranking/week.json', 'WEEK') ;
		ranking_to_file('ranking/month.json', 'MONTH') ;
		ranking_to_file('ranking/year.json', 'YEAR') ;
		$day = date('j') ;
	}
	// TS3
	ts3_co() ;
	$cid = ts3_chan('Tournament '.$tournament->id, $tournament->name) ; // Create chan
	ts3_invite($players, $cid) ; // Move each tournament's player to chan
	ts3_disco() ;
}
?>
