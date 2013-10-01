<?php
include '../lib.php' ;
include '../includes/db.php' ;
include '../includes/card.php' ;

// Game
$game = param_or_die($_GET, 'game') ;
$gameo = query_oneshot("SELECT `creator_id`, `creator_nick`, `joiner_id`, `joiner_nick` FROM `round` WHERE `id` = '$game'") ;
switch ( $player_id ) {
	case $gameo->creator_id :
		$zone = 'game.creator.battlefield' ;
		$nick = $gameo->creator_nick ;
		break ;
	case $gameo->joiner_id :
		$zone = 'game.joiner.battlefield' ;
		$nick = $gameo->joiner_nick ;
		break ;
	default :
		die('Player not creator nor joiner') ;
}

// Avatar
$avatar = param_or_die($_GET, 'avatar') ;

// Converted cost
$cc = intval(param($_GET, 'cc', -1)) ;
$target = param($_GET, 'target', null) ;

//
$nb = 1 ;
$where = array() ;
switch ( $avatar ) {
	case 'momir' : 
		$where[] = "`types` LIKE '%Creature%'" ;
		$where[] = "`attrs` LIKE '%\"converted_cost\":$cc,%'" ;
		break ;
	case 'jhoira-instant' : 
		$where[] = "`types` LIKE '%Instant%'" ;
		$nb = 3 ;
		break ;
	case 'jhoira-sorcery' : 
		$where[] = "`types` LIKE '%Sorcery%'" ;
		$nb = 3 ;
		break ;
	case 'stonehewer' : 
		$where[] = "`types` LIKE '%Equipment%'" ;
		$where2 = array() ;
		for ( $i = 0 ; $i <= $cc ; $i++ )
			$where2[] = "`attrs` LIKE '%\"converted_cost\":$i,%'" ;
		$where[] = '( '.implode(' OR ', $where2).') ' ;
		send_msg($where[count($where)-1]) ;
		break ;
	case 'nokiou' : 
		$where[] = "`types` NOT LIKE '%Instant%'" ;
		$where[] = "`types` NOT LIKE '%Sorcery%'" ;
		$where[] = "`types` NOT LIKE '%Land%'" ;
		$where[] = "`types` NOT LIKE '%Creature%'" ;
		//$where[] = "`types` NOT LIKE '%Equipment%'" ;
		$where[] = "`attrs` LIKE '%\"converted_cost\":$cc,%'" ;
		break ;
	default :
		send_msg('Avatar '. $avatar.' unknown', true) ;
}

$connec = card_connect() ;
$i = 0 ;
while ( $nb > 0 ) {
	$i++ ;
	if ( $i > 100 )
		send_msg($i.' unsuccessfull tries', true) ;
	$card = query_oneshot("SELECT id, name, cost, types, attrs, fixed_attrs FROM card
		WHERE ".implode(' AND ', $where)." ORDER BY RAND() LIMIT 1 ", 'Card search', $connec) ;
	if ( ! $card )
		send_msg('No card found with CC='.$cc, true) ;
	$card->attrs = json_decode($card->attrs) ;
	$card->fixed_attrs = json_decode($card->fixed_attrs) ;
	$ext = query_as_array("
		SELECT extension.se, card_ext.nbpics
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
		$nbpics = intval($ext[0]->nbpics) ;
		if ( $nbpics > 1 ) {
			$card->attrs->nb = rand(1, $nbpics) ;
			send_msg($card->attrs->nb.' / '.$nbpics, false) ;
		}
		$card->ext = $ext[0]->se ;
		$card->zone = $zone ;
		if ( ( $avatar == 'stonehewer' ) && ( $target != null ) )
			$card->target = $target ;
		$escaped = addcslashes(json_encode($card), "'\"") ;
		query("INSERT INTO `action` ( `game`, `sender`, `local_index`, `recieved`, `type`, `param`)
			VALUES ( '$game', '', ".time().", '0', 'mojosto', '$escaped' );") ;
		send_msg($nick.'\'s avatar '.$avatar.' casts : '.$card->name) ;
		$nb-- ;
	} else
		send_msg('No extension found for '.$card->name, false) ;
}

function send_msg($txt, $die=null) {
	global $game, $player_id ;
	query("INSERT INTO `action` (`game`,`sender`,`local_index`,`recieved`,`type`,`param`)
		VALUES('$game','',".time().",'0','text','{\"text\": \"".addslashes($txt)."\"}');") ;
	if ( $die )
		die($txt) ;
	else if ( is_bool($die) )
		echo $txt ;
}
?>
