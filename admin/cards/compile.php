<?php
include_once 'lib.php' ;
include 'import/HtmlDiff.php' ;

// Apply ?
$apply = param($_GET, 'apply', false) ;
if ( $apply !== false ) {
	$apply = true ;
}
?>
<html>
 <head>
  <link type="text/css" rel="stylesheet" href="../../themes/jay_kay/css/diff.css">
  <style>
table {
	width: 100%;
}
td {
	border: 1px solid black ;
	white-space: pre;
}
  </style>
 </head>
 <body>
  <pre>
<?php
// Create a list of changed attrs + Apply if needed
$changed = [] ;
$query = query('SELECT * FROM card ORDER BY `id` DESC') ; // Last added cards are managed first, in order not to wait before a die
$nb = 0 ;
while ( $arr = mysql_fetch_array($query) ) {
	$arr['text'] = card_text_sanitize($arr['text']) ;
	$attrs_obj = new attrs($arr) ;
	$attrs = json_encode($attrs_obj) ;
	if ( $arr['attrs'] != $attrs ) {
		$diff = new HtmlDiff(jsonpp($arr['attrs']), jsonpp($attrs)) ;
		$arr['diff'] = $diff->build() ;
		$changed[] = $arr ;
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
	}
}
?>
  </pre>
  <table>
   <tr>
    <th>Card</th>
    <th>Diff</th>
   </tr>
<?php
foreach ( $changed as $arr ) {
	echo '<tr>' ;
	echo '<td><a href="card.php?id='.$arr['id'].'">'.$arr['name'].'</a></td>' ;
	echo '<td>'.$arr['diff'].'</td>';
	echo '</tr>' ;
}
?>
   <caption><?php echo count($changed); ; ?> updates <a href="?apply=1">apply</a></caption>
  </table>
 </body>
</html>
