<?php
// Play a game previously created, both players must have registered
include 'lib.php' ;
include 'includes/db.php' ;
// Guess game data by comparing client data with mysql data
$id = param_or_die($_GET, 'id') ;
$replay = param($_GET, 'replay', 0) ; // Defaults to no replay, may be changed by tournament status
$row = query_oneshot("SELECT * FROM `round` WHERE `id` = '$id'") ;
$client_status = 'Unknown' ; // Is client creator, joiner, goldfish, or spectactor ?
$goldfish = 'false' ;
$creator = 'false' ;
$spectactor = 'false' ;
$spectactor_id = '' ;
if ( ( $player_id == $row->joiner_id ) && ( $player_id != $row->creator_id ) ) { // I am joiner but not a goldfish
	$client_status = 'Joiner' ;
	// Me : joiner
	$player_nick = $row->joiner_nick ;
	$player_id = $row->joiner_id ;
	$player_avatar = $row->joiner_avatar ;
	$player_score = $row->joiner_score ;
	// My opponent : creator
	$opponent_nick = $row->creator_nick ;
	$opponent_id = $row->creator_id ;
	$opponent_avatar = $row->creator_avatar ;
	$opponent_score = $row->creator_score ;
} else { // I'm not a joiner (creator, spectactor) or a goldfish
	$creator = 'true' ; // Only used to associate game.player/opponent and game.creator/joiner
	if ( $player_id == $row->creator_id ) {// I am creator (or goldfish)
		if ( $player_id == $row->joiner_id ) { // Goldfish = i'm both players
			$goldfish = 'true' ;
			$client_status = 'Goldfish' ;
		}
		$client_status = 'Creator' ;
	} else {
		$client_status = 'Spectactor' ;
		$spectactor = 'true' ;
		$spectactor_id = $player_id ;
	}
	// Me : creator
	$player_nick = $row->creator_nick ;
	$player_id = $row->creator_id ;
	$player_avatar = $row->creator_avatar ;
	$player_score = $row->creator_score ;
	// My opponent : joiner
	$opponent_nick = $row->joiner_nick ;
	$opponent_id = $row->joiner_id ;
	$opponent_avatar = $row->joiner_avatar ;
	$opponent_score = $row->joiner_score ;
}
// Status evolution
$status = $row->status ;
switch ( $status ) {
	case 0 : 
		die('This game has timeout') ;
		break ;
	case 1 : // Joined a game created by another
		$status++ ;
		break;
	case 2 : // Joined game created by self
		$status++ ;
		break;
	case 4 : // Created by a tournament, no join
	case 5 : // Created by a tournament, one join
		$status++ ;
	case 6 : // Created by a tournament, both join
	case 3 : // Tried to join a game that already has 2 players
		break ;
	case 7 : // Replay for ended tournament's games
		$replay = 1 ;
		break ;
	default : // Tried to join a game in an unknown status
		die('Unknown status : '+$status) ;
}
if ( $replay == 1 ) {
	$client_status = 'Replay' ;
	$spectactor = 'true' ;
	$spectactor_id = $player_id ;
	$player_score = 0 ; // In order to trigger score changes
	$opponent_score = 0 ;
}
if ( $status != $row->status )
	$query = query("UPDATE `$mysql_db`.`round` SET  `status` =  '$status' WHERE `id` = '$id'") ;
if ( $row->tournament != 0 ) 
	query("UPDATE `registration` SET `status` = '4' WHERE `tournament_id` = '".$row->tournament."' AND `player_id` = '$player_id' ; ") ;
?>
<!DOCTYPE html>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title>MTGAS : <?php echo $row->name.' ('.$client_status.')' ; ?></title>
  <link rel="icon" type="image/jpg" href="/themes/<?php echo $theme ; ?>/favicon.jpg">
<?php
add_css(array(
	'play.css',
	'list.css',
	'side.css',
	'menu.css'
	)
) ;
?>
  <script type="text/javascript" src="js/lib/jquery.js"></script>
  <script type="text/javascript" src="js/lib/jquery.cookie.js"></script>
  <script type="text/javascript" src="js/lib/raphael-min.js"></script>
  <script type="text/javascript" src="js/play.js"></script>
  <script type="text/javascript" src="js/dnd.js"></script>
  <script type="text/javascript" src="js/player.js"></script>
  <script type="text/javascript" src="js/canvas.js"></script>
  <script type="text/javascript" src="js/network.js"></script>
  <script type="text/javascript" src="js/canvasutilities.js"></script>
  <script type="text/javascript" src="js/image.js"></script>
  <script type="text/javascript" src="js/card.js"></script>
  <script type="text/javascript" src="js/token.js"></script>
  <script type="text/javascript" src="js/math.js"></script>
  <script type="text/javascript" src="js/menu.js"></script>
  <script type="text/javascript" src="js/listeditor.js"></script>
  <script type="text/javascript" src="js/target.js"></script>
  <script type="text/javascript" src="js/workarounds.js"></script>
  <script type="text/javascript" src="js/zone.js"></script>
  <script type="text/javascript" src="js/manapool.js"></script>
  <script type="text/javascript" src="js/turn.js"></script>
  <script type="text/javascript" src="js/side.js"></script>
  <script type="text/javascript" src="js/html.js"></script>
  <script type="text/javascript" src="js/selection.js"></script>
  <script type="text/javascript" src="variables.js.php"></script>
  <script type="text/javascript">
