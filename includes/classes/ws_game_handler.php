<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class GameHandler extends ParentHandler {
	public $users_fields = array('player_id', 'nick', 'game', 'focused') ;
	protected function register_user($user, $data) {
		// Search game
		$game = $this->observer->joined_duel($data->id) ;
		if ( $game === false )
			return $this->observer->say('game '.$data->id.' not registered') ;
		// Register user to handler
		$user->game = $game ;
		$user->player_id = $data->player_id ;
		$user->nick = $data->nick ;
		$user->focused = true ;
		// Register user to game
		if ( $game->setUser($user, $this) ) {
			$field = $game->which($user->player_id) ;
			$field .= '_status' ;
			if ( $game->{$field} < 1 )
				$game->{$field} = 1 ;
		}
		// Join an empty game, make it appear on index
		if ( $this->displayed($game) )
			$this->observer->index->broadcast(json_encode($game)) ;
		$data->sender = $data->player_id ;
		$this->broadcast(json_encode($data), $game, $user) ;
		// Send game data to user
			// Tokens
		foreach ( $this->observer->tokens as $ext => $tokens)
			$user->sendString('{"type":"tokens", "sender": "", "ext": "'.$ext.'", '.
				'"param":'.json_encode($tokens).'}') ;
			// Actions
		$from = null ;
		if ( isset($data->from) )
			$from = $data->from ;
		foreach ( $game->getActions($from) as $action )
			$user->sendString(json_encode($action)) ;
			// Connected users
		foreach ( $this->getConnections() as $connected )
			if (
				isset($connected->game)
				&& ( $connected->game->id == $game->id )
				//&& ( $connected != $user )
				) {
				$user->sendString('{"type":"register", "sender":"'.$connected->player_id.'"}') ;
				if ( ! $connected->focused )
					$user->sendString('{"type":"blur", "sender":"'.$connected->player_id.'"}') ;
			}
		if ( $game->tournament > 0 ) {
			$tournament = Tournament::get($game->tournament) ;
			if ( $tournament != null )
				$tournament->player_connect($user->player_id, 'game_'.$game->id) ;
		}
	}
	protected function recieve($user, $data) {
		switch ( $data->type ) {
			case 'recieve' :
				$user->game->recieveAction($data->id) ;
				break ;
			case 'mojosto' :
				switch ( $user->player_id ) {
					case $user->game->creator_id :
						$zone = 'game.creator.battlefield' ;
						break ;
					case $user->game->joiner_id :
						$zone = 'game.joiner.battlefield' ;
						break ;
					default :
						die('Player not creator nor joiner') ;
				}
				$param = json_decode($data->param) ;
				$target = isset($param->target)?$param->target:null ;
				$cards = mojosto($param->avatar, $param->cc) ;
				if ( count($cards) == 0 )
					$user->sendString('{"type": "msg", "sender": "", "param": {"text": "'.
						$param->avatar.' can\'t cast anything with cc='.$param->cc.'"}}') ;
				else
					foreach ( $cards as $card ) {
						$card->zone = $zone ;
						if ( ( $param->avatar == 'stonehewer' ) && ( $target != null ) )
							$card->target = $target ;
						$action = $user->game->addAction($user->player_id, 'mojosto', json_encode($card)) ;
						$this->broadcast(json_encode($action), $user->game) ;
					}
				break ;
			case 'focus' :
			case 'blur' :
				$user->focused = ( $data->type == 'focus' ) ;
				$data->sender = $user->player_id ;
				$this->broadcast(json_encode($data), $user->game, $user) ;
				break ;
			case 'land' :
				$param = json_decode($data->param) ;
				$land = Card::get($param->name) ;
				if ( $user->game->isCreator($user->player_id) )
					$zone = 'game.creator.library' ;
				else if ( $user->game->isJoiner($user->player_id) )
					$zone = 'game.joiner.library' ;
				else {
					$this->observer->say('Land : nor creator nor joiner') ;
					return false ;
				}
				$land->zone = $zone ;
				$action = $user->game->addAction($user->player_id, 'card', json_encode($land)) ;
				$this->broadcast(json_encode($action), $user->game) ;
				break ;
			default :
				$action = $user->game->addAction($user->player_id, $data->type,
					$data->param, $data->local_index) ;
				if ( $action == null )
					$user->sendString('{"type": "msg", "sender": "", "param": {"text": "You can\'t send '.$data->type.'"}}') ;
				else {
					// Send back to sender if containing a callback ID
					if ( isset($data->callback) )
						$action->callback = $data->callback ;
					$this->broadcast(json_encode($action), $user->game,
						isset($action->callback)?null:$user) ;
				}
		}
	}
	public function displayed($game) { // Does the game needs to be displayed on index
		foreach ( $this->getConnections() as $user )
			if (
				isset($user->game)
				&& ( $user->game->tournament == 0 ) // Not a tournament game
				&& ( $user->game->id == $game->id ) // At least one user connected
				&& ( $game->creator_id != $game->joiner_id ) // Not goldfish
				&& (
					( $user->player_id == $game->creator_id ) // Connected user is a player
					|| ( $user->player_id == $game->joiner_id )
				)
			)
				return true ;
		return false ;
	}
	public function connected($id, $game) {
		foreach ( $this->getConnections() as $user )
			if (
				isset($user->game)
				&& ( $user->game->id == $game->id )
				&& ( $user->player_id == $id )
			)
				return true ;
		return false ;
	}
	// Send a message to each user registered to $game, other than $sender
	public function broadcast($msg, $game = null, WebSocketTransportInterface $sender = null) {
		foreach ( $this->getConnections() as $user )
			if (
				isset($user->game)
				&& ( $user->game->id == $game->id )
				&& ( $user != $sender )
			)
				$user->sendString($msg) ;
	}
	public function onDisconnect(WebSocketTransportInterface $user) {
		if ( ! isset($user->player_id) ) { // Unregistered user
			$this->observer->say('Disconnection from unregistered user') ;
			return false ;
		}
		if ( ! $this->connected($user->player_id, $user->game) ) { // Last connexion on this game from that user
			// Send disconnection to game
			$this->broadcast('{"type": "unregister", "sender": "'.$user->player_id.'"}', $user->game) ;
			// Update player status
			$field = $user->game->which($user->player_id) ;
			if ( $field != '' ) {
				$field .= '_status' ;
				if ( $user->game->{$field} > 0 )
					$user->game->{$field} = 0 ;
			}
			// Update tournament player status
			if ( $user->game->tournament > 0 ) {
				$tournament = Tournament::get($user->game->tournament) ;
				$tournament->player_disconnect($user->player_id, 'game_'.$user->game->id) ;
			} else {
					// Update / remove from index
				if ( ! $this->displayed($user->game) ) 
					$this->observer->index->broadcast('{"type": "duelcancel", "id": "'.$user->game->id.'"}');
				else if ( $field != '' )
					$this->observer->index->broadcast(json_encode($user->game)) ;
			}
		}
	}
}
