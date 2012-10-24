// card.js : Class and prototype for cards (and tokens, and duplicates) management
function Card(id, extension, name, zone, attributes) {
	Widget(this) ;
	this.type = 'card' ; // Used in right DND
	this.init('c_' + id, extension, name, zone, attributes, false) ;
	this.image_url = card_image_url(this.ext, this.name, this.attrs) ;
	this.bordercolor = 'black' ;
	this.setzone(zone) ; // Initial zone initialisation
	game.cards.push(this) ; // Auto referencing as a card
	if ( ( zone.type == 'library' ) && ( localStorage['check_preload_image'] == 'true' ) ) { // If option checked and not sideboard card
		this.attrs.visible = true ;
		this.load_image() ;
		if ( this.transformed_attrs && iss(this.transformed_attrs.name) ) { // And transformed one if needed
			this.attrs.transformed = true ;
			this.load_image() ;
			this.attrs.transformed = false ;
		}
		this.attrs.visible = null ;
		this.load_image() ; // Load BG
	}
}
function card_prototype() {
	// Methods
		// Purely object
	this.init = function(id, extension, name, zone, attributes) { // Common initialisation code between card and token
		// Basic data
		this.id = id ;
		this.zone = zone ;
		this.orig_zone = this.zone ; // Zone on game begin, used for siding
		this.init_zone = this.zone ; // Initial zone, as described in deckfile, used for reinit deck in side window
		this.owner = zone.player ;
		this.controler = zone.player ;
		this.name = name ;
		this.ext = extension ;
		// Canvas display
		this.w = cardwidth ;
		this.h = cardheight ;
		this.coords_set() ;
		this.targeted = false ;
		this.img = null ;
		// Attributes
		if ( ( typeof attributes == 'object' ) && ( attributes != null ) ) // typeof null == 'object', and null.attr crashes JS
			var attrs = attributes ;
		else
			var attrs = {} ;
		this.prev_visible = null ;
		this.init_attrs(attrs) ;
		// Expected and no need to sync
		this.expected_attrs('manas', attrs) ;
		//this.expected_attrs('color', attrs) ;
		this.expected_attrs('converted_cost', attrs) ;
		//this.expected_attrs('types', attrs) ;
		//this.expected_attrs('subtypes', attrs) ;
		//this.expected_attrs('tokens', attrs) ;
		this.expected_attrs('transformed_attrs', attrs) ;
		if ( this.transformed_attrs)
			attrs.transformed = false ; // Default value
		// / Attributes
		this.orig_attrs = attrs ;
		this.attrs = clone(this.orig_attrs) ;
		this.sync_attrs = clone(this.attrs) ; // Track changes
		// Watching in list
		this.watching = false ; // Nobody watches it
		return this ;
	}
	this.init_attrs = function(attrs) { // Initialises all "must have" variables
		if ( ! iso(attrs) )
			attrs = this.attrs ;
		attrs.visible = null ;
		if ( attrs.vanishing && ! attrs.counter )
			attrs.counter = attrs.vanishing ;
		if ( attrs.fading && ! attrs.counter )
			attrs.counter = attrs.fading ;
		attrs.attacking = false ;
		attrs.attachedto = null ;
		attrs.attached = new Array() ;
		attrs.damages = 0 ;
	}
	this.expected_attrs = function(param, attrs) { // 'move' data
		if ( attrs[param] ) {
			this[param] = attrs[param] ;
			delete attrs[param] ;
		}
	}
	this.toString = function() { return this.id } ;
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
	// Generic
	this.is_color = function(color) {
		if ( iss(this.attrs.color) && ( this.attrs.color.indexOf(color) != -1 ) )
			return true ;
		if ( iso(this.animated_attrs) && iss(this.animated_attrs.color) && ( this.animated_attrs.color.indexOf(color) != -1 ) )
			return true ;
		return false ;
	}
	this.is_supertype = function(type) {
		if ( iso(this.attrs.supertypes) && ( this.attrs.supertypes.indexOf(type) != -1 ) )
			return true ;
		if ( iso(this.animated_attrs) && iso(this.animated_attrs.supertypes) && ( this.animated_attrs.supertypes.indexOf(type) != -1 ) )
			return true ;
		return false ;
	}
	this.is_type = function(type) {
		if ( iso(this.attrs.types) && ( this.attrs.types.indexOf(type) != -1 ) )
			return true ;
		if ( iso(this.animated_attrs) && iso(this.animated_attrs.types) && ( this.animated_attrs.types.indexOf(type) != -1 ) )
			return true ;
		return false ;
	}
	this.is_subtype = function(subtype) {
		if ( this.attrs.changeling )
			return true ;
		var st = this.get_subtypes() ;
		if ( iso(st) && ( st.indexOf(subtype) != -1 ) )
			return true ;
		if ( iso(this.animated_attrs) && this.animated_attrs.changeling )
			return true ;
		if ( iso(this.animated_attrs) && iso(this.animated_attrs.subtypes) && ( this.animated_attrs.subtypes.indexOf(subtype) != -1 ) )
			return true ;
		return false ;
	}
	this.get_subtypes = function() {
		if ( this.attrs.transformed && iso(this.transformed_attrs) ) // Transformed subtypes REPLACE subtypes
			return this.transformed_attrs.subtypes ;
		else
			return this.attrs.subtypes ;
	}
	// Types
	this.get_types = function() {
		if ( this.attrs.transformed && iso(this.transformed_attrs) )
			return this.transformed_attrs.types ;
		else
			return this.attrs.types ;
	}
	this.is_creature = function() {
		return inarray('creature', this.get_types()) || iso(this.animated_attrs) ;
	}
	this.is_land = function() {
		return inarray('land', this.get_types()) ;
	}
	this.is_planeswalker = function() {
		return inarray('planeswalker', this.get_types()) ;
	}
	// Identity
	this.get_name = function(oldzone) {
		var name = 'a card' ; // Generic default
		if ( this.is_visible(oldzone) ) {
			if ( iso(this.attrs.transformed) )
				name = this.transformed_attrs.name ;
			else
				name = this.name ;
		} else
			if ( this.zone.type == 'battlefield' )
				name = 'faced down card' ;
		if ( iso(this.attrs.copy) && ( this.attrs.copy != null ) )
			name = this.attrs.copy.get_name()+' copied by '+name
		return name ;
	}
	this.debug_name = function() {
		return this.name+' ('+this+')' ;
	}
	// Selection
	this.selected = function() {
		return ( game.selected.cards.indexOf(this) > -1 ) ;
	}
	// Misc
	this.is_visible = function(oldzone) {
		if ( typeof oldzone != 'object' )
			oldzone = this.zone ;
		if ( oldzone == this.zone ) { // Just zone, not "from another zone to that one"
			if ( this.watching ) // Don't use normal visibility mecanism when watching a zone, as player is the only one who see the cards
				return true ;
			if ( typeof this.attrs.visible == 'boolean' ) // Forced as true or false, return value
				return this.attrs.visible ;
			else if ( this.attrs.visible != null ) // The only other possible value is null
				log(this+'\'s visibility ('+this.attrs.visible+') differs from known values : [ true | null | false ]') ;
			else { // Visibility is set as "null" wich means no value were forced (battlefield-hidden nor hand-revealed for example)
				//value depends on zone
				if ( this.zone.type == 'library' ) // Library is defaulted as not visible, exceptions :
					if ( ( this.owner.attrs.library_revealed ) && ( this.zone.cards.indexOf(this) == this.zone.cards.length-1 ) )
						return true ;
				return this.zone.default_visibility ;
			}
		} else { // From a zone to another (only used in "get_names()", as during changezone) card is visible if any of leaving/going zone is
			if ( ( this.attrs.visible == null ) && ( this.prev_visible == null ) ) // Card has no forced visibility nor it had in previous zone
				return ( this.zone.default_visibility || oldzone.default_visibility ) ; // Return lower visibility between old and new zone
			else { // Card has or had forced visibility
				//	Check if card WAS visible or IS visible
				var visibility_before = ( ( this.prev_visible !== false ) && oldzone.default_visibility ) ;
				var visibility_after = ( ( this.attrs.visible !== false ) && this.zone.default_visibility ) ;
				return visibility_before || visibility_after ;
			}
			// E.g. 
			// 	from BF to grave : visible (both are)
			// 	from library to BF : visible (only BF is, but opponnent will see the card)
			// 	from opponent's hand to opponent's library : NOT visible (both are not, you won't see the card)
		}
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
				case 'type' :
					if ( this.is_type(pieces[1]) )
						return true ;
					break ;
				case 'ctype' :
					if ( this.is_subtype(pieces[1]) )
						return true ;
				case 'name' :
					if ( this.is_visible() && ( this.name == pieces[1] ) )
						return true ;
					break ;
				default :
					log('Unknown boost condition : '+pieces[0]) ;
			}
		} // No condition were satisfied by card
		return false ;
	}
// === [ SETTERS ] =============================================================
	this.set_visible = function(visible) {
		this.prev_visible = this.attrs.visible ; // Backup previous for zonechange
		this.attrs.visible = visible ;
	}
	this.coords_set = function(x, y) {
		if ( ! isn(x) )
			x = 0 ;
		else
			x = Math.floor(x) ;
		if ( ! isn(y) )
			y = 0 ;
		else
			y = Math.floor(y) ;
		this.x = x ;
		this.y = y ;
		this.set_coords(this.x, this.y, this.w, this.h) ;
	}
