<?php
$locale = 'en_US.UTF8' ;
if ( isset($_COOKIE['lang']) ) {
	switch ($_COOKIE['lang']) {
		case 'fr' :
			$locale = "fr_FR.UTF8" ;
			break ;
		case 'en' :
			$locale = 'en_US.UTF8' ;
			break ;
	}
}
$localedir = $dir.'/locale' ;
putenv("LC_ALL=$locale") ;
if ( ! setlocale(LC_ALL, $locale) )
	die('No support for locale '.$locale) ;
bindtextdomain('messages', $localedir) ;
textdomain('messages') ;
function __($txt) { return _($txt) ; } // Wrapper
?>
