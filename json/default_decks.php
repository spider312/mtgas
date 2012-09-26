<?php
$folder = '../decks/default/' ;
$decks = Array() ;
$decknames = Array() ;
if ($handle = opendir($folder))
	while ( false !== ($file = readdir($handle)) )
		if ( ( $file != '.' ) && ( $file != '..' ) ) {
			$deck = $file ;
			if ( ereg('(.*)\.mwDeck', $deck, $matches) )
				$deck = $matches[1] ;
			$decknames[] = $deck ;
			$content = file_get_contents($folder.'/'.$file) ;
			$content = str_replace('\\r\\n', "*", $content) ;
			$decks['deck_'.$deck] = $content ;
		}
$decks['decks'] = implode(',', $decknames) ;
die(json_encode($decks)) ;
?>
