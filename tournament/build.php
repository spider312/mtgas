<?php
include '../lib.php' ;
$id = intval(param_or_die($_GET, 'id')) ;
$pid = param($_GET, 'pid', '') ;
html_head('Building #'.$id,
	array(
		'style.css',
		'options.css',
		'menu.css',
		'tournament.css',
		'build.css'
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
		, 'websockets.js'
		, 'menu.js'
		, 'spectactor.js'
		, 'tournament/lib.js'
		, 'tournament/limited.js'
		, 'tournament/build.js'
		, '../variables.js.php'
	)
) ;
?>
 <body onload="start(<?=$id;?>, '<?=$pid;?>')">
  <div id="info" class="section">
   <input id="timeleft" type="text" value="Initializing" disabled="disabled" title="Time left for building" size="8"><br>
   <label title="Tournament starts if every player check this box before timer ends"><input id="ready" type="checkbox" disabled="disabled">I'm ready</label>
  </div>

  <div id="stats" class="section">
   <h2>Actions</h2>
   <button id="base_lands" title="Manage basic lands" disabled>Basic lands</button>
   <br><label><input id="smallres_check" type="checkbox">Small resolution</label>
   <h2>Stats</h2>
   <label><input id="stats_side" type="checkbox">Stats side</label>
   <div id="stats_graphs"></div>
  </div>

  <div id="selectors" class="section"></div>

  <div id="div_side" class="section">
   <h1>Sideboard <?=ws_indicator();?></h1>
   <table id="table_side"></table>
  </div>

  <div id="div_main" class="section">
   <h1>MainDeck
   <button id="clear_button" title="Send all cards from maindeck to sideboard" disabled>Clear</button>
   </h1>
   <table id="table_main"></table>
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

  <div id="zoom" class="hidden nowrap"><img src="<?php echo $cardimages_default ; ?>/back.jpg"><img src="" class="hidden"></div><!-- Spaces in HTML adds text nodes -->
<?php
html_foot() ;
