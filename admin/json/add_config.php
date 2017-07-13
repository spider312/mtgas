<?php
require_once('../lib.php');
$cluster = param_or_die($_GET, 'cluster') ;
$name = param_or_die($_GET, 'name') ;
$value = param_or_die($_GET, 'value') ;
$result = $db->insert("INSERT INTO `config` (`cluster`, `name`, `value`) VALUES ('$cluster', '$name', '$value')") ;
die("'$result'") ;
