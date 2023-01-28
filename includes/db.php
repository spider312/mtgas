<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../config.php' ;

function query($query, $name='Query', $conn=null) {
	if ( $conn == null  ) {
		global $mysql_connection ;
		if ( ! $mysql_connection ) {
			global $mysql_login, $mysql_password, $mysql_db ;
			// MySQL
			$mysql_connection = mysql_connect('', $mysql_login, $mysql_password) ;
			if ( ! $mysql_connection ) {
				die('Connection failed : '.mysql_error()) ;
			}
			if ( ! mysql_select_db($mysql_db, $mysql_connection) ) {
				die('Selection failed : '.mysql_error()) ;
			}
		}
		$conn = $mysql_connection ;
	}
	$result = mysql_query($query, $conn) ;
	if ( ! $result )
		die($name.' failed : '.mysql_errno().' : '.mysql_error().' ('.$query.')') ;
	return $result ;
}
function query_oneshot($query, $name='Query', $conn=null) {
	$result = query($query, $name, $conn) ;
	$nb = 0 ;
	if ( $result && ! is_bool($result) )
		$nb = mysql_num_rows($result) ;
	if ( $nb != 1 )
		return null ;
	if ( $row = mysql_fetch_object($result) )
		return $row ;
	else
		return null ;
}
function query_as_array($query, $name='Query', $conn=null) {
	$result = query($query, $name, $conn) ;
	$array = array() ;
	while ( $row = mysql_fetch_object($result) )
		$array[] = $row ;
	return $array ;
}
function card_connect() {
	global $mysql_login, $card_login, $card_password, $card_db ;
	$card_connection = mysql_connect('', $card_login, $card_password, ( $card_login == $mysql_login )) ; // in case $card_login == $mysql_login, must open a new connexion
	if ( ! $card_connection ) {
		die('Card connection failed : '.mysql_error()) ;
	}
	if ( ! mysql_select_db($card_db, $card_connection) ) {
		die('Card selection failed : '.mysql_error()) ;
	}
	return $card_connection ;
}
class Db {
	private $host = '' ;
	private $user = '' ;
	private $pass = '' ;
	private $db = '' ;
	private $link = false ;
	private $charset = 'utf8' ; // Unused ATM
	public $debug = false ;
	public function __construct($host_, $user_, $pass_, $db_) {
		$this->host = $host_ ;
		$this->user = $user_ ;
		$this->pass = $pass_ ;
		$this->db = $db_ ;
	}
	private function say($msg) {
		echo $msg."\n" ;
	}
	public function dierr($msg) {
		die($msg) ;
	}
	public function escape($string) {
		$this->check() ;
		return $this->link->real_escape_string($string) ;
	}
	private function connect() {
		$this->link = new mysqli($this->host, $this->user, $this->pass, $this->db) ;
		if ( $this->link->connect_error ) {
			$this->dierr('Erreur de connexion ('.$this->link->connect_errno.' : '.$this->link->connect_error.')') ;
		}
		if ( $this->debug ) {
			//$this->say("MySQL character set is ".$this->link->character_set_name());
		}
		//$this->set_charset() ;
	}
	public function set_charset($charset=null) {
		if ( $charset === null ) {
			$charset = $this->charset ;
		}
		$this->check() ;
		$curr = $this->link->character_set_name() ;
		if ( $curr !== $charset ) {
			$this->link->set_charset($charset) ;
			//$this->say("MySQL character set changed to ".$this->link->character_set_name());
		}
	}
	private function check() {
		if ( ! $this->link ) {
			$this->connect() ;
		}
		if ( ! $this->link->ping() ) {
			$this->connect() ;
			$this->say("MySQLi reconnected") ;
		}
		return $this->link ;
	}
	private function query($query) {
		$this->check() ;
		$start = microtime(true) ;
		$result = $this->link->query($query) ;
		if ( $this->debug ) {
			$end = microtime(true) ;
			$duration = ( $end - $start ) * 1000 ; // microtime returns seconds as float
			$duration = intval($duration) ;
			if ( $duration > 250 ) {
				$this->say('SQL Query '.$duration.'ms : '.substr($query, 0, 100)) ;
			}
		}
		if ( $result === false )
			 $this->dierr("Erreur de requête : \n$query\n{$this->link->error}") ;
		return $result ;
	}
	public function insert($query) { // Runs sync insert efault, returning insert ID
		$this->query($query) ;
		return $this->link->insert_id ;
	}
	public function select($query) { // Runs a sync select, returning selected data
		$result = $this->query($query) ;
		for ($res = array(); $tmp = $result->fetch_object();)
			$res[] = $tmp;
		return $res;
	}
	public function update($query) { // Runs a sync update, returning affected rows
		$this->query($query) ;
		return $this->link->affected_rows ;
	}
	public function delete($query) { // Runs a sync delete, returning affected rows
		$this->query($query) ;
		return $this->link->affected_rows ;
	}
}
$db = new Db('', $mysql_login, $mysql_password, $mysql_db) ;
$db_cards = new Db('', $card_login, $card_password, $card_db) ;
?>
