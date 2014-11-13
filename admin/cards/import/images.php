<pre><?php
include 'lib.php' ;
echo session_cache_expire()."\n" ;
if ( ! array_key_exists('importer', $_SESSION) ) {
	print_r($_SESSION) ;
	die('No importer : '.session_name()) ;
}
$_SESSION['importer']->download() ;
?></pre>
