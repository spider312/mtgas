<?php
//ini_set('memory_limit','128M') ; // Empyrical, 64M isn't enough for stef or chris
ini_set('memory_limit', '256M');
include 'lib.php' ;
include 'includes/db.php' ;
html_head(
	'Stats',
	array(
		'style.css'
	),
	array(
		'stats.js'
	)
) ;
?>
 <body>
<?php
html_menu() ;
$get = '' ;
$initial_player_id = $player_id ;
if ( array_key_exists('id', $_GET) ) {
	$player_id = $_GET['id'] ;
	$get = '&id='.$player_id ;
}
$actions = query_as_array("SELECT id FROM `action` WHERE `sender` = '".$player_id."'") ;
$registrations = query_as_array("SELECT `date` FROM `registration` WHERE `player_id` = '".$player_id."'") ;
$rounds = query_as_array("SELECT
	`creator_nick`, `creator_id`, `creator_score`,
	`joiner_nick`, `joiner_id`, `joiner_score`, 
	`tournament`
FROM `round` WHERE `creator_id` = '".$player_id."' OR `joiner_id` = '".$player_id."'") ;
$tournament_rounds = array() ;
$duel_rounds = array() ;
$goldfish_rounds = array() ;
$opponents = array() ;
$self_nicks = array() ;
foreach ( $rounds as $round ) {
	if ( $round->creator_id == $round->joiner_id )
		$goldfish_rounds = $round ;
	else {
		if ( $round->tournament != '0' )
			$tournament_rounds[] = $round ;
		else
			$duel_rounds[] = $round ;
		if ( $round->creator_id == $player_id ) {
			$self_nick = $round->creator_nick ;
			$opponent_id = $round->joiner_id ;
			$opponent_nick = $round->joiner_nick ;
			$round->self_score = $round->creator_score ;
			$round->opponent_score = $round->joiner_score ;
		} else if ( $round->joiner_id == $player_id ) {
			$self_nick = $round->joiner_nick ;
			$opponent_id = $round->creator_id ;
			$opponent_nick = $round->creator_nick ;
			$round->self_score = $round->joiner_score ;
			$round->opponent_score = $round->creator_score ;
		}
		if ( $opponent_id == '' )
			continue ;
		if ( ! array_key_exists($opponent_id, $opponents) ) { // First game against that opponent, creating its obj
			$opponent = new simple_object() ;
			$opponent->id = $opponent_id ;
			$opponent->nicks = array() ;
			$opponent->games = array() ;
			$opponents[$opponent_id] = $opponent ;
		} else
			$opponent = $opponents[$opponent_id] ;
		if ( array_search($opponent_nick, $opponent->nicks) === false )
			$opponent->nicks[] = $opponent_nick ;
		$opponent->games[] = $round ;
		if ( array_search($self_nick, $self_nicks) === false )
			$self_nicks[] = $self_nick ;
	}
}
foreach ( $opponents as $opponent ) {
	$opponent->victories = 0 ;
	$opponent->defeats = 0 ;
	foreach ( $opponent->games as $round ) {
		if ( $round->self_score > $round->opponent_score )
			$opponent->victories++ ;
		if ( $round->self_score < $round->opponent_score )
			$opponent->defeats++ ;
	}
}
function sort_games($a, $b) {
	return count($b->games) - count($a->games) ;
}
function sort_wins($a, $b) {
	return $b->victories - $a->victories ;
}
function sort_loses($a, $b) {
	return $b->defeats - $a->defeats ;
}
function disp_opponent($opponent) {
	global $initial_player_id ;
	$result = '<a href="?id='.$opponent->id.'">'.join(', ', $opponent->nicks).'</a>' ;
	if ( $initial_player_id == $opponent->id )
		$result .= ' (you)' ;
	return $result ;
}
?>
  <div class="section">
   <h1>Stats for <?php echo join(', ', $self_nicks) ; ?></h1>
   <h2>Overall</h2>
    You played <?php echo count($rounds) ; ?> games
    (<?php echo count($tournament_rounds) ; ?> during tournaments,
    <?php echo count($duel_rounds) ; ?> during duels
    and <?php echo count($goldfish_rounds) ; ?> during goldfish),
    registered to <?php echo count($registrations) ; ?> tournaments,
    and sent a total of <?php echo count($actions) ; ?> actions
   <h2>Opponents</h2>
<?php
/*
   <h3>Most encountered</h3>
   <ul>
<?php
	usort($opponents, 'sort_games') ;
	foreach ( $opponents as $opponent )
		echo '    <li>'.disp_opponent($opponent).' : '.count($opponent->games).'</li>'."\n" ;
?>
   </ul>
   <h3>Most beaten</h3>
   <ul>
<?php
	usort($opponents, 'sort_wins') ;
	foreach ( $opponents as $opponent )
		if ( $opponent->victories > 0 )
			echo '    <li>'.disp_opponent($opponent).' : '.$opponent->victories.'</li>'."\n" ;
		else
			break ;
?>
   </ul>
   <h3>Most lost</h3>
   <ul>
<?php
	usort($opponents, 'sort_loses') ;
	foreach ( $opponents as $opponent )
		if ( $opponent->defeats > 0 )
			echo '    <li>'.disp_opponent($opponent).' : '.$opponent->defeats.'</li>'."\n" ;
		else
			break ;
?>
   </ul>

   <h3>Details</h3>
*/
?>
   <table>
    <tr>
     <th>Nicknames</th>
     <th><a href="?s=m<?php echo $get ; ?>">Matches</a></th>
     <th><a href="?s=v<?php echo $get ; ?>">Victories</a></th>
     <th><a href="?s=v<?php echo $get ; ?>">%</a></th>
     <th><a href="?s=d<?php echo $get ; ?>">Defeats</a></th>
     <th><a href="?s=d<?php echo $get ; ?>">%</a></th>
    </tr>
<?php
	if ( array_key_exists('s', $_GET) )
		switch ( $_GET['s'] ) {
			case 'm' :
				usort($opponents, 'sort_games') ;
				break ;
			case 'v' :
				usort($opponents, 'sort_wins') ;
				break ;
			case 'd' :
				usort($opponents, 'sort_loses') ;
				break ;
			default :
				echo 'Sorting unknown : '.$_GET['s'] ;
		}
	foreach ( $opponents as $opponent ) {
		echo '    <tr>'."\n" ;
		echo '     <td>'.disp_opponent($opponent)."\n" ;
		echo '     <td>'.count($opponent->games)."\n" ;
		echo '     <td>'.$opponent->victories."\n" ;
		echo '     <td>'.round($opponent->victories/count($opponent->games)*100, 2).'%'."\n" ;
		echo '     <td>'.$opponent->defeats."\n" ;
		echo '     <td>'.round($opponent->defeats/count($opponent->games)*100, 2).'%'."\n" ;
		echo '    </tr>'."\n" ;
	}
?>
   </table>

  </div>
 </body>
</html>
