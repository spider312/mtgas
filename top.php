<?php
include 'lib.php' ;
html_head('Top players ', 
	array(
		'style.css', 
		'options.css',
		'top.css'
	), 
	array(
		'lib/jquery.js',
		'../variables.js.php'
		, 'options.js'
		, 'html.js' 
		, 'math.js'
		, 'image.js'
		, 'top.js'
	)
) ;
?>
 <body onload="init(event)">
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
	  <td id="eval_avg" class="sortable">Evaluation</td>
	 </tr>
	</thead>
	<tbody id="loading">
	 <tr>
	  <td colspan="7">Loading...</td>
	 </tr>
	</tbody>
	<tbody id="table"></tbody>
   </table>
  </div>
<?php
html_foot() ;
