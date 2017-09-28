<?php
require_once('../lib.php');
$id = param_or_die($_GET, 'id') ;
$to = param_or_die($_GET, 'to') ;

$target = $db->select("SELECT `cluster`, `position` FROM `config` WHERE `id` = '$id'"); // Get its current position for swapping
if ( count($target) < 1 ) {
	die('not existing') ;
}
$position = $target[0]->position ;
$cluster = $target[0]->cluster ;
$db->update("UPDATE `config` SET `position` = '$position' WHERE `position` = '$to' AND `cluster` = '$cluster'") ; // Swap if needed
$result = $db->update("UPDATE `config` SET `position` = '$to' WHERE `id` = '$id'") ;

reorder_cluster($cluster) ;

die("'$result'") ;
