<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'../config.php' ;

function query($query, $name='Query', $conn=null) {
	if ( $conn == null  ) {
		global $mysql_connection ;
		if ( ! $mysql_connection ) {
			global $mysql_login, $mysql_password ;
			// MySQL
			$mysql_connection = mysql_connect('', $mysql_login, $mysql_password) ;
			if ( ! $mysql_connection )
				die('Connection failed : '.mysql_error()) ;
			if ( ! mysql_select_db($mysql_db, $mysql_connection) )
				die('Selection failed : '.mysql_error()) ;
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
function get2where($get, $comp, $prefix, $suffix, $table='') {
	$where = 'WHERE 1 ' ;
	foreach ( $get as $i => $val ) {
		if ( ( $i != 'submit' ) && ( $val != '' ) ) {
			$where .= 'AND ' ;
			if ( $table != '' )
				$where .= '`'.$table.'`.' ;
			$where .= '`'.$i . '` '.$comp.' \''.$prefix . mysql_real_escape_string($val) . $suffix . '\' ' ;
		}
	}
	return $where ;
}
function card_connect() {
	global $mysql_login, $card_login, $card_password, $card_db ;
	$card_connection = mysql_connect('', $card_login, $card_password, ( $card_login == $mysql_login )) ; // in case $card_login == $mysql_login, must open a new connexion
	if ( ! $card_connection )
		die('Card connection failed : '.mysql_error()) ;
	if ( ! mysql_select_db($card_db, $card_connection) )
		die('Card selection failed : '.mysql_error()) ;
	return $card_connection ;
}
class Db {
	private $host = '' ;
	private $user = '' ;
	private $pass = '' ;
	private $db = '' ;
	private $link = false ;
	public function __construct($host_, $user_, $pass_, $db_) {
		$this->host = $host_ ;
		$this->user = $user_ ;
		$this->pass = $pass_ ;
		$this->db = $db_ ;
	}
	public function dierr($msg) {
		die($msg) ;
	}
	public function escape($string) {
		$this->check() ;
		return $this->link->real_escape_string($string) ;
	}
	public function check() {
		if ( ! $this->link ) {
			$this->link = new mysqli($this->host, $this->user, $this->pass, $this->db) ;
			if ( $this->link->connect_error )
				$this->dierr('Erreur de connexion - check ('.$this->link->connect_errno.' : '.$this->link->connect_error.')') ;
		}
		if ( ! $this->link->ping() ) {
			//$this->dierr('Erreur de reconnexion - ping') ;
			$this->link = new mysqli($this->host, $this->user, $this->pass, $this->db) ;
			echo "MySQLi reconnected\n" ;
		}
		return $this->link ;
	}
	public function query($query) {
		$this->check() ;
		$result = $this->link->query($query) ;
		if ( $result === false )
			 $this->dierr("Erreur de requête : \n$query\n{$this->link->error}") ;
		return $result ;
	}
	public function insert($query) {
		$this->query($query) ;
		return $this->link->insert_id ;
	}
	public function select($query) {
		$result = $this->query($query) ;
		if (method_exists('mysqli_result', 'fetch_all')) # Compatibility layer with PHP < 5.3
			$res = $result->fetch_all(MYSQLI_ASSOC);
		else
			for ($res = array(); $tmp = $result->fetch_object();)
				$res[] = $tmp;
		return $res;
	}
	public function update($query) {
		$this->query($query) ;
		return $this->link->affected_rows ;
	}
	public function delete($query) {
		return $this->update($query) ;
	}
}
$db = new Db('', $mysql_login, $mysql_password, $mysql_db) ;
$db_cards = new Db('', $card_login, $card_password, $card_db) ;
?>
