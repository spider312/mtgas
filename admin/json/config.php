<?php
require_once('../lib.php');

$config = param($_GET, 'config', null) ;

$result = array() ;

if ( $config === null ) {
	$lines = $db->select("SELECT `id`, `cluster`, `name`, `value`, `position` FROM `config` ORDER BY `cluster`, `position`") ;
	$cluster = null ;
	$clusterName = null ;
	foreach ( $lines as $line ) {
		if ( $line->cluster !== $clusterName ) {
			$clusterName = $line->cluster ;
			$cluster = array() ;
			array_push($result, $cluster) ;
		}
		array_push($cluster, $line) ;
	}
} else {
	$result = $db->select("SELECT `id`, `name`, `value`, `position` FROM `config` WHERE `cluster` = '$config' ORDER BY `position`") ;
}

die(json_encode($result)) ;
