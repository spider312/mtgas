<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../lib.php' ;
/*
function tournament_constructed($type) {
	switch ( $type ) {
		case 'draft' :
		case 'sealed' :
			return false ;
		case 'vintage' :
		case 'legacy' :
		case 'modern' :
		case 'extend' :
		case 'standard' :
		case 'edh' :
			return true ;
		default :
			return null ;
	}
}
function tournament_limited($type) {
	switch ( $type ) {
		case 'draft' :
		case 'sealed' :
			return true ;
		case 'vintage' :
		case 'legacy' :
		case 'modern' :
		case 'extend' :
		case 'standard' :
		case 'edh' :
			return false ;
		default :
			return null ;
	}
}
function player_status($stat, $ready) {
	$result = 'Initializing' ;
	switch ( intval($stat) ) {
		case 0 :
			$result = 'Waiting' ;
			break;
		case 1 :
			$result = 'Redirecting' ;
			break;
		case 2 :
			if ( $ready == 1 )
				$result = 'Finished drafting' ;
			else
				$result = 'Drafting' ;
			break;
		case 3 :
			if ( $ready == 1 )
				$result = 'Finished building' ;
			else
				$result = 'Building' ;
			break;
		case 4 :
			if ( $ready == 1 )
				$result = 'Finished playing' ;
			else
				$result = 'Playing' ;
			break;
		case 5 :
			$result = 'Ended' ;
			break;
		case 6 :
			$result = 'BYE' ;
			break;
		case 7 :
			$result = 'Dropped' ;
			break;
		default : 
			$result = 'Unknown status : '.$stat ;
	}
	return $result ;
}
*/
function tournament_status($stat) {
	$result = 'Initializing' ;
	switch ( intval($stat) ) {
		case 0 :
			$result = 'Canceled' ;
			break;
		case 1 :
			$result = 'Pending' ;
			break;
		case 2 :
			$result = 'Waiting players' ;
			break;
		case 3 :
			$result = 'Drafting' ;
			break;
		case 4 :
			$result = 'Building' ;
			break;
		case 5 :
			$result = 'Playing' ;
			break;
		case 6 :
			$result = 'Ended' ;
			break;
		default : 
			$result = 'Unknown status : '+stat ;
	}
	return $result ;
}
?>
