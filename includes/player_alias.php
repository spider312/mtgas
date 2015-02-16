<?php

function alias_pid($player_id) {
	$aliases = query_as_array("SELECT * FROM `player_alias` WHERE `enabled` = 1 AND `alias` = '$player_id'") ;
	$player_ids = array() ;
	if ( count($aliases) > 0 ) {
		foreach ( $aliases as $alias )
			array_push($player_ids, $alias->player_id) ;
	} else {
		array_push($player_ids, $player_id) ;
	}
	return $player_ids ;
}

function pid2where($player_ids) {
	$where = '' ;
	foreach ( $player_ids as $pid ) {
		if ( $where != '' )
			$where .= ' OR ' ;
		$where .= "`creator_id` = '$pid' OR `joiner_id` = '$pid'" ;
	}
	return $where ;
}

function pid2wheret($player_ids) {
	$where = '' ;
	foreach ( $player_ids as $pid ) {
		if ( $where != '' )
			$where .= ' OR ' ;
		$where .= "`registration`.`player_id` = '$pid'" ;
	}
	return $where ;
}

