<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../includes/card.php' ;
$data = new stdClass() ;

$data->base = query_as_array(("SELECT *, UNIX_TIMESTAMP(release_date) as rd FROM extension WHERE `bloc` = 0 ORDER BY release_date DESC"), 'Extension list', card_connect()) ;
$data->bloc = query_as_array(("SELECT *, UNIX_TIMESTAMP(release_date) as rd FROM extension WHERE `bloc` > 0 ORDER BY release_date DESC"), 'Extension list', card_connect()) ;
$data->special = query_as_array(("SELECT *, UNIX_TIMESTAMP(release_date) as rd FROM extension WHERE `bloc` < 0 ORDER BY release_date DESC"), 'Extension list', card_connect()) ;

die(json_encode($data)) ;
?>
