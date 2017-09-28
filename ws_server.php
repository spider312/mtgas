#!/php -q
<?php
// Manage process ident/unicity
$pidfile = __DIR__.'/mtgas.pid' ;
	// Get currently running
$pid = false ; // Result for file_get_contents in case of error
if ( file_exists($pidfile) ) {
	$pid = file_get_contents($pidfile);
}
	// Check if it runs
if ( ( $pid !== false ) && file_exists('/proc/'.$pid) ) {
	exit('Already running under pid '.$pid."\n") ;
}
	// Set new currently running
if ( file_put_contents($pidfile, posix_getpid()) === false ) {
	exit('Unable to write pidfile '.$pidfile);
}
// Signal catching
declare(ticks = 1) ; // Required for pcntl_signal
pcntl_signal(SIGTERM, "signal_handler") ;
pcntl_signal(SIGINT, "signal_handler") ;
function signal_handler($signo, $siginfo=null) {
	switch($signo) {
		case SIGTERM:
			print "Caught SIGTERM\n" ;
			break ;
		case SIGINT:
			print "Caught SIGINT\n" ;
			break ;
		default:
			print "Caught signal $signo\n ";
	}
	global $pidfile ;
	unlink($pidfile) ;
	exit($signo);
}
// WS Lib
require_once('vendor/autoload.php') ;
use Devristo\Phpws\Server\WebSocketServer ;
// Custom server
require_once('includes/classes/ws_gameserver.php') ;
$gameserver = new GameServer($wsport) ;
$gameserver->import() ; // Will need global object $gameserver
$gameserver->start() ;
