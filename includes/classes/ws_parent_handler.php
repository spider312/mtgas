<?php
// Parent class for all - not game - handlers (index, tournament index build draft, admin)
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class ParentHandler extends WebSocketUriHandler {
	public $observer = null ;
	public $type = 'parent' ;
	public $users_fields = array('player_id', 'nick') ; // Data for user listing - default
	protected $debug = false ;
	public function __construct($logger, $observer, $type) {
		parent::__construct($logger) ;
		$this->observer = $observer ;
		$this->type = $type ;
	}
	public function say($msg) {
		$this->observer->say($msg) ;
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
		$data = json_decode($msg->getData()) ;
		if ( $data == null ) {
			$this->say('Unparsable JSON : '.$msg->getData()) ;
			return false ;
		}
		if ( ! isset($data->type) ) {
			$this->say('No type given') ;
			return false ;
		}
		if ( $this->debug )
			$this->say(' <- '.$data->type) ;
		switch ( $data->type ) {
			case 'ping' :
				$user->sendString($msg->getData()) ;
				break ;
			case 'register' :
				if ( isset($data->player_id) && isset($data->nick) ) {
					$user->player_id = $data->player_id ;
					$user->nick = $data->nick ;
					$this->register_user($user, $data) ;
				} else {
					$this->say('Incomplete registration : '.$data->player_id.' / '.$data->nick) ;
					$user->close();
				}
				break ;
			default :
				if ( isset($user->player_id) )
					$this->recieve($user, $data) ;
				else
					$this->say('Action '.$data->type.' from unregistered user') ;
		}
	}
}
