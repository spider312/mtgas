<?php
include_once '../lib.php' ;
html_head(
	'Suggestions management',
	array(
		'style.css'
		, 'options.css'
		, 'admin.css'
		, 'suggestions.css'
	),
	array(
		'../variables.js.php'
		, 'math.js'
		, 'html.js'
		, 'image.js'
		, 'options.js'
		, 'admin/config.js'
		, 'admin/suggestions.js'
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

   <h1>Suggestions</h1>

   <h2>Sealed</h2>
   <table id="suggest_sealed"></table>

   <h2>Draft</h2>
   <table id="suggest_draft"></table>

  </div>

 </body>
</html>
