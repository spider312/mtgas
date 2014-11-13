<?php
include 'lib.php' ;
include 'includes/db.php' ;

$get = '' ;
$initial_player_id = $player_id ;
if ( array_key_exists('id', $_GET) ) {
	$player_id = $_GET['id'] ;
	$get = '&id='.$player_id ;
}
$rounds = query_as_array("SELECT
	`creator_nick`, `creator_id`, `creator_score`,
	`joiner_nick`, `joiner_id`, `joiner_score`, 
	`tournament`
FROM `round` WHERE `creator_id` = '".$player_id."' OR `joiner_id` = '".$player_id."'") ;
$self_nicks = array() ;
foreach ( $rounds as $round ) {
	if ( $round->creator_id == $round->joiner_id ) {
		$self_nick = $round->creator_nick ;
		$opponent_id = $round->joiner_id ;
	} else {
		if ( $round->creator_id == $player_id ) {
			$self_nick = $round->creator_nick ;
			$opponent_id = $round->joiner_id ;
		} else if ( $round->joiner_id == $player_id ) {
			$self_nick = $round->joiner_nick ;
			$opponent_id = $round->creator_id ;
		}
		if ( $opponent_id == '' )
			continue ;
		if ( array_search($self_nick, $self_nicks) === false )
			$self_nicks[] = $self_nick ;
	}
}

html_head(
	'Player data',
	array(
		'style.css',
		'options.css',
		'player.css'
	),
	array(
		'lib/jquery.js',
		'lib/jquery.cookie.js',
		'../variables.js.php',
		'html.js',
		'math.js',
		'image.js',
		'tournament/lib.js',
		'options.js',
		'player_data.js'
	)
) ;
?>
 <body onload="start('<?=$player_id?>')">
<?php html_menu() ; ?>
  <div class="section">
   <h1>Data for <?php echo join(', ', $self_nicks) ; ?></h1>
   <h2>Duels replay
    <select id="past_games_delay" title="Delay">
     <option value="">All</option>
     <option value="YEAR">Year</option>
     <option value="MONTH">Month</option>
     <option value="WEEK" selected="selected">Week</option>
     <option value="DAY">Day</option>
     <option value="HOUR">Hour</option>
    </select>
   </h2>
   <table id="past_games_list">
    <thead>
     <tr>
       <td>Game name</td>
       <td>Opponent</td>
       <td>Age</td>
       <td>Score</td>
     </tr>
    </thead>
    <tbody id="past_games">
     <tr>
      <td colspan="6">Waiting for list of suscribed games</td>
     </tr>
    </tbody>
    <tbody id="no_past_games">
     <tr>
      <td colspan="6">No suscribed games</td>
     </tr>
    </tbody>
   </table>

   <h2>Tournaments replay
    <select id="past_tournaments_delay" title="Delay">
     <option value="">All</option>
     <option value="YEAR">Year</option>
     <option value="MONTH">Month</option>
     <option value="WEEK" selected="selected">Week</option>
     <option value="DAY">Day</option>
     <option value="HOUR">Hour</option>
    </select>
   </h2>
   <table id="past_tournament_list">
    <thead>
     <tr>
      <td>Type</td>
      <td>Name</td>
      <td>Date</td>
      <td>Rank</td>
      <td>Status</td>
      <td>Players (<span class="win">win</span>, <span class="draw">draw</span>, <span class="lose">lose</span>, <span class="noop">not an opponent</span>)</td>
     </tr>
    </thead>
    <tbody id="past_tournaments">
     <tr>
      <td colspan="6">Waiting for list of suscribed tournaments</td>
     </tr>
    </tbody>
    <tbody id="no_past_tournaments">
     <tr>
      <td colspan="6">No suscribed tournaments</td>
     </tr>
    </tbody>
   </table>

  </div>
 </body>
</html>
