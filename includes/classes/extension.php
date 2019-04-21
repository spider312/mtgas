<?php
require_once __dir__ . '/../../includes/db.php' ;
require_once __dir__ . '/../../includes/classes/card.php' ;
class Extension {
	public $id ;
	public $se ;
	public $sea ;
	public $name ;
	public $priority ;
	public $release_date ;
	public $bloc ;
	public $uniq ;
	public $data ;
	public $cards_nb = 0 ;
	private $cards = array() ; // All cards
	private $cards_rarity = array() ; // Cards grouped by rarity
	private $cards_apart = array() ; // Apart cards won't be included in normal booster generation, but added with own rules (Transform, forced legendary or planeswalker)
	static $cache = array() ;
	public function __construct($ext) { // Recieves a line from DB
		$this->id = $ext->id ;
		$this->se = $ext->se ;
		$this->sea = $ext->sea ;
		$this->name = $ext->name ;
		$this->priority = $ext->priority ;
		$this->release_date = $ext->release_date ;
		$this->bloc = $ext->bloc ;
		$this->uniq = $ext->uniq ;
		$this->data = json_decode($ext->data) ;
	}
	public function add_card($card, $rarity='') {
		if ( $card == null )
			return false ;
		$nb = 1 ;
		if ( $card->name === 'Wastes' )
			$nb = 2 ;
		for ( $i = 0 ; $i < $nb ; $i++ ) {
			$this->cards[] = $card ;
			$this->cards_nb = count($this->cards) ;
			if ( 
				( // Transform
					(
						$this->get_data('transform', false) 
						|| $this->get_data('transform2', false) 
					)
					&& property_exists($card->attrs, 'transformed_attrs')
				)
				||
				( // Forced planeswalker
					$this->get_data('planeswalker', false)
					&& ( array_search('planeswalker', $card->attrs->types) !== false )
				)
			) {
				$dest =& $this->cards_apart ;
			} else {
				$dest =& $this->cards_rarity ;
			}
			if ( $rarity == '' )
				$rarity = $card->rarity ;
			if ( ! array_key_exists($rarity, $dest) )
				$dest[$rarity] = array() ;
			$dest[$rarity][] = $card ;
		}
	}
	private function get_cards() { // Import card list from DB and dispatch by rarity/transformability
		if ( count($this->cards) > 0 )
			return false ;
		if ( $this->get_data('all', false) ) {
			$this->cards = Card::$cache ;
			$this->cards_rarity['C'] = $this->cards ;
			//$cards = $db_cards->select("SELECT `card`.`name` FROM `card` ORDER BY `card`.`id` ASC") ;
		} else {
			global $db_cards ;
			$cards = $db_cards->select("SELECT `card`.`name`
			FROM `card_ext`, `card`
			WHERE
				`card_ext`.`card` = `card`.`id` AND
				`card_ext`.`ext` = {$this->id}
			ORDER BY `card`.`id` ASC") ;
			foreach ( $cards as $card )
				$this->add_card(Card::get($card->name, $this->se)) ;
		}
	}
	// Get a property of data object or a default value
	private function get_data($property, $value) {
		if ( property_exists($this->data, $property) ) {
			return $this->data->$property ;
		} else {
			return $value ;
		}
	}
	// Randomly adds on card from $from to $booster, if not already containing it
	// checks upool unicity if extension data asks for
	// Don't do checkings for a foil
	private function rand_card($from, &$booster, &$upool, $foil=false) {
		shuffle($from) ; // Shuffle instead of random, allowing for easier unicity checkings
		foreach ( $from as $card ) {
			if ( ( ! $foil ) && card_in_booster($card, $booster) ) { // Booster unicity
				continue ;
			}
			if ( $this->get_data('uniq', false) ) { // Pool unicity
				if ( card_in_booster($card, $upool) ) {
					continue ;
				} else {
					$upool[] = $card ; // Add card to pool unicity cache
				}
			}
			// Passed unicity checkings
			$result = $card->extend($this->se) ; // Select current extension for card
			array_unshift($booster, $result) ; // Generated in reverse order
			return true ;
		}
		// No card in source passed unicity checkings (or source is empty)
		echo "No card to add\n" ;
		return false ;
	}
	// Generate a booster : returns a list of randomly chosen cards which properties are defined in extension data
	// Booster is generated in reverse order, as random cards (normal foils, masterpieces) take a common slot
	public function booster(&$upool) { // upool is current user pool, for unicity
		// Make sure cache is up2date
		$this->get_cards() ;
		// Get booster params from extension
			// Base cards
		$nb_c = $this->get_data('c', 0) ; // Commons
		$nb_u = $this->get_data('u', 0) ; // Uncos
		$nb_r = $this->get_data('r', 0) ; // Rares / Mythics
		$nb_l = $this->get_data('l', 0) ; // Base lands
			// Exception cards
		$timeshift = $this->get_data('timeshift', false) ; // Timeshifted (for TSP)
		$transform = $this->get_data('transform', false)  ; // Transformable (for ISD/DKA)
		$transform2 = $this->get_data('transform2', false) ; // Transformable, 2nd wave (SOI)
		$foil = $this->get_data('foil', 0) ; // Forced foils (Modern Masters)
		$mps = $this->get_data('mps', '') ; // Masterpieces
		$planeswalker = $this->get_data('planeswalker', false) ;
		// Init
		$result = array() ; // Generated booster's cards
		$foil_able = true ; // A booster may only have one foil added (forced foil, masterpiece, normal foil), keep a track of this
		// Last cards : Lands
		if ( array_key_exists('L', $this->cards_rarity) ) {
			for ( $i = 0 ; $i < $nb_l ; $i++ ) {
				$this->rand_card($this->cards_rarity['L'], $result, $upool) ;
			}
		} else {
			$nb_c += $nb_l ;
		}
		// Foils
			// Forced foil (Modern Masters)
		if ( $foil_able && ( $foil > 0 ) ) {
			for ( $i = 0 ; $i < $foil ; $i++ ) {
				$this->rand_card($this->cards, $result, $upool, true) ;
			}
			$foil_able = false ;
		}
			// Masterpieces
		if ( $foil_able && ( $mps !== '' ) && ( $nb_c > 0 ) ) {
			global $proba_masterpiece ;
			if ( rand(1, $proba_masterpiece) == 1 ) {
				$ext = Extension::get($mps) ;
				$ext->get_cards() ;
				if ( array_key_exists('S', $ext->cards_rarity) ) {
					$nb_c-- ;
					$foil_able = false ;
					$this->rand_card($ext->cards_rarity['S'], $result, $upool, true) ;
				}
			}
		}
			// Normal foil
		if ( $foil_able && ( $nb_c > 0 ) ) {
			global $proba_foil ;
			if ( rand(1, $proba_foil) == 1 ) {
				$nb_c-- ;
				$foil_able = false ;
				$this->rand_card($this->cards, $result, $upool, true) ;
			}
		}
		// Exceptions
		if ( $timeshift ) {
			$ext = Extension::get('TSB') ;
			$ext->get_cards() ;
			if ( array_key_exists('S', $ext->cards_rarity) ) {
				$this->rand_card($ext->cards_rarity['S'], $result, $upool) ;
			} else {
				$nb_c++ ;
			}
		} 
		if ( $transform ) {
			$r = '' ; // Transform rarity
			$n = rand(1, 14) ;
			if ( $n > 13 )		$r = Extension::r_or_m($this->cards_apart) ;
			elseif ( $n > 10 )	$r = 'U' ;
			else				$r = 'C' ;
			if ( array_key_exists($r, $this->cards_apart) ) {
				$this->rand_card($this->cards_apart[$r], $result, $upool) ;
			} else {
				$nb_c++ ;
			}
		}
		if ( $transform2 ) {
			// Each booster contains 1 transform, common or uncommon
			$tr_c = array_merge($this->cards_apart['C'], $this->cards_apart['U']) ;
			$this->rand_card($tr_c, $result, $upool) ;
			// Once over 8 boosters, 1 uncommon is replaced by 1 transform, rare or mythic
			if ( ( rand(1, 8) == 1 ) && ( $nb_u > 0 ) ) {
				$nb_u-- ;
				$tr_r = array_merge($this->cards_apart['R'], $this->cards_apart['R'], $this->cards_apart['M']) ; // Rares appears twice than mythics
				$this->rand_card($tr_r, $result, $upool) ;
			}
		}
		// Planeswalker
		if ( $planeswalker ) {
			// Combining all rarities PW in one array
			$pws = array() ;
			foreach ( $this->cards_apart as $apart ) {
				$pws = array_merge($pws, $apart) ;
			}
			// Add one random card from this array
			$this->rand_card($pws, $result, $upool) ;
			// Remove one card of the rarity from normal booster generation
			switch ( $result[0]->rarity ) {
				case 'M' :
				case 'R' :
					$nb_r-- ;
					break ;
				case 'U' :
					$nb_u-- ;
					break ;
				default :
					$nb_c-- ;
			}
		}
		// Normal generation
			// Rare or Mythic
		if ( array_key_exists('R', $this->cards_rarity) || array_key_exists('M', $this->cards_rarity) ) {
			for ( $i = 0 ; $i < $nb_r ; $i++ ) {
				$this->rand_card($this->cards_rarity[Extension::r_or_m($this->cards_rarity)], $result, $upool);
			}
		} else {
			$nb_c += $nb_r ;
		}
			// Uncommons
		if ( array_key_exists('U', $this->cards_rarity) ) {
			for ( $i = 0 ; $i < $nb_u ; $i++ ) {
				$this->rand_card($this->cards_rarity['U'], $result, $upool) ;
			}
		} else {
			$nb_c += $nb_u ;
		}
			// Commons (after all other exceptions have been managed)
		if ( array_key_exists('C', $this->cards_rarity) && (count($this->cards_rarity['C']) >= $nb_c) ) {
			for ( $i = 0 ; $i < $nb_c ; $i++ ) {
				$this->rand_card($this->cards_rarity['C'], $result, $upool) ;
			}
		} else {
			if ( $nb_c > 0 ) {
				echo 'Not enough commons leftin ext '.$this->se." ($nb_c/".count($cards['C']).")\n" ;
			}
		}
		// Manage "guilds" (preview boosters specific to a guild)
		$guilds = $this->get_data('guilds', null) ;
		if ( $guilds !== null ) {
			$guild = $guilds[rand(0, count($guilds)-1)] ;
			$ext = Extension::get($guild) ;
			if ( $ext === null ) {
				echo 'Guild extension not found : '.$this->se.'/'.$guild."\n" ;
			} else {
				$guildboost = $ext->booster($upool) ;
				$result = array_merge($result, $guildboost) ;
			}
		}
		return $result ;
	}
	static function r_or_m($cards) {
		global $proba_m ;
		if ( ! array_key_exists('M', $cards) || count($cards['M']) == 0 ) { // No mythics
			if ( ( ! array_key_exists('R', $cards) ) || ( count($cards['R']) == 0 ) ) // And no rares
				return 'S' ; // TSB
			else
				return 'R' ;
		}
		if ( ! array_key_exists('R', $cards) || count($cards['R']) == 0 ) // No rares
			return 'M' ;
		// Rares and Mythics
		if ( rand(1, $proba_m) == 1 )
			return 'M' ;
		return 'R' ;
	}
	static function get($name) {
		foreach (Extension::$cache as $extension)
			if ( $extension->se == $name )
				return $extension ;
		global $db_cards ;
		$name = $db_cards->escape($name) ;
		$exts = $db_cards->select("SELECT * FROM `extension` WHERE `se` LIKE '$name'") ;
		if ( count($exts) > 1 ) // Multiple cards found, bug
			echo count($exts)." extensions found : $name\n" ;
		else if ( count($exts) == 0 ) {
			echo "Extension not found : $name\n" ;
			return null ;
		}
		$ext = new Extension($exts[0]) ;
		Extension::$cache[] = $ext ;
		return $ext ;
	}
	static function fill_cache() {
		Extension::$cache = array() ;
		global $db_cards ;
		$raw = $db_cards->select("SELECT * FROM `extension` ORDER BY release_date DESC, name ASC") ;
		foreach ( $raw as $ext )
			Extension::$cache[] = new Extension($ext) ;
		return $raw ;
	}
}
function card_in_booster($card, $booster) {
	foreach ( $booster as $currcard  )
		if ( $card->name == $currcard->name )
			return true ;
	return false ;
}