// === [ DESIGN ] ==============================================================
	this.draw = function(context, x, y) {
		// First, draw attached in reverse order
		var attached = this.get_attached() ;
		for ( var j = attached.length -1 ; j >= 0 ; j-- ) { // Attached card position is stored in that card (for selection, click, etc.)
			var card = attached[j] ;
			var xo = this.x - card.x ;
			var yo = this.y - card.y ;
			card.draw(context, x-xo, y-yo) ;
		}
		// Then draw card on top
		context.save() ; // For rotation
		// Card may be drawn at specific coordinates (DND for ex), otherwise, draw it at its own coordinates*
		var indnd = ( isn(x) && isn(y) ) ;
		if ( ! isn(x) )
			x = this.x ; //- this.zone.x ;
		if ( ! isn(y) )
			y = this.y ; //- this.zone.y ;
		context.translate(x+this.w/2, y+this.h/2) ; // For rotation
		// Rotate
		var angle = 0 ;
		if ( ( this.zone.type == 'battlefield' ) && this.attrs.tapped )
			angle += 90 ;
		var is_top = this.zone.player.is_top ;
		if ( indnd && ( game.drag != null ) && game.selected.isin(this) && ( game.widget_under_mouse != null ) && ( game.widget_under_mouse.type == 'battlefield' ) ) // Draging current card on top BF
			is_top = game.widget_under_mouse.player.is_top ;
		if ( is_top && ( localStorage['invert_bf'] == 'true' ) )
			angle += 180 ;
		if ( angle > 0 )
			context.rotate(angle* Math.PI / 180) ;
		context.translate(-this.w/2, -this.h/2) ; // For rotation
		context.drawImage(this.cache, 0, 0) ;
		context.restore() ; // For rotation
	}
	this.refresh = function(from) { // Compute coordinates for all zone's cards, called on each adding/removing of cards, or visibility change
		var context = this.context ;
		context.clearRect(0, 0, this.w, this.h) ;
		// card border is a rectangle with width double (half shown, half hidden under image)
		var lw = 3 ;
		context.lineWidth = lw * 2 ;
		context.lineJoin = 'round' ;
		// Border color depending on card status
			// Outer line : UI card status (selected) 
		if ( ( this.zone.selzone ) && this.selected() )
			context.strokeStyle = 'lime' ;
		else
			context.strokeStyle = this.bordercolor ;
		context.strokeRect(lw, lw, this.w-2*lw, this.h-2*lw) ;
			// Inner line : card object status (attacking, revealed, won't untap)
		var color = this.bordercolor ;
		if ( this.zone.selzone && this.is_visible() ) {
			if ( this.zone.type == 'hand' ) {
				if ( this.attrs.visible == true )
					color = 'blue' ;
			} else if ( this.zone.type == 'battlefield' ) {
				if ( this.attrs.attacking )
					color = 'red' ;
				else if ( ( this.attrs.no_untap ) || ( this.attrs.no_untap_once ) )
					color = 'maroon' ;
			}
		}
		if ( color != this.bordercolor ) { // Need to draw inner line
			context.strokeStyle = color ;
			var offset = 1.5 ;
			context.strokeRect(lw+offset, lw+offset, this.w-2*(lw+offset), this.h-2*(lw+offset)) ;
		}
		// Image
		if ( this.img != null )
			context.drawImage(this.img, lw, lw, this.w-2*lw, this.h-2*lw) ;
		// Copy
		if ( iso(this.attrs.copy) && ( this.attrs.copy != null ) && ( this.attrs.copy.img != null ) ) {
			var offset = 10 ;
			var top = lw ;
			var bottom = this.h-lw ;
			var middle = lw + this.h/2
			var center = lw + this.w/2
			var left = lw+offset ;
			var right = this.w-lw ;
			context.save() ;
			context.beginPath();
			context.moveTo(right, top) ; // UR
			context.lineTo(right, bottom) ; // BR
			context.lineTo(left, bottom) ; // BL
			//context.lineTo(left, middle) ; // ML
			context.lineTo(center, top) ; // CT
			context.lineTo(right, top) ; // UR
			context.clip() ;
			context.drawImage(this.attrs.copy.img, lw, lw, this.w-2*lw, this.h-2*lw) ;
			context.restore() ;
		}
		if ( this.zone.type == 'battlefield' ) { // Textual infos (p/t, note, damages, counters)
			var top = lw ;
			var bottom = this.w-2*lw+1 ;
			var left = lw ;
			var right = this.h-2*lw+1 ;
			if ( this.is_visible() && ( localStorage['display_card_names'] == 'true' ) ) { // || ( game.hover == this )
				// Name
				var name = this.get_name() ;
				if ( name.length > 10 )
					var name = name.substr(0, 10)+'...' ;
				context.font = '7pt Arial' ;
				canvas_framed_text_tl(context, name, top, left, 'white', cardwidth-2*(lw+2), this.bordercolor) ;
			}
			context.font = '12pt Arial' ;
			// Note
			var note = this.getnote() ;
			if ( iss(note) && ( note != '' ) ) {
				if ( iso(this.note_img) ) {
					var w = 32 ;
					var h = w ;
					context.drawImage(this.note_img, (this.w-w)/2, (this.h-h)/2, w, h) ;
				} else 
					canvas_framed_text_c(context, note, this.w/2, this.h/2, 'white', cardwidth-2*(lw+2)-1, this.bordercolor) ;
			}
			// Damages
			var damages = this.get_damages() ;
			if ( damages > 0 )
				canvas_framed_text_br(context, damages, bottom, right-12-5, 'red', cardwidth, this.bordercolor) ;
			// Counters
			var counters = this.getcounter() ;
			if ( counters > 0 ) {
				var color = 'white' ;
				if ( this.is_planeswalker() && ( this.get_damages() >= counters ) )
					color = 'red' ;
				canvas_framed_text_bl(context, counters, top, right, color, cardwidth, this.bordercolor) ;
			}
			// PT
			if ( iss(this.pow_thou) && ( this.pow_thou != '' ) )
				canvas_framed_text_br(context, this.pow_thou, bottom, right, this.pow_thou_color, cardwidth, this.bordercolor) ;
		}
		if ( ( this.zone.type == 'hand' ) && ( localStorage['display_card_names'] == 'true' ) && this.hasOwnProperty('manas') && this.is_visible() ) {
			// Display mana cost
				// Compute begining of cost displaying zone
			var size = 16 ;
			var w = this.manas.length * size ; // Width of an icon
			var mw = cardwidth-2*(lw+2) ; // Max width all icon can take
			if ( w > mw ) { // Cost larger than card
				iw = Math.floor(mw/this.manas.length) ; // Compute new icon width
				w = iw * this.manas.length ;
			} else
				iw = size ;
				// Display each icon
			for ( var i = 0 ; i < this.manas.length ; i++ ) {
				var color = this.manas[i] ;
				if ( ! game.manaicons[color] )
					game.image_cache.load(theme_image('/ManaIcons/'+color+'.png'), function(img, data) {
						var color = data[0] ;
						game.manaicons[color] = img ;
						var card = data[1] ;
						card.refresh('mana image loaded') ;
					}, function(data) {
						var color = data[0] ;
						log('Image not found for '+color) ;
					}, [color, this]) ;
				else
					context.drawImage(game.manaicons[color], this.w - lw - w + iw * i, lw, iw, size) ;
			}
		}
		// UI status (targeted, attaching ...)
		var color = this.bordercolor
		if ( game.dragover == this )
			color = 'purple' ;
		else if ( ( game.target.tmp != null ) && ( game.target.tmp.targeted == this ) )
			color = 'yellow' ;
		if ( color != this.bordercolor ) {
			canvas_set_alpha(zopacity, context) ;
			context.fillStyle = color ;
			context.fillRect(0, 0, this.w, this.h) ;
			canvas_reset_alpha(context) ;
		}
		/*
		if ( !iss(from) )
			from = '?' ;
		refresh_card_count++ ;
		log('refresh '+refresh_card_count+' '+from) ;
		*/
	}
	this.rect = function() { // Coordinates of rectangle representation of card (for "under mouse")
		if ( this.attrs.tapped ) {
			var d = ( this.h - this.w ) / 2
			var x = this.x - d ;
			var y = this.y + d ;				
			var w = this.h ;
			var h = this.w ;
		} else {
			var x = this.x ;
			var y = this.y ;
			var w = this.w ;
			var h = this.h ;
		}
		return new rectwh(x, y, w, h) ;
	}
// === [ IMAGE ] ===============================================================
	this.load_image = function(callback, param) {
		this.img_loading = game.image_cache.load(this.imgurl(), function(img, card) {
			if ( img == card.img_loading ) { // Load image only if it's last asked
				var prev_img = card.img ;
				card.img = img ;
				if ( isf(callback) )
					callback(img, param) ;
				card.refresh('load_image') ;
				if ( ( prev_img != img ) && ( ! card.zone.selzone ) ) { // In unselzones, refresh entire zone cache
					if ( card.zone.type == 'library' ) { // For library, only refresh top card
						if ( card.IndexInZone() == card.zone.cards.length-1 )
							card.zone.refresh() ;
					} else
						card.zone.refresh() ;
				}
				draw() ;
			}
		}, function(card, zone) {
			log('Image not found for '+card.name+', creating text') ;
		}, this, param) ;
	}
	this.imgurl = function(small) { // Return image URL depending on image status
		if ( this.is_visible() ) {
			var url = this.imgurl_relative() ;
			if ( small )
				return card_images('../THUMB/'+url) ;
			else
				return card_images(url) ;
		} else
			if ( small )
				return card_images('../THUMB/back.jpg') ;
			else
				return card_images('back.jpg') ;
	}
	this.imgurl_relative = function() {
		if ( ( this.attrs.transformed ) && this.transformed_attrs && iss(this.transformed_attrs.name) )
			return card_image_url(this.ext, this.transformed_attrs.name, this.attrs) ;
		else
			return this.image_url ;
	}
	this.zoom = function(doc) {
		if ( ! doc )
			var doc = document ;
		var zoom = doc.getElementById('zoom') ;
		zoom.thing = this ;
		// Load image
		game.image_cache.load(this.imgurl(), function(img, card) {
			doc.getElementById('zoom').src = img.src ;
		}, function(card, url) {}, this) ;
		// Manage image events
		if ( this.is_visible() ) { // Image visible
			if ( this.transformed_attrs ) { // Transform
				// Give "zoom" image ability to display other face when hovered
				zoom.onmouseover = function(ev) {
					var card = ev.target.thing ;
					card.attrs.transformed = ! card.attrs.transformed ; // Invert card's value on mouseover and restore on mouseout
					card.zoom() ;
				} ;
				zoom.onmouseout = zoom.onmouseover ;
			}
			zoom.oncontextmenu = function(ev) { // Overwrite previous listener
				var card = ev.target.thing ;
				// Zoom will simulate card's menu
				if ( ( card.zone.type == 'battlefield' ) || ( card.zone.type == 'hand' ) )
					game.selected.set(card) ;
				var menu = card.menu(ev)
				return eventStop(ev) ;
			} ;
			zoom.onmousedown = function(ev) {
				var card = ev.target.thing ;
				card.mousedown(ev) ;
				return eventStop(ev) ;
			}
		} else { // hidden cards
			zoom.oncontextmenu = eventStop ; // Overwrite previous listener
			zoom.onmousedown = eventStop ;
		}
	}
