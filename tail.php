<?php
include 'lib.php' ;
include 'includes/db.php' ;
html_head(
	'Tail (last actions recieved by server)',
	array(
		'style.css',
	)
) ;

?>
 <body>
  <table width="100%" id="tail">
   <thead>
    <tr>
     <td>id</td>
     <td>game</td>
     <td>player</td>
     <td>local_index</td>
     <td>recieved</td>
     <td>type</td>
     <td>param</td>
    </tr>
   </thead>
   <tbody>
<?php
function disp_obj($obj) {
	if ( $obj == null )
		echo '<i>null</i>' ;
	else
		foreach ( $obj as $key => $value )
			if ( is_object($value) || is_array($value) ) {
				echo "        <li>$key : \n         <ul>\n" ;
				disp_obj($value) ;
				echo "         </li>\n        </ul>\n" ;
			} else
				echo '       <li>'.$key.' : '.$value.'('.gettype($value).")</li>\n" ;
}
$game = param($_GET, 'game', 0) ;
if ( $game > 0 )
	$where = "WHERE `game` = '$game' ORDER BY `id` ASC" ;
else {
	$nb = param($_GET, 'nb', 100) ;
	$where = "ORDER BY `id` DESC LIMIT 0,$nb" ;
}
$query = query("SELECT * FROM `action` $where") ;
while ( $row = mysql_fetch_object($query) ) {
	$game = query_oneshot("SELECT * FROM `round` WHERE `id` = '".$row->game."' ;") ;
	$nick = 'nobody' ;
	switch ( $row->sender ) {
		case $game->creator_id ;
			$self = 'yes' ;
			$nick = $game->creator_nick ;
			break ;
		case $game->joiner_id ;
			$self = 'no' ;
			$nick = $game->joiner_nick ;
			break ;
		case '' :
			$self = 'little' ;
			$nick = 'server' ;
			break ;
		default :
			$self = '' ;
			$nick = 'another : '.$row->sender ;
	}
	$param = json_decode($row->param) ;
	echo '    <tr class="'.$self.'">
     <td>'.$row->id.'</td>
     <td>'.$row->game.'</td>
     <td class="nowrap">'.$nick.'</td>
     <td class="nowrap">'.date(DATE_RFC822, $row->local_index).'</td>
     <td>'.$row->recieved.'</td>
     <td>'.$row->type."</td>
     <td title='".$row->param."'>
      <ul>\n" ;
     disp_obj($param) ;
     echo '      </ul>
     </td>
    </tr>
' ;
}
?>
   </tbody>
  </table>
 </body>
</html>
