<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class AdminHandler extends ParentHandler {
	public function register_user(WebSocketTransportInterface $user, $data) {
		$overall = new stdClass() ;
		$overall->type = 'overall' ;

		$overall->pending_duels = $this->observer->pending_duels ;
		$overall->joined_duels = $this->observer->joined_duels ;
		$overall->pending_tournaments = $this->observer->pending_tournaments ;
		$overall->running_tournaments = $this->observer->running_tournaments ;

		$overall->handlers = new stdClass() ;
		foreach ( $this->observer->handlers as $handler ) {
			$h = $this->observer->{$handler} ;
			$overall->handlers->{$handler} = $h->list_users() ;
		}

		$user->sendString(json_encode($overall)) ;
	}
	public function recieve(WebSocketTransportInterface $user, $data) {
		switch ( $data->type ) {
			case 'tournament_set' :
				$t = Tournament::get($data->id) ;
				if ( $t == null )
					$user->sendString('{"type": "msg", "msg": "No tournament '.$data->id.'"}') ;
				else {
					foreach ( $data as $field => $value)
						if ( ( $field != 'type' ) && ( $field != 'id' ) ) {
							if ( ! property_exists($t, $field) ) {
								$this->say('Updating non existing field '.$field) ;
								continue ;
							}
							$this->say('Admin setting "'.$field.'" to "'.$value.'"') ;
							if ( $field == 'due_time' ) { // Have to relaunch timer, using dedicated func
								// Go on
								$left = strtotime($value) - time() ;
								if ( $left > 0 ) // Some time left in current tournament step
									$t->timer_goon($left) ;
								else
									$user->sendString('{"type": "msg", "msg": "Can\'t set due to past time"}') ;
							} else {
								$t->$field = $value ;
								$t->commit($field) ;
							}
						}
					$t->send() ;
				}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
}
