<?php
include '../lib.php' ;
$id = intval(param_or_die($_GET, 'id')) ;
html_head('Drafting #'.$id,
	array(
		'style.css',
		'tournament.css',
		'draft.css',
		'options.css'
	),
	array(
		'lib/jquery.js'
		, 'lib/jquery.cookie.js'
		, 'lib/Flotr2/flotr2.min.js'
		, 'html.js'
		, 'math.js'
		, 'image.js'
		, 'deck.js'
		, 'options.js'
		, 'stats.js'
		, 'spectactor.js'
		, 'tournament/lib.js'
		, 'tournament/limited.js'
		, 'tournament/draft.js'
		, '../variables.js.php'
		, 'websockets.js'
	)
) ;
?>
 <body onload="start(<?php echo $id ; ?>)">

  <div id="info" class="section">
   <input id="timeleft" type="text" value="Initializing" readonly="readonly" title="Time left for picking" size="8"><br>
   <label title="Boosters are switched if every player check this box before timer ends"><input id="ready" type="checkbox">I'm ready</label>
  </div>

  <div class="section central">
   <h1>Draft <?=ws_indicator();?></h1>
   <div id="booster_cards"></div>
   <table id="tournament_info"></table>
  </div>

  <div class="section central">
   <h1>Deck</h1>
   <div id="drafted_cards"></div>
  </div>

  <div class="section central">
   <h1>Side</h1>
   <div id="sided_cards"></div>
  </div>


  <div id="stats" class="section">
   <h2>Stats</h2>
   <div id="stats_graphs"></div>
  </div>

  <div id="tournament" class="section">
   <h2>Players</h2>
   <ul id="players_ul"></ul>
   <h2>Log</h2>
   <ul id="tournament_log"></ul>
   <form id="chat"><input type="text" name="msg"></form>
  </div>

  <div id="back" class="section">
   <a id="mainpage" title="Main page" href="../"><img src="/themes/<?=$theme?>/<?=$index_image?>"></a>
   <span id="aditional_link"></span>
  </div>
<?php
html_foot() ;
