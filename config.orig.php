<?php
// Application internals
$appname = 'MTGAS' ; // Must be different on servers hosted behind the same hostname
$default_theme = 'mogg' ;
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
$timeout = 60 ; // Time in secs without refresh from creator before ending a created game without an opponent
$daemon_delay = 1 ; // Time in secs between daemon iterations
$log = false ; // Daemon returning data
// Admin
$spoiler_dir = 'spoiler/ext' ;
$raw_dir = 'spoiler/raw' ;
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
$tournament_timeout = 3600 ; // Age in secs for a tournament to be canceled
$draft_base_time = 15 ; // Draft time = 15 secs + 5 secs per card in booster
$draft_time_per_card = 5 ;
$draft_lastpick_time = 60 ; // 60 seconds to views picks at the end of all boosters except last one
$build_duration = 20 * 60 ; // 20 min is enough, as cards sorting by color / cost is done by system
$round_duration = 40 * 60 ; // 40 min is enough, as all shuffle and tutor operation are fastened
	// Number of cards in boosters
$nb_c = 10 ; // 10 commons
$nb_u = 3 ; // 3 uncommons
$nb_r = 1 ; // 1 rare or 1 mythic
$nb_l = 0 ; // Useless, slows draft, complicates build
$proba_m = 8 ; // 1 chance over 8 to get a mythic instead of a rare
$proba_foil = 3 ; // 1 chance over 3 to get a (foil) card of any rarity instead of a common
	// Suggestion
$suggest_draft = array() ;
$suggest_draft['Current : Magic 2013'] = 'M13*3' ;
$suggest_draft['Previous : Avacyn Restored'] = 'AVR*3' ;
$suggest_draft['Pre-previous : Innistrad + Dark Ascension'] = 'DKA*1-ISD*2' ;
$suggest_draft['MTGO Cube'] = 'CUB*3' ;
$suggest_sealed = array() ;
$suggest_sealed['Current : Magic 2013'] = 'M13*6' ;
$suggest_sealed['Previous : Avacyn Restored'] = 'AVR*3' ;
$suggest_sealed['Pre-previous : Innistrad + Dark Ascension'] = 'DKA*3-ISD*3' ;
$suggest_sealed['MTGO Cube'] = 'CUB*6' ;
?>
