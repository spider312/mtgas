// player.js
// Base game class, declaring main game objects (game, players)
// https://developer.mozilla.org/En/DragDrop/Drag_Operations
/* game				(game)
	player,opponent		(player)
	creator,joiner		(player)
		life		(zone)
		hand		(zone)
		library		(zone)
		graveyard	(zone)
		exile		(zone)
		battlefield	(zone)
			cards	(array of cards)
			svg	(svg object)

Player : 
game.player is myself, game.opponent is my opponent, they are relative, and only concern local actions
game.creator is the player who created, game.joiner is the one who joined, they are absolute, and are used over network to identify a player server-side (for ajax)
*/
// Classes
function Game(id, options, player_id, player_nick, player_avatar, player_score, opponent_id, opponent_nick, opponent_avatar, opponent_score) {
	this.id = id ;
	this.options = options ;
	this.match_num = function() {
		return this.player.attrs.score + this.opponent.attrs.score ;
	}
	this.manacolors = Array('W', 'U', 'B', 'R', 'G', 'X') ;
	this.mananames = {'W': 'White', 'U': 'Blue', 'B': 'Black', 'R': 'Red', 'G': 'Green', 'X': 'Colorless'} ;
	this.image_cache = new image_cache() ;
	// Restrict access to opponent's cards and zones (Before players) ;
	this.restricted_access = restricted_access || spectactor ; // Access restriction system must be enabled in spectactor mode
	game = this ; // Workaround for old fashion "game" object declaration compatibility
	// Players
	this.widgets = [] ;
	this.player = new Player(this, false, player_id, player_nick, player_avatar, player_score) ;
	this.opponent = new Player(this, true, opponent_id, opponent_nick, opponent_avatar, opponent_score) ;
	// Registering
	this.player.opponent = this.opponent ;
	this.opponent.opponent = this.player ;
	if ( creator )
		this.creator = this.player ;
	else
		this.creator = this.opponent ;
	this.joiner = this.creator.opponent
	// Fake player for server
	this.server = new Object() ;
	this.server.name = 'Server' ;
	this.turn = new Turn(this) ;
	update_score() ;
	// Cards
	this.cards = new Array() ;
	// Tokens
	this.tokens = new Array() ;
	this.tokens_catalog = null ;
	$.getJSON('json/tokens.php', null, function(data) {
		game.tokens_catalog = data ;
	}) ;
	// Selection
	this.selected = new Selection() ;
	// Graphic elements
	this.arrows = new Array() ;
	// Sound
	this.sound = new Sound() ;
	// Targets
	this.target = new Targets() ;
	// Phase
	//this.phase = 0 ; // Initially there
	this.phase = 1 ; // Initially in start, run after game creation
	// Spectactors
	this.spectactors = [] ;
	this.hover = null ;
	// Canvas
	this.canvas = document.getElementById('paper') ;
	this.context = this.canvas.getContext('2d') ;
	resize_window() ;
	canvas_add_events(this.canvas) ;
	// Send stack
	this.action_stack = new Array() ;
	// Mana icons (all, not just pool ones)
	this.manaicons = new Array() ;
	// canvas title
	this.title = '' ;
	this.settittle = function(title) {
		if ( iss(title) )
			this.title = title ;
	}
	this.movedate = new Date() ;
	this.infobulle = new InfoBulle() ;
	// Options custom behaviour
	this.options.add_trigger('sounds', function(option) { if ( option.input.checked ) game.sound.loadall() ; }) ;
	this.options.add_trigger('invert_bf', function() { resize_window() ; }) ;
	this.options.add_trigger('display_card_names', function() { refresh_cards_in_selzone() ; draw() ; }) ;
	this.options.add_trigger('transparency', function() {
		refresh_cards_in_selzone() ;
		for ( var i in game.turn.steps )
			game.turn.steps[i].refresh() ;
		resize_window() ;
	}) ;
}
function Player(game, is_top, id, name, avatar, score) { // game as a param as it's not already a global
	// Methods
	this.toString = function() { // Access to this object as string will return a string containing global varname of this object
		// eval() it will return the object itself
		if ( this == game.creator )
			return 'game.creator' ;
		else 
			if ( this == game.joiner )
				return 'game.joiner' ;
			else 
				return 'null' ;
	}
	this.get_name = function() {
		if ( iss(this.name) )
			return this.name ;
		else
			return '' ;
	}
	this.lists_opened = function() {
		var result = 0 ;
		result += this.list_opened(this.hand) ;
		result += this.list_opened(this.battlefield) ;
		result += this.list_opened(this.library) ;
		result += this.list_opened(this.graveyard) ;
		result += this.list_opened(this.exile) ;
		result += this.list_opened(this.sideboard) ;
		return result ;
	}
	this.list_opened = function(zone) {
		if ( zone.editor_window != null )
			return 1 ;
		return 0 ;
	}
	this.focuslists = function() {
		var result = '' ;
		result += this.focuslist(this.hand) ;
		result += this.focuslist(this.battlefield) ;
		result += this.focuslist(this.library) ;
		result += this.focuslist(this.graveyard) ;
		result += this.focuslist(this.exile) ;
		result += this.focuslist(this.sideboard) ;
		return result ;
	}
	this.focuslist = function(zone) {
		if ( zone.editor_window )
			//zone.editor_window.focus() ;
			return zone.get_name()+' ' ;
		return '' ;
	}
	this.sync = function(callback) {
		// Send only difference between last synched attrs and current attrs
		var attrs = {} ;
		for ( i in this.attrs ) {
			if ( this.attrs[i] != this.sync_attrs[i] ) {
				// Workaround for the loop of siding synchronisation
				if ( ( i == 'siding' ) && ( this != game.player ) ) // I am the lone who can change my siding status
					continue ;
				attrs[i] = this.attrs[i] ;
			}
		}
		// Reclone for next synch
		this.sync_attrs = clone(this.attrs) ;
		if ( JSON.stringify(attrs) != '{}' )
			action_send('psync', {'player': this.toString(), 'attrs': attrs}, callback) ; // this.attrs for full sync, attrs for diff sync
		return attrs ;
	}
	this.sync_recieve = function(attrs) { // For each possible value, if it's from wanted type and its value changed, apply new value
		// Score
		if ( isn(attrs.score) && ( this.attrs.score != attrs.score ) ) { // Triggered on player which score changed, that is winner
			this.attrs.score = attrs.score ;
			this.sync_attrs = clone(this.attrs) ; // Don't wait 'til end of sync_recieve to mark as already sync, as sync will happend while changing "siding"
			message(this.name+' won match '+game.match_num(), 'win') ;
			if ( ( spectactor != 1 ) && ( this.attrs.score > this.score ) ) { // Only ask for side on real score change
				this.score = this.attrs.score ;
				side_start(game.player, this) ;
			}
			this.endgame() ;
		}
		// Side
		if ( isb(attrs.siding) && ( this.attrs.siding != attrs.siding ) ) {
			if ( attrs.siding )
				this.side_start_recieve() ;
			else
				this.side_stop_recieve() ;
		}
		// Life
		if ( isn(attrs.life) && ( this.attrs.life != attrs.life ) )
			this.life.setlife_recieve(attrs.life) ;
		// Poison
		if ( isn(attrs.poison) && ( this.attrs.poison != attrs.poison ) )
			this.life.setpoison_recieve(attrs.poison) ;
		// Damages
		if ( isn(attrs.damages) && ( this.attrs.damages != attrs.damages ) )
			this.life.damages_recieve(attrs.damages) ;
		// Library
		if ( isb(attrs.library_revealed) && ( this.attrs.library_revealed != attrs.library_revealed ) ) {
			if ( attrs.library_revealed )
				this.library.reveal_recieve() ;
			else
				this.library.unreveal_recieve() ;
		}
		this.sync_attrs = clone(this.attrs) ;
	}
	this.endgame = function() {
		update_score() ;
		// Remove tokens
		var tk = null ;
		while (	game.tokens.length > 0 )
			game.tokens[0].del() ;
	}
	this.win = function() {
		this.attrs.score++ ;
		this.score = this.attrs.score ;
		message(this.name+' won match '+game.match_num(), 'win') ;
		var player = this ;
		this.sync(function(param) {
			// Only delays side start, hoping an action recieve/manage will trigger before response, as it will redirect player if round started
			// before asking him for side
			side_start(game.player, player) ;
		}) ;
		this.endgame() ;
	}
	// Side messages and changezone message hiding
	this.side_start = function() {
		this.side_start_recieve() ;
		this.sync() ;
	}
	this.side_stop = function() {
		this.side_stop_recieve() ;
		this.sync() ;
	}
	this.side_start_recieve = function() {
		this.attrs.siding = true ;
		message(this.name+' starts siding', 'side') ;
	}
	this.side_stop_recieve = function() {
		this.attrs.siding = false ;
		message(this.name+' finished siding', 'side') ;
	}
	this.access = function() {
		if ( spectactor )
			return false ;
		return this.me || ! game.restricted_access ;
	}
	// Game helpers
	this.controls = function(obj) {
		var res = 0 ;
		for ( var i = 0 ; i < this.battlefield.cards.length ; i++ ) { // Each card on BF
			var card = this.battlefield.cards[i] ;
			for ( var j in obj ) { // Each attr in param
				if ( iso(card.attrs[j]) ) { // Searched field is an array, check each of its values
					for ( var k in card.attrs[j] )
						if ( card.attrs[j][k] == obj[j] ) {
							res++ ;
							break ;
						}
				} else
					if ( card[j] == obj[j] )
						res++ ;
			}
		}
		return res
	}
	// Init
	this.init = function() {
		// Modify all params in local then sync
		this.life.setlife_recieve(20) ;
		this.life.setpoison_recieve(0) ;
		this.life.damages_recieve(0) ;
		this.attrs.library_revealed = false ;
		this.attrs.draw = 1 ;
		this.attrs.untap_lands = true ;
		this.attrs.untap_creatures = true ;
		this.attrs.untap_all = true ;
		// Empty mana pools
		this.manapool.empty() ;
		this.sync() ;
	}
	// Who am i absolutely
	this.id = id ;
	this.name = name ;
	this.avatar = avatar ;
	this.draw = new Array() ;
	this.attrs = new Object() ;
	// Who am i relative to server
	this.is_top = is_top ;
	if ( spectactor )
		this.me = false ;
	else
		this.me = ( ! is_top ) || goldfish ;
	this.other = ! this.me ;
	this.score = score ;
	// Attributes (data synced)
	this.attrs.score = 0 ; // In order to manage scores change
	//this.init() ; // Will check existence of previous values, but none were defined
	this.attrs.life = 20 ;
	this.attrs.poison = 0 ;
	this.attrs.damages = 0 ;
	this.attrs.library_revealed = false ;
	this.attrs.draw = 1 ;
	this.attrs.untap_lands = true ;
	this.attrs.untap_creatures = true ;
	this.attrs.untap_all = true ;
	this.attrs.siding = false ;
	this.sync_attrs = clone(this.attrs) ;
	// Zones
	this.life = new Life(this) ;
	this.battlefield = battlefield(this) ;
	this.hand = hand(this) ;
	this.library = library(this) ;
	this.graveyard = graveyard(this) ;
	this.exile = exile(this) ;
	this.sideboard = sideboard(this) ;
	this.manapool = new Manapool(game, this) ;
	// Default visibility (3 states : true = force visible, false = force hidden, null = default behaviour for zone
	this.library.default_visibility = false ; // Library is private
	this.hand.default_visibility = this.me ; // Self/goldfish hand is public, opponent's one is private
	this.life.default_visibility = true ; // All other are public
	this.battlefield.default_visibility = true ;
	this.graveyard.default_visibility = true ;
	this.exile.default_visibility = true ;
	this.sideboard.default_visibility = false ; // No preloading, seeing those cards will be done by other mechanisms (listeditor, side)
}
function Sound() {
	// Sounds
	this.sounds = {} ;
	this.toString = function() {
		return 'game.sound' ;
	}
	this.load = function createSound(name, src) {
		if ( this.sounds[name] == undefined ) {
			var el = document.createElement('audio');
			this.sounds[name] = el ;
			// el.autobuffer = true ; // Gecko < 2.0
			el.preload = 'auto' ;
			el.src = src ;
		}
	}
	this.play = function(target) {
		if ( game.options.get('sounds') ) {
			if ( this.sounds[target] )
				this.sounds[target].play() ;
			else
				log('Sound "'+target+'" doesn\'t exists') ;
		}
	}
	this.loadall = function() {
		this.load('click', url+'/themes/'+theme+'/Sounds/click.wav') ;
		this.load('draw', url+'/themes/'+theme+'/Sounds/draw.wav') ;
		this.load('endturn', url+'/themes/'+theme+'/Sounds/endturn.wav') ;
		this.load('move', url+'/themes/'+theme+'/Sounds/move.wav') ;
		this.load('shuffle', url+'/themes/'+theme+'/Sounds/shuffle.wav') ;
		this.load('tap', url+'/themes/'+theme+'/Sounds/tap.wav') ;
	}
	if ( game.options.get('sounds') )
		this.loadall() ;
}
// Lib
function update_score() {
	var match = 1 + game.player.attrs.score + game.opponent.attrs.score ;
	var txt = 'Match '+match+' : '+game.player.attrs.score+' - '+game.opponent.attrs.score
	if ( tournament > 0 )
		txt = 'Round '+round+', '+txt
	var info = document.getElementById('info') ;
	node_empty(info) ;
	info.appendChild(document.createTextNode(txt)) ;
}
