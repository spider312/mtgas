<?php
include_once 'lib.php' ;
include 'HtmlDiff.php' ;
// Args
$ext_local = param($_GET, 'ext_local', '') ;
$source = param($_GET, 'source', 'mtgjson') ;
$ext_source = param($_GET, 'ext_source', $ext_local) ;
$apply = param($_GET, 'apply', false) ;
if ( $apply !== false )
	$apply = true ;
$verbose = false ;

html_head(
	'Admin > Cards > Importer',
	array(
		'style.css'
		, 'admin.css'
	)
) ;
?>
 <body>

<style type=text/css>
	td > li {
		list-style-type: none ;
	}
	.hidden {
		display: none ;
	}
	caption {
		min-width: 500px ;
	}
</style>

<?php
html_menu() ;
?>
  <div class="section">
   <h1>Import cards</h1>
   <a href="../../">Return to admin</a>

   <form>
    <fieldset>
     <legend>From</legend>
     <label>Source : <select name="source">
      <?=html_option('mtgjson', 'MTGJSON', $source) ; ?>
      <?=html_option('mci', 'Magic Cards Info', $source) ; ?>
      <?/*=html_option('mv', 'Magic Ville', $source) ; */?>
      <?=html_option('mv_dom', 'Magic Ville DOM', $source) ; ?>
      <?=html_option('mtgsalvation', 'MTGSalvation', $source) ; ?>
     </select></label>
     <label>Ext code (in source) : <input type="text" name="ext_source" value="<?=$ext_source?>"><label>
    </fieldset>
    <fieldset>
     <legend>To</legend>
     <label>Ext code (in DB) : <input type="text" name="ext_local" value="<?=$ext_local?>"></label>
     <label><?=html_checkbox('apply', $apply) ; ?>Apply<label>
    </fieldset>
    <button type="sublit">Refresh</button>
   </form>
  </div>

<?php
if ( $ext_source == '' )
	die('No ext to parse') ;

$ext_local = strtolower($ext_local) ;
$ext_source = strtolower($ext_source) ;
?>
  <div class="section">
   <h2>From</h2>
<?php
$chosen_importer = 'importer/'.$source.'.php' ;
if ( ! file_exists($chosen_importer) )
	die('No importer for source '.$source) ;

$importer = new ImportExtension() ;
echo '<pre>' ;
include_once $chosen_importer ;
if ( ! $importer->validate() ) {
	$apply = false ;
}
$caption = count($importer->cards) . ' cards' ;
$nbimages = $importer->nbimages() ;
if ( count($importer->cards) != $nbimages )
	$caption .= ', '.$nbimages.' images' ;
?></pre>

   <table>
    <caption><?php echo $caption ; ?> cards detected <button id="import_cards_button">Show / Hide</button></caption>
    <tbody id="import_cards">
     <tr>
      <th>#</th>
      <th>Rarity</th>
      <th>Name</th>
      <th>Cost</th>
      <th>Types</th>
      <th>Images</th>
      <th>URL</th>
      <th>MultiverseID</th>
      <th>Langs</th>
     </tr>
<?php
foreach ( $importer->cards as $i => $card ) {
	echo '     <tr title="'.htmlentities($card->text).'">
      <td>'.($i+1).'</td>
      <td>'.$card->rarity.'</td>
      <td>'."\n      " ;
	foreach ( $card->urls as $i => $url ) {
		$name = ( ( $i == 1 ) && ( $card->secondname != '' ) ) ? $name = $card->secondname.'*' : $name = $card->name ; // Second line and card has a second name
		echo '      <li><a href="'.$url.'" target="_blank">'.$name.'</a></li>'."\n" ;
	}
	echo '</td>
      <td>'.manacost2html($card->cost).'</td>
      <td>'.$card->types.'</td>
      <td>'.$card->nbimages.'</td>
      <td>' ;
	foreach ( $card->images as $i => $image)
		echo '<li><a href="'.$image.'" target="_blank">['.($i+1).']</a></li>' ;
	echo '      </td>'."\n" ;
	echo '      <td>'.$card->multiverseid.'</td>'."\n" ;
	echo '      <td>' ;
	$line = array() ; // Pivot multiple images for a card's translations
	foreach ( $card->langs as $code => $lang ) {
		if ( isset($lang['name']) ) {
			$title = ' title="'.$lang['name'].'"' ;
			$code = ''.$code.'*' ;
		} else
			$title = '' ;
		foreach ( $lang['images'] as $i =>$image ) {
			if ( !isset($line[$i]) )
				$line[$i] = '' ;
			$line[$i] .= '<a target="_blank" href="'.$image.'"'.$title.'>'.$code.'</a> ' ;
		}
	}
	echo implode($line, '<br>') ; // /Pivot
	echo '      </td>'."\n" ;
	echo '     </tr>'."\n" ;
}
?>
    </tbody>
   </table>

