<?php
function query($query, $name='Query', $conn=null) {
	if ( $conn == null  ) {
		global $mysql_connection ;
		$conn = $mysql_connection ;
	}
	$result = mysql_query($query, $conn) ;
	if ( ! $result )
		die($name.' failed : '.mysql_errno().' : '.mysql_error().' ('.$query.')') ;
	return $result ;
}
function query_oneshot($query, $name='Query', $conn=null) {
	$result = query($query, $name, $conn) ;
	$nb = mysql_num_rows($result) ;
	if ( $nb > 1 )
		die("$name returned $nb rows") ;
	if ( $row = mysql_fetch_object($result) )
		return $row ;
	else
		return null ;
}
function query_as_array($query, $name='Query', $conn=null) {
	$result = query($query, $name, $conn) ;
	$array = array() ;
	while ( $row = mysql_fetch_object($result) )
		$array[] = $row ;
	return $array ;
}
function get2where($get, $comp, $prefix, $suffix) {
	$where = '' ;
	foreach ( $get as $i => $val ) {
		if ( ( $i != 'submit' ) && ( $val != '' ) ) {
			if ( $where != '' )
				$where .= 'AND ' ;
			$where .= '`'.$i . '` '.$comp.' \''.$prefix . mysql_real_escape_string($val) . $suffix . '\' ' ;
		}
	}
	if ( $where != '' )
		$where = 'WHERE '.$where ;
	return $where ;
}
function card_connect() {
	global $mysql_login, $card_login, $card_password, $card_db ;
	$card_connection = mysql_connect('', $card_login, $card_password, ( $card_login == $mysql_login )) ; // in case $card_login == $mysql_login, must open a new connexion
	if ( ! $card_connection )
		die('Card connection failed : '.mysql_error()) ;
	if ( ! mysql_select_db($card_db, $card_connection) )
		die('Card selection failed : '.mysql_error()) ;
	return $card_connection ;
}
?>
