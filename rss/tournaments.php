<?php
include '../lib.php' ;
include '../includes/db.php' ;
$status = param($_GET, 'status', '') ;
header('Content-Type: text/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8" ?>' ;
?>
<rss version="2.0">
	<channel>
		<title>MOGG.fr Tournaments</title>
		<link>http://mogg.fr</link>
		<description>Tournaments on mogg.fr</description>
<?php

// List pending tournaments
if ( $status != '' )
	$q_status = "AND `status` = '$status'" ;
else
	$q_status = '' ;
$query = query("SELECT id, name, type, creation_date, status FROM `tournament` WHERE `min_players` > 1 $q_status ORDER BY `id` DESC LIMIT 0, 10") ;
while ( $row = mysql_fetch_object($query) ) {
	// List this tournament's registered players
	$players = query_as_array("SELECT nick FROM `registration` WHERE `tournament_id`='".$row->id."' ; ") ;
	$with = '' ;
	if ( count($players) > 0 ) {
		$players_names = array() ;
		foreach ( $players as $player )
			$players_names[] = $player->nick ;
		$with = ' with '.implode(', ',$players_names) ;
	}
	$link = '' ;
	if ( ( $row->status != '0' ) && ( $row->status != '6' ) )
		$link = $url . '/tournament/?id='.$row->id ;
	// Display item
?>
		<item>
			<guid><?php echo $row->id ; ?></guid>
			<pubDate><?php echo date("D, d M Y H:i:s", strtotime($row->creation_date)); ?></pubDate>
			<title><?php echo $row->type . ' ' . $row->name . $with . ' (#' . $row->id . ')' ; ?></title>
			<link><?php echo $link ; ?></link>
			<description><?php echo $row->type . ' ' . $row->name . $with ; ?></description>
		</item>
<?php
}
?>
	</channel>
</rss>
