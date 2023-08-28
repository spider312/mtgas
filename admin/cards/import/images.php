<pre><?php
include 'lib.php' ;
if ( ! array_key_exists('importer', $_SESSION) ) {
	print_r($_SESSION) ;
	die('No importer : '.session_name()) ;
}
$_SESSION['importer']->download() ;

// Thumbnailing
$code = $_SESSION['importer']->dbcode ;
	// Images
echo "Thumbnailing images ... " ;
$begin = microtime(true) ;
`/home/mogg/bin/thumb $code` ;
echo 'Done in '.(microtime(true)-$begin)."ms\n" ;
	// Tokens
echo "Thumbnailing tokens ... " ;
$begin = microtime(true) ;
`/home/mogg/bin/thumb TK/$code` ;
echo 'Done in '.(microtime(true)-$begin)."ms\n" ;
?></pre>
