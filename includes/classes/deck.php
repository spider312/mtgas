<?php 
include_once('card.php') ;
class Deck {
	public $type = 'deck' ; // JSON
	public $main = array() ;
	public $side = array() ;
	private $sort_fields = array('color_index', 'converted_cost', 'name') ;
	public function __construct($deck=null, $ext='') {
		if ( is_string($deck) ) {
			$reg_comment = '/^\s*\/\/(.*)/' ;
			$reg_empty = "/^\s*\n$/" ;
			//$reg_card_mwd = '/(\d+)\s*\[(.*)\]\s*\b(.+)\b/' ; // replaced by trim
			$reg_card_mwd = '/(\d+)\s*\[(.*)\]\s*(.+)$/' ;
			$reg_card_apr = '/(\d+)\s*(.+)$/' ;
			$reg_side = '/^SB:(.*)$/' ;
			$lines = explode("\n", $deck) ; // Cut file content in lines
			$notfound = 0 ;
			foreach ( $lines as $value ) { // Parse lines one by one
				$card = null ;
				if ( preg_match($reg_side, $value, $matches) ) { // Card goes to side
					$value = $matches[1] ;
					$side = true ;
				} else
					$side = false ; // By default, card goes maindeck
				if ( preg_match($reg_comment, $value, $matches) ) { // Comment line
				} elseif ( preg_match($reg_empty, $value, $matches) ) { // Empty line
				} elseif ( preg_match($reg_card_mwd, $value, $matches) ) { // MWS
					list($line, $nb, $cardext, $name) = $matches ;
					if ( $ext != '' )
						$cardext = $ext ;
					$card = Card::get(trim($name), $cardext) ;
				} elseif ( preg_match($reg_card_apr, $value, $matches) ) { // Aprentice
					list($line, $nb, $name) = $matches ;
					$card = Card::get(trim($name)) ;
				}
				if ( $card == null ) {
					if ( ++$notfound > 3 ) {
						echo 'Too many cards not found, deck parsing canceled' ;
						return false ;
					}
				} else {
					if ( $notfound > 0 )
						$notfound-- ;
					for ( $i = 0 ; $i < $nb ; $i++ )
						if ( $side )
							$this->side[] = $card ;
						else
							$this->main[] = $card ;
				}
			}
		}
	}
	public function sort_func($a, $b) {
		foreach ( $this->sort_fields as $field ) {
			if ( property_exists($a, $field) ) {
				if ( $a->{$field} < $b->{$field} )
					return -1 ;
				else if ( $a->{$field} > $b->{$field} )
					return 1 ;
			} else {
				if ( $a->attrs->{$field} < $b->attrs->{$field} )
					return -1 ;
				else if ( $a->attrs->{$field} > $b->attrs->{$field} )
					return 1 ;
			}
		}
		return 0 ;
	}
	public function sort() {
		usort($this->side, array($this, 'sort_func')) ;
		usort($this->main, array($this, 'sort_func')) ;
	}
	public function summarize() { // generate text deck from object deck
		$result = '' ;
		foreach ( $this->main as $card )
			$result .= ' 1 '.$card->line() ;
		foreach ( $this->side as $card )
			$result .= 'SB: 1 '.$card->line() ;
		return $result ;
	}
}
