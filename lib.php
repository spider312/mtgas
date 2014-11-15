<?php
// Before config inclusion as it should need it
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'includes/lib.php' ;
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'config.php' ;
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'includes/lang.php' ;

// Globals
$cr = "\n" ;

// Application globals
	// Default theme, or theme stored in cookies if any
$theme = $default_theme ;
if ( array_key_exists('theme', $_COOKIE) )
	if ( is_dir('themes/'.$_COOKIE['theme']) )
		$theme = $_COOKIE['theme'] ;

// Default menu entries
menu_add(__('menu.ranking'), '/top.php', __('menu.ranking.title')) ;
menu_add(__('menu.data'), '/player.php', __('menu.data.title')) ;
menu_add('<img src="/themes/'.$theme.'/icon-facebook.png" alt="Facebook">', 'https://www.facebook.com/mogg.fr', __('menu.facebook.title')) ;
menu_add(__('menu.gui'), '/doc/GUI.php', __('menu.gui.title')) ;
menu_add(__('menu.chat'), 'http://tchat.rs2i.net/?chan=mtg&amp;soft=qwebirc', __('menu.chat.title')) ;
menu_add(__('menu.forum'), 'http://forum.mogg.fr', __('menu.forum.title')) ;

// Session
$session_id = $appname.'playerid' ;
session_name($session_id) ;
session_start() or die('Session failed');
if ( session_id() == '' )
	session_regenerate_id() ;
// Sets session cookie to live more than just session
$cookie_expire = time()+60*60*24*365 ; // One year cookies
if ( array_key_exists($session_id, $_COOKIE) )
	setcookie($session_id, $_COOKIE[$session_id], $cookie_expire, '/') ;

// Reconnection
if ( 
	( ! array_key_exists('login', $_SESSION) || ! array_key_exists('password', $_SESSION) ) // Missing login or pass in session
	&& array_key_exists('login', $_COOKIE) && array_key_exists('password', $_COOKIE) // And existing in cookies
) {
	$_SESSION['login'] = $_COOKIE['login'] ;
	$_SESSION['password'] = $_COOKIE['password'] ;
}

// Globals depending from session
if ( array_key_exists($session_id, $_COOKIE) ) // Server has no cookies
	$player_id = $_COOKIE[$session_id] ;
else
	$player_id = '' ;
?>
