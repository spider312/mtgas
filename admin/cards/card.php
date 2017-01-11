<?php
include_once 'lib.php' ;
$id = param_or_die($_GET, 'id') ;

html_head(
	'Admin > Cards > View one',
	array(
		'style.css'
		, 'admin.css'
	), 
	array (
		'lib/jquery.js',
		'math.js',
		'html.js'
	)
) ;
?>
  <script type="text/javascript">
function setimage(src, backsrc) {
	var img = new Image() ;
	img.addEventListener('load', function(ev) {
		var ci = document.getElementById('cardimage') ;
		ci.style.width = this.width+'px' ;
		ci.style.height = this.height+'px' ;
		ci.style.backgroundImage = 'url("'+src+'")' ;
	}, false) ;
	img.addEventListener('error', function(ev) {
		alert('Error loading '+ev.target.src) ;
	}, false) ;
	img.src = src ;
	if ( backsrc ) {
		var img = new Image() ;
		img.addEventListener('load', function(ev) {
			var ci = document.getElementById('cardimageback') ;
			ci.style.width = this.width+'px' ;
			ci.style.height = this.height+'px' ;
			ci.style.backgroundImage = 'url("'+backsrc+'")' ;
		}, false) ;
		img.src = backsrc ;
	}
}
function start() {
	ajax_error_management() ;
	document.getElementById('update_card').addEventListener('submit', function(ev) {
		ev.target.parentNode.classList.add('updating') ;
		if ( ev.target.fixed_attrs.value != '' )
			ev.target.fixed_attrs.value = JSON.stringify(JSON.parse(ev.target.fixed_attrs.value)) ;
		$.getJSON(ev.target.action, {
			'card_id': ev.target.card_id.value,
			'card_name': ev.target.card_name.value,
			'cost': ev.target.cost.value,
			'types': ev.target.types.value,
			'text': ev.target.text.value,
			'fixed_attrs': ev.target.fixed_attrs.value
		}, function(data) {
			ev.target.parentNode.classList.remove('updating') ;
			if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
				ev.target.submit.value = data.msg ;
			else {
				if ( data.nb != 1 )
					ev.target.submit.value = data.nb+' rows updated' ;
				else
					ev.target.submit.value = 'Updated' ;
			}
		}) ;
		return eventStop(ev) ;
	}, false) ;
}
  </script>

 <body onload="start()">
<?php
html_menu() ;
?>
  <div class="section">
<?php
$query = query("SELECT * FROM card WHERE `id` = '$id' ; ") ;
while ( $arr = mysql_fetch_array($query) )
	$card_bdd = $arr ; // Backup last extension line (normally only 1)
$nb = mysql_num_rows($query) ;
$comment = '' ;
if ( $nb != 1 )
	$comment = ' ('.mysql_num_rows($query).')' ;
echo '  <h1>'.$card_bdd['name'].$comment.'</h1>' ;
?>
   <a href="../">Return to admin</a>
<?php
echo '  <h2>#'.$card_bdd['id'].'</h2>' ;

// Add to extension
if ( array_key_exists('ext', $_GET) ) {
	$query = query("SELECT id FROM extension WHERE `se` = '".$_GET['ext']."'", 'ext2id') ;
	if ( $arr = mysql_fetch_array($query) ) {
		$ext_id = $arr['id'] ;
		if ( $card = query_oneshot("SELECT * FROM `card_ext` WHERE `card` = '$id' AND `ext` = '$ext_id' ;") ) {
			if ( query("UPDATE `card_ext` SET `rarity` = '".$_GET['rarity']."', `nbpics` = '".$_GET['nbpics']."' WHERE `card` = '".$card_bdd['id']."' AND `ext` = '$ext_id' LIMIT 1 ; ; ") )
				echo '<p>Card successfully updated for extension '.$_GET['ext'].' with rarity '.$_GET['rarity'].'</p>' ;
			else
				echo '<p>Impossible to update card for extension '.$_GET['ext'].' with rarity '.$_GET['rarity'].'</p>' ;
		} else {
			if ( query("INSERT INTO `card_ext` (`card`, `ext`, `rarity`, `nbpics`) VALUES ( '".$card_bdd['id']."', '$ext_id', '".$_GET['rarity']."', '".$_GET['nbpics']."') ; ") )
				echo '<p>Card successfully added to extension '.$_GET['ext'].' with rarity '.$_GET['rarity'].'</p>' ;
			else
				echo '<p>Impossible to add card to extension '.$_GET['ext'].' with rarity '.$_GET['rarity'].'</p>' ;
		}
	} else
		echo '<p>Can\'t find extension '.$_GET['ext'].' to add card to</p>' ;
}

