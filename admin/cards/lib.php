<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../lib.php' ;
// Fake card connexion as normal mysql_connexion, in order not to have to specify on each query() call
$mysql_connection = mysql_connect('', $card_login, $card_password ) ;
if ( ! $mysql_connection )
	die('Connection failed : '.mysql_error()) ;
if ( ! mysql_select_db($card_db) )
	die('Selection failed : '.mysql_error()) ;
?>
