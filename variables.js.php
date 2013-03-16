<?php include 'lib.php' ; ?>

// Internal
session_id = '<?php echo $session_id ; ?>' ;

// From conf / user data
url = '<?php echo $url ; ?>' ;
theme = '<?php echo $theme ; ?>' ;
cardimages = localStorage['cardimages'] ;
cardimages_default = '<?php echo $cardimages_default ; ?>' ;

// Options
cardimages_choice = {} ;
<?php
foreach ( $cardimages_choice as $choice_name => $choice_url ) 
	echo "cardimages_choice['$choice_url'] = '$choice_name' ; \n" ;
?>

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

// Timers (in ms)
game_list_timer = 1000 ;
create_timer = 1000 ;
ajax_interval = 1000 ; // ms between ajax calls
tournament_timer = 1000 ; // Tournament index refresh
draft_timer = 1000 ; // Draft refresh
sealed_timer = 1000 ; // Sealed refresh, give 30 secs before redirects attempts (when player didn't finish build)
