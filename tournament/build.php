<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../tournament/lib.php' ;
$id = param($_GET, 'id', 0) ;
$pid = param($_GET, 'pid', '') ;
$name = param($_POST, 'name', '') ;
$deck = param($_POST, 'deck', '') ;
if ( $id > 0 ) {
	$tournament = query_oneshot("SELECT * FROM `tournament` WHERE `id` = '$id' ; ", 'Tournament get') ; // Used for deck saving name
	query("UPDATE `registration` SET `status` = '3' WHERE `tournament_id` = '$id' AND `player_id` = '$player_id' ; ") ;
	$title = $tournament->name ;
	if ( $pid == '' ) {
		$pid = $player_id ;
		$title = 'Building '.$tournament->name ;
	} else
		$title = 'Spectacting '.$tournament->name ;
	$registration = query_oneshot("SELECT * FROM `registration` WHERE `tournament_id` = '$id' AND `player_id` = '$pid'; ", 'Tournament get') ;
} else
	$title = 'Building a previous tournament\'s deck' ;
html_head($title, 
	array(
		'style.css',
		'tournament.css',
		'build.css'
	), 
	array(
		'lib/jquery.js',
		'lib/jquery.cookie.js',
		'../variables.js.php',
		'html.js',
		'math.js',
		'image.js',
		'deck.js',
		'tournament/build.js',
		'stats.js',
		'lib/Flotr2/flotr2.min.js',
		'tournament/lib.js'
	)
) ;
if ( $id > 0 ) {
	if ( $pid == $player_id ) 
		echo ' <body onload="start_tournament('.$id.')">'."\n" ;
	else
		echo ' <body onload="start_spectactor('.$id.", '".addslashes($tournament->name)."', '".addslashes($registration->nick)."', '".str_replace("\n", "\\n\\\r\n", addslashes($registration->deck))."')\">"."\n" ;
} else
	echo ' <body onload="start_standalone(\''.addslashes($name)."', '".str_replace("\r\n", "\\n\\\r\n", addslashes($deck)).'\')">'."\n" ;
?>
  <div id="filter-color" class="section">
<?php
foreach ( array('X', 'W', 'U', 'B', 'R', 'G') as $color ) {
?>
   <label class="manacheck">
    <img src="/themes/<?php echo $theme ; ?>/ManaIcons/<?php echo $color ; ?>.png" height="20" alt="<?php echo $color ; ?>">
    <input id="check_c_<?php echo $color ; ?>" type="checkbox" value="<?php echo $color ; ?>" checked>
   </label>
<?php
}
?>
   <label class="manacheck">
    all
    <input id="check_c_all" type="checkbox" value="<?php echo $color ; ?>">
   </label>
  </div>

  <div id="filter-rarity" class="section">
<?php
foreach ( array('C', 'U', 'R') as $rarity ) {
?>
   <label class="manacheck">
    <img src="/themes/<?php echo $theme ; ?>/RarityIcons/<?php echo $rarity ; ?>.gif" height="20" alt="<?php echo $rarity ; ?>">
    <input id="check_r_<?php echo $rarity ; ?>" type="checkbox" value="<?php echo $rarity ; ?>" checked>
   </label>
<?php
}
?>
   <label class="manacheck">
    all
    <input id="check_r_all" type="checkbox" value="<?php echo $color ; ?>">
   </label>
  </div>

  <div id="info" class="section">
<?php
if ( $id > 0 ) {
	if ( $registration->ready == 0 )
		$checked = '' ;
	else
		$checked = 'checked="checked"' ;
?>
   <input id="timeleft" type="text" value="Initializing" readonly="readonly" title="Time left for building" size="8"><br>
   <label title="Tournament starts if every player check this box before timer ends"><input id="ready" type="checkbox" <?php echo $checked ?>>I'm ready</label>
<?php
}
?>
   <input id="save" type="button" value="Save" title="Save modifications to your deck">
   <br><!--a href="build_alt.php?id=<?php echo $id ?>">Try new builder</a-->
  </div>

  <div id="stats" class="section">
   <h2>Stats</h2>
   <div id="stats_color"></div>
   <div id="stats_cost"></div>
   <div id="stats_type"></div>
  </div>

  <div class="section group overscroll">
   <h2>Pool</h2>
   <table id="pool"></table>
  </div>
  <div class="section group overscroll">
   <h2>Deck</h2>
   <table id="deck"></table>
  </div>
  <div class="section group overscroll">
   <h2>Basic lands</h2>
   <table id="land"></table>
  </div>
<?php
if ( $id > 0 ) {
?> 
  <div id="tournament" class="section">
   <h2>Tournament</h2>
   <h3>Players</h3>
   <ul id="players_ul"></ul>
   <h3>Log</h3>
   <ul id="log_ul"></ul>
   <form id="chat" action="json/log.php"><input type="text" name="msg"></form>
  </div>
<?php
}
?> 

  <div id="back" class="section">
   <a href="../">main page</a><?php
if ( $id > 0 ) {
?> &gt;
   <a href="./?id=<?php echo $id ; ?>">tournament</a>
<?php 
}
?>
  </div>

  <div id="zoom" class="nowrap"><img id="zoomed" src="<?php echo $cardimages_default ; ?>/back.jpg" alt="Zoom on hovered card"><img id="transformed" src="<?php echo $cardimages_default ; ?>/back.jpg" alt="Transformed part for zoom on hovered card"></div><!-- hidden by CSS, displayed on card hover -->
<?php
if ( is_file('../footer.php') )
	include '../footer.php' ;
?>
 </body>
</html>
