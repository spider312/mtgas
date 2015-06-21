<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class AdminHandler extends ParentHandler {
	public function register_user(WebSocketTransportInterface $user, $data) {
		$overall = new stdClass() ;
		$overall->type = 'overall' ;
		// Games & tournaments
		$overall->pending_duels = count($this->observer->pending_duels) ;
		$overall->joined_duels = count($this->observer->joined_duels) ;
		$overall->pending_tournaments = count($this->observer->pending_tournaments) ;
		$overall->running_tournaments = count($this->observer->running_tournaments) ;
		$overall->ended_tournaments = count($this->observer->ended_tournaments) ;
		// Handlers (connected users)
		$overall->handlers = new stdClass() ;
		foreach ( $this->observer->handlers as $handler ) {
			$h = $this->observer->{$handler} ;
			$overall->handlers->{$handler} = $h->list_users() ;
		}
		// MTG Data
		$overall->extensions = count(Extension::$cache) ;
		$overall->cards = count(Card::$cache) ;
		// Broadcast
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
			case 'refresh_mtg_data' :
				$this->observer->import_mtg() ;
				break ;
			case 'kick' :
				if ( property_exists($data, 'handler') && property_exists($data, 'id') ) {
					if ( property_exists($this->observer, $data->handler) ) {
						$handler = $this->observer->{$data->handler} ;
						$kicked = array() ;
						foreach ( $handler->getConnections() as $cnx )
							if ( isset($cnx->player_id) && ( $cnx->player_id == $data->id ) ) {
								$cnx->close('Kicked') ;
								$kicked[] = $cnx->nick ;
							}
						if ( count($kicked) > 0 )
							$this->say('No players with id '.$data->id.' to kick') ;
						else
							$this->say(implode($kicked).' kicked') ;
					} else
						$this->say('Trying to kick from unexisting handler '.$data->handler) ;
				}
				break ;
			default :
				$this->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
}
