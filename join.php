<?php
// Register to a game that has just been created
include 'lib.php' ;
include 'includes/db.php' ;
// Data
$id = param_or_die($_POST, 'id') ;
// DB
$row = mysql_fetch_object(query("SELECT `joiner_id` FROM `$mysql_db`.`round` WHERE `id` = '$id' ; ")) ;
if ( ! $row )
	die('Unfetchable game '.mysql_error()) ;
// Processing
if ( ( $row->joiner_id == '' ) || ( $row->joiner_id == $player_id ) ) { // Game has only 1 player or i'm joiner
	$nick = $_POST['nick'] ;
	$avatar = $_POST['avatar'] ;
	$deck = addslashes($_POST['deck']) ;
	// Update game info with joiner data
	query("UPDATE `$mysql_db`.`round`
	SET
		`joiner_nick` =  '$nick'
		, `joiner_id` = '$player_id'
		, `joiner_avatar` = '$avatar'
		, `joiner_deck` = '$deck'
	WHERE `id` = '$id' ; ") ;
	include 'includes/card.php' ;
	include 'includes/deck.php' ;
	parse_deck($id, 'game.joiner', $player_id, $deck) ;
	game_toss($id) ;
	header('location: play.php?id='.$id) ;
	die('<a href="play.php?id='.$id.'">continue</a>') ;
} else
	die('You are not registered and this game is full') ;
