<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../includes/card.php' ;
die(json_encode(card_search($_GET, card_connect()))) ;
?>