// Extensions and images list
$query = query('SELECT * FROM card_ext, extension WHERE `card_ext`.`card` = '.$card_bdd['id'].' AND `card_ext`.`ext` = `extension`.`id` ORDER BY `extension`.`release_date` ASC ;') ;
while ( $arr_ext = mysql_fetch_array($query) ) {
	$tmp = array() ;
	$tmp['se'] = $arr_ext['se'] ;
	$tmp['ext'] = $arr_ext['ext'] ;
	$tmp['name'] = $arr_ext['name'] ;
	$tmp['nbpics'] = $arr_ext['nbpics'] ;
	$tmp['rarity'] = $arr_ext['rarity'] ;
	$tmp['images'] = array() ;
	$filename = '../img/FULL/'.$arr_ext['se'].'/'.$card_bdd['name'].'.full.jpg' ;
	if ( is_file($filename) )
		$tmp['images'][] = $filename ;
	$imgnb = 1 ;
	$filename = '../img/FULL/'.$arr_ext['se'].'/'.$card_bdd['name'].$imgnb.'.full.jpg' ;
	while ( is_file($filename) ) {
		$tmp['images'][] = $filename ;
		$filename = '../img/FULL/'.$arr_ext['se'].'/'.$card_bdd['name'].++$imgnb.'.full.jpg' ;
	}
	$ext[$tmp['se']] = $tmp ;
}
// Languages
$langs = query_as_array("SELECT * FROM cardname WHERE card_id = $id");
?>
  <a href="http://magiccards.info/query?q=!<?php echo $card_bdd['name'] ?>&v=card&s=cname">View on MCI</a>
  <table>
   <tr>
    <th>Extensions</th>
    <td>
     <ul>
<?php
$json = JSON_decode($card_bdd['attrs']) ;
foreach ( $ext as $i => $value ) {
	if ( $ext[$i]['nbpics'] == 0 )
		echo '      <li>'.$ext[$i]['name'].' ('.$ext[$i]['rarity'].')</li>' ;
	else if ( $ext[$i]['nbpics'] == 1 ) {
		$imgurl = $cardimages_default.'/'.$ext[$i]['se'].'/'.addslashes(card_img_by_name($card_bdd['name'])) ;
		if ( isset($json->transformed_attrs->name) ) {
			$imgurl .= "', '".$cardimages_default.'/'.$ext[$i]['se'].'/'.addslashes(card_img_by_name($json->transformed_attrs->name)) ;
		}
		echo '      <li><a href="extension.php?ext='.$ext[$i]['se'].'" onmouseover="javascript:setimage(\''.$imgurl.'\')">'.$ext[$i]['name'].'</a> ('.$ext[$i]['rarity'].')'."\n" ;
		echo '      </li>' ;
		if ( ! isset($firstimgurl) )
			$firstimgurl = $imgurl ;
	} else {
		echo '      <li><a href="extension.php?ext='.$ext[$i]['se'].'">'.$ext[$i]['name'].'</a> ('.$ext[$i]['rarity'].')'."\n" ;

		echo '       <ul>'."\n" ;
		for ( $j = 1 ; $j <= $ext[$i]['nbpics'] ; $j++) {
			$imgurl = $cardimages_default.'/'.$ext[$i]['se'].'/'.addslashes(card_img_by_name($card_bdd['name'], $j, $ext[$i]['nbpics'])) ;
			echo '        <li><a onmouseover="javascript:setimage(\''.$imgurl.'\')">'.$j.'</a></li>'."\n" ;
			if ( ! isset($firstimgurl) )
				$firstimgurl = $imgurl ;
		}
		echo '       </ul>'."\n" ;
		echo '      </li>'."\n" ;
	}
}
?>
     </ul>
     <script type="text/javascript">
	setimage('<?php echo $firstimgurl ; ?>') ;
     </script>
     <form action="card.php" method="get">
      <input type="hidden" name="id" value="<?php echo $card_bdd['id'] ; ?>">
      <input type="text" name="ext" value="EXT" size="4">
      <input type="text" name="rarity" value="C" size="1">
      <input type="text" name="nbpics" value="1" size="2">
      <input type="submit" value="Add">
     </form>
    </td>
    <td id="cardimage" rowspan="10" style="background-position: left top ; background-repeat: repeat-y">
