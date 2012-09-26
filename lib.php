<?php
// Includes
include_once 'includes/lib.php' ;
// Default menu entries
menu_add('Chat', 'http://tchat.rs2i.net/?chan=mtg&soft=qwebirc', 'Meet other players') ;
menu_add('Forum', 'http://forum.mogg.fr', 'Discuss the game, help improve it') ;
//menu_add('Blog', 'http://blog.mogg.fr', 'Keep yourself informed about Mogg\'s evolution') ;
menu_add('Top players', '/top.php', 'Player rankings based on games played and won, for various periods') ;
//menu_add('Your stats', '/stats.php', 'Some statistics on your games played') ; // Broken for huge playing players
menu_add('Your data', '/player.php', 'Replays') ; // Broken for huge playing players

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

menu_add('<img src="/themes/'.$theme.'/icon-facebook.png" alt="Facebook">', 'https://www.facebook.com/mogg.fr', 'Another way to keep yourself informed : Mogg.fr on facebook') ;
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
