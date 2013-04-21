<?php
$locale = "fr_FR.UTF8" ;
putenv("LC_ALL=$locale") ;
if ( ! setlocale(LC_ALL, $locale) )
	dir('No support for locale '.$locale) ;
bindtextdomain("messages", "./locale") ;
textdomain("messages") ;
function __($txt) { return _($txt) ; } // Wrapper
?>
