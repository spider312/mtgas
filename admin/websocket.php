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

 <body>
<?php
html_menu() ;
?>

  <div class="section">
   <h1>Index <?=ws_indicator();?></h1>
   <ul id="connected_users"></ul>
  </div>


  <div class="section">
   <h1>Games</h1>
   <h2>Pending</h2>
   <ul id="pending_duels"></ul>
   <h2>Running</h2>
   <ul id="joined_duels"></ul>
  </div>

  <div class="section">
   <h1>Tournament</h1>
   <h2>Pending</h2>
   <ul id="pending_tournaments"></ul>
   <h2>Running</h2>
   <ul id="running_tournaments"></ul>
  </div>

  <div class="section">
   <h1>MTG Data</h1>
   <ul id="mtg_data"></ul>
  </div>

 </body>
</html>
