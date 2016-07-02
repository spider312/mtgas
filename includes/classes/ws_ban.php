<?php
class Bans {
	public $list = array() ;
	private $observer = null ;
	public function __construct($observer) {
		$this->observer = $observer ;
		global $db ;
		$raw = $db->select("SELECT * FROM `ban`") ;
		foreach ( $raw as $ban )
			$this->list[] = new Ban($ban->reason, $ban->host, $ban->player_id, $ban->expire, $ban->id) ;
	}
	public function is($host = null, $player_id = null) {
		foreach ( $this->list as $ban )
			if ( $ban->is($host, $player_id) )
				return $ban ;
		return false ;
	}
	public function add($reason = null, $host = null, $player_id = null, $expire = null) {
		$ban = new Ban($reason, $host, $player_id, $expire) ;
		$this->list[] = $ban ;
		$this->observer->say('Ban #'.$ban->id.' added : '.$ban->reason.'('.$ban->mask().')') ;
	}
	public function del($id = null) {
		if ( $id == null )
			return false ;
		foreach ( $this->list as $i => $ban )
			if ( $ban->id == $id ) {
				array_splice($this->list, $i, 1) ;
				$this->observer->say('Ban #'.$ban->id.' removed : '.$ban->reason.'('.$ban->mask().')') ;
			}
	}
}
class Ban {
	public $id = -1 ;
	public $reason = 'no reason given' ;
	public $host = null ;
	public $player_id = null ;
	public $expire = null ;
	// Init
	public function __construct($reason = null, $host = null, $player_id = null, $expire = null, $id = null) {
		if ( $reason != null )
			$this->reason = $reason ;
		if ( $host != null )
			$this->host = $host ;
		if ( $player_id != null )
			$this->player_id = $player_id ;
		if ( $expire != null )
			$this->expire = $expire ;
		if ( $id != null )
			$this->id = $id ;
		else
			$this->insert() ;
	}
	public function __destruct() {
		global $db ;
		$db->delete("DELETE FROM `ban` WHERE `id` = '{$this->id}'") ;
	}
	public function mask() {
		$user = ( $this->player_id == null ) ? '*' : $this->player_id ;
		$user .= '@' ;
		$user .= ( $this->host == null ) ? '*' : $this->host ;
		return $user ;
	}
	public function insert() {
		global $db ;
		$this->id = $db->insert("INSERT INTO `ban` (
			 `reason`,
			`host`,
			`player_id`
		) VALUES (
			'".$db->escape($this->reason)."',
			'".$db->escape($this->host)."',
			'".$db->escape($this->player_id)."'
		);") ;
	}
	public function is($host = null, $player_id = null) {
		if ( ( $host != null ) && ( $host == $this->host ) )
			return true ;
		if ( ( $player_id != null ) && ( $player_id == $this->player_id ) )
			return true ;
		return false ;
	}
}
