<pre><?php
include 'lib.php' ;
if ( ! array_key_exists('importer', $_SESSION) ) {
	print_r($_SESSION) ;
	die('No importer : '.session_name()) ;
}
$_SESSION['importer']->download() ;
$code = $_SESSION['importer']->dbcode ;
echo `/home/mogg/bin/thumb $code` ;
?></pre>
