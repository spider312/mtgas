<?php
require_once('../lib.php');
$id = param_or_die($_GET, 'id') ;
$result = $db->delete("DELETE FROM `config` WHERE `id` = '$id'") ;
die("'$result'") ;
