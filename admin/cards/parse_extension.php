<?php
//include_once '../../includes/lib.php' ;
//include_once '../../config.php' ;
//include_once '../../lib.php' ;
//include_once '../../includes/db.php' ;
//include_once '../../includes/card.php' ;
include 'lib.php' ;
$apply = param($_GET, 'apply', false) ;
?>
<!DOCTYPE html>
<html>
 <head>
  <title>Extension management : Install a new one</title>
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
	$file_content = file_get_contents($dir.'/'.$spoiler_dir.'/'.$file) ;
	//$cards = explode('Name:', $file_content) ;
	$cards = explode('Card Name:	', $file_content) ;
	if ( count($cards) == 1 )
		echo 'File does not contain cards : <br><pre>'.$file_content.'</pre>' ;
	else {
?>
  <table>
   <tr>
    <th>Name</th>
    <th>Type</th>
    <th>New</th>
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
		for ( $i = 1 ; $i < count($cards) ; $i++ ) {
			$lines = preg_split( "/\r?\n/", $cards[$i]) ;
			$name = mysql_real_escape_string($lines[0]) ;
			$name = trim($name) ;
			if ( preg_match('/(.*) \((\d)\)/', $name, $matches) ) { // Extract number after card name, as it's the image ID for that extension
				$name = $matches[1] ; // Remove it from name
				$nbpics = intval($matches[2]) ;
			} else
				$nbpics = 1 ;
			$cost = 'none' ;
			$types = 'none' ;
			$powthou = '' ;
			$text = '' ;
			$rarity = 'N' ;
			for ( $j = 1 ; $j < count($lines)-2 ; $j++ ) // First line is already parsed, last 2 lines are spacer and begining of next card
				if ( $lines[$j] != '' ) {
					$lines[$j] = preg_replace('/\(.*\)/', '', $lines[$j]) ; // Remove reminder texts
					$firstchar = substr($lines[$j], 0, 1) ;
					if ( ( $firstchar != ' ' ) && ( $firstchar != "\t" ) ) // Line is a first line for field
						$cols = explode(':', $lines[$j], 2) ;
					else
						$cols = array($lines[$j]) ; // Here we have a second+ line of text containing ":", such as "B:regenerate", must consider it as only one line of text
					if ( count($cols) != 2 ) {
						if ( ! isset($lastfield) )
							$masterlog .= count($cols).' cols : ['.$lines[$j].'] - '.$name."\n" ;
						else
							$lastfield = $lastfield."\n".trim($cols[0]) ;
					} else {
						$attrname = trim($cols[0]) ;
						$attrval = trim($cols[1]) ;
						switch ( $attrname ) {
							case 'Card Name' :
							case 'Name' :
								$masterlog .= "Name managed\n" ;
								$lastfield =& $name ;
								break ;
							case 'Mana Cost' :
							case 'Cost' :
								$cost = str_replace('%', '', $attrval) ; // Hybrid mana is often under the form "%I"
								$lastfield =& $cost ;
								break;
							case 'Type & Class' :
							case 'Type' :
								$types = $attrval ;
								$lastfield =& $types ;
								break;
							case 'Pow/Tou' :
							case 'Pow/Tgh' :
								$powthou = $attrval ;
								$lastfield =& $powthou ;
								break;
							case 'Card Text' :
							case 'Text' :
								$text = mysql_real_escape_string($attrval) ;
								$lastfield =& $text ;
								break;
							case 'Rarity' :
								if ( strpos($types, 'Basic Land') === false ) // Little hack for spoilers containing basic lands as comons
									$rarity = $attrval ;
								else
									$rarity = 'L' ;
								break;
							default:
								unset($lastfield) ;
						}
					}
				}
			if ( $powthou != '' )
				$text = $powthou."\n".$text ;
			if ( ( strpos($cost, '/') > -1 ) && ( strpos($cost, '{') === FALSE ) ) // Correct hybrid costs
				$cost = preg_replace('#(.)/(.)#', '{$1/$2}', $cost) ;
			$log = '<tr title="'.$text.'">' ;
			$log = '<td class="rarity'.$rarity.'" title="'.$text.'">'.$name.'</td><td>'.$types.'</td>' ;
			$qs = query("SELECT * FROM card WHERE `name` = '$name'") ;
			if ( $arr = mysql_fetch_array($qs) ) {
				$compiled = json_encode(new attrs($arr)) ;
				$card_id = $arr['id'] ;
				$log .= "<td class=\"no\">No</td>" ;
				if ( $apply ) {
					query("UPDATE `card` SET 
						`cost` = '$cost',
						`types` = '$types',
						`text` = '".mysql_real_escape_string($text)."',
						`attrs` = '".mysql_real_escape_string($compiled)."'
					WHERE `id` = '$card_id' ; ") ;
					if ( mysql_affected_rows() > 0 )
						$log .= ' updated data ('.mysql_affected_rows().')' ;
				}
			} else {
				$card_id = 0 ; // Notices during first pass
				if ( $apply ) {
					$qc = query("INSERT INTO card (`name`, `cost`, `types`, `text`, `attrs`) VALUES ('$name', '$cost', '$types', '".mysql_real_escape_string($text)."', '".mysql_real_escape_string($compiled)."')") ;
					$card_id = mysql_insert_id() ;
				}
				$log .= '<td class="yes">Yes</td>' ;
			}
			$query = query("SELECT * FROM card_ext WHERE `card` = '$card_id' AND `ext` = '$ext_id' ;") ;
			$log .= '<td>' ;
			if ( $res = mysql_fetch_object($query) ) {
				if ( $apply) {
					if ( $res->nbpics > $nbpics ) // Do not downgrade nbpics (if 3 is managed after 4 for exemple)
						$nbpics = $res->nbpics ;
					query("UPDATE card_ext SET `rarity` = '$rarity', `nbpics` = '$nbpics' WHERE `card` = $card_id AND `ext` = $ext_id ;") ;
					if ( mysql_affected_rows() > 0 ) {
						$log .= 'Updated ('.mysql_affected_rows().') for '.$ext.' (' ;
						if ( $res->rarity != $rarity )
							$log .= 'rarity : '.$res->rarity.' -> '.$rarity ;
						if ( $res->nbpics != $nbpics )
							$log .= $res->nbpics.' -> '.$nbpics.' pics' ;
						$log .= ')' ;
					} else
						$log .= 'Nothing' ;
				}
			} else {
				if ( $apply)
					query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`) VALUES ('$card_id', '$ext_id', '$rarity', '$nbpics') ;") ;
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
