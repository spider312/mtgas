<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/card.php' ;
include 'includes/deck.php' ;
$order = param($_GET, 'order', 'score_ratio') ;
$report = param($_GET, 'report', '') ;
$type = param($_GET, 'type', '') ;
$color = param($_GET, 'color', '') ;
$rarity = param($_GET, 'rarity', '') ;
html_head(
	'Inclusion and performance statistics',
	array('style.css', 'mtg.css', 'sealed_top.css')
) ;
?>
 <body>
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Inclusion in sealed and performance statistics</h1>
   <h2>Report</h2>
   <form>
    Report :
    <select name="report">
<?php
$reports = scandir('stats') ;
foreach ( $reports as $r )
	if ( ( $r != '.' ) && ( $r != '..' ) ) {
		echo '     <option ' ;
		option_value($r, $report) ;
		echo '>'.$r.' (updated '.date ("F d Y H:i:s.", filemtime('stats/'.$r)).')</option>'."\n" ;
	}
?>
    </select>
   <h2>Params</h2>
    Sort : 
    <select name="order">
     <option <?php option_value("opened", $order) ; ?>>Opened</option>
     <option <?php option_value("played", $order) ; ?>>Played</option>
     <option <?php option_value("scored", $order) ; ?>>Scored</option>
     <option <?php option_value("play_ratio", $order) ; ?>>Play ratio</option>
     <option <?php option_value("score_ratio", $order) ; ?>>Score ratio</option>
     <option <?php option_value("play_score_ratio", $order) ; ?>>Score ratio (played)</option>
    </select>
    <br>
    Filter : 
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

<?php
if ( $report == '' )
	die('Please select a report') ;
$report = json_decode(file_get_contents('stats/'.$report)) ;
$card_connection = card_connect() ;
$p = array() ; ;
foreach ( $report->cards as $i => $card ) {
	$c = query_oneshot("SELECT `name`, `attrs`, `text` FROM `card` WHERE `id` = $i", 'Pick', $card_connection) ;
	$card->name = $c->name ;
	$card->attrs = $c->attrs ;
	$card->text = $c->text ;
	if ( $card->opened != 0 ) {
		$card->play_ratio = $card->played / $card->opened ;
		$card->score_ratio = $card->scored / $card->opened / 2 ;
	} else {
		$card->play_ratio = 0 ;
		$card->score_ratio = 0 ;
	}
	if ( $card->played != 0 )
		$card->play_score_ratio = $card->scored / $card->played / 2 ;
	else
		$card->play_score_ratio = 0 ;
	$p[] = $card ;
}
usort($p, 'sort_'.$order) ;
$totalgames = $report->starter_won + $report->starter_lost ;
$winpct = $report->starter_won / $totalgames ;
?>
   <h2>Stats</h2>
   <ul>
    <li><?=count((array)$report->tournaments);?> tournament parsed</li>
    <li>Starter won <?=$report->starter_won;?> (<?=round(100*$winpct, 2);?>%) and lost <?=$report->starter_lost;?> in <?=$totalgames;?> games</li>
   </ul>

   <h2>List</h2>
   <table>
    <tr>
     <th title="Ranking in selection / absolutely">#</th>
     <th title="Most found card's rarity in played extensions. Mouse over to get details">R</th>
     <th title="Card's name">Name</th>
     <th title="Card's colors">C</th>
     <th title="Number of times card was opened">Opened</th>
     <th title="Number of times card have been inserted in a deck">Played</th>
     <th title="Number of games won with this card in deck">Scored</th>
     <th title="Played / Opened">Play ratio</th>
     <th title="Scored / Opened">Score ratio</th>
     <th title="Scored / Played">Score ratio (played)</th>
    </tr>
<?php
$nb = 0 ;
foreach ( $p as $i => $c ) {
	// Rarity
	$crarity = 'S' ;
	$nrarity = 0 ;
	$rdisp = '' ;
	foreach ( $c->rarity as $r => $rnb ) {
		$rdisp .= $r.' : '.$rnb.' ' ;
		if ( $rnb > $nrarity ) {
			$crarity = $r ;
			$nrarity = $rnb ;
		}
	}
	// Filters
	if ( ( $rarity != '' ) && ( $crarity != $rarity ) )
		continue ;
	$d = json_decode($c->attrs) ;
	if ( ( $color != '' ) && is_string($d->color) && ( strpos($d->color, $color) === false ) )
		continue ;
	if ( ( $type != '' ) && is_array($d->types) && ( array_search($type, $d->types) === false ) )
		continue ;
	// Cost
	$manas = array() ;
	foreach ( $d->manas as $mana ) {
		if ( is_numeric($mana) || (  $mana == 'X' ))
			continue ;
		if ( array_search($mana, $manas) === false )
			$manas[] = $mana ;
	}
	if ( count($manas) == 0 )
		$manas[] = 'X' ;
	$colors = '' ;
	foreach ( $manas as $mana )
		$colors .= '<img src="'.theme_image('ManaIcons/'.$mana.'.png').'">' ;
	echo '    <tr title="'.$c->text.'">
     <td>'.$nb++.'/'.$i.'</td>
     <td class="bg_r_'.$crarity.'" title="'.$rdisp.'">'.$crarity.'</td>
     <td><a href="http://magiccards.info/query?q=!'.$c->name.'">'.$c->name.'</a></td>
     <td>'.$colors.'</td>
     <td>'.$c->opened.'</td>
     <td>'.$c->played.'</td>
     <td>'.$c->scored.'</td>
     <td>'.round($c->play_ratio*100, 2).'%</td>
     <td>'.round($c->score_ratio*100, 2).'%</td>
     <td>'.round($c->play_score_ratio*100, 2).'%</td>
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
<?php
function sort_by($field, $a, $b) {
	$res = $b->$field - $a->$field ;
	if ( $res == 0 )
		return 0 ;
	return $res / abs($res) ;
}
function sort_opened($a, $b) {
	return sort_by('opened', $a, $b) ;
}
function sort_played($a, $b) {
	return sort_by('played', $a, $b) ;
}
function sort_scored($a, $b) {
	return sort_by('scored', $a, $b) ;
}
function sort_play_ratio($a, $b) {
	return sort_by('play_ratio', $a, $b) ;
}
function sort_score_ratio($a, $b) {
	return sort_by('score_ratio', $a, $b) ;
}
function sort_play_score_ratio($a, $b) {
	return sort_by('play_score_ratio', $a, $b) ;
}
function option_value($value, $default) {
	echo 'value="'.$value.'"' ;
	if ( $value == $default )
		echo ' selected' ;
}
?>
