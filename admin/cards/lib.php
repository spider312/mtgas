<?php
include_once '../../config.php' ;
include_once '../../includes/db.php' ;
$mysql_connection = mysql_connect('', $card_login, $card_password ) ;
if ( ! $mysql_connection )
	die('Connection failed : '.mysql_error()) ;
if ( ! mysql_select_db($card_db) )
	die('Selection failed : '.mysql_error()) ;
?>
