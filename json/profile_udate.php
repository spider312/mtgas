<?php
include '../lib.php' ;
include '../includes/db.php' ;
$result = new simple_object() ;
$json = param_or_die($_POST, 'json') ;
$email = $_SESSION['login'] ;
$password = $_SESSION['password'] ;
$q = query("SELECT `content` FROM `profile` WHERE `email` = '$email' AND `password` = '$password' ;") ;
if ( mysql_num_rows($q) == 0 ) {
	$result->msg = 'Account does not exist or wrong password' ;
} else if ( mysql_num_rows($q) == 1 ) {
	if ( $o = mysql_fetch_object($q) ) {
		// Merge stored object and param
		$json = json_decode(stripslashes($json)) ;
		$content = json_decode($o->content) ;
		$result->msg = 'Changes'."\n" ;
		foreach( $json as $k => $v ) {
			$result->msg .= ' - '.$k.' : '.$content->$k.' -> '.$v ;
			if ( $v == null ) {
				unset($content->$k) ;
				$result->msg .= ' (deleted)' ;
			} else
				$content->$k = $v ;
			$result->msg .= "\n" ;
		}
		$content = mysql_real_escape_string(json_encode($content)) ;
		$result->msg = $content ;
		// Store merged object
		$u = query("UPDATE `profile` SET `content` = '$content' WHERE `email` = '$email' AND `password` = '$password' ;") ;
		$result->affected = mysql_affected_rows() ;
	} else
		$result->msg .= 'Profile unfetchable' ;
} else { // Error
	$result->msg = 'multiple emails found, bug' ;
}
die(json_encode($result))
?>