// === [ EVENTS ] ==============================================================
	this.mouseover = function(ev) {
		var name = this.get_name() ;
		if ( name.length > 10 ) 
			game.settittle(name) ;
		this.zoom() ;
		game.hover = this ;
		this.refresh() ; // For bug "cards reversed in starting hand"
		if ( ( game.draginit == null ) && ( game.current_targeting == null ) ) // Not dragging nor targeting
			game.canvas.style.cursor = 'pointer' ;
		if ( ( this.zone.type == 'battlefield' ) && ( this.attrs.attachedto == null ) ) {
			var idx = this.IndexInZone() ;
			if ( idx != 0 ) { // If not last card in its zone, set it last (will be drawn after other, and appear over)
				this.zone.cards.splice(idx, 1) ;
				this.zone.cards.push(this) ;
			}
		}
		if ( this.zone.selzone ) { // BF + hand utils (DND, target)
			if ( game.draginit != null ) { // While left click holding
				if ( game.draginit == this ) // Return on drag origin
					game.drag = null ; // Undisplay DND helper
				else { // Hover another card while DND
					game.dragover = this ; // Mark as draged over
					this.refresh('draged over') ;
				}
			}
			if ( game.target.tmp != null ) { // Targeting in progress
				if ( inarray(game.target.tmp.cards, this) ) // Card is targeting
					game.target.tmp.stop(null) ; // Hide targets
				else { // Card isn't selected, becomes targeted
					game.target.tmp.over(this) ;
					this.refresh('targeted') ;
				}
			}
		}
		// Display hovered card on top of other
		if ( ! this.zone.selzone ) {
			this.zone.refresh() ;
			draw() ;
		}
	}
	this.mouseout = function(ev) {
		game.settittle('') ;
		if ( ( game.draginit == null ) && ( game.current_targeting == null ) )
			game.canvas.style.cursor = '' ;
		if ( this.zone.selzone ) { // BF + hand utils (DND, target)
			if ( game.draginit != null ) { // While left click holding
				if  ( game.draginit == this ) { // Leave drag origin
					drag_start() ;
					game.canvas.style.cursor = 'move' ;
				} else {
					game.dragover = null ; // Mark as draged out
					this.refresh('draged out') ;
				}
			}
			if ( game.current_targeting == this ) { // Leave while right click holding & card is targeting
				game.target.start(game.selected.get_cards(), ev) ;
				this.refresh('/targeted') ;
			} else 
				if ( game.target.tmp != null ) {
					game.target.tmp.out(this) ;
					this.refresh('/targeted') ;
				}
		}
		// Display hovered card on top of other
		if ( ! this.zone.selzone ) {
			this.zone.refresh() ;
			draw() ;
		}
	}
	this.mousedown = function(ev) {
		switch ( ev.button ) {
			case 0 : // Left click : Select (or deselect, or add to selection)
				//if ( XOR(ev.shiftKey, ( game.turn.steps[game.turn.step].name == 'attackers' ) ) ) // With Shift
				if ( ev.shiftKey ) // With Shift
					game.selected.toggle(this)
				else // Normal click
					drag_init(this, ev) ;		
				break ;
			case 1 : // Middle button click
				this.info() ;
				return eventStop(ev) ; // Must be done in mousedown in order to cancel default action for middle clicking an image : open it in a new tab
			case 2 : // Right click
				if ( ! this.selected() ) // Right clicking on a card that isn't selected
					game.selected.set(this) ; // Select only that one
				// Prepare targeting
				if ( game.current_targeting == null )
					game.current_targeting = this ;
				break ;
		}
	}
	this.mouseup = function(ev) {
		if ( game.target.tmp != null ) {
			game.target.tmp.stop(this) ;
			this.refresh() ;
		} else {
			// Drop
			if ( game.drag != null ) {
				switch ( this.zone.type ) {
					case 'battlefield' :
						selected = game.selected.get_cards() ;
						for ( var i in selected )
							this.attach(selected[i]) ;
						break ;
					case 'hand' :
						var index = this.IndexInZone() ;
						if ( game.selected.zone == this.zone )
							game.selected.moveinzone(index) ;
						else
							game.selected.changezone(this.zone, null, index) ;
							
						break ;
					default :
						log('Impossible to drop '+game.drag+' on '+this+' in '+this.zone) ;
				}
				game.dragover = null ;
				this.refresh() ;
			}
			// Damages
			if ( ev.ctrlKey ) {
				switch ( ev.button ) {
					case 0 : // Left click
						this.set_damages(this.get_damages()+1) ;
						break ;
					case 1 : // Middle button click
						this.set_damages(0) ;
						break ;
					case 2 : // Right click
						this.set_damages(this.get_damages()-1) ;
						break ;
				}
			} else
				switch ( ev.button ) {
					case 2 : // Right click
						this.menu(ev) ;
				}
		}
		return eventStop(ev) ;
	}
	this.dblclick = function(ev) {
		if ( ( ! this.controler.access() ) || ( ev.ctrlKey ) || ( ev.shiftKey ) ) // Ctrl is for damages, Shift for selection, we don't want to trigger dblclick
			return eventStop(ev) ;
		switch ( this.zone.type ) {
			case 'battlefield' :
				// Card's controler is declaring attackers
				if ( this.is_creature() && ( this.zone.player == game.turn.current_player ) && ( game.turn.step == 5 ) )
					game.selected.attack(this) ; // Must replace tap because vigilance creatures don't tap
				else
					game.selected.tap(!this.attrs.tapped) ;
				break ;
			case 'hand' :
				var visible = null ; // Set to default visibility (not forced true) in order not to sync in default case
				if ( ev.ctrlKey || ev.altKey || ev.shiftKey )
					visible = false ;
				// Changezone
				game.selected.changezone(game.selected.zone.player.battlefield, visible, null) ;
				// Create living weapon token
				if ( this.attrs.living_weapon )
					create_token('MBS', 'Germ', this.zone, {'types': ['creature'], 'pow':0, 'thou':0}, 1, function(tk, lw) {
						tk.attach(lw) ;
					}, this) ;
				break ;
			default :
				log('Impossible to dbclick a card in '+this.zone.type) ;
		}
		return eventStop(ev) ; // Without, dblclick is passed under, that means 2 events are triggered on a card when
		// player dblclick on a text on a card (1 for the text, 1 for the card under)
	}
	this.dragstart = function(ev) {
		// Difference between position of each card and position of reference card's : when moving multiple cards, apply the same scheme on destination/drop
		switch ( this.zone.type ) {
			case 'battlefield' : // On BF : difference in X and Y coords in grid
				for ( var i = 0 ; i < game.selected.cards.length ; i++ ) {
					var mycard = game.selected.cards[i] ;
					mycard.xoffset = mycard.grid_x - this.grid_x ;
					mycard.yoffset = mycard.grid_y - this.grid_y ;
				}
				game.selected.cards.sort(function(a,b) { // Sort cards by coordinates
					if ( a.grid_x == b.grid_x ) {
						if ( a.grid_y == b.grid_y )
							return 0
						else
							return a.grid_y - b.grid_y ;
					} else
						return a.grid_x - b.grid_x ;
				}) ;
				break ;
			case 'hand' : // In hand : diffenrece in index in zone
				game.selected.cards.reverse() ;
				var xoffset = this.IndexInZone() ;
				for ( var i = 0 ; i < game.selected.cards.length ; i++ ) {
					var mycard = game.selected.cards[i] ;
					mycard.xoffset = xoffset - mycard.IndexInZone()  ;
					mycard.yoffset = 0 ;
				}
				break ;
			default : // Elsewhere : null offset as only 1 card may be DNDed
				for ( var i = 0 ; i < game.selected.cards.length ; i++ ) {
					var mycard = game.selected.cards[i] ;
					mycard.xoffset = 0 ;
					mycard.yoffset = 0 ;
				}
		}
	}