<?php
if ( count($importer->tokens) > 0 ) {
?>
   <table>
    <caption><?php echo count($importer->tokens) ; ?> tokens detected <button id="import_tokens_button">Show / Hide</button></caption>
    <tbody id="import_tokens">
     <tr>
      <th>name</th>
      <th>pow</th>
      <th>tou</th>
      <th>url</th>
     </tr>
<?php
foreach ( $importer->tokens as $token ) {
	echo '     <tr>
      <td><a href="'.$token['card_url'].'" target="_blank">'.$token['type'].'</td>
      <td>'.$token['pow'].'</td>
      <td>'.$token['tou'].'</td>
      <td><a href="'.$token['image_url'].'">'.$token['image_url'].'</a></td>
     </tr>
' ;
}
?>
    </tbody>
   </table>
<?php
}
?>
  </div>

  <div class="section">
   <h2>To</h2>
   <pre><?php $import_log = $importer->import($ext_local, $apply) ; // PHP Import ?></pre>

   <table>
    <tbody id="imported_cards">
     <tr>
      <th>Name</th>
      <th>Found</th>
      <th>Updates</th>
     </tr>
<?php
$updates = 0 ;
$found = array() ;
$notfound = array() ;
$actions = array() ;
$translations = array() ;
foreach ( $import_log as $i => $log ) {
	$card = $log['card'] ;
	$updated = false ;
	foreach ( $log['langs'] as $code => $lang ) {
		$action = $lang['action'] ;
		if ( ! isset($translations[$action]) )
			$translations[$action] = 1 ;
		else
			$translations[$action]++ ;
		if ( isset($lang['from']) )
			$updated = true ;
	}
	if ( count($log['updates']) > 0 ) {
		$updates++ ;
		$updated = true ;
	} else {
		if ( $log['found'] )
			$found[] = $card->name ;
		else
			$notfound[] = $card->name ;
	}
	if ( $updated ) {
		echo '     <tr>'."\n" ;
		echo '      <td>'.$i.' : <a href="'.$card->urls[0].'" target="_blank">'.$card->name.'</a></td>'."\n" ;
		if ( ( count($log['updates']) == 1 ) && isset($log['updates']['multiverseid']) )
			echo '      <td>only multiverseID</td>'."\n" ;
		else
			echo '      <td><img src="'.$card->images[0].'"></td>'."\n" ;
		echo '      <td>'."\n" ;
		foreach ( $log['updates'] as $field => $upd ) {
			$diff = new HtmlDiff($upd,  $card->{$field}) ;
			echo '       <strong title="'.$card->{$field}.'">'.$field.' : </strong><div style="white-space:pre-wrap">'.$diff->build().'</div>'."\n" ;
		}
		foreach ( $log['langs'] as $code => $lang ) {
			if ( ! isset($lang['from']) ) continue ;
			$diff = new HtmlDiff($lang['from'], $lang['to']) ;
			echo '       <strong>'.$code.' name : </strong><div style="white-space:pre-wrap">'.$diff->build().'</div>'."\n" ;
			echo strdebug($lang['from']).strdebug($lang['to']) ;
		}
		echo '      </td>'."\n" ;
		echo '     </tr>'."\n" ;
	}
	if ( isset($actions[$log['action']]) )
		$actions[$log['action']][] = $card ;
	else
		$actions[$log['action']] = array($card) ;
}
?>
    </tbody>
    <caption><?php echo $updates ; ?> cards <?php echo isset($translations['update']) ? '+ '.$translations['update'].' translations' : '' ; ?> to update<button id="imported_cards_button">Show / Hide</button></caption>
   </table>
<?php
if ( count($found) > 0 )
	echo '<p title="'.implode(', ', $found).'">'.count($found).' cards found but not updated</p>' ;
if ( count($notfound) > 0 )
	echo '<p class="warn" title="'.implode(', ', $notfound).'">'.count($notfound).' cards not found, so inserted</p>' ;
// Links
echo '<p>Actions on links : <ul>' ;
foreach ( $actions as $i => $action ) {
	$names = array() ;
	foreach ( $action as $card )
		$names[] = $card->name ;
	if ( $i == 'to insert' )
		$class = 'class="warn" ' ;
	else
		$class = '' ;
	echo '<li '.$class.'title="'.implode($names, ', ').'">'.$i.' : '.count($action).'</li>' ;
}
echo '</ul></p>' ;
// Translations
echo '<p>Actions on translations : <ul>' ;
foreach ( $translations as $action => $nb )
	echo '<li>'.$action.' : '.$nb.'</li>' ;
echo '</ul></p>' ;
?>
  </div>

  <div class="section">
   <h2>Pics</h2>
<pre><?php
$_SESSION['importer'] = $importer ; // May need to comment 'session_name()' in /lib.php
?></pre>
   <a href="images.php" target="img_dl">Download</a><br>
   <iframe name="img_dl" class="fullwidth" height="800">
   </iframe>
  </div>

<script type="text/javascript">
	var buttons = ['import_cards', 'import_tokens', 'imported_cards'] ;
	for ( var i = 0 ; i < buttons.length ; i++ ) {
		var button = buttons[i] ; // ID
		var but = document.getElementById(button+'_button'); // Button
		if ( ! but ) continue ;
		but.target = document.getElementById(button) ; // TBody to toggle
		if ( ! but.target ) continue ;
		but.target.classList.add('hidden') ; // Hide by default
		but.addEventListener('click', function(ev) { this.target.classList.toggle('hidden') ; }, false) ; // Toggle on button click
	}
</script>

 </body>
</html>
