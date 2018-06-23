<?php
function array_utf8_encode($dat) {
	if (is_string($dat))
		return utf8_encode($dat);
	if (!is_array($dat) && !is_object($dat))
		return $dat;
	$ret = array();
	foreach ($dat as $i => $d)
		$ret[$i] = array_utf8_encode($d);
	return $ret;
}
if ( array_key_exists('ext', $_GET) ) {
	include '../../../lib.php' ;
	include '../../../includes/db.php' ;
	include '../../../includes/card.php' ;
	$connec = card_connect() ;
	$ext = param_or_die($_GET, 'ext') ;
	$rarity = param($_GET, 'rarity', false) ;
	$type = param($_GET, 'type', false) ;
	$text = param($_GET, 'text', false) ;
	$where = '' ;
	if ( $rarity )
		$where .= 'AND `card_ext`.`rarity` = \''.$rarity.'\' ' ;
	if ( $type )
		$where .= 'AND `card`.`types` LIKE \'%'.$type.'%\' ' ;
	if ( $text )
		$where .= 'AND `card`.`text` LIKE \'%'.$text.'%\' ' ;
	$result = query_as_array("SELECT * FROM card_ext, card  WHERE `card_ext`.`ext` = '$ext' AND `card`.`id` = `card_ext`.`card` $where ORDER BY `card`.`name`", 'Card list', $connec) ;
	die(json_encode(array_utf8_encode($result))) ;
} else
	die("{'msg' : 'No ext in param'}") ;
?>
