<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
$result = new simple_object() ;
$email = param_or_die($_GET, 'email') ;
$password = md5(param_or_die($_GET, 'password')) ;
$remember = param_or_die($_GET, 'remember') ;
$q = query("SELECT `password`, `content` FROM `profile` WHERE `email` = '$email' ;") ;
if ( mysql_num_rows($q) == 0 )
	$result->msg = 'E-mail not found, please check it or create an account'."\n" ;
else if ( mysql_num_rows($q) == 1 ) {
	if ( $o = mysql_fetch_object($q) ) {
		if ( $o->password == $password ) {
			$result->recieve = $o->content ;
			$_SESSION['login'] = $email ;
			$_SESSION['password'] = $password ;
			setcookie('login', $email, $cookie_expire, '/') ;
			if ( $remember == 'true' )
				setcookie('password', $password, $cookie_expire, '/') ;
			else
				setcookie('password', false, 0, '/') ;
		} else
			$result->msg = 'Wrong password for adress '.$email ;
	} else
		$result->msg = 'Profile unfetchable' ;
} else // Error
	$result->msg = 'Multiple accounts found, please contact an admin' ;
die(json_encode($result))
?>
