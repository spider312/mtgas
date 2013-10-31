<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../tournament/lib.php' ;
$id = intval(param_or_die($_GET, 'id')) ;
$tournament = query_oneshot("SELECT * FROM `tournament` WHERE `id` = '$id' ; ", 'Tournament get') ;
$registration = query_oneshot("SELECT * FROM `registration` WHERE `tournament_id` = '$id' AND `player_id` = '$player_id' ; ") ;
if ( isset($registration->status) )
	switch ( intval($registration->status) ) {
		case 0 : // Was waiting, now is redirected
			query("UPDATE `registration` SET `status` = '1' WHERE `tournament_id` = '$id' AND `player_id` = '$player_id' ; ") ;
			break ;
		case 1 : // redirected (refresh)
		case 2 : // drafting
		case 3 : // building
		case 4 : // playing
		case 5 : // ended
		case 6 : // bye
		case 7 : // droped
			break ;
		default :
			die('Unknown registration status : '.$registration->status.' ('.gettype($registration->status).')') ;
	}
// Clear cache
$key = 'cache_tournament_'.$id ;
html_head('Tournament '.$tournament->name, 
	array(
		'style.css', 
		'tournament.css', 
		'tournament_index.css', 
		'options.css'
	), 
	array(
		'lib/jquery.js'
		, 'lib/jquery.cookie.js'
		, 'math.js'
		, 'html.js'
		, 'deck.js'
		, 'image.js'
		, 'options.js'
		, 'tournament/lib.js'
		, 'tournament/index.js'
		, '../variables.js.php'
	)
) ;
?>
 <body onload="start(<?php echo $tournament->id ; ?>)">
<?php
	html_menu() ;
?>
  <div class="section group">
   <h1><?php echo $tournament->name ; ?></h1>
   <div class="column">
    <input id="status" type="text" value="Initializing" disabled size="50">
    <p><a id="toplink"></a></p>
    <p id="register"></p>
    <h2>Players</h2>
    <table>
     <thead>
      <tr>
       <th><abbr  title="Player number : only used to define player's relative position during draft">#</abbr></th>
       <th>Nick</th>
       <th>Avatar</th>
       <th>Status</th>
       <th>Rank</th>
       <th>Points</th>
       <th><abbr title="Opponent Match Win : percentage of matches won for all player's opponents (first tie breaker)">OMW</abbr></th>
       <th><abbr title="Opponent Game Win : percentage of games won for all player's opponents (second tie breaker)">OGW</abbr></th>
       <th><abbr title="Match Win : percentage of matches won by player (third tie breaker)">MW</abbr></th>
       <th><abbr title="Game Win : percentage of games won by player (just for fun)">GW</abbr></th>
       <th>Actions</th>
      </tr>
     </thead>
     <tbody id="players_table"></tbody>
    </table>
    <div id="current_round"></div>
    <div id="past_rounds"></div>
   </div>

   <div class="column">
    <h2>Log</h2>
    <ul id="log_ul"></ul>
    <form id="chat" action="json/log.php">
     <input type="text" name="msg">
     <input type="submit" value="Send">
    </form>
   </div>

   <br clear="both">

  </div>
<?php
if ( is_file('../footer.php') )
	include '../footer.php' ;
?>
 </body>
</html>
