<?php
require_once(__DIR__ . '/../lib.php') ;
require_once(__DIR__ . '/../vendor/autoload.php');
$loop = \React\EventLoop\Factory::create();
$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);
$client = new \Devristo\Phpws\Client\WebSocket("ws://127.0.0.1:$wsport/admin/", $loop, $logger);
$client->on("connect", function() use ($logger, $client) {
	$client->send('{"type": "register", "player_id": "a", "nick": "a"}');
});
$client->on("message", function($message) use ($client, $logger) {
	$client->close() ; // No need to stay connected to this handler
	$json = json_decode($message->getData()) ; // Registering to admin sends an "overall" JSON string
	echo count($json->handlers->index->users)."\n";
	// Sum handlers dedicated to gaming
	$players = 0 ;
	foreach ( array('draft', 'build', 'game') as $handler ) $players += count($json->handlers->{$handler}->users) ;
	echo $players."\n" ;
});
$client->open() ;
$loop->run();
