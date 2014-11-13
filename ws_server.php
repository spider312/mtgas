#!/php -q
<?php
// Signal management
declare(ticks = 1);
pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT, "signal_handler");
function signal_handler($signal) {
	switch($signal) {
		case SIGTERM:
			print "Caught SIGTERM\n" ;
			exit;
		case SIGINT:
			print "Caught SIGINT\n" ;
			exit;
		default:
			print "Caught signal $signal\n ";
			exit;
	}
}
// WS Lib
require_once('vendor/autoload.php') ;
use Devristo\Phpws\Server\WebSocketServer ;
// Custom server
require_once('includes/classes/ws_gameserver.php') ;
$gameserver = new GameServer($wsport) ;
$gameserver->import() ; // Will need global object $gameserver
$gameserver->start() ;
