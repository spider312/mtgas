<?php
include '../lib.php' ;
include '../includes/db.php' ;
$result = new simple_object() ;
$email = param_or_die($_GET, 'email') ;
$password = param_or_die($_GET, 'password') ;
$q = query("SELECT `password`, `content` FROM `profile` WHERE `email` = '$email' ;") ;
if ( mysql_num_rows($q) == 0 ) { // Register
	$result->msg = 'email not found, creating account'."\n" ;
	$r = query("INSERT INTO `profile` ( `email`, `password`, `content` ) VALUES ( '$email', MD5('$password'), '{}' )") ;
	if ( mysql_affected_rows() == 1 ) {
		$result->msg .= 'Creation OK' ;
		mail($email, 'Account created on '.$url, 'This email confirms your online profile on '.$url.' has been created.') ;
		$result->send = true ;
		$_SESSION['login'] = $email ;
		$_SESSION['password'] = md5($password) ;
		setcookie('login', $email, 0, '/') ;
	} else
		$result->msg .= 'Creation NOT OK' ;
} else if ( mysql_num_rows($q) == 1 ) { // Login
	if ( $o = mysql_fetch_object($q) ) {
		if ( $o->password == md5($password) ) {
			$result->recieve = $o->content ;
			$_SESSION['login'] = $email ;
			$_SESSION['password'] = md5($password) ;
			setcookie('login', $email, 0, '/') ;
		} else
			$result->msg .= 'Login NOT OK, please check your password' ;
	} else
		$result->msg .= 'Profile unfetchable' ;
} else { // Error
	$result->msg = 'multiple emails found, bug' ;
}
die(json_encode($result))
?>
