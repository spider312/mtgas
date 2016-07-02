<?php
include_once '../lib.php' ;
$source = param($_GET, 'source', 'mv_dom') ;
html_head(
	'Admin > Websocket server',
	array(
		'style.css'
		, 'admin.css'
		, 'import.css'
		, 'options.css'
	),
	array(
		'../variables.js.php'
		, 'math.js'
		, 'html.js'
		, 'image.js'
		, 'options.js'
		, 'websockets.js'
		, 'admin/import.js'
	)
) ;
?>

 <body onload="start(event)">
<?php
html_menu() ;
?>

  <div class="section">
   <a href="../../">Return to admin</a>

   <h1>Import <?=ws_indicator();?></h1>

   <form id="import_form">
    <label>Source : <select name="source">
     <?=html_option('mv_dom', 'Magic Ville DOM', $source) ; ?>
    </select></label>
    <label>Ext code (in source) : <input type="text" name="ext_source"></label>
    <button id="import_submit" type="submit">Parse</button>
   </form>

   <table>
    <caption></caption>
    <thead>
     <tr>
      <th>Rarity</th>
      <th>Name</th>
      <th>Cost</th>
      <th>Types</th>
      <th>Text</th>
      <th>Images</th>
      <th>Langs</th>
     </tr>
	</thead>
    <tbody id="download_table">
	</tbody>
   </table>
  </div>

 </body>
</html>
