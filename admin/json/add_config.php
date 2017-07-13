<?php
require_once('../lib.php');
$cluster = param_or_die($_GET, 'cluster') ;
$name = param_or_die($_GET, 'name') ;
$value = param_or_die($_GET, 'value') ;
$result = $db->insert("INSERT INTO `config` (`cluster`, `name`, `value`, `position`) VALUES ('$cluster', '$name', '$value', '-1')") ;
reorder_cluster($cluster) ;
die("'$result'") ;
