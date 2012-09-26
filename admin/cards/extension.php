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
	),
	array(
		'lib/jquery.js',
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
	echo '<h1>'.$ext.' - '.$ext_bdd['name'].' (#'.$ext_bdd['id'].')</h1>' ;
}
?>
<a href="extensions.php">Return to extension list</a>
<?php
// Remove card from extension
if ( array_key_exists('id', $_GET) ) {
	if ( array_key_exists('nbpics', $_GET) ) {
		$nb = $_GET['nbpics'] ;
		$rarity = $_GET['rarity'] ;
		$card_id = $_GET['id'] ;
		$ext_id = $ext_bdd['id'] ;
		$query = query("UPDATE `card_ext` SET `nbpics` = '$nb', `rarity` = '$rarity' WHERE `card`=$card_id AND `ext`=$ext_id ; ") ;
		if ( mysql_affected_rows() > 0 )
			echo '<p>'.mysql_affected_rows().' cards with id #'.$card_id.' set as '.$rarity.' and having '.$nb.'pics</p>' ;
		else
			echo '<p>Card #'.$_GET['id'].' not found in extension</p>' ;
	} else {
		$query = query("DELETE FROM card_ext WHERE `card` = '".$_GET['id']."' AND `ext` = '".$ext_bdd['id']."'; ") ;
		if ( mysql_affected_rows() > 0 )
			echo '<p>'.mysql_affected_rows().' cards with id #'.$_GET['id'].' removed from extension</p>' ;
		else
			echo '<p>Card #'.$_GET['id'].' not found in extension</p>' ;
	}
}

// Add card to extension
if ( array_key_exists('name', $_GET) ) {
	$query = query("SELECT * FROM card WHERE `name` = '".$_GET['name']."' ; ") ;
	if ( $arr_add_ext = mysql_fetch_array($query) ) {
		if ( query("INSERT INTO `card_ext` (`card`, `ext`, `rarity`) VALUES ( '".$arr_add_ext['id']."', '".$ext_bdd['id']."', '".$_GET['rarity']."') ; ") )
			echo '<p>Card '.$_GET['name'].' successfully added to extension with rarity '.$_GET['rarity'].'</p>' ;
		else
			echo '<p>Impossible to add card '.$_GET['name'].' to extension with rarity '.$_GET['rarity'].'</p>' ;
	} else
		echo '<p>Can\'t find card '.$_GET['name'].' to add to extension</p>' ;
}
?>
<?php
if ( isset($ext_bdd) ) {
	$query = query('SELECT * FROM card_ext, card  WHERE `card_ext`.`ext` = '.$ext_bdd['id'].' AND `card`.`id` = `card_ext`.`card` ORDER BY `card`.`name`') ;
?>
  <table>
   <tr>
    <th>Card (<?php echo mysql_num_rows($query) ; ?>)</th>
    <th>Cost</th>
    <th colspan="2">Actions for card link to extension</th>
   </tr>
<?php
	$rarities = array() ;
	while ( $arr = mysql_fetch_array($query) ) {
		echo '   <tr title="'.$arr['text'].'">'."\n" ;
		echo '    <td><a href="card.php?id='.$arr['id'].'">'.$arr['name'].'</a></td>'."\n" ;
		echo '    <td>'.$arr['cost'].'</td>'."\n" ;
		$filename = $arr['name'] ;
		if ( ! array_key_exists($arr['rarity'], $rarities) )
			$rarities[$arr['rarity']] = 1 ;
		else
			$rarities[$arr['rarity']]++ ;
?>
    <td>
     <form action="extension.php" method="get">
      <input type="hidden" name="ext" value="<?php echo $ext ; ?>">
      <input type="hidden" name="id" value="<?php echo $arr['id'] ; ?>">
      <input type="submit" value="Remove">
     </form>
    </td>
    <td>
     <form action="extension.php" method="get">
      <input type="hidden" name="ext" value="<?php echo $ext ; ?>">
      <input type="hidden" name="id" value="<?php echo $arr['id'] ; ?>">
      <input type="text" name="nbpics" value="<?php echo $arr['nbpics'] ;?>" size="2">
      <input type="text" name="rarity" value="<?php echo $arr['rarity'] ;?>" size="2">
      <input type="submit" value="Set nb pics / rarity">
     </form>
    </td>

<?php
		echo '   </tr>'."\n" ;
	}
}
?>
  </table>
  <ul>
<?php
foreach ( $rarities as $key => $val ) 
	echo '   <li>'.$key.' : '.$val.'</li>' ;
?>
  </ul>
 </body>
</html>
