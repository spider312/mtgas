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
		'lib/jquery.js'
		, 'lib/jquery.cookie.js'
		, '../variables.js.php'
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
   <h1>Core <?=ws_indicator();?></h1>

   <h2>Games</h2>
   <h3>Pending</h3>
   <ul id="pending_duels"></ul>
   <h3>Running</h3>
   <ul id="joined_duels"></ul>

   <h2>Tournament</h2>
   <h3>Pending</h3>
   <ul id="pending_tournaments"></ul>
   <h3>Running</h3>
   <ul id="running_tournaments"></ul>

  </div>

  <div class="section">
   <h1>Connected users</h1>
   <ul id="connected_users"></ul>
  </div>

 </body>
</html>
