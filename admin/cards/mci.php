<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'lib.php' ;
include_once 'import.php' ;

// Args
if ( isset($argv) && ( count($argv) > 1 ) ) { // CLI
	if ( count($argv) > 1 )
		$ext = $argv[1] ;
	else
		die($argv[0].' [extension code]') ;
	if ( count($argv) > 2 )
		$ext_mci = $argv[2] ;
	else
		$ext_mci = $ext ;
	if ( ( count($argv) > 3 ) && $argv[3] )
		$apply = $argv[3] ;
	else
		$apply = false ;
} else { // Web
	$ext = param_or_die($_GET, 'ext') ;
	$ext_mci = param($_GET, 'ext_mci', $ext) ;
	$apply = param($_GET, 'apply', false) ;
}
// Funcs
$base_image_dir = substr(`bash -c "echo ~"`, 0, -1) ;
function image_downable($code) {
	global $base_image_dir, $ext_mci, $image_name, $ext, $nbpics, $match, $matches, $i, $apply ;
	echo $code.' : ' ;
	$image_name_l = $image_name ; // Local copy
	if ( ( $nbpics > 1 ) || ( ( $i < count($matches) - 1 ) && ( $match['name'] == $matches[$i+1]['name'] ) ) ) {// Next card has same name as current
		$image_name_l .= $nbpics ; // Append its number to its name
		echo $image_name.$nbpics.' / ' ;
	}
	$image_name_l = card_img_by_name($image_name_l) ;
	$image_path = $base_image_dir.'/img/'.strtoupper($code).'/'.$ext.'/'.$image_name_l ;
	if ( is_file($image_path) ) {
		echo 'Present' ;
		return false ;
	} else {
		echo 'Absent' ;
		$url = 'http://magiccards.info/scans/'.strtolower($code).'/'.strtolower($ext_mci).'/'.$match['id'].'.jpg' ;
		return array($url, $image_path) ;
	}
}
function image_download($arr) {
	$url = $arr[0] ;
	$image_path = $arr[1] ;
	echo $url.' -> '.$image_path.' : ' ;
	// Try to download image before anything else, don't create folder if image isn't downloadable (for cards not existing in this lang/ext)
	$content = @file_get_contents($url) ;
	if ( $content === FALSE ) {
		echo "Not DLable\n" ;
		return false ;
	}
	// Verify/create folder
	$image_dir = dirname($image_path) ;
	if ( ! is_dir($image_dir) ) {
		$oldumask = umask(0) ;
		$created = mkdir($image_dir, 0750, true) ;
		umask($oldumask) ;
		if ( $created ) 
			echo 'Dir created, ' ;
		else {
			echo 'Dir NOT created'."\n" ;
			return false ;
		}
	}
	// Write file
	$size = @file_put_contents($image_path, $content) ;
	if ( $size === FALSE )
		echo 'NOT updated' ;
	else {
		chmod($image_path, 0640) ;
		echo 'Downloaded ('.human_filesize($size).')' ;
	}
	echo "\n" ;
	return true ;
}

