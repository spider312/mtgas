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

   <h2>Draft</h2>
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

 </body>
</html>
