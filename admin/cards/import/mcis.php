<?php
include_once 'lib.php' ;
$source = param($_GET, 'source', 'json') ;

switch ( $source ) {
	case 'json' :
		//$url = 'http://mtgjson.com/json/AllSets.json' ;
		$url = 'http://mtgjson.com/json/SetList.json' ;
		$json = cache_get($url, 'cache/json_exts') ;
		$exts = json_decode($json) ;
		break ;
	case 'mci' :
		// Extract all extension in one language from MCI sitemap
		$url = 'http://magiccards.info/sitemap.html' ;
		$html = cache_get($url, 'cache/mci_sitemap') ;
		$nb = preg_match_all('@<a name="(?<langcode>.*?)"></a><h2>(?<langname>.*?)\s*<img src="http://magiccards.info/images/(?<langflag>.*?).gif" alt="(?<langaltname>.*?)" width="16" height="11">\s*<small style="color: #aaa;">(?<langsmallcode>.*?)</small></h2>\s*<table cellpadding="0" cellspacing="0" width="100%" border="0">(?<content>.*?)</table>@', $html, $matches, PREG_SET_ORDER) ;
		if ( $nb < 1)
			die('URL '.$url.' does not seem to be a valid MCI sitemap') ;
		$eng_matches = $matches[0] ;

		// Extract all extensions from the language section from MCI sitemap
		$nb = preg_match_all('@<li><a href="/(?<code>.*?)/en.html">(?<name>.*?)</a> <small style="color: #aaa;">(?<sea>.*?)</small></li>@', $eng_matches['content'], $matches, PREG_SET_ORDER) ;
		if ( $nb < 1)
			die('No extension found in : '.$eng_matches['content']) ;
		$exts = Array() ;
		foreach ( array_reverse($matches) as $m ) {
			$ext = new stdClass() ;
			$ext->code = strtoupper($m['code']) ;
			$ext->name = $m['name'] ;
			$exts[] = $ext ;
		}
		break ;
	default :
		die('Source '.$source.' not found') ;
}
?>
<a href="<?=$url;?>">Import from <?=$source;?> Sitemap</a>

<table>
 <tr>
  <th>DB se</th>
  <th>DB sea</th>
  <th>DB name</th>
  <th>MCI se</th>
  <th>MCI name</th>
  <th>Comment</th>
 </tr>
<?php
$db_exts = query_as_array("SELECT * FROM extension ORDER BY release_date;") ;
foreach ( $db_exts as $db_i => $db_ext ) {
	foreach ( $exts as $mci_i => $mci_ext ) {
		if (
			( strtoupper($db_ext->se) == $mci_ext->code )
			|| ( strtoupper($db_ext->sea) == $mci_ext->code )
			
		) {
			echo ' <tr>' ;
			echo '  <td>'.$db_ext->se.'</td>' ;
			echo '  <td>'.$db_ext->sea.'</td>' ;
			echo '  <td>'.$db_ext->name.'</td>' ;
			echo '  <td>'.$mci_ext->code.'</td>' ;
			echo '  <td>'.$mci_ext->name.'</td>' ;
			if ( $db_ext->name != $mci_ext->name )
				echo '  <td>Non identical names</td>' ;
			else
				echo '  <td>All OK</td>' ;
			echo ' </tr>' ;
			unset($exts[$mci_i]) ;
			unset($db_exts[$db_i]) ;
			continue 2 ;
		}
	}
	echo ' <tr>' ;
	echo '  <td>'.$db_ext->se.'</td>' ;
	echo '  <td>'.$db_ext->sea.'</td>' ;
	echo '  <td>'.$db_ext->name.'</td>' ;
	echo '  <td colspan="3" style="color: red">Not found on remote</td>' ;
	echo ' </tr>' ;
}
foreach ( $exts as $mci_ext ) {
	echo ' <tr>' ;
	echo '  <td colspan="3" style="color: red">Not found in DB</td>' ;
	echo '  <td>'.$mci_ext->code.'</td>' ;
	echo '  <td>'.$mci_ext->name.'</td>' ;
	echo '  <td style="color: red">Not found in DB</td>' ;
	echo ' </tr>' ;


}

?>
</table>
