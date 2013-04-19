<?php
include_once '../../includes/lib.php' ;
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'lib.php' ;

// Args
if ( isset($argv) && ( count($argv) > 1 ) ) { // CLI
	if ( count($argv) > 1 )
		$ext = $argv[1] ;
	else
		die($argv[0].' [extension code]') ;
	if ( count($argv) > 2 )
		$apply = $argv[2] ;
	else
		$apply = false ;
} else { // Web
	$ext = param_or_die($_GET, 'ext') ;
	$apply = param($_GET, 'apply', false) ;
}
// Funcs
function image_downable($code) {
	global $base_image_dir, $ext, $image_name, $nbpics, $match, $matches, $i, $apply ;
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
		$url = 'http://magiccards.info/scans/'.strtolower($code).'/'.strtolower($ext).'/'.$match['id'].'.jpg' ;
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
		echo 'Not DLable'."\n" ;
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
		echo 'Updated ('.human_filesize($size).')' ;
	}
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
if ( ! $apply )
	echo '  <p>Changes will NOT be applied <a href="?ext='.$ext.'&apply=1">apply</a></p>'."\n" ;
// Init
$base_image_dir = substr(`bash -c "echo ~"`, 0, -1) ;
// Get page content, and parse
$ext = strtolower($ext) ;
$cache_file = 'cache/'.$ext ;
$url = 'http://magiccards.info/'.$ext.'/en.html' ;
if ( file_exists($cache_file) )
	$html = file_get_contents($cache_file) ;
