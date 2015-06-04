// card.js : Class and prototype for cards (and tokens, and duplicates) management

// Here
	// - init
	// - getters
	// - setters
// -> card/display.js
	// - desgin
	// - image
// -> card/events.js
	// - events
// -> card/menu.js
	// - menu
// -> card/zone.js
	// - zone
// Here
	// - attrs
	// - rules
function Card(id, extension, name, zone, attributes, exts) {
	this.type = 'card' ; // Used in right DND
	this.init('c_' + id, extension, name, zone, attributes, exts) ;
	this.bordercolor = 'black' ;
	this.setzone(zone) ; // Initial zone initialisation
	game.cards.push(this) ; // Auto referencing as a card
	// Preload image if option is checked and card is not in sideboard
	if ( ( zone.type == 'library' ) && game.options.get('check_preload_image') ) {
		game.image_cache.load(this.imgurl('initial')) ;
		if ( this.attrs.base_has('transform') )
			game.image_cache.load(this.imgurl('transform')) ;
		if ( this.attrs.base_has('morph') )
			game.image_cache.load(this.imgurl('morph')) ;
	}
}
function card_prototype() {
	// Methods
		// Purely object
	this.init = function(id, extension, name, zone, attributes, exts) {
		// Common initialisation code between card and token
		Widget(this) ; // this doesn't put it in game.widgets
		// Basic data
		this.id = id ;
		this.zone = zone ;
		this.orig_zone = this.zone ; // Zone on game begin, used for siding
		this.init_zone = this.zone ; // Initial zone, as described in deckfile, used for reinit deck in side window
		this.owner = zone.player ;
		this.name = name ;
		this.ext = extension ;
		this.exts = exts ; 
		// Canvas display
		this.w = cardwidth ;
		this.h = cardheight ;
		this.coords_set() ;
		this.targeted = false ;
		this.img = null ;
		this.img_loading = null ;
		// Attributes
		attributes.name = name ;
		attributes.ext = extension ;
		this.attrs = new Attrs(attributes) ;
		// Watching in list
		this.watching = false ; // Nobody watches it
		return this ;
	}
	this.del = function() {
		// Remove linked data
		game.target.del_by_target(this) ;
		// Remove from zone
		var i = this.zone.cards.indexOf(this) ;
		if ( i >= 0 ) { // It hasn't already been removed from zone
			// Remove from grid
			if ( this.zone.type == 'battlefield' )
				this.clean_battlefield() ;
			// Remove from zone
			this.zone.cards.splice(this.zone.cards.indexOf(this),1) ;
		}
		// Unlink
		var from = game.cards ;
		if ( this.type == 'token' )
			from = game.tokens ;
		from.splice(from.indexOf(this), 1) ;
		// Remove from memory
		delete this ;
	}
// === [ GETTERS ] =============================================================
	// Javascript generic (identification for network json)
	this.toString = function() { return this.id } ;
	this.toJSON = function() { return this.id } ;
	// Buisness generic
	this.is_color = function(color) {
		var mycolor = this.attrs.get('color') ;
		if ( iss(mycolor) && ( mycolor.indexOf(color) != -1 ) )
			return true ;
		return false ;
	}
	this.is_supertype = function(type) {
		var supertypes = this.attrs.get('supertypes') ;
		if ( iso(supertypes) && ( supertypes.indexOf(type) != -1 ) )
			return true ;
		return false ;
	}
	this.is_type = function(type) {
		var mytypes = this.attrs.get('types') ;
		if ( iso(mytypes) && ( mytypes.indexOf(type) != -1 ) )
			return true ;
		return false ;
	}
	this.is_subtype = function(subtype) {
		if ( this.attrs.get('changeling') )
			return true ;
		var st = this.attrs.get('subtypes') ;
		if ( iso(st) && ( st.indexOf(subtype) != -1 ) )
			return true ;
		return false ;
	}
	// Types
	this.get_types = function(base) {
		return this.attrs.get('types', [], base) ;
	}
	this.is_creature = function(base) {
		return inarray('creature', this.get_types(base)) ;
	}
	this.is_land = function(base) {
		return inarray('land', this.get_types(base)) ;
	}
	this.is_planeswalker = function(base) {
		return inarray('planeswalker', this.get_types(base)) ;
	}
	// Identity
	this.get_name = function(base) {
		var name = this.attrs.get('name', null, base) ;
		if ( iso(this.attrs.get('copy')) )
			name = this.attrs.get('copy').get_name()+' copied by '+name ;
		return name ;
	}
	// Selection
	this.selected = function() {
		return ( game.selected.cards.indexOf(this) > -1 ) ;
	}
	// Misc
	this.IndexInZone = function() { // Return card's index in its zone
		return this.zone.cards.indexOf(this) ;
	}
	this.theorical_base = function(zone) { // Returns base depending on current (or param) zone
		if ( ! iso(zone) )
			zone = this.zone ;
		return zone.default_visibility ? 'initial' : 'back' ;
	}
	this.is_visible = function() { // Deprecated
		// Library top card revealed
		if (
			( this.zone.type == 'library' )
			&& ( this.owner.attrs.library_revealed )
			&& ( this.zone.cards.indexOf(this) == this.zone.cards.length-1 )
		)
			return true ;
		// Other cases than exceptions
		var base = this.attrs.base_current() ;
		return ( base != 'back' ) ;
	}
	this.imgurl = function(base) {
		if ( !iss(base) )
			var base = this.attrs.base_current() ;
		switch(base) {
			case 'back' :
				if ( this.watching )
					base = 'initial' ;
				else
					return card_images('back.jpg')
				break ;
			case 'flip' : // Flip hasn't its own image, it uses normal card image rotated 180°
				base = 'initial' ;
				break ;
		}
		var ext = this.attrs.get('ext', null, base) ;
		var name = this.attrs.get('name', null, base) ;
		var nb = this.attrs.get('nb', null, base) ;
		var result = '' ;
		if ( this.attrs.get('token', null, base) ) {
			var pow = this.attrs.get('pow', null, base) ;
			var tou = this.attrs.get('thou', null, base) ;
			result = token_image_url(ext, name, nb, pow, tou) ;
		} else
			result = card_image_url(ext, name, nb) ;
		return card_images(result) ; // As array with fallback
	}
	this.position_name = function(word, link, index, zone) { // Returns a string describing card's position in its zone
		// Used to display "on top of" or "on bottom of" when moving a card inside library
		if ( ! isn(index) )
			index = this.IndexInZone() ;
		if ( ! iso(zone) )
			zone = this.zone ;
		if ( zone.type == 'library' )
			switch ( index ) {
				case 0 :
					word = link+' bottom of' ;
					break ;
				case this.zone.cards.length-1 :
					word = link+' top of' ;
					break ;
			}
		word += ' '+zone.get_name() ;
		return word ;
	}
	this.satisfy_condition = function(cond) {
		if ( ! iss(cond) )
			return true ;
		var conds = cond.split('|') ;
		for ( var i = 0 ; i < conds.length ; i++ ) {
			var pieces = conds[i].split('=') ;
			if ( pieces.length != 2 ) {
				log('Error parsing cond : '+pieces) ;
				continue ;
			}
			switch ( pieces[0] ) {
				case 'class' :
					if ( this.type == pieces[1] )
						return true ;
					break ;
				case 'color' :
					if ( this.is_color(pieces[1]) )
						return true ;
					break ;
				case 'stype' :
					if ( this.is_supertype(pieces[1]) )
						return true ;
					break ;
				case 'type' :
					if ( this.is_type(pieces[1]) )
						return true ;
					break ;
				case 'ctype' :
					if ( this.is_subtype(pieces[1]) )
						return true ;
					break ;
				case 'name' :
					if ( this.attrs.get('name') == pieces[1] )
						return true ;
					break ;
				default :
					log('Unknown boost condition : '+pieces[0]) ;
			}
		} // No condition were satisfied by card
		return false ;
	}
// === [ SETTERS ] =============================================================
	this.coords_set = function(x, y) {
		if ( ! isn(x) )
			x = 0 ;
		if ( ! isn(y) )
			y = 0 ;
		this.x = x ;
		this.y = y ;
		this.set_coords(this.x, this.y, this.w, this.h) ;
	}
// ### [ card/display.js ] ######################################################
// === [ DESIGN ] ==============================================================
	this.draw = card_draw ; // Draw card to a canvas
	this.refreshpowthou = card_refreshpowthou ; // Cache pow, thou and color
	this.refresh = card_refresh ; // Redraw canvas cache
	this.rect = card_rect ; // Returns card's rectangle coordinates
	this.load_image = card_load_image ;
// ### [ card/events.js ] ######################################################
// === [ EVENTS ] ==============================================================
	this.mouseover = card_mouseover ;
	this.mouseout = card_mouseout ;
	this.mousedown = card_mousedown ;
	this.mouseup = card_mouseup ;
	this.dblclick = card_dblclick ;
	this.dragstart = card_dragstart ;
	this.zoom = card_zoom ;
// ### [ card/menu.js ] ########################################################
// === [ MENU ] ================================================================
	this.menu = card_menu ;
	this.changezone_menu = card_changezone_menu ;
	this.info = card_info ;
	this.mojosto = card_mojosto ;
// ### [ card/zone.js ] ########################################################
// === [ ZONE MANAGEMENT ] =====================================================
	this.moveinzone = card_moveinzone ;
	this.changezone = card_changezone ;
	this.changezone_recieve = card_changezone_recieve ;
	this.setzone = card_setzone ;
	this.place_row = card_place_row ;
	this.place = card_place ;
	this.place_recieve = card_place_recieve ;
	this.move = card_move ;
	this.set_grid = card_set_grid ;
	this.clean_battlefield = card_clean_battlefield ;
// === [ ATTRIBUTES + SYNCHRONISATION ] ========================================
	this.base_set = function(base) {
		this.base_set_recieve(base) ;
		action_send('base', {'card': this.id, 'base': base}) ;
	}
	this.base_set_recieve = function(base) {
		// Save current state for messages
		var oldbase = this.attrs.base_current() ;
		var cardname = this.get_name() ;
		// Action
		this.attrs.base_transfer(base, 'pow') ;
		this.attrs.base_transfer(base, 'thou') ;
		this.attrs.base_set(base) ;
		// Messages
		var msg = null ;
		switch ( base ) { // To
			case 'back': 
			case 'manifest': // Theorically shouldn't exist
			case 'morph': 
				msg = active_player.name+' turns '+cardname+' face down ('+base+')' ;
				break ;
			case 'transform': 
			case 'flip': 
				msg = active_player.name+' '+base+'s '+cardname+' into '+this.get_name() ;
				break ;
			case 'initial':
				switch ( oldbase ) { // From
					case 'back': 
					case 'manifest': 
					case 'morph': 
						msg = active_player.name+' turns '+this.get_name()+' face up ('+oldbase+')' ;
						break ;
					case 'transform': 
					case 'flip': 
						msg = active_player.name+' '+oldbase+'s '+cardname+' into '+this.get_name() ;
						break ;
					default: 
						log('Base set '+oldbase+' -> '+base) ;
				}
				break ;
			default: 
				log('Base set '+oldbase+' -> '+base) ;
		}
		// Message after change as sometimes it requires new state
		if ( msg != null )
			message(msg, 'note') ;
		// Refresh representation
		this.refreshpowthou() ;
		this.load_image() ;
	}
	this.sync = function() { // Send
		/*
		var result = false ; // By default, no action will be done
		// Send only difference between last synched attrs and current attrs
		var attrs = {} ;
		for ( i in this.attrs ) {
			if ( ( i == 'tapped' ) || ( i == 'attacking' ) || ( i == 'revealed' ) ) // Sync is in progress via selection
				continue ;
			// Workaround for the loop of siding synchronisation
			if ( ( i == 'siding' ) && ( this != game.player ) ) // I am the lone who can change my siding status
				continue ;
			// Compare JSON for objects/arrays
			var previous = JSON.stringify(this.sync_attrs[i]) ;
			var current = JSON.stringify(this.attrs[i]) ;
			if ( previous != current ) {
				attrs[i] = this.attrs[i] ;
				this.sync_attrs[i] = this.attrs[i] ;
				if ( ! result ) 
					result = true ; // At least one attribute will be sync
			}
		}
		// Reclone for next synch
		if ( result ) {
			//this.sync_attrs = clone(this.attrs, true) ;
			action_send('attrs', {'card': this.id, 'attrs': attrs}) ; // this.attrs for full sync, attrs for diff sync
		}
		return attrs ;
		*/
		action_send('attrs', {'card': this.id, 'attrs': this.attrs.own}) ; 
	}
	this.setattrs = function(attrs) { // Get a new "attrs" array, and compare each element with current "attrs" array, then apply each difference
		if ( isb(attrs.flipped) && ( attrs.flipped != this.attrs.flipped ) )
			this.flip_recieve() ;
		if ( typeof attrs.transformed != 'undefined' )
			if ( this.attrs.transformed != attrs.transformed ) {
				if ( attrs.transformed )
					this.transform_recieve() ;
				else
					this.untransform_recieve() ;
			}
		if ( typeof attrs.damages != 'undefined' )
			if ( this.attrs.damages != attrs.damages )
				this.set_damages_recieve(attrs.damages) ;
		if ( ( typeof attrs.pow != 'undefined' ) | ( typeof attrs.thou != 'undefined' ) ) { // At least one change
			// Solve cases where one is set and not the other
			var pow = this.attrs.pow ; // Actual values as default
			var thou = this.attrs.thou ;
			if ( isn(attrs.pow) )
				pow = attrs.pow ;
			if ( isn(attrs.thou) )
				thou = attrs.thou ;
			if ( ( this.attrs.pow != attrs.pow ) | ( this.attrs.thou != attrs.thou ) )
				this.disp_powthou_recieve(pow, thou) ;
		}
		if ( ( typeof attrs.pow_eot != 'undefined' ) | ( typeof attrs.thou_eot != 'undefined' ) ) {
			// Solve cases where one is set and not the other
			var pow_eot = this.attrs.pow_eot ; // Actual values as default
			var thou_eot = this.attrs.thou_eot ;
			if ( isn(attrs.pow_eot) )
				pow_eot = attrs.pow_eot ;
			if(  isn(attrs.thou_eot) )
				thou_eot = attrs.thou_eot ;
			if ( ( this.attrs.pow_eot != attrs.pow_eot ) | ( this.attrs.thou_eot != attrs.thou_eot ) )
				this.disp_powthou_eot_recieve(pow_eot, thou_eot) ;
		}
		if ( typeof attrs.counter != 'undefined' )
			if ( this.attrs.counter != attrs.counter )
				this.setcounter_disp(attrs.counter) ;
		if ( typeof attrs.note != 'undefined' )
			if ( this.attrs.note != attrs.note )
				this.setnote_recieve(attrs.note)
		if ( typeof attrs.no_untap != 'undefined' )
			if ( this.attrs.no_untap != attrs.no_untap ) {
				if ( attrs.no_untap )
					this.does_not_untap_recieve() ;
				else
					this.untap_as_normal_recieve() ;
			}
		if ( typeof attrs.no_untap_once != 'undefined' )
			if ( this.attrs.no_untap_once != attrs.no_untap_once ) {
				this.attrs.no_untap_once = attrs.no_untap_once ;
				if ( attrs.no_untap_once )
					this.does_not_untap_once_recieve() ;
				else 
					this.untap_as_normal_once_recieve() ;
			}
		if ( typeof attrs.copy != 'undefined' )
			if ( attrs.copy != this.attrs.copy ) {
				if ( attrs.copy != null ) {
					var card = get_card(attrs.copy) ;
					if ( card != null )
						this.copy_recieve(card) ;
					else
						log(this+' can\'t find '+attrs.copy+' to copy') ;
				} else
					this.uncopy_recieve() ;
			}
		//var boost = false ;
		if ( typeof attrs.boost_bf != 'undefined' ) {
			this.attrs.boost_bf = attrs.boost_bf ;
			game.player.battlefield.refresh_pt() ;
			game.opponent.battlefield.refresh_pt() ;
		}
		if ( typeof attrs.manifested != 'undefined' ) {
			this.attrs.manifested = attrs.manifested ;
			this.load_image();
		}
		/* Legacy ones, currently managed by another way */
		if ( typeof attrs.attachedto != 'undefined' ) {
			var attachedto = get_card(attrs.attachedto) ;
			if ( this.get_attachedto() != attachedto )
				if ( attachedto != null )
					attachedto.attach_recieve(this) ;
		}
		//this.sync_attrs = clone(this.attrs, true) ;
	}
	this.has_attr = function(attr) {
		if ( this.attrs.get(attr) == true )
			return true ;
		if ( iso(this.animated_attrs) && isb(this.animated_attrs[attr]) && this.animated_attrs[attr] )
			return true ;
		var attached = this.get_attached() ;
		for ( var i = 0 ; i < attached.length ; i++ ) {
			var bonus = attached[i].attrs.get('bonus') ;
			if ( iso(bonus) && bonus[attr] )
				return true ;
		}
		// this.apply_boost : 
		var cards = this.zone.cards ;
		for ( var i = 0 ; i < cards.length ; i++ ) {
			var boost_bf = cards[i].boost_bf() ;
			for ( var j = 0 ; j < boost_bf.length ; j++ ) {
				var boost = boost_bf[j] ;
				if ( ( ! boost.self ) && ( this == cards[i] ) ) // Boost doesn't work on self
					continue ;
				if ( iss(boost.cond) && ( ! this.satisfy_condition(boost.cond) ) ) // Boost verify a condition
					continue ;
				if ( boost[attr] == true ) {
					return true ;
				}
			}
		}
		return false ;
	}
	// Power / thoughness
		// Permanent
	this.get_pow = function() { // Getter
		return this.attrs.get('pow', 0) ;
	}
	this.get_thou = function() {
		return this.attrs.get('thou', 0) ;
	}
	this.get_powthou = function() {
		return this.get_pow()+'/'+this.get_thou() ;
	}
	this.set_pow = function(nb) { // Setter which does not display anything
		if ( isn(nb) ) {
			var pow = nb - this.get_pow() ;
			this.attrs.set('pow', nb) ;
		} else 
			var pow = 0 ;
		return disp_int(pow) ;
	}
	this.set_thou = function(nb) {
		if ( isn(nb) ) {
			var thou = nb - this.get_thou() ;
			this.attrs.set('thou', nb) ;
		} else
			var thou = 0 ;
		return disp_int(thou) ;
	}
	this.set_powthou = function(pow, thou) {
		return this.set_pow(pow)+'/'+this.set_thou(thou) ;
	}
	this.add_pow = function(nb) { // Setter that adds instead of replace, and does not display anything
		if ( isn(nb) )
			this.attrs.set('pow', this.attrs.get('pow', 0) + nb) ;
	}
	this.add_thou = function(nb) {
		if ( isn(nb) )
			this.attrs.set('thou', this.attrs.get('thou', 0) + nb) ;
	}
	this.add_powthou = function(pow, thou) {
		return this.add_pow(pow)+'/'+this.add_thou(thou) ;
	}
	this.disp_powthou = function(pow, thou) { // Setter that display a summary
		this.disp_powthou_recieve(pow, thou) ;
		this.sync() ;
	}
	this.disp_powthou_recieve = function(pow, thou) {
		if ( isn(pow) && isn(thou) ) {
			var bonus = this.set_powthou(pow, thou) ;
			this.refreshpowthou() ;
			message(this.get_name()+' is now '+this.get_powthou_total()+' ('+bonus+')', 'pow_thou') ;
		} else {
			this.attrs.set('pow', null) ;
			this.attrs.set('thou', null) ;
			this.refreshpowthou() ;
			message(this.get_name()+' hasn\'t power nor toughness anymore', 'pow_thou') ;
		}
	}
	this.ask_powthou = function() { // Asker that display a summary
		var txt = prompt('Set power and toughness for '+this.get_name(), this.get_powthou()) ;
		if ( txt != null ) {
			if ( txt == '' )
				this.disp_powthou() ;
			else {
				var arr = txt.split('/') ;
				if ( arr.length == 2 )
					this.disp_powthou(parseInt(arr[0]), parseInt(arr[1])) ;
			}
		}
	}
		// Temporary
	this.get_pow_eot = function() { // Getter
		return this.attrs.get('pow_eot', 0) ;
	}
	this.get_thou_eot = function() {
		return this.attrs.get('thou_eot', 0) ;
	}
	this.get_powthou_eot = function() {
		return disp_int(this.get_pow_eot())+'/'+disp_int(this.get_thou_eot()) ;
	}
	this.set_pow_eot = function(nb) { // Setter which does not display anything
		if ( isn(nb) ) {
			var pow = nb - this.get_pow_eot() ;
			this.attrs.set('pow_eot', nb) ;
		} else 
			var pow = 0 ;
		if ( this.attrs.get('pow_eot') == 0 )
			this.attrs.set('pow_eot', null) ;
		return disp_int(pow) ;
	}
	this.set_thou_eot = function(nb) {
		if ( isn(nb) ) {
			var thou = nb - this.get_thou_eot() ;
			this.attrs.set('thou_eot', nb) ;
		} else
			var thou = 0 ;
		if ( this.attrs.get('thou_eot') == 0 )
			this.attrs.set('thou_eot', null) ;
		return disp_int(thou) ;
	}
	this.set_powthou_eot = function(pow, thou) {
		return this.set_pow_eot(pow)+'/'+this.set_thou_eot(thou) ;
	}
	this.add_pow_eot = function(nb) { // Setter that adds instead of replace, and does not display anything
		if ( isn(nb) ) {
			var val = this.attrs.get('pow_eot', 0) + nb ;
			if ( val == 0 )
				this.attrs.set('pow_eot', null) ;
			else
				this.attrs.set('pow_eot', val) ;
		}
	}
	this.add_thou_eot = function(nb) {
		if ( isn(nb) ) {
			var val = this.attrs.get('thou_eot', 0) + nb ;
			if ( val == 0 )
				this.attrs.set('thou_eot', null) ;
			else
				this.attrs.set('thou_eot', val) ;
		}
	}
	this.add_powthou_eot = function(pow, thou) {
		return this.add_pow_eot(pow)+'/'+this.add_thou_eot(thou) ;
	}
	this.disp_powthou_eot = function(pow, thou) { // Setter that display a summary
		this.disp_powthou_eot_recieve(pow, thou) ;
		this.sync() ;
	}
	this.disp_powthou_eot_recieve = function(pow, thou) {
		if ( isn(pow) && isn(thou) ) {
			var bonus = this.set_powthou_eot(pow, thou) ;
			message(this.get_name()+' is now '+this.get_powthou_total()+' ('+bonus+') until end of turn', 'pow_thou') ;
		} else {
			this.attrs.set('pow_eot', null) ;
			this.attrs.set('thou_eot', null) ;
			message(this.get_name()+' hasn\'t anymore power nor toughness bonus until EOT', 'pow_thou') ;
		}
		this.refreshpowthou() ;
	}
	this.ask_powthou_eot = function() { // Asker that display a summary
		var txt = prompt('Set power and toughness bonus for '+this.get_name(), this.get_powthou_eot()) ;
		if ( txt != null ) {
			if ( txt == '' )
				this.disp_powthou_eot() ;
			else {
				var arr = txt.split('/') ;
				if ( arr.length == 2 )
					this.disp_powthou_eot(parseInt(arr[0]), parseInt(arr[1])) ;
			}
		}
	}
		// Conditionnal
	this.get_pow_cond = function(pt) {
		var result = 0 ;
		var powtoucond = this.attrs.get('powtoucond') ;
		if ( iso(powtoucond) ) {
			if ( iss(pt) && isn(powtoucond[pt]) )
				var boost = powtoucond[pt] ;
			else
				var boost = 1 ;
			var from_str = powtoucond.from ;
			var player = this.zone.player ;
			if ( from_str[0] == '!' ) {
				from_str = from_str.substr(1) ;
				player = player.opponent ;
			}
			var from = [] ;
			if ( iso(player[from_str]) )
				from = from.concat(player[from_str].cards) ;
			else {
				from_str = from_str.substr(0, from_str.length-1) ;
				if ( iso(player[from_str]) )
					from = from.concat(player[from_str].cards) ;
				if ( iso(player.opponent[from_str]) )
					from = from.concat(player.opponent[from_str].cards) ;
			}
			switch ( powtoucond.what ) {
				case 'cards' : // Standard case : counting cards (master of etherium, kotr)
				case 'card' : // Standard case : presence of any number (kird ape)
					for ( var i in from ) {
						if ( isb(powtoucond.other) && powtoucond.other && ( from[i] == this ) )
							continue ;
						if ( from[i].satisfy_condition(powtoucond.cond) ) {
							result += boost ;
							if ( powtoucond.what == 'card' ) // Searching for 1
								break ;
						}
					}
					break ;
				case 'types' : // Tarmogoyf
					var types = [] ;
					for ( var i in from ) {
						var mytypes = from[i].attrs.get('types') ;
						for ( var j in mytypes )
							if ( ! inarray(mytypes[j], types) )
								types.push(mytypes[j]) ;
					}
					result += types.length ;
					break ;
				default :
					log('Unknown what : '+powtoucond.what) ;
			}

		}
		return result ;
	}
		// Total (attrs + cond + eot + attach + boost)
	this.get_port_total = function(port) { // port = pow or thou, as string
		var result = 0 ;
		var myport = ( port == 'thou' ) ? 'tou' : port ; // Attach bonus and boost have tou instead of thou
		// Native
		result += this.attrs.get(port, 0) ;
		// Conditionnal
		result += this.get_pow_cond(port) ;
		// EOT
		result += this.attrs.get(port+'_eot', 0) ;
		// Attach
		var a = this.get_attached() ;
		for ( var i = 0 ; i < a.length ; i++ ) {
			result += a[i].get_pow_cond(port) ;
			var bonus = a[i].attrs.get('bonus') ;
			if ( iso(bonus) && isn(bonus[myport]) )
				result += bonus[myport] ;
		}
		// Boost
		result += this.apply_boost(myport) ;
		return result

	}
	this.get_pow_total = function() {
		return this.get_port_total('pow') ;
	}
	this.get_thou_total = function() {
		return this.get_port_total('thou') ;
	}
	this.get_powthou_total = function() {
		return this.get_pow_total()+'/'+this.get_thou_total() ;
	}
	// Returns sum of all cards' boost_bf in battlefields
	this.apply_boost_from = function(type, zone, own) {
		var result = 0 ;
		var cards = this.zone.cards ;
		for ( var i = 0 ; i < cards.length ; i++ ) {
			var boost_bf = cards[i].boost_bf() ;
			for ( var j = 0 ; j < boost_bf.length ; j++ ) {
				var boost = boost_bf[j] ;
				if ( ! boost.enabled )
					continue ;
				if ( ( ! boost.self ) && ( this == cards[i] ) ) // Boost doesn't work on self
					continue ;
				if ( iss(boost.cond) && ( ! this.satisfy_condition(boost.cond) ) ) // Boost verify a condition
					continue ;
				if ( own && ( boost.control < 0 ) )
					continue ;
				if ( ! own && ( boost.control > 0 ) )
					continue ;
				result += boost[type] ;
			}
		}
		return result ;
	}
	this.apply_boost = function(type) {
		var result = 0 ;
		result += this.apply_boost_from(type, this.zone, true) ;
		result += this.apply_boost_from(type, this.zone.player.opponent[this.zone.type], false) ;
		return result ;
	}
	// Damages
	this.get_damages = function() {
		return this.attrs.get('damages', 0) ;
	}
	this.set_damages = function(nb) {
		this.set_damages_recieve(nb) ;
		this.sync() ;
	}
	this.set_damages_recieve = function(nb) {
		if ( ! isn(nb) || ( nb < 0 ) )
			nb = null ;
		this.attrs.set('damages', nb) ;
		this.refreshpowthou() ;
	}
	// Counters
	this.getcounter = function() {
		return this.attrs.get('counter', 0) ;
	}
	this.addcounter = function(nb) {
		if ( !isn(nb) )
			nb = prompt_int('How many counters to add for '+this.get_name()+' ?', 1) ;
		if ( isn(nb) )
			this.setcounter(nb + this.getcounter()) ;
	}
	this.setcounter = function(nb) {
		if ( ! isn(nb) ) {
			nb = this.getcounter() ;
			if ( this.is_planeswalker() )
				nb -= this.get_damages() ;
			nb = prompt_int('How many counters to set for '+this.get_name()+' ?', nb) ;
		}
		if ( isn(nb) ) {
			this.setcounter_disp(nb) ;
			this.sync() ;
		}
	}
	this.setcounter_disp = function(nb) {
		var old_nb = this.getcounter() ;
		this.setcounter_recieve(nb) ;
		message(this.get_name()+' has now '+nb+' ('+disp_int(nb-old_nb)+') counters', 'counter') ;
	}
	this.setcounter_recieve = function(nb) {
		if ( ! isn(nb) || nb < 0 )
			nb = 0 ;
		if ( nb != this.getcounter() ) {
			this.attrs.set('counter', nb) ;
			if ( this.is_planeswalker() && ( nb < this.getcounter() ) ) // taking damages
				this.set_damages(max(0, this.get_damages() - old_nb + nb)) ;
			this.refresh('counters') ;
		}
	}
	// Note
	this.getnote = function() {
		return this.attrs.get('note', '')
	}
	this.setnote = function(note) {
		if ( ! iss(note) ) {
			if ( this.getnote() == '' ) {
				var targeted = game.target.targetedby(this) ;
				var targetednames = targeted.map(function(val, idx, arr) { return val.get_name() ;}) ;
				note = targetednames.join(', ') ;
			} else
				note = this.getnote() ;
			note = prompt('Note for '+this.get_name()+' ?', note) ;
		}
		this.setnote_recieve(note) ;
		this.sync() ;
	}
	this.setnote_recieve = function(note) {
		if ( note == '' )
			note = null ;
		if ( note != this.attrs.get('note') ) {
			this.attrs.set('note', note) ;
			delete this.note_img ;
			if ( inarray(note.toUpperCase(), game.manacolors) )
				game.image_cache.load(theme_image('ManaIcons/'+note.toUpperCase()+'.png'), function(img, card) {
					card.note_img = img ;
					card.refresh() ;
				}, function(card, zone) {
					log('Image not found for '+card.name+', creating text') ;
				}, this, param) ;
			if ( note == null )
				message(this.get_name()+' isn\'t annoted anymore', 'note') ;
			else
				message(this.get_name()+'\'s annotation is now : '+note, 'note') ;
			this.refresh('note') ;
		}
	}
	// No untap as normal
	this.untap_toggle = function() {
		if ( this.attrs.get('no_untap', false) )
			this.untap_as_normal_recieve() ;
		else
			this.does_not_untap_recieve() ;
		this.sync() ;
	}
	this.untap_once_toggle = function() {
		if ( this.attrs.get('no_untap_once') )
			this.untap_as_normal_once_recieve() ;
		else
			this.does_not_untap_once_recieve() ;
		this.sync() ;
	}
	this.untap_as_normal_recieve = function() {
		this.attrs.set('no_untap', null) ; 
		message(this.get_name()+' will untap as normal', 'note') ;
		this.refresh('untap_as_normal') ;
	}
	this.untap_as_normal_once_recieve = function() {
		this.attrs.set('no_untap_once', null) ;
		message(this.get_name()+' will untap as normal', 'note') ;
		this.refresh('untap_as_normal_once') ;
	}
	this.does_not_untap_recieve = function() {
		this.attrs.set('no_untap', true) ;
		message(this.get_name()+' wont untap as normal', 'note') ;
		this.refresh('does_not_untap') ;
	}
	this.does_not_untap_once_recieve = function() {
		this.attrs.set('no_untap_once', true) ;
		message(this.get_name()+' wont untap as normal next untap phase', 'note') ;
		this.refresh('does_not_untap_once') ;
	}
		// Reveal (hand + library top)
	this.toggle_reveal = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.attrs.get('visible') )
			mycard.unreveal() ;
		else
			mycard.reveal() ;
	}
	this.reveal = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.reveal_recieve(mycard) )
			mycard.sync() ;
	}
	this.reveal_recieve = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( ! mycard.attrs.get('visible') ) { // Not already forced as true
			mycard.attrs.set('visible', true) ;
			mycard.load_image() ; // Display background if in opponent's hand
			message(active_player.name+' reveals '+mycard.get_name()+' from '+mycard.zone.get_name(), 'note') ;
			return true ; // Returns if something changed
		}
		return false ;
	}
	this.unreveal = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.unreveal_recieve(mycard) )
			mycard.sync() ;
	}
	this.unreveal_recieve = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.attrs.get('visible') ) {
			message(active_player.name+' hides '+mycard.get_name()+' from '+mycard.zone.get_name()) ;
			mycard.attrs.set('visible', null) ;
			mycard.load_image() ;
			return true ;
		}
		return false ;
	}
	// Attach
	this.get_attachedto = function() { // Returns the card which this is attached, or null
		if ( iso(this.attachedto) )
			return this.attachedto ;
		return null ;
	}
	this.get_attached = function() { // Returns an array of cards attached to this
		if ( ! iso(this.attached) )
			this.attached = new Array() ;
		return this.attached ;
	}
	this.attach = function(dragcard) { // Attach dragcard to this
		this.attach_recieve(dragcard) ;
		action_send('attach', {'card': dragcard.toString(), 'to': this.toString()}) ;
	}
	this.attach_recieve = function(attached) {
		var attach_to = this ; // By default, attach to dropped card
		while ( attach_to.get_attachedto() != null ) // But if it is attached itself, attach to root attached card
			attach_to = attach_to.get_attachedto() ;
		if ( attach_to != attached ) {
			if ( attach_to.zone != attached.zone ) // If attached from hand, library, grave ...
				attached.changezone_recieve(attach_to.zone) ; // First move it to desired zone
			attached.detach() ; // If attached, detached from where it is attached
			// If attached has itself attached cards, detach those and replace them
			while ( ( attached.get_attached() != null ) && ( attached.get_attached().length > 0 ) ) {
				var changeattach = attached.get_attached()[0] ;
				changeattach.detach() ;
				changeattach.place() ;
			}
			// Mark 'attached' as attached to 'attach_to'
			attached.attachedto = attach_to ;
			attach_to.get_attached().push(attached) ;
			// Clean
			attached.clean_battlefield() ;
			attached.set_grid(attach_to.grid_x, attach_to.grid_y) ;
				// Re place it where it already is, in order to place correctly all attached
			attach_to.place_recieve(attach_to.grid_x, attach_to.grid_y) ;
			attach_to.refreshpowthou() ;
			message(attached.get_name()+' attached to '+attach_to.get_name(), 'note') ;
		} else
			log('Can\'t attach to itself') ;
	}
	this.detach = function() { // If this is attached to another, detach from it
		var from = this.get_attachedto() ;
		if ( from != null ) {
			var attached = from.get_attached()
			if ( attached == null )
				return false ;
			attached.splice(attached.indexOf(this), 1) ;
			this.attachedto = null ;
			if ( ! this.zone.player.attrs.siding ) // No detach message during side
				message(this.get_name()+' detached from '+from.get_name(), 'note') ;
			from.place_recieve(from.grid_x, from.grid_y) ; // Refresh their position
			from.refreshpowthou() ;
		}
	}
	// Duplicate
	this.duplicate = function() {
		var card = this ;
		action_send('duplicate', {'card': this.toString()}, function(data) {
			card.duplicate_recieve(data.id) ;
		}) ;
	}
	this.duplicate_recieve = function(id) {
		var attrs = clone(this.attrs.base_get(), true) ;
		var duplicate = new Token(id, attrs.ext, attrs.name, this.zone, attrs) ;
		duplicate.get_name = function() {
			return this.name+' (copy)' ; // Default is token, overriding
		}
		message(active_player.name+' duplicates '+this.get_name(), 'zone') ;
	}
	// Copy (cloning effect)
	this.copy = function() {
		var tby = game.target.targetedby(this) ;
		var tocopy = tby[0] ;
		while ( iso(tocopy.attrs.copy) )
			tocopy = tocopy.attrs.copy ;
		if ( this.copy_recieve(tocopy) ) 
			this.sync() ;
	}
	this.copy_recieve = function(card) {
		if ( card == this ) {
			log('Card trying to copy self') ;
			return false ;
		}
		message(this.get_name()+' becomes a copy of '+card.get_name()) ; // get it without "copy of ..." suffix
		this.attrs = clone(card.orig_attrs, true) ;
		//this.sync_attrs = clone(this.attrs) ;
		this.attrs.copy = card ;
		// Refresh data linked to attrs
		this.refreshpowthou() ; // Own
		this.zone.refresh_pt(true) ; // Other cards on BF
		// Load copied card image & trigger refresh when loaded
		card.load_image(function(img, card) {
			card.refresh() ;
		}, this) ;
		return true ;
	}
	this.uncopy = function() {
		this.uncopy_recieve()
		this.sync() ;
	}
	this.uncopy_recieve = function() {
		if ( iso(this.attrs.copy) && ( this.attrs.copy != null ) ) {
			var copy = this.attrs.copy ;
			this.attrs = clone(this.orig_attrs, true) ;
			this.attrs.copy = null ;
			// Refresh data linked to attrs
			this.refreshpowthou() ; // Own
			this.zone.refresh_pt(true) ; // Other cards on BF
			this.refresh() ;
			message(this.get_name()+' isn\'t anymore a copy of '+copy.get_name()) ;
		} else
			log(this+' can\'t uncopy '+this.attrs.copy) ;
	}
