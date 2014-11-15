<?php
// Parent class for all interface - not game - handlers (index, tournament index build draft, admin)
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class ParentHandler extends WebSocketUriHandler {
	public $observer = null ;
	protected $debug = false ;
	public function __construct($logger, $observer) {
		parent::__construct($logger) ;
		$this->observer = $observer ;
	}
	public function say($msg) {
		$this->observer->say($msg) ;
	}
	public function onMessage(WebSocketTransportInterface $user, WebSocketMessageInterface $msg){
		$data = json_decode($msg->getData()) ;
		if ( $data == null ) {
			$this->say('Unparsable JSON : '.$msg->getData()) ;
			return false ;
		}
		if ( ! isset($data->type) ) {
			$this->say('No type given') ;
			return false ;
		}
		if ( $this->debug )
			$this->say(' <- '.$data->type) ;
		switch ( $data->type ) {
			case 'ping' :
				$user->sendString($msg->getData()) ;
				break ;
			case 'register' :
				if ( isset($data->player_id) && isset($data->nick) ) {
					$user->player_id = $data->player_id ;
					$user->nick = $data->nick ;
					$this->register_user($user, $data) ;
				} else {
					$this->say('Incomplete registration : '.$data->player_id.' / '.$data->nick) ;
					$user->close();
				}
				break ;
			default :
				if ( isset($user->player_id) )
					$this->recieve($user, $data) ;
				else
					$this->say('Action '.$data->type.' from unregistered user') ;
		}
	}
}
