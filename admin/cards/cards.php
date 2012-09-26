<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'lib.php' ;

$name = param($_GET, 'name', '') ;

$data = card_search($_GET, $mysql_connection) ;
if ( $data->num_rows == 1 )
	header('location: card.php?id='.$data->cards[0]->id) ;
else {
	html_head(
		'Admin > Cards > Card search',
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
   <h1>Search results</h1>
   <a href="../">Return to admin</a>
  <form action="cards.php" method="get">
   <input type="text" name="name" value="<?php echo $name ; ?>">
   <input type="submit" value="Search">
  </form>
  <ul>
<?php
	echo '<p>'.$data->num_rows.' results in "'.$data->mode.'" search mode</p>' ;
	foreach ( $data->cards as $card )
		echo '   <li><a href="card.php?id='.$card->id.'">'.$card->name.'</a></li>'."\n" ;
?>
  </ul>
 </body>
</html>
<?php
}
?>
