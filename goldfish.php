<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/card.php' ;
include 'includes/deck.php' ;
$id = game_create('Goldfish', $_POST['nick'], $player_id, $_POST['avatar'], addslashes($_POST['deck']), addslashes($_POST['goldfish_nick']), $player_id, $_POST['goldfish_avatar'], addslashes($_POST['goldfish_deck'])) ;
header('location: play.php?id='.$id) ;
die('<a href="play.php?id='.$id.'">continue</a>') ;
?>
