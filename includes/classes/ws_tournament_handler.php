<?php
// Parent for all handlers relative to a tournament (index, draft, build)
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class TournamentHandler extends ParentHandler {
	// Send a message to each registered users other than the one in parameter
	public function broadcast($tournament, $msg, WebSocketTransportInterface $sender = null) {
		foreach ( $this->getConnections() as $user )
			if (
				isset($user->tournament)
				&& ( $user->tournament->id == $tournament->id )
				&& ( $user != $sender )
			) {
				$user->sendString($msg) ;
				if ( $this->debug ) {
					$obj = json_decode($msg) ;
					$this->observer->say(' -> '.$obj->type) ;
				}
			}
	}
	public function recieve(WebSocketTransportInterface $user, $data) {
		switch ( $data->type ) {
			case 'msg' :
				$user->tournament->message($user->player_id, $data->msg) ;
				$user->tournament->send() ;
				break ;
			case 'allow' :
				$user->tournament->log($user->player_id, 'allow', $data->value) ;
				$user->tournament->send() ;
				break ;
			default :
				$this->recieved($user, $data) ;
		}
	}

}
