<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../includes/card.php' ;

$game = param_or_die($_GET, 'game') ;

$gameo = query_oneshot("SELECT `creator_id`, `joiner_id` FROM `round` WHERE `id` = '$game'") ;
switch ( $player_id ) {
	case $gameo->creator_id :
		$zone = 'game.creator.battlefield' ;
		break ;
	case $gameo->joiner_id :
		$zone = 'game.joiner.battlefield' ;
		break ;
	default :
		die('Player not creator nor joiner') ;
}

$cc = intval(param($_GET, 'cc', -1)) ;

$where = array("`types` LIKE '%Creature%'") ;

if ( $cc > -1 )
	$where[] = "`attrs` LIKE '%\"converted_cost\":$cc%'" ;

$connec = card_connect() ;
$i = 0 ;
while ( true ) {
	$i++ ;
	if ( $i > 100 )
		send_msg($i.' unsuccessfull tries', true) ;
	$card = query_oneshot("SELECT id, name, cost, types, attrs, fixed_attrs FROM card WHERE ".implode('AND ', $where)." ORDER BY RAND() LIMIT 1 ", 'Card search', $connec) ;
	if ( ! $card )
		send_msg('No card found with CC='.$cc, true) ;
	$ext = query_as_array("
		SELECT extension.se
		FROM card_ext, extension
		WHERE
			card_ext.card = '".$card->id."'
			AND card_ext.ext = extension.id
			AND card_ext.nbpics > 0
		ORDER BY RAND()
	", 'Card\' extension', $connec) ;
	if ( count($ext) > 0 ) {
		if ( ( $ext[0]->se == 'UG' ) || ( $ext[0]->se == 'UNH' ) )
			continue ;
		$card->ext = $ext[0]->se ;
		$card->zone = $zone ;
		break ;
	} else {
		send_msg('Can\'t send '.$card->name, false) ;
		d($ext) ;
	}
}
$card->attrs = json_decode($card->attrs) ;
$card->fixed_attrs = json_decode($card->fixed_attrs) ;

$escaped = addcslashes(json_encode($card), "'\"") ;
query("INSERT INTO `action` (
	`game`,
	`sender`,
	`local_index`,
	`recieved`,
	`type`,
	`param`
) VALUES(
	'$game',
	'',
	".time().",
	'0',
	'card',
	'$escaped'
);") ;

send_msg('Momired : '.$card->name) ;

function send_msg($txt, $die=null) {
	global $game, $player_id ;
	query("INSERT INTO `action` (
		`game`,
		`sender`,
		`local_index`,
		`recieved`,
		`type`,
		`param`
	) VALUES(
		'$game',
		'',
		".time().",
		'0',
		'text',
		'{\"text\": \"".addslashes($txt)."\"}'
	);") ;
	if ( $die )
		die($txt) ;
	else if ( is_bool($die) )
		echo $txt ;
}
?>
