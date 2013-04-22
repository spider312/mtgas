<?php

//$xml = file_get_contents('dgm_update.xml') ;
$xml = file_get_contents('cards.xml') ;

/*
$p = xml_parser_create() ;
if ( xml_parse_into_struct($p, $xml, $vals, $index) == 0 ) {
	echo xml_get_error_code($p).' : '.xml_error_string(xml_get_error_code($p)) ;
}
echo count($index)."\n".count($vals)."\n" ;
//print_r($index) ;
//print_r($vals) ;
*/

$file = new SimpleXMLElement($xml) ;
$i = 0  ;
foreach ($file->cards->card as $card)
	if ( $card->set == 'DGM' ) {
		echo $card->name ;
		$i++ ;
	}
echo $i
//	echo gettype($card)."\n" ;

?>
