<?php
// Parent for all limited tournament related handlers (draft, build)
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class LimitedHandler extends TournamentHandler {
	/*
	Common code between draft and build : search tournament and manage follower
	*/
	public function limited_register(WebSocketTransportInterface $user, $data) {
		// Link to tournament
		$tournament = Tournament::get($data->tournament) ;
		if ( $tournament === null ) {
			$user->sendString('{"msg": "Tournament not exist"}') ;
			$this->say('Tournament '.$data->tournament.' not exist') ;
			return false ;
		}
		$user->tournament = $tournament ;
		$user->sendString(json_encode($tournament)) ;
		// Manage follower
		$user->follow = null ;
		if ( property_exists($data, 'follow') ) {
			$pid = $data->follow ;
			$user->follow = $data->follow ;
		} else {
			$pid = $user->player_id ;
		}
		// Link to player
		$player = $tournament->get_player($pid) ;
		if ( $player === null ) {
			$this->observer->say('Player '.$user->player_id.' not found in limited handler '.$tournament->id) ;
			return false ;
		}
		// Inited
		$player->connect($this->type) ; // Connection indicator, broadcasts tournament if changed
		$user->player = $player ;
		return true ;
	}
	/*
	Broadcasts player deck to each follower of player
	*/
	public function broadcast_following($player, $msg=null) {
		foreach ( $this->getConnections() as $user )
			if (
				isset($user->tournament)
				&& ( $user->tournament->id === $player->get_tournament_id() )
				&& isset($user->follow)
				&& ( $user->follow !== null )
				&& ( $user->player === $player )
			) {
				if ( $msg === null ) {
					$msg = json_encode($player->get_deck()) ;
				}
				$user->sendString($msg) ;
			}
	}
	// Send a message to each registered users other than the one in parameter
	public function broadcast_player($tournament, $player, $msg, $sender = null) {
		foreach ( $this->getConnections() as $user )
			if (
				isset($user->tournament)
				&& ( $user->tournament->id == $tournament->id )
				&& isset($user->player)
				&& ( $user->player->player_id == $player->player_id )
				&& ( $user != $sender )
			) {
				$user->sendString($msg) ;
			}
	}
	// Is the user already connected to this handler ?
	public function is_connected($tournament, $player_id) {
		foreach ( $this->getConnections() as $user )
			if ( ( $user->tournament->id == $tournament->id )
				&& ( $user->player->player_id == $player_id ) )
				return true ;
		return false ;
	}
}
