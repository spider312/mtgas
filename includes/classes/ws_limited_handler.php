<?php
// Parent for all limited tournament related handlers (draft, build)
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class LimitedHandler extends TournamentHandler {
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
				if ( $this->debug ) {
					$obj = json_decode($msg) ;
					$this->say(' -> '.$obj->type) ;
				}
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
