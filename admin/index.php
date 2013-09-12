<?php
include_once '../lib.php' ;
include_once '../includes/db.php' ;
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
// === [ Tournaments ] =========================================================
?>

  <div class="section">
   <h1>Tournaments</h1>
   <h2>Tools</h2>
   <ul>
    <li><a href="tournament/recompute.php">Recompute tournament's scores</a></li>
    <li>
     <form action="tournament/sealed_parse.php">
      Update card inclusion statistics : 
      <input type="date" name="date" placeholder="Starting date" value="2013-01-29">
      <input type="text" name="name" placeholder="Name mask" value="-CUB">
      <input type="submit" value="Update">
     </form>
    </li>
    <li><a href="/sealed_top.php">Card inclusion statistics</a> (public)</li>

   </ul>
   <h2>Current</h2>
   <ul>
<?php
$t = query_as_array("SELECT `id`, `name` FROM `tournament` WHERE `status` > 0 AND `status` < 6 ;", 'tournament list', $mysql_connection) ;
if ( count($t) == 0 )
	echo '    <li>No running tournament</li>' ;
foreach ( $t as $tournament )
	echo '    <li><a href="tournament/?id='.$tournament->id.'">'.$tournament->name.'</a></li>'
?>
   </ul>
   
   <h2>Previous</h2>
   <ul>
<?php
$t = query_as_array("SELECT `id`, `name`, `creation_date` FROM `tournament` WHERE `status` = 0 OR `status` > 5 ORDER BY `creation_date` DESC LIMIT 0, 10 ;", 'tournament list', $mysql_connection) ;
if ( count($t) == 0 )
	echo '    <li>No previous tournament</li>' ;
foreach ( $t as $tournament )
	echo '    <li><a href="tournament/?id='.$tournament->id.'">'.$tournament->creation_date.' : '.$tournament->name.'</a></li>'
?>
   </ul>
  </div>
<?php // === [ Cards ] ========================================================= ?>
  <div class="section">
   <h1>Cards</h1>
   <h2>Browse</h2>
   <p><a href="cards/extensions.php">Extensions list</a> (<a href="http://www.wizards.com/magic/TCG/Article.aspx?x=mtg/tcg/products/allproducts">Official list of all products</a>)</p>

   <h2>Search</h2>
   <form action="cards/cards.php" method="get">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="text" placeholder="Text">
    <input type="text" name="types" placeholder="Type">
    <input type="text" name="cost" placeholder="Cost">
    <input type="text" name="attrs" placeholder="Compiled">
    <input type="submit" value="Search">
   </form>

   <h2>Import</h2>
   <a href="cards/import/">Import cards</a>
   <form action="cards/upload.php" enctype="multipart/form-data" method="post">
    Create/update a (<abbr title="No new card will be created, listed cards will be added to extension if already existing">virtual</Abbr>) extension from a list
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo return_bytes(ini_get('upload_max_filesize')) ; ?>">
    <input type="file" name="list">
    <button type="submit">Upload list</button>
   </form>

   <h2>Tools</h2>
   <h3>Actions</h3>
   <ul>
    <li><a href="cards/compile.php">Compile cards (adds attributes specific to MTGAS in database)</a></li>
    <li><a href="cards/import/mci_extra.php">Import token images from MCI</a></li>
    <li><a href="cards/cube.php">Dispatch cards from CUB to CUBL / CUBS depending on rarity</a>
   </ul>
   <h3>Checks</h3>
   <ul>
    <li><a href="cards/import/mcis.php">Compare extensions from MCI with DB</a></li>
    <li><a href="cards/check_images.php">Compare images and DB</a> (<a href="http://www.slightlymagic.net/forum/viewtopic.php?f=15&t=453">Slightly Promo topic</a>)</li>
    <li><a href="cards/check_integrity.php">Check database integrity</a></li>
    <li><a href="cards/cub_csv.php">Download CUB list as a spreadsheet</a></li>
   </ul>
  </div><!-- Cards -->


  <div class="section">
   <h1>Player</h1>
   <ul>
    <li><a href="player_merge.php">Merge multiple player scores into one</a></li>
   </ul>
  </div>

 </body>
</html>
