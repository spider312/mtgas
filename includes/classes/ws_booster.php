<?php 
class Booster { // Only used in draft, interface with "booster" db table
	public $type = 'booster' ; // JSON Communication
	private $tournament = null ;
	private $player = null ;
	public $number = null ;
	public $pick = 0 ;
	public $destination = 'side' ;
	public $content = null ;
	private $fullcontent = null ;
	// Init
	public function __construct($tournament, $player, $number, $pick=0, $destination='side') {
		$this->tournament = $tournament ;
		$this->player = $player ;
		$this->number = $number ;
		$this->pick = $pick ;
		$this->destination = $destination ;
	}
	public function import($content) { // From DB
		global $db_cards ;
		$this->fullcontent = array() ;
		$this->content = json_decode($content) ;
		foreach ( $this->content as $card )
			$this->fullcontent[] = Card::get($card->name, $card->ext
				, property_exists($card, 'nb')?$card->nb:0) ;
	}
	public function generate($ext, $upool) { // Creation by daemon
		// Generate content
		$ext_obj = Extension::get($ext) ;
		if ( $ext_obj == null ) {
			$this->tournament->say("Extension $ext not found in booster generation") ;
			return false ;
		}
		$this->fullcontent = $ext_obj->booster($upool) ;
		$this->summarize() ;
		// Insert
		global $db ;
		$db->query("INSERT INTO `booster` (
			`content`,
			`tournament`,
			`player`,
			`number`,
			`pick`,
			`destination`
		) VALUES(
			'".$db->escape(json_encode($this->content))."',
			'{$this->tournament->id}',
			'{$this->player->order}',
			'{$this->number}',
			'{$this->pick}',
			'{$this->destination}'
		);") ;
	}
	private function summarize() { // generate content from fullcontent
		$this->content = array() ; // New object with only card names
		foreach ( $this->fullcontent as $fullcard ) {
			$card = new stdClass() ;
			$card->name = $fullcard->name ;
			$card->ext = $fullcard->ext ;
			$card->ext_img = $fullcard->ext_img ;
			if ( property_exists($fullcard->attrs, 'nb') )
				$card->nb = $fullcard->attrs->nb ;
			$card->rarity = $fullcard->rarity ;
			$this->content[] = $card ;
		}
	}
	// Accessors
	public function is($player, $number) { // Comparison without making $this->player public
		return ( ( $this->player == $player ) && ( $this->number == $number ) ) ;
	}
	// Evolution
	public function set_pick($nb, $main=false) { // User click on a card in drafter
		$destination = $main?'main':'side' ;
		if ( ( $this->pick == $nb ) && ( $this->destination == $destination) ) 
			return false ;
		$this->pick = $nb ;
		$this->destination = $destination ;
		$this->commit('pick', 'destination') ;
		return true ;
	}
	public function do_pick() { // Happends when judge says "draft"
		$max = count($this->fullcontent) ;
		if ( $max < 1 )
			return null ;
		if ( ( $this->pick < 1 ) || ($this->pick > $max) )
			$this->pick = rand(1, $max) ;
		$spl = array_splice($this->fullcontent, $this->pick-1, 1) ;
		$this->summarize() ;
		$this->pick = 0 ;
		$this->destination = 'side' ;
		$this->commit('content', 'pick', 'destination') ;
		return $spl[0] ;
	}
	// DB
	private function commit() {
		if ( func_num_args() < 1 )
			return false ;
		global $db ;
		$update = '' ;
		foreach ( func_get_args() as $field )
			if ( property_exists($this, $field) ) {
				if ( $update != '' )
					$update .= ', ' ;
				if ( is_array($this->$field) )
					$update .= "`$field` = '".
					$db->escape(json_encode($this->$field))."'" ;
				else
					$update .= "`$field` = '{$this->$field}'" ;
			} else
				return false ;
		$upd = $db->update("UPDATE `booster` SET $update
		WHERE
			`tournament` = '{$this->tournament->id}'
			AND `player` = '{$this->player->order}'
			AND `number` = '{$this->tournament->round}'
		; ") ;
		if ( $upd != 1 )
			$this->tournament->say('Error : '.$upd.' boosters commited, fields : '.
				implode(', ', func_get_args())) ;
	}
	public function delete() {
		foreach ( $this->tournament->boosters as $i => $booster )
			if ( $booster == $this ) {
				array_splice($this->tournament->boosters, $i, 1) ;
				global $db ;
				$upd = $db->delete("DELETE FROM `booster`
				WHERE
					`tournament` = '{$this->tournament->id}'
					AND `player` = '{$this->player->order}'
					AND `number` = '{$this->tournament->round}'
				; ") ;
				if ( $upd != 1 )
					$this->tournament->say('Error : '.$upd.' boosters deleted') ;
				return true ;
			}
		$this->tournament->say("Booster not found t {$this->tournament->id} p {$this->player->order} n {$this->tournament->round}") ;
		return false ;
	}
}
