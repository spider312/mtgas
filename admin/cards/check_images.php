<?php
include_once 'lib.php' ;
html_head(
	'Admin > Cards > Compare images and DB',
	array(
		'style.css'
		, 'admin.css'
	), 
	array('html.js')
) ;
$baseimagedir = '/home/mogg/img/' ;
$dir = param($_GET, 'dir', 'HIRES') ;
$url = $baseimagedir.$dir.'/' ;
$base = intval(param($_GET, 'base', '0')) ; // Only display base editions and extentions, usefull for language img, as other extentions aren't translated
$exts = scan($url) ;
if ( isset($exts['TK']) )
	unset($exts['TK']) ; // Don't compre Tokens
if ( isset($exts['back.jpg']) )
	unset($exts['back.jpg']) ;
?>
 <body>
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Comparison between cards images and database's ones</h1>
   <a href="../">Return to admin</a>
   <form>
    <select name="dir">
<?php
foreach ( scandir($baseimagedir) as $rep )
	if ( ( substr($rep, 0, 1) != '.' ) && ( array_search($rep, array('scrot', 'VOA')) === false ) ) {
		if ( $dir == $rep )
			$selected = 'selected="selected" ' ;
		else
			$selected = '' ;
		echo '     <option value="'.$rep.'"'.$selected.'>'.$rep.'</option>'."\n" ;
	}
?>
    </select>
    <label>
     <input type="checkbox" name="base" value="1" <?php if ( $base ) echo ' checked' ; ?>>
     Keep only base editions and extensions
    </label>
    <input type="submit" value="Refresh">
   </form>
   <h2>Comparing DB and cards in <?php echo $url ; ?></h2>
   <table>
    <tr>
     <th>Ext</th>
     <th>Nb cards</th>
     <th>Nb img</th>
     <th>Missing images</th>
     <th>Missing cards</th>
     <th>Folder size</th>
     <th>Mean size</th>
     <th>Variance</th>
    </tr>
<?php
$query = query('SELECT *, UNIX_TIMESTAMP(release_date) as rd FROM extension ORDER BY release_date ASC') ;
while ( $arr = mysql_fetch_array($query) ) {
	if ( $base && intval($arr['bloc']) < 0 ) {
		unset($exts[$arr['se']]) ;
		continue ;
	}
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
	$filesizes = array() ;
	if ( array_key_exists($arr['se'], $exts) ) {
		$images = $exts[$arr['se']] ;
		$nbimages = count($images, true) ;
		while ( count($cards) > 0 ) {
			$card = array_shift($cards) ;
			for ( $i = 1 ; $i <= $card['nbpics'] ; $i++ ) {
				$cardimg = card_img_by_name($card['name'], $i, $card['nbpics']) ;
				if ( array_key_exists($cardimg, $images) ) {
					unset($images[$cardimg]) ;
					$fs = filesize($url.$arr['se'].'/'.$cardimg) ;
					$filesizes[] = $fs/1024 ;
					$foldersize += $fs ;
				} else {
					// Dual cards have an image for each face, do the same on both
					if ( preg_match('/(.*) \((.*)\)/', $card['name'], $regs) ) {
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
	if ( $arr['sea'] != '' )
		$mcicode = $arr['sea'] ;
	else
		$mcicode = $arr['se'] ;
	$mcicode = strtolower($mcicode) ;
	echo '      <td><a href="http://magiccards.info/'.$mcicode.'/en.html" title="View card list on MCI">'.$arr['se'].'</a></td>'."\n" ;
	echo '      <td><a href="http://dev.mogg.fr/admin/cards/extension.php?ext='.$arr['se'].'" title="View card list in admin">'.$nbcards.'</a></td>'."\n" ;
	echo '      <td><a href="http://img.mogg.fr/'.$dir.'/'.$arr['se'].'" title="View images folder">'.$nbimages.'</a></td>'."\n" ;
	echo '      <td>'."\n" ;
	if ( count($unimagedcards) > 0 ) {
		echo '       <ul>'."\n" ;
		foreach ( $unimagedcards as $card )
			echo '        <li>'.$card.'</li>'."\n" ;
		echo '       </ul>'."\n" ;
	} else
		echo '       <i>None</i>'."\n" ;
	echo '      </td>'."\n" ;
	echo '      <td>'."\n" ;
	if ( count($images) > 0 ) {
		echo '     <ul>'."\n" ;
		foreach ( $images as $card => $void )
			echo '       <li>'.card_name_by_img($card).' </li>'."\n" ;
		echo '      </ul>'."\n" ;
	} else
		if ( ( $nbcards != 0 ) && ( $nbimages == 0 ) )
			echo '       <strong>All</strong>'."\n" ;
		else
			echo '       <i>None</i>'."\n" ;
	echo '      </td>'."\n" ;
	if ( $nbimages == 0 )
		echo '      <td colspan="3">N/A</td>'."\n" ;
	else {
		$meansize = round($foldersize/$nbimages) ;
		echo '      <td>'.human_filesize($foldersize).'</td>'."\n" ;
		echo '      <td style="background-color: '.nb2html(round($meansize/1024)).' !important ;">'.human_filesize($meansize).'</td>'."\n" ;
		$variance = round(variance($filesizes)) ;
		$cv = round((5000 - $variance ) / 20) ;
		echo '      <td style="background-color: '.nb2html($cv).' !important ;">'.$variance.'</td>'."\n" ;
	}
	echo '     </tr>'."\n" ;
	unset($exts[$arr['se']]) ;
}
function nb2html($nb, $max=255) {
	$nb = min($nb, $max) ; // $nb can't be > $max
	$mid = $max/2 ;
	$r = $g = $b = 0 ;
	if ( $nb <= $mid ) {
		$r = 255 ;
		$g = 2 * $nb ;
	} else {
		$nb -= $mid ;
		$g = 255 ;
		$r = 255 - $nb * 2 ;
	}
	return '#'.
		str_pad(base_convert($r, 10, 16), 2, '0', STR_PAD_LEFT).
		str_pad(base_convert($g, 10, 16), 2, '0', STR_PAD_LEFT).
		str_pad(base_convert($b, 10, 16), 2, '0', STR_PAD_LEFT) ;
}
function average($arr) {
	if (!count($arr)) return 0 ;
	$sum = 0 ;
	for ( $i = 0 ; $i < count($arr) ; $i++ )
		$sum += $arr[$i] ;
	return $sum / count($arr) ;
}
function variance($arr) {
	if (count($arr) < 2) return 0;
	$mean = average($arr) ;
	$sos = 0 ; // Sum of squares
	for ($i = 0 ; $i < count($arr) ; $i++)
		$sos += ($arr[$i] - $mean) * ($arr[$i] - $mean) ;
	return $sos / (count($arr)-1) ;
}
?>
   </table>
   Not found in DB : 
   <pre><?php print_r($exts) ; ?></pre>
  </div>
 </body>
</html>
