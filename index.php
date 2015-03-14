<?php
include 'lib.php' ;
html_head(
	'Main page',
	array(
		'style.css'
		, 'index.css'
		, 'options.css'
	),
	array(
		'lib/jquery.js'
		, '../variables.js.php'
		, 'deck.js'
		, 'html.js'
		, 'index.js'
		, 'math.js'
		, 'workarounds.js'
		, 'tournament/lib.js'
		, 'options.js'
		, 'image.js'
		, 'websockets.js' 
	), 
	array(
		'Canceled tournaments' => 'rss/tournaments.php?status=0', 
		'Pending tournaments' => 'rss/tournaments.php?status=1', 
		'Started tournaments' => 'rss/tournaments.php?status=5', 
		'Ended tournaments' => 'rss/tournaments.php?status=6' 
	)
) ;
?>

 <body onload="start()">
<?php
html_menu() ;
include 'includes/Browser.php' ;
$browser = new Browser();
if( ( $browser->getBrowser() != Browser::BROWSER_FIREFOX ) || ( $browser->getVersion() < 5 ) ) {
?>
   <div id="browser" class="section">
    <div>In order to enjoy every functionnalities of this game, i <strong>really</strong> encourage you to play it under <a href="http://www.mozilla.com/">Firefox 5.0+</a> and you are <strong>not</strong> (<a href="doc/browsers.php">Why ?</a>)</div>
	<div>If you didn't recieve a notification request and you want to allow them, you can click <button onclick="notification_request('Accepted', 'Granted')">this button</button></div>
   </div>
<?php
}
?>

  <div id="left_col"><!-- - - - - - - LEFT COLUMN - - - - - - -->

  <div id="shoutbox" class="section">
   <h1>Shoutbox <?=ws_indicator();?></h1>
   <div id="shout_body">
    <ul id="shouts"></ul>
    <select id="shouters" multiple title="Double click to insert nickname into your next shout">
	</select>
   </div>
   <form id="shout" action="json/shout.php" autocomplete="off">
    <input type="text" name="text" placeholder="Shout something"><input type="submit" value="Send">
   </form>
  </div>

<?php
include 'index_tournaments.php' ;
include 'index_ts.php' ;
?>

  </div><!-- id="left_col" --><!-- - - - - - - - - - - / LEFT COLUMN - - - - - - - - - - -->

  <div id="right_col"><!-- - - - - - - - - - - RIGHT COLUMN - - - - - - - - - - -->


<?php
include 'index_duels.php' ;
include 'index_decks.php' ;
?>

  </div><!-- id="right_col" --><!-- - - - - - - - - - - / RIGHT COLUMN - - - - - -  - - - - -->

  <div id="footer" class="section"><a href="https://github.com/spider312/mtgas">MTGAS developpement version</a>, hosted by <a href="mailto:mtg@spiderou.net">SpideR</a></div>
<?php
html_foot() ;
