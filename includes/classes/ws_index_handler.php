<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class IndexHandler extends ParentHandler {
	protected $shouts = array() ;
	protected $nbshouts = 50 ; // Max number of shouts to keep in cache
	public function __construct($logger, $observer) {
		parent::__construct($logger, $observer) ;
		global $db ;
		// Import shouts
		$this->shouts = array_reverse($db->select("SELECT
			`sender_id` AS `player_id`,
			`sender_nick` AS `player_nick`,
			`time`,
			`message`,
			'shout' AS `type`
			FROM `shout`
			ORDER BY `id` DESC
			LIMIT {$this->nbshouts}")) ;
	}
	private function list_users() {
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
			$obj->player_id = $cnx->player_id ;
			$obj->nick = $cnx->nick ;
			$obj->inactive = $cnx->inactive ;
			$obj->typing = $cnx->typing ;
			$result->users[] = $obj ;
		}
		return $result ;
	}
	public function register_user(WebSocketTransportInterface $user, $data) {
		$user->inactive = false ;
		$user->typing = false ;
		// Update clients list on all clients
		$this->broadcast(json_encode($this->list_users())) ;
		// Send shouts
		foreach ( $this->shouts as $shout )
			$user->sendString(json_encode($shout)) ;
		// Send duels
		foreach ( $this->observer->pending_duels as $duel )
			$user->sendString(json_encode($duel)) ;
		foreach ( $this->observer->joined_duels as $duel )
			if ( $this->observer->game->displayed($duel) )
				$user->sendString(json_encode($duel)) ;
		// Send tournaments
		foreach ( $this->observer->pending_tournaments as $tournament )
			$user->sendString(json_encode($tournament)) ;
		foreach ( $this->observer->running_tournaments as $tournament )
			$user->sendString(json_encode($tournament)) ;
	}
	public function recieve(WebSocketTransportInterface $user, $data) {
		switch ( $data->type ) {
			// Shout
			case 'shout' :
				global $db ;
				$nick = $db->escape($user->nick) ;
				$message = $db->escape($data->message) ;
				$db->query("INSERT
					INTO `shout` (`sender_id`, `sender_nick`, `message`)
					VALUES ('{$user->player_id}', '$nick', '$message')") ;
				$data->player_id = $user->player_id ;
				$data->player_nick = $user->nick ;
				$data->time = now() ;
				$this->shouts[] = $data ;
				if ( count($this->shouts) > $this->nbshouts )
					array_shift($this->shouts) ;
				$this->broadcast(json_encode($data)) ;
				break ;
			case 'blur' :
				$user->inactive = true ;
				$this->broadcast(json_encode($this->list_users())) ;
				break ;
			case 'focus' :
				$user->inactive = false ;
				$this->broadcast(json_encode($this->list_users())) ;
				break ;
			case 'keypress' :
				$user->typing = true ;
				$this->broadcast(json_encode($this->list_users())) ;
				break ;
			case 'keyup' :
				$user->typing = false ;
				$this->broadcast(json_encode($this->list_users())) ;
				break ;
			// Duels
			case 'pendingduel' :
				$data->creator_id = $user->player_id ;
				$duel = new Game($data) ;
				$this->observer->pending_duels[] = $duel ;
				$this->broadcast(json_encode($duel)) ;
				break ;
			case 'joineduel' :
				$duel_index = $this->observer->pending_duel($data->id) ;
				if ( $duel_index === false ) {
					$this->say('Pending duel '.$data->id.' not found') ;
					break ;
				}
				$splduels = array_splice($this->observer->pending_duels, $duel_index, 1) ;
				$duel = $splduels[0] ;
				if ( $duel->creator_id == $user->player_id )
					$this->broadcast('{"type": "duelcancel", "id": "'.$data->id.'"}');
				else {
					$data->joiner_id = $user->player_id ;
					$duel = $duel->join($data) ;
					$duel->type = 'joineduel' ;
					$duel->redirect = true ;
					$this->broadcast(json_encode($duel)) ;
					$duel->redirect = false ;
					$this->observer->joined_duels[] = $duel ;
				}
				break ;
			case 'goldfish' :
				$data->type = 'joineduel' ;
				$data->name = 'Goldfish' ;
				$data->creator_id = $user->player_id ;
				$data->joiner_id = $user->player_id ;
				$duel = new Game($data) ;
				$duel->join($data) ;
				$duel->redirect = true ;
				$user->sendString(json_encode($duel)) ;
				$duel->redirect = false ;
				$this->observer->joined_duels[] = $duel ;
				break ;
			// Tournament
			case 'pending_tournament' :
				foreach ( $this->observer->pending_tournaments as $tournament )
					if ( $tournament->registered($user) !== false )
						$tournament->unregister($user) ;
				$data->type = 'pending_tournament' ;
				$tournament = Tournament::create($data, $user) ;
				if ( is_string($tournament) )
					$user->sendString('{"type": "msg", "msg": "'.$tournament.'"}') ;
				else {
					$tournament->type = $data->type ;
					$this->observer->pending_tournaments[] = $tournament ;
					$data->player_id = $user->player_id ;
					$tournament->register($data, $user) ;
				}
				break ;
			case 'tournament_register' :
				$data->player_id = $user->player_id ;
				foreach ( $this->observer->pending_tournaments as $tournament )
					if ( $tournament->registered($user) !== false )
						$tournament->unregister($user) ;
					else { // Client not registered
						if ( $tournament->id == $data->id ) // Wanted tournament
							$tournament->register($data, $user) ;
					}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
	public function onDisconnect(WebSocketTransportInterface $user) {
		if ( isset($user->player_id) ) {
			foreach ( $this->observer->clean_duels($user->player_id) as $duelid )
				$this->broadcast('{"type": "duelcancel", "id": "'.$duelid.'"}') ;
			foreach ( $this->observer->pending_tournaments as $tournament )
				if ( $tournament->registered($user) !== false )
					$tournament->unregister($user, $this) ;
			//$this->broadcast('{"type": "unregister", "player_id": "'.$user->player_id.'"}') ;
			$this->broadcast(json_encode($this->list_users())) ;
		} else
			$this->say('Disconection from unregistered user') ;
	}
	// Send a message to each registered users other than the one in parameter
	public function broadcast($msg, WebSocketTransportInterface $sender = null) {
		foreach ( $this->getConnections() as $user )
			if ( $user != $sender ) {
				$user->sendString($msg) ;
				if ( $this->debug ) {
					$obj = json_decode($msg) ;
					$this->say(' -> '.$obj->type) ;
				}
			}
	}
}
