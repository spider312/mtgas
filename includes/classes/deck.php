<?php 
include_once('card.php') ;
class Deck {
	public $type = 'deck' ; // JSON
	public $main = array() ;
	public $side = array() ;
	private $sort_fields = array('color_index', 'converted_cost', 'name') ;
	public function __construct($deck=null) {
		if ( is_string($deck) ) {
			$reg_comment = '/^\s*\/\/(.*)/' ;
			$reg_empty = "/^\s*\n$/" ;
			$reg_side = '/^SB:(.*)$/' ;
			$reg_card_mwd = '/(\d+)\s*\[(.*)\]\s*(.+)$/' ;
			$reg_card_apr = '/(\d+)\s*(.+)$/' ;
			$lines = explode("\n", $deck) ; // Cut file content in lines
			$notfound = 0 ;
			foreach ( $lines as $value ) { // Parse lines one by one
				// Not a card line
				if ( preg_match($reg_comment, $value, $matches) ) // Comment line
					continue ;
				if ( preg_match($reg_empty, $value, $matches) ) // Empty line
					continue ;
				// Sideboard
				if ( $side = preg_match($reg_side, $value, $matches) )
					$value = $matches[1] ;
				// Search
				$line = null ;
				$card = null ;
				$ext = null ;
				if ( preg_match($reg_card_mwd, $value, $matches) ) { // MWS
					list($line, $nb, $ext, $name) = $matches ;
				} else if ( preg_match($reg_card_apr, $value, $matches) ) { // Aprentice
					list($line, $nb, $name) = $matches ;
				}
				if ( $line != null ) { // Card found in MWS or Aprentice
					$card = Card::get(trim($name), $ext) ;
				}
				// (not) Found
				if ( $card == null ) {
					if ( ++$notfound > 3 ) {
						echo "Too many cards not found, deck parsing canceled\n" ;
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
