<?php
include_once '../lib.php' ;
html_head(
	'Keywords management',
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
		, 'admin/keywords.js'
	)
) ;
?>

 <body onload="start()">
  <script language="javascript">
  </script>
<?php
html_menu() ;
?>

  <div class="section">
   <h1>Keywords</h1>
   <h2>Generic</h2>
   <table id="keyword"></table>
  </div>

 </body>
</html>
