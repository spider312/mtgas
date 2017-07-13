<?php
// Base includes
require_once 'includes/lib.php' ;
require_once 'includes/db.php' ;
require_once 'includes/mojosto.php' ;
require_once 'includes/card.php' ;
require_once 'includes/ranking.php' ;
require_once 'includes/ts3.php' ;
require_once 'includes/classes/evaluation.php' ;
// Websocket objects
require_once 'includes/classes/ws_ban.php' ;
require_once 'includes/classes/ws_game.php' ;
require_once 'includes/classes/ws_tournament.php' ;
require_once 'includes/classes/ws_registration.php' ;
require_once 'includes/classes/ws_booster.php' ;
require_once 'includes/classes/ws_spectator.php' ;
// Handlers
require_once 'includes/classes/ws_parent_handler.php' ; // Parent for all handlers
require_once 'includes/classes/ws_index_handler.php' ;
require_once 'includes/classes/ws_tournament_handler.php' ; // Parent for tournament handlers
require_once 'includes/classes/ws_tournament_index_handler.php' ;
require_once 'includes/classes/ws_limited_handler.php' ; // Parent for limited handlers
require_once 'includes/classes/ws_draft_handler.php' ;
require_once 'includes/classes/ws_build_handler.php' ;
require_once 'includes/classes/ws_game_handler.php' ;
require_once 'includes/classes/ws_admin_handler.php' ;
// External
require_once 'includes/classes/class.algoritm1.php' ; // Generate rounds

// Object cenralizing MOGG data and each system object used in websocket server
class GameServer {
	// Interface
	public $loop = null ; // public because tournament object adds timers
	private $server = null ;
	private $logger = null ;
	// Handlers
	public $index = null ;
	public $draft = null ;
	public $build = null ;
	public $tournament = null ;
	public $game = null ;
	// Parameters
	public $ts3 = false ;
	// MTG data
	public $tokens = array() ;
	// MOGG data
	public $suggest_draft = array() ;
	public $suggest_sealed = array() ;
	public $pending_duels = array() ;
	public $joined_duels = array() ;
	public $pending_tournaments = array() ;
	public $running_tournaments = array() ;
	public $ended_tournaments = array() ;

