<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'lib.php' ;
html_head(
	'Admin > Cards > Compare images and DB',
	array(
		'style.css'
		, 'admin.css'
	)
) ;
$url = param($_GET, 'url', '/home/hosted/mogg/img/HIRES/'/*$cardimages_default*/) ;
$repair = param($_GET, 'repair', '') ;
/*
$content = file_get_contents($url.'/cardlist.php') ;
$exts = json_decode($content) ;
if ( $exts == NULL )
	die(json_verbose_error(json_last_error())) ;
 */
function scan($dir) {
	if ( is_dir($dir) ) {
		$result = array() ;
		foreach ( scandir($dir) as $file ) 
			if ( ( $file != '..' ) && ( $file != '.' ) )
				$result[$file] = scan($dir.'/'.$file) ;
	} else
		$result = '' ;
	return $result ;
}
$exts = scan($url) ;
?>
 <body>
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Comparison between cards images and database's ones</h1>
   <a href="../">Return to admin</a>
   <form>
    <input type="text" name="url" value="<?php echo $url ; ?>">
   </form>
   <h2>Comparing DB and cards in <?php echo $url ; ?></h2>
   <table>
    <tr>
     <th>Ext</th>
     <th>Nb cards</th>
     <th>Nb img</th>
     <th>Missing images</th>
     <th>Missing cards</th>
     <th>Mean size</th>
    </tr>
<?
$query = query('SELECT *, UNIX_TIMESTAMP(release_date) as rd FROM extension ORDER BY release_date ASC') ;
while ( $arr = mysql_fetch_array($query) ) {
	$query_b = query('SELECT * FROM card_ext, card  WHERE `card_ext`.`ext` = '.$arr['id'].' AND `card`.`id` = `card_ext`.`card` ORDER BY `card`.`name`') ;
	$nbcards = 0 ;
	$cards = array() ;
	while ( $card = mysql_fetch_array($query_b) ) {
		$nbcards += $card['nbpics'] ;
		$cards[] = $card ;
		$attrs = json_decode($card['attrs']) ;
		if ( isset($attrs->transformed_attrs) && ( $card['name'] != $attrs->transformed_attrs->name ) ) { // Curse of the fire penguin has same
			$trcard = $card ; // Arrays are copied by value Oo
			$trcard['name'] = $attrs->transformed_attrs->name ;
			$nbcards += $card['nbpics'] ;
			$cards[] = $trcard ;
		}
	}
	$images= array() ;
	$unimagedcards = array() ;
	$foldersize = 0 ;
	if ( array_key_exists($arr['se'], $exts) ) {
		//$images = get_object_vars($exts->$arr['se']) ;
		$images = $exts[$arr['se']] ;
		$nbimages = count($images, true) ;
		while ( count($cards) > 0 ) {
			$card = array_shift($cards) ;
			for ( $i = 1 ; $i <= $card['nbpics'] ; $i++ ) {
				$cardimg = card_img_by_name($card['name'], $i, $card['nbpics']) ;
				if ( array_key_exists($cardimg, $images) ) {
					unset($images[$cardimg]) ;
					$foldersize += filesize($url.$arr['se'].'/'.$cardimg) ;
				} else {
					// Dual cards have an image for each face, do the same on both
					if ( ereg ('(.*) \((.*)\)', $card['name'], $regs) ) {
						$card1 = card_img_by_name($regs[1]) ;
						$card2 = card_img_by_name($regs[2]) ;
						if ( array_key_exists($card1, $images) && array_key_exists($card2, $images) ) {
							$nbimages-- ; // 2 images for 1 card, let's pretend having only 1 image
							unset($images[$card1]) ;
							unset($images[$card2]) ;
						} else
							$unimagedcards[] = $card['name'] ;
					} else
						$unimagedcards[] = $card['name'] ;
				}
			}
		}
	} else
		$nbimages = 0 ;
	if ( $nbcards == $nbimages )
		$class = 'yes' ;
	else
		$class = 'no' ;
	echo '     <tr class="'.$class.'">'."\n" ;
	echo '      <td><a href="http://dev.mogg.fr/admin/cards/extension.php?ext='.$arr['se'].'">'.$arr['se'].'</a></td>'."\n" ;
	echo '      <td>'.$nbcards.'</td>'."\n" ;
	echo '      <td>'.$nbimages.'</td>'."\n" ;
	echo '      <td>'."\n" ;
	if ( count($unimagedcards) > 0 ) {
		echo '       <ul><a title="See folder" href="'.$url.'/'.$arr['se'].'">'."\n" ;
		foreach ( $unimagedcards as $card )
			echo '        <li>'.$card.'</li>'."\n" ;
		echo '       </a></ul>'."\n" ;
	} else
		echo '       <i>None</i>'."\n" ;
	echo '      </td>'."\n" ;
	echo '      <td>'."\n" ;
	if ( count($images) > 0 ) {
		echo '     <a title="repair" href="?repair='.$arr['se'].'"><ul>'."\n" ;
		foreach ( $images as $card => $void ) {
			$card = card_name_by_img($card) ;
			echo '       <li>'.$card ;
			if ( $repair == $arr['se'] ) {
				$id = card_id($card) ;
				if ( $id > 0 ) {
					if ( query("INSERT INTO `card_ext` (`card`, `ext`, `rarity`, `nbpics`) VALUES ( '$id', '".$arr['id']."', 'C', '1') ; ") )
						echo ' (repaired)' ;
					else
						echo ' (NOT repaired)' ;
				}
			echo '       </li>'."\n" ;
			}
		}
		echo '      </ul>'."\n" ;
	} else
		if ( $nbimages == 0 )
			echo '       <strong>All</strong>'."\n" ;
		else
			echo '       <i>None</i>'."\n" ;
	echo '      </td>'."\n" ;
	if ( $nbimages == 0 )
		echo '      <td>N/A</td>'."\n" ;
	else
		echo '      <td>'.human_filesize(round($foldersize/$nbimages)).'</td>'."\n" ;
	echo '     </tr>'."\n" ;
	//unset($exts->$arr['se']) ;
	unset($exts[$arr['se']]) ;
	//echo count($exts) ;
}
?>
   </table>
   Not found in DB : 
   <pre><?php print_r($exts) ; ?></pre>
  </div>
 </body>
</html>