// === [ RULES ] ===============================================================
	this.boost_bf = function() {
		var result = [] ;
		var boost_bf = this.attrs.get('boost_bf') ;
		if ( iso(boost_bf) )
			result = result.concat(boost_bf) ;
		return result ;
	}
	this.boost_bf_enable = function(line) {
		this.boost_bf_enable_recieve(line) ;
		this.sync() ;
	}
	this.boost_bf_enable_recieve = function(line) {
		line.enabled = ! line.enabled ;
		game.player.battlefield.refresh_pt() ;
		game.opponent.battlefield.refresh_pt() ;
	}
	this.cycle = function() {
		(new Selection(this)).settype('cycle').changezone(this.owner.graveyard) ;
		this.owner.hand.draw_card() ;
	}
	this.animate = function(animate) {
		if ( ! isn(animate.pow) || ! isn(animate.tou) ) {
			log('Can\'t animate '+this.name+' : ') ;
			log(animate) ;
			return null ;
		}
		this.animate_recieve(animate) ;
		action_send('animate', {'card': this.id, 'attrs': animate}) ;
	}
	this.animate_recieve = function(animate) {
		this.animated_attrs = animate ;
		if ( animate.eot )
			this.disp_powthou_eot(animate.pow, animate.tou) ;
		else
			this.disp_powthou(animate.pow, animate.tou) ;
		this.zone.refresh_pt() ;
	}
	this.suspend = function() {
		if ( ! isn(this.attrs.suspend) )
			return false ;
		// Use 'orig' to set number of counter in order it apears natural (no messages for counter adding) doesn't work over network
		// Defining changezone params
		var zone = game.player.battlefield ; // Current player plays spell (even if it's in an opponent's hand)
		var xzone = zone.grid.length - 3 ; // On BF's right to show they're not in play
		var yzone = this.place_row() ;
		(new Selection(this)).settype('suspend').changezone(zone, null, null, xzone, yzone) ;
		this.setcounter(this.attrs.suspend) ;
	}
	this.dredge = function(nb) {
		if ( isn(this.attrs.dredge) )
			nb = this.attrs.dredge ;
		else {
			if ( ! isn(nb) ) {
				log('Trying to dredge an undredgeable card, reverting to draw') ;
				this.owner.hand.draw_card() ;
				return null ;
			}
		}
		if ( this.owner.library.cards.length >= nb ) {
			var sel = new Selection(this) ;
			sel.type = 'dredge' ;
			sel.changezone(this.owner.hand) ;
			this.owner.library.changezone(this.owner.graveyard, nb) ;
			return true ;
		} else {
			message('You can\'t dredge '+nb+' with '+this.owner.library.cards.length+' cards in your library') ;
			return false ;
		}
	}
	this.cascade = function() {
		var tobottom = new Selection() ;
		var tobf = new Selection() ;
		var i = 0 ;
		while (this.zone.player.library.cards.length > i) {
			var card = this.zone.player.library.cards[this.zone.player.library.cards.length-1-i++] ;
			if ( ( ! card.is_land() ) && ( card.attrs.converted_cost < this.attrs.converted_cost ) ) {
				tobf.add(card) ;
				break ;
			} else
				tobottom.add(card) ;
		}
		if ( tobf.cards.length != 1 )
			message('Nothing to cascade under '+this.attrs.converted_cost) ;
		else
			tobf.changezone(this.zone.player.battlefield) ;
		if ( tobottom.cards.length > 0 ) {
			shuffle(tobottom.cards) ;
			tobottom.changezone(this.zone.player.exile) ;
			tobottom.changezone(this.zone.player.library, null, 0) ;
		}
	}
	this.living_weapon = function() {
		create_token('MBS', 'Germ', this.owner.battlefield, {'color': 'B', 'types': ['creature'], 'pow':0, 'thou':0}, 1, function(tk, lw) {
			tk.attach(lw) ;
		}, this) ;
	}
	this.manifest = function() {
		var zone = game.player.library ;
		if ( zone.cards.length > 0 ) {
			var tobf = new Selection(zone.cards[zone.cards.length-1]) ;
			tobf.changezone(game.player.battlefield, 'manifest') ;
		}
	}
}
function refresh_cards_in_zone(zone) {
	for ( var i = 0 ; i < zone.cards.length ; i++ )
		zone.cards[i].refresh() ;
}
function refresh_cards_in_selzone() {
	refresh_cards_in_zone(game.opponent.hand) ;
	refresh_cards_in_zone(game.opponent.battlefield) ;
	refresh_cards_in_zone(game.player.hand) ;
	refresh_cards_in_zone(game.player.battlefield) ;
}
