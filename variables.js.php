// This file is used to get data from PHP in Javascript
<?php include 'lib.php' ; ?>

// Internal
session_id = '<?php echo $session_id ; ?>' ;

// From conf / user data
url = '<?php echo $url ; ?>' ;
theme = '<?php echo $theme ; ?>' ;
cardimages = localStorage['cardimages'] ;
cardimages_default = '<?php echo $cardimages_default ; ?>' ;
default_avatar = '<?php echo $default_avatar ; ?>' ;
wsport = <?=$wsport;?> ;

// Options
	// Lang
<?php
function get_client_language(/*$availableLanguages, */$default='en'){
	if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
		// C : fr,fr-FR;q=0.8,en-US;q=0.6,en;q=0.4
		// F : fr-fr,fr;q=0.8,en;q=0.5,en-us;q=0.3
		$langs = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']) ;
		foreach ( $langs as $value ) {
			$choice = substr($value, 0, 2) ;
			//if( in_array($choice, $availableLanguages) )
				return $choice ;
		}
	}
	return $default ;
}
//$_COOKIE[$session_id]
echo "lang = '".get_client_language()."'\n" ;

/* Client side way
var a_lang = window.navigator.language.split('-') ;
lang = a_lang[0].toLowerCase() ;
var variant = lang
if ( a_lang.length == 2 ) // fr-FR
	var variant = a_lang[1].toLowerCase() ;*/
?>

// Images languages
langs = {} ;
<?php
foreach ( $langs as $code => $lang ) 
	echo "langs['$code'] = '$lang' ; \n" ;
?>
// Images default
if ( ( lang != 'en' ) && ( langs[lang] ) ) // Browser's language exists in languages
	cardimages_default_lang = 'http://img.mogg.fr/'+lang.toUpperCase()+'/'
else
	cardimages_default_lang = cardimages_default ;

	// Card images
cardimages_choice = {} ;
<?php
foreach ( $cardimages_choice as $choice_name => $choice_url ) 
	echo "cardimages_choice['$choice_url'] = '$choice_name' ; \n" ;
?>

// Application languages
applangs = {} ;
<?php
foreach ( scandir($localedir) as $dir )
	if ( ( $dir != '.' ) && ( $dir != '..' ) )
		echo "applangs['$dir'] = langs['$dir'] ; \n" ;
?>
if ( applangs[lang] )
	applang = lang ;
else
	applang = 'en' ;

// Index
draft_formats = <?php echo JSON_encode($suggest_draft) ; ?> ;
sealed_formats = <?php echo JSON_encode($suggest_sealed) ; ?> ;

// Game params
restricted_access = false ;
deckname_maxlength = 32 ; // Displayed length in deck list

// Colors
bgcolor = 'black' ;
bgopacity = .75 ; // cards
zopacity = .5 ; // zones
//fgopacity = 0.5 ;
stropacity = 1 ; // stroke opacity
bordercolor = 'white' ;
drawborder = false ;
largezonemargin = 50 ;
smallzonemargin = 10 ;

// Dimensions
if ( window.innerHeight > 800 )
	cardimagewidth = 250 ; // Width of card images in zoom, draft and build
else
	cardimagewidth = 180 ;
	// Paper internal
handheight = 90 ;
elementwidth = 100 ;
minturnsheight = 32 ;
manapoolswidth = 31 ;
	// Grid
bfrows = 7 ;
bfcols = 25 ; 
	// Card
cardwidth = 56 ;
cardheight = 80 ;
cardhandspace = 30 ;
place_offset = 2 ; // Offset on gris when "placing" cards (if "0, 0" is occupied, then try "0, offset", then "0, 2*offset" ... "offset, 0", "offset, offset"

// Delays
notification_duration = 5000 ; // Notifications auto-close after 5 sec in browsers not managing it

// Ingame timers during tournament
timer_notice_time = 20 ;
timer_alert_time = 10 ;
