<?php
include_once '../lib.php' ;
html_head(
	'Admin',
	array(
		'style.css'
		, 'options.css'
		, 'admin.css'
	),
	array(
		'../variables.js.php'
		, 'math.js'
		, 'html.js'
		, 'image.js'
		, 'options.js'
		, 'admin/index.js'
	)
) ;
?>

 <body onload="start()">
  <script language="javascript">
  </script>
<?php
html_menu() ;
// === [ Tournaments ] =========================================================
?>

  <div class="section">
   <h1>General configuration</h1>
   <h2>Suggestions</h2>
   <h3>Sealed</h3>
   <table>
    <thead>
     <tr>
	  <td>Name</td>
	  <td>Value</td>
	  <td>Action</td>
  	 </tr>
	</thead>
	<tbody id="suggest_sealed">
	</tbody>
	<tfoot>
	 <tr>
	  <td colspan="3">
       <form id="suggest_sealed_add">
        <input type="text" name="name" placeholder="name">
        <input type="text" name="value" placeholder="value">
        <input type="submit" value="add">
       </form>
	  </td>
     </tr>
	</tfoot>
   </table>
   <h3>Draft</h3>
   <table>
    <thead>
     <tr>
	  <td>Name</td>
	  <td>Value</td>
	  <td>Action</td>
  	 </tr>
	</thead>
	<tbody id="suggest_draft">
	</tbody>
	<tfoot>
	 <tr>
	  <td colspan="3">
       <form id="suggest_draft_add">
        <input type="text" name="name" placeholder="name">
        <input type="text" name="value" placeholder="value">
        <input type="submit" value="add">
       </form>
	  </td>
     </tr>
	</tfoot>

   </table>
  </div>

  <div class="section">
   <h1>Tournaments</h1>
   <h2>Server</h2>
   <a href="websocket.php">Manage server</a>
   <h2>Tools</h2>
   <ul>
    <li><a href="tournament/recompute.php">Recompute tournament's scores</a></li>
   </ul>

   <h2>Metagame cards analysis</h2>
   <form id="stats_create" action="tournament/metagame.php">
    <input type="text" name="name" placeholder="Name">
    <select name="format">
     <optgroup label="<?=__('index.tournaments.create.limited');?>">
      <option value="draft"><?=__('index.tournaments.create.draft');?></option>
      <option value="sealed"><?=__('index.tournaments.create.sealed');?></option>
     </optgroup>
     <optgroup label="<?=__('index.tournaments.create.constructed');?>">
      <option value="vintage"><?=__('index.tournaments.create.vintage');?></option>
      <option value="legacy"><?=__('index.tournaments.create.legacy');?></option>
      <option value="extended"><?=__('index.tournaments.create.modern');?></option>
      <option value="standard"><?=__('index.tournaments.create.standard');?></option>
      <option value="edh"><?=__('index.tournaments.create.edh');?></option>
     </optgroup>
    </select>

    <input type="date" name="date" placeholder="Starting date">
    <input type="text" name="exts" placeholder="EXT1,EXT2,...">
    <input type="text" name="mask" placeholder="Name mask">
    <input type="text" name="imask" placeholder="Name ignore mask">
    <input type="submit" value="Create">
   </form>

<?php
$dir = '../stats/' ;

if ( array_key_exists('delete_report', $_GET) ) {
	$spl = split("\|", param_or_die($_GET, 'name')) ;
	$name = $spl[0] ;
	if ( unlink($dir.$name) )
		echo "<p>Deleted $name</p>" ;
	else
		echo "<p>Can't delete $name</p>" ;
}

$reports = array_reverse(sorted_scandir($dir)) ;
if ( count($reports) > 0 ) {
?>
     <form>
     Load : <select id="report_list" name="name">
     <option selected disabled>Select a report</option>
<?php
	foreach ( $reports as $r ) {
		$content = file_get_contents($dir.$r) ;
		$data = json_decode($content) ;
		$value  = $r ;
		$value .= '|'.(isset($data->date)  ? $data->date:'') ;
		$value .= '|'.(isset($data->format)? $data->format:'') ;
		$value .= '|'.(isset($data->exts)  ? implode(',', $data->exts):'') ;
		$value .= '|'.(isset($data->mask)  ? $data->mask:'') ;
		$value .= '|'.(isset($data->imask) ? $data->imask:'') ;
		echo '     <option value="'.$value.'">'.$r.' (updated '.date ("Y-m-d H:i:s.", filemtime('../stats/'.$r)).')</option>'."\n" ;
	}
?>
     </select>
     <button name="delete_report" value="true">Delete</button>
    </form>
<?php
}
?>
   <li><a href="../metagame.php">See result</a> (public)</li>
  </div>
<?php // === [ Cards ] ========================================================= ?>
  <div class="section">
   <h1>Cards</h1>
   <h2>Browse</h2>
   <p><a href="cards/extensions.php">Extensions list</a></p>

   <h2>Search</h2>
   <form action="cards/cards.php" method="get">
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="ext" placeholder="Ext" size="3">
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
   </ul>
   <h3>Checks</h3>
   <ul>
    <li><a href="cards/import/mcis.php">Compare extensions from MCI with DB</a></li>
    <li><a href="cards/check_images.php">Compare images and DB</a> (<a href="http://www.slightlymagic.net/forum/viewtopic.php?f=15&t=453">Slightly Promo topic</a>)</li>
    <li><a href="cards/check_integrity.php">Check database integrity</a></li>
    <li><a href="cards/check_langs.php">Check Languages</a></li>
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
