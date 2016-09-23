<?php
include 'lib.php' ;
include 'includes/db.php' ;
include 'includes/card.php' ;
// Filter values
$default_types = array (
	'land' => 'Land',
	'artifact' => 'Artifact',
	'enchantment' => 'Enchant',
	'creature' => 'Creature',
	'planeswalker' => 'Planeswalker',
	'sorcery' => 'Sorcery',
	'instant' => 'Instant',
	'tribal' => 'Tribal'
) ;
$default_colors = array (
	'X' => 'Colorless',
	'W' => 'White',
	'U' => 'Blue',
	'B' => 'Black',
	'R' => 'Red',
	'G' => 'Green'
) ;
$default_rarities = array (
	'M' => 'Mythics',
	'R' => 'Rares',
	'U' => 'Uncos',
	'C' => 'Commons',
	'L' => 'Lands',
	'S' => 'Specials'
) ;

// Params
$report_name = param($_GET, 'report', '') ;
$order = param($_GET, 'order', 'score_ratio') ;
$type = param($_GET, 'type', array_keys($default_types)) ;
$color = param($_GET, 'color', array_keys($default_colors)) ;
$rarity = param($_GET, 'rarity', array_keys($default_rarities)) ;

// Get report data
$report = json_decode(file_get_contents('stats/'.$report_name)) ;

// Process score data
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

// Display
html_head(
	'Metagame cards analysis',
	array('style.css', 'options.css', 'mtg.css', 'metagame.css'),
	array('../variables.js.php', 'html.js', 'math.js', 'image.js', 'options.js', 'metagame.js')
) ;
?>
 <body onload="start()">
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Metagame cards analysis</h1>
   <div id="first_col">
   <p>Reports are statistics about cards included in decks for a given context (format, dates ...) in mogg's tournaments</p>
   <h2>Report</h2>
   <form>
    Report :
    <select name="report">
<?php
$reports = array_reverse(sorted_scandir('stats')) ;
foreach ( $reports as $r )
	if ( ( $r != '.' ) && ( $r != '..' ) ) {
		echo '     <option ' ;
		option_value($r, $report_name) ;
		echo '>'.$r.' (updated '.date ("F d Y H:i:s.", filemtime('stats/'.$r)).')</option>'."\n" ;
	}
?>
    </select>
    <input type="submit" value="Load" onclick="form_submit(event)">

<?php
if ( $report == '' ) {
	die('</form></div><br style="clear: both;"></body></html>') ;
}
echo '   <ul>' ;
if ( isset($report->date) )
	echo "<li>Selection by date : {$report->date}</li>" ;
if ( isset($report->exts) && ( count($report->exts) > 0 ) )
	echo "<li>Selection by extensions : ".implode(', ', $report->exts)."</li>" ;
if ( isset($report->mask) )
	echo "<li>Selection by name mask : {$report->mask}</li>" ;
if ( isset($report->imask) )
	echo "<li>Selection by name ignore mask : {$report->imask}</li>" ;
?>
   </ul>

   <a name="stats"></a>
   <h2>Stats</h2>
   <ul>
    <li><?=count((array)$report->tournaments);?> tournament parsed</li>
    <li>Starter won <?=$report->starter_won;?> (<?=round(100*$winpct, 2);?>%) and lost <?=$report->starter_lost;?> in <?=$totalgames;?> games</li>
   </ul>

   <h2>Display</h2>
    Filter : <br>
<?php
$filter_size = 8 ;
html_filter('type', $default_types, $type, $filter_size) ;
html_filter('color', $default_colors, $color, $filter_size) ;
html_filter('rarity', $default_rarities, $rarity, $filter_size) ;
?>
    <br>
    Sort : 
    <select name="order">
     <option <?php option_value("name", $order) ; ?>>Name</option>
     <option <?php option_value("opened", $order) ; ?>>Opened</option>
     <option <?php option_value("played", $order) ; ?>>Played</option>
     <option <?php option_value("scored", $order) ; ?>>Scored</option>
     <option <?php option_value("play_ratio", $order) ; ?>>Play ratio</option>
     <option <?php option_value("score_ratio", $order) ; ?>>Score ratio</option>
     <option <?php option_value("play_score_ratio", $order) ; ?>>Score ratio (played)</option>
    </select>
    <br>
    <input type="button" value="Reset" onclick="filter_reset(event);">
    <input type="submit" value="Apply" onclick="form_submit(event)">
   </form>
  </div>

  <div id="second_col">
   <a name="list"></a>
   <table>
    <tr>
     <th title="Ranking in selection / absolutely">#</th>
     <th title="Most found card's rarity in played extensions. Mouse over to get details">R</th>
     <th title="Card's name">Name</th>
     <th title="Card's cost">Cost</th>
     <th title="Number of times card was opened">Opened</th>
     <th title="Number of times card have been inserted in a deck">Played</th>
     <th title="Number of games won with this card in deck / 2">Scored</th>
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
	foreach ( $c->rarity as $r => $rnb ) { // Search most present rarities in rarities
		$rdisp .= $r.' : '.$rnb.' ' ;
		if ( $rnb > $nrarity ) {
			$crarity = $r ;
			$nrarity = $rnb ;
		}
	}
	// Filters
	if ( array_search($crarity, $rarity) === false ) {
		continue ;
	}
	$d = json_decode($c->attrs) ;
	if ( count(array_intersect(str_split($d->color), $color)) === 0 ) {
		continue ;
	}
	if ( count(array_intersect($d->types, $type)) === 0 ) {
		continue ;
	}
	echo '    <tr title="'.htmlentities((count($d->manas)>0 ? implode($d->manas)."\n" : '').$c->text).'">
     <td>'.$nb++.'/'.$i.'</td>
     <td class="bg_r_'.$crarity.'" title="'.$rdisp.'">'.$crarity.'</td>
     <td><a href="http://magiccards.info/query?q=!'.$c->name.'">'.$c->name.'</a></td>
     <td>'.manas2html($d->manas).'</td>
     <td>'.$c->opened.'</td>
     <td>'.$c->played.'</td>
     <td>'.($c->scored/2).'</td>
     <td>'.round($c->play_ratio*100, 2).'%</td>
     <td>'.round($c->score_ratio*100, 2).'%</td>
     <td>'.round($c->play_score_ratio*100, 2).'%</td>
    </tr>'."\n" ;
}
// Caption
echo '    <caption>'.$nb.' / '.count($p).' cards</caption>' ;
?>
   </table>
   </div>
  </div>
<?php
html_foot() ;

function sort_by($field, $a, $b) {
	$res = $b->$field - $a->$field ;
	if ( $res == 0 )
		return 0 ;
	return $res / abs($res) ;
}
function sort_name($a, $b) {
	if ( $a->name === $b->name ) 
		return 0 ;
	else if ( $a->name < $b->name )
		return -1 ;
	else
		return 1 ;
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
	if ( ! is_array($default) ) {
		$default = array($default) ;
	}
	if ( array_search($value, $default) !== false  )  {
		echo ' selected' ;
	}
}
function html_filter($name, $values, $selected, $size=0) {
	if ( $size < 1 ) {
		$size = count($values) ;
	}
	echo '    <select
	  name="'.$name.'[]" multiple="true" size="'.$size.'"
	  onmousedown="filter(event);"
	  onmouseover="filter(event);"
	  ondblclick="select(event);"
	  oncontextmenu="eventStop(event)"
	>'."\n" ;
	foreach ( $values as $key => $value ) {
		echo '     <option ' ;
		option_value($key, $selected) ;
		echo '>'.$value.'</option>'."\n" ;
	}
	echo '    </select>'."\n";
}
?>
