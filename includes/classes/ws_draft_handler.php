<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class DraftHandler extends LimitedHandler {
	public function register_user(WebSocketTransportInterface $user, $data) {
		$tournament = Tournament::get($data->tournament) ;
		if ( $tournament == null ) {
			$user->sendString('{"msg": "Tournament not exist"}') ;
			$this->say('Tournament '.$data->tournament.' not exist') ;
			return false ;
		}
		// Register itself
		$user->tournament = $tournament ;
		$player = $tournament->get_player($user->player_id) ;
		if ( $player == null )
			$this->observer->say('Player '.$user->player_id.' not found in draft') ;
		else {
			$player->connect($this->type) ;
			$user->player = $player ;
			if ( $tournament->status == 3 ) {
				$booster = $tournament->get_booster($player->order) ;
				if ( $booster == null )
					$this->observer->say("Booster not found t {$this->tournament->id}"
					." p {$this->player->order} n {$this->tournament->round}") ;
				else
					$user->sendString(json_encode($booster)) ;
			}
			$user->sendString(json_encode($tournament)) ;
			$user->sendString(json_encode($player->get_deck())) ;
		}
	}
	public function recieved(WebSocketTransportInterface $user, $data) {
		switch ( $data->type ) {
			case 'pick' :
				$booster = $user->tournament->get_booster($user->player->order) ;
				if ( ( $booster != null ) && property_exists($data, 'pick') )
					$booster->set_pick($data->pick, $data->main) ;
				if ( property_exists($data, 'ready') ) {
					$user->player->set_ready($data->ready) ;
					$user->tournament->send('tournament', 'build', 'draft') ;
					$this->observer->build->broadcast_following($user->player) ;
				}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
}
