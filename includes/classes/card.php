<?php
require_once __dir__ . '/extension.php' ;
require_once __dir__ . '/../../includes/card.php' ;
//
$cards_cache = array() ;
class Card {
	// id 	cost 	types 	text
	public $id = -1 ;
	public $name = '' ;
	public $attrs = null ;
	public $ext = '' ; // Higher priority image (or given) extension
	public $rarity = '' ; // Rarity in selected extension
	public $text = '' ;
	public $exts = array() ; // Extension list
	public $extensions = array() ; // Full data for extensions
	public function __construct($card) {
		$this->occurence = ++Card::$occurences ;
		$this->id = $card->id ;
		$this->name = $card->name ;
		$this->text = $card->text ;
		$this->attrs = json_decode($card->attrs) ;
		// Merge attrs and fixed_attrs
		if ( $card->fixed_attrs != '' ) {
			$fixed_attrs = json_decode($card->fixed_attrs) ;
			if ( $fixed_attrs == null ) {
				echo "{$this->name} has buggy fixed attrs : {$card->fixed_attrs}\n" ;
				echo json_verbose_error()."\n" ;
			} else
				foreach($fixed_attrs as $k => $v)
					$this->attrs->$k = $v ; // Overwrites array attrs such as tokens
		}
		// All extensions for this card
		global $db_cards ;
		$this->extensions = $db_cards->select("
			SELECT
				`extension`.`se`,
				`card_ext`.`rarity`,
				`card_ext`.`nbpics`,
				`extension`.`priority`
			FROM
				`card_ext`,
				`extension`
			WHERE
				`card_ext`.`card` = '{$card->id}' AND
				`extension`.`id` = `card_ext`.`ext`
			ORDER BY
				`extension`.`priority` DESC,
				`extension`.`release_date` DESC") ;
		foreach ( $this->extensions as $ext )
			$this->exts[] = $ext->se ;
	}
	public function __clone() {
		$this->occurence = ++Card::$occurences ;
		$this->attrs = clone $this->attrs ;
	}
	public function extend($ext='', $pic_num=0) { // Get a card copy for an ext and a num
		$result = clone $this ;
		$result->ext = '' ;
		$result->rarity = '' ;
		$result->ext_img = '' ;
		if ( count($this->extensions) == 0 ) {
			echo "No extension for {$this->name}\n" ;
			return $result ;
		}
		// Search given extension for rarity
		$ext_row = null ;
		if ( $ext == '' )
			$ext_row = $this->extensions[0] ;
		else
			foreach ( $this->extensions as $extension )
				if ( $extension->se == $ext ) {
					$ext_row = $extension ;
					break ;
				}
		if ( $ext_row == null )
			$ext_row = $this->extensions[0] ;
		$result->ext = $ext_row->se ;
		$result->rarity = $ext_row->rarity ;
		// Search an extension with pictures if given hasn't
		if  ( $ext_row->nbpics > 0 ) 
			$ext_img = $ext_row ;
		else {
			$ext_img = null ;
			foreach ( $this->extensions as $extension )
				if ( $extension->nbpics > 0 ) {
					$ext_img = $extension ;
					break ;
				}
		}
		if ( $ext_img == null )
			echo "No extension with image for {$this->name}\n" ;
		else {
			$result->ext_img = $ext_img->se ;
			if ( $ext_img->nbpics > 1 ) {
				if ( ( $pic_num < 1 ) || ( $pic_num > $ext_row->nbpics ) )
					$pic_num = rand(1, $ext_img->nbpics) ; // Random pic
				$result->attrs->nb = $pic_num ;
			}
		}
		return $result ;
	}
	public function line() {
		$result = "[{$this->ext}] {$this->name}" ;
		if ( property_exists($this->attrs, 'nb') )
			$result .= " ({$this->attrs->nb})" ;
		$result  .= "\n" ;
		return $result ;
	}
	// Search
	static $cache = array() ;
	static $occurences = 0 ;
	static function get($name, $ext='') {
		$name = card_name_sanitize($name) ;
		// Parse image number
		$pic_num = 0 ;
		if ( preg_match('/(.*) \((\d*)\)/', $name, $matches) ) {
			$name = $matches[1] ; // Remove it from name
			$pic_num = intval($matches[2]) ;
		}
		// Search in cache
		foreach ( Card::$cache as $card )
			if ( $card->name == $name )
				return $card->extend($ext, $pic_num) ;
		// Search in DB
		global $db_cards ;
		$name = $db_cards->escape($name) ;
		$cards = $db_cards->select("SELECT * FROM `card` WHERE `name` LIKE '$name'") ;
		if ( count($cards) > 1 ) { // Multiple cards found, bug
			echo count($cards)." cards found : $name\n" ;
		} else if ( count($cards) == 0 ) {
			// Transforms stored as "day/moon"
			$pieces = explode('/', $name) ;
			if ( count($pieces) > 1 ) {
				$name = $pieces[0] ;
				return Card::get(trim($name), $ext) ;
			}
			echo "Card not found : [$name]\n" ;
			return null ;
		}
		$card_obj = new Card($cards[0]) ;
		Card::$cache[] = $card_obj ;
		return $card_obj->extend($ext, $pic_num) ;
	}
	static function fill_cache() {
		Card::$cache = array() ;
		global $db_cards ;
		$raw = $db_cards->select("SELECT * FROM `card` ORDER BY `id` DESC") ;
		$links = 0 ;
		foreach ( $raw as $card ) {
			$card = new Card($card) ;
			foreach ( $card->extensions as $ext ) {
				$links++ ;
				$extension = Extension::get($ext->se) ;
				if ( $extension == null )
					echo "Extension {$ext->se} not found for {$card->name}\n" ;
				else
					$extension->add_card($card, $ext->rarity) ;
			}
			Card::$cache[] = $card ;
		}
		return $links ;
	}
}