$(function() { // When page is loaded : initialize everything
	init_title = document.title ;
	unseen_actions = 0 ;
	recieve_time = null ;
	sent_time = null ;
	// Current game globals
	creator = <?php echo $creator ; ?> ; // Am i that game's creator ?
	goldfish = <?php echo $goldfish ; ?> ; // Is the game a goldfish ?
	spectactor = <?php echo $spectactor ; ?> ; // Am i a spectactor ?
	spectactor_id = '<?php echo $spectactor_id ; ?>' ; // Am i a spectactor ?
	replay = <?php echo $replay ; ?> ; // Replay
	tournament = <?php echo $row->tournament ; ?> ;
	round = <?php echo $row->round ; ?> ;
	// Init all that must be inited before "game" (+canvas) creation (for now, log messages, depending on chat)
	init_chat() ;
	game = new Game(
		// Game
			<?php echo $id."\n" ; ?>
		// Player
			, '<?php echo $player_id ; ?>'
			, '<?php echo addslashes($player_nick) ; ?>'
			, '<?php echo addslashes($player_avatar) ; ?>'
			, <?php echo $player_score."\n" ; ?>
		// Opponent
			, '<?php echo $opponent_id ; ?>'
			, '<?php echo addslashes($opponent_nick) ; ?>'
			, '<?php echo addslashes($opponent_avatar) ; ?>'
			, <?php echo $opponent_score."\n" ; ?>
	) ;
<?php
if ( array_key_exists('messages', $_SESSION) ) { // If messages were sent during game creation/join, display them on page load
	foreach ($_SESSION['messages'] as $value) 
		echo "		log('$value') ;\n" ;
	unset($_SESSION['messages']) ;
}
?>
	// Start all that must be started after "game" (+canvas) initialisation (initialisations, events, network)
	start() ;
}) ;
  </script>
 </head>

 <body>
  <canvas id="paper"></canvas>
  <button id="nextstep" title="Click : Trigger step and go next step. Ctrl+click : End turn. Right click : Go previous step">uninitialized</button>
  <div id="rightframe">
   <img id="zoom" draggable="false" src="<?php echo $cardimages_default ; ?>/back.jpg" oncontextmenu="event.preventDefault() ; ">
   <div id="timeleft">Timeleft</div>
   <div id="info">Game infos</div>
   <div id="chatbox">
    <table id="chattable">
     <tbody id="chathisto">
    </table>
   </div>
   <form id="chat">
    <input id="sendbox" type="text">
   </form>
   <div id="autotext" title="Click a button to 'say' its content. Click '...' to change messages"></div>
   <div id="autotext_window">
    <textarea id="autotext_area" title="Type wanted buttons text, one line per button. Empty to reinitialize"></textarea>
    <button id="autotext_ok">Ok</button>
    <button id="autotext_cancel">Cancel</button>
   </div>
  </div>
  <ul id="target_helper">
   <li style="color: red">Shift : Step</li>
   <li style="color: gold">No modifier : Phase</li>
   <li style="color: lime">Ctrl : Turn</li>
   <li style="color: RoyalBlue">Alt : Definitive</li>
  </ul>
  <div id="options">
   <!--button id="fullscreen" title="Sets the window fullscreen"><img src="/themes/<?php echo $theme ; ?>/fullscreen.png" alt="fullscreen"></button-->
<?php
html_options() ;
?>
   <button id="options_close" title="Close options window. Each option is applied when changing it, there is no 'apply' nor 'cancel'"><img src="/themes/<?php echo $theme ; ?>/deckbuilder/button_ok.png" alt="close"></button>
  </div>
  <div id="log_window">
   <textarea id="log_area" readonly="readonly"></textarea>
   <button id="log_close">Close</button>
   <button id="log_clear">Clear</button>
  </div>
<?php
if ( is_file('footer.php') )
	include 'footer.php' ;
?>
 </body>
</html>
