<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include 'lib.php' ;
$apply = param($_GET, 'apply', false) ;
?>
<!DOCTYPE html>
<html>
 <head>
  <title>Extension management : Install virtual from raw list</title>
  <link rel="stylesheet" href="style.css" type="text/css">
 </head>

 <body>
<?php
if ( array_key_exists('file', $_GET) ) {
	$file = $_GET['file'] ;
	if ( ! $apply )
		echo '<p>Changes will NOT be applied <a href="?file='.$file.'&apply=1">apply</a></p>' ;
	if ( substr($file, -4) == '.txt' )
		$ext = substr($file, 0, -4) ; 
	else
		$ext = $file ;
	$cards = file($dir.'/'.$raw_dir.'/'.$file) ;
	if ( count($cards) <= 1 )
		echo 'File does not contain cards : <br><pre>'.$file_content.'</pre>' ;
	else {
?>
  <table>
   <tr>
    <th>Name</th>
    <th>Action</th>
   </tr>
<?php
		$masterlog = '' ;
		$query = query("SELECT * FROM extension WHERE `se` = '$ext'") ;
		if ( $res = mysql_fetch_object($query) ) {
			$ext_id = $res->id ;
			if ( $apply)
				query("DELETE FROM `card_ext` WHERE `ext` = '$ext_id'") ;
			$masterlog = mysql_affected_rows().' cards unlinked from '.$ext."\n" ;
		} else {
			if ( $apply)
				$query = query("INSERT INTO extension (`se`, `name`) VALUES ('$file', '".$cards[0]."')") ;
			$ext_id = mysql_insert_id() ;
		}
		for ( $i = 0 ; $i < count($cards) ; $i++ ) {
			$name = mysql_real_escape_string(trim($cards[$i])) ;
			$log = '<tr title="'.$name.'">' ;
			$log = '<td>'.$name.'</td>' ;
			$qs = query("SELECT id FROM card WHERE `name` = '$name'") ;
			if ( $arr = mysql_fetch_array($qs) )
				$card_id = $arr['id'] ;
			else 
				die('Card not found : '.$name) ;
			$query = query("SELECT * FROM card_ext WHERE `card` = '$card_id' AND `ext` = '$ext_id' ;") ;
			$log .= '<td>' ;
			if ( $res = mysql_fetch_object($query) ) {
			} else {
				if ( $apply)
					query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`) VALUES ('$card_id', '$ext_id', 'C', '0') ;") ;
				$log .= 'Added to '.$file ;
			}
			$log .= '</td>' ;
			echo $log."</tr>\n" ;
		}
?>
  </ul>
<?php
		echo '<pre>'.$masterlog.'</pre>' ;
	}
}
?>
 </body>
</html>
