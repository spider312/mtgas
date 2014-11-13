<?php
include_once 'lib.php' ;
html_head(
	'Admin > Cards > Check languages',
	array(
		'style.css'
		, 'admin.css'
	)
) ;
?>
 <body>
<?php
html_menu() ;
?>
  <div class="section">
   <h1>Extension / language list</h1>
   <a href="../">Return to admin</a>
   <table>
    <tr>
     <th>Ext</th>
     <th>Name</th>
     <th>Cards</th>
<?php
// Card names are not imported in asian languages (only images are)
foreach ( $langs as $code => $lang )
	if ( $code != 'en' )
		echo '     <th>'.$lang.'</th>'
?>
     <th>Actions</th>
    </tr>
<?
$query = query('SELECT *, UNIX_TIMESTAMP(release_date) as rd FROM extension ORDER BY release_date ASC') ;
while ( $arr = mysql_fetch_array($query) ) {
	$links = query_as_array('SELECT * FROM card_ext  WHERE `card_ext`.`ext` = '.$arr['id']) ;
	$nbcards = count($links) ;
	if ( $arr['sea'] != '' )
		$mcicode = $arr['sea'] ;
	else
		$mcicode = $arr['se'] ;
	$mcicode = strtolower($mcicode) ;
	echo "    <tr>\n" ;
	echo '     <td><a title="Open extension\'s admin page" href="/admin/cards/extension.php?ext='.$arr['se'].'">'.$arr['se']."</a></td>\n" ;
	echo '     <td><a title="Open extension on MCI" href="http://magiccards.info/'.$mcicode.'/en.html">'.$arr['name']."</a></td>\n" ;
	echo "     <td>$nbcards</td>\n" ;
	foreach ( $langs as $code => $lang ) {
		if ( $code == 'en' )
			continue ;
		$nbtranslations = 0 ;
		foreach ( $links as $link ) {
			$langlink = query_as_array("SELECT * FROM cardname  WHERE `cardname`.`card_id` = ".$link->card." AND `lang` = '$code' ") ;
			if ( count($langlink) > 0 )
				$nbtranslations++ ;

		}
		$text = $nbtranslations ;
		if ( $nbtranslations == $nbcards )
			$class = 'yes' ;
		else if ( $nbtranslations == 0 )
			$class = 'no' ;
		else
			$class = 'little' ;
		echo "     <td class=\"$class\">$text</td>\n" ;
	}
	echo '     <td><a href="/admin/cards/import/?source=mci&ext_source='.$mcicode.'&ext_local='.$arr['se'].'">Import</a></td>'."\n" ;
}
?>
   </table>
  </div>
 </body>
</html>
