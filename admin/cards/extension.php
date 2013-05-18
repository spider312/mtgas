<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'lib.php' ;

$ext = param_or_die($_GET, 'ext') ;

html_head(
	'Admin > Cards > Extension',
	array(
		'style.css'
		, 'admin.css'
		, 'admin_extension.css'
	),
	array(
		'lib/jquery.js',
		'html.js', // create_*
		'math.js', // isf
		'stats.js',
		'lib/Flotr2/flotr2.min.js',
		'admin/extension.js'
	)
) ;
?>

 <body>
<?php
html_menu() ;
?>
  <div class="section">
<?php
$query = query("SELECT * FROM extension WHERE `se` = '$ext' ; ") ;
if ( $arr = mysql_fetch_array($query) ) {
	$ext_bdd = $arr ; // Backup first extension line (normally only 1)
	echo '  <h1>'.$ext.' - '.$ext_bdd['name'].' (#'.$ext_bdd['id'].')</h1>' ;
	echo '  <input id="ext" type="hidden" value="'.$ext_bdd['id'].'">' ;
}
?>
  <a href="extensions.php">Return to extension list</a>

  <form id="update_ext" action="json/extension.php">
   <input type="hidden" name="ext_id" value="<?php echo $ext_bdd['id'] ; ?>">
    Code : <input type="text" name="se" size="4" value="<?php echo $ext_bdd['se'] ; ?>">
    Alternate code : <input type="text" name="sea" size="4" value="<?php echo $ext_bdd['sea'] ; ?>">
    Name : <input type="text" name="name" size="64" value="<?php echo $ext_bdd['name'] ; ?>">
    Priority : <input type="text" name="priority" size="2" value="<?php echo $ext_bdd['priority'] ; ?>">
    Release date : <input type="text" name="release_date" size="10" value="<?php echo $ext_bdd['release_date'] ; ?>">
    Bloc : <input type="text" name="bloc" size="2" value="<?php echo $ext_bdd['bloc'] ; ?>">
    <input type="submit" name="update" value="Update">
  </form>

  <form id="filter">
   Rarity : <input type="text" name="rarity">
   <input type="submit" name="filter" value="filter">
  </form>

  <table>
   <tbody>
    <tr>
     <th>Card <input id="cardnb" type="text" disabled size="3"></th>
     <th>Cost</th>
     <th>Multiverseid</th>
     <th colspan="2">Actions</th><? // Colspan in order to get "remove" button and "set" form on same line, as form is block ?>
    </tr>
   </tbody>
   <tbody id="cards"></tbody>
  </table>

  <div id="stats">
   <label><input type="checkbox" id="stats_multi">Don't count multicolored</label>
   <div id="stats_color"></div>
   <div id="stats_cost"></div>
   <div id="stats_typelist"></div>
   <div id="stats_type"></div>
   <ul id="rarities"></ul>
  </div>

 </body>
</html>
