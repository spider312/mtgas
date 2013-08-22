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
?>

 <body>
<?php
html_menu() ;
?>

  <div class="section">
   <h1>Player merge</h1>
<?php
$ret = false ;
// Actions
if ( array_key_exists('id_from', $_GET) && array_key_exists('id_to', $_GET) ) {
	$to = $_GET['id_to'] ;
	foreach ( $_GET['id_from'] as $from )
		if ( $from == $to )
			echo 'Merging the same ID<br>' ;
		else {
			echo 'Merging '.$from.' into '.$to.' : <ul>' ;
			// Registrations
			query("UPDATE `registration` SET `player_id` = '$to' WHERE `player_id` = '$from' ; ") ;
			echo '<li>'.mysql_affected_rows().' registrations updated</li>' ;
			// Games
			query("UPDATE `round` SET `last_update_date` = `last_update_date`, `creator_id` = '$to' WHERE `creator_id` = '$from' ; ") ;
			$i = mysql_affected_rows() ;
			query("UPDATE `round` SET `last_update_date` = `last_update_date`, `joiner_id` = '$to' WHERE `joiner_id` = '$from' ; ") ;
			echo '<li>'.($i+mysql_affected_rows()).' games updated</li>' ;
			// Actions
			query("UPDATE `action` SET `sender` = '$to' WHERE `sender` = '$from' ; ") ;
			echo '<li>'.mysql_affected_rows().' actions updated</li>' ;
			echo '</ul>' ;
		}
	$ret = true ;
}
if ( array_key_exists('clean_id', $_GET) && array_key_exists('nick', $_GET) ) {
	$from = $_GET['clean_id'] ;
	$to = $_GET['nick'] ;
	query("UPDATE `round` SET `last_update_date` = `last_update_date`, `creator_nick` = '$to' WHERE `creator_id` = '$from' AND `creator_nick` = 'Nickname' ; ") ;
	$i = mysql_affected_rows() ;
	query("UPDATE `round` SET `last_update_date` = `last_update_date`, `joiner_nick` = '$to' WHERE `joiner_id` = '$from' AND `joiner_nick` = 'Nickname' ; ") ;
	echo ($i+mysql_affected_rows()).' games updated' ;
	$ret = true ;
}
	// Displaying
	$rounds = query_as_array("SELECT
		`creator_nick`, `creator_id`,
		`joiner_nick`, `joiner_id`
	FROM `round`
	WHERE `creator_id` != `joiner_id`
	ORDER BY `id` DESC") ;
	$players = array() ; // For each ID found, a list of nicknames the player has
	foreach ( $rounds as $round ) {
		// Creator
		if ( $round->creator_id != '' ) {
			if ( ! array_key_exists($round->creator_id, $players) )
				$players[$round->creator_id] = array() ;
			if ( array_search($round->creator_nick, $players[$round->creator_id]) === false )
				$players[$round->creator_id][] = $round->creator_nick ;
		}
		// Joiner
		if ( $round->joiner_id != '' ) {
			if ( ! array_key_exists($round->joiner_id, $players) )
				$players[$round->joiner_id] = array() ;
			if ( array_search($round->joiner_nick, $players[$round->joiner_id]) === false )
				$players[$round->joiner_id][] = $round->joiner_nick ;
		}
	}
	function disp_opponent($id, $nicks) {
		global $player_id ;
		$result = '<a href="../stats.php?id='.$id.'">'.join(', ', $nicks).'</a>' ;
		if ( $player_id == $id )
			$result .= ' (you)' ;
		return $result ;
	}
?>
   <form>
    <table class="hlhover">
     <tr>
      <th>Nick</th>
      <th><input type="submit" value="Merge"></th>
      <th>Clean nicks</th>
     </tr>
<?php
	foreach ( $players as $id => $player ) {
		if ( ( count($player) == 1 ) && $player[0] == 'Nickname' )
			echo '     <!-- Nickname with id '.$id.' -->'."\n" ;
		else {
			echo '     <tr>'."\n" ;
			echo '      <td>'.disp_opponent($id, $player).'</td>'."\n" ;
			echo '      <td>'."\n" ;
			echo '       <input type="checkbox" name="id_from[]" value="'.$id.'" title="from">'."\n" ;
			echo '       <input type="radio" name="id_to" value="'.$id.'" title="to">'."\n" ;
			echo '       <input type="button" value="Search" onclick="search_players(this, '.str_replace('"', "'", json_encode($player)).')">'."\n" ;
			echo '       <input type="hidden" value="'.str_replace('"', "'", json_encode($player)).'">'."\n" ;
			echo '      </td>'."\n" ;
			echo '      <td>'."\n" ;
			if ( ( array_search('Nickname', $player) !== false ) && ( count($player) > 1 ) ) // Has "Nickname" in nicks, and at least another nick
				foreach ( $player as $nick )
					if ( $nick != 'Nickname' )
						echo '       <input type="button" value="'.$nick.'" onclick="window.location.replace(\'?clean_id='.$id.'&nick='.$nick.'\')">'."\n" ;
			echo '      </td>'."\n" ;
			echo '     </tr>'."\n" ;
		}
	}
?>
    </table>
   </form>
  </div>
 </body>
</html>
