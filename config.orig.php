<?php
// MUST CHANGE
$dir = '/path/to/folder/containing/this/' ; // Required for cron inclusions, can be relative to ~
$url = 'http://mogg.fr/' ;
// Database
	// MTGAS database
$mysql_db = 'mtgas' ;
$mysql_login = 'mtgas' ;
$mysql_password = '' ;
	// Cards database (may be the same)
$card_db = 'mtgcards' ;
$card_login = 'mtgcards' ;
$card_password = '' ;

// Should change
$default_avatar = 'img/avatar/kuser.png' ;
	// Custom menu entries
//menu_add('Mogg', 'http://mogg.fr', 'Original website') ;
	// Suggestion for limited on index
$suggest_draft = array(
	'MOGG Cube' => 'CUB*3', 
	'Original MTGO Cube' => 'OMC*3'
) ;
$suggest_sealed = array(
	'MTGO Cube' = 'CUB*6',
	'Original MTGO Cube' => 'OMC*6'
) ;
	// Card images
$cardimages_default = 'http://img.mogg.fr/MIDRES/' ;
$cardimages_choice = Array( // Choices in options
//	'English, too high quality' => 'http://img.mogg.fr/XLHQ/',
	'English, high quality' => 'http://img.mogg.fr/HIRES/',
	'English, medium quality' => 'http://img.mogg.fr/MIDRES/',
//	'English, low quality (thumbs)' => 'http://img.mogg.fr/THUMB/',
	'Remastered' => 'http://img.mogg.fr/Modern/'
) ;
foreach ( $langs as $code => $name ) // Add each languages
	if ( $code != 'n' )
		$cardimages_choice[$name] =  'http://img.mogg.fr/'.strtoupper($code).'/' ;

// May change
$wait_duration = 3 * 60 ; // 3 minutes waiting for players beeing redirected
$build_duration = 40 * 60 ; // 40 mins for build
$round_duration = 60 * 60 ; // 60 mins for rounds as there are no additionnal turns

// Shouldn't change
$appname = 'MTGAS' ; // Must be different on servers hosted behind the same hostname
$default_theme = 'jay_kay' ;
$index_image = 'Mogg Maniac.crop.png' ; // Relative to theme folder
$log = false ; // Daemon returning data
$wsport = 1337 ;
// MTG rules to be tweaked
$draft_base_time = 15 ; // Draft time = 15 secs + 5 secs per card in booster
$draft_time_per_card = 5 ;
$draft_lastpick_time = 60 ; // 60 seconds to views picks at the end of all boosters except last one
$proba_m = 8 ; // 1 chance over 8 to get a mythic instead of a rare
$proba_foil = 3 ; // 1 chance over 3 to get a (foil) card of any rarity instead of a common
?>
