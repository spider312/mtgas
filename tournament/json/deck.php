<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
include '../../includes/card.php' ;
include '../../includes/deck.php' ;
include '../../tournament/lib.php' ;
$id = param($_GET, 'id', 0) ;
$reg = registration_get($id) ;
if ( $reg == null )
	$deck = param_or_die($_POST, 'deck') ;
else
	$deck = $reg->deck ;
$deck = deck2arr($deck) ;
die(json_encode($deck)) ;
?>
