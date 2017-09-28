<?php
require_once('../lib.php');
$id = param_or_die($_GET, 'id') ;

$target = $db->select("SELECT `cluster` FROM `config` WHERE `id` = '$id'"); // Get its cluster for reordering
if ( count($target) < 1 ) {
	die('not existing') ;
}
$cluster = $target[0]->cluster ;
$result = $db->delete("DELETE FROM `config` WHERE `id` = '$id'") ;

reorder_cluster($cluster) ;

die("'$result'") ;
