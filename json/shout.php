<?php
include '../lib.php' ;
include '../includes/db.php' ;
$nick = param($_GET, 'nick', null) ;
$message = param($_GET, 'message', null) ;
$from = param($_GET, 'from', 0) ;

if ( ( $nick != null ) && ( $message != null ) ) {
	$nick = mysql_real_escape_string($nick) ;
	$message = mysql_real_escape_string($message) ;
	query("INSERT
		INTO `shout` (`sender_id`, `sender_nick`, `message`)
		VALUES ('$player_id', '$nick', '$message')") ;
}

$query = "SELECT * FROM `shout` WHERE `id` > $from AND `time` > TIMESTAMPADD(DAY, -1, NOW()) ORDER BY `id` ASC" ;
$shouts = query_as_array($query) ;
die(json_encode($shouts)) ;
?>
