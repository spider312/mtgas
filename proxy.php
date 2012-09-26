<?php
if ( array_key_exists('url', $_GET) ) 
	if ( ! @readfile($_GET['url']) )
		die('File not downable '+$_GET['url']) ;
?>
