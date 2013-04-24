<?php
// Returns a token list, grouped by extension, ordered by release date
function scan($dir) {
	if ( is_dir($dir) ) {
		$result = array() ;
		foreach ( scandir($dir) as $file ) 
			if ( ( $file != '..' ) && ( $file != '.' ) )
				$result[$file] = scan($dir.'/'.$file) ;
	} else
		$result = '' ;
	return $result ;
}
include '../lib.php' ;
include '../includes/db.php' ;
include '../includes/card.php' ;

// Database (for ordering by release date)
$connec = card_connect() ;
$query = query("SELECT extension.se FROM extension ORDER BY extension.release_date ", 'Card\' extension', $connec) ;

// Files (for tokens existence)
$base = '/home/hosted/mogg/img/HIRES/TK/' ;
$tokendirs = scan($base) ;

// Processing (list all existing tokens ordered by database)
$orderedtokens = array() ;
while ( $obj = mysql_fetch_object($query) )
	if ( isset($tokendirs[$obj->se]) ) {
		$orderedtokens[$obj->se] = $tokendirs[$obj->se] ;
		unset($tokendirs[$obj->se]) ;
	}
if ( isset($tokendirs['EXT']) ) { // Fallback tokens without specific extension
	$orderedtokens['EXT'] = $tokendirs['EXT'] ;
	unset($tokendirs['EXT']) ;
}

// Result
die(json_encode($orderedtokens)) ;
?>
