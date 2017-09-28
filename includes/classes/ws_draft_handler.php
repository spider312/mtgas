<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class DraftHandler extends LimitedHandler {
	public function register_user(WebSocketTransportInterface $user, $data) {
		if ( $this->limited_register($user, $data) ) {
			$user->sendString(json_encode($user->tournament)) ;
			$user->sendString(json_encode($user->player->get_deck())) ;
			$booster = $user->tournament->get_booster($user->player->order) ;
			if ( $booster !== null ) {
				$user->sendString(json_encode($booster)) ;
			}
		}
	}
	public function recieved(WebSocketTransportInterface $user, $data) {
		if ( $user->follow !== null )
			return false ;
		switch ( $data->type ) {
			case 'pick' :
				$booster = $user->tournament->get_booster($user->player->order) ;
				if ( ( $booster != null ) && property_exists($data, 'pick') )
					$booster->set_pick($data->pick, $data->main) ;
				if ( property_exists($data, 'ready') ) {
					$user->player->set_ready($data->ready) ;
					$user->tournament->send('tournament', 'draft') ;
					$this->broadcast_following($user->player, json_encode($booster)) ;
				}
				break ;
			case 'reorder' :
				$user->player->reorder($data->pool, $data->from, $data->to) ;
				$this->broadcast_following($user->player) ;
				break ;
			case 'toggle' :
				if ( $user->player->toggle($data->card, $data->from, $data->to) ) {
					$user->sendString(json_encode($user->player->get_deck())) ;
					$user->tournament->send('tournament', 'draft') ;
					$this->broadcast_following($user->player) ;
				} else {
					$user->sendString('{"msg": "'.$data->cardname.' not found in '.$data->from.'"}') ;
				}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
}
