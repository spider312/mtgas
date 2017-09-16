<?php
include_once 'lib.php' ;

$card_id = param($_GET, 'card_id', null) ;
$card_ids = param($_GET, 'card_ids', null) ;
$ext_id = param($_GET, 'ext_id', null) ;
$ext_ids = param($_GET, 'ext_ids', null) ;

// Remove card
$todel = array() ;
if ( $card_ids !== null ) {
	$todel = explode('|', $card_ids) ;
}
if ( $card_id !== null ) {
	array_push($todel, $card_id) ;
}
if ( count($todel) > 0 ) {
	echo 'Deleting ' . count($todel) . ' cards<br>' ;
	$rows = $db_cards->delete('DELETE FROM card WHERE id IN ("'.implode('", "', $todel).'")') ;
	echo $rows . ' cards deleted'."\n" ;
}

// Remove link
$todel = array() ;
if ( $ext_ids !== null ) {
	$todel = explode('|', $ext_ids) ;
}
if ( $ext_id !== null ) {
	array_push($todel, $ext_id) ;
}
if ( count($todel) > 0 ) {
	echo 'Deleting links for ' . count($todel) . ' exts<br>' ;
	$rows = $db_cards->delete('DELETE FROM card_ext WHERE ext IN ("'.implode('", "', $todel).'")') ;
	echo $rows . ' links deleted'."\n" ;
}

// Get all extensions
echo '<h1>Extensions</h1>'."\n" ;
$ext = array() ;
$query = query('SELECT id, se FROM extension WHERE se != "ALL" ORDER BY id ASC ;') ;
echo ' <ul>'."\n" ;
while ( $arr = mysql_fetch_array($query) ) {
	$ext[$arr['id']] = $arr['se'] ;
}
echo ' </ul>'."\n\n" ;

// Get all cards
echo '<h1>Cards</h1>'."\n" ;
$card = array() ;
$inNoExt = array() ;
$query = query('SELECT id, name FROM card ORDER BY id ASC ;') ;
while ( $arr = mysql_fetch_array($query) ) {
	$card[$arr['id']] = $arr['name'] ;
	$query_b = query('SELECT * FROM card_ext WHERE card = '.$arr['id'].' ; ') ;
	if ( mysql_num_rows($query_b) < 1 ) {
		$inNoExt[] = $arr ;
	}
}
if ( count($inNoExt) > 0 ) {
	echo '<h2>In no extension</h2>' . "\n" ;
	$ids = array() ;
	echo ' <ul>'."\n" ;
	foreach ( $inNoExt as $curr ) {
		echo '  <li><a href="?card_id='.$curr['id'].'">'.$curr['name'].' ('.$curr['id'].')</a></li>'."\n" ;
		array_push($ids, $curr['id']) ;
	}
	echo ' </ul>'."\n" ;
	echo ' <a href="?card_ids='.implode('|', $ids).'">Del all</a>' ;
}

// Check links
echo '<h1>Links</h1>'."\n" ;
$notExistingExt = array() ;
$notExistingCard = array() ;
$query = query('SELECT card, ext FROM card_ext ;') ;
while ( $arr = mysql_fetch_array($query) ) {
	if ( ! array_key_exists($arr['ext'], $ext) && ! in_array($arr['ext'], $notExistingExt) ) {
		$notExistingExt[] = $arr['ext'] ;
	}
	if ( ! array_key_exists($arr['card'], $card) && ! in_array($arr['card'], $notExistingCard) ) {
		$notExistingCard[] = $arr['card'] ;
	}
}
if ( count($notExistingExt) > 0 ) {
	echo '<h2>Ext not existing</h2>' . "\n" ;
	echo ' <ul>'."\n" ;
	foreach ( $notExistingExt as $curr ) {
		echo '  <li><a href="?ext_id='.$curr.'">' . $curr . '</li>' . "\n" ;
	}
	echo ' </ul>'."\n" ;
	echo ' <a href="?ext_ids='.implode('|', $notExistingExt).'">Del all</a>' ;
}
if ( count($notExistingCard) > 0 ) {
	echo '<h2>Card not existing</h2>' . "\n" ;
	echo ' <ul>'."\n" ;
	foreach ( $notExistingCard as $curr ) {
		echo '  <li>' . $curr . '</li>' . "\n" ;
	}
	echo ' </ul>'."\n" ;
}

?>
<p>finished</p>
