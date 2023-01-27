<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class BuildHandler extends LimitedHandler {
	public function register_user(WebSocketTransportInterface $user, $data) {
		if ( $this->limited_register($user, $data) ) {
			$deck = $user->player->get_deck() ;
			$deck_json = json_encode($deck) ;
			if ( $deck_json === false ) {
				foreach ( $deck->side as $card ) {
					$json = json_encode($card) ;
					if ( $json === false ) {
						 $this->say("Card text of [".$card->name."] can't be JSONized : ".json_last_error_msg()) ;
					}
				}
			}
			$user->sendString($deck_json) ;
			// Send keywords
			$keywords = new stdClass() ;
			$keywords->type = 'keywords' ;
			$keywords->keywords = $user->tournament->keywords() ;
			$keywords->base = $this->observer->keywords ;
			$user->sendString(json_encode($keywords)) ;
			// Send infos about extensions in sealed
			$lands = new stdClass() ;
			$lands->type = 'lands' ;
			$lands->exts = array() ;
			$exts = array_unique($user->tournament->data->boosters) ;
			foreach ( $exts as $extse ) {
				$ext = Extension::get($extse) ;
				$result = new stdClass() ;
				$result->se = $extse ;
				$result->name = $ext->name ;
				$result->nb = $ext->basics ;
				$lands->exts[] = $result ;
			}
			$user->sendString(json_encode($lands)) ;
		}
	}
	public function recieved(WebSocketTransportInterface $user, $data) {
		if ( $user->follow !== null ) {
			return false ;
		}
		switch ( $data->type ) {
			case 'add' :
				$user->player->add($data->card, $data->nb) ;
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
