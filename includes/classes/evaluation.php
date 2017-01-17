<?php

class Evaluation {
// One evaluation
	public $from ;
	public $to ;
	public $rating ;
	public $date ;
	public function __construct($from, $to, $rating, $date = null) {
		$this->from   = $from ;
		$this->to     = $to ;
		$this->rating = intval($rating) ;
		$this->date   = $date ;
		// Indexes
		Evaluation::$cache[] = $this ; // Main cache
		Evaluation::set_by_to($this) ; // Indexed by $to
		Evaluation::set_average($to) ; // Updates average cache for $to
	}
	// Checks existence in DB and insert or update accordingly
	public function store() {
		global $db ;
		$from = "`from` = '{$this->from}' AND `to` = '{$this->to}'" ;
		$prev = $db->select("SELECT * FROM `evaluation` WHERE $from") ;
		if ( count($prev) === 0 ) { // Not existing : create
			$db->insert("INSERT INTO `evaluation` ( `from`, `to`, `rating` )
				VALUES ( '{$this->from}', '{$this->to}', '{$this->rating}' ) ") ;
		} else if ( intval($prev[0]->rating) !== $this->rating ) { // Already existing and value changed
			$db->update("UPDATE `evaluation` SET `rating` = '{$this->rating}' WHERE $from") ;
		}
		/*
		// Update date
		$prev = $db->select("SELECT date FROM `evaluation` WHERE $from") ;
		$this->date = $prev[0]->date ;
		*/
		Evaluation::set_average($this->to) ;
	}
// List of evaluations
	static $cache = array();
	// Fill cache with all data in DB
	static function fill() {
		global $db ;
		$raw = $db->select("SELECT * FROM `evaluation`") ;
		foreach ( $raw as $e )
			$new = new Evaluation($e->from, $e->to, $e->rating, $e->date) ; // Will self index
		return $raw ;
	}
	// Returns an array of each evaluation matching $to and $from
	static function get($to = null, $from = null) { // 'to' is before 'from' because we'll often want to get by 'to'
		$result = array() ;
		$source = Evaluation::$cache ; // No search on 'to'
		// Search 'to'
		if ( $to !== null ) { // Search on 'to' by index
			$source = Evaluation::get_by_to($to) ;
		}
		// Search 'from'
		if ( $from === null ) {
			$result = $source ;
		} else {
			foreach ( $source as $evaluation ) {
				if ( $evaluation->from === $from ) {
					array_push($result, $evaluation) ;
				}
			}
		}
		return $result ;
	}
	static function get_rating($to = null, $from = null) {
		$result = self::get($to, $from) ;
		if ( count($result) > 0 ) {
			return $result[0]->rating ;
		} else {
			return 0 ;
		}
	}
	// Creates a new or updates an existing evaluation, then stores to db
	static function set($from, $to, $rating) {
		$prev = Evaluation::get($to, $from) ;
		if ( count($prev) === 0 ) {
			$new = new Evaluation($from, $to, $rating) ;
		} else {
			$new = $prev[0] ;
			$new->rating = $rating ;
		}
		$new->store() ;
	}
// Indexed by 'to'
	static $index_to = array() ;
	// Get all evaluations for $to (in indexed)
	static function get_by_to($to = '') {
		if ( array_key_exists($to, Evaluation::$index_to) ) {
			return Evaluation::$index_to[$to] ;
		}
		return array() ;
	}
	// Index for $evaluation (creating index array or pushing it)
	static function set_by_to($evaluation) {
		if ( array_key_exists($evaluation->to, Evaluation::$index_to) ) {
			array_push(Evaluation::$index_to{$evaluation->to}, $evaluation) ;
		} else {
			Evaluation::$index_to[$evaluation->to] = array($evaluation) ;
		}
	}
// Averages by 'to'
	static $average_to = array() ;
	// Returns an array [sum of ratings, number of ratings] for $to
	static function average($to) {
		$evaluations = Evaluation::get_by_to($to) ;
		$nb = count($evaluations) ;
		$rating = 0 ;
		if ( $nb > 0 ) {
			foreach ( $evaluations as $evaluation ) {
				$rating += $evaluation->rating ;
			}
		}
		return array($rating, $nb) ;
	}
	// Store in cache average() for $to
	static function set_average($to) {
		Evaluation::$average_to[$to] = Evaluation::average($to) ;
	}
	// Get from cache average() for $to
	static function get_average($to) {
		if ( array_key_exists($to, Evaluation::$average_to) ) {
			return Evaluation::$average_to[$to] ;
		}
		return array(0, 0) ;
	}
}
?>
