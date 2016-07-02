<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class ImportHandler extends ParentHandler {
	public function register_user(WebSocketTransportInterface $user, $data) {
	}
	public function recieve(WebSocketTransportInterface $user, $data) {
		switch ( $data->type ) {
			case 'import' :
				$source = $data->source ;
				$ext_source = $data->ext_source ;
				$importer_path = 'admin/cards/import_ws/importer/'.$source.'.php' ;
				if ( file_exists($importer_path) ) {
					include_once 'admin/cards/import_ws/lib.php' ;
					$importer = new ImportExtension() ;
					//$importer->handler = $this ;
					include $importer_path ;
					$importer->validated = $importer->validate() ;
					$importer->imported = $importer->import($importer->code, false) ;
					$importer->type = 'downloaded_extension' ;
					$this->broadcast(JSON_encode($importer)) ;
				} else {
					$user->sendString('{"type": "msg", "value": "No importer for '.$source.'"}') ;
				}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
	// Send a message to each registered users other than the one in parameter
	public function broadcast($msg, WebSocketTransportInterface $sender = null) {
		foreach ( $this->getConnections() as $user )
			if ( $user != $sender )
				$user->sendString($msg) ;
	}
}
