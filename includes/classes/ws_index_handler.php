<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class IndexHandler extends ParentHandler {
	protected $shouts = array() ;
	protected $nbshouts = 50 ; // Max number of shouts to keep in cache
	public $users_fields = array('player_id', 'nick', 'inactive', 'typing') ;
	public function __construct($logger, $observer, $type) {
		parent::__construct($logger, $observer, $type) ;
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
	public function register_user(WebSocketTransportInterface $user, $data) {
		$user->inactive = false ;
		$user->typing = false ;
		// Update clients list on all clients
		$this->broadcast(json_encode($this->list_users())) ;
		// Send shouts
		foreach ( $this->shouts as $shout ) {
			$user->sendString(json_encode($shout)) ;
		}
		// Send duels
		foreach ( $this->observer->pending_duels as $duel ) {
			$user->sendString(json_encode($duel)) ;
		}
		foreach ( $this->observer->joined_duels as $duel ) {
			if ( $this->observer->game->displayed($duel) ) {
				$user->sendString(json_encode($duel)) ;
			}
		}
		// Send tournaments
		foreach ( $this->observer->pending_tournaments as $tournament ) {
			$user->sendString(json_encode($tournament)) ;
		}
		foreach ( $this->observer->running_tournaments as $tournament ) {
			if ( $tournament->min_players > 1 ) {
				$user->sendString(json_encode($tournament)) ;
			}
		}
		$suggest = new stdClass ;
		$suggest->type = 'suggest' ;
		$suggest->draft = $this->observer->suggest_draft ;
		$suggest->sealed = $this->observer->suggest_sealed ;
		$user->sendString(json_encode($suggest)) ;
		// Send extensions - keep last step
		$exts = new stdClass ;
		$exts->type = 'extensions' ;
		$exts->data = Extension::$cache ;
		$user->sendString(json_encode($exts)) ;
	}
	public function recieve(WebSocketTransportInterface $user, $data) {
		switch ( $data->type ) {
			// Shout
			case 'shout' :
				global $db ;
				$nick = $db->escape($user->nick) ;
				$message = $db->escape($data->message) ;
				$db->insert("INSERT
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
					// Send first one time to both players
					$duel->redirect = true ;
					$json = json_encode($duel) ;
					$this->send_first($json, $duel->creator_id) ;
					$this->send_first($json, $duel->joiner_id) ;
					// Then broadcast to inform other users game was joined
					$duel->redirect = false ;
					$this->broadcast(json_encode($duel)) ;
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
				foreach ( $this->observer->pending_tournaments as $tournament ) {
					if ( $tournament->id == $data->id ) { // Wanted tournament
						if ( $tournament->registered($user) !== false ) {
							$tournament->unregister($user) ;
						} else {
							$tournament->register($data, $user) ;
						}
					}
				}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
	// Send a message to each registered users other than the one in parameter
	public function broadcast($msg, WebSocketTransportInterface $sender = null) {
		foreach ( $this->getConnections() as $user )
			if ( $user != $sender )
				$user->sendString($msg) ;
	}
	// Send a message to first connected user with given player_id
	public function send_first($msg, $player_id) {
		foreach ( $this->getConnections() as $user )
			if ( isset($user->player_id) && ( $user->player_id == $player_id ) ) {
				$user->sendString($msg) ;
				return true ;
			}
		return false ;
	}
	public function onDisconnect(WebSocketTransportInterface $user) {
		if ( isset($user->player_id) ) {
			foreach ( $this->observer->clean_duels($user->player_id) as $duelid )
				$this->broadcast('{"type": "duelcancel", "id": "'.$duelid.'"}') ;
			foreach ( $this->observer->pending_tournaments as $tournament )
				if ( $tournament->registered($user) !== false )
					$tournament->unregister($user, $this) ;
			$this->broadcast(json_encode($this->list_users())) ;
		}
	}
}