<?php
if ( isset($json->transformed_attrs) ) {
?>
    <td id="cardimageback" rowspan="10" style="background-position: left top ; background-repeat: repeat-y">
<?php
}
?>
    </td>
   </tr>
   <form id="update_card" action="json/card.php">
    <input type="hidden" name="card_id" value="<?php echo $card_bdd['id'] ; ?>">
    <tr>
     <th>Name</th>
     <td>
      <input type="text" name="card_name" size="80" value="<?php echo $card_bdd['name'] ; ?>">
<?php
if ( count($langs) > 0 ) {
	echo '      <ul>'."\n";
	foreach ( $langs as $lang )
		echo '       <li>'.$lang->lang.' : '.$lang->card_name.'</li>'."\n";
	echo '      </ul>'."\n";
}
?>
     </td>
    </tr>
    <tr>
     <th>Cost</th>
     <td>
      <input type="text" name="cost" value="<?php echo $card_bdd['cost'] ; ?>">
      <input type="submit" name="submit" value="Update">
     </td>
    </tr>
    <tr>
     <th>Types</th>
     <td><input type="text" name="types" size="80" value="<?php echo $card_bdd['types'] ; ?>"></td>
    </tr>
    <tr>
     <th>Text</th>
     <td><textarea name="text" cols="80" rows="7"><?php echo $card_bdd['text'] ; ?></textarea></td>
    </tr>
    <tr>
     <th>Fixed</th>
     <td><textarea name="fixed_attrs" cols="80" rows="7"><?php echo $card_bdd['fixed_attrs'] ; ?></textarea></td>
    </tr>
    <tr>
     <th>Merged</th>
	 <td><pre>
<?php
if ( $card_bdd['fixed_attrs'] != '' ) {
// Merge stored and fixed
$merged = null ;
$fixed_attrs = json_decode($card_bdd['fixed_attrs']) ;
if ( $fixed_attrs == null ) {
	echo "{$card_bdd['name']} has buggy fixed attrs : {$card_bdd['fixed_attrs']}\n" ;
	echo json_verbose_error()."\n" ;
} else {
	$merged = JSON_decode($card_bdd['attrs']) ;
	foreach($fixed_attrs as $k => $v) {
		if ( isset($merged->$k) ) {
			echo " - Replacing stored attr $k with fixed one\n" ;
		}
		$merged->$k = $v ; // Overwrites array attrs such as tokens
	}
	echo "Result : " ;
	print_r($merged) ;
}
?>
<?php
} else {
    echo "No fixed to merge" ;
}
?>
     </pre></td>
    </tr>
    <tr>
     <th>Stored</th>
     <td title="<?php echo str_replace('"', "'", $card_bdd['attrs']) ; ?>"><pre><?php print_r($json) ; ?></pre></td>
    </tr>
    <tr>
     <th>Compile log</th>
     <td><pre><?php $attrs = new attrs($card_bdd) ;?></pre></td>
    </tr>
    <tr>
     <th>Compiled</th>
     <td title="<?php echo str_replace('"', "'", JSON_encode($attrs)) ; ?>"><pre><?php print_r($attrs) ; ?></pre></td>
    </tr>
   </form>

  </table>
 </body>
</html>