html_head(
	'Admin > Cards > MCI Parser',
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
   <h1>Get extension info from MCI</h1>
   <a href="../">Return to admin</a>
<?php
if ( $apply )
	echo '  <p>Changes <strong>applied</strong></p>' ;
else
	echo '  <p>Changes will NOT be applied <a href="?ext='.$ext.'&ext_mci='.$ext_mci.'&apply=1">apply</a></p>'."\n" ;
// Get page content, and parse
$ext = strtolower($ext) ;
$ext_mci = strtolower($ext_mci) ;
?>
  <table>
   <tr>
    <th colspan="2">#</th>
    <th>Name</th>
    <th>Rarity</th>
    <th>Card</th>
    <th>Extension</th>
    <th>Localization (image / name)</th>
   </tr>
<?php
// Extension in DB
$ext = strtoupper($ext) ;
$query = query("SELECT * FROM extension WHERE `se` = '$ext' OR `sea` = '$ext' ; ") ;
if ( $res = mysql_fetch_object($query) ) {
	$ext_id = $res->id ;
	if ( $apply) {
		query("DELETE FROM `card_ext` WHERE `ext` = '$ext_id'") ;
		echo '  <p>'.mysql_affected_rows().' cards unlinked from '.$ext."</p>\n\n" ;
	}
} else {
	$query = query("INSERT INTO extension (`se`, `name`) VALUES ('$ext', '".mysql_real_escape_string($matches[0]['ext'])."')") ;
	echo '<p>Extension not existing, creating</p>' ;
	$ext_id = mysql_insert_id() ;
}

// MCI card list
$url = 'http://magiccards.info/'.$ext_mci.'/en.html' ;
$html = cache_get($url, 'cache/'.$ext) ;
$nb = preg_match_all('#<tr class="(even|odd)">
\s*<td align="right">(?<id>\d*[ab]?)</td>
\s*<td><a href="(?<url>/'.$ext_mci.'/en/\d*a?b?\.html)">(?<name>.*)</a></td>
\s*<td>(?<type>.*)</td>
\s*<td>(?<cost>.*)</td>
\s*<td>(?<rarity>.*)</td>
\s*<td>(?<artist>.*)</td>
\s*<td><img src="http://magiccards.info/images/en.gif" alt="English" width="16" height="11" class="flag2">(?<ext>.*)</td>
\s*</tr>#', $html, $matches, PREG_SET_ORDER) ;
if ( $nb < 1)
	die('URL '.$url.' does not seem to be a valid MCI card list : '.count($matches)) ;

echo '<p>'.count($matches).' cards detected</p>'."\n\n" ;

$images = array() ;
$creation = 0 ;
$update = 0 ;
$nothing = 0 ;
$text = '' ;
foreach ( $matches as $i => $match ) {
	$log = '' ;
	$name = str_replace('á', 'a', $match['name']) ;
	$name = str_replace('é', 'e', $name) ;
	$name = str_replace('í', 'i', $name) ;
	$name = str_replace('ö', 'o', $name) ;
	$name = str_replace('ú', 'u', $name) ;
	$name = str_replace('û', 'u', $name) ;
	$name = str_replace('Æ', 'AE', $name) ;
	$rarity = substr($match['rarity'], 0, 1) ;
	// Parse card itself
	$html = cache_get(dirname($url).'/..'.$match['url'], 'cache/'.str_replace('/', '_', $match['url'])) ;
	$nb = preg_match('#<p>(?<typescost>.*)</p>
        <p class="ctext"><b>(?<text>.*)</b></p>.*http\://gatherer.wizards.com/Pages/Card/Details.aspx\?multiverseid=(?<multiverseid>\d*)#s', substr($html, 0, 10240), $card_matches) ;
	// Base checks
	if ( $nb < 1 ) {
		echo '<td colspan="4">Unparsable : <textarea>'.$html.'</textarea></td></tr>' ;
		continue ;
	}
	$multiverseid = intval($card_matches['multiverseid']) ;
	// Double cards : recompute name, mark as being second part (in which case card will be added, not replaced)
	$second = false ;
	$image_name = $name ;
	if ( preg_match('/(.*) \((\1)\/(.*)\)/', $name, $name_matches) ) {
		$name = $name_matches[2] . ' / ' . $name_matches[3] ;
		$image_name = $name_matches[2] . $name_matches[3] ;
	}
	if ( preg_match('/(.*) \((.*)\/(\1)\)/', $name, $name_matches) ) {
		$second = true ;
		$name = $name_matches[2] . ' / ' . $name_matches[3] ;
		$image_name = $name_matches[2].$name_matches[3] ;
	}
	// Text
	$dlimage = true ;
	$prevtext = $text ; // For Duals
	$text = str_replace('<br><br>', "\n", $card_matches['text']) ; // Un-HTML-ise text
	$text = trim($text) ;
	// Types / cost
	$typescost = $card_matches['typescost'] ;
	if ( preg_match('#(?<types>.*)(, \n(?<cost>.*))#', $typescost, $typescost_matches) ) {
		$types = $typescost_matches['types'] ;
		$cost = trim($typescost_matches['cost']) ;
	} else { // No 'cost' in 'types + cost', it's a land
		if ( preg_match('#(?<types>.*)\n#', $typescost, $land_matches) )
			$types = $land_matches['types'] ;
		else
			$types = $typescost ; // 'typescost' only contains 'type'
		$cost = '' ; // 'cost' is empty
	}
	$types = str_replace('—', '-', $types) ; 
	// Cost
	if ( preg_match('/(?<cost>.*) \((?<cc>\d*)\)/', $cost, $cost_matches) )
		$cost = $cost_matches['cost'] ;
	// Types
		// Creature
	if ( preg_match('/(?<types>.*) (?<pow>[^\s]*)\/(?<tou>[^\s]*)/', $types, $types_matches) ) {
		$types = $types_matches['types'] ;
		$text = $types_matches['pow'].'/'.$types_matches['tou']."\n".$text ;
	}
		// Planeswalker
	if ( preg_match('/(?<types>.*) \(Loyalty: (?<loyalty>\d)\)/', $types, $types_matches) ) {
		$types = $types_matches['types'] ;
		$text = $types_matches['loyalty']."\n".$text ;
	}
	echo "   <tr>\n" ;
	echo "    <td>".$card_matches['multiverseid']."</td>" ;
	echo "    <td>".($i+1)."</td>\n" ;
	echo "    <td>$name</td>\n" ;
	$facedown = ( intval($match['id']).'b' == $match['id'] ) ;
	if ( $facedown || $second ) { // Second part of dual cards (all cards having multiple lines on mci)
		if ( preg_match('/\(Color Indicator: (?<color>.{1,100})\)/', $html, $colors_matches) ) { // Double Face card
			// Don't work for chalice of life/death, as it has no color indicator
			$ci = '%' ;
			foreach ( explode(' ', $colors_matches['color']) as $color )
				if ( ( $c = array_search(strtolower($color), $colors) ) !== false )
					$ci .= $c ;
			$add = "\n-----\n$name\n$ci $types\n$text" ;
		} else { 
			$dlimage = false ; // Dual has only one image, Flipped is the same image
			if ( $second ) // Dual card
				$add = "\n----\n$cost\n$types\n$text" ;
			else // Flip card
				$add = "\n----\n$name\n$types\n$text" ;
		}
		$arr['text'] = $prevtext.$add ;
		echo '    <td colspan="3" title="'.$arr['text'].'">Update : text' ;
		if ( $apply)
			$q = query("UPDATE `card` SET
			`text` = '".mysql_real_escape_string($arr['text'])."'
			, `attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."'
			WHERE `id` = $card_id ;") ;
		echo '</td>' ;
	} else { // "normal" cards (1 line on mci) or first part of dual card
		$qs = query("SELECT * FROM card WHERE `name` = '".mysql_real_escape_string($name)."' ; ") ;
		echo "    <td>$rarity</td>\n" ;
		$nbpics = 1 ; // Anticipating number for first card havin same name as next one
		if ( $arr = mysql_fetch_array($qs) ) {
			$card_id = $arr['id'] ;
			echo "    <td>Existing $card_id<br>" ;
			// Update
			$updates = array() ;
			if ( $arr['cost'] != $cost ) {
				$log .= '<li>Cost : ['.$arr['cost'].'] -> ['.$cost.']</li>' ;
				$updates[] = "`cost` = '$cost'" ;
			}
			if ( $arr['types'] != $types ) {
				$log .= '<li>Types : ['.$arr['types'].'] -> ['.$types.']</li>' ;
				$updates[] = "`types` = '".mysql_real_escape_string($types)."'" ;
			}
			if ( trim($arr['text']) != $text ) {
				$log .= '<li><acro title="'.htmlspecialchars($arr['text']."\n->\n".$text).'">Text</acro></li>' ;
				$updates[] = "`text` = '".mysql_real_escape_string($text)."'" ;
			}
			if ( $log == '' ) {
				$nothing++ ;
				$log = 'up to date' ;
			} else {
				$update++ ;
				$log = 'Updates : <ul>'.$log.'</ul>' ;
				if ( $apply)
					$q = query("UPDATE `card` SET ".implode(', ', $updates).", `attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."' WHERE `id` = $card_id ;") ;
			}
			echo $log.'</td>' ;
			// Link with extension
			$query = query("SELECT * FROM card_ext WHERE `card` = '$card_id' AND `ext` = '$ext_id' ;") ;
			if ( $res = mysql_fetch_object($query) ) {
				echo "<td>Already in extension</td>\n" ;
				if ( $apply) {
					$nbpics = $res->nbpics + 1  ;
					query("UPDATE card_ext SET `rarity` = '$rarity', `nbpics` = '$nbpics', `multiverseid` = '$multiverseid' WHERE `card` = $card_id AND `ext` = $ext_id ;") ;
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
				echo "<td>Not in extension</td>\n" ;
				if ( $apply)
					query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`, `multiverseid`) VALUES ('$card_id', '$ext_id', '$rarity', '1', '$multiverseid') ;") ;
			}
		} else {
			$creation++ ;
			echo '    <td colspan="2" style="color:red;">Not existing</td>'."\n" ;
			if ( $apply) {
				// Insert card
				query("INSERT INTO `mtg`.`card`
				(`name` ,`cost` ,`types` ,`text`, `attrs`)
				VALUES ('".mysql_real_escape_string($name)."', '$cost', '$types', '".mysql_real_escape_string($text)."', '".mysql_escape_string(json_encode(new attrs($arr)))."');") ;
				$card_id = mysql_insert_id($mysql_connection) ;
				// Link to extension
				query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`, `multiverseid`) VALUES ('$card_id', '$ext_id', '$rarity', '1', '$multiverseid') ;") ;
				$log .= 'Created and linked to '.$ext.' ('.$card_id.')' ;
			} else
				$log .= '<b>Insert</b> : <ul><li>'.$name.'</li><li>'.$typescost.'</li><li>'.$types.'</li><li>'.$cost.'</li><li><pre>'.$text.'</pre></li></ul><b>Link</b> : <ul><li>'.$ext_id.'</li><li>'.$rarity.'</li>' ;
		}
	}
	// Image
	echo "    <td>" ;
	if ( ! $dlimage )
		echo 'Useless for flip cards' ;
	else {
		$nb = preg_match_all('#<img src="http://magiccards.info/images/(?<code>.{2}).gif" alt="(?<lang>.{1,100})" \n\s*width="16" height="11" class="flag2"> \n\s*<a href="(?<url>.{1,200})">(?<name>.{1,100})</a><br>#', $html, $matches_lang, PREG_SET_ORDER) ;
		if ( $nb < 1 )
			echo 'does not seem to be a valid MCI lang list<br>' ;
		if ( $dl = image_downable('en') ) // Force DL of EN, it's not referenced as a lang as current page is in english
			array_push($images, $dl) ;
		foreach ( $matches_lang as $j => $lang ) {
			$code = $lang['code'] ;
			$localname = $lang['name'] ;
			// Images
			echo "     <br>" ;
			if ( $dl = image_downable($code) )
				array_push($images, $dl) ;
			// Lang
			echo ", $localname : " ;
			if ( $facedown ) {
				echo 'face down not managed' ;
				continue ;
			}
			if ( array_search($code, array('de', 'fr', 'it', 'es', 'pt')) === false ) { // Expected charset
				echo 'not a managed language' ;
				continue ;
			}
			$query = query("SELECT * FROM `cardname` WHERE `card_id` = '$card_id' AND `lang` = '$code' ;") ;
			if ( $res = mysql_fetch_object($query) ) {
				if ( $res->card_name == $localname )
					echo "already right" ;
				else
					echo "<span class=\"no\">should be updated as $localname</span>" ;
			} else {
				if ( $apply ) {
					$query = query("INSERT INTO `mtg`.`cardname` (`card_id`, `lang` ,`card_name`) VALUES ('$card_id', '$code', '".mysql_real_escape_string($localname)."');") ;
					if ( $query )
						echo 'inserted' ;
					else
						echo 'not inserted' ;
				} else {
					echo 'will be inserted' ;
				}
			}
		}
	}
	echo "    </td>" ;
	echo "   </tr>\n" ;
}
?>
   <caption><?php echo "$creation created, $update updated, $nothing untouched" ; ?></caption>
  </table>
  <pre>
<?php
if ( $apply )
	foreach ( $images as $image )
		image_download($image) ;
?>
  </pre>
 </body>
</html>
