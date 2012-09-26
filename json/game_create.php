<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../includes/card.php' ;
include '../includes/deck.php' ;
$source = $_POST ;
$name = param_or_die($source, 'name') ;
$nick = param_or_die($source, 'nick') ;
$avatar = param_or_die($source, 'avatar') ;
$deck = addslashes(param_or_die($source, 'deck')) ;
$game = new simple_object() ;
$game->id = game_create($name, $nick, $player_id, $avatar, $deck) ;
die(json_encode($game)) ;
?>
