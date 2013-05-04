<?php
include_once 'includes/lang.php' ;
// Includes
include_once 'includes/lib.php' ;

// Default menu entries
menu_add(__('menu.chat'), 'http://tchat.rs2i.net/?chan=mtg&amp;soft=qwebirc', _('menu.chat.title')) ;
menu_add(__('menu.forum'), 'http://forum.mogg.fr', __('menu.forum.title')) ;
menu_add(__('menu.ranking'), '/top.php', __('menu.ranking.title')) ;
menu_add(__('menu.data'), , '/player.php',__('menu.data.title')) ;

include_once 'config.php' ;

// Globals
$cr = "\n" ;
$session_id = $appname.'playerid' ;

// Application globals
	// Default theme, or theme stored in cookies if any
$theme = $default_theme ;
if ( array_key_exists('theme', $_COOKIE) )
	if ( is_dir('themes/'.$_COOKIE['theme']) )
		$theme = $_COOKIE['theme'] ;

menu_add('<img src="/themes/'.$theme.'/icon-facebook.png" alt="Facebook">', 'https://www.facebook.com/mogg.fr', __('menu.facebook.title')) ;
menu_add(__('menu.gui'), '/doc/GUI.php', __('menu.gui.title')) ;
// MySQL
$mysql_connection = mysql_connect('', $mysql_login, $mysql_password) ;
if ( ! $mysql_connection )
	die('Connection failed : '.mysql_error()) ;
if ( ! mysql_select_db($mysql_db, $mysql_connection) )
	die('Selection failed : '.mysql_error()) ;

// Session
session_name($session_id) ;
session_start() or die('Session failed');
if ( session_id() == '' )
	session_regenerate_id() ;

// Globals depending from session
if ( array_key_exists($session_id, $_COOKIE) ) // Server has no cookies
	$player_id = $_COOKIE[$session_id] ;
else
	$player_id = '' ;
?>
