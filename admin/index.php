<?php
include_once '../lib.php' ;
include_once '../includes/db.php' ;
include_once '../config.php' ;
include_once '../includes/card.php' ;
include_once '../includes/lib.php' ;
html_head(
	'Admin',
	array(
		'style.css'
		, 'admin.css'
	)
) ;
?>

 <body>
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Tournaments</h1>
   <ul>
<?php
$t = query_as_array("SELECT `id`, `name` FROM `tournament` WHERE `status` > 1 AND `status` < 6 ;", 'tournament list', $mysql_connection) ;
if ( count($t) == 0 )
	echo '    <li>No running tournament</li>' ;
foreach ( $t as $tournament )
	echo '    <li><a href="tournament/?id='.$tournament->id.'">'.$tournament->name.'</a></li>'
?>
   </ul>
   <li><a href="recompute_tournaments.php">Recompute</a></li>
  </div>

  <div class="section">
   <h1>Cards</h1>
   <ul>
    <li><a href="cards/extensions.php">Extensions list</a></li>
    <li>Install extension from text spoiler : 
<?php
include_once 'cards/lib.php' ;
if ($handle = opendir('../'.$spoiler_dir)) {
	echo '  <ul>' ;
	while (false !== ($file = readdir($handle)))
		if ( ( $file != '..' ) && ( $file != '.' ) )
			echo '   <li><a href="cards/parse_extension.php?file='.$file.'">'.$file.'</a></li>' ;
			$ext_spoil[] = $file ;
	closedir($handle) ;
	echo '  </ul>' ;
}
?>
    </li>
    <li>Install extension from raw list : 
<?php
include_once 'cards/lib.php' ;
if ($handle = opendir('../'.$raw_dir)) {
	echo '  <ul>' ;
	while (false !== ($file = readdir($handle)))
		if ( ( $file != '..' ) && ( $file != '.' ) )
			echo '   <li><a href="cards/parse_raw.php?file='.$file.'">'.$file.'</a></li>' ;
			$ext_spoil[] = $file ;
	closedir($handle) ;
	echo '  </ul>' ;
}
?>
    </li>
    <li>
     <form action="cards/cards.php" method="get">
      <input type="text" name="name" placeholder="Name">
      <input type="text" name="text" placeholder="Text">
      <input type="text" name="types" placeholder="Ctype">
      <input type="text" name="cost" placeholder="Cost">
      <input type="submit" value="Search">
     </form>
    </li>
    <li>
     <form action="cards/mci.php" method="get">
      <input type="text" name="ext" placeholder="Extension's 3 letters code">
      <input type="submit" value="Import from MCI">
     </form>
    </li>
    <!--li><a href="cards/parse_all.php">Install all cards from master text spoiler</a></li-->
    <li><a href="cards/compile.php">Compile cards (adds attributes specific to MTGAS in database)</a></li>
    <li><a href="cards/check_images.php">Compare images and DB</a></li>
    <li><a href="cards/check_integrity.php">Check database integrity</a></li>
   <ul>
  </div>

  <div class="section">
   <h1>Player</h1>
   <ul>
    <li><a href="player_merge.php">Merge</a></li>
   </ul>
  </div>

  <div class="section">
   <h1>Card inclusion statistics</h1>
   <ul>
    <li><a href="/sealed_parse.php">Update</a></li>
    <li><a href="/sealed_top.php">Show</a></li>
   </ul>
  </div>

 </body>
</html>
