<?php
// Mark action passed in param as recieved
include('../lib.php') ;
include('../includes/db.php') ;
$action = param_or_die($_GET, 'action') ;
query("UPDATE `action` SET `recieved` = `recieved`+1 WHERE `id`='$action' ;") ;
die("{}") ;
?>
