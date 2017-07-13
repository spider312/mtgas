<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../lib.php' ;
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../includes/db.php' ;
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../includes/card.php' ;

// Config management
function reorder_cluster($cluster=null) {
	global $db ;
	if ( $cluster === null ) {
		die('reorder_cluster : missing $cluster') ;
	}
	$cluster_lines = $db->select("SELECT `id` FROM `config` WHERE `cluster` = '$cluster' ORDER BY `position`, `id`") ;
	foreach ( $cluster_lines as $position => $line ) {
		$db->update("UPDATE `config` SET `position` = '$position' WHERE `id` = '{$line->id}'") ;
	}
}
?>
