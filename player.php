<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/player_alias.php' ;
if ( array_key_exists('id', $_GET) ) {
	$player_id = $_GET['id'] ;
}
$player_ids = alias_pid($player_id) ;
$where = pid2where($player_ids) ;
$rounds = query_as_array("SELECT
	`creator_nick`, `creator_id`, `creator_score`,
	`joiner_nick`, `joiner_id`, `joiner_score`, 
	`tournament`
FROM `round` WHERE $where") ;
if ( count($rounds) < 1 )
	die('No game found for that player') ;
$self_nicks = array() ;
foreach ( $rounds as $round ) {
	if ( array_search($round->creator_id, $player_ids) !== false ) {
		$self_nick = $round->creator_nick ;
		$opponent_id = $round->joiner_id ;
	} else if ( array_search($round->joiner_id, $player_ids) !== false ) {
		$self_nick = $round->joiner_nick ;
		$opponent_id = $round->creator_id ;
	} else
		echo $round->creator_id.' '.$round->creator_nick.' '.$round->joiner_nick.' '.$round->joiner_id.'<br>' ;
	if ( $opponent_id == '' )
		continue ;
	if ( array_search($self_nick, $self_nicks) === false )
		$self_nicks[] = $self_nick ;
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
   <h1><?php echo join(', ', $self_nicks) ; ?>'s recent games</h1>
   <h2>Duels
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
    <caption>uninitialised</caption>
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

   <h2>Tournaments
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
    <caption>Loading default</caption>
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

  <script type="text/javascript">
	player_ids = <?=json_encode($player_ids)?> ;
  </script>
<?php
html_foot() ;