	public function __construct($wsport){
		$this->say('Creating server') ;
		// Logger (required for handlers)
		$this->logger = new \Zend\Log\Logger();
			// Log PHP errors
		Zend\Log\Logger::registerErrorHandler($this->logger);
			// Writes to stdout
		$writer = new \Zend\Log\Writer\Stream("php://output");
		$this->logger->addWriter($writer);
			// Filter log messages
		/*
		Possible values : EMERG, ALERT, CRIT, ERR, WARN, NOTICE, INFO, DEBUG
		Errors are displayed in a normal use case in WARN and ERR levels, that's why CRIT is the chosen one
		*/
		$filter = new Zend\Log\Filter\Priority(\Zend\Log\Logger::CRIT);
		$writer->addFilter($filter);
			// Also log to a file
		//$writer2 = new Zend\Log\Writer\Stream('/path/to/logfile');
		//$this->logger->addWriter($writer2);
		//$writer2->addFilter($filter);
		// WebSocket server
		$this->loop = \React\EventLoop\Factory::create();
		$this->server = new \Devristo\Phpws\Server\WebSocketServer("tcp://0.0.0.0:$wsport",
			$this->loop, $this->logger);
		// Handlers
		$this->index = new IndexHandler($this->logger, $this, 'index') ;
		$this->tournament = new TournamentIndexHandler($this->logger, $this, 'tournament') ;
		$this->draft = new DraftHandler($this->logger, $this, 'draft') ;
		$this->build = new BuildHandler($this->logger, $this, 'build') ;
		$this->game = new GameHandler($this->logger, $this, 'game') ;
		$this->admin = new AdminHandler($this->logger, $this, 'admin') ;
		$this->handlers = array('index', 'tournament', 'draft', 'build', 'game', 'admin') ;
		// Routes
		$router = new \Devristo\Phpws\Server\UriHandler\ClientRouter($this->server,$this->logger);
		$router->addRoute('#^/index#i', $this->index);
		$router->addRoute('#^/game#i', $this->game);
		$router->addRoute('#^/tournament#i', $this->tournament);
		$router->addRoute('#^/draft#i', $this->draft);
		$router->addRoute('#^/build#i', $this->build);
		$router->addRoute('#^/admin#i', $this->admin);
		// Params
		global $ts3 ;
		$this->ts3 = $ts3 ;
		$this->say('Server created') ;
	}
	public function import() {
		$this->import_mtg() ;
		$this->import_mogg() ;
	}
	public function import_mtg() {
		$this->say("\tBegin MTG import") ;
		Extension::fill_cache() ;
		$this->say("\t\t".count(Extension::$cache).' extensions imported') ;
		$links = Card::fill_cache() ;
		$this->say("\t\t".count(Card::$cache).' cards, '.$links.' links imported');
		$this->import_tokens() ;
		$this->import_suggestions() ;
	}
	public function import_tokens() {
		// Token images
		$exts = array_reverse(Extension::$cache) ;
		// Files (for tokens existence)
		global $base_image_dir ;
		$tokendirs = scan($base_image_dir.'/MIDRES/TK/') ;
		// Processing (list all existing tokens ordered by database)
		$nbtoken = 0 ;
		$orderedtokens = array() ;
		foreach ( $exts as $obj )
			if ( isset($tokendirs[$obj->se]) ) {
				$orderedtokens[$obj->se] = $tokendirs[$obj->se] ;
				$nbtoken += count($tokendirs[$obj->se]) ;
				unset($tokendirs[$obj->se]) ;
			}
		if ( isset($tokendirs['EXT']) ) { // Fallback tokens without specific extension
			$orderedtokens['EXT'] = $tokendirs['EXT'] ;
			$nbtoken += count($tokendirs['EXT']) ;
			unset($tokendirs['EXT']) ;
		}
		$this->tokens = $orderedtokens ;
		$this->say("\t\t$nbtoken tokens listed") ;
		$this->say("\tEnd MTG import") ;
	}
	public function import_suggestions() {
		global $db ;
		$this->suggest_draft = $db->select("SELECT `name`, `value` FROM `config` WHERE `cluster` = 'suggest_draft' ORDER BY `position`") ;
		$this->say("\t\t".count($this->suggest_draft).' draft suggestions imported') ;
		$this->suggest_sealed = $db->select("SELECT `name`, `value` FROM `config` WHERE `cluster` = 'suggest_sealed' ORDER BY `position`") ;
		$this->say("\t\t".count($this->suggest_sealed).' sealed suggestions imported') ;
	}
	public function import_mogg() {
		$this->say("\tBegin MOGG import") ;
		$this->bans = new Bans($this) ;
		$this->say("\t\t".count($this->bans->list).' bans imported') ;
		global $db ;
			// Running games
		$this->joined_duels = array() ;
		foreach ( $db->select("SELECT id FROM `round`
			WHERE
				`status` = '3' AND `tournament` = '0'
				AND TIMESTAMPDIFF(MINUTE, `last_update_date`, NOW()) < 10
			ORDER BY `id` ASC") as $duel ) {
			$g = Game::get($duel->id) ;
			if ( $g != null ) {
				$g->type = 'joineduel' ; // JSON communication
				$this->joined_duels[] = $g ;
			}
		}
		$this->say("\t\t".count($this->joined_duels).' running duels imported') ; 
			// Running tournaments
		foreach ( $db->select("SELECT `id` FROM `tournament`
			WHERE `status` > '1' AND `status` < '6'	ORDER BY `id` ASC") as $tournament ) {
			Tournament::get($tournament->id) ; // Will add it itself to corresponding list
		}
		$this->say("\t\t".count($this->running_tournaments).' running tournaments imported') ;
			// Evaluations
		Evaluation::fill() ;
		$this->say("\t\t".count(Evaluation::$cache).' evaluations imported') ;
		$this->say("\tEnd MOGG import") ;
		// Once all "fat" queries are finished, enable debug on DB
		$db->debug = true ;
	}
	public function export() { // Not called anymore, week & month are generated on tournament end and year and all are generated via crontab
		$this->say("\tBegin export") ;
		// Export
		ranking_to_file('ranking/week.json', 'WEEK') ;
		ranking_to_file('ranking/month.json', 'MONTH') ;
		ranking_to_file('ranking/year.json', 'YEAR') ;
		ranking_to_file('ranking/all.json', 'YEAR', 10) ;
		$this->say("\t\t".'Ranking exported') ;
		$this->say("\tEnd export") ;
	}
	public function start() {
		$this->check() ;
		$this->check_tournaments() ;
		// Bind the server
		$this->server->bind();
		// Start the event loop
		$this->say('Starting server') ;
		$this->loop->run();
	}
	// Checks
	public function check() { // Pings users on index
		global $index_timeout ;
		$observer = $this ;
		$this->loop->addTimer($index_timeout, function() use($observer) {
			$observer->index->check_users() ;
			$observer->game->check_users() ;
			Action::commit();
			$observer->check() ;
		}) ;
	}
	public function check_tournaments() { // Check user's connection status
		global $tournament_timeout ;
		$observer = $this ;
		$this->loop->addTimer($tournament_timeout, function() use($observer) {
			foreach ( $observer->running_tournaments as $tournament )
				foreach ( $tournament->get_players() as $player )
					if ( ( count($player->connected_prev) == 0 ) && ( count($player->connected) == 0 ) )
						$player->drop('Not connected') ;
					else
						$player->connected_prev = $player->connected ;
			$observer->check_tournaments() ;
		}) ;
	}
	// Log management
	public function emerg($msg) { $this->logger->log(Zend\Log\Logger::EMERG, $msg) ; }
	public function alert($msg) { $this->logger->log(Zend\Log\Logger::ALERT, $msg) ; }
	public function crit($msg) { $this->logger->log(Zend\Log\Logger::CRIT, $msg) ; }
	public function err($msg) { $this->logger->log(Zend\Log\Logger::ERR, $msg) ; }
	public function warn($msg) { $this->logger->log(Zend\Log\Logger::WARN, $msg) ; }
	public function notice($msg) { $this->logger->log(Zend\Log\Logger::NOTICE, $msg) ; }
	public function info($msg) { $this->logger->log(Zend\Log\Logger::INFO, $msg) ; }
	public function debug($msg) { $this->logger->log(Zend\Log\Logger::DEBUG, $msg) ; }
	public function say($msg) { // Fake log messages for when it doesn't exist
		echo date(DATE_ATOM).' SAY (-1): '.$msg."\n" ;
	}
	// Data accessors
		// Duels
	public function pending_duel($id) {
		foreach ( $this->pending_duels as $i => $duel )
			if ( $duel->id == $id )
				return $i ;
		return false ;
	}
	public function joined_duel($id) {
		foreach ( $this->joined_duels as $i => $duel )
			if ( $duel->id == $id )
				return $duel ;
		$duel = Game::get($id) ;
		if ( $duel == null )
			return false ;
		$duel->type = 'joineduel' ;
		$this->joined_duels[] = $duel ;
		return $duel ;
	}
	public function clean_duels($player_id) {
		$result = array() ;
		foreach ( $this->pending_duels as $i => $duel )
			if ( ( $duel->creator_id == $player_id ) || ( $duel->joiner_id == $player_id ) ) {
				$result[] = $duel->id ;
				array_splice($this->pending_duels, array_search($duel, $this->pending_duels), 1) ;
			}
		return $result ;
	}
	public function clean_duel($duel) {
		$i = array_search($duel, $this->joined_duels) ;
		if ( $i > -1 ) {
			$duels = array_splice($this->joined_duels, $i, 1) ;
			foreach ( $duels as $spliceduel )
				unset($spliceduel) ;
		} else
			$this->say('Joined duel not found : '.$duel->name) ;
	}
		// Tournament
	public function move_tournament($tournament, $from, $to) {
		$oto = $to ;
		$from .= '_tournaments' ;
		$to .= '_tournaments' ;
		// Base checks
		if ( ! property_exists($this, $from) ) {
			$this->say('Gameserver has no '.$from) ;
			return false ;
		}
		$idx = array_search($tournament, $this->{$from}, true) ;
		if ( $idx === false ) {
			$this->say($from.' has no tournament '.$tournament->id.' to move to '.$to) ;
			return false ;
		}
		$tournament->type = $oto.'_tournament' ;
		$spl = array_splice($this->{$from}, $idx, 1) ;
		if ( ! property_exists($this, $to) ) {
			$this->say('Gameserver has no '.$to) ;
			return false ;
		}
		$this->{$to}[] = $spl[0] ;
	}
}
