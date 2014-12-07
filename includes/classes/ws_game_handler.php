<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class GameHandler extends WebSocketUriHandler {
	public $observer = null ;
	public $users_fields = array('player_id', 'nick', 'game', 'focused') ;
	public function __construct($logger, $observer) {
		parent::__construct($logger) ;
		$this->observer = $observer ;
	}
	public function list_users() {
		$result = new stdClass() ;
		$result->type = 'userlist' ;
		$result->users = array() ;
		foreach ( $this->getConnections() as $cnx ) {
			if ( ! isset($cnx->player_id) )
				continue ;
			foreach ( $result->users as $user )
				if ( $user->player_id == $cnx->player_id )
					continue 2 ;
			$obj = new stdClass() ;
			foreach ( $this->users_fields as $field )
				$obj->{$field} = $cnx->{$field} ;
			$result->users[] = $obj ;
		}
		return $result ;
	}
	public function onMessage(WebSocketTransportInterface $user, WebSocketMessageInterface $msg){
		$strdata = $msg->getData() ;
		$data = json_decode($strdata) ;
		if ( $data != null ) 
			return $this->manage_message($user, $data) ;
		$this->observer->say('Unparsable JSON : '.$strdata) ;
		if ( preg_match('/\}(.)\{/', $strdata, $matches) ) {
			$parts = preg_split ('}.{', $strdata) ;
			$this->observer->say('bug char : '.ord($matches[1]).', cut in '.count($parts)) ;
			//$parts = explode('}'.chr($bugchar).'{', $strdata) ;
			foreach ( $parts as $i => $part ) {
				if ( $i == 0 )
					$part .= '}' ;
				elseif ( $i == count($parts) - 1 )
					$part = '{'.$part ;
				else
					 $part = '{'.$part.'}' ;
				$data = json_decode($part) ;
				if ( $data != null )
					$this->manage_message($user, $data) ;
			}
		}
	}
	private function manage_message($user, $data) {
		if ( ! isset($data->type) ) {
			$this->observer->say('No type given') ;
			return false ;
		}
		// Refuse messages other than 'registration' from unregistered connexions
		if ( ( $data->type != 'register' ) && !isset($user->game) ) {
			$this->observer->say("{$data->type} from unregistered user") ;
			$user->close() ;
			return false ;
		}
		switch ( $data->type ) {
			case 'ping' :
				$data->type = 'pong' ;
				$user->sendString(json_encode($data)) ;
				break ;
			case 'register' :
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
				break ;
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
	public function displayed($game, $tournament_id=0) { // Does the game needs to be displayed on index
		foreach ( $this->getConnections() as $user )
			if (
				isset($user->game)
				&& ( $user->game->tournament == $tournament_id ) // Not a tournament game
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
		if ( ! $this->connected($user->player_id, $user->game) ) { // Last tab open on this page
			// Send disconnection to game
			$this->broadcast('{"type": "unregister", "sender": "'.$user->player_id.'"}', $user->game) ;
			// Update player status
			$field = $user->game->which($user->player_id) ;
			if ( $field != '' ) {
				$field .= '_status' ;
				if ( $user->game->{$field} > 0 )
					$user->game->{$field} = 0 ;
			}
			// Update / remove from index
			if ( ! $this->displayed($user->game) ) 
				$this->observer->index->broadcast('{"type": "duelcancel", "id": "'.$user->game->id.'"}');
			else if ( $field != '' )
				$this->observer->index->broadcast(json_encode($user->game)) ;
			// Update tournament player status
			if ( $user->game->tournament > 0 ) {
				$tournament = Tournament::get($user->game->tournament) ;
				$tournament->player_disconnect($user->player_id, 'game_'.$user->game->id) ;
				if ( ! $this->displayed($user->game, $user->game->tournament) ) {
					$creator = $tournament->get_player($user->game->creator_id) ;
					$joiner = $tournament->get_player($user->game->joiner_id) ;
					$creator->set_ready(true) ;
					$joiner->set_ready(true) ;
				}
			}
		}
	}
}
