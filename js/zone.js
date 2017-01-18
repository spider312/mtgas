// zone.js
// Classes and function for zone initialisation and events
// Visible
// === [ CLASSES ] =============================================================
// Each zone, real or virtual (bf, hand, lib, grave, exile, sideboard, NOT : life, manapool, turn)
function Zone(player, type) {
	// Methods
	this.toString = function() {
		return this.player+'.'+this.type ; // string representing basic variable's name (game.player.zonename)
	}
	this.get_name = function() {
		return this.player.name + '\'s ' + this.type ; ;
	}
	this.moveinzone = function(nb, to, from) {
		if ( ! isn(nb) )
			nb = prompt_int('How many cards to move ?', 1) ; // By default, prompt user how many cards
		if ( ! isn(from) )
			from = this.cards.length ; // By default, from top
		if ( ! isn(to) )
			to = 0 ; // By default, to bottom
		var cards = this.cards.slice(from-nb, from) ;
		cards = cards.reverse() ; // From top to bottom
		var sel = new Selection(cards) ;
		sel.moveinzone(to) ;
	}
	this.changezone = function(dest_zone, nb, to, from, visible) {
		if ( ! isn(nb) )
			nb = prompt_int('How many cards to move from '+this.type+' to '+dest_zone.type, this.cards.length) ;
		if ( ! isn(from) )
			from = this.cards.length ;
		if ( from > this.cards.length )
			from = this.cards.length ;
		if ( from < 0 )
			from = 0 ;
		if ( ! isb(visible) )
			visible = null ;
		(new Selection(this.cards.slice(from-nb, from).reverse())).changezone(dest_zone, visible, to) ;
	}
	this.rand_selection = function(cards, nb) { // Returns a selection containing nb cards crom cards
		if ( ! isn(nb) || ( nb < 1 ) )
			return false ;
		// Work on a copy of array as we will splice cards in order not to discard 2 times the same card
		var cards = cards.concat() ;
		var sel = new Selection() ;
		sel.settype('rand') ;
		for ( var i = 0 ; i < nb ; i++ ) // The number of times user choosed
			// Remove a random card from clone and add it to selection
			sel.add(cards.splice(rand(cards.length), 1)[0]) ;
		return sel ;
	}
	this.rand_changezone = function(dest_zone) { // Same as changezone, with "from" randomly defined
		var nb = prompt_int('How many cards to move from '+this.type+' to '+dest_zone.type+' ?', 1) ;
		var sel = this.rand_selection(this.cards, nb) ;
		if ( sel )
			sel.changezone(dest_zone) ;
	}
	this.rand_reveal = function() {
		var nb = prompt_int('How many cards to reveal from your '+this.type+' ?', 1) ;
		var sel = this.rand_selection(this.cards, nb) ;
		if ( sel )
			sel.reveal_from_hand(true, true) ;
	}
	this.get_card = function(id) { // duplicated here for cardlisteditor that can't use globals (based on another window object)
		for ( var j = 0 ; j < this.cards.length ; j++ )
			if ( this.cards[j].id == id )
				return this.cards[j] ;
		return null ;
	}
	this.shuffle = function() {
		if ( this.cards.length < 1 )
			return null ;
		var shuffled = new Array() ;
		while ( this.cards.length > 0 ) {
			var pos = rand(this.cards.length) ;
			var card_in_arr = this.cards.splice(pos,1) ;
			var card = card_in_arr[0] ;
			shuffled.push(card) ;
		}
		this.cards = shuffled ;
		this.sync(true) ;
		message(active_player.name+' shuffled '+this.get_name(), 'zone') ;
		game.sound.play('shuffle') ;
		if ( isf(this.refresh) )
			this.refresh() ;
	}
	this.sync = function(shuffle) {
		for ( var i = 0 ; i < this.cards.length ; i++ )
			this.cards[i].visible = null ;
		var attrs = {'zone': this.toString(), 'cards': this.cards.join(',')}
		if ( shuffle )
			attrs.shuffle = true ;
		action_send('zsync', attrs) ;
	}
	this.sync_recieve = function(sel, shuffle) {
		if ( this.cards.length != sel.cards.length )
			log('Recieved a zone reordering that will change card nb for '+this.get_name()+
				' ('+this.cards.length+' -> '+sel.cards.length+')') ;
		this.cards = sel.cards ;
		for ( var i = 0 ; i < this.cards.length ; i++ )
			this.cards[i].visible = null ;
		if ( shuffle )
			message(active_player.name+' shuffled '+this.get_name(), 'zone') ;
		else
			message(active_player.name+' reordered '+this.get_name(), 'zone') ;
		return true ;
	}
	// Attributes
	this.type = type ;
	this.player = player ;
	this.cards = new Array() ;
	this.editor_window = null ;
}
// Zone with a visual representation (widget)
function VisibleZone(player, type) {
	// Heritage
	var zone = new Zone(player, type) ;
	Widget(zone) ;
	// Referencing
	game.widgets.push(zone) ;
	// Events
	zone.mousemove = function(ev) { // Mousemove to trigger mouseup & mouseout on cards
		var card = this.card_under_mouse(ev) ;
		if ( card != game.card_under_mouse ) { // Hovering a new item
			// Mouseout previous item
			if ( ( game.card_under_mouse != null ) &&  isf(game.card_under_mouse.mouseout) )
				game.card_under_mouse.mouseout(ev) ;
			// Update item cache
			game.card_under_mouse = card ;
			// Mouseover new item
			if ( ( game.card_under_mouse != null ) && isf(game.card_under_mouse.mouseover) )
				game.card_under_mouse.mouseover(ev) ;
			if ( ( game.card_under_mouse == null ) && isf(this.mouseover) )
				this.mouseover(ev) ;
			// Display hovered card on top of other
			if ( ! this.selzone )
				this.refresh() ;
		}
		return card ;
	}
	// Accessors
	zone.card_under_mouse = function(ev) {
		// Loop unattached cards
		for ( var i = this.cards.length ; i > 0 ; i-- ) {
			var card = this.cards[i-1] ;
			if ( card.get_attachedto() == null ) {
				if ( dot_in_rect(new dot(ev.clientX, ev.clientY), card.rect()) )
					return card ;
				// Loop its attached
				var attached = card.get_attached()
				if ( attached != null )
					for ( var j = 0 ; j < attached.length ; j++ ) {
						var a = attached[j] ;
						if (dot_in_rect(new dot(ev.clientX, ev.clientY), a.rect()) )
							return a ;
					}
			}
				
		}
		// Didn't loop attached cards
		return null ;
	}
	zone.disp_card_number = function(context, force) {
		var margin = 5 ;
		context.font = "10pt Arial";
		var x = this.w ;
		var y = 0 ;
		if ( this.selzone || force ) {
			x += this.x ;
			y += this.y ;
		}
		canvas_text_tr(context, this.cards.length, x - margin, y + margin, bordercolor) ;
	}
	return zone ;
}
// === [ UNSELZONES ] ==========================================================
// Library, graveyard, exile
function unselzone(result) { // Common
	virtual_unselzones(result) ;
	// Events
	result.parent_mousedown = result.mousedown ;
	result.mousedown = function(ev) {
		result.parent_mousedown(ev) ;
		switch ( ev.button ) {
			case 0 : // Left click
				if ( this.cards.length > 0 ) { // Start DND
					var card = this.card_under_mouse(ev) ; // On card under mouse
					if ( card == null ) // If not (drag from zone background)
						card = this.cards[this.cards.length-1] ; // On card on top of zone
					game.selected.set(card) ;
					drag_init(card, ev) ;
					drag_start() ;
				}
				break ;
			case 1 : // Middle button click
				break ;
			case 2 : // Right click
				break ;
		}
	}
	result.parent_mouseup = result.mouseup ;
	result.mouseup = function(ev) {
		result.parent_mouseup(ev) ; // Target
		// Drop
		if ( ( game.drag != null ) && ( game.drag.zone != this ) )
			game.selected.changezone(this) ;
	}
	result.click = function(ev) {
		switch ( ev.button ) {
			case 0 : // Left click
				break ;
			case 1 : // Middle button click
				if ( this.cards.length > 0 ) {
					var card = this.card_under_mouse(ev) ; // On card under mouse
					if ( card == null ) // If not (drag from zone background)
						card = this.cards[this.cards.length-1] ; // On card on top of zone
					card.info() ;
				}
				break ;
			case 2 : // Right click
				this.contextmenu(ev) ;
				break ;
		}
	}
	result.dblclick = function(ev) {
		card_list_edit(this) ;
	}
	result.selzone = false ;
	// Drawing
	result.draw = function(context) {
		context.drawImage(this.cache, this.x, this.y) ;
	}
	result.refresh = function() {
		if ( this.editor_window != null )
			refresh_list(this) ; // Will set some cards as "watching", must be done before canvas refreshing
		// Compute coordinates for all zone's cards
		if ( this.cards.length > 0 ) {
			// Reinit all cards' position : Centered in zone
			for ( var i = 0 ; i < this.cards.length ; i++ )
				this.cards[i].coords_set(
					this.x + ( this.w - cardwidth ) / 2,
					this.y + ( this.h - cardheight ) / 2
				) ;
			// Count how many top cards are visible
			var topidx = this.cards.length - 1 ;
			this.vcards = 0 ;
			while ( ( topidx - this.vcards >= 0 ) && this.cards[topidx-this.vcards].is_visible() )
				this.vcards++ ;
			if ( this.type == 'library' ) {
				if ( this.vcards > 1 )
					this.vcards = 1 ;
			} else {
				// More than 1 visible card : find a position to show them all
				if ( this.vcards > 1 ) {
					// Display of 1 card + m px of each other card
					var xm = ( this.w - cardwidth ) / ( this.vcards + 1 ) ; // (simulate 2 more cards : margin of "m" px on left & right)
					var ym = ( this.h - cardheight ) / ( this.vcards + 1 ) ;
					var cardswidth = cardwidth + xm * ( this.vcards ) ;
					var cardsheight = cardheight + ym * ( this.vcards ) ;
					var xo = 1
					var yo = 1 ;
					for ( var i = this.vcards-1 ; i >= 0 ; i-- ) {
						var card = this.cards[topidx-i] ;
						card.coords_set(this.x + xo + this.w - cardswidth, this.y + yo + this.h - cardsheight) ;
						xo += xm ;
						yo += ym ;
					}
				}
			}
		}
		// Positions computed, let's draw that on cache
		var context = this.context ;
		context.clearRect(0, 0, this.w, this.h) ;
		// Border / background
		canvas_set_alpha(zopacity, context) ;
		if ( drawborder ) {
			context.strokeStyle = bordercolor ;
			context.strokeRect(.5, .5, this.w, this.h) ;
		} else
			if (
					( this.type == 'graveyard' ) // Graveyard for both players
					|| ( ( this.player == game.player ) && ( this.type == 'library' ) )
					|| ( ( this.player != game.player ) && ( this.type == 'exile' ) )
			) {
				context.strokeStyle = bordercolor ;
				context.beginPath();
				context.moveTo(.5 + smallzonemargin, .5) ;
				context.lineTo(.5+this.w - smallzonemargin, .5) ;
				context.stroke() ;
			}
		if ( ( game.target.tmp != null ) && ( game.target.tmp.targeted == this ) ) {
			context.fillStyle = 'yellow' ;
			context.fillRect(.5, .5, this.w, this.h) ;
		}
		canvas_reset_alpha(context) ;
		// Data : card number
		var opt = game.options.get('zone_card_number') ;
		if ( ( opt == 'all' ) || ( opt == 'selzone' ) )
			this.disp_card_number(context) ;
		// Cards
		if ( this.cards.length == 0 ) {
			// Icon
			if ( this.img != null )
				context.drawImage(this.img, (this.w-this.img.width)/2 , (this.h-this.img.height)/2) ;
		} else {
			if ( this.vcards <= 1 ) { // No visible card, draw back, 1 visible card, draw it
				var card = this.cards[this.cards.length-1] ;
				card.draw(context, card.x - this.x, card.y - this.y) ;
			} else { // More than 1 visible card
				for ( var i = this.vcards-1 ; i >= 0 ; i-- ) // Draw them
					if ( i < this.cards.length ) { // ???
						var idx = this.cards.length-i-1 ; // this.cards.length - 1 = top index, i = 0-n top visible cards
						var card = this.cards[idx] ;
						card.draw(context, card.x - this.x, card.y - this.y) ;
					}
				var card = game.card_under_mouse ;
				if ( ( game.widget_under_mouse == this ) && ( card != null ) && ( card.zone == this ) )
					card.draw(context, card.x - this.x, card.y - this.y) ;
			}
		}
	}
	// Init
	result.selzone = false ;
	// Image
	result.img = null ;
	game.image_cache.load(theme_image('ZoneIcons/'+result.type+'.png'), function(img, widget) {
		widget.img = img ;
		widget.refresh() ;
	}, function(widget) {
		log('Unable to load image for '+widget) ;
	}, result) ;
	// Cards
	result.vcards = 0 ; // Number of visible cards from top
	return result ;
}
function virtual_unselzones(result) { // Unselzones + life (targeting)
	result.mousedown = function(ev) {
		switch ( ev.button ) {
			case 0 : // Left click
				break ;
			case 1 : // Middle button click
				break ;
			case 2 : // Right click
				// Prepare targeting
				if ( game.current_targeting == null )
					game.current_targeting = this ;
				break ;
		}
	}
	result.mouseover = function(ev) {
		if ( game.target.tmp != null ) { // Targeting in progress
			if ( game.current_targeting == this ) 
				game.target.tmp.stop(null) ;
			else
				game.target.tmp.over(this) ;
		}
	}
	result.mouseout = function(ev) {
		if ( ( game.card_under_mouse != null ) && ( isf(this.mousemove) ) )
			this.mousemove(ev) ; // Left a zone, if a card was under mouse, it probably isn't anymore
		if ( game.target.tmp != null )
			game.target.tmp.out(this) ;
		else if ( game.current_targeting == this ) 
			game.target.start(this, ev) ;
	}
	result.mouseup = function(ev) {
		if ( game.target.tmp != null )
			game.target.tmp.stop(this) ;
	}
	return result ;
}
function library(player) {
	var mylib = new VisibleZone(player, 'library') ;
	unselzone(mylib) ;
	// Events
	mylib.dblclick = function(ev) {
		if ( this.player.access() )
			switch ( game.options.get('library_doubleclick_action') ) {
				case 'draw': 
					this.player.hand.draw_card() ;
					break ;
				case 'edit' : 
					card_list_edit(this) ;
					break ;
				case 'look_top_n' : 
				default :
					card_list_edit_n(this) ;
			}
	}
	mylib.contextmenu = function(ev) {
		var mylib = this ;
		var menu = new menu_init(mylib) ;
		if ( this.player.access() ) {
			menu.addline('Library',				card_list_edit,		mylib) ;
			if ( mylib.cards.length > 0 ) {
				menu.addline() ;
				menu.addline('Look top cards ...', 		card_list_edit_n, 	mylib) ;
				menu.addline('Look bottom cards ...', 		card_list_edit_n_bottom, 	mylib) ;
				menu.addline('Shuffle', 			mylib.shuffle) ;
				var line = menu.addline('Play with top card revealed',	mylib.toggle_reveal) ;
				line.checked = mylib.player.attrs.library_revealed ;
				if ( ! mylib.player.attrs.library_revealed ) {
					var topcard = mylib.cards[mylib.cards.length-1] ;
					var checked = topcard.attrs.visible ; // Default to card visibility
					if ( checked == null ) // Replace 'null' value by false to display unchecked checkbox
						checked = false ;
					menu.addline('Reveal top card',		topcard.toggle_reveal_from_unselzone,	topcard).checked = checked ;
				}
				menu.addline() ;
				menu.addline('To hand ...',		mylib.changezone,	mylib.player.hand) ;
				menu.addline('To battlefield ...',	mylib.changezone,	mylib.player.battlefield) ;
				menu.addline('To battlefield face down ...',	mylib.changezone,	mylib.player.battlefield, null, null, null, false) ;
				menu.addline('To bottom deck ...',	mylib.moveinzone) ;
				menu.addline('To graveyard ...',	mylib.changezone,	mylib.player.graveyard) ;
				menu.addline('To exile ...',		mylib.changezone,	mylib.player.exile) ;
			}
		} else
			menu.addline('Library') ;
		if ( game.options.get('debug') )
			menu.addline() ;
			menu.addline('Debug internals', function(zone) {
				log2(this) ;
			}) ;

		return menu.start(ev) ;
	}
	// Accessors
	mylib.reveal = function() {
		if ( this.reveal_recieve() )
			this.player.sync() ;
	}
	mylib.reveal_recieve = function() {
		if ( ! this.player.attrs.library_revealed ) {
			this.player.attrs.library_revealed = true ;
			message(this.player.name+' plays with top of his library revealed : '+this.cards[this.cards.length-1].name, 'note') ;
			this.cards[this.cards.length-1].load_image() ;
			return true ;
		}
		return false ;
	}
	mylib.unreveal = function() {
		if ( this.unreveal_recieve()  )
			this.player.sync() ;
	}
	mylib.unreveal_recieve = function() {
		if ( this.player.attrs.library_revealed ) {
			this.player.attrs.library_revealed = false ;
			for ( var i in this.cards ) { // Recieving opponent's side end after ending side
				this.cards[i].load_image() ;
				this.cards[i].refresh() ;
			}
			message(this.player.name+' plays with top of his library hidden : '+this.cards[this.cards.length-1].name, 'note') ;
			this.cards[this.cards.length-1].load_image() ;
			return true ;
		}
		return false ;
	}
	mylib.toggle_reveal = function() {
		if ( this.player.attrs.library_revealed )
			this.unreveal() ;
		else
			this.reveal() ;
	}
	return mylib ;
}
function graveyard(player) {
	var mygrave = new VisibleZone(player, 'graveyard') ;
	unselzone(mygrave) ;
	// Events
	mygrave.contextmenu = function(ev) {
		var menu = new menu_init(this) ;
		menu.addline('Graveyard',		card_list_edit, this) ;
		if ( this.player.access() ) {
			if ( this.cards.length > 0 ) {
				menu.addline() ;
				// Various Submenus
				var creatsubmenu = new menu_init(this) ;
				var fbsubmenu = new menu_init(this) ;
				var dredgesubmenu = new menu_init(this) ;
				var retracesubmenu = new menu_init(this) ;
				var scavengesubmenu = new menu_init(this) ;
				for ( var i in this.cards ) {
					var card = this.cards[i] ;
					if ( card.is_creature() ) {
						var l = creatsubmenu.addline(card.name, card.changezone, this.player.battlefield) ;
						l.override_target = card ;
						l.moimg = card.imgurl() ;
					}
					if ( iss(card.attrs.flashback) ) {
						var l = fbsubmenu.addline(card.name+' ('+card.attrs.flashback+')', card.changezone, this.player.battlefield) ;
						l.override_target = card ;
						l.moimg = card.imgurl() ;
					}
					if ( isn(card.attrs.dredge) ) {
						var l = dredgesubmenu.addline(card.name+' ('+card.attrs.dredge+')', card.dredge) ;
						l.override_target = card ;
						l.moimg = card.imgurl() ;
					}
					if ( isb(card.attrs.retrace) && card.attrs.retrace ) {
						var l = retracesubmenu.addline(card.name+' ('+card.attrs.cost+')', card.changezone, this.player.battlefield) ;
						l.override_target = card ;
						l.moimg = card.imgurl() ;
					}
					if ( iss(card.attrs.scavenge) ) {
						var l = scavengesubmenu.addline(card.name+' ('+card.attrs.scavenge+')', card.changezone, this.player.exile) ;
						l.override_target = card ;
						l.moimg = card.imgurl() ;
					}
				}
				var addline = false ;
				if ( creatsubmenu.items.length > 0 ) {
					menu.addline('Creatures ('+creatsubmenu.items[0].length+')', creatsubmenu) ;
					addline = true ;
				}
				if ( fbsubmenu.items.length > 0 ) {
					menu.addline('Flashback ('+fbsubmenu.items[0].length+')', fbsubmenu) ;
					addline = true ;
				}
				if ( dredgesubmenu.items.length > 0 ) {
					menu.addline('Dredge ('+dredgesubmenu.items[0].length+')', dredgesubmenu) ;
					addline = true ;
				}
				if ( retracesubmenu.items.length > 0 ) {
					menu.addline('Retrace ('+retracesubmenu.items[0].length+')', retracesubmenu) ;
					addline = true ;
				}
				if ( scavengesubmenu.items.length > 0 ) {
					menu.addline('Scavenge ('+scavengesubmenu.items[0].length+')', scavengesubmenu) ;
					addline = true ;
				}
				if ( addline )
					menu.addline() ;
				// Zone menu
				menu.addline('To hand ...',		this.changezone, this.player.hand) ;
				menu.addline('To battlefield ...',	this.changezone, this.player.battlefield) ;
				menu.addline('To top deck ...',		this.changezone, this.player.library) ;
				menu.addline('To bottom deck ...',	this.changezone, this.player.library, null, 0) ;
				menu.addline('To exile ...',		this.changezone, this.player.exile) ;
			}
		}
		return menu.start(ev) ;
	}
	return mygrave ;
}
function exile(player) {
	var myexile = new VisibleZone(player, 'exile') ;
	unselzone(myexile) ;
	// Events
	myexile.contextmenu = function(ev) {
		var menu = new menu_init(this) ;
		menu.addline('Exile',			card_list_edit, this) ;
		if ( this.player.access() ) {
			if ( this.cards.length > 0 ) {
				menu.addline() ;
				menu.addline('To hand ...',		this.changezone, this.player.hand) ;
				menu.addline('To battlefield ...',	this.changezone, this.player.battlefield) ;
				menu.addline('To top deck ...',		this.changezone, this.player.library) ;
				menu.addline('To bottom deck ...',	this.changezone, this.player.library, null, 0) ;
				menu.addline('To graveyard ...',	this.changezone, this.player.graveyard) ;
			}
		}
		return menu.start(ev) ;
	}
	return myexile ;
}
// === [ SELZONES ] ============================================================
// Hand, battlefield
function selzone(result) { // Common
	// Event
	result.mousedown = function(ev) {
		var card = this.card_under_mouse(ev) ;
		if ( card != null )
			card.mousedown(ev) ;
		else { // Selection rectangle start
			switch ( ev.button ) {
				case 0 : // Left click
					// Clear selection
					game.selected.clear() ;
					// Start selection rectangle
					game.mouseX = ev.clientX ; 		
					game.mouseY = ev.clientY ;
					if ( this.type == 'hand' ) 
						game.selection_rectangle = {'x': ev.clientX, 'y': this.y, 'zone': this} ; // Selection in hand take all height
					else
						game.selection_rectangle = {'x': ev.clientX, 'y': ev.clientY, 'zone': this} ;						
					break ;
				case 1 : // Middle button click
					break ;
				case 2 : // Right click
					break ;
			}

		}
	}
	result.mouseup = function(ev) {
		switch ( ev.button ) {
			case 0 : // Left click
				this.selection = null ;
				break ;
			case 1 : // Middle button click
				break ;
			case 2 : // Right click
				if ( game.target.tmp == null )
					this.menu(ev) ;
				break ;
		}
	}
	result.dblclick = function(ev) {
		var card = this.card_under_mouse(ev) ;
		if ( card == null )
			this.selectline(ev) ;
		else
			card.dblclick(ev) ;
	}
	// Methods
	result.selectin = function(xb,yb,xe,ye) {
		var sel = new rectbe(xb,yb,xe,ye) ;
		for ( var i in this.cards ) {
			var card = this.cards[i] ;
			var selected = ( ( this.cards[i].get_attachedto() == null ) && ( collision(card.rect(), sel) ) ) ;
			if ( selected )
				game.selected.add(this.cards[i]) ;
			else
				game.selected.remove(this.cards[i]) ;
		}
	}
	// Init
	result.selzone = true ;
	return result ;
}
function hand(player) {
	// Init
	myhand = new VisibleZone(player, 'hand') ;
	selzone(myhand) ;
	// Image
	this.img = null ;
	game.image_cache.load(theme_image('ZoneIcons/'+myhand.type+'.png'), function(img, widget) {
		widget.img = img ;
	}, function(widget) {
		log('Unable to load image for '+widget) ;
	}, myhand) ;
	// Events
	myhand.parent_mouseup = myhand.mouseup ;
	myhand.mouseup = function(ev) {
		var card = this.card_under_mouse(ev) ;
		if ( card != null )
			card.mouseup(ev) ;
		else {
			this.parent_mouseup(ev) ;
			if ( game.drag != null ) {
				// Drop
				var idx = this.index_at(ev.clientX) ; // Drop on card #index
				if ( game.selected.zone != this ) // From another zone
					game.selected.changezone(this, null, idx) ;
				else
					game.selected.moveinzone(idx) ;
			}
		}
	}
	myhand.menu = function(ev) {
		var menu = new menu_init(this) ;
		if ( this.player.access() ) {
			menu.addline('Hand',			card_list_edit, this) ;
			menu.addline() ;
			menu.addline('Draw', 			this.draw_card) ;
			menu.addline('Undo last draw', 		this.undo) ;
			menu.addline('Mulligan',		this.mulligan) ;
			menu.addline() ;
			var all_revealed = true ;
			var all_hidden = true ;
			if ( this.cards.length == 0 ) {
				var all_revealed = false ;
				var all_hidden = false ;
			} else
				for ( var i = 0 ; i < this.cards.length ; i++ ) {
					var card = this.cards[i] ;
					if ( card.attrs.visible )
						all_hidden = false ;
					else
						all_revealed = false ;
				}
			if ( ! all_revealed )
				menu.addline('Reveal hand', this.reveal, true) ;
			if ( ! all_hidden )
				menu.addline('Hide hand', this.reveal, false) ;
			var rand_menu = new menu_init(this) ;
			rand_menu.addline('Discard ...', 	this.rand_changezone, this.player.graveyard) ;
			rand_menu.addline('Reveal ...', 	this.rand_reveal) ;
			menu.addline('Randomly', rand_menu) ;
		} else
			menu.addline('Hand') ;
		return menu.start(ev) ;
	}
	// Drawing
	myhand.draw = function(context) {
		canvas_set_alpha(zopacity/2, context) ;
		// Background
		if ( this.cards.length > 7 ) {
			context.fillStyle = 'red' ;
			context.fillRect(this.x+.5, this.y+.5, this.w, this.h) ;
		}
		canvas_set_alpha(zopacity, context) ;
		// Border
		if ( drawborder ) {
			context.strokeStyle = bordercolor ;
			context.strokeRect(this.x+.5, this.y+.5, this.w, this.h) ;
		} else {
			if ( this.player == game.player)
				var y = this.y ;
			else
				var y = this.y+this.h ;
			context.strokeStyle = bordercolor ;
			context.beginPath();
			context.moveTo(this.x+.5 + largezonemargin + manapoolswidth, y+.5) ;
			context.lineTo(this.x+.5+this.w - largezonemargin, y+.5) ;
			context.stroke() ;
		}
		canvas_reset_alpha(context) ;
		// Icon
		if ( this.img != null )
			context.drawImage(this.img, this.x + (this.w-this.img.width)/2 , this.y +(this.h-this.img.height)/2) ;
		// Cards
		for ( var i = this.cards.length ; i > 0 ; i-- ) {
			var card = this.cards[i-1] ;
			if ( isf(card.draw) ) {
				card.draw(context) ;
			} else {
				console.log('No draw for '+typeof card) ;
				console.log(card) ;
			}
		}
		// Data : card number
		var opt = game.options.get('zone_card_number') ;
		if ( ( opt == 'all' ) || ( opt == 'selzone' ) ) 
			this.disp_card_number(context) ;
	}
	myhand.refresh = function() { // For each card in zone, calculate + store its coords
		if ( this.editor_window != null )
			refresh_list(this) ; // Will set some cards as "watching", must be done before canvas refreshing
		if ( this.cards.length > 0 ) {
			// Determining optimal space between cards
			var spaceoffset = 0 ; // Will be --ed at least 1 time
			do {
				var space = cardhandspace + --spaceoffset ;
				var gridsize = cardwidth + space ; // Width of 1 card + 1 space between cards
				var cardswidth = gridsize * this.cards.length + space ; // Width of all cards + space between cards + space around cards
				var marginwidh = Math.floor( ( this.w - cardswidth ) / 2 ) ;
			} while ( cardswidth > this.w ) ;
			// Apply coordinates
			for ( var i = 0 ; i < this.cards.length ; i++ ) {
				index = this.cards.length-i-1
				margin = marginwidh + space
				x = margin + ( space + cardwidth ) * index ;
				y = Math.floor( ( this.h - cardheight ) / 2 ) - 1 ;
				this.cards[i].coords_set(this.x + x, this.y + y) ; 
			}
		}
	}
	// Methods
	myhand.mulligan = function() {
		var library = this.player.library ;
		// Defining how many cards to draw
		var nb = this.cards.length ;
		// Send all cards from hand to library
		if ( nb > 0 )
			(new Selection(this.cards)).changezone(library) ;
		library.shuffle() ;
		if ( nb > 1 ) // don't mulligan @ 0 when only 1 card in hand
			nb-- ;
		else
			nb = 7 ;
		// Draw cards
		this.draw_card(nb, true) ;
	}
	myhand.draw_card = function(nb, mulligan) {
		var library = this.player.library ;
		if ( ! isn(nb) )
			nb = 1 ;
		if ( library.cards.length < nb )
			return this.player.life.lose('Drawing while having no card in library') ;
		var sel = new Selection(library.cards.slice(-nb).reverse()) ;
		if ( mulligan )
			sel.settype('mulligan') ;
		sel.changezone(this) ;
	}
	myhand.undo = function() {
		var draw = this.player.draw.pop() ;
		if ( draw )
			(new Selection(draw[0])).settype('undo').changezone(draw[1]) ;
	}
	myhand.selectall = function(ev) {
		for ( var i in this.cards )
			game.selected.add(this.cards[i]) ;
	}
	myhand.selectline = myhand.selectall ;
	myhand.index_at = function(x) { // Indexes : 0 = right, this.cards.length -1 = left
		for ( var i = 0 ; i < this.cards.length ; i++ )
			if ( x > this.cards[i].x + this.cards[i].w/2 )
				return i ;
		return this.cards.length ;
	}
	myhand.reveal = function(b) {
		var sel = new Selection(this.cards) ;
		sel.reveal_from_hand(b) ;
	}
	return myhand ;
}
function battlefield(player) {
	var mybf = new VisibleZone(player, 'battlefield') ;
	selzone(mybf) ;
	// Getters
	mybf.untaped_lands = function() {
		var result = 0 ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			if ( card.is_land() && ! card.tapped )
				result++ ;
		}
		return result ;
	}
	// Events
	mybf.parent_mouseup = mybf.mouseup ;
	mybf.mouseup = function(ev) {
		var card = this.card_under_mouse(ev) ;
		if ( card != null )
			card.mouseup(ev) ;
		else {
			this.parent_mouseup(ev) ;
			// Drop
			if ( game.drag != null ) {
				var p = this.grid_at(ev.clientX - game.dragxoffset, ev.clientY - game.dragyoffset) ;
				if ( game.selected.zone != this ) {// From another zone
					game.selected.changezone(this, !ev.ctrlKey, null, p.x, p.y) ;
				} else {
					game.selected.place(p.x, p.y) ;
				}
			}
		}
	}
	mybf.menu = function(ev) {
		var mybf = this ;
		var menu = new menu_init(mybf) ;
		if ( mybf.player.access() ) {
			menu.addline('Battlefield',	card_list_edit, mybf) ;
			menu.addline() ;
			menu.addline('Re arrange',	mybf.rearange, mybf) ;
			var submenu = new menu_init(mybf) ;
			submenu.addline('20 sided',     rolldice, 20) ; // Can't add player as "message_send" mechanism is used
			submenu.addline('6 sided',     rolldice, 6) ;
			submenu.addline('Type number of faces',     rolldice) ;
			menu.addline('Roll a dice',	submenu) ;
			menu.addline('Flip a coin',	flip_coin) ;
		} else
			menu.addline('Battlefield') ;
		return menu.start(ev) ;
	}
	// Drawing
	mybf.draw = function(context) {
		// Border / background
		var y = this.y ;
		var h = this.h ;
		if ( this.player == game.player ) { // Down BF
			if ( game.turn.current_player == game.player ) { // I'm drawing player's BF during its turn
				y = handheight + bfheight  ;
				h = bfheight + turnsheight ;
			} else
				y = handheight + bfheight + turnsheight ;
		} else { // Up BF
			if ( game.turn.current_player == game.player ) // I'm drawing opponent's BF during my turn
				h = bfheight ;
			else
				h = bfheight + turnsheight;
		}
		canvas_set_alpha(zopacity, context) ;
		if ( drawborder ) {
			context.strokeStyle = bordercolor ;
			context.strokeRect(this.x+.5, y+.5, this.w, h) ;
		} else
			if ( this.player == game.player) {
				context.strokeStyle = bordercolor ;
				context.beginPath();
				context.moveTo(this.x+.5 + largezonemargin, y+.5) ;
				context.lineTo(this.x+.5+this.w - largezonemargin, y+.5) ;
				context.stroke() ;
			}
		canvas_reset_alpha(context) ;
		// Data : card number
		var opt = game.options.get('zone_card_number') ;
		if ( opt == 'all' )
			this.disp_card_number(context) ;
		// Grid
		if ( ( game.drag != null ) && ( game.widget_under_mouse == this) ) {
			context.strokeStyle = 'white' ;
			canvas_set_alpha(.1, context) ;
			for ( var i = 0 ; i < bfcols ; i++ ) {
				for ( var j = 0 ; j < bfrows ; j++ ) {
					from = this.grid_coords(i, j) ;
					to = this.grid_coords(i+1, j+1) ;
					var w = to.x - from.x ;
					var h = to.y - from.y ;
					if ( this.player.is_top && game.options.get('invert_bf') )
						from.y += cardheight ;
					context.strokeRect(from.x-.5, from.y-.5, w, h) ;
				}
			}
			canvas_reset_alpha(context) ;
		}
		// Cards
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			if ( card.get_attachedto() == null ) // All cards attached to nothing
				card.draw(context) ;
		}
	}
	mybf.refresh = function() { // For each card in zone, calculate + store its coords
		if ( this.editor_window != null )
			refresh_list(this) ; // Will set some cards as "watching", must be done before canvas refreshing
		for ( var i = 0 ; i < this.cards.length ; i++ )
			this.refresh_card(this.cards[i]) ;
	}
	mybf.refresh_card = function(card) { // Refresh one cards in zone
		if ( card.get_attachedto() == null ) { // Refresh all cards attached to nothing
			if ( card.zone.player.is_top && game.options.get('invert_bf') )
				var equip_offset = -10 ; // Invert equip offset
			else
				var equip_offset = 10 ;
			var coords = this.grid_coords(card.grid_x, card.grid_y) ;
			card.coords_set(coords.x, coords.y) ; 
			var attached = card.get_attached() ; // And all cards attached to
			for ( var j = 0 ; j < attached.length ; j++ ) {
				var offset = ( j + 1 ) * equip_offset ;
				attached[j].coords_set(coords.x + offset, coords.y - offset) ;
			}
		}
	}
	// Methods
		// Grid
	mybf.gridinit = function() { // Used in rearange
		this.grid = Array() ;
		for ( var i = 0 ; i < bfcols ; i++ ) {
			this.grid[i] = Array() ;
			for ( var j = 0 ; j < bfrows ; j++ )
				this.grid[i][j] = null ;
		}
	}
	mybf.ingrid = function(x,y) {
		if ( !isn(x) || !isn(y) )
			return false ;
		else
			return ( ( x >= 0 ) && ( x < bfcols ) && ( y >= 0 ) && ( y < bfrows ) ) ;
	}
	mybf.rearange = function() {
		this.gridinit() ;
		for ( var i = 0 ; i < this.cards.length ; i++ ) {
			var card = this.cards[i] ;
			if ( card.get_attachedto() == null )
				card.place(0, card.place_row()) ;
		}
		this.refresh() ;
	}
	mybf.grid_line_full = function(line) {
		if ( ( line < 0 ) || ( line >= bfrows ) )
			return true ; // Not in grid
		for ( var j = 0 ; j < bfcols ; j++ ) // Searching for 1 empty cell
			if ( this.grid[j][line] == null )
				return false ; // Found, line isn't full
		return true ; // Not found, line is full
	}
	mybf.grid_full = function() {
		for ( var i = 0 ; i < bfcols ; i++ )
			for ( var j = 0 ; j < bfrows ; j++ )
				if ( this.grid[i][j] == null )
					return false ;
		return true ;
	}
	mybf.grid_coords = function(gx, gy) { // Takes coords in grid, returns coords in canvas
		if ( ! isn(gx) )
			var lx = 0 ;
		else
			var lx = gx ;
		if ( ! isn(gy) )
			var ly = 0 ;
		else
			var ly = gy ;
		if ( this.player.is_top && game.options.get('invert_bf') ) // Invert cards' ordinate on top BF
			gy = bfrows - gy - 1 ;
		x = this.x + gridsmarginh + ( gridswidth * gx ) + cardxoffset ;
		y = this.y + gridsmarginv + ( gridsheight * gy ) + cardyoffset ;
		return {'x': x, 'y': y} ;
	}
	mybf.grid_at = function(cx, cy) { // Takes coords in canvas, returns coords in grid
		cx -= this.x + gridsmarginh + cardxoffset ; // Relative to zone
		cy -= this.y + gridsmarginv + cardyoffset ;
		cx = Math.floor(cx/gridswidth) ; // Scaled to grid
		cy = Math.floor(cy/gridsheight) ;
		// Stay in grid
		cx = max(cx, 0) ;
		cx = min(cx, bfcols-1) ;
		cy = max(cy, 0) ;
		cy = min(cy, bfrows-1) ;
		// Invert
		if ( this.player.is_top && game.options.get('invert_bf') )
			cy = bfrows - cy - 1 ;
		return {'x': cx, 'y': cy} ;
	}
	// Other BF specific
	mybf.refresh_pt = function(boost_bf) { // Refresh all powtou that may be affected by a card. Called on any changezone, and on transform
		for ( var i = 0 ; i < this.cards.length ; i++ )
			this.cards[i].refreshpowthou() ;
	}
	mybf.selectall = function(ev) {
		for ( var i in this.cards )
			game.selected.add(this.cards[i]) ;
	}
	mybf.selectline = function(ev) {
		var pos = this.grid_at(ev.clientX, ev.clientY) ;
		for ( var i in this.cards ) {
			var card = this.cards[i] ;
			if ( between(pos.y, card.grid_y-1, card.grid_y+1) && ( card.get_attachedto() == null ) )
				game.selected.add(card) ;
		}
	}
	mybf.untapall = function() {
		var sel = new Selection() ;
		for ( var i in this.cards ) {
			var card = this.cards[i] ;
			// Step settings
			if ( ! game.player.attrs.untap_all )
				continue ;
			if ( ( ! game.player.attrs.untap_lands ) && ( card.is_land() ) )
				continue ;
			if ( ( ! game.player.attrs.untap_creatures ) && ( card.is_creature() ) )
				continue ;
			// Attrs
			if ( ! card.has_attr('no_untap') ) {
				if ( card.attrs.no_untap_once ) {
					card.attrs.no_untap_once = false ;
					card.refresh() ;
					card.sync() ;
				} else
					sel.add(card) ;
			}
		}
		sel.tap(false) ;
	}
	// Initialisation
	mybf.gridinit() ;
	return mybf ;
}
// === [ VIRTUAL ] =============================================================
function sideboard(player) {
	var myside = new Zone(player, 'sideboard') ;
	myside.selzone = false ;
	myside.refresh = function() { // Only used for list editor
		if ( this.editor_window != null )
			refresh_list(this) ;
	}
	return myside ;
}
function Life(player) {
	// Referencing
	game.widgets.push(this) ;
	this.player = player ;
	virtual_unselzones(this) ; // Common code with unselzones
	Widget(this) ;
	// Drawing cache
	this.cache = document.createElement('canvas') ;
	this.context = this.cache.getContext('2d') ;
	// Image
	this.img = null ;
	game.image_cache.load([player.avatar, fallback_avatar], function(img, widget) {
		widget.img = img ;
		widget.refresh() ; // Initial drawing
	}, function(widget) {
		log('Unable to load image for '+widget) ;
	}, this) ;
	this.cnximg = null ;
	// Methods
	this.toString = function() {
		return this.player+'.life' ; // string representing basic variable's name (game.player.zonename)
	}
	// Drawing
	this.draw = function(context) {
		context.drawImage(this.cache, this.x, this.y) ;
	}
	this.refresh = function() { // Compute coordinates for all zone's cards, called on each adding/removing of cards, or visibility change
		var context = this.context ;
		context.clearRect(0, 0, this.w, this.h) ;
		// Border / background
		canvas_set_alpha(zopacity, context) ; // 0.5
		if ( iso(game.target) && ( game.target.tmp != null ) && ( game.target.tmp.targeted == this ) ) {
			context.fillStyle = 'yellow' ;
		} else
			context.fillStyle = 'black' ;
		context.fillRect(0, 0, this.w, this.h) ;
		if ( drawborder ) {
			context.strokeStyle = bordercolor ;
			context.strokeRect(.5, .5, this.w, this.h) ;
		} else {
			if ( this.player == game.player)
				var y = 0 ;
			else
				var y = this.h - 2 ;
			context.strokeStyle = bordercolor ;
			context.beginPath();
			context.moveTo(.5 + smallzonemargin, y+.5) ;
			context.lineTo(.5+this.w - smallzonemargin, y+.5) ;
			context.stroke() ;
		}
		canvas_reset_alpha(context) ;
		// Avatar
		if ( this.img != null )
			canvas_stretch_img(context, this.img, 0, 0, this.w, this.h, 10)
		// Data
		var margin = 5 ;
		var mw = this.w - 2 * margin ;
		var fontsize = 12 ;
		context.font = fontsize+"pt Arial";
			// Connexion icon
		var iconw = 0 ;
		if ( ! this.player.me ) {
			var iconw = 20 ;
			if ( this.cnximg != null )
				context.drawImage(this.cnximg, margin, margin, iconw, iconw) ;
		}
			// Nick
		canvas_framed_text_tl(context, this.player.name, margin+iconw, margin, bordercolor, mw, 'black', fontsize) ;
			// Life
		canvas_framed_text_tr(context, this.player.attrs.life, this.w - margin, margin + 20, bordercolor, mw, 'black', fontsize) ;
		// Damages
		if ( this.player.attrs.damages > 0 )
			canvas_framed_text_tr(context, this.player.attrs.damages, this.w - margin, margin + 17 + 20, 'red', mw, 'black', fontsize) ;
		// Poison
		if ( this.player.attrs.poison > 0 )
			canvas_framed_text_br(context, this.player.attrs.poison, this.w - margin, this.h - margin, 'lime', mw, 'black', fontsize) ;
	}
	// Events
	this.click = function(ev) {
		if ( ev.ctrlKey ) // With Ctrl : Damages
			switch ( ev.button ) {
				case 0 : // Left click
					this.damages_set(this.player.attrs.damages+1) ;
					break ;
				case 1 : // Middle button click
					this.damages_set(0) ;
					break ;
				case 2 : // Right click
						this.damages_set(this.player.attrs.damages-1) ;
					break ;
			}
		else if ( ev.shiftKey ) // With Shift : Poison counters
			switch ( ev.button ) {
				case 0 : // Left click
						this.setpoison(this.player.attrs.poison+1) ;
					break ;
				case 1 : // Middle button click
						this.setpoison(0) ;
					break ;
				case 2 : // Right click
						this.setpoison(this.player.attrs.poison-1) ;
					break ;
			}
		else // No modifier
			switch ( ev.button ) {
				case 0 : // Left click
					break ;
				case 1 : // Middle button click
					break ;
				case 2 : // Right click
					this.contextmenu(ev) ;
					break ;
			}
	}
	this.dblclick = function(ev) {
		if ( this.player.access() )
			if ( ! ev.ctrlKey && ! ev.shiftKey && ! ev.altKey && ! ev.metaKey ) // No modifier (Ctrl = dmg, other may be assigned one day)
				this.changelife() ;
	}
	this.contextmenu = function(ev) {
		var mylife = this ;
		if ( ev.ctrlKey ) // Damages workaround
			return mylife.damages_event(ev) ;
		var menu = new menu_init(mylife) ;
		if ( this.player.access() ) {
			menu.addline('+/- ?',	mylife.changelife).title = 'Type how many life you want to add to your current amount' ;
			menu.addline('-1',	mylife.changelife, -1) ;
			menu.addline('-2',	mylife.changelife, -2) ;
			menu.addline('-3',	mylife.changelife, -3) ;
			menu.addline('-4',	mylife.changelife, -4) ;
			menu.addline('-5',	mylife.changelife, -5) ;
			menu.addline('-10',	mylife.changelife, -10) ;
			menu.addline() ;
			menu.addline('+/- ?',	mylife.changepoison).title = 'Type how many poison counters you want to add to your current amount' ;
			menu.addline('+1',	mylife.setpoison, mylife.player.attrs.poison+1) ;
			menu.addline() ;
			if ( mylife.player.me ) // Player can only declares himself as loser or opponent as winner
				menu.addline('Lose',	mylife.lose).title = 'Declare you lost the game, then start siding' ;
			else
				menu.addline('Lose',	null).title = 'You can only declare you lost the game' ;
			menu.addCol() ;
			menu.addline('Life') ;
			menu.addline('+1',	mylife.changelife, 1) ;
			menu.addline('+2',	mylife.changelife, 2) ;
			menu.addline('+3',	mylife.changelife, 3) ;
			menu.addline('+4',	mylife.changelife, 4) ;
			menu.addline('+5',	mylife.changelife, 5) ;
			menu.addline('+10',	mylife.changelife, 10) ;
			menu.addline() ;
			menu.addline('Poison') ;
			menu.addline('-1',	mylife.setpoison, mylife.player.attrs.poison-1) ;
			menu.addline() ;
			if ( mylife.player.me ) // Player can only declares himself as loser or opponent as winner
				menu.addline('Win',	null).title = 'You can only declare your opponent won the game' ;
			else
				menu.addline('Win',	mylife.win).title = 'Declare your opponent won the game, then start siding' ;
		} else
			menu.addline('Life') ;
		return menu.start(ev) ;
	}
	// Accessors
	this.get_name = function() {
		return this.player.name ;
	}
		// Win /lose
	this.win = function(msg) {
		if ( ! msg )
			msg = '' ;
		var result = confirm('Did '+this.player.name+' just win ?\n'+msg) ;
		if ( result )
			this.player.win(msg) ;
		return result ;
	}
	this.lose = function(msg) {
		if ( ! msg )
			msg = '' ;
		var result =  confirm('Did '+this.player.name+' just lose ?\n'+msg) ;
		if ( result )
			this.player.opponent.win(msg) ;
		return result ;
	}
		// Poison
	this.setpoison_recieve = function(n) {
		if ( isn(n) ) {
			var diff = n - this.player.attrs.poison ;
			if ( ( diff != 0 ) && ( n >= 0 ) ) {
				this.player.attrs.poison = n ;
				message(this.player.name+' is now at '+this.player.attrs.poison+' ('+disp_int(diff)+') poison counters', 'poison') ;
				this.refresh() ;
			}
		}
	}
	this.setpoison = function(n) {
		if ( isn(n) ) {
			this.setpoison_recieve(n) ;
			if ( n >= 10 )
				if ( this.lose('Having 10 or more poison counters') )
					return null ; // this.lose does the sync 
			this.player.sync() ;
		}
	}
	this.changepoison = function() {
		n = 2 ;
		if ( game.turn.phase == 2 ) // Attack phase
			n = sum_attackers_powers(this.player)[1] ;
		n = prompt_int('How many poison counters to add to '+this.player.get_name()+' ?', n) ;
		this.setpoison(this.player.attrs.poison+n) ;
	}
		// Life
	this.changelife = function(n) {
		if ( ! isn(n) ) { // If no value passed, let's ask user, but befire, determine automatically default value based on attacking creatures
			if ( this.player.attrs.damages > 0 ) {
				n = -this.player.attrs.damages ;
			} else {
				if ( game.turn.phase.name == 'combat' ) // Attack phase
					n = sum_attackers_powers(this.player)[0] ;
				else
					n = -4 ;
			}
			n = prompt_int('How many life to add to '+this.player.get_name()+' ?', n) ;
		}
		if ( isn(n) && ( n != 0 ) )
			this.setlife(this.player.attrs.life+n) ;
	}
	this.setlife = function(n) {
		if ( ! isn(n) )
			n = prompt_int('New life score ?',this.player.attrs.life) ;
		if ( isn(n) && ( n != this.player.attrs.life  ) ) {
			if ( n < this.player.attrs.life ) { // taking damages
				this.player.attrs.damages = max(0, this.player.attrs.damages - this.player.attrs.life + n) ;
			}
			this.setlife_recieve(n) ;
			if ( n <= 0 )
				if ( this.lose('Having life less or equal than 0') )
					return null ; // this.lose does the sync 
			this.player.sync() ;
		}
	}
	this.setlife_recieve = function(newscore) {
		if ( isn(newscore) ) {
			var n = newscore - this.player.attrs.life ;
			if ( n != 0 ) {
				this.player.attrs.life = newscore ;
				message(this.player.name+' is now at '+this.player.attrs.life+' ('+disp_int(n)+') life points', 'life') ;
				this.refresh() ;
			}
		}
	}
		// Damages
	this.damages_event = function(ev) {
		if ( ev.ctrlKey ) { // Damages
			ev.preventDefault() ; // Default is start DND
			var life = ev.target.thing ;
			switch ( ev.button ) {
				case 0 : // Left click
					life.damages_recieve(life.player.attrs.damages+1) ;
					break ;
				case 1 : // Middle button click
					life.damages_recieve(0) ;
					break ;
				case 2 : // Right click
					life.damages_recieve(life.player.attrs.damages-1) ;
					break ;
			}
			life.player.sync() ;
			return false ;
		}
	}
	this.damages_set = function(n) {
		this.damages_recieve(n)
		this.player.sync() ;
	}
	this.damages_recieve = function(n) {
		if ( n >= 0 ) {
			this.player.attrs.damages = n ;
			this.refresh() ;
		}
	}
	return this ;
}
