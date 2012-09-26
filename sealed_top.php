<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/card.php' ;
include 'includes/deck.php' ;
$ext = param($_GET, 'ext', 'CUB') ;
$order = param($_GET, 'order', 'sealed_score_ratio') ;
function option_value($value) {
	global $order ;
	echo 'value="'.$value.'"' ;
	if ( $value == $order )
		echo ' selected' ;
}
?>
<form>
 <input type="text" name="ext" placeholder="Extension code" value="<?php echo $ext ?>" title="Extension code">
 <select name="order">
  <option <?php option_value("sealed_open") ; ?>>Opened</option>
  <option <?php option_value("sealed_play") ; ?>>Played</option>
  <option <?php option_value("sealed_score") ; ?>>Scored</option>
  <option <?php option_value("sealed_play_ratio") ; ?>>Play ratio</option>
  <option <?php option_value("sealed_score_ratio") ; ?>>Score ratio</option>
  <option <?php option_value("sealed_play_score_ratio") ; ?>>Score ratio (played)</option>
 </select>
 <input type="submit">
</form>
<table>
<?php
$card_connection = card_connect() ;
if ( $ext == '' ) {
	echo ' <caption>Card inclusion score for all cards</caption>' ;
	$fromwhere = "
		FROM
			`pick`,
			`card`
		WHERE
			`card`.`id` = `pick`.`card_id`" ;
} else {
	echo ' <caption>Card inclusion score for extension '.$ext.'</caption>' ;
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
		`card`.`name`
$fromwhere
	ORDER BY
		`$order` DESC
;", 'pick', $card_connection) ;
/*	LIMIT
		0, 50*/

?>
 <tr>
  <th>#</th>
  <th>Name</th>
  <th>Opened</th>
  <th>Played</th>
  <th title="Number of matches won with this card in deck / 2">Scored</th>
  <th title="Played / Opened">Play ratio</th>
  <th title="Scored / Opened">Score ratio</th>
  <th title="Scored / Played">Score ratio (played)</th>
 </tr>
<?php
foreach ( $p as $i => $c ) {
	echo ' <tr>
  <td>'.$i.'</td>
  <td><a href="http://magiccards.info/query?q=!'.$c->name.'">'.$c->name.'</a></td>
  <td>'.$c->sealed_open.'</td>
  <td>'.$c->sealed_play.'</td>
  <td>'.$c->sealed_score.'</td>
  <td>'.round($c->sealed_play_ratio*100, 2).'%</td>
  <td>'.round($c->sealed_score_ratio*100, 2).'%</td>
  <td>'.round($c->sealed_play_score_ratio*100, 2).'%</td>
 </tr>'."\n" ;
}
die('</table>') ;
?>
