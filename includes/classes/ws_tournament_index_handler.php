<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class TournamentIndexHandler extends TournamentHandler {
	public $users_fields = array('player_id', 'nick', 'tournament') ;
	public function register_user(WebSocketTransportInterface $user, $data) {
		// Get tournament
		$tournament = Tournament::get($data->tournament) ;
		if ( $tournament === null ) {
			$user->sendString('{"msg": "Tournament not exist"}') ;
			$this->observer->say('Tournament '.$data->tournament.' not exist') ;
			return false ;
		}
		$user->tournament = $tournament ;
		// Get player
		$i = $tournament->registered($user) ;
		if ( $i !== false ) {
			$player = $tournament->players[$i] ;
		} else {
			$player = null ;
		}
		if ( $player === null ) {
			// Not a player, register as spectactor
			if ( ! $tournament->register_spectator($user) ) {
				$user->sendString(json_encode($tournament)) ; // And send to client unless registration has broadcast
			}
			return true ;
		}
		$sent = false ; // Try to limit the number of times tournament is sent
		// Player redirected from index : set status as redirected
		if ( ( $tournament->status == 2 ) && ( $player->set_ready(true) ) ) {
			$tournament->send() ; // Player status changed
			$sent = true ;
		}
		// Connection indicator for player
		$sent = $player->connect($this->type) || $sent ;
		$user->player = $player ;
		// Redirect
		if ( ( $tournament->status == 3 )
			&& ( ! $this->observer->draft->is_connected($tournament, $user->player_id) ) ) {
			$user->sendString('{"type": "redirect"}') ;
			$sent = true ;
		}
		if ( ( $tournament->status == 4 )
			&& ( ! $this->observer->build->is_connected($tournament, $user->player_id) ) ) {
			$user->sendString('{"type": "redirect"}') ;
			$sent = true ;
		}
		if ( $tournament->status == 5 ) {
			$game = $tournament->player_match($user->player_id) ;
			if ( $game == null )
				$this->observer->say('No game found') ;
			else if ( $game->joiner_id == '' ) { // Bye
			} else if ( ! $this->observer->game->connected($user->player_id, $game)
			&& ( $game->status < 7 ) ) {
				$user->sendString('{"type": "redirect", "game": '.$game->id.'}') ;
				$sent = true ;
			}
		}
		// Send tournament data if not already automatically sent and not redirected
		if ( ! $sent ) {
			$user->sendString(json_encode($tournament)) ;
		}
	}
	public function recieved(WebSocketTransportInterface $user, $data) {
		switch ( $data->type ) {
			case 'save' :
				$player = $user->tournament->get_player($data->player) ;
				if ( $player != null ) {
					$name = $user->tournament->name.' '.$player->nick ;
					$deck = "// Deck file for Magic Workstation created with mogg.fr\n" ;
					$deck .= "// NAME : {$user->tournament->name}\n" ;
					$deck .= "// CREATOR : {$player->nick}\n" ;
					$deck .= "// FORMAT : {$user->tournament->type}\n" ;
					$deck .= $player->get_deck()->summarize() ;
					$deck = json_encode($deck) ;
					$user->sendString('{"type": "save", "name": "'.$name.'", "deck": '.$deck.'}') ;
				}
				break ;
			case 'drop' :
				$player = $user->tournament->get_player($user->player_id) ;
				if ( ( $player != null ) && ( $player->status < 5 ) )
					$player->drop('Requested by player') ;
				break ;
			default :
				$this->observer->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
}