// === [ MENU ] ================================================================
	this.menu = function(ev) {
		var card = this ;
		switch ( card.zone.type ) {
			case 'battlefield' :
				if ( card.controler.access() )
					var selected = game.selected.get_cards() ;
				else // Current player has no access on card (spectactor), he can't have a selection. Let's create one with only current card
					var selected = [card] ; //new Selection([card])
				var menu = new menu_init(selected) ;
				if ( selected.length > 1 )
					menu.addline(selected.length+' cards in '+selected[0].zone.get_name()) ;
				else
					menu.addline(selected[0].get_name()) ;
				menu.addline() ;
				if ( ! card.controler.access()  )
					menu.addline('No action') ;
				else {
					if ( card.attrs.tapped )
						msg = 'Untap' ;
					else
						msg = 'Tap' ;
					menu.addline(msg,  game.selected.tap, ! card.attrs.tapped).override_target = game.selected ;
					// Attacking status
					if ( card.attrs.attacking ) // Any moment, if attacking
						menu.addline('Cancel attack', game.selected.attack, this).override_target = game.selected ;
					// Card's controler is declaring attackers
					if ( card.is_creature() && ( card.zone.player == game.turn.current_player ) && ( game.turn.step == 5 ) ) // During attackers declaration step
						menu.addline('Attack without tapping', game.selected.attack_notap, this).override_target = game.selected ;
					//menu.addline() ;
					var submenu = new menu_init(selected) ;
					this.changezone_menu(submenu) ;
					menu.addline('Move', submenu) ;
					menu.addline() ;
					// Morph
					if ( card.owner.me && iss(card.attrs.morph) )
						if ( card.attrs.visible == false) {
							card.attrs.visible = true ; // Temporary shunt visibility for morph display
							menu.addline('Morph as '+card.name+' ('+card.attrs.morph+')', card.face_up).moimg = card.imgurl() ;
							card.attrs.visible = false ; // End of Temporary shunt visibility for morph display
						} else {
							menu.addline('Unmorph', card.morph).moimg = card.imgurl() ;
						}
					// Transform
					if ( card.transformed_attrs ) {
						var l = menu.addline('Transform', card.toggle_transform) ;
						l.checked = card.attrs.transformed ;
						this.attrs.transformed = ! this.attrs.transformed ;
						var test = card.imgurl() ;
						l.moimg = test ;
						this.attrs.transformed = ! this.attrs.transformed ;
					}
					// P/T
					var pt = menu.addline('Set P/T', 		card.ask_powthou) ;
					pt.buttons.push({'text': '+1', 'callback': function(ev, cards) {
						(new Selection(cards)).add_powthou(1, 1) ;
					}}) ;
					pt.buttons.push({'text': '-1', 'callback': function(ev, cards) {
						(new Selection(cards)).add_powthou(-1, -1) ;
					}}) ;
					/*if ( isb(card.attrs.switch_pt) )
						var switch_pt = card.attrs.switch_pt ;
					else
						var switch_pt = false ;
					menu.addline('Switch P/T', 		card.switch_powthou).checked = switch_pt ;*/
					var pteot = menu.addline('Change P/T until EOT',	card.ask_powthou_eot) ;	
					pteot.buttons.push({'text': '+1', 'callback': function(ev, cards) {
						(new Selection(cards)).add_powthou_eot(1, 1) ;
					}}) ;
					pteot.buttons.push({'text': '-1', 'callback': function(ev, cards) {
						(new Selection(cards)).add_powthou_eot(-1, -1) ;
					}}) ;
					/*if ( isb(card.attrs.switch_pt_eot) )
						var switch_pt_eot = card.attrs.switch_pt_eot ;
					else
						var switch_pt_eot = false ;
					menu.addline('Switch P/T until EOT', 	card.switch_powthou_eot).checked = switch_pt_eot ;*/
					// Counters
					var c = menu.addline('Set counters',		card.setcounter) ;
					if ( card.attrs.transformed && card.transformed_attrs)
						var steps = clone(card.transformed_attrs.steps) ;
					else
						var steps = clone(this.attrs.steps)
					if ( ( ! steps ) || ( steps.length < 1 ) )
						var steps = ['+1', '-1'] ;
					if ( ! this.attrs.counter )
						var counter = 0 ;
					else
						var counter = this.attrs.counter ;
					if ( this.is_planeswalker() && ! inarray('-X', steps) )
						steps.push('+X') ;
					for ( i in steps ) {
						var step = parseInt(steps[i]) ;
						if ( step != 0 ) {
							var compstep = step ; // Step used just for comparison
							if ( isNaN(compstep) )
								compstep = -1 ;
							var menubut = {'text': steps[i]} ;
							if ( counter + compstep >= 0 ) {
								menubut.callback = function(ev, cards, param) {
									(new Selection(cards)).add_counter(param) ;
								}
								menubut.param = step ;
							}
							c.buttons.push(menubut) ;
						}
					}
					// Note
					menu.addline('Set a note', card.setnote) ;
					// Tokens
					var tokens = [] ;
					if ( card.attrs.transformed && card.transformed_attrs ) {
						if ( card.transformed_attrs.tokens )
							tokens = card.transformed_attrs.tokens ;
					} else {
						if ( card.attrs.tokens )
							tokens = card.attrs.tokens ;
					}
					for ( var i = 0 ; i < tokens.length ; i++ ) {
						var ext = card.ext ;
						var name = tokens[i].name ;
						var attrs = tokens[i].attrs ;
						var nname = name ; // Name + number, for eldrazi spawn or other multiple-images tokens
						if ( name == 'Eldrazi Spawn' ) // No number specified, add one at random
							nname += rand(3) + 1 ;
						var img = nname ; // Base img
						if ( isn(attrs.pow) && isn(attrs.thou) ) // Emblems doesn't have them
							img += '.'+attrs.pow+'.'+attrs.thou ;
						img += '.jpg' ;
						// Little workaround to find an existing image if token isn't from extension
						// (generally it's from an older extension from the bloc)
						if ( ! iso(game.tokens_catalog[ext]) || ! iss(game.tokens_catalog[ext][img]) ) {
							if ( iso(game.tokens_catalog['EXT']) && iss(game.tokens_catalog['EXT'][img]) )
								ext = 'EXT' ;
							else {
								for ( var j in game.tokens_catalog )
									if ( iss(game.tokens_catalog[j][img]) ) {
										ext = j ;
										break ;
									}
								log(img+' found in no extension') ;
							}
						}
						if ( isn(attrs.pow) && isn(attrs.thou) )
							var txt = 'Token '+name+' '+attrs.pow+'/'+attrs.thou ;
						else
							var txt = name ;
						if ( tokens[i].nb > 1 )
							txt += ' x '+tokens[i].nb
						var l = menu.addline(txt, create_token, ext, name, card.zone, attrs, tokens[i].nb) ;
						l.moimg = card_images(token_image_url(ext, nname, attrs)) ;
					}
					// Animate
					if ( iso(card.attrs.animate) ) {
						for ( var i = 0 ; i < card.attrs.animate.length ; i++ ) {
							var anim = card.attrs.animate[i] ;
							var name = 'Animate as '+anim.pow+'/'+anim.tou
							if ( iso(anim.subtypes) )
								name += ' '+anim.subtypes.join(' ') ;
							if ( iss(anim.cost) )
								name += ' ('+anim.cost+')' ;
							menu.addline(name, card.animate, anim) ;
						}
					}
					menu.addline() ;
					if ( card.is_visible() )
						fufunc = card.face_down ;
					else
						fufunc = card.face_up ;
					menu.addline('Face down',		fufunc).checked = ( card.attrs.visible == false ) ;
					menu.addline('Duplicate',		card.duplicate) ;
					//menu.addline('Switch controler',	card.controler_switch) ;
					// Copy
					var tby = game.target.targetedby(card) ;
					if ( ( tby.length == 1 ) && ( ( tby[0].type == 'card' ) || ( tby[0].type == 'token' ) ) ) { 
						if ( ( iso(this.attrs.copy) ) && ( tby[0] == this.attrs.copy ) ) {
						} else
							menu.addline('Copy '+tby[0].get_name(),	card.copy) ;
					}
					if ( iso(this.attrs.copy) && ( this.attrs.copy != null ) )
						menu.addline('Uncopy '+this.attrs.copy.get_name(),	card.uncopy) ;
					// Cascade
					if ( this.attrs.cascade) {
						menu.addline('Cascade ', this.cascade) ;
					}
					// No untap
					menu.addline() ;
					if ( isb(card.attrs.no_untap) )
						var no_untap = card.attrs.no_untap ;
					else
						var no_untap = false ;
					menu.addline('Won\'t untap',		card.untap_toggle).checked = no_untap ;
					if ( isb(card.attrs.no_untap_once) )
						var no_untap_once = card.attrs.no_untap_once ;
					else
						var no_untap_once = false ;
					menu.addline('Won\'t untap once',	card.untap_once_toggle).checked = no_untap_once ;
				}
				break ;
			case 'hand' :
				if ( card.controler.access() )
					var selected = game.selected.get_cards() ;
				else // Current player has no access on card (spectactor), he can't have a selection. Let's create one with only current card
					var selected = [card] ; //new Selection([card])
				var menu = new menu_init(selected) ;
				menu.addline(selected.length+' cards in '+selected[0].zone.get_name()) ;
				menu.addline() ;
				if ( card.controler.access()  ) {
					this.changezone_menu(menu) ;
					menu.addline() ;
					var line = menu.addline('Reveal', 	game.selected.toggle_reveal_from_hand, card)
					line.checked = ( card.attrs.visible == true ) ;
					line.override_target = game.selected ;
					if ( iss(card.attrs.morph) )
						menu.addline('Morph', card.morph) ;
					if ( isn(card.attrs.suspend) )
						menu.addline('Suspend ('+card.attrs.suspend_cost+')', card.suspend) ;
					if ( iss(card.attrs.cycling) )
						menu.addline('Cycle ('+card.attrs.cycling+')',	card.cycle) ;
				}
				break ;
			case 'library' :
			case 'graveyard' :
			case 'exile' :
				var menu = new menu_init() ;
				this.changezone_menu(menu) ;
				break ;
			default : 
				menu.addline('No menu on a card from zone ' + card.zone.type) ;
		}
		menu.addline() ;
		if ( card.is_visible() ) {
			menu.addline('Informations (MCI)', card.info) ;
			if ( localStorage['debug'] == 'true' )
				menu.addline('Debug internals', function(card) {
					log2(this) ;
					log2(this.attrs) ;
					if ( iso(this.attrs.bonus) )
						log2(this.attrs.bonus) ;
				}) ;
		} else
			menu.addline('No information aviable from hidden card') ;
		menu.start(ev) ;
		return menu ;
	}
	this.changezone_menu = function(menu, target) { // Generate a menu depending on current zone, to send card to each other zone
		var card = this ;
		var player = card.owner ;
		if ( target ) // Called from listeditor
			var sel = new Selection([target]) ;
		else // Called from SVG
			var sel = game.selected ;
		if ( this.zone.type != 'battlefield' ) {
			menu.addline('To battlefield',	sel.changezone, player.battlefield).override_target = sel ;
			menu.addline('Play face down',	sel.changezone, player.battlefield, false).override_target = sel ;
		}
		if ( this.zone.type != 'hand' )
			menu.addline('To hand',			sel.changezone, player.hand).override_target = sel ;
		if ( this.zone.type != 'library' ) {
			menu.addline('To top deck',		sel.changezone, player.library).override_target = sel ;
			menu.addline('To bottom deck',		sel.changezone, player.library, null, 0).override_target = sel ;
		} else {
			var i = card.IndexInZone() ;
			var j = card.zone.cards.length - 1 ;
			if ( i != j )
				menu.addline('To top deck',		sel.moveinzone, j).override_target = sel ;
			if ( i != 0 )
				menu.addline('To bottom deck',		sel.moveinzone, 0).override_target = sel ;
		}
		if ( this.zone.type != 'graveyard' )
			menu.addline('To graveyard',		sel.changezone, player.graveyard).override_target = sel ;
		if ( this.zone.type != 'exile' )
			menu.addline('To exile',		sel.changezone, player.exile).override_target = sel ;
	}
	this.controler_switch = function() {
		this.controler = this.controler.opponent ;
		this.changezone(this.controler.battlefield) ;
	}
	this.info = function() {
		var res = this.is_visible() ;
		if ( res )
			window.open('http://magiccards.info/query?q=!'+this.name+'&v=card&s=cname') ;
		else
			log('You can\'t ask info for hidden card') ;
		return res ;
	}
