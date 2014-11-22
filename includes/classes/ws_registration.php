<?php 
require_once 'deck.php' ;
class Registration {
	// DB Fields + date, update
	private $tournament = null ;
	public $player_id = '' ;
	public $nick = '' ;
	public $avatar = '' ;
	public $status = 1 ;
	public $order = -2 ;
	public $ready = false ;
	public $score = null ;
	private $deck = '' ;
	private $deck_obj = null ;
	public $deck_cards = -1 ;
	public $side_cards = -1 ;
	public $connected = array() ;
	public function __construct($obj, $tournament) {
		$this->type = 'player' ; // JSON over WS
		$this->tournament = $tournament ;
		$this->player_id = $obj->player_id ;
		$this->nick = $obj->nick ;
		$this->avatar = $obj->avatar ;
		$this->deck = $obj->deck ;
		$this->status = property_exists($obj, 'status') ? $obj->status : $this->status ;
		$this->order = property_exists($obj, 'order') ? $obj->order : $this->order ;
		$this->ready = property_exists($obj, 'ready') ? (bool)$obj->ready : $this->ready ;
		$this->deck_obj = new Deck($this->deck) ;
		$this->deck_cards = count($this->deck_obj->main) ;
		$this->side_cards = count($this->deck_obj->side) ;
	}
	public function get_tournament_id() {
		return $this->tournament->id ;
	}
	public function connect($from) {
		if ( ( $i = array_search($from, $this->connected) ) === false ) {
			array_push($this->connected, $from) ;
			$this->tournament->send() ;
		}
	}
	public function disconnect($from) {
		if ( ( $i = array_search($from, $this->connected) ) !== false ) {
			array_splice($this->connected, $i, 1) ;
			$this->tournament->send() ;
		}
	}
	/* Don't work for JSON, PHP 5.4 has JsonSerializable for that
	public function __get($name) {} */
	// Draft
	public function pick($card, $destination='side') {
		$side = ( $destination == 'side' ) ;
		if ( $side )
			$this->deck_obj->side[] = $card ;
		else
			$this->deck_obj->main[] = $card ;
		$this->summarize() ;
		$this->set_ready(false) ;
	}
	// Build
		// Pool cards
	public function toggle($name, $from) {
		if ( ! property_exists($this->deck_obj, $from) )
			return false ;
		if ( $from == 'main' ) {
			$from =& $this->deck_obj->main ;
			$to =& $this->deck_obj->side ;
		} else {
			$from =& $this->deck_obj->side ;
			$to =& $this->deck_obj->main ;
		}
		foreach ( $from as $i => $card )
			if ( $card->name == $name ) {
				$spl = array_splice($from, $i, 1) ;
				array_push($to, $spl[0]) ;
				//$this->deck_obj->sort() ;
				$this->card_removed() ;
				$this->summarize() ;
				return true ;
			}
		return false ;
	}
		// Lands in maindeck
	public function add($name, $nb) {
		for ( $i = 0 ; $i < $nb ; $i++ ) {
			$card = Card::get($name) ;
			if ( $card != null )
				$this->deck_obj->main[] = $card ;
		}
		$this->summarize() ;
	}
	public function remove($name) {
		foreach ( $this->deck_obj->main as $i => $card )
			if ( $card->name == $name ) {
				array_splice($this->deck_obj->main, $i, 1) ;
				$this->card_removed() ;
				$this->summarize() ;
				return true ;
			}
		echo "can't find $name to remove" ;
	}
	private function card_removed() { // After a card was removed, check if deck still has 40 cards
		if ( $this->ready && ( count($this->deck_obj->main) < 40 ) )
			$this->set_ready(false) ;
	}
	// Tournament run
	public function get_score() {
		$score = new stdClass() ;
		$score->matchplayed = 0 ;
		$score->matchpoints = 0 ;
		$score->gameplayed = 0 ;
		$score->gamepoints = 0 ;
		foreach ( $this->tournament->games as $round ) {
			foreach ( $round as $match ) {
				if ( $this->player_id == $match->creator_id ) {
					$player_score = $match->creator_score ;
					$opponent_score = $match->joiner_score ;
				} else if ( $this->player_id == $match->joiner_id ) {
					$player_score = $match->joiner_score ;
					$opponent_score = $match->creator_score ;
				} else // Player didn't participate in this match
					continue ; // Go next match
				$score->matchplayed++ ;
				// Match wins
				if ( $player_score > $opponent_score ) // Player won
					$score->matchpoints += 3 ;
				else if ( $player_score == $opponent_score ) // Player tied
					$score->matchpoints += 1 ;
				// Game wins
				if ( $player_score + $opponent_score > 0 )
					$score->gameplayed += $player_score + $opponent_score ;
				else
					$score->gameplayed++ ; // At least 1 game per round
				$score->gamepoints += 3 * $player_score ;
			}
		}
		// Percentages
			// Match win
		if ( $score->matchplayed == 0 )
			$score->matchwinpct = 0 ;
		else
			$score->matchwinpct = max(1/3, $score->matchpoints/(3*$score->matchplayed)) ;
			// Game win
		if ( $score->gameplayed == 0 )
			$score->gamewinpct = 0 ;
		else
			$score->gamewinpct = max(1/3, $score->gamepoints/(3*$score->gameplayed)) ;
		$this->score = $score ;
		return $score ;
	}
	function get_omw() {
		// Get a list of player's opponents
		$opponents = array() ;
		foreach ( $this->tournament->games as $round_nb => $round )
			foreach ( $round as $match )
				if ( $match->joiner_id != '' ) { // Not a bye
					if ( $this->player_id == $match->creator_id )
						$opponents[$round_nb] = $match->joiner_id ;
					else if ( $this->player_id == $match->joiner_id)
						$opponents[$round_nb] = $match->creator_id ;
				}
		// Sum their OMW
		$matchwin = 0 ;
		$gamewin = 0 ;
		$nb = 0 ;
		foreach ( $this->tournament->players as $player )
			if ( in_array($player->player_id, $opponents) ) {
				$nb++ ;
				$matchwin += $player->score->matchwinpct ;
				$gamewin += $player->score->gamewinpct ;
			}
		// Mean
		if ( $nb != 0 ) {
			$this->score->opponentmatchwinpct = $matchwin / $nb ;
			$this->score->opponentgamewinpct = $gamewin / $nb ;
		}
		return $this->score ;
	}
	// Lib
	public function get_deck() {
		return $this->deck_obj ;
	}
	public function summarize($sort=false) {
		$this->deck = $this->deck_obj->summarize() ;
		if ( $sort )
			$this->deck_obj->sort() ;
		$this->deck_cards = count($this->deck_obj->main) ;
		$this->side_cards = count($this->deck_obj->side) ;
		$this->commit('deck') ;
	}
	public function set_status($status) {
		if ( $this->status < 6 ) {
			$this->status = $status ;
			$this->commit('status') ;
			$this->set_ready(false) ;
		}
	}
	public function set_ready($ready=true) { // Return if value changed
		if ( $this->ready == $ready )
			return false ;
		$this->ready = $ready ;
		$this->commit('ready') ;
		// Tournament consequences
		if ( $this->tournament->status == 4 )
			$this->tournament->log($this->player_id, 'ready', $this->ready) ;
		$this->tournament->players_ready() ;
		return true ;
	}
	public function insert($i) {
		$this->order = $i ;
		global $db ;
		$db->query("INSERT INTO `registration` (
			`tournament_id`,
			`player_id`,
			`nick`,
			`avatar`,
			`deck`,
			`order`
		) VALUES(
			'{$this->tournament->id}',
			'{$this->player_id}',
			'{$this->nick}',
			'{$this->avatar}',
			'".$db->escape($this->deck)."',
			'{$this->order}'
		);") ;
	}
	private function commit() {
		if ( func_num_args() < 1 )
			return false ;
		global $db ;
		$this->update = now() ;
		$args = func_get_args() ;
		$args[] = 'update' ; // Force updating this field
		$update = '' ;
		foreach ( $args as $field )
			if ( property_exists($this, $field) ) {
				if ( $update != '' )
					$update .= ', ' ;
				if ( is_object($this->$field) )
					$update .= "`$field` = '".
						$db->escape(json_encode($this->$field))."'" ;
				else
					$update .= "`$field` = '".
						$db->escape($this->$field)."'" ;
			} else
				return false ;
		$db->query("UPDATE `registration` SET $update
		WHERE `tournament_id` = '{$this->tournament->id}'
		AND `player_id` = '{$this->player_id}' ; ") ;
	}
}
