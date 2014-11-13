<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
if ( ! array_key_exists('login', $_SESSION) || ! array_key_exists('password', $_SESSION) )
	die('{"msg": "No login or password in session"}') ;
$result = new stdClass() ;
$json = param_or_die($_POST, 'json') ;
$email = param_or_die($_SESSION, 'login') ;
$password = param_or_die($_SESSION, 'password') ;
$q = query("SELECT `password`, `content` FROM `profile` WHERE `email` = '$email' ;") ;
if ( mysql_num_rows($q) == 0 ) {
	$result->msg = 'Account does not exist. ' ;
} else if ( mysql_num_rows($q) == 1 ) {
	if ( $profile = mysql_fetch_object($q) ) {
		if ( $password != $profile->password ) {
			$result->msg .= 'Wrong password. ' ;
		} else {
			// Merge stored object and param
			$json = json_decode($json) ;
			$content = json_decode($profile->content) ;
			$log = 'Changes'."\n" ;
			foreach( $json as $k => $v ) {
				if ( $v == null ) {
					unset($content->$k) ;
					$log .= $k.' deleted'."\n" ;
				} else {
					if ( property_exists($content, $k) )
						$log .= $k.' : '.$content->$k.' -> '.$v."\n" ;
					else
						$log .= $k.' : '.$v."\n" ;
					$content->$k = $v ;
				}
			}
			// Store merged object
			$content = mysql_real_escape_string(json_encode($content)) ;
			$u = query("UPDATE `profile` SET `content` = '$content' WHERE `email` = '$email' AND `password` = '$password' ;") ;
			// Return
			$result->affected = mysql_affected_rows() ;
			$result->log = $log ;
		}
	} else
		$result->msg .= 'Profile unfetchable. ' ;
} else { // Error
	$result->msg = 'Multiple emails found. ' ;
}
die(json_encode($result))
?>
