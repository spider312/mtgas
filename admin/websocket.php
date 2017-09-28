<?php
include_once '../lib.php' ;
html_head(
	'Admin > Websocket server',
	array(
		'style.css'
		, 'admin.css'
		, 'options.css'
	),
	array(
		'../variables.js.php'
		, 'math.js'
		, 'html.js'
		, 'image.js'
		, 'options.js'
		, 'websockets.js'
		, 'admin/websocket.js'
	)
) ;
?>

 <body onload="start(event)">
<?php
html_menu() ;
?>

  <div class="section">
   <h1>Server <?=ws_indicator();?></h1>
   <h2>Handlers</h2>
   <ul id="connected_users"></ul>
   <h2>Bans</h2>
   <ul id="bans"></ul>
   <h2>Options</h2>
   <label>
    Scheduled restart
    <input type="checkbox" id="restart">
   </label>
  </div>

  <div class="section">
   <h1>Games</h1>
   <h2>Pending <input type="text" id="pending_duels_input" size="4" disabled></h2>
   <ul id="pending_duels"></ul>
   <h2>Running <input type="text" id="joined_duels_input" size="4" disabled></h2>
   <ul id="joined_duels"></ul>
  </div>

  <div class="section">
   <h1>Tournament</h1>
   <h2>Pending <input type="text" id="pending_tournaments_input" size="4" disabled></h2>
   <ul id="pending_tournaments"></ul>
   <h2>Running <input type="text" id="running_tournaments_input" size="4" disabled></h2>
   <ul id="running_tournaments"></ul>
   <h2>Ended <input type="text" id="ended_tournaments_input" size="4" disabled></h2>
   <ul id="ended_tournaments"></ul>
  </div>

  <div class="section">
   <h1>MTG Data</h1>
   <ul id="mtg_data"></ul>
   <button id="refresh_mtg_data">Refresh</button>
  </div>

  <div class="section">
   <h1>Bench</h1>
   <ul id="bench"></ul>
  </div>

 </body>
</html>
