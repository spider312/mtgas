<?php
include '../lib.php' ;
include '../includes/db.php' ;
unset($_SESSION['login']) ;
unset($_SESSION['password']) ;
setcookie('login', false, 0, '/') ;
$result = new simple_object() ;
die(json_encode($result))
?>
