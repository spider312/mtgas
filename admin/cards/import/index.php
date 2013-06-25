<?php
include_once 'lib.php' ;
include 'HtmlDiff.php' ;
// Args
$ext_local = param($_GET, 'ext_local', '') ;
$source = param($_GET, 'source', 'mci') ;
$ext_source = param($_GET, 'ext_source', $ext_local) ;
$apply = param($_GET, 'apply', false) ;
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
	#import_cards, #import_tokens, #imported_cards {
		display: none ;
	}
	#import_cards.shown, #import_tokens.shown, #imported_cards.shown {
		display: table-row-group ;
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

   <form>
    <fieldset>
     <legend>From</legend>
     <label>Source : <select name="source">
      <?=html_option('mci', 'MagicCardsInfo', $source) ; ?>
      <?=html_option('mv', 'MagicVille', $source) ; ?>
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
if ( file_exists($chosen_importer) ) {
	$importer = new ImportExtension() ;
	include_once $chosen_importer ;
} else
	die('No importer for source '.$source) ;
?>

   <table>
    <caption><?php echo count($importer->cards) ; ?> cards detected <button id="import_cards_button">Show / Hide</button></caption>
    <tbody id="import_cards">
     <tr>
      <th>rarity</th>
      <th>name</th>
      <th>cost</th>
      <th>types</th>
      <th>image url</th>
     </tr>
<?php
foreach ( $importer->cards as $i => $card ) {
	echo '     <tr title="'.$card->text.'">
      <td>'.$i.' : '.$card->rarity.'</td>
      <td>'.$card->name.'</td>
      <td>'.$card->cost.'</td>
      <td>'.$card->types.'</td>
      <td>' ;
	foreach ( $card->images as $image)
		echo '<li><a href="'.$image.'">'.$image.'</a></li>' ;
	echo '</td>
     </tr>
' ;
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
      <td>'.$token[0].'</td>
      <td>'.$token[1].'</td>
      <td>'.$token[2].'</td>
      <td><a href="'.$token[3].'">'.$token[3].'</a></td>
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
<?php
$import_log = $importer->import($apply) ;
?>

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
foreach ( $import_log as $i => $log ) {
	$card = $log['card'] ;
	if ( count($log['updates']) > 0 ) {
		$updates++ ;
		echo '     <tr>
      <td>'.$i.' : '.$card->name.'</td>
      <td>'.$log['found'].'</td>
      <td>' ;
		foreach ( $log['updates'] as $field => $upd ) {
			if ( $field == 'text' ) {
				//echo $field.'<textarea cols="50" rows="5">'.$upd.'</textarea>->' ;
				//echo '<textarea cols="50" rows="5">'.$card->{$field}.'</textarea>' ;
				$diff = new HtmlDiff($upd,  $card->{$field}) ;
				echo '<div style="white-space:pre-wrap">'.$diff->build().'</div>' ;
				//htmlDiff($upd, $card->{$field})
			} else
				echo $field.' : '.$upd.' -> '.$card->{$field} ;
			echo '<br>' ;
		}
		echo '</td>
     </tr>
' ;
	} else {
		if ( $log['found'] )
			$found[] = $card->name ;
		else
			$notfound[] = $card->name ;
	}
}
?>
    </tbody>
    <caption><?php echo $updates ; ?> cards to update <button id="imported_cards_button">Show / Hide</button></caption>
   </table>
<?php
if ( count($found) > 0 )
	echo '<p>'.count($found).' cards found but not updated : <br>'.implode(', ', $found).'</p>' ;
if ( count($notfound) > 0 )
	echo '<p>'.count($notfound).' cards not found, so inserted : <br>'.implode(', ', $notfound).'</p>' ;
?>

  </div>

<script type="text/javascript">
	document.getElementById('import_cards_button').addEventListener('click', function(ev) {
		tbody = document.getElementById('import_cards') ;
		tbody.classList.toggle('shown') ;
	}, false) ;
	var e = document.getElementById('import_tokens_button')
	if ( e ) 
		e.addEventListener('click', function(ev) {
			tbody = document.getElementById('import_tokens') ;
			tbody.classList.toggle('shown') ;
		}, false) ;
	document.getElementById('imported_cards_button').addEventListener('click', function(ev) {
		tbody = document.getElementById('imported_cards') ;
		tbody.classList.toggle('shown') ;
	}, false) ;

</script>



 </body>
</html>
