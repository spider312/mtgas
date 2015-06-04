<?php
// Params
$version_file = dirname(__FILE__).'/version.json' ;
$version_url = 'http://mtgjson.com/json/version.json' ;
$changelog_url = 'http://mtgjson.com/json/changelog.json' ;
// Local version
if ( file_exists($version_file) )
	$local_version = JSON_decode(file_get_contents($version_file)) ;
else {
	echo "No current version present\n" ;
	$local_version = '0' ;
}
// Remote version
$content = @file_get_contents($version_url) ;
if ( $content === false )
	die('Unable to download remote version') ;
$remote_version = JSON_decode($content) ;
// Version changed
if ( $remote_version != $local_version ) {
	echo "Version change : $local_version -> $remote_version : \n" ;
	file_put_contents($version_file, $content) ;
	$content = @file_get_contents($changelog_url) ;
	if ( $content === false )
		die('Unable to download changelog') ;
	$changelog = JSON_decode($content) ;
	foreach ( $changelog as $log ) {
		if ( version_compare($log->version, $local_version, '>') ) {
			echo "$local_version -> {$log->version} ({$log->when}) : \n" ;
			foreach ( $log->changes as $change )
				echo " - $change\n" ;
			if ( property_exists($log, 'newSetFiles') )
				echo "New sets : ".join(', ', $log->newSetFiles)."\n" ;
			if ( property_exists($log, 'updatedSetFiles') )
				echo "Updated sets : ".join(', ', $log->updatedSetFiles)."\n" ;
			$local_version = $log->version ;
			echo "\n" ;
		}
	}
	echo "end\n" ;
}
