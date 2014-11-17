<?php
use \Devristo\Phpws\Server\UriHandler\WebSocketUriHandler ;
use \Devristo\Phpws\Protocol\WebSocketTransportInterface ;
use \Devristo\Phpws\Messaging\WebSocketMessageInterface ;
class TournamentIndexHandler extends TournamentHandler {
	public $users_fields = array('player_id', 'nick', 'tournament') ;
	public function register_user(WebSocketTransportInterface $user, $data) {
		// Get tournament
		$tournament = Tournament::get($data->tournament) ;
		if ( $tournament == null ) {
			$user->sendString('{"msg": "Tournament not exist"}') ;
			$this->observer->say('Tournament '.$data->tournament.' not exist') ;
			return false ;
		}
		$user->tournament = $tournament ;
		// Get player
		$i = $tournament->registered($user) ;
		if ( $i !== false )
			$player = $tournament->players[$i] ;
		else
			$player = null ;
		// Player redirected from index
		if ( ( $tournament->status == 2 ) && ( $player != null ) )
			$tournament->players[$i]->set_ready(true) ;
		$tournament->send() ;
		if ( $player == null )
			$tournament->register_spectator($user) ;
		else {
			// Redirect
			if ( ( $tournament->status == 3 )
				&& ( ! $this->observer->draft->is_connected($tournament, $user->player_id) ) )
				$user->sendString('{"type": "redirect"}') ;
			if ( ( $tournament->status == 4 )
				&& ( ! $this->observer->build->is_connected($tournament, $user->player_id) ) )
				$user->sendString('{"type": "redirect"}') ;
			if ( $tournament->status == 5 ) {
				$game = $tournament->player_match($user->player_id) ;
				if ( $game == null )
					$this->observer->say('No game found') ;
				else if ( $game->joiner_id == '' ) { // Bye
				} else if ( ! $this->observer->game->connected($user->player_id, $game)
				&& ( $game->status < 7 ) )
					$user->sendString('{"type": "redirect", "game": '.$game->id.'}') ;
			}
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
				if ( ( $player != null ) && ( $player->status < 5 ) ) {
					$user->tournament->log($user->player_id, 'drop', '') ;
					$player->set_status(7) ;
					if ( count($user->tournament->get_players()) < 2 )	
						$user->tournament->end() ;
					$user->tournament->send() ;
				}
				break ;
			default :
				$this->observer->say('Unknown type : '.$data->type) ;
				print_r($data) ;
		}
	}
}
