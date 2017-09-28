<?php
include_once 'lib.php' ;
$ext = param($_GET, 'ext', 'CUB') ;
$equery = query("SELECT * FROM extension WHERE `se` = '$ext' ; ", 'Extension selection') ;
if ( $e = mysql_fetch_object($equery) ) {
	header("Content-type: application/csv-tab-delimited-table"); 
	header("Content-disposition: attachment; filename=\"$ext.csv\"");
	$cquery = query('SELECT * FROM card_ext, card  WHERE `card_ext`.`ext` = '.$e->id.' AND `card`.`id` = `card_ext`.`card` ORDER BY `card`.`name`', 'Card selection') ;
	echo '"Rarity";"Name";"Color";"Cost";Converted;"Supertypes";"Types";"Subtypes";"Text"'."\n" ;
	while ( $c = mysql_fetch_object($cquery) ) {
		$j = json_decode($c->attrs) ;
		echo '"'.$c->rarity.'";"'.$c->name.'";"'.$j->color.'";"'.$c->cost.'";'.$j->converted_cost.';"' ;
		// Types, if exists
		if ( property_exists($j, 'supertypes') )
			echo implode(' ', $j->supertypes) ;
		echo '";"' ;
		if ( property_exists($j, 'types') )
			echo implode(' ', $j->types) ;
		echo '";"' ;
		if ( property_exists($j, 'subtypes') )
			echo implode(' ', $j->subtypes) ;
		echo '";"' . str_replace('"', "'", $c->text) ;
		echo '"'."\n" ;
	}
} else 
	die('Extension does not exists') ;
?>
