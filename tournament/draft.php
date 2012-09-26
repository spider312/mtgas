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
		'../variables.js.php',
		'html.js',
		'math.js',
		'image.js',
		'deck.js',
		'tournament/draft.js'
	)
) ;
?>
 <body onload="start(<?php echo $id ; ?>)">
  <div id="info" class="section">
   <input id="timeleft" type="text" readonly="readonly" value="Initializing"><br>
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
