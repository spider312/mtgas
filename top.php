<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/ranking.php' ;
$period = param($_GET, 'p', '') ;
function disp_ranking($players, $nb, $caption, $legend) {
	global $player_id ;
	echo '  <table title="'.$legend.'">
   <caption>'.$caption.'</caption>
   <tr>
    <th>#</th>
    <th>Avatar</th>
    <th>Nick</th>
    <th>Games</th>
    <th>Score</th>
    <th>Ratio</th>
   </tr>
' ;
	$i = 0 ;
	foreach ( $players as $id => $player ) {
		if ( $player->matches < 2 ) // Player must have played enough games to appear in ranks
			continue ;
		if ( ( $player->nick == 'Nickname' ) && ( $player->avatar == 'img/avatar/kuser.png' ) )
			continue ;
		if ( ( $nb < 1 ) | ( $i++ < $nb ) ) {
				if ( $player_id == $id )
					$class = 'self' ;
				else
					$class = '' ;
		echo '   <tr class="'.$class.'">
    <td>'.$i.'</td>
    <td><img src="'.$player->avatar.'" height="30"></td>
    <td><a href="stats.php?id='.$id.'">'.$player->nick.'</a></td>
    <td>'.$player->matches.'</td>
    <td>'.$player->score.'</td>
    <td>'.round(($player->score/$player->matches), 2).'</td>
   </tr>
' ;
		}
	}
	if ( $nb == 0 )
		$txt = 'All ' ;
	else
		$txt = 'Top '.$nb.' of ' ;
	echo '   <tr><td colspan="6">'.$txt.$i.' players</td></tr>
  </table>' ;
}
function disp_rankings($period, $nb) {
	echo '<h2>'.$period.'</h2>' ;
	$filename = $period.'.json' ;
	$players = (array)json_decode(file_get_contents('ranking/'.$filename)) ;
	uasort($players, 'sort_matches') ;
	disp_ranking($players, $nb, 'Top playing', 'This list is sorted by number of games played by the player') ;
	uasort($players, 'sort_score') ;
	disp_ranking($players, $nb, 'Top winning', 'This list is sorted by total player score') ;
	uasort($players, 'sort_ratio') ;
	disp_ranking($players, $nb, 'Better ratio', 'This list is sorted by player\'s average score per game') ;
	echo '<p>Updated : '.date("F d Y H:i:s.", filemtime('ranking/'.$filename)).'</p>' ;
}
html_head('Top players ', 
	array(
		'style.css', 
		'top.css'
	)
) ;
?>
 <body>
<?php
html_menu(true) ;
?>
  <div id="stats" class="section">
   <h1>Top players</h1>
<?php
if ( $period == '' ) {
	$d = dir('ranking') ;
	/*
	$files = array() ;
	while ( false !== ( $entry = $d->read() ) )
		if ( ( $entry != '.' ) && ( $entry != '..' ) ) 
			$files[] = $entry
	*/
	//$files = glob("ranking/*.json") ;
	$periods = array('week', 'month', 'year') ; // Hardcoded for sorting
	foreach ( $periods as $period ) {
		disp_rankings($period, 10) ;
		echo '<p><a href="?p='.$period.'">View full ranking for last '.$period.'</a></p>' ;
	}
} else
	disp_rankings($period, 0) ;
if ( is_file('footer.php') )
	include 'footer.php' ;
?>
 </body>
</html>