// === [ ZONE MANAGEMENT ] =====================================================
	// Workaround for selections
	this.changezone = function(zone, visible, index, xzone, yzone) {
		if ( game.selected ) // Flashback not clearing selection
			game.selected.clear() ;
		// Automatically place
		if ( ! isn(xzone) )
			xzone = 0 ;
		if ( ! isn(yzone) )
			yzone = this.place_row() ;
		var sel = new Selection([this]) ;
		var result = sel.changezone(zone, visible, index, xzone, yzone) ;
		return result ;
	}
	this.changezone_recieve = function(zone, visible, index, xzone, yzone) {
		if ( typeof visible != 'boolean') 
			visible = null ;
		if ( !isn(index) )
			index = null ;
		if ( !isn(xzone) )
			xzone = null ;
		if ( !isn(yzone) )
			yzone = null ;
		//var sel = new Selection([this]) ;
		game.selected.set(this) ; // Use global selection in order to keep it up to date
		return game.selected.changezone_recieve(zone, visible, index, xzone, yzone) ;
	}
	this.moveinzone = function(to_index) {
		var sel = new Selection([this]) ;
		return sel.moveinzone(to_index) ;
	}
	// Zone management
	this.IndexInZone = function() { // Return card's index in its zone
		return this.zone.cards.indexOf(this) ;
	}
	this.setzone = function(zone, visible, index, xzone, yzone) { // Change a card's zone, here goes stuff to do on new zone
		if ( ( zone.type != 'battlefield' ) && ( this.owner != zone.player ) ) {
			zone = this.owner[zone.type] ;
		}
		var oldzone = this.zone ;
		this.zone = zone ; // Set new zone
		if ( typeof visible != 'boolean' )
			visible = null ; // No forced value, behaviour will only depend on zone
		if ( ! isn(index) )
			index = zone.cards.length ;
		this.zone.cards.splice(index, 0, this) ; // Insert this card into zone
		if ( visible == false ) { // Card forced as face down
			this.on_face_down() ;
			this.sync() ; // Face up on cards played face down from hand doesn't works without
		} else {
			if ( ( oldzone.type != 'battlefield' ) || ( this.zone.type != 'battlefield' ) ) { // Don't reinit if going from a BF to a BF
				var vi = this.attrs.visible ;
				this.attrs = clone(this.orig_attrs) ; // Resync with creation attrs, because they will change afterward
				this.attrs.visible = vi ;
				delete this.animated_attrs ;
			}
			this.set_visible(visible) ;
		}
		switch ( this.zone.type ) { // Specific actions depending on zone type
			case 'battlefield' : // to a battlefield
				if ( iss(this.attrs.ciptc) && eval(this.attrs.ciptc) ) // Condition for coming into play tapped
					this.attrs.tapped = true ;
				// Force xdest,ydest to enter in grid, as when moving to extremes, system may be thinking we are moving outside
				xzone = max(xzone, 0) ;
				xzone = min(xzone, bfcols-1) ;
				yzone = max(yzone, 0) ;
				yzone = min(yzone, bfrows-1) ;
				if ( ! this.place_recieve(xzone, yzone) )
					log('Failed to place '+this.name+' at '+xzone+', '+yzone) ;
				this.refreshpowthou() ;
				this.zone.refresh_card(this) ;
				break ;
			default :
				this.refresh('setzone') ;
				// Refresh new zone's cards coordinates
				if ( isf(this.zone.refresh) ) // Some virtual or non visual zones have none (sideboard)
					this.zone.refresh() ;
		}
		this.load_image() ; // Visibility possibly changed, reload image
		return true ;
	}
	this.position_name = function(word, link, index, zone) { // Returns for a card, a string describing the position in a zone
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
	this.place_row = function() { // Returns wich row to place card by default (depending on its type and user configuration)
		var optype = '' ;
		if ( this.is_creature() )
			optype = 'place_creatures' ;
		else 
			if ( this.is_land() )
				optype = 'place_lands' ;
			else
				optype = 'place_noncreatures' ;
		var yzone = 0 ;
		switch ( localStorage[optype] ) {
			case 'top' :
				yzone = 0 ;
				break ;
			case 'middle' :
				yzone = Math.floor(bfrows/2) ;
				break ;
			case 'bottom' :
				yzone = bfrows - 1 ;
				break ;
			default :
				switch ( optype ) {
					case 'place_noncreatures' :
						yzone = 0 ; // Noncreatures spells on first line
						break ;
					case 'place_creatures' :
						yzone = Math.floor(bfrows/2) ; // Creatures on middle line
						break ;
					case 'place_lands' :
						yzone = bfrows - 1 ; // Lands on last line
						break ;
					default :
						log('Place problem') ;
				}
		}
		return yzone ;

	}
		// Movement
	this.place = function(xzone, yzone) {
		if ( ! isn(xzone) )
			xzone = null ;
		if ( ! isn(yzone) )
			yzone = null ;
		var res = this.place_recieve(xzone, yzone) ;
		if ( res )
			action_send('place', {'card': this.id, 'x': this.grid_x, 'y': this.grid_y}) ;
		return res ;
	}
	this.place_recieve = function(xzone,yzone) { // Move card to x,y if unoccupied, else x+1,y, etc.
		if ( this.zone.type != 'battlefield' ) {
			log('Unable to place a card ('+this.name+') in '+this.zone) ;
			return false ;
		}
		// Default place
		if ( ! isn(xzone) )
			xzone = 0 ;
		if ( ! isn(yzone) )
			yzone = 0 ;
		// Force xdest,ydest to enter in grid
		xzone = max(xzone, 0) ;
		xzone = min(xzone, bfcols-1) ;
		yzone = max(yzone, 0) ;
		yzone = min(yzone, bfrows-1) ;
		// In case of moving multiple cards to battlefield, each one has offsets indicating relative positions of each other
		var i = xzone ;
		var j = yzone ;
		// Loop once on every cards in grid
		var oneturn = false ;
		while ( ! this.move(i, j) ) {
			if ( oneturn && ( xzone == i ) && ( yzone == j ) ) { // We tried all positions
				log('Tried all positions, zone is full') ;
				return false ;
			}
			i += place_offset ;
			if ( i >= this.zone.grid.length ) { // End of line of grid, go next line
				i = 0 ;
				j += place_offset ;
				if ( j >= this.zone.grid[i].length ) {
					if ( ! oneturn ) { // End of grid, mark we end it 1 time
						oneturn = true ;
						j = 0 ;
					}
				}
			}
		}
		this.zone.refresh_card(this) ;
		return true ;
	}
	this.move = function(xdest, ydest) { // Move if possible a card to x, y, returns false otherwise
		if ( this.zone.type != 'battlefield' ) {
			log('Unable to move a card ('+this.name+') on a '+this.zone.type) ;
			return false ;
		}
		// Force xdest,ydest to enter in grid, as when moving to extremes, system may be thinking we are moving outside
		xdest = max(xdest, 0) ;
		xdest = min(xdest, bfcols-1) ;
		ydest = max(ydest, 0) ;
		ydest = min(ydest, bfrows-1) ;
		var destination = this.zone.grid[xdest][ydest] ;
		if ( destination != null ) { // Check if destination is clearable
			if ( ( destination.grid_x != xdest ) || ( destination.grid_y != ydest ) ) { // Checking if it's still here
				this.zone.grid[xdest][ydest] = null ; // else clear (garbage collected)
				log('A card previously declared as being at '+xdest+', '+ydest+' isn\'t there anymore : '+destination.get_name()+' ('+destination+') : cleaned') ;
			}
		}
		var destination = this.zone.grid[xdest][ydest] ;
		result = ( ( destination == null ) || ( destination == this ) ) ;
		if ( result ) { // There is no card on destination : move
			if ( this.get_attachedto() != null ) {
				this.detach() ;
				//this.sync() ;
				this.sync_attrs = clone(this.attrs) ;
			} else
				this.clean_battlefield() ;
			this.set_grid(xdest, ydest) ;
			this.zone.grid[this.grid_x][this.grid_y] = this ; // Move
			// Consider attached cards as being @ new pos
			if ( this.attrs.attached )
				for ( var i = 0 ; i < this.attrs.attached.length ; i++ )
					this.attrs.attached[i].set_grid(xdest, ydest) ;
			game.sound.play('click') ;
		}
		return result ;
	}
	this.set_grid = function(x, y) {
		this.grid_x = x ;
		this.grid_y = y ;
	}
	this.clean_battlefield = function() { // Clean old position in BF after moving a card
		// Clean old position
		if ( ! this.zone.ingrid )
			log(this.zone+'') ;
		else {
			if ( isn(this.grid_x) && isn(this.grid_y) ) {
				if ( this.zone.ingrid(this.grid_x, this.grid_y) ) { // Only working of it was not in another zone before
					if ( this.zone.grid[this.grid_x][this.grid_y] == this ) { // card is root card (card @ current position, not attached to it)
						this.zone.grid[this.grid_x][this.grid_y] = null ; // Clean battlefield
						//log(this.get_name()+' : cleaning '+this.grid_x+', '+this.grid_y)
					}
					// Else, card was a card attached to root card, nothing should be done
				} else 
					log('Can\'t clean a card at '+this.grid_x+', '+this.grid_y+' : not in grid : '+this.get_name()) ;
			}/* else
				log('Cleaning a card that has no position : '+this.get_name()) ;*/
		}
	}
// === [ ATTRIBUTES + SYNCHRONISATION ] ========================================
	this.sync = function() { // Send
		var result = false ; // By default, no action will be done
		// Send only difference between last synched attrs and current attrs
		var attrs = {} ;
		for ( i in this.attrs ) {
			if ( this.attrs[i] != this.sync_attrs[i] ) {
				if ( ( i == 'tapped' ) || ( i == 'attacking' ) || ( i == 'revealed' ) ) // Sync is in progress via selection
					continue ;
				// Workaround for the loop of siding synchronisation
				if ( ( i == 'siding' ) && ( this != game.player ) ) // I am the lone who can change my siding status
					continue ;
				attrs[i] = this.attrs[i] ;
				if ( ! result ) 
					result = true ; // At least one attribute will be sync
			}
		}
		// Reclone for next synch
		if ( result ) {
			this.sync_attrs = clone(this.attrs) ;
			action_send('attrs', {'card': this.id, 'attrs': attrs}) ; // this.attrs for full sync, attrs for diff sync
		}
		return attrs ;
	}
	this.setattrs = function(attrs) { // Get a new "attrs" array, and compare each element with current "attrs" array, then apply each difference
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

		if ( typeof attrs.switch_pt != 'undefined' ) {
			if ( attrs.switch_pt != this.attrs.switch_pt )
				this.switch_powthou_recieve() ;
		}
		if ( typeof attrs.switch_pt_eot != 'undefined' ) {
			if ( attrs.switch_pt_eot != this.attrs.switch_pt_eot )
				this.switch_powthou_eot_recieve() ;
		}
		if ( typeof attrs.counter != 'undefined' )
			if ( this.attrs.counter != attrs.counter )
				this.setcounter_disp(attrs.counter) ;
		if ( typeof attrs.note != 'undefined' )
			if ( this.attrs.note != attrs.note )
				this.setnote_recieve(attrs.note)
		if ( typeof attrs.visible != 'undefined' )
			if ( this.attrs.visible != attrs.visible )  { // Visibility : revealed cards in hand or face-down cards on BF
				switch ( this.zone.type ) {
					case 'battlefield' :
						switch ( attrs.visible ) {
							case null : // Default behaviour : display depending on zone
							case true : // Forced revealed
								this.face_up_recieve() ;
								break ;
							case false : // Face down on BF
								this.face_down_recieve() ;
								break ;
							default : 
								log('Unknown value '+attrs.visible+' for card visibility') ;
						}
						break ;
					case 'hand' :
						log('Trying to define hand visibility in card, despite it\'s managed by selection')
						break ;
					case 'library' :
					case 'graveyard' :
					case 'exile' :
						switch ( attrs.visible ) {
							case true : 
								this.reveal_from_unselzone_recieve() ;
								break ;
							case null :
							case false : 
								this.unreveal_from_unselzone_recieve() ;
								break ;
							default :
								log('Unknown value '+attrs.visible+' for card visibility') ;
						}
						break ;
					default :
						log('Unknown value '+this.zone.type+' for card zone type') ;
				}
			}
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
		if ( typeof attrs.attachedto != 'undefined' ) {
			var attachedto = get_card(attrs.attachedto) ;
			if ( this.get_attachedto() != attachedto )
				if ( attachedto != null )
					attachedto.attach_recieve(this) ;
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
		this.sync_attrs = clone(this.attrs) ;
	}
	this.has_attr = function(attr) {
		if ( this.attrs[attr] == true )
			return true ;
		if ( iso(this.animated_attrs) && isb(this.animated_attrs[attr]) && this.animated_attrs[attr] )
			return true ;
		var attached = this.get_attached() ;
		for ( var i = 0 ; i < attached.length ; i++ )
			if ( iso(attached[i].attrs.bonus) && attached[i].attrs.bonus[attr] )
				return true ;
		// this.apply_boost : 
		var cards = this.zone.cards ;
		for ( var i = 0 ; i < cards.length ; i++ ) {
			var card = cards[i] ;
			if ( iso(card.attrs.boost_bf) ) {
				for ( var j = 0 ; j < card.attrs.boost_bf.length ; j++ ) {
					var boost = card.attrs.boost_bf[j] ;
					if ( ( ! boost.self ) && ( this == card ) ) // Boost doesn't work on self
						continue ;
					if ( iss(boost.cond) && ( ! this.satisfy_condition(boost.cond) ) ) // Boost verify a condition
						continue ;
					if ( boost[attr] == true ) {
						return true ;
					}
				}
			}
		}

		return false ;
	}
	// Transform
	this.toggle_transform = function() {
		if ( this.attrs.transformed )
			this.untransform() ;
		else
			this.transform() ;
	}
	this.transform = function() {
		if ( this.transform_recieve() )
			this.sync() ;
	}
	this.transform_recieve = function() {
		if ( ! this.transformed_attrs ) // If a non-transformable card recieve transform, it will crash
			return false ;
		var cardname = this.get_name() ; // Backup as it will change during process
		this.attrs.transformed = true ; // Transform itself
		this.load_image() ; // Reload image
		// Save & delete powthou as it's used by is_creature
		var ipow = this.get_pow() ;
		var ithou = this.get_thou() ;
		delete this.attrs.pow ;
		delete this.attrs.thou ;
		delete this.attrs.pow_eot ;
		delete this.attrs.thou_eot ;
		if ( iss(this.attrs.trigger_upkeep) ) // Special case for Delver
			delete this.attrs.trigger_upkeep ;
		if ( this.is_creature() ) { // If card is a creat, apply transformed pow/tou
			var mpow = 0 ;
			var mthou = 0 ;
			if ( isn(this.orig_attrs.pow) )
				mpow = this.orig_attrs.pow ;
			if ( isn(this.orig_attrs.thou) )
				mthou = this.orig_attrs.thou ;
			mpow -= ipow ;
			mthou -= ithou ;
			this.set_powthou(this.transformed_attrs.pow - mpow, this.transformed_attrs.thou - mthou) ;
		}
		this.transform_attrs(this.transformed_attrs) ; // Replace attrs by transformed ones, in case they differ
		this.sync_attrs = clone(this.attrs) ; // Only sync 'transformed' status, other attrs change will be done client-side
		this.sync_attrs.transformed = false ;
		this.refreshpowthou() ;
		this.zone.refresh_pt(iso(this.attrs.boost_bf)||iso(this.orig_attrs.boost_bf)) ;
		message(active_player.name+' transforms '+cardname+' into '+this.get_name()) ;
		return true ;
	}
	this.transform_attrs = function(from) { // Copy some attrs from an attrs obj into card's one, removing unexisting attrs
		var creat_attrs = Array( 'double_strike', 'lifelink', 'vigilance', 'infect', 'trample', 'trigger_upkeep', 'boost_bf' ) ; // pow, thou
		for ( var i = 0 ; i < creat_attrs.length ; i++ ) {
			var attr = creat_attrs[i] ;
			if ( isset(from[attr]) ) // In from
				this.attrs[attr] = from[attr] ; // Copy
			else // Not in from
				if ( isset(this.attrs[attr]) ) // But existing in to
					delete this.attrs[attr] ; // Delete in to
		}
	}
	this.untransform = function() {
		if ( this.untransform_recieve() )
			this.sync() ;
	}
	this.untransform_recieve = function() {
		if ( ! this.transformed_attrs ) // If a non-transformable card recieve transform, it will crash
			return false ;
		var cardname = this.get_name() ; // Backup as it will change during process
		this.attrs.transformed = false ; // Untransform itself
		this.load_image() ; // Reload image
		if ( this.is_creature() ) { // If card is a creat, apply untransformed pow/tou
			var mpow = this.transformed_attrs.pow - this.get_pow() ;
			var mthou = this.transformed_attrs.thou - this.get_thou() ;
			this.set_powthou(this.orig_attrs.pow - mpow, this.orig_attrs.thou - mthou) ;
		}
		this.transform_attrs(this.orig_attrs) ; // Restore original attrs, in case they differ from transformed
		this.sync_attrs = clone(this.attrs) ; // Only sync 'transformed' status, other attrs change will be done client-side
		this.sync_attrs.transformed = true ;
		this.refreshpowthou() ;
		this.zone.refresh_pt(iso(this.attrs.boost_bf)||iso(this.transformed_attrs.boost_bf)) ;
		message(active_player.name+' reverts '+cardname+' as '+this.get_name()) ;
		return true ;
	}
	// Damages
	this.get_damages = function() {
		if ( ! isn(this.attrs.damages) )
			return 0 ;
		else
			return this.attrs.damages ;
	}
	this.set_damages = function(nb) {
		this.set_damages_recieve(nb) ;
		this.sync() ;
	}
	this.set_damages_recieve = function(nb) {
		if ( ! isn(nb) || ( nb < 0 ) )
			nb = 0 ;
		this.attrs.damages = nb ;
		this.refreshpowthou() ;
	}
	// Power / thoughness
		// Permanent
	this.get_pow = function() { // Getter
		if ( ! isn(this.attrs.pow) )
			return 0 ;
		else
			return this.attrs.pow ;
	}
	this.get_thou = function() {
		if ( ! isn(this.attrs.thou) )
			return 0 ;
		else
			return this.attrs.thou ;
	}
	this.get_powthou = function() {
		return this.get_pow()+'/'+this.get_thou() ;
	}
	this.set_pow = function(nb) { // Setter which does not display anything
		if ( isn(nb) ) {
			var pow = nb - this.get_pow() ;
			this.attrs.pow = nb ;
		} else 
			var pow = 0 ;
		return disp_int(pow) ;
	}
	this.set_thou = function(nb) {
		if ( isn(nb) ) {
			var thou = nb - this.get_thou() ;
			this.attrs.thou = nb ;
		} else
			var thou = 0 ;
		return disp_int(thou) ;
	}
	this.set_powthou = function(pow, thou) {
		return this.set_pow(pow)+'/'+this.set_thou(thou) ;
	}
	this.add_pow = function(nb) { // Setter that adds instead of replace, and does not display anything
		if ( isn(nb) ) {
			if ( ! isn(this.attrs.pow) )
				this.attrs.pow = 0 ;
			this.attrs.pow += nb ;
		}
	}
	this.add_thou = function(nb) {
		if ( isn(nb) ) {
			if ( ! isn(this.attrs.thou) )
				this.attrs.thou = 0 ;
			this.attrs.thou += nb ;
		}
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
			delete this.attrs.pow ;
			delete this.attrs.thou ;
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
		if ( ! isn(this.attrs.pow_eot) )
			return 0 ;
		else
			return this.attrs.pow_eot ;
	}
	this.get_thou_eot = function() {
		if ( ! isn(this.attrs.thou_eot) )
			return 0 ;
		else
			return this.attrs.thou_eot ;
	}
	this.get_powthou_eot = function() {
		return disp_int(this.get_pow_eot())+'/'+disp_int(this.get_thou_eot()) ;
	}
	this.set_pow_eot = function(nb) { // Setter which does not display anything
		if ( isn(nb) ) {
			var pow = nb - this.get_pow_eot() ;
			this.attrs.pow_eot = nb ;
		} else 
			var pow = 0 ;
		if ( this.attrs.pow_eot == 0 )
			delete this.attrs.pow_eot ;
		return disp_int(pow) ;
	}
	this.set_thou_eot = function(nb) {
		if ( isn(nb) ) {
			var thou = nb - this.get_thou_eot() ;
			this.attrs.thou_eot = nb ;
		} else
			var thou = 0 ;
		if ( this.attrs.thou_eot == 0 )
			delete this.attrs.thou_eot ;
		return disp_int(thou) ;
	}
	this.set_powthou_eot = function(pow, thou) {
		return this.set_pow_eot(pow)+'/'+this.set_thou_eot(thou) ;
	}
	this.add_pow_eot = function(nb) { // Setter that adds instead of replace, and does not display anything
		if ( isn(nb) ) {
			if ( ! isn(this.attrs.pow_eot) )
				this.attrs.pow_eot = 0 ;
			this.attrs.pow_eot += nb ;
			if ( this.attrs.pow_eot == 0 )
				delete this.attrs.pow_eot ;
		}
	}
	this.add_thou_eot = function(nb) {
		if ( isn(nb) ) {
			if ( ! isn(this.attrs.thou_eot) )
				this.attrs.thou_eot = 0 ;
			this.attrs.thou_eot += nb ;
			if ( this.attrs.thou_eot == 0 )
				delete this.attrs.thou_eot ;
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
			delete this.attrs.pow_eot ;
			delete this.attrs.thou_eot ;
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
		if ( iso(this.attrs.powtoucond) ) {
			if ( iss(pt) && isn(this.attrs.powtoucond[pt]) )
				var boost = this.attrs.powtoucond[pt] ;
			else
				var boost = 1 ;
			var from_str = this.attrs.powtoucond.from ;
			var player = this.controler ;
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
			switch ( this.attrs.powtoucond.what ) {
				case 'cards' : // Standard case : counting cards (master of etherium, kotr)
				case 'card' : // Standard case : presence of any number (kird ape)
					for ( var i in from ) {
						if ( isb(this.attrs.powtoucond.other) && this.attrs.powtoucond.other && ( from[i] == this ) )
							continue ;
						if ( from[i].satisfy_condition(this.attrs.powtoucond.cond) ) {
							result += boost ;
							if ( this.attrs.powtoucond.what == 'card' ) // Searching for 1
								break ;
						}
					}
					break ;
				case 'types' : // Tarmogoyf
					var types = [] ;
					for ( var i in from )
						for ( var j in from[i].attrs.types )
							if ( ! inarray(from[i].attrs.types[j], types) )
								types.push(from[i].attrs.types[j]) ;
					result += types.length ;
					break ;
				default :
					log('Unknown what : '+this.attrs.powtoucond.what) ;
			}

		}
		return result ;
	}
		// Total	
	this.get_pow_tot = function() {
		var result = 0 ;
		// Native
		if ( isn(this.attrs.pow) )
			result += this.attrs.pow ;
		// Conditionnal
		result += this.get_pow_cond('pow') ;
		// EOT
		if ( isn(this.attrs.pow_eot) )
			result += this.attrs.pow_eot ;
		// Attach
		var a = this.get_attached() ;
		for ( var i = 0 ; i < a.length ; i++ ) {
			if ( iso(a[i].attrs.bonus) && isn(a[i].attrs.bonus.pow) )
				result += a[i].attrs.bonus.pow ;
			if ( iso(a[i].attrs.powtoucond) )
				result += a[i].get_pow_cond('pow') ;
		}
		// Boost
		result += this.apply_boost('pow') ;
		return result
	}
	this.get_pow_total = function() {
		if ( this.attrs.switch_pt | this.attrs.switch_pt_eot )
			return this.get_thou_tot() ;
		else
			return this.get_pow_tot() ;
	}
	this.get_thou_tot = function() {
		var result = 0 ;
		// Native
		if ( isn(this.attrs.thou) )
			result += this.attrs.thou ;
		// Conditionnal
		result += this.get_pow_cond('thou') ;
		// EOT
		if ( isn(this.attrs.thou_eot) )
			result += this.attrs.thou_eot ;
		// Attach
		var a = this.get_attached() ;
		for ( var i = 0 ; i < a.length ; i++ ) {
			if ( iso(a[i].attrs.bonus) && isn(a[i].attrs.bonus.tou) )
				result += a[i].attrs.bonus.tou ;
			if ( iso(a[i].attrs.powtoucond) )
				result += a[i].get_pow_cond('thou') ;
		}
		// Boost
		result += this.apply_boost('tou') ;
		return result
	}
	this.get_thou_total = function() {
		if ( this.attrs.switch_pt | this.attrs.switch_pt_eot )
			return this.get_pow_tot() ;
		else
			return this.get_thou_tot() ;
	}
	this.get_powthou_total = function() {
		return this.get_pow_total()+'/'+this.get_thou_total() ;
	}
	// All creat booster
	this.apply_boost = function(type) {
		var result = 0 ;
		var cards = this.zone.cards ;
		for ( var i = 0 ; i < cards.length ; i++ ) {
			var card = cards[i] ;
			if ( iso(card.attrs.boost_bf) ) {
				for ( var j = 0 ; j < card.attrs.boost_bf.length ; j++ ) {
					var boost = card.attrs.boost_bf[j] ;
					if ( ( ! boost.self ) && ( this == card ) ) // Boost doesn't work on self
						continue ;
					if ( iss(boost.cond) && ( ! this.satisfy_condition(boost.cond) ) ) // Boost verify a condition
						continue ;
					result += boost[type] ;
				}
			}
		}
		return result ;
	}
	this.refreshpowthou = function() { // Cache power and toughness in order not to recompute it on each frame draw
		var redraw = ( ( this.zone.type == 'battlefield' ) /*&& this.is_creature()*/ ) // Only redraw if it's a creature on the battlefield
		redraw = redraw && ( ( isn(this.attrs.pow) && isn(this.attrs.thou) ) || ( isn(this.attrs.pow_eot) && isn(this.attrs.thou_eot) ) ) ; // Don't redraw if pow/thou has been removed
		if ( redraw ) { // Would redraw
			this.pow_thou = this.get_powthou_total() ;
			this.pow_thou_color = 'white' ;
			if ( this.get_damages() >= this.get_thou_total() ) // Show damages would kill creat
				this.pow_thou_color = 'red' ;
			else
				if ( isn(this.attrs.pow_eot) || isn(this.attrs.thou_eot) ) // Show creat has modified P/T until EOT
					this.pow_thou_color = 'lightblue' ;
		} else // Wouldn't redraw
			this.pow_thou = '' ; // Erase
		this.refresh('powtou') ;
		return redraw ;
	}
	// Switch
	this.switch_powthou = function() {
		this.switch_powthou_recieve()
		this.sync() ;
	}
	this.switch_powthou_recieve = function() {
		this.attrs.switch_pt = ! this.attrs.switch_pt ;
		if ( this.attrs.switch_pt )
			message(this.get_name()+' has switched power and toughness', 'pow_thou') ;
		else
			message(this.get_name()+' hasn\'t switched power and toughness anymore', 'pow_thou') ;
		this.refreshpowthou() ;
	}
	this.switch_powthou_eot = function() {
		this.switch_powthou_eot_recieve()
		this.sync() ;
	}
	this.switch_powthou_eot_recieve = function() {
		this.attrs.switch_pt_eot = ! this.attrs.switch_pt_eot ;
		if ( this.attrs.switch_pt_eot )
			message(this.get_name()+' has switched power and toughness until end of turn', 'pow_thou') ;
		else
			message(this.get_name()+' hasn\'t switched power and toughness until end of turn anymore', 'pow_thou') ;
		this.refreshpowthou() ;
	}
	// Counters
	this.getcounter = function() {
		var result = 0 ;
		if ( isn(this.attrs.counter) )
			result += this.attrs.counter ;
		if ( result < 0 )
			result = 0 ;
		return result ;
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
			this.attrs.counter = nb ;
			if ( this.is_planeswalker() && ( nb < this.getcounter() ) ) // taking damages
				this.set_damages(max(0, this.get_damages() - old_nb + nb)) ;
			this.refresh('counters') ;
		}
	}
	// Note
	this.getnote = function() {
		var result = '' ;
		if ( iss(this.attrs.note) )
			result = this.attrs.note ;
		return result ;
	}
	this.setnote = function(note) {
		if ( ! iss(note) ) {
			if ( this.getnote() == '' ) // Empty note
				note = game.target.targetedby(this).map(function(val, idx, arr) { return val.get_name() ;}).join(', ') ;
			else
				note = this.getnote() ;
			note = prompt('Note for '+this.get_name()+' ?', note) ;
		}
		if ( note != null ) {
			this.setnote_recieve(note) ;
			this.sync() ;
		}
	}
	this.setnote_recieve = function(note) {
		if ( note != this.attrs.note ) {
			this.attrs.note = note ;
			delete this.note_img ;
			if ( inarray(note.toUpperCase(), game.manacolors) )
				game.image_cache.load(theme_image('/ManaIcons/'+note.toUpperCase()+'.png'), function(img, card) {
					card.note_img = img ;
					card.refresh() ;
				}, function(card, zone) {
					log('Image not found for '+card.name+', creating text') ;
				}, this, param) ;
			if ( note == '' )
				message(this.get_name()+' isn\'t annoted anymore', 'note') ;
			else
				message(this.get_name()+'\'s annotation is now : '+note, 'note') ;
			this.refresh('note') ;
		}
	}
	// No untap as normal
	this.untap_toggle = function() {
		if ( this.attrs.no_untap )
			this.untap_as_normal_recieve() ;
		else
			this.does_not_untap_recieve() ;
		this.sync() ;
	}
	this.untap_once_toggle = function() {
		if ( this.attrs.no_untap_once )
			this.untap_as_normal_once_recieve() ;
		else
			this.does_not_untap_once_recieve() ;
		this.sync() ;
	}
	this.untap_as_normal_recieve = function() {
		this.attrs.no_untap = false ;
		message(this.get_name()+' will untap as normal', 'note') ;
		this.refresh('untap_as_normal') ;
	}
	this.untap_as_normal_once_recieve = function() {
		this.attrs.no_untap_once = false ;
		message(this.get_name()+' will untap as normal', 'note') ;
		this.refresh('untap_as_normal_once') ;
	}
	this.does_not_untap_recieve = function() {
		this.attrs.no_untap = true ;
		message(this.get_name()+' wont untap as normal', 'note') ;
		this.refresh('does_not_untap') ;
	}
	this.does_not_untap_once_recieve = function() {
		this.attrs.no_untap_once = true ;
		message(this.get_name()+' wont untap as normal next untap phase', 'note') ;
		this.refresh('does_not_untap_once') ;
	}
		// Reveal
	this.toggle_reveal_from_hand = function() {
		if ( this.attrs.visible )
			this.unreveal_from_hand() ;
		else
			this.reveal_from_hand() ;
	}
	this.reveal_from_hand = function() {
		if ( this.reveal_from_hand_recieve() )
			this.sync() ;
	}
	this.reveal_from_hand_recieve = function() {
		if ( this.attrs.visible != true )  { // Not forced as true
			this.set_visible(true) ;
			this.load_image() ; // Display image if in opponent's hand
			message(active_player.name+' reveals '+this.get_name()+' from hand', 'note') ;
			return true ; // Returns if something changed
		}
		return false ;
	}
	this.unreveal_from_hand = function() {
		if ( this.unreveal_from_hand_recieve() )
			this.sync() ;
	}
	this.unreveal_from_hand_recieve = function() {
		if ( this.attrs.visible == true )  { // Forced as true
			this.set_visible(null) ;
			this.load_image() ; // Display background if in opponent's hand
			message(active_player.name+' hides '+this.get_name()+' from hand', 'note') ;
			return true ; // Returns if something changed
		}
		return false ;
	}
		// show / hide for zones purpose (reveal top card of library typicaly)
	this.toggle_reveal_from_unselzone = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.attrs.visible )
			mycard.unreveal_from_unselzone() ;
		else
			mycard.reveal_from_unselzone() ;
	}
	this.reveal_from_unselzone = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.reveal_from_unselzone_recieve(mycard) )
			mycard.sync() ;
	}
	this.reveal_from_unselzone_recieve = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.attrs.visible != true ) {
			mycard.set_visible(true) ;
			message(active_player.name+' reveals '+mycard.get_name()+' from '+mycard.zone.get_name()) ;
			mycard.load_image() ;
			return true ;
		}
		return false ;
	}
	this.unreveal_from_unselzone = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.unreveal_from_unselzone_recieve(mycard) )
			mycard.sync() ;
	}
	this.unreveal_from_unselzone_recieve = function(mycard) {
		if ( ! mycard )
			mycard = this ;
		if ( mycard.attrs.visible != null ) {
			message(active_player.name+' hides '+mycard.get_name()+' from '+mycard.zone.get_name()) ;
			mycard.set_visible(null) ;
			mycard.load_image() ;
			return true ;
		}
		return false ;
	}
		// Face up/down
	this.face_up = function() {
		if ( this.face_up_recieve() )
			this.sync() ;
	}
	this.face_up_recieve = function() {
		if ( this.zone.type != 'battlefield' ) {
			log('Impossible to toggle face a card on '+this.zone.type) ;
			return false ;
		}
		if ( this.attrs.visible ) {
			log('Can\'t face up a visible card') ;
			return false ;
		}
		this.attrs = clone(this.orig_attrs) ; // Resync with creation attrs
		this.set_visible(null) ; // Return to default behaviour : display depending zone
		this.load_image() ;
		this.refreshpowthou() ;
		message(active_player.name+' turns '+this.get_name()+' face up', 'note') ;
		return true ;
	}
	this.face_down = function() {
		if ( this.face_down_recieve() )
			this.sync() ;
	}
	this.face_down_recieve = function() {
		if ( this.zone.type != 'battlefield' ) {
			log('Impossible to toggle face a card on '+this.zone.type) ;
			return false ;
		}
		if ( this.attrs.visible == false ) { // this.attrs.visible can be "null" which behaves as "false", but on BF means "visible"
			log('Can\'t face down a hidden card') ;
			return false ;
		}
		message(active_player.name+' turns '+this.get_name()+' face down', 'note') ; // Message before changing because next line will change get_name's behaviour
		this.on_face_down() ;
		this.load_image() ;
		this.refreshpowthou() ;
		return true ;
	}
	this.on_face_down = function() {
		var prev_attrs = clone(this.attrs) ;
		this.attrs = {} ;
		this.init_attrs() ;
		this.attrs.visible = prev_attrs.visible ; // Its attributes have to be managed individually from faced-up card, except the visible value that will be saved before beeing set
		this.set_visible(false) ;
		if ( iss(prev_attrs.morph) )
			this.attrs.morph = prev_attrs.morph ; // Restore morph
	}
	// Attach
	this.get_attachedto = function() { // Returns the card which this is attached, or null
		if ( iso(this.attrs.attachedto) )
			return this.attrs.attachedto ;
		return null ;
	}
	this.get_attached = function() { // Returns an array of cards attached to this
		if ( this.attrs.attached )
			var result = this.attrs.attached ;
		else
			var result = new Array() ;
		return result ;
	}
	this.attach = function(dragcard) { // Attach dragcard to this
		this.attach_recieve(dragcard) ;
		dragcard.sync() ;
	}
	this.attach_recieve = function(attached) {
		var attach_to = this ; // By default, attach to dropped card
		while ( attach_to.attrs.attachedto != null ) // But if it is attached itself, attach to bottom attached card
			attach_to = attach_to.attrs.attachedto ;
		if ( attach_to != attached ) {
			var x = attached.grid_x ; // Back-up to place cards attached to attaching card
			var y = attached.grid_y ;
			if ( attach_to.zone != attached.zone ) // If attached from hand, library, grave ...
				attached.changezone_recieve(attach_to.zone) ; // First move it to desired zone
			attached.detach() ; // If attached, detached from where it is attached
			// Mark 'attached' as attached to 'attach_to'
			attached.attrs.attachedto = attach_to ;
			attach_to.get_attached().push(attached) ;
			attached.clean_battlefield() ;
			attached.set_grid(attach_to.grid_x, attach_to.grid_y) ;
			attach_to.place_recieve(attach_to.grid_x, attach_to.grid_y) ; // Re place it where it already is, in order to place correctly all attached
			message(attached.get_name()+' attached to '+attach_to.get_name(), 'note') ;
			while ( attached.attrs.attached.length > 0 ) { // If attached has itself attached cards, detach those and replace them
				var changeattach = attached.attrs.attached[0] ;
				changeattach.detach() ;
				changeattach.place(x, y) ;
			}
			attach_to.refreshpowthou() ;
		}
	}
	this.detach = function() { // If this is attached to another, detach from it
		var from = this.get_attachedto() ;
		if ( from != null ) {
			if ( ! iso(from.attrs.attached) )
				return false ;
			var attached = from.attrs.attached ;
			attached.splice(attached.indexOf(this), 1) ;
			this.attrs.attachedto = null ;
			if ( ! this.zone.player.attrs.siding ) // No detach message during side
				message(this.get_name()+' detached from '+from.get_name(), 'note') ;
			from.place_recieve(from.grid_x, from.grid_y) ; // Refresh their position
			from.refreshpowthou() ;
		}
	}
	// Duplicate
	this.duplicate_attrs = function(name, to) {
		to[name] = this[name] ;
	}
	this.duplicate = function() {
		var card = this ;
		action_send('duplicate', {'card': this.toString()}, function(data) {
			card.duplicate_recieve(data.id) ;
		}) ;
	}
	this.duplicate_recieve = function(id) {
		var attrs = clone(this.attrs) ;
		this.duplicate_attrs('cost', attrs) ;
		//this.duplicate_attrs('color', attrs) ;
		this.duplicate_attrs('converted_cost', attrs) ;
		//this.duplicate_attrs('types', attrs) ;
		//this.duplicate_attrs('subtypes', attrs) ;
		this.duplicate_attrs('tokens', attrs) ;
		this.duplicate_attrs('transformed_attrs', attrs) ;
		var duplicate = new Token(id, this.ext, this.name, this.zone, attrs, this.imgurl_relative()) ;
		duplicate.get_name = function() {
			return 'copy of '+this.name ; // Default is token, overriding
		}
		duplicate.place(0, duplicate.place_row()) ;
		message(active_player.name+' duplicates '+this.get_name(), 'zone') ;
	}
	// Copy (cloning effect)
	this.copy = function() {
		var tby = game.target.targetedby(this) ;
		if ( this.copy_recieve(tby[0]) ) 
			this.sync() ;
	}
	this.copy_recieve = function(card) {
		if ( card == this ) {
			log('Card trying to copy self') ;
			return false ;
		}
		message(this.get_name()+' becomes a copy of '+card.get_name()) ; // get it without "copy of ..." suffix
		this.attrs = clone(card.attrs) ;
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
			this.attrs = clone(this.orig_attrs) ;
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
	this.morph = function() {
		if ( this.zone.type != 'battlefield' ) {
			this.attrs.types = ['creature'] ;
			this.changezone(this.owner.battlefield, false) ;
		} else
			this.face_down() ;
		this.disp_powthou(2, 2) ;
		this.refreshpowthou() ;
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
		do {
			var card = this.controler.library.cards[this.controler.library.cards.length-1-i++] ;
			if ( ( ! card.is_land() ) && ( card.converted_cost < this.converted_cost ) ) {
				tobf.add(card) ;
				break ;
			} else
				tobottom.add(card) ;
		} while (this.controler.library.cards.length > i) ;
		if ( tobf.cards.length != 1 )
			message('Nothing to cascade under '+this.converted_cost) ;
		else
			tobf.changezone(this.controler.battlefield) ;
		if ( tobottom.cards.length > 0 ) {
			shuffle(tobottom.cards) ;
			tobottom.changezone(this.controler.exile) ;
			tobottom.changezone(this.controler.library, null, 0) ;
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
