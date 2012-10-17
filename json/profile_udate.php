<?php
include '../lib.php' ;
include '../includes/db.php' ;
$result = new simple_object() ;
$json = param_or_die($_POST, 'json') ;
if ( ! array_key_exists('login', $_SESSION) || ! array_key_exists('password', $_SESSION) )
	die('{}') ;
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
			$json = json_decode(stripslashes($json)) ;
			$content = json_decode($profile->content) ;
			$result->msg = 'Changes'."\n" ;
			foreach( $json as $k => $v ) {
				if ( $v == null ) {
					unset($content->$k) ;
					$result->msg .= ' (deleted)' ;
				} else
					$content->$k = $v ;
			}
			$content = mysql_real_escape_string(json_encode($content)) ;
			$result->msg = $content ;
			// Store merged object
			$u = query("UPDATE `profile` SET `content` = '$content' WHERE `email` = '$email' AND `password` = '$password' ;") ;
			//$result->affected = mysql_affected_rows() ;
		}
	} else
		$result->msg .= 'Profile unfetchable. ' ;
} else { // Error
	$result->msg = 'Multiple emails found. ' ;
}
die(json_encode($result))
?>
