<?php
include '../lib.php' ;
$id = intval(param_or_die($_GET, 'id')) ;
html_head('Tournament #'.$id, 
	array(
		'style.css', 
		'tournament.css', 
		'tournament_index.css', 
		'options.css', 
		'menu.css'
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
		, 'websockets.js'
		, 'spectactor.js'
		, 'menu.js'
	)
) ;
?>
 <body onload="start(<?=$id?>)">
<?php
	html_menu() ;
?>
  <div class="section central">
   <h1>Tournament <?=ws_indicator();?></h1>
   <div class="column">
    <h2 id="tournament_name">Tournament's name</h2>
    Status : <strong id="status"></strong> left <strong id="timeleft"></strong>
	<div>Created : <strong id="tournament_created"></strong></div>
	<div>Format : <strong id="tournament_format"></strong></div>
	<div>Players : <strong id="tournament_player_nb"></strong></div>
    <div id="tournament_info"></div>
	<button id="drop">Drop tournament</button>
    <p id="register"></p>
    <h2>Players</h2>
    <table>
     <thead>
      <tr>
       <th><abbr  title="Player number : used to define player's relative position during draft">#</abbr></th>
       <th>Nick</th>
       <th>Avatar</th>
       <th>Status</th>
       <th>Rank</th>
       <th>Points</th>
       <th><abbr title="Opponent Match Win : percentage of matches won for all player's opponents (first tie breaker)">OMW</abbr></th>
       <th><abbr title="Opponent Game Win : percentage of games won for all player's opponents (second tie breaker)">OGW</abbr></th>
       <th><abbr title="Match Win : percentage of matches won by player (third tie breaker)">MW</abbr></th>
       <th>Deck</th>
       <th>Actions</th>
      </tr>
     </thead>
     <tbody id="players_table"></tbody>
    </table>
    <div class="hidden">
     <h2>Rounds</h2>
     <div id="rounds"></div>
    </div>
   </div>

   <div class="column">
    <h2>Log</h2>
    <ul id="tournament_log"></ul>
    <form id="chat">
     <input type="text" name="msg">
     <input type="submit" value="Send">
    </form>
	<button id="spectators">Spectators</button>
   </div>

   <br clear="both">

  </div>
<?php
html_foot() ;
