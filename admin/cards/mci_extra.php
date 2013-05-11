<style type="text/css">
caption {
	text-align: left ;
}
th {
}
</style>
<?php
include_once '../../config.php' ;
include_once '../../lib.php' ;
include_once '../../includes/db.php' ;
include_once '../../includes/card.php' ;
include_once 'lib.php' ;
include_once 'import.php' ;
$url = 'http://magiccards.info/extras.html' ;
echo '<p>Parsing <a href="'.$url.'">'.$url.'</a></p>' ;
$verbose = false ;
$html = cache_get($url, 'cache/mci_extras', $verbose) ;

$exts = preg_split ('/<a name="/', $html) ;
$i = 0 ;
array_shift($exts) ; // First part is begining of page, useless
?>
<table border="1">
 <tr>
  <th>MCI</th>
  <th>Filename</th>
  <th>Actions</th>
 </tr>
<?php
$nocodename = array() ;
$notoken = array() ;
$nodb = array() ;
foreach ( $exts as $ext ) {
	if ( ! preg_match('@^(?<code>.*?)"></a><h2>(?<name>.*?)</h2>@', $ext, $matches) ) {
		$nocodename[] = $ext ;
		continue ;
	}
	$nb = preg_match_all('@<tr style="background-color: #(e0e0e0|fafafa);">\s*<td><a href="/extra/token/'.$matches['code'].'/(?<url>.*?).html">(?<name>.*?)</a></td>\s*<td>(?<type>.*?)</td>\s*<td>(?<number>.*?)</td>\s*<td>(?<artist>.*?)</td>@', $ext, $matches_line, PREG_SET_ORDER) ;
	if ( $nb < 1 ) {
		$notoken[$matches['code']] = $matches['name'] ;
		continue ;
	}
	$query = query("SELECT * FROM extension WHERE `name` = '".$matches['name']."' ; ") ;
	if ( $res = mysql_fetch_object($query) )
		echo '<tr><th colspan="4"><a href="'.$url.'#'.$matches['code'].'">'.$res->name.' ('.$res->se.') '.'</a></th></tr>' ;
	else {
		$nodb[$matches['code']] = $matches['name'] ;
		continue ;
	}
	foreach ( $matches_line as $match ) {
		if ( $match['type'] == 'Token' ) {
			$img = cache_get('http://magiccards.info/extras/token/'.$matches['code'].'/'.$match['url'].'.jpg', 'cache/'.$matches['code'].'_'.$match['url'], $verbose) ;
			echo '<tr>' ;
			echo '<td><a href="http://magiccards.info/extra/token/'.$matches['code'].'/'.$match['url'].'.html">'.$match['name'].'</a></td>' ;
			switch ( $match['name'] ) {
				// Various bugs
				case 'Spirit 2 1/1' :
					$pname = 'SpiritU.1.1' ;
					break ;
				case 'Emblem' :
					$pname = 'Emblem.sorin' ;
					break ;
				case 'Pentavite' : 
					$pname = $match['name'].'.1.1' ;
					break ;
				case 'Poison Counter' :
					continue 2 ;
				case 'Wurm 1 3/3' : 
					$pname = 'Wurm.Deathtouch.3.3' ;
					break ;
				case 'Wurm 2 3/3' : 
					$pname = 'Wurm.Lifelink.3.3' ;
					break ;
				case 'Elf Warrior (2) 1/1' : 
					$pname = 'Elf Warrior.GW.1.1' ;
					break ;
				case 'Elemental (2) 4/4' : 
					$pname = 'Elemental.W.4.4' ;
					break ;
				// Normal case : parse pow/thou or planeswalker name
				default :
					if ( preg_match('@(?<name>.*?)(?<number> \(?\d\)?)? (?<pow>[\w|\*]+)/(?<thou>[\w|\*]+)@', $match['name'], $match_name) ) {
						if ( strlen($match_name['number']) > 0 ) {
							// Overrides for tokens having multiple tokens
							// Simpler than ignore all tokens that have multiple images on MCI despite having only one card IRL
							if ( ( $res->se == 'ISD' ) && ( $match_name['name'] == 'Zombie' ) )
								$match_name['name'] .= substr($match_name['number'], 1) ;
							if ( ( $res->se == 'ROE' ) && ( $match_name['name'] == 'Eldrazi Spawn' ) )
								$match_name['name'] .= substr($match_name['number'], 2, -1) ;

						}
						if ( ( $res->se == 'M12' ) && ( $match_name['name'] == 'Bird' ) ) {
							$match_name['pow'] = '3' ;
							$match_name['thou'] = '3' ;
						}
						( $match_name['pow'] == '*' ) && $match_name['pow'] = '0' ; // */* creatures have 0/0 in tk name
						( $match_name['thou'] == '*' ) && $match_name['thou'] = '0' ;
						$pname = $match_name['name'].'.'.$match_name['pow'].'.'.$match_name['thou'] ;
					} else {
						$parts = explode('-', $match['url']) ;
						if ( $parts[0] == 'emblem' )
							array_shift($parts) ;
						if ( count($parts) > 1 ) {
							$pname = 'Emblem.'.$parts[0] ;
							echo 'Emblem '.$parts[0] ;
						} else {
							echo 'Not a creature token nor emblem' ;
						}
					}

			}
			$iurl = 'HIRES/TK/'.$res->se.'/'.$pname.'.jpg' ;
			$lurl = $base_image_dir.$iurl ;
			echo '<td><a href="http://img.mogg.fr/'.$iurl.'">'.$pname.'</a></td>' ;
			echo '<td>'.human_filesize(strlen($img)) ;
			$oldumask = umask(0) ;
			if ( ! file_exists($lurl) ) {
				if ( ! file_exists(dirname($lurl)) ) {
					if ( mkdir(dirname($lurl), 0755, true) )
						echo '[Dir created]';
					else
						echo '[Dir NOT created]';
				}
				if ( copy('cache/'.$matches['code'].'_'.$match['url'], $lurl) )
					echo '[Created]' ;
				else
					echo '[NOT Created]' ;
			} else {
				if ( strlen($img) > filesize($lurl) ) {
					echo ' > '.human_filesize(filesize($lurl)) ;
					if ( copy('cache/'.$matches['code'].'_'.$match['url'], $lurl) )
						echo '[Updated]';
					else
						echo '[NOT Updated]';
				} else
					echo ' < '.human_filesize(filesize($lurl)) ;
			}
			umask($oldumask) ;
			echo '</td>' ;
			echo '</tr>' ;
		}
	}
}
echo '<caption>No code / name : '.count($nocodename).'<br><br>No token in parsed page : <ul>' ;
foreach ( $notoken as $k => $v )
	echo '<li><a href="'.$url.'#'.$k.'">'.$v.'</a></li>' ;
echo '</ul>Extention not found in db : <ul>' ;
foreach ( $nodb as $k => $v )
	echo '<li><a href="'.$url.'#'.$k.'">'.$v.'</a></li>' ;
echo '</ul></caption>' ;
?>
</table>
