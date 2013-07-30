<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
unset($_SESSION['login']) ;
unset($_SESSION['password']) ;
setcookie('login', false, $cookie_expire, '/') ;
setcookie('password', false, $cookie_expire, '/') ;
$result = new simple_object() ;
die(json_encode($result))
?>
