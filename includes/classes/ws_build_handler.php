<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class BuildHandler extends LimitedHandler {
	public function register_user(WebSocketTransportInterface $user, $data) {
		$tournament = Tournament::get($data->tournament) ;
		if ( $tournament == null ) {
			$user->sendString('{"msg": "Tournament not exist"}') ;
			$this->say('Tournament '.$data->tournament.' not exist') ;
			return false ;
		}
		$user->tournament = $tournament ;
		$user->follow = null ;
		if ( property_exists($data, 'follow') ) {
			$pid = $data->follow ;
			$user->follow = $user->player_id ;
		} else
			$pid = $user->player_id ;
		$user->sendString(json_encode($tournament)) ;
		$player = $tournament->get_player($pid) ;
		if ( $player == null )
			$this->observer->say('Player '.$user->player_id.' not found in build') ;
		else {
			$player->connect($this->type) ;
			$user->player = $player ;
			$user->sendString(json_encode($player->get_deck())) ;
		}
	}
	public function broadcast_following($player) {
		foreach ( $this->getConnections() as $user )
			if (
				isset($user->tournament)
				&& ( $user->tournament->id == $player->get_tournament_id() )
				&& isset($user->follow)
				&& ( $user->follow != null )
				&& ( $user->player == $player )
			)
				$user->sendString(json_encode($player->get_deck())) ;
	}
	public function recieved(WebSocketTransportInterface $user, $data) {
		if ( $user->follow != null )
			return false ;
		switch ( $data->type ) {
			case 'add' :
				$user->player->add($data->cardname, $data->nb) ;
				$user->tournament->send() ;
				$this->broadcast_following($user->player) ;
				break ;
			case 'remove' :
				$user->player->remove($data->cardname) ;
				$user->tournament->send() ;
				$this->broadcast_following($user->player) ;
				break ;
			case 'toggle' :
				if ( $user->player->toggle($data->cardname, $data->from) ) {
					$user->tournament->send() ;
					$this->broadcast_following($user->player) ;
				} else
					$user->sendString('{"msg": "'.$data->cardname.' not found in '.$data->from.'"}') ;
				break ;
			case 'ready' :
				if ( ( $user->tournament->status == 4 ) && property_exists($data, 'ready') ) {
					if ( ( $data->ready ) && ( $user->player->deck_cards < 40 ) )
						 $user->sendString('{"msg": "You only have '.$user->player->deck_cards
						 	.' cards in your deck"}') ;
					else  {
						$user->player->set_ready($data->ready) ;
						$user->tournament->send() ;
					}
				}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
}
