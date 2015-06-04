function card_mouseover(ev) {
	var name = this.get_name() ;
	if ( name.length > 10 ) 
		game.settittle(name) ;
	this.zoom() ;
	//this.refresh() ; // For bug "cards reversed in starting hand"
	if ( ( game.draginit == null ) && ( game.current_targeting == null ) ) // Not dragging nor targeting
		game.canvas.style.cursor = 'pointer' ;
	if ( ( this.zone.type == 'battlefield' ) && ( this.get_attachedto() == null ) ) {
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
}
function card_mouseout(ev) {
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
}
function card_mousedown(ev) {
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
		case 2 : // Right click : menu, targeting
			if ( ! this.selected() ) // Right clicking on a card that isn't selected
				game.selected.set(this) ; // Select only that one
			// Prepare targeting
			if ( game.current_targeting == null )
				game.current_targeting = this ;
			break ;
	}
}
function card_mouseup(ev) {
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
function card_dblclick(ev) {
	if ( ( ! this.zone.player.access() ) || ( ev.ctrlKey ) || ( ev.shiftKey ) ) // Ctrl is for damages, Shift for selection, we don't want to trigger dblclick
		return eventStop(ev) ;
	switch ( this.zone.type ) {
		case 'battlefield' :
			// Card's controler is declaring attackers
			if ( this.is_creature() && ( this.zone.player == game.turn.current_player ) && ( game.turn.step == 5 ) )
				game.selected.attack(this) ; // Must replace tap because vigilance creatures don't tap
			else
				game.selected.tap(!this.attrs.get('tapped')) ;
			break ;
		case 'hand' :
			if ( this.attrs.get('living_weapon') )
				this.living_weapon() ;
			else {
				var base = null ;
				if ( ev.ctrlKey || ev.altKey || ev.shiftKey )
					base = 'back' ;
				// Changezone
				game.selected.changezone(game.selected.zone.player.battlefield, base) ;
			}
			// Create living weapon token
			if ( game.stonehewer && this.is_creature() )
				tk.mojosto('stonehewer', this.attrs.get('converted_cost'), this) ;
			break ;
		default :
			log('Impossible to dbclick a card in '+this.zone.type) ;
	}
	return eventStop(ev) ; // Without, dblclick is passed under, that means 2 events are triggered on a card when
	// player dblclick on a text on a card (1 for the text, 1 for the card under)
}
function card_dragstart(ev) {
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
				mycard.xoffset = place_offset * ( xoffset - mycard.IndexInZone() ) ;
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
function card_zoom(base) {
	var zoom = document.getElementById('zoom') ;
	zoom.thing = this ;
	if ( this.attrs.base_current() == 'flip' )
		zoom.classList.add('rotated') ;
	else
		zoom.classList.remove('rotated') ;
	// Load image
	game.image_cache.load(this.imgurl(base), function(img, card) {
		document.getElementById('zoom').src = img.src ;
	}, function(card, url) {}, this) ;
	// Manage image events
	if ( this.is_visible() ) { // Image visible
		zoom.oncontextmenu = function(ev) { // Overwrite previous listener
			var card = ev.target.thing ;
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
