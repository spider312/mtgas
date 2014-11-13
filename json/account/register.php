<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
$result = new stdClass() ;
$email = param_or_die($_GET, 'email') ;
$password = param_or_die($_GET, 'password') ;
$remember = param_or_die($_GET, 'remember') ;
$q = query("SELECT `password`, `content` FROM `profile` WHERE `email` = '$email' ;") ;
if ( mysql_num_rows($q) != 0 )
	$result->msg = $email.' already registered' ;
else { // Register
	$r = query("INSERT INTO `profile` ( `email`, `password`, `content` ) VALUES ( '$email', MD5('$password'), '{}' )") ;
	if ( mysql_affected_rows() == 1 ) {
		mail($email, 'Account created on '.$url, 'This email confirms your online profile on '.$url.' has been created.') ;
		$_SESSION['login'] = $email ;
		$_SESSION['password'] = md5($password) ;
		setcookie('login', $email, $cookie_expire, '/') ;
		if ( $remember == 'true' )
			setcookie('password', $password, $cookie_expire, '/') ;
		else
			setcookie('password', false, 0, '/') ;
	} else
		$result->msg .= 'Creation NOT OK' ;
}
die(json_encode($result))
?>
