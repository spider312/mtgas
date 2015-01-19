<?php
function mojosto($avatar, $cc) {
	global $db_cards ;
	$nb = 1 ; // Number of cards cast by avatar
	$where = array() ; // Array of conditions, defined upon avatar
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
			if ( $cc == 0 )
				return false ;
			$where[] = "`types` LIKE '%Equipment%'" ;
			$where2 = array() ;
			$i = 0 ;
			do {
				$where2[] = "`attrs` LIKE '%\"converted_cost\":$i,%'" ;
				$i++ ;
			} while ( $i < $cc );
			if ( count($where2) > 0 )
				$where[] = '( '.implode(' OR ', $where2).') ' ;
			break ;
		case 'nokiou' : 
			$where[] = "`types` NOT LIKE '%Instant%'" ;
			$where[] = "`types` NOT LIKE '%Sorcery%'" ;
			$where[] = "`types` NOT LIKE '%Land%'" ;
			$where[] = "`types` NOT LIKE '%Creature%'" ;
			$where[] = "`attrs` LIKE '%\"converted_cost\":$cc,%'" ;
			break ;
		default :
			echo 'Avatar '. $avatar.' unknown'."\n" ;
			return null ;
	}
	$cards = $db_cards->select("SELECT id, name, cost, types, attrs, fixed_attrs FROM card
			WHERE ".implode(' AND ', $where)." ORDER BY RAND() LIMIT $nb ") ;
	$result = array() ;
	foreach ( $cards as $card ) {
		$card->avatar = $avatar ;
		$card->attrs = json_decode($card->attrs) ;
		$card->fixed_attrs = json_decode($card->fixed_attrs) ;
		$ext = $db_cards->select("
			SELECT extension.se, card_ext.nbpics
			FROM card_ext, extension
			WHERE
				card_ext.card = '".$card->id."'
				AND card_ext.ext = extension.id
				AND card_ext.nbpics > 0
			ORDER BY RAND()
		") ; // AND se != 'UG' AND se != 'UNH'
		if ( count($ext) > 0 ) {
			$nbpics = intval($ext[0]->nbpics) ;
			if ( $nbpics > 1 )
				$card->attrs->nb = rand(1, $nbpics) ;
			$card->ext = $ext[0]->se ;
			$result[] = $card ;
		} else
			echo 'No extension found for '.$card->name."\n" ;
	}
	return $result ;
}
?>
