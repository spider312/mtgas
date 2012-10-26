<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
include '../lib.php' ;
$id = intval(param_or_die($_GET, 'id')) ;
$value = mysql_real_escape_string(param_or_die($_GET, 'nick')) ;
die('{"nb":'.tournament_log($id, $player_id, 'spectactor', $value).'}') ;
?>
