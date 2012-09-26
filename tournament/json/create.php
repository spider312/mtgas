<?php
include '../../lib.php' ;
include '../../includes/card.php' ;
include '../../includes/db.php' ;
include '../lib.php' ;

$source = $_POST ;

$type = param_or_die($source, 'type') ;
$name = param_or_die($source, 'name') ;
$nick = param_or_die($source, 'nick') ;
$avatar = param_or_die($source, 'avatar') ;
$deck = param_or_die($source, 'deck') ;
$players = intval(param_or_die($source, 'players')) ;
$boosters = param_or_die($source, 'boosters', '') ;
$rounds_number = param_or_die($source, 'rounds_number', '') ;
$rounds_duration = param_or_die($source, 'rounds_duration', '') ;
$clone_sealed = param($source, 'clone_sealed', '') ;

$tournament = new simple_object() ;
$options = new simple_object() ;
$options->rounds_number = $rounds_number ;
$options->rounds_duration = $rounds_duration ;
$options->clone_sealed = $clone_sealed == 'true' ;
//die('{\'msg\': \''.$clone_sealed.'\'}') ;

list($tournament->id, $tournament->msg) = tournament_create($type, $name, $players, $boosters, $options) ;
if ( $tournament->id > -1 )
	$tournament->msg .= tournament_register($tournament->id, $nick, $avatar, $deck) ;
die(json_encode($tournament)) ;
?>