else {
	$html = file_get_contents($url) ;
	file_put_contents($cache_file, $html) ;
}
$nb = preg_match_all('#  <tr class="(even|odd)">
    <td align="right">(?<id>\d*[ab]?)</td>
    <td><a href="(?<url>/'.$ext.'/en/\d*a?b?\.html)">(?<name>.*)</a></td>
    <td>(?<type>.*)</td>
    <td>(?<cost>.*)</td>
    <td>(?<rarity>.*)</td>
    <td>(?<artist>.*)</td>
    <td><img src="http://magiccards.info/images/en.gif" alt="English" width="16" height="11" class="flag2">(?<ext>.*)</td>
  </tr>#', $html, $matches, PREG_SET_ORDER) ;
if ( $nb < 1)
	die('URL '.$url.' does not seem to be a valid MCI card list : '.count($matches)) ;

echo '<p>'.count($matches).' cards detected</p>'."\n\n" ;

// Comparison with extension in DB
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
?>
  <table>
   <tr>
    <th>#</th>
    <th>Name</th>
    <th>Rarity</th>
    <th>Card</th>
    <th>Extension</th>
    <th>Localization (image / name)</th>
   </tr>
<?php
$images = array() ;
$text = '' ;
foreach ( $matches as $i => $match ) {
	$log = '' ;
	$name = str_replace('á', 'a', $match['name']) ;
	$name = str_replace('é', 'e', $name) ;
	$name = str_replace('í', 'i', $name) ;
	$name = str_replace('ú', 'u', $name) ;
	$name = str_replace('û', 'u', $name) ;
	$name = str_replace('Æ', 'AE', $name) ;
	$rarity = substr($match['rarity'], 0, 1) ;
	// Parse card itself
	$cache_file = 'cache/'.str_replace('/', '_', $match['url']) ;
	if ( file_exists($cache_file) )
		$html = file_get_contents($cache_file) ;
	else {
		$html = file_get_contents(dirname($url).'/..'.$match['url']) ;
		file_put_contents($cache_file, $html) ;
	}
	$nb = preg_match('#<p>(?<typescost>.*)</p>
        <p class="ctext"><b>(?<text>.*)</b></p>.*http\://gatherer.wizards.com/Pages/Card/Details.aspx\?multiverseid=(?<multiverseid>\d*)#s', substr($html, 0, 10240), $card_matches) ;
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
	// Base checks
	if ( $nb < 1 ) {
		echo '<td colspan="4">Unparsable : <textarea>'.$html.'</textarea></td></tr>' ;
		continue ;
	}
	/*
	if ( ( ! $second ) && ( intval($card_matches['multiverseid']) < 1 ) ) { // On MCI, second part of a card has no multiverseID
		echo '<td>No multiverseID</td></tr>' ;
		continue ;
	}
	*/
	// Text
	$prevtext = $text ; // For DFC
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
	echo "    <td>".($i+1)."</td>\n" ;
	echo "    <td>$name</td>\n" ;
	$facedown = ( intval($match['id']).'b' == $match['id'] ) ;
	if ( $facedown ) {
		if ( preg_match('/\(Color Indicator: (?<color>.{1,100})\)/', $html, $colors_matches) ) {
			$ci = '' ;
			foreach ( explode(' ', $colors_matches['color']) as $color ) {
				if ( ( $c = array_search(strtolower($color), $colors) ) !== false )
					$ci .= $c ;
			}
			$add = "\n-----\n$name\n%$ci $types\n$text" ;
			echo '    <td colspan="3" title="'.$add.'">Update : text' ;
			if ( $apply) {
				$arr['text'] = $prevtext.$add ;
				$q = query("UPDATE `card` SET
				`text` = '".mysql_real_escape_string($arr['text'])."'
				, `attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."'
				WHERE `id` = $card_id ;") ;
			}
			echo '</td>' ;
		} else
			echo '    <td colspan="3">Unmanaged double face card\'s face down (missing color indicator)</td>' ;
	} else if ( $second ) {
		$add = "\n----\n$cost\n$types\n$text" ;
		echo "    <td>Second part : $add</td>" ;
		if ( $apply) {
			$arr['text'] = $prevtext.$add ;
			$q = query("UPDATE `card` SET
			`text` = '".mysql_real_escape_string($arr['text'])."'
			, `attrs` = '".mysql_escape_string(json_encode(new attrs($arr)))."'
			WHERE `id` = $card_id ;") ;
		}
	} else {
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
			if ( $log == '' )
				$log = 'up to date' ;
			else {
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
				echo "<td>Not in extension</td>\n" ;
				if ( $apply)
					query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`) VALUES ('$card_id', '$ext_id', '$rarity', '1') ;") ;
			}
		} else {
			echo "    <td>Not existing</td>\n" ;
			if ( $apply) {
				// Insert card
				query("INSERT INTO `mtg`.`card`
				(`name` ,`cost` ,`types` ,`text`, `attrs`)
				VALUES ('".mysql_real_escape_string($name)."', '$cost', '$types', '".mysql_real_escape_string($text)."', '".mysql_escape_string(json_encode(new attrs($arr)))."');") ;
				$card_id = mysql_insert_id($mysql_connection) ;
				// Link to extension
				query("INSERT INTO card_ext (`card`, `ext`, `rarity`, `nbpics`) VALUES ('$card_id', '$ext_id', '$rarity', '1') ;") ;
				$log .= 'Created and linked to '.$ext.' ('.$card_id.')' ;
			} else
				$log .= '<b>Insert</b> : <ul><li>'.$name.'</li><li>'.$typescost.'</li><li>'.$types.'</li><li>'.$cost.'</li><li><pre>'.$text.'</pre></li></ul><b>Link</b> : <ul><li>'.$ext_id.'</li><li>'.$rarity.'</li>' ;
		}
	}
	// Image
	echo "    <td>" ;
	$nb = preg_match_all('#<img src="http://magiccards.info/images/(?<code>.{2}).gif" alt="(?<lang>.{1,100})" 
              width="16" height="11" class="flag2"> 
              <a href="(?<url>.{1,200})">(?<name>.{1,100})</a><br>#', $html, $matches_lang, PREG_SET_ORDER) ;
	if ( $nb < 1 )
		echo 'URL '.$url.' does not seem to be a valid MCI card list : '.count($matches) ;
	foreach ( $matches_lang as $j => $lang ) {
		$code = $lang['code'] ;
		$localname = $lang['name'] ;
		// Images
		if ( ( $j == 0 ) && ( $dl = image_downable('en') ) ) // Force DL of EN, it's not referenced as a lang as current page is in english
			array_push($images, $dl) ;
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
	echo "    </td>" ;
	echo "   </tr>\n" ;
}
?>
  </table>
  <pre>
<?php
if ( $apply )
	foreach ( $images as $image )
		image_download($image) ;
else
	print_r($images) ;
?>
  </pre>
 </body>
</html>
