// card/zone.js : card's zone management + position in battlefield

// Workarounds for selections
function card_moveinzone(to_index) {
	var sel = new Selection([this]) ;
	return sel.moveinzone(to_index) ;
}
function card_changezone(zone, base, index, xzone, yzone) {
	if ( game.selected ) // Flashback not clearing selection
		game.selected.clear() ;
	// Automatically place
	if ( ! isn(xzone) )
		xzone = 0 ;
	if ( ! isn(yzone) )
		yzone = this.place_row() ;
	var sel = new Selection([this]) ;
	var result = sel.changezone(zone, base, index, xzone, yzone) ;
	return result ;
}
function card_changezone_recieve(zone, base, index, xzone, yzone) { // Used only by detach ?
	game.selected.set(this) ; // Use global selection in order to keep it up to date
	return game.selected.changezone_recieve(zone, base, index, xzone, yzone) ;
}
// Change a card's zone, here goes stuff to do on new zone
function card_setzone(zone, base, index, xzone, yzone) {
	if ( ( zone.type != 'battlefield' ) && ( this.owner != zone.player ) )
		zone = this.owner[zone.type] ;
	this.zone = zone ; // Set new zone
	if ( ! isn(index) )
		index = zone.cards.length ;
	this.zone.cards.splice(index, 0, this) ; // Insert this card into zone
	if ( ! this.attrs.base_has(base) )
		var base = this.theorical_base() ;
	this.attrs.base_set(base)  ;
	switch ( this.zone.type ) { // Specific actions depending on zone type
		case 'battlefield' : // to a battlefield
			var ciptc = this.attrs.get('ciptc') ; // Condition for coming into play tapped
			if ( iss(ciptc) && eval(ciptc) )
				this.attrs.set('tapped', true) ;
			if ( ! isn(yzone) )
				yzone = this.place_row() ;
			// Place
			if ( ! this.place_recieve(xzone, yzone) ) {
				log('Failed to place '+this.name+' at '+xzone+', '+yzone) ;
				return false ;
			}
			this.refreshpowthou() ;
			break ;
		default: 
			this.attrs.clear() ;
	}
	this.load_image(function(img, card) {
		card.zone.refresh() ;
	}, this) ;
	return true ;
}
// Returns wich row to place card by default (depending on its type and user configuration)
function card_place_row(base) {
	var optype = '' ;
	if ( this.is_creature(base) )
		optype = 'place_creatures' ;
	else 
		if ( this.is_land(base) )
			optype = 'place_lands' ;
		else
			optype = 'place_noncreatures' ;
	var yzone = 0 ;
	switch ( game.options.get(optype) ) {
		case 'top' :
			yzone = 0 ;
			break ;
		case 'middle' :
			yzone = Math.floor(bfrows/2) ;
			break ;
		case 'bottom' :
			yzone = bfrows - 1 ;
			break ;
		default : // Unconfigured ?
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
function card_place(xzone, yzone) {
	var res = this.place_recieve(xzone, yzone) ;
	if ( res )
		action_send('place', {'card': this.id, 'x': this.grid_x, 'y': this.grid_y}) ;
	return res ;
}
function card_place_recieve(xzone,yzone) { // Move card to x,y if unoccupied, else x+1,y, etc.
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
	// In case of moving multiple cards to battlefield, each one has offsets indicating relative positions from ref
	var i = xzone ;
	var j = yzone ;
	// Loop all place_offset in line, then idem for all other lines
	var k = 0 ;
	var l = 1 ; // Try lines from top to bottom
	if ( j == this.zone.grid[0].length - 1 ) // Trying to place on last line
		var l = -1 ; // Try lines from bottom to top
	while ( ! this.move(i, j) ) {
		// Here, placing at i, j failed, let's find out next plausible position
		if ( this.zone.grid_full() ) { // Tried all positions
			log('Tried all positions, zone is full') ;
			return false ;
		}
		if ( this.zone.grid_line_full(j) ) { // Line is full
			j += l ; // Try next line, in desired direction
			i = 0 ; // Try from left
			k = 0 ; // Try without offset
			// Stay inside grid
			if ( j >= this.zone.grid[i].length )
				j = 0 ;
			if ( j < 0 )
				j = this.zone.grid[i].length ;
		} else { // Line isn't full
			i += place_offset ;
			if ( i >= this.zone.grid.length ) // End of line of grid with this offset, trying another offset
				i = ++k ;
		}
	}
	this.zone.refresh_card(this) ;
	return true ;
}
function card_move(xdest, ydest) { // Move if possible a card to x, y, returns false otherwise
	if ( this.zone.type != 'battlefield' ) {
		log('Unable to move a card ('+this.name+') on a '+this.zone.type) ;
		return false ;
	}
	// Force xdest,ydest in grid
	xdest = max(xdest, 0) ;
	xdest = min(xdest, bfcols-1) ;
	ydest = max(ydest, 0) ;
	ydest = min(ydest, bfrows-1) ;
	var destination = this.zone.grid[xdest][ydest] ;
	result = ( ( destination == null ) || ( destination == this ) ) ;
	if ( result ) { // There is no card on destination : move
		if ( this.get_attachedto() != null ) {
			this.detach() ;
			//this.sync_attrs = clone(this.attrs, true) ;
		} else
			this.clean_battlefield() ;
		this.set_grid(xdest, ydest) ;
		this.zone.grid[this.grid_x][this.grid_y] = this ; // Move
		// Consider attached cards as being @ new pos
		var attached = this.get_attached() ;
		if ( attached != null )
			for ( var i = 0 ; i < attached.length ; i++ )
				attached[i].set_grid(xdest, ydest) ;
		game.sound.play('click') ;
	}
	return result ;
}
function card_set_grid(x, y) {
	this.grid_x = x ;
	this.grid_y = y ;
}
function card_clean_battlefield() { // Clean old position in BF after moving a card
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
