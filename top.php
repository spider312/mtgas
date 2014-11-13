<?php
include 'lib.php' ;
html_head('Top players ', 
	array(
		'style.css', 
		'options.css',
		'top.css'
	), 
	array(
		'lib/jquery.js'
		, 'lib/jquery.cookie.js'
		, '../variables.js.php'
		, 'options.js'
		, 'html.js' 
		, 'math.js'
		, 'image.js'
		, 'top.js'
	)
) ;
?>
 <body>
<?php
html_menu(true) ;
?>
  <div id="top" class="section">
   <h1>Top players</h1>
   <div id="tabs"></div>
   <table>
    <thead>
	 <tr>
	  <td>#</td>
	  <td>Avatar</td>
	  <td>Nick</td>
	  <td id="matches" class="sortable">Games</td>
	  <td id="score" class="sortable">Score</td>
	  <td id="ratio" class="sortable">Ratio</td>
	 </tr>
	</thead>
	<tbody id="table"></tbody>
   </table>
  </div>
  <script type="text/javascript">
<?php
	$folder = 'ranking' ;
	$d = dir($folder) ;
	while ( false !== ( $entry = $d->read() ) )
		if ( ( $entry != '.' ) && ( $entry != '..' ) ) {
			$period = substr($entry, 0, strlen($entry)-5) ;
			$path = $folder.'/'.$entry ;
			//date("F d Y H:i:s.",) ;
			echo 'ranking_import("'.$period.'", '.file_get_contents($path).', '.filemtime($path).') ;'."\n" ;
		}
?>
	init() ;
  </script>
<?
html_foot() ;
