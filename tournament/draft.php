<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../tournament/lib.php' ;
$id = intval(param_or_die($_GET, 'id')) ;
$tournament = query_oneshot("SELECT * FROM `tournament` WHERE `id` = '$id' ; ", 'Tournament get') ;
query("UPDATE `registration` SET `status` = '2' WHERE `tournament_id` = '$id' AND `player_id` = '$player_id' ; ") ;

$data = json_decode($tournament->data) ;
foreach ( $data->players as $i => $player )
	if ( $player->player_id == $player_id ) {
		if ( $i > 0 )
			$previous = $data->players[$i-1] ;
		else
			$previous = $data->players[count($data->players)-1] ;
		if ( $i < count($data->players)-1 )
			$next = $data->players[$i+1] ;
		else
			$next = $data->players[0] ;
		break ;
	}

html_head('Drafting '.$tournament->name,
	array(
		'style.css',
		'tournament.css',
		'draft.css'
	),
	array(
		'lib/jquery.js',
		'lib/jquery.cookie.js',
		'../variables.js.php',
		'html.js',
		'math.js',
		'image.js',
		'deck.js',
		'tournament/draft.js', 
		'stats.js',
		'lib/Flotr2/flotr2.min.js',
		'tournament/lib.js'
	)
) ;
?>
 <body onload="start(<?php echo $id ; ?>)">

  <div id="info" class="section">
   <input id="timeleft" type="text" value="Initializing" readonly="readonly" title="Time left for picking" size="8"><br>
   <label title="Boosters are switched if every player check this box before timer ends"><input id="ready" type="checkbox">I'm ready</label>
  </div>

  <div class="section group">
   <h1>Draft</h1>
   <div id="booster_cards"></div>
   <div>On your left : <?php echo $next->nick ; ?>, on your right : <?php echo $previous->nick ; ?></div>
  </div>

  <div class="section group">
   <h1>Pool</h1>
   <div id="drafted_cards"></div>
  </div>

  <div id="stats" class="section">
   <h2>Stats</h2>
   <div id="stats_color"></div>
   <div id="stats_cost"></div>
   <div id="stats_type"></div>
  </div>

  <div id="tournament" class="section">
   <h2>Tournament</h2>
   <h3>Players</h3>
   <ul id="players_ul"></ul>
   <h3>Log</h3>
   <ul id="log_ul"></ul>
   <form id="chat" action="json/log.php"><input type="text" name="msg"></form>
  </div>

  <div id="back" class="section">
   <a href="../">main page</a> &gt;
   <a href="./?id=<?php echo $id ; ?>">tournament</a>
  </div>

<?php
if ( is_file('../footer.php') )
	include '../footer.php' ;
?>
 </body>
</html>
