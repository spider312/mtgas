<?php
// Play a game previously created, both players must have registered
include 'lib.php' ;
include 'includes/db.php' ;
// Guess game data by comparing client data with mysql data
$id = param_or_die($_GET, 'id') ;
$replay = param($_GET, 'replay', 0) ; // Defaults to no replay, may be changed by tournament status
$row = $db->select("SELECT * FROM `round` WHERE `id` = '$id'") ;
$row = $row[0] ;
$client_status = 'Unknown' ; // Is client creator, joiner, goldfish, or spectactor ?
$goldfish = 'false' ;
$creator = 'false' ;
$spectactor = 'false' ;
$spectactor_id = '' ;
if ( ( $player_id == $row->joiner_id ) && ( $player_id != $row->creator_id ) ) { // I am joiner but not a goldfish
	$client_status = 'Joiner' ;
	// Me : joiner
	$player_nick = $row->joiner_nick ;
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
		$player_id = $row->creator_id ; // Fake spectator as creator
	}
	// Me : creator
	$player_nick = $row->creator_nick ;
	$player_avatar = $row->creator_avatar ;
	$player_score = $row->creator_score ;
	// My opponent : joiner
	$opponent_nick = $row->joiner_nick ;
	$opponent_id = $row->joiner_id ;
	$opponent_avatar = $row->joiner_avatar ;
	$opponent_score = $row->joiner_score ;
}
// Status management
switch ( $row->status ) {
	case 0 : 
		die('This game has timeout') ;
		break ;
	case 7 : // Replay for ended tournament's games
		$replay = 1 ;
		break ;
}
if ( $replay == 1 ) {
	$client_status = 'Replay' ;
	$spectactor = 'true' ;
	$spectactor_id = $player_id ;
	$player_score = 0 ; // In order to trigger score changes
	$opponent_score = 0 ;
}
if ( $row->tournament > 0 ) {
	$tournament = $db->select("SELECT * FROM `tournament` WHERE `id` = '{$row->tournament}'") ;
	$tournament_data = json_encode($tournament[0]) ;
} else
	$tournament_data = null ;
?>
<!DOCTYPE html>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title><?=$appname?> : <?php echo $row->name.' ('.$client_status.')' ; ?></title>
  <link type="image/jpg" rel="icon" href="themes/<?=$theme?>/Mogg Maniac.crop.png">
<?php
add_css(array(
	'debug.css',
	'play.css',
	'list.css',
	'side.css',
	'menu.css', 
	'options.css',
	'evaluate.css'
)) ;
add_js(array(
	'debug.js',
	'play.js',
	'dnd.js',
	'player.js',
	'canvas.js',
	'events.js',
	'network.js',
	'websockets.js',
	'canvasutilities.js',
	'image.js',
	'card.js',
	'token.js',
	'math.js',
	'menu.js',
	'listeditor.js',
	'target.js',
	'workarounds.js',
	'zone.js',
	'manapool.js',
	'turn.js',
	'side.js', 
	'html.js',
	'selection.js',
	'options.js',
	'spectactor.js',
	'evaluation.js',
	'../variables.js.php'
)) ;
?>
  <script type="text/javascript">
function start() { // When page is loaded : initialize everything
	init_title = document.title ;
	unseen_actions = 0 ;
	sent_time = null ;
	// Current game globals
	creator = <?php echo $creator ; ?> ; // Am i that game's creator ?
	goldfish = <?php echo $goldfish ; ?> ; // Is the game a goldfish ?
	spectactor = <?php echo $spectactor ; ?> ; // Am i a spectactor ?
	spectactor_id = '<?php echo $spectactor_id ; ?>' ; // Am i a spectactor ?
	replay = <?php echo $replay ; ?> ; // Replay
	tournament = <?php echo $row->tournament ; ?> ;
	tournament_data = <?php echo ($tournament_data==null)?'null':$tournament_data ; ?> ;
	round = <?php echo $row->round ; ?> ;
	// Init all that must be inited before "game" (+canvas) creation (for now, log messages, depending on chat)
	var options = new Options() ;
	init_chat(options) ;
	game = new Game(
		// Game
			<?php echo $id."\n" ; ?>
			, '<?=$row->creation_date;?>'
			, options
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
	// Start all that must be started after "game" (+canvas) initialisation (initialisations, events, network)
	game_start() ;
}
  </script>
 </head>

 <body onload="start();">
  <canvas id="paper"></canvas>
  <div id="rightframe">
   <img id="zoom" draggable="false" src="<?php echo $cardimages_default ; ?>/back.jpg" oncontextmenu="event.preventDefault() ; ">
   <div id="timeleft"><?=ws_indicator();?><span>Timeleft</span></div>
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
  <div id="log_window">
   <textarea id="log_area" readonly="readonly"></textarea>
   <button id="log_close">Close</button>
   <button id="log_clear">Clear</button>
  </div>
<?php
html_foot() ;
