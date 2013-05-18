<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include 'lib.php' ;

// File upload/reuse
$file_input = 'list' ;
$dir = '/tmp/' ;
if ( isset($_FILES[$file_input]) ) {
	$file = $_FILES[$file_input]['name'] ;
	if ( ! move_uploaded_file($_FILES[$file_input]['tmp_name'], $dir.$file) ) {
		l($_FILES) ;
		die('upload NOT OK') ;
	}
	$apply = false ;
} else if ( isset($_GET['file']) ) {
	$file = $_GET['file'] ;
	$apply = true ;
} else
	die('No file uploaded') ;

// Extension name
$ext_parts = explode('.', $file) ;
if ( count($ext_parts) > 1 )
	array_pop($ext_parts) ; // Remove file extension
$ext = implode('.', $ext_parts) ;

// Read lines (= cards)
$cards = file($dir.'/'.$file) ;
if ( count($cards) <= 1 )
	die('File does not contain cards : <br><pre>'.$file_content.'</pre>') ;

?>
<!DOCTYPE html>
<html>
 <head>
  <title>Extension management : Install virtual from raw list</title>
  <link rel="stylesheet" href="style.css" type="text/css">
 </head>

 <body>
<?php
if ( ! $apply )
	echo '<p>Changes will NOT be applied <a href="?file='.$file.'">Apply</a>' ;

$masterlog = '' ;
$query = query("SELECT * FROM extension WHERE `se` = '$ext'") ;
if ( $res = mysql_fetch_object($query) ) {
	$ext_id = $res->id ;
	if ( $apply) {
		query("DELETE FROM `card_ext` WHERE `ext` = '$ext_id'") ;
		$masterlog = mysql_affected_rows().' cards unlinked from '.$ext."\n" ;
	} else {
		$links = query_as_array("SELECT * FROM `card_ext` WHERE `ext` = '$ext_id'") ;
		$masterlog = count($links).' cards are already in '.$ext."\n" ;
	}
} else {
	if ( $apply)
		$query = query("INSERT INTO extension (`se`, `name`) VALUES ('$ext', '".$cards[0]."')") ;
	$ext_id = mysql_insert_id() ;
}

?>
  <table>
   <tr>
    <th>Name</th>
    <th>Action</th>
   </tr>
<?php
for ( $i = 0 ; $i < count($cards) ; $i++ ) {
	$name = trim($cards[$i]) ;
	$name = str_replace('’', "'", $name) ;
	$name = str_replace('//', " / ", $name) ;
	$name = mysql_real_escape_string($name) ;
	$log = '<tr title="'.$name.'">' ;
	$log = '<td>'.$name.'</td>' ;
	$qs = query("SELECT id FROM card WHERE `name` = '$name'") ;
	if ( $arr = mysql_fetch_array($qs) )
		$card_id = $arr['id'] ;
	else {
		$masterlog .= 'Card not found : '.$name ;
		break ;
	}
	$query = query("SELECT * FROM card_ext WHERE `card` = '$card_id' AND `ext` = '$ext_id' ;") ;
	$log .= '<td>' ;
	if ( $res = mysql_fetch_object($query) ) {
	} else {
		if ( $apply)
			query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`) VALUES ('$card_id', '$ext_id', 'C', '0') ;") ;
		$log .= 'Added to '.$ext ;
	}
	$log .= '</td>' ;
	echo $log."</tr>\n" ;
}
echo '<caption>'.$i.' cards in list<pre>'.$masterlog.'</pre></caption>' ;
?>
  </table>
 </body>
</html>
