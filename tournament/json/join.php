<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
include '../lib.php' ;
$id = intval(param_or_die($_POST, 'id')) ;
$nick = param_or_die($_POST, 'nick') ;
$avatar = param_or_die($_POST, 'avatar') ;
$deck = param_or_die($_POST, 'deck') ;
$tournament = new simple_object() ;
$tournament->msg = tournament_register($id, $nick, $avatar, $deck) ;
// Return infos
die(json_encode($tournament)) ;
?>
