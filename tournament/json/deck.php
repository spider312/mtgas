<?php
include '../../lib.php' ;
include '../../includes/db.php' ;
include '../../includes/card.php' ;
include '../../includes/deck.php' ;
include '../../tournament/lib.php' ;
$id = param($_GET, 'id', 0) ;
$reg = registration_get($id) ;
if ( $reg == null )
	$deck = param_or_die($_POST, 'deck') ;
else
	$deck = $reg->deck ;
$deck = deck2arr($deck) ;
/* Stats for those cards */
$card_connection = card_connect() ;
$order_by = 'score_ratio' ;
//$order_by = 'play_score_ratio' ;
$cards = query_as_array("SELECT
	`pick`.`card_id`,
	`pick`.`sealed_open`,
	`pick`.`sealed_play`,
	`pick`.`sealed_score`,
	`pick`.`sealed_play` / `pick`.`sealed_open` as `sealed_play_ratio`,
	`pick`.`sealed_score` / `pick`.`sealed_open` as `sealed_score_ratio`,
	`pick`.`sealed_score` / `pick`.`sealed_play` as `sealed_play_score_ratio`
FROM
	`pick`,
	`card`
WHERE
	`card`.`id` = `pick`.`card_id`
ORDER BY
	`sealed_$order_by` DESC
;", 'stats', $card_connection) ;
function statsize($card, $cards) {
	global $order_by ;
	foreach ( $cards as $i => $c )
		if ( $c->card_id == $card->id )
			break ;
	$card->stats = $c ;
	$card->stats->rank = $i ;
	$card->stats->count = count($cards) ;
	$card->stats->order_by = $order_by ;
}
foreach ( $deck->main as $card )
	statsize($card, $cards) ;
foreach ( $deck->side as $card )
	statsize($card, $cards) ;
/* End of stats */
die(json_encode($deck)) ;
?>
