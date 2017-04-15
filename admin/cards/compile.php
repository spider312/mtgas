<?php
include_once 'lib.php' ;
?>
<html>
<head>
<style>
td {
	border: 1px solid black ;
}
</style>
</head>
<body>
<table>
 <tr>
  <th>Card</th>
  <th>Removed</th>
  <th>Added</th>
 </tr>
<?php
// Apply ?
$apply = param($_GET, 'apply', false) ;
if ( $apply !== false )
	$apply = true ;
// Loop
$query = query('SELECT * FROM card ORDER BY `id` DESC') ; // Last added cards are managed first, in order not to wait before a die
$nb = 0 ;
while ( $arr = mysql_fetch_array($query) ) {
	$arr['text'] = card_text_sanitize($arr['text']) ;
	$attrs_obj = new attrs($arr) ;
	$attrs = json_encode($attrs_obj) ;
	if ( $arr['attrs'] != $attrs ) {
		$nb++ ;
		echo '<tr>' ;
		echo '<td><a href="card.php?id='.$arr['id'].'">'.$arr['name'].'</td>' ;
		$diff = obj_diff(json_decode($arr['attrs']), $attrs_obj) ;
		echo '<td title="'.str_replace('"', "'", JSON_encode($diff)).'"><pre>'.print_r($diff, true).'</pre></td>';
		$diff = obj_diff($attrs_obj, json_decode($arr['attrs'])) ;
		echo '<td title="'.str_replace('"', "'", JSON_encode($diff)).'"><pre>'.print_r($diff, true).'</pre></td>';
		if ( $apply ) {
			query("UPDATE
				card
			SET
				`attrs` = '".mysql_escape_string($attrs)."'
				, `text` = '".mysql_escape_string($arr['text'])."'
			WHERE
				`id` = '".$arr['id']."'
			; ") ;
		}
		echo '</tr>' ;
	}
}
?>
<caption><?php echo $nb ; ?> updates <a href="?apply=1">apply</a></caption>
</table>
</body>
</html>
