<?php 
class Game {
	private $fields = array(
		'id', 'status', 'creation_date', 'last_update_date', 'name', 'tournament', 'round',
		'creator_nick', 'creator_id', 'creator_avatar',
		'creator_deck', 'creator_score', 'creator_lastacti',
		'joiner_nick', 'joiner_id', 'joiner_avatar',
		'joiner_deck', 'joiner_score', 'joiner_lastacti'
	) ;
	private $actions = array() ;
	public $spectators = null ;
	private $tournament_obj = null ;
	static $cache = array() ;
	static function get($id) {
		foreach (Game::$cache as $game)
			if ( $game->id == $id )
				return $game ;
		global $db ;
		$games = $db->select("SELECT * FROM `round` WHERE `id` = '$id'") ;
		if ( count($games) > 0 ) {
			if ( count($games) > 1 )
				echo count($games)." games found : $id\n" ;
			$game = new Game($games[0]) ;
			return $game ;
		}
		return null ;
	}
	public function __construct($obj, $type='') {
		Game::$cache[] = $this ;
		foreach ( $this->fields as $field ) {
			if ( property_exists($obj, $field) )
				$this->$field = $obj->$field ;
			else
				$this->$field = '' ;
		}
		$this->spectators = new Spectators() ;
		if ( ! isset($obj->id) )
			$this->create() ;
		else
			$this->getActions() ;
		if ( isset($obj->type) && ( $obj->type != '' ) )
			$this->type = $obj->type ;
		else
			$this->type = $type ;
		if ( isset($this->creator_score) && ( $this->creator_score == '' ) )
			$this->creator_score = 0 ;
		if ( isset($this->joiner_score) && ( $this->joiner_score == '' ) )
			$this->joiner_score = 0 ;
		$this->tournament_obj = Tournament::get($this->tournament, 'tournament') ;
	}
	// Actions
	public function getActions($from = null) {
		if ( count($this->actions) == 0 ) {
			global $db ;
			foreach (
				$db->select("SELECT * FROM `action` WHERE `game` = '{$this->id}' ORDER BY `id`")
				as $data
			) {
				$action = new Action($this, $data->sender, $data->type, $data->param,
					$data->local_index) ;
				$action->import($data->id, $data->recieved) ;
				$this->actions[] = $action ;
				if ( $action->type == 'spectactor' ) {
					$json = json_decode($action->param) ;
					if ( isset($json->nick) )
						$this->spectators->add($action->sender, $json->nick) ;
				}
			}
		}
		if ( $from == null )
			return $this->actions ;
		else {
			$result = array() ;
			foreach ( $this->actions as $action )
				if ( $action->id > $from ) 
					$result[] = $action ;
			return $result ;
		}
	}
	public function addAction($sender, $type, $param, $local_index=null) {
		if (
			( $sender != '' ) && ! $this->isPlayer($sender)
			&& ! in_array($type, array('text', 'spectactor' ))
		)
			return null ;
		$action = new Action($this, $sender, $type, $param, $local_index) ;
		$action->insert() ;
		$this->actions[] = $action ;
		switch ( $action->type ) {
			case 'psync' :
				$json = $action->param ;
				if ( is_string($json) )
					$json = json_decode($json) ;
				if ( property_exists($json->attrs, 'score') ) {
					switch ( $json->player ) {
						case 'game.creator' :
							$this->creator_score = $json->attrs->score ;
							$this->commit('creator_score') ;
							break ;
						case 'game.joiner' :
							$this->joiner_score = $json->attrs->score ;
							$this->commit('joiner_score') ;
							break ;
						default :
							$this->say('Unknown player : '.$json->player) ;
					}
					if ( $this->tournament_obj != null )
						$this->tournament_obj->match_won($this) ;
				}
			break ;
		}
		return $action ;
	}
	public function recieveAction($id) {
		foreach ( $this->actions as $action )
			if ( $action->id == $id )
				return $action->recieve() ;
		echo "Can't find action $id to recieve\n" ;
		return false ;
	}
	// Users
	public function isCreator($player_id) {
		return ( $player_id == $this->creator_id ) ;
	}
	public function isJoiner($player_id) {
		return ( $player_id == $this->joiner_id ) ;
	}
	public function isPlayer($player_id) {
		return ( $this->isCreator($player_id) || $this->isJoiner($player_id) ) ;
	}
	public function setUser($user, $handler) {
		if ( $this->isPlayer($user->player_id) )
			return true ;
		if ( $this->spectators->get($user->player_id) != null )
			return true ;
		// This user hasn't registered yet as spectator, let's do it
		$spectator = $this->spectators->add($user->player_id, $user->nick) ;
		$action = $this->addAction($user->player_id, 'spectactor', $spectator) ;
		$handler->broadcast(json_encode($action), $this) ;
		return false ;
	}
	// DB
	private function commit() { // Update all DB fields in param with this object's data
		if ( func_num_args() < 1 )
			return debug('commit() wait at least 1 arg') ;
		$this->last_update_date = now() ;
		$args = func_get_args() ;
		$args[] = 'last_update_date' ; // Force updating this field
		global $db ;
		$update = '' ;
		foreach ( $args as $field )
			if ( property_exists($this, $field) ) {
				if ( $update != '' )
					$update .= ', ' ;
				if ( is_object($this->$field) )
					$value = json_encode($this->$field) ;
				else
					$value = $this->$field ;
				$update .= "`$field` = '".$db->escape($value)."'" ;
			} else
				return debug('cannot commit '.$field) ;
		$db->query("UPDATE `round` SET $update WHERE `id` = '{$this->id}' ; ") ;
	}
	private function create() {
		global $db ;
		if ( $this->creator_id == $this->joiner_id ) // Goldfish
			$this->status = 3 ;
		else {
			if ( $this->tournament == '' )
				$this->status = 1 ;
			else
				$this->status = 4 ;
		}
		$this->creation_date = now() ;
		$this->last_update_date = $this->creation_date ;
		$this->id = $db->insert("INSERT INTO `round` (
			`status`, `creation_date`, `last_update_date`, `name`, `tournament`, `round`,
			`creator_nick`, `creator_id`, `creator_avatar`, `creator_deck`,
			`joiner_nick`, `joiner_id`, `joiner_avatar`, `joiner_deck`
		) VALUES (
			'{$this->status}', NOW(), NOW(), '".$db->escape($this->name)."',
			'{$this->tournament}', '{$this->round}',
			'".$db->escape($this->creator_nick)."',
			'".$db->escape($this->creator_id)."',
			'".$db->escape($this->creator_avatar)."',
			'".$db->escape($this->creator_deck)."',
			'".$db->escape($this->joiner_nick)."',
			'".$db->escape($this->joiner_id)."',
			'".$db->escape($this->joiner_avatar)."',
			'".$db->escape($this->joiner_deck)."'
		) ;") ;
		return $this->id ;
	}
	public function join($data) {
		global $db ;
		$this->status++ ;
		$this->joiner_nick = $data->joiner_nick ;
		$this->joiner_id = $data->joiner_id ;
		$this->joiner_avatar = $data->joiner_avatar ;
		$this->joiner_deck = $data->joiner_deck ;
		$this->commit('status', 'joiner_nick', 'joiner_id', 'joiner_avatar', 'joiner_deck') ;
		$this->deck('creator', $this->creator_deck) ;
		$this->deck('joiner', $this->joiner_deck) ;
		$starter = 'game.'.(mt_rand(0,1)?'creator':'joiner') ;
		$this->addAction('', 'toss', "{\"player\":\"$starter\"}") ;
		return $this ;
	}
	private function deck($player, $deck) {
		$cards = new Deck($deck) ;
		$this->cards($cards->main, 'game.'.$player.'.library') ;
		$this->cards($cards->side, 'game.'.$player.'.sideboard') ;
		return ( ( count($cards->main) + count($cards->side) ) > 0) ;
	}
	private function cards($cards, $zone) {
		if ( count($cards) <= 0 )
			return false ;
		global $db ;
		foreach ( $cards as $card ) {
			$card->zone = $zone ;
			$this->addAction('', 'card', json_encode($card)) ;
		}
	}
}
class Action {
	public $id = null ;
	public $game = null ;
	public $sender = null ;
	public $local_index = null ;
	public $recieved = 0 ;
	public $type = null ;
	public $param = null ;
	public function __construct($game, $sender, $type, $param, $local_index = null) {
		if ( $local_index == null )
			$local_index = time() ;
		$this->game = $game ;
		$this->sender = $sender ;
		$this->local_index = $local_index ;
		$this->type = $type ;
		$this->param = $param ;
	}
	public function import($id, $recieved) {
		$this->id = $id ;
		$this->recieved = $recieved ;
	}
	public function insert() {
		global $db ;
		$param = $this->param ;
		if ( is_object($param) )
			$param = json_encode($param) ;
		$this->id = $db->insert("INSERT INTO `action`
			(`game`, `sender`, `local_index`, `type`, `param`, `recieved`)
		VALUES (
			'{$this->game->id}',
			'{$this->sender}',
			'{$this->local_index}',
			'{$this->type}',
			'".$db->escape($param)."',
			'{$this->recieved}'
		);") ;
	}
	public function recieve() {
		global $db ;
		$db->query("UPDATE `action` SET `recieved` = `recieved`+1 WHERE `id`='{$this->id}' ;") ;
		$this->recieved++ ;
	}
}
