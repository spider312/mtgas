<pre><?php
include 'lib.php' ;
if ( ! array_key_exists('importer', $_SESSION) )
	die('No importer') ;
$_SESSION['importer']->download() ;
?></pre>
