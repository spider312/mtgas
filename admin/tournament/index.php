<?php
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../config.php' ;
include_once '../../includes/card.php' ;
include_once '../../includes/lib.php' ;
include_once '../../includes/tournament.php' ;
$id = param_or_die($_GET, 'id') ;
html_head(
	'Admin > Tournament',
	array(
		'style.css'
		, 'admin.css'
	)
) ;
?>

 <body>
<?php
html_menu() ;
$t = query_oneshot("SELECT * FROM `tournament` WHERE `id` = '$id' ; ") ;
$data = json_decode($t->data) ;
?>
  <div class="section">
   <h1>Tournament : <?php echo $t->name ; ?></h1>
   <h2>General data</h2>
   <ul>
    <li>ID : <?php echo $t->id ; ?></li>
    <li>Created : <?php echo $t->creation_date ; ?></li>
    <li>Tyle : <?php echo $t->type ; ?></li>
    <li>Players : <?php echo $t->min_players ; ?></li>
    <li>Status : <?php echo tournament_status($t->status) ; ?></li>
    <li>Current round : <?php echo $t->round ; ?></li>
    <li>Last update : <?php echo $t->update_date ; ?></li>
    <li>Next due : <?php echo $t->due_time ; ?></li>
    <li>Data :
     <ul>
<?php
if ( $data->boosters )
	echo '      <li>Boosters : '.implode(', ',$data->boosters).'</li>'."\n" ;
if ( $data->rounds_number > 0 )
	echo '      <li>Forced rounds : '.$data->rounds_number.'</li>'."\n" ;
if ( $data->rounds_duration != $round_duration / 60 )
	echo '      <li>Rounds duration : '.$data->rounds_duration.'</li>'."\n" ;
?>
     </ul>
    </li>
   </ul>


   <h2>Players</h2>
   <ul>
<?php
$r = query_as_array("SELECT * FROM `registration` WHERE `tournament_id` = '$id'  ;") ;
$registrations = array() ;
foreach ( $r as $registration ) {
	$registrations[$registration->id] = $r ;
	if ( substr($registration->avatar, 0, 6) != 'http://' )
		$registration->avatar = '../../'.$registration->avatar ;
	echo '    <li><img src="'.$registration->avatar.'">'.$registration->nick.'</li>' ;
}
?>
   </ul>

   <h2>Matches</h2>
<?php
$r = query_as_array("SELECT * FROM `round` WHERE `tournament` = '$id' ORDER BY `round` ASC, `id` ASC ;") ;
$c_r = 0 ;
foreach ( $r as $round ) {
	if ( $round->round != $c_r )
		echo '   <h3>Round '.$round->round.'</h3>'."\n".'<ul>' ;
	echo '<li>'.$round->creator_nick.' '.$round->creator_score.' - '.$round->joiner_score.' '.$round->joiner_nick.'</li>' ;
}
?>
   </ul>

   <h2>Log</h2>
   <ul>
<?php
$l = query_as_array("SELECT * FROM `tournament_log` WHERE `tournament_id` = '$id'  ;") ;
foreach ( $l as $log ) {
	if ( array_key_exists($log->sender, $registrations) )
		$sender = $registrations[$log->sender][0]->nick ;
	else
		$sender = 'server' ;
	echo '    <li>'.$sender.' : '.$log->type.' : '.$log->value.'</li>'."\n" ;
}
?>
   </ul>
  </div>

 </body>
</html>
