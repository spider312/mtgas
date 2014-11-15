<?php
class Spectators {
	public $spectators = array();
	public function __construct() {
	}
	public function get($id) {
		foreach ( $this->spectators as $spectator )
			if ( $id == $spectator->player_id )
				return $spectator ;
		return null ;
	}
	public function add($id, $nick) {
		$result = $this->get($id) ;
		if ( $result != null )
			return $result ;
		$result = new Spectator($id, $nick) ;
		$this->spectators[] = $result ;
		return $result ;
	}
}
class Spectator {
	public $player_id = '' ;
	public $nick = '' ;
	public function __construct($id, $nick)  {
		$this->player_id = $id ;
		$this->nick = $nick ;
	}
}
?>
