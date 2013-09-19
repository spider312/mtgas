<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/card.php' ;
include 'includes/deck.php' ;
$order = param($_GET, 'order', 'sealed_score_ratio') ;
$ext = param($_GET, 'ext', 'CUB') ;
$type = param($_GET, 'type', '') ;
$color = param($_GET, 'color', '') ;
$rarity = param($_GET, 'rarity', '') ;
function option_value($value, $default) {
	echo 'value="'.$value.'"' ;
	if ( $value == $default )
		echo ' selected' ;
}
?>
<form>
 Sort : 
 <select name="order">
  <option <?php option_value("sealed_open", $order) ; ?>>Opened</option>
  <option <?php option_value("sealed_play", $order) ; ?>>Played</option>
  <option <?php option_value("sealed_score", $order) ; ?>>Scored</option>
  <option <?php option_value("sealed_play_ratio", $order) ; ?>>Play ratio</option>
  <option <?php option_value("sealed_score_ratio", $order) ; ?>>Score ratio</option>
  <option <?php option_value("sealed_play_score_ratio", $order) ; ?>>Score ratio (played)</option>
 </select>
 <br>
 Filter : 
 <input type="text" name="ext" placeholder="Extension code" value="<?php echo $ext ?>" title="Extension code" size="3">
 <select name="type">
  <option <?php option_value("", $type) ; ?>>All types</option>
  <option <?php option_value("land", $type) ; ?>>Land</option>
  <option <?php option_value("artifact", $type) ; ?>>Artifact</option>
  <option <?php option_value("enchantment", $type) ; ?>>Enchant</option>
  <option <?php option_value("creature", $type) ; ?>>Creature</option>
  <option <?php option_value("planeswalker", $type) ; ?>>Planeswalker</option>
  <option <?php option_value("sorcery", $type) ; ?>>Sorcery</option>
  <option <?php option_value("instant", $type) ; ?>>Instant</option>
  <option <?php option_value("tribal", $type) ; ?>>Tribal</option>
 </select>
 <select name="color">
  <option <?php option_value("", $color) ; ?>>All colors</option>
  <option <?php option_value("X", $color) ; ?>>None</option>
  <option <?php option_value("W", $color) ; ?>>White</option>
  <option <?php option_value("U", $color) ; ?>>Blue</option>
  <option <?php option_value("B", $color) ; ?>>Black</option>
  <option <?php option_value("R", $color) ; ?>>Red</option>
  <option <?php option_value("G", $color) ; ?>>Green</option>
 </select>
 <select name="rarity">
  <option <?php option_value("", $rarity) ; ?>>All rarities</option>
  <option <?php option_value("M", $rarity) ; ?>>Mythics</option>
  <option <?php option_value("R", $rarity) ; ?>>Rares</option>
  <option <?php option_value("U", $rarity) ; ?>>Uncos</option>
  <option <?php option_value("C", $rarity) ; ?>>Commons</option>
  <option <?php option_value("S", $rarity) ; ?>>Specials</option>
 </select>
 <input type="submit">
</form>
<table>
<?php
$card_connection = card_connect() ;
if ( $ext == '' ) {
	$fromwhere = "
	FROM
		`pick`,
		`card`
	WHERE
		`card`.`id` = `pick`.`card_id`" ;
} else {
	$fromwhere = "
	FROM
		`pick`,
		`card`,
		`card_ext`,
		`extension`
	WHERE
		`extension`.`se` = '$ext' AND
		`extension`.`id` = `card_ext`.`ext` AND
		`card_ext`.`card` = `card`.`id` AND
		`card`.`id` = `pick`.`card_id`" ;
}
$p = query_as_array("
	SELECT
		`pick`.`sealed_open`,
		`pick`.`sealed_play`,
		`pick`.`sealed_score`,
		`pick`.`sealed_play` / `pick`.`sealed_open` as `sealed_play_ratio`,
		`pick`.`sealed_score` / `pick`.`sealed_open` as `sealed_score_ratio`,
		`pick`.`sealed_score` / `pick`.`sealed_play` as `sealed_play_score_ratio`,
		`card`.`name`,
		`card`.`attrs`, 
		`card_ext`.`rarity`
$fromwhere
	ORDER BY
		`$order` DESC
;", 'pick', $card_connection) ;

?>
 <tr>
  <th title="In selection / absolutely">#</th>
  <th>Name</th>
  <th>Opened</th>
  <th>Played</th>
  <th title="Number of matches won with this card in deck / 2">Scored</th>
  <th title="Played / Opened">Play ratio</th>
  <th title="Scored / Opened">Score ratio</th>
  <th title="Scored / Played">Score ratio (played)</th>
 </tr>
<?php
$nb = 0 ;
foreach ( $p as $i => $c ) {
	if ( ( $rarity != '' ) && ( $c->rarity != $rarity ) )
		continue ;
	$d = json_decode($c->attrs) ;
	//echo '<pre>' ; print_r($d) ; echo '</pre>' ;
	if ( ( $color != '' ) && is_string($d->color) && ( strpos($d->color, $color) === false ) )
		continue ;
	if ( ( $type != '' ) && is_array($d->types) && ( array_search($type, $d->types) === false ) )
		continue ;
	echo ' <tr>
  <td>'.$nb++.'/'.$i.'</td>
  <td><a href="http://magiccards.info/query?q=!'.$c->name.'">'.$c->name.'</a></td>
  <td>'.$c->sealed_open.'</td>
  <td>'.$c->sealed_play.'</td>
  <td>'.$c->sealed_score.'</td>
  <td>'.round($c->sealed_play_ratio*100, 2).'%</td>
  <td>'.round($c->sealed_score_ratio*100, 2).'%</td>
  <td>'.round($c->sealed_play_score_ratio*100, 2).'%</td>
 </tr>'."\n" ;
}
echo '<caption>'.$nb.' / '.count($p).' cards with type="'.$type.'" and color="'.$color.'"</caption>' ;
die('</table>') ;
?>
