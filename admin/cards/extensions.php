<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'lib.php' ;
$sort = param($_GET, 'sort', 'rd') ;
html_head(
	'Admin > Cards > Extensions list',
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
   <h1>Extensions</h1>
   <a href="../">Return to admin</a>
   <form method="get">
    <select name="sort">
     <option value="rd">Release date</option>
     <option value="priority">Priority</option>
    </select>
    <input type="submit">
   </form>
<?php
$ext = param($_GET, 'ext_del', 0) ;
if ( $ext != 0 ) {
	query("DELETE FROM extension WHERE `id` = '$ext' ; ") ;
	echo "  <p>Extension $ext removed</p>" ;
}
?>
   <table>
    <tr>
     <th>Abv.</th>
     <th>Full name</th>
     <th>Cards in DB</th>
     <th>Release date</th>
     <th title="For images, higher priority is selected">Priority</th>
     <th>Actions</th>
    </tr>
<?php
$query = query('SELECT *, UNIX_TIMESTAMP(release_date) as rd FROM extension ORDER BY '.$sort.' ASC') ;
while ( $arr = mysql_fetch_array($query) ) {
	$nbcards = 0 ;
	$query_b = query('SELECT * FROM card_ext WHERE `ext` = '.$arr['id']) ;
	while ( $card = mysql_fetch_object($query_b) )
		$nbcards += intval($card->nbpics) ;
	echo '    <tr>'."\n" ;
	echo '     <td><a href="extension.php?ext='.$arr['se'].'">'.$arr['se'].'</a></td>'."\n" ;
	echo '     <td>'.$arr['name'].'</td>'."\n" ;
	echo '     <td>'.$nbcards.'</td>'."\n" ;
	echo '     <td>'.date('d F Y', $arr['rd']).'</td>'."\n" ;
	echo '     <td>'.$arr['priority'].'</td>' ;
	echo '     <td><a href="?ext_del='.$arr['id'].'">del</a></td>'."\n" ;
	echo '    </tr>'."\n" ;
}
?>
   </table>
  </div>
 </body>
</html>
