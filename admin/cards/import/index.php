<?php
include_once '../../../lib.php' ; // param* html*
include_once '../../../config.php' ;
include_once 'lib.php' ; // cache_get
include_once '../lib.php' ; // cache_get
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
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Import cards</h1>

   <form>
    <fieldset>
     <legend>From</legend>
     <select name="source">
      <?=html_option('mci', 'MagicCardsInfo', $source) ; ?>
      <?=html_option('mv', 'MagicVille', $source) ; ?>
     </select><br>
     Ext code : <input type="text" name="ext_source" value="<?=$ext_source?>"><br>
    </fieldset>
    <fieldset>
     <legend>To</legend>
     Ext code : <input type="text" name="ext_local" value="<?=$ext_local?>"><br>
     <?=html_checkbox('apply', $apply) ; ?>Apply<br>
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
	$importer = new Importrer() ;
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
	echo '    <tr title="'.$card->text.'">
     <td>'.$i.' : '.$card->rarity.'</td>
     <td>'.$card->name.'</td>
     <td>'.$card->cost.'</td>
     <td>'.$card->types.'</td>
     <td><a href="'.$card->images[0].'">'.$card->images[0].'</a></td>
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
	echo '    <tr>
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
<style type=text/css>
	#import_cards, #import_tokens {
		display: none ;
	}
	#import_cards.shown, #import_tokens.shown {
		display: table-row-group ;
	}
	caption {
		min-width: 500px ;
	}
</style>

<script type="text/javascript">
	document.getElementById('import_cards_button').addEventListener('click', function(ev) {
		tbody = document.getElementById('import_cards') ;
		tbody.classList.toggle('shown') ;
	}, false) ;
	document.getElementById('import_tokens_button').addEventListener('click', function(ev) {
		tbody = document.getElementById('import_tokens') ;
		tbody.classList.toggle('shown') ;
	}, false) ;

</script>

  <div class="section">
   <h2>To</h2>
  </div>

 </body>
</html>
