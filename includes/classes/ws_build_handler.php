<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class BuildHandler extends LimitedHandler {
	public function register_user(WebSocketTransportInterface $user, $data) {
		if ( $this->limited_register($user, $data) ) {
			$user->sendString(json_encode($user->player->get_deck())) ;
		}
	}
	public function recieved(WebSocketTransportInterface $user, $data) {
		if ( $user->follow !== null ) {
			return false ;
		}
		switch ( $data->type ) {
			case 'add' :
				$user->player->add($data->cardname, $data->nb) ;
				$user->tournament->send('tournament', 'build') ;
				$this->broadcast_following($user->player) ;
				break ;
			case 'remove' :
				$user->player->remove($data->cardname) ;
				$user->tournament->send('tournament', 'build') ;
				$this->broadcast_following($user->player) ;
				break ;
			case 'toggle' :
				if ( $user->player->toggle($data->cardname, $data->from) ) {
					$user->tournament->send('tournament', 'build') ;
					$this->broadcast_following($user->player) ;
				} else {
					$user->sendString('{"msg": "'.$data->cardname.' not found in '.$data->from.'"}') ;
				}
				break ;
			case 'ready' :
				if ( ( $user->tournament->status == 4 ) && property_exists($data, 'ready') ) {
					if ( ( $data->ready ) && ( $user->player->deck_cards < 40 ) ) {
						 $user->sendString('{"msg": "You only have '.$user->player->deck_cards
						 	.' cards in your deck"}') ;
					} else  {
						$user->player->set_ready($data->ready) ;
						$user->tournament->send('tournament', 'build') ;
					}
				}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
}
