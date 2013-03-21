<?php
// http://docs.planetteamspeak.com/ts3/php/framework/index.html
include 'includes/ts3/libraries/TeamSpeak3/TeamSpeak3.php' ;
$ts3_selected = false ;
// Lib
function ts3_co() {
	global $ts3_selected, $ts3_host ;
	$ts3_host = TeamSpeak3::factory("serverquery://213.246.55.19:10011/?nickname=MOGG");
	$ts3_host->serverSelect(1) ;
	return $ts3_selected = $ts3_host->serverGetSelected() ;
	//$ts3_myid = $ts3_host->whoamiGet('client_id') ;
	//$ts3_defaultchan = $ts3_host->whoamiGet('client_channel_id') ;
}
function ts3_disco() {
	global $ts3_selected, $ts3_host ;
	if ( ! $ts3_selected )
		return false ;
	$ts3_host->getAdapter()->getTransport()->disconnect() ;
}
function ts3_client($name) {
	global $ts3_selected ;
	if ( ! $ts3_selected )
		return false ;
	try {
		$matched = $ts3_selected->clientFind($name) ;
		if ( count($matched) == 1 ) {
			$client = array_shift($matched) ;
			return $client['clid'] ;
		} else {
			echo count($matched).' clients matching '.$name."\n" ;
			return false ;
		}
	} catch (Exception $e) {
		//echo 'Func exception: '.$e->getMessage().' : '.$name."\n" ;
		return false ;
	}
}
function ts3_chan($name='Unnamed', $topic=null, $parent=null) {
	global $ts3_selected ;
	if ( ! $ts3_selected )
		return false ;
	try { // Try to get
		$cid = $ts3_selected->channelGetByName($name) ;
	} catch (Exception $e) { // Catch create
		if ( $topic == null )
			$topic = $name ;
		$param = array(
		  'channel_name'           => $name,
		  'channel_topic'          => $topic,
		  'channel_flag_permanent' => False
		) ;
		if ( $parent != null )
			$param['cpid'] = $parent ;
		$cid = $ts3_selected->channelCreate($param) ;
		//echo 'Func exception in channel creating ('.$name.') : '.$e->getMessage()."\n" ;
	}
	return $cid ;
}
function ts3_invite($players, $chan, $needall = false) {
	global $ts3_selected ;
	if ( ! $ts3_selected )
		return false ;
	$clients = array() ; // Get a list of all players connected to TS (clients)
	foreach ( $players as $player )
		if ( $client = ts3_client($player->nick) )
			$clients[] = $client ;
	if ( $needall && ( count($players) != count($clients) ) ) // Need all players to be connected to invite them (duel chan)
		return false ;
	try {
		foreach ( $clients as $client )
			$ts3_selected->clientMove($client, $chan) ; // Invite clients
	} catch (Exception $e) {
		echo 'Func exception in player moving ('.$player->nick.'/'.$chan.') : '.$e->getMessage()."\n";
		return false ;
	}
	return true ;
}
?>
