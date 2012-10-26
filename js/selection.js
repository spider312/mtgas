// Selection : list of selected cards
// There is 1 main selection, in game.selected, in order to keep cards selected in only 1 zone
// action_recieve can create selections in order to apply an action to multiple cards
function Selection() {
	// Object
		// Getters
	this.toString = function() {
		if ( this == game.selected ) 
			var txt = 'game.' ;
		else
			var txt = '' ;
		return txt+'Selection('+this.cards_names()+')' ;
	}
	this.cards_ids = function() {
		var arr = [] ;
		for ( var i = 0 ; i < this.cards.length ; i++ )
			arr.push(this.cards[i].id) ;
		return arr.join(',') ;
	}
	this.cards_names = function(oldzone) {
		var arr = [] ;
		var fd = 0 ;
		var c = 0 ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) { // Regroup cards by name
			var name = this.cards[i].get_name(oldzone) ;
			switch (name) {
				case 'a card'  : 
					c++ ;
					break ;
				case 'faced down card' :
					fd++ ;
					break ;
				default : 
					arr.push(name) ;
			}
		}
		if ( c > 0 )
			arr.push(c+' cards') ;
		if ( fd > 0 )
			arr.push(fd+' faced down cards') ;
		return arr.join(', ') ;
	}
		// Setters
	this.settype = function(type) {
		if ( iss(type) )
			this.type = type ;
		return this ;
	}
	// Modifying
	this.get_cards = function() {
		return this.cards.concat() ; // We have to clone this array, as calling method may modify it without refreshing modifications
	}
	this.isin = function(card) {
		var idx = this.cards.indexOf(card) ;
		return ( idx > -1 ) ;
	}
	this.clear = function() {
			while ( this.cards.length > 0 ) {
				var card = this.cards.pop() ;
				if ( ( this == game.selected ) && card.zone.selzone )
					card.refresh('selection clear') ;
			}
			this.zone = null ;
	}
	this.set = function() { // Set cards in parameter as the only ones in selection
		this.clear() ; // Clear old selection
		cards = arguments ;
		if ( cards.length > 0 )
			while ( isn(cards[0].length) )
				cards = cards[0] ;
		this.add.apply(this, cards) ; // Fill selection with cards in parameter
	}
	this.add = function() { // Add cards in parameter to selection
		for ( var i = 0 ; i < arguments.length ; i++ ) {
			var card = arguments[i] ;
			if ( this.cards.indexOf(card) == -1 ) { // If card not even in selection
				if ( card.zone != this.zone ) { // If card isn't in same zone than last selection
					this.clear() ; // Clear old selection
					this.zone = card.zone ; // Set selection in new zone
				}
				this.cards.push(card) ; // Add card to selection
				if ( ( this == game.selected ) && card.zone.selzone )
					card.refresh('selection add') ;
			}
		}
	}
	this.add_array = function(arr) { // Add cards in parameter to selection
		for ( var i = 0 ; i < arr.length ; i++ )
			this.add(arr[i]) ;
	}
	this.remove = function() { // Remove cards in parameter from selection
		for ( var i = 0 ; i < arguments.length ; i++ ) {
			var card = arguments[i] ;
			var idx = this.cards.indexOf(card) ;
			if ( idx > -1 ) {
				var cards = this.cards.splice(idx, 1) ;
				if ( ( this == game.selected ) && card.zone.selzone )
					cards[0].refresh('selection remove') ;
			}
		}
	}
	this.toggle = function() { // Toggles selected status of cards in parameter
		 // Build arrays of cards to add and cards to remove, then apply
		var toadd = [] ;
		var toremove = [] ;
		for ( var i = 0 ; i < arguments.length ; i++ ) {
			var card = arguments[i] ;
			if ( this.isin(card) )
				toremove.push(card) ;
			else
				toadd.push(card) ;
		}
		if ( toadd.length > 0 )
			this.add.apply(this, toadd) ;
		if ( toremove.length > 0 )
			this.remove.apply(this, toremove) ;
	}
	this.parse = function(txt) {
		var cards_split = txt.split(',') ;
		for ( var i in cards_split ) {
			var card = get_card(cards_split[i]) ;
			if ( card != null ) {
				// Sometimes parsed cards aren't allin the same zone
				if ( this.zone == null )
					this.zone = card.zone
				else
					if ( card.zone != this.zone )
						card.changezone(this.zone) ;
				var before = this.cards.length ;
				this.add(card) ;
				if ( before + 1 != this.cards.length )
					log('Error adding '+cards_split[i]+' ('+card+')') ;
			} else
				log('Card does not exists : '+cards_split[i]) ;
		}
	}
	// Actions management
	this.refresh = function(reason) {
		if ( isn(reason) )
			reason = 'selection.refresh()'
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			card.refresh(reason) ;
		}
	}
	this.sync = function(obj) { // Send
		action_send('sattrs', {'cards': this.cards_ids(), 'attrs': obj}) ; // this.attrs for full sync, attrs for diff sync
	}
	this.setattrs = function(attrs) { // Recieve
		// Tap
		if ( isb(attrs.tapped) )
			this.tap_recieve(attrs.tapped) ;
		// Attacking
		if ( isb(attrs.attacking) )
			this.attack_recieve(attrs.attacking) ;
		// P/T
		var pt = {'pow': 0, 'tou': 0} ;
		if ( isn(attrs.pow) )
			pt.pow = attrs.pow ;
		if ( isn(attrs.tou) )
			pt.tou = attrs.tou ;
		if ( ( pt.pow != 0 ) || ( pt.tou != 0 ) )
			this.add_powthou_recieve(pt.pow, pt.tou) ;
			// Until EOT
		var pteot = {'pow': 0, 'tou': 0} ;
		if ( isn(attrs.pow_eot) )
			pteot.pow = attrs.pow_eot ;
		if ( isn(attrs.tou_eot) )
			pteot.tou = attrs.tou_eot ;
		if ( ( pteot.pow != 0 ) || ( pteot.tou != 0 ) )
			this.add_powthou_eot_recieve(pteot.pow, pteot.tou) ;
		// Counters
		if ( isn(attrs.counter) )
			this.add_counter_recieve(attrs.counter) ;
		// Revealed from hand
		if ( isb(attrs.revealed) )
			this.reveal_from_hand_recieve(attrs.revealed) ;
	}
	// Actions
		// Pow / Tou
	this.add_powthou = function(pow, tou) {
		if ( ( pow != 0 ) || ( tou != 0 ) )
			if ( this.add_powthou_recieve(pow, tou) ) {
				var attrs = {} ;
				if ( pow != 0 )
					attrs.pow = pow ;
				if ( tou != 0 )
					attrs.tou = tou ;
				this.sync(attrs) ;
				return true ;
			}
		return false ;
	}
	this.add_powthou_recieve = function(pow, tou) {
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			card.add_powthou(pow, tou) ;
			card.refreshpowthou() ;
		}
		message(active_player.name+' gaves '+disp_int(pow)+'/'+disp_int(tou)+' to '+this.cards_names(), 'pow_thou') ;
		return true ;
	}
	this.add_powthou_eot = function(pow, tou) {
		if ( ( pow != 0 ) || ( tou != 0 ) )
			if ( this.add_powthou_eot_recieve(pow, tou) ) {
				var attrs = {} ;
				if ( pow != 0 )
					attrs.pow_eot = pow ;
				if ( tou != 0 )
					attrs.tou_eot = tou ;
				this.sync(attrs) ;
				return true ;
			}
		return false ;
	}
	this.add_powthou_eot_recieve = function(pow, tou) {
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			card.add_powthou_eot(pow, tou) ;
			card.refreshpowthou() ;
		}
		message(active_player.name+' gaves '+disp_int(pow)+'/'+disp_int(tou)+' until end of turn to '+this.cards_names(), 'pow_thou') ;
		return true ;
	}
		// Counters
	this.add_counter = function(nb) {
		if ( !isn(nb) ) {
			var i = 1 ;
			if ( nb == '-X' )
				i = -1 ;
			nb = prompt_int('How many counters to add for '+this.cards_names()+' ?', i) ;
		}
		if ( nb != 0 )
			if ( this.add_counter_recieve(nb) ) {
				this.sync({'counter': nb}) ;
				return true ;
			}
		return false ;
	}
	this.add_counter_recieve = function(nb) {
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			card.setcounter_recieve(card.getcounter() + nb) ;
		}
		if ( nb > 0 )
			message(active_player.name+' added '+nb+' counters on '+this.cards_names(), 'counter') ;
		else
			message(active_player.name+' removed '+(-nb)+' counters from '+this.cards_names(), 'counter') ;
		return true ;
	}
		// Attack
	this.attack_notap = function(refcard) {
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		var sel_a = new Selection() ; // Cards that will attack (creatures, not already attacking)
		if ( ! iso(refcard) )
			refcard = this.cards[0] ;
		var tapping = ! ( refcard.attrs.tapped || refcard.attrs.attacking ) ; // [un]taping and [un]attacking depending on double clicked card
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			if ( card.is_creature() ) // Creatures attack
				sel_a.add(card) ;
		}
		if ( sel_a.cards.length > 0 ) { // Some cards attacking
			if ( sel_a.attack_recieve(tapping) ) // Sync "attacking" status
				sel_a.sync({'attacking': tapping}) ;
		}

	}
	this.attack = function(refcard) {
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		var sel_a = new Selection() ; // Cards that will attack (creatures, not already attacking)
		var sel_t = new Selection() ; // Cards that will be [un]tapped (all except creatures with vigilance, except if untapping)
		if ( ! iso(refcard) )
			refcard = this.cards[0] ;
		var tapping = ! ( refcard.attrs.tapped || refcard.attrs.attacking ) ; // [un]taping and [un]attacking depending on double clicked card
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			if ( card.is_creature() && ( card.attrs.attacking != tapping ) ) // Creatures attack
				sel_a.add(card) ;
			if ( ( ! card.has_attr('vigilance') ) || ( ! tapping ) ) // All cards except vigilance creatures taps, except when untapping
				sel_t.add(card) ;
		}
		if ( sel_t.cards.length > 0 ) { // Some cards tapped
			if ( sel_t.tap_recieve(tapping) )
				sel_t.sync({'tapped': tapping}) ;
		}
		if ( sel_a.cards.length > 0 ) { // Some cards attacking
			if ( sel_a.attack_recieve(tapping) ) // Sync "attacking" status
				sel_a.sync({'attacking': tapping}) ;
		}
	}
	this.attack_recieve = function(b, silent) {
		b = ( b == true ) ;
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		for ( var i = 0 ; i < this.cards.length ; i++ )
			this.cards[i].attrs.attacking = b ;
		this.refresh('attacking') ;
		if ( ! silent )  {
			if ( b )
				var action = 'attacks with' ;
			else
				var action = 'removes from attackers' ;
			message(active_player.name+' '+action+' '+this.cards_names(), 'attack') ;
		}
		return true ;

	}
		// Tap
	this.tap = function(b) {
		b = ( b == true ) ;
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		result = this.tap_recieve(b) ;
		if ( result )
			this.sync({'tapped': b}) ;
		return result ;
	}
	this.tap_recieve = function(b) {
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		b = ( b == true ) ;
		for ( var i = 0 ; i < this.cards.length ; i++ )
			this.cards[i].attrs.tapped = b ;
		if ( b )
			var action = 'taps' ;
		else
			var action = 'untaps' ;
		message(active_player.name+' '+action+' '+this.cards_names(), 'tap') ;
		game.sound.play('tap') ;
		return true ;
	}
		// Reveal
	this.toggle_reveal_from_hand = function(card) {
		this.reveal_from_hand(!card.attrs.visible) ; // Selection visibility becomes opposite of card visibility
	}
	this.reveal_from_hand = function(b) {
		if ( this.reveal_from_hand_recieve(b) )
			this.sync({'revealed': b}) ;
	}
	this.reveal_from_hand_recieve = function(b) {
		if ( ! isb(b) )
			b = false ;
		var result = false ; // By default, consider this method has done nothing and won't sync
		if ( ! b ) // Will hide them
			var cardnames = this.cards_names() ; // Take their name now, as it will be 'a card' after the 'for'
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			if ( card.attrs.visible != b )  { // Not forced as true
				if ( b )
					card.set_visible(b) ; // Force as true
				else
					card.set_visible(null) ; // Set as default value : display depending on zone
				card.load_image() ; // Display image if in opponent's hand
				result = true ; // At least one card revealed, will sync
			}
		}
		if ( ! b )
			message(active_player.name+' hides from hand : '+cardnames, 'note') ;
		else
			message(active_player.name+' reveals from hand : '+this.cards_names(), 'note') ;
		return result ;
	}
		// Zone
	this.changezone = function(zone, visible, index, xzone, yzone) {
		if ( this.cards.length < 1 )
			return false ;
		// Automatically place
		if ( ! isn(xzone) )
			xzone = 0 ;
		if ( ! isn(yzone) )
			yzone = this.cards[0].place_row() ;
		// Client part
		result = this.changezone_recieve(zone, visible, index, xzone, yzone) ;
		// Server part
		if ( result ) {
			var attrs = {'cards': this.cards_ids(), 'zone': zone.toString()} ;
			if ( typeof visible == 'boolean')
				attrs.visible = visible ;
			if ( isn(index) )
				attrs.index = index ;
			// Transmit placement
			attrs.x = xzone ;
			attrs.y = yzone ;
			if ( iss(this.type) && ( this.type != '' ) )
				attrs.type = this.type ;
			action_send('zone', attrs) ;
		}
		return result ;
	}
	this.changezone_recieve = function(zone, visible, index, xzone, yzone) { 
		if ( this.cards.length < 1 )
			return false ;
		if ( typeof visible != 'boolean')
			visible = null ;
		if ( !isn(index) )
			index = null ;
		if ( !isn(xzone) )
			xzone = null ;
		if ( !isn(yzone) )
			yzone = null ;
		if ( zone == this.zone ) { // Changing from zone in which this is
			log('changezone from and to '+zone+' : '+this.cards_names()) ;
			for ( var i = 0 ; i < this.cards.length ; i++ ) {
				var card = this.cards[i] ;
				card.set_visible(visible) ; // Maybe we just wanted to change visibility (such as non side-out cards in side_do)
				card.load_image() ; // Load new image in case it changed
				card.sync() ; // Send attrs changes to opponent anyway
			}
			return false ; // Don't send changezone to opponent
		}
		var oldzone = this.zone ;
		this.zone = zone ; // All cards have been moved, inform selection that its zone changed
		var boost_bf = false ;
		var oldindex = 0 ; // Index of top moving card, used to distinguish drawn cards and tutored cards
		var errored = new Selection() ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			var idx = card.zone.cards.indexOf(card) ;
			if ( idx > oldindex )
				oldindex = idx ;
			card.watching = ( zone.editor_window != null ) // A list editor is opened on this zone, make the card watchable
			game.target.del_by_target(card) ;
			if ( ( oldzone.type == 'battlefield' ) && ( zone.type != 'battlefield' ) ) { // Leaving a BF & not going to a BF : preclean (detach)
				card.detach() ; // Needs .attrs which is changed in setzone
				// Detach all cards attached (after grid cleaning, as first attached will take its place)
				var attached = card.get_attached() ;
				while ( attached.length > 0 )
					attached[0].place_recieve() ; // Replace on BF
			}
			if ( oldzone != card.zone ) { // Typically, a conflict that happend when a player is moving a card while it's opponent is doing it (collision)
				log(card.id+' '+card.get_name()+' moving from '+card.zone+' to '+zone+' but should be in '+oldzone) ;
				//continue ; // And should be ignored // or NOT, seems to forbid a card to move "sometimes"
			}
			if ( card.zone.type == 'battlefield' ) { // Leaving a BF
				// Changing a token's zone (+duplicates), except for from BF to BF
				if ( ( card.type == 'token' ) && ( zone.type != 'battlefield' ) ) {
					//message(card.controler.name+' destroys '+card.get_name(), 'zone') ;
					card.del() ;
					continue ;
				}
				card.clean_battlefield() ; // Clean before setzone
				delete card.x ; // if going to another BF, setzone must not clean
				delete card.y ;
			}
			if ( ( card.zone.type == 'sideboard' ) || ( zone.type == 'sideboard' ) ) // Leaving sideboard or going to sideboard
				card.orig_zone = zone ; // Update "orig zone", used for future sideboards, working on recieve (such as F5)
			// Changing a card's zone (or a token from BF to BF)
			var xdest = xzone 
			var ydest = yzone ;
				// Add offsets for multiple drag'n'dropped cards
			if ( isn(card.xoffset) )
				xdest += card.xoffset ;
			if ( isn(card.yoffset) )
				ydest += card.yoffset ;
				// Set zone
			//if ( card.attrs.visible )
				//visible = true ; // If card was visible in previous zone, it keeps being
			if ( ! card.setzone(zone, visible, index, xdest, ydest) ) {
				log('Something went wrong in setzone('+zone+', '+index+', '+xzone+', '+yzone+'), reverting') ;
				errored.add(card) ;
				//card.setzone(oldzone, null, null, card.x, card.y) ; // If it failed, send back to previous zone
			}
			// Setzone succeded
			oldzone.cards.splice(oldzone.cards.indexOf(card),1) ; // Remove card from old zone
			switch ( oldzone.type ) {
				case 'library' : // Left library
					oldzone.player.draw.push([card, oldzone, zone]) ; // Save drawn cards to undo
					break ;
			}
		}
		//this.zone = zone ; // All cards have been moved, inform selection that its zone changed
		// On each changezone, refresh all powtou from all creat on all BF (only if they have to, such as tarmo, or if a card moving may affects them, such as goblin king)
		game.player.battlefield.refresh_pt() ;
		game.opponent.battlefield.refresh_pt() ;
		if ( isf(oldzone.refresh) ) // Side doesn't have
			oldzone.refresh() ; // At least one card was removed, refresh zone
		else
			log(game.opponent.get_name()+' sided '+this.cards.length+' cards') ;
		switch ( oldzone.type ) {
			case 'library' : // Left library
				if ( oldzone.player.attrs.library_revealed )
					oldzone.cards[oldzone.cards.length-1].load_image() ; // Refresh top card of library
				break ;
		}
		// Message display and sound
		var sound = '' ;
		if ( ! zone.player.attrs.siding ) { // No changezone messages while siding
			switch ( this.type ) {
				case 'mulligan':
					sound = 'draw' ;
					if ( this.cards.length == 7 )
						message(active_player.name+' draws its first hand', 'zone') ;
					else
						message(active_player.name+' mulligans @ '+this.cards.length, 'zone') ;
					break ;
				case 'dredge' :
					sound = 'draw' ;
					message(active_player.name+' dredges '+this.cards_names(oldzone)) ;
					break ;
				case 'suspend' :
					sound = 'move' ;
					message(active_player.name+' suspends '+this.cards_names(oldzone)) ;
					break ;
				case 'cycle' :
					sound = 'draw' ;
					message(active_player.name+' cycles '+this.cards_names(oldzone)) ;
					break ;
				default :
					var meaning = ['zone'] ;
					var fw = '' ;
					switch ( this.type ) {
						case 'undo' :
							fw += 'undoes ' ;
							// Swap for message in right order
							var tmp = zone ;
							zone = oldzone ;
							oldzone = tmp ;
							break ;
						case 'rand' :
							fw += 'randomly ' ;
							break ;
					}
					var cardname = this.cards_names(oldzone) ; // Save card names before changing zone, as it may change with attributes (transform)
					var word = 'moves '+cardname+' from '+oldzone.get_name()+' to '+zone.get_name() ;
					sound = 'move' ;
					if ( oldzone.player == zone.player )
						if ( zone.type == 'exile' )
								word = 'exiles '+cardname+' from '+oldzone.type ;
						else if ( zone.type == 'battlefield' ) {
							if ( oldzone.type == 'hand' ) {
								word = 'plays '+cardname ;
								for ( var i = 0 ; i < this.cards.length ; i++ ) // Brownish background if at least a land is played
									if ( this.cards[i].is_land() ) {
										meaning.push('land') ;
										break ;
									}
							} else if ( oldzone.type == 'graveyard' )
								word = 'reanimates  '+cardname ;
						} else if ( zone.type == 'hand' ) {
							if ( oldzone.type == 'battlefield' )
								word = 'bounces '+cardname ;
							else if ( ( oldzone.type == 'library' ) && ( ( oldindex >= oldzone.cards.length ) || ( this.type == 'undo' ) ) ) {
								word = 'draws '+cardname ;
								sound = 'draw' ;
							} else
								word = 'tutors '+cardname ;
						} else if ( zone.type == 'graveyard' ) {
							if ( oldzone.type == 'battlefield' )
								word = 'destroys '+cardname ;
							else if ( oldzone.type == 'hand' )
								word = 'discards '+cardname ;
						} else if ( zone.type == 'library' )
							word = 'moves '+cardname+' from '+oldzone.get_name()+' '+card.position_name('to', 'to') ;
					message(active_player.name+' '+fw+word, meaning) ;
			}
		}
		if ( sound != '' )
			game.sound.play(sound) ;
		errored.changezone(oldzone) ;
		return true ;
	}
	this.moveinzone = function(to_index) { // Change a card's index inside its own zone
		if ( this.cards.length < 1 )
			return false ;
		if ( ! isn(to_index) )
			to_index = this.zone.cards.length ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			card = this.cards[i] ;
			if ( card.zone != this.zone ) { // Unsynchro
				log(card.name+'('+card.zone+') should be in '+this.zone) ;
				continue ;
			}
			var from_index = card.IndexInZone() ;
			if ( to_index != from_index ) {
				card.zone.cards.splice(from_index, 1) ; // Extract that card from where it is in array
				card.zone.cards.splice(to_index, 0, card) ; // Insert it where it should go
			}
		}
		this.zone.sync() ;
		this.zone.refresh() ;
		message(active_player.name+' moves '+this.cards_names(this.zone)+' '+this.cards[0].position_name('inside', 'on'), 'zone') ; // Only works well with 1 card sel
		return true ;
	}
	this.place = function(x, y) {
		if ( ( this.cards.length < 1 ) || ( this.zone.type != 'battlefield' ) )
			return false ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			var xo = x ;
			var yo = y ;
			if ( isn(card.xoffset) ) {
				xo += card.xoffset ;
				delete card.xoffset;
			}
			if ( isn(card.yoffset) ) {
				yo += card.yoffset ;
				delete card.yoffset ;
			}
			card.place(xo, yo) ;
		}
	}
	// Initialisation
	this.zone = null ;
	this.cards = [] ;
	this.type = '' ; // Specificity in zone change message (mulligan, random)
	if ( arguments.length > 0 ) {
		switch ( typeof arguments[0] ) {
			case 'string' : // Cards passed as a string, will have to parse
				this.parse(arguments[0]) ;
				break ;
			case 'object'  : 
				this.set(arguments) ;
				break ;
			default : 
				alert(typeof arguments[0]) ;
		}
	}
}
