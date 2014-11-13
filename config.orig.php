<?php
// Application internals
$appname = 'MTGAS' ; // Must be different on servers hosted behind the same hostname
$default_theme = 'jay_kay' ;
$index_image = 'Mogg Maniac.crop.jpg' ; // Relative to theme folder
$url = 'http://mogg.fr/' ;
$dir = '/path/to/folder/containing/this/' ; // Required for cron inclusions, can be relative to ~
$cardimages_default = 'http://img.mogg.fr/MIDRES/' ; // Default working img dir
$cardimages_choice = Array(
	'High quality' => 'http://img.mogg.fr/HIRES/',
	'Medium quality' => 'http://img.mogg.fr/MIDRES/',
	'Very low quality (thumbs)' => 'http://img.mogg.fr/THUMB/',
	'French, low quality' => 'http://img.mogg.fr/FR_LQ/',
	'Custom' => ''
) ;
$default_avatar = 'img/avatar/kuser.png' ;
$daemon_delay = 1 ; // Time in secs between daemon iterations
$log = false ; // Daemon returning data
// Custom menu entries
//menu_add('Mogg', 'http://mogg.fr', 'Original website') ;
// Database
	// MTGAS database
$mysql_db = 'mtgas' ;
$mysql_login = 'mtgas' ;
$mysql_password = '' ;
	// Cards database (may be the same)
$card_db = 'mtgcards' ;
$card_login = 'mtgcards' ;
$card_password = '' ;
// Tournaments
	// Durations
$draft_base_time = 15 ; // Draft time = 15 secs + 5 secs per card in booster
$draft_time_per_card = 5 ;
$draft_lastpick_time = 60 ; // 60 seconds to views picks at the end of all boosters except last one
$build_duration = 20 * 60 ; // 20 min is enough, as cards sorting by color / cost is done by system
$round_duration = 40 * 60 ; // 40 min is enough, as all shuffle and tutor operation are fastened
	// Timeouts
$tournament_timeout = 3600 ; // Age in secs for a tournament to be canceled
$redirect_timeout = 180 ; // For players being redirected from index to a tournament
	// Limited magic numbers
$proba_m = 8 ; // 1 chance over 8 to get a mythic instead of a rare
$proba_foil = 3 ; // 1 chance over 3 to get a (foil) card of any rarity instead of a common
	// Suggestion
$suggest_draft = array() ;
$suggest_draft['MTGO Cube'] = 'CUB*3' ;
$suggest_sealed = array() ;
$suggest_sealed['MTGO Cube'] = 'CUB*6' ;
?>
