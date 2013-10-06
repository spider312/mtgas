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
html_head(
	'Inclusion and performance statistics for cards in '.$ext,
	array('style.css', 'mtg.css', 'sealed_top.css')
) ;
?>
 <body>
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Inclusion and performance statistics for cards in <?=$ext?></h1>
   <h2>Params</h2>
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
    <br>
    <input type="submit" value="Apply">
   </form>

   <h2>List</h2>
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
		`card`.`text`, 
		`card_ext`.`rarity`
$fromwhere
	ORDER BY
		`$order` DESC
;", 'pick', $card_connection) ;

?>
    <tr>
     <th title="Ranking in selection / absolutely">#</th>
     <th title="Card's rarity in this extension">R</th>
     <th title="Card's name">Name</th>
     <th title="Card's color">C</th>
     <th title="Number of times card was opened">Opened</th>
     <th title="Number of times card have been inserted in a deck">Played</th>
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
	if ( ( $color != '' ) && is_string($d->color) && ( strpos($d->color, $color) === false ) )
		continue ;
	if ( ( $type != '' ) && is_array($d->types) && ( array_search($type, $d->types) === false ) )
		continue ;
	$color = '' ;
	for ( $i = 0 ; $i < strlen($d->color) ; $i++ ) 
		$color .= '<span class="bg_c_'.substr($d->color, $i, 1).'">'.$d->color.'</span>' ;
	echo '    <tr title="'.$c->text.'">
     <td>'.$nb++.'/'.$i.'</td>
     <td class="bg_r_'.$c->rarity.'">'.$c->rarity.'</td>
     <td><a href="http://magiccards.info/query?q=!'.$c->name.'">'.$c->name.'</a></td>
     <td>'.$color.'</td>
     <td>'.$c->sealed_open.'</td>
     <td>'.$c->sealed_play.'</td>
     <td>'.$c->sealed_score.'</td>
     <td>'.round($c->sealed_play_ratio*100, 2).'%</td>
     <td>'.round($c->sealed_score_ratio*100, 2).'%</td>
     <td>'.round($c->sealed_play_score_ratio*100, 2).'%</td>
    </tr>'."\n" ;
}
// Caption
$caption = count($p).' cards' ;
$crit = array() ;
if ( $type != '' ) $crit[] = 'type="'.$type.'"' ;
if ( $color != '' ) $crit[] = 'color="'.$color.'"' ;
if ( $rarity != '' ) $crit[] = 'rarity="'.$rarity.'"' ;
if ( count($crit) > 0 )
	$caption = $nb.' / '.$caption.' with '.implode(', ', $crit) ;
echo '    <caption>'.$caption.'</caption>' ;
?>
   </table>
  </div>
 </body>
</html>
