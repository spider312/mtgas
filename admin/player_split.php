<?php
include '../lib.php' ;
include '../includes/db.php' ;
html_head(
	'Admin > Player > Merge',
	array(
		'style.css'
		, 'admin.css'
	),
	array(
		'player_merge.js'
	) 
) ;
$player_id = param_or_die($_GET, 'player_id') ;
?>

 <body>
<?php
html_menu() ;
?>

  <div class="section">
   <h1>Identities split</h1>
<?php
if ( isset($_GET['nick']) ) {
	$nick = param_or_die($_GET, 'nick') ;
	$from = $player_id ;
	$to = param_or_die($_GET, 'id_to') ;
	if ( $to == '' )
		die('A "to" value is required') ;
	echo 'Splitting '.$player_id.' / '.$nick.' into '.$to.' :' ;
	$rounds = query_as_array("
SELECT id, tournament
FROM `round`
WHERE
	( `creator_id` = '$player_id' OR `joiner_id` = '$player_id' )
	AND ( `creator_nick` = '$nick' OR `joiner_id` = '$nick' )
") ;
	$updated = array('tour' => 0, 'notour' => 0, 'registrations' => 0, 'games' => 0, 'actions' => 0, 'tournament_log' => 0) ;
	foreach ( $rounds as $round ) {
		$rid = $round->id ;
		$tid = $round->tournament ;
		if ( $tid == '0' ) {
			$updated['notour']++ ;
		} else {
			$updated['tour']++ ;
			// Registrations
			query("UPDATE `registration` SET `player_id` = '$to' WHERE `player_id` = '$from' AND `tournament_id` = '$tid'; ") ;
			$updated['registrations'] += mysql_affected_rows() ;

		}
		// Games
		query("UPDATE `round` SET `last_update_date` = `last_update_date`, `creator_id` = '$to' WHERE `id` = '$rid' AND `creator_id` = '$from' ; ", 'creator') ;
		$updated['games'] += mysql_affected_rows() ;
		query("UPDATE `round` SET `last_update_date` = `last_update_date`, `joiner_id` = '$to' WHERE `id` = '$rid' AND `joiner_id` = '$from' ; ", 'joiner') ;
		$updated['games'] += mysql_affected_rows() ;
		// Actions
		query("UPDATE `action` SET `sender` = '$to' WHERE `game` = '$rid' AND `sender` = '$from' ; ") ;
		$updated['actions'] += mysql_affected_rows() ;
		// Tournament log
		query("UPDATE `tournament_log` SET `sender` = '$to' WHERE `sender` = '$from' ; ") ;
		$updated['tournament_log'] += mysql_affected_rows() ;
	}
	l($updated) ;
}
echo '<p>' ;
echo "For $player_id : " ;
$rounds = query_as_array("SELECT
	`id`,
	`creator_nick`, `creator_id`,
	`joiner_nick`, `joiner_id`
FROM `round`
WHERE `creator_id` = '$player_id' OR `joiner_id` = '$player_id'
ORDER BY `id` DESC") ;

$nicknames = array() ;
foreach ( $rounds as $round ) {
	if ( $round->creator_id == $player_id ) {
		$prefix = 'creator' ;
		$nick = $round->creator_nick ;
	} else {
		if ( $round->joiner_id == $player_id ) {
			$prefix = 'joiner' ;
			$nick = $round->joiner_nick ;
		} else
			die('Identity is nor creator nor joiner for game'.$round->id) ;
	}
	if ( !isset($nicknames[$nick]) )
		$nicknames[$nick] = array() ;
	$nicknames[$nick][] = $round->id ;
}
echo '<form>' ;
echo '<input type="hidden" name="player_id" value="'.$player_id.'">' ;
echo '<input type="text" name="id_to" value="" placeholder="Split to ID"><br>' ;
foreach ( $nicknames as $nick => $games )
	echo '<input type="submit" name="nick" value="'.$nick.'"</input> ('.count($games).')<br>' ;
echo '</form>' ;
echo '</p>' ;
?>
  </div>
 </body>
</html>
