<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../../lib.php' ;
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../../includes/db.php' ;
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../../includes/card.php' ; // Used in several (but not every) scripts

$mysql_connection = mysql_connect('', $card_login, $card_password ) ;
if ( ! $mysql_connection )
	die('Connection failed : '.mysql_error()) ;
if ( ! mysql_select_db($card_db) )
	die('Selection failed : '.mysql_error()) ;
?>
