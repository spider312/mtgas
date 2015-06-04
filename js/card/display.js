// card/design.js : Card prototype functions relative to design and image management

// === [ DESIGN ] ==============================================================
// Coordinates of rectangle representation of card (for "under mouse")
function card_rect() {
	if ( this.attrs.get('tapped') ) {
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
// Draw card on 'context' at 'x', 'y', as if it were in 'zone'
function card_draw(context, x, y, zone) {
	var j, xo, yo ;
	if ( ! isn(x) )
		x = this.x ;
	if ( ! isn(y) )
		y = this.y ;
	if ( !iso(zone) || !iso(zone.player) ) // 'zone' may be another widget than zone (turn)
		zone = this.zone
	// First, draw attached in reverse order
	var attached = this.get_attached() ;
	for ( j = attached.length - 1 ; j >= 0 ; j-- ) {
		// Get offset from attached card stored position
		// Attached card position is stored in that card (for selection, click, etc.)
		var card = attached[j] ;
		xo = card.x - this.x ;
		yo = card.y - this.y ;
		if ( zone == this.zone || ! game.options.get('invert_bf') )
			card.draw(context, x + xo, y + yo, zone) ;
		else // DND from a battlefield to another
			card.draw(context, x - xo, y - yo, zone) ; // Invert xoffset as equiped cards have offset for current zone
	}
	// Then draw card on top
	context.save() ; // For rotation
	context.translate(x+this.w/2, y+this.h/2) ; // For rotation
	var angle = 0 ;
	// Inverted in an opponent's zone
	if ( zone.player.is_top && game.options.get('invert_bf') )
		angle += 180 ;
	// Rotate (tapped)
	if ( ( zone.type == 'battlefield' ) && this.attrs.get('tapped') )
		angle += 90 ;
	if ( angle > 0 )
		context.rotate(angle* Math.PI / 180) ;
	context.translate(-this.w/2, -this.h/2) ; // For rotation
	context.drawImage(this.cache, 0, 0) ;
	context.restore() ; // For rotation
}
// Cache power, toughness and their color in order not to recompute it on each frame draw
function card_refreshpowthou() {
	var redraw = ( this.zone.type == 'battlefield' ) ;
	redraw = redraw && ( 
		isn(this.attrs.get('pow')) && isn(this.attrs.get('thou')) ) 
		|| ( isn(this.attrs.get('pow_eot')) && isn(this.attrs.get('thou_eot'))
	) ;
	// Don't redraw if pow/thou has been removed
	if ( redraw ) { // Would redraw
		this.pow_thou = this.get_powthou_total() ;
		this.pow_thou_color = 'white' ;
		// Show creat has modified P/T until EOT
		if ( isn(this.attrs.get('pow_eot')) || isn(this.attrs.get('thou_eot')) )
			this.pow_thou_color = 'lightblue' ;
		// Show damages would kill creat
		if ( this.get_damages() >= this.get_thou_total() )
			this.pow_thou_color = 'red' ;
	} else // Wouldn't redraw
		this.pow_thou = '' ; // Erase
	this.refresh('powtou') ;
	return redraw ;
}
// Redraw canvas cache
function card_refresh(from) {
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
	switch ( this.zone.type ) {
		case 'hand' :
			if ( this.attrs.base_current() != this.theorical_base() )
				color = 'blue' ;
			break ;
		case 'battlefield' : // Attacking takes precedence over no_untap, so applied after
			if ( ( this.attrs.get('no_untap') ) || ( this.attrs.get('no_untap_once') ) )
				color = 'maroon' ;
			if ( this.attrs.get('attacking') )
				color = 'red' ;
			break ;
	}
	if ( color != this.bordercolor ) { // Need to draw inner line
		context.strokeStyle = color ;
		var offset = 1.5 ;
		context.strokeRect(lw+offset, lw+offset, this.w-2*(lw+offset), this.h-2*(lw+offset)) ;
	}
	// Flip
	var flipped = ( this.attrs.base_current() == 'flip' ) ;
	if ( flipped ) {
		context.translate(this.w/2, this.h/2) ;
		context.rotate(Math.PI) ;
		context.translate(-this.w/2, -this.h/2) ;
	}
	// Image
	if ( this.img != null )
		context.drawImage(this.img, lw, lw, this.w-2*lw, this.h-2*lw) ;
	// Unflip
	if ( flipped ) {
		context.translate(this.w/2, this.h/2) ;
		context.rotate(-Math.PI) ;
		context.translate(-this.w/2, -this.h/2) ;
	}
	// Copy
	var copy = this.attrs.get('copy') ;
	if ( iso(copy) && ( copy.img != null ) ) { // TODO : don't use copy.img, as copy can change status
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
		context.drawImage(copy.img, lw, lw, this.w-2*lw, this.h-2*lw) ;
		context.restore() ;
	}
	if ( this.zone.type == 'battlefield' ) { // Textual infos (p/t, note, damages, counters)
		var top = lw ;
		var bottom = this.w-2*lw+1 ;
		var left = lw ;
		var right = this.h-2*lw+1 ;
		if ( game.options.get('display_card_names') ) {
			// Name
			var name = this.get_name() ;
			if ( iss(name) && ( name.length > 10 ) )
				name = name.substr(0, 10)+'...' ;
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
				canvas_framed_text_c(context, note, this.w/2, this.h/2, 'white',
					cardwidth-2*(lw+2)-1, this.bordercolor) ;
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
			canvas_framed_text_br(context, this.pow_thou, bottom, right,
				this.pow_thou_color, cardwidth, this.bordercolor) ;
	}
	var manas = this.attrs.get('manas') ;
	if (
		( this.zone.type == 'hand' )
		&& game.options.get('display_card_names')
		&& iso(manas)
	) {
		// Display mana cost
			// Compute begining of cost displaying zone
		var size = 16 ;
		var w = manas.length * size ; // Width of an icon
		var mw = cardwidth-2*(lw+2) ; // Max width all icon can take
		if ( w > mw ) { // Cost larger than card
			iw = Math.floor(mw/manas.length) ; // Compute new icon width
			w = iw * manas.length ;
		} else
			iw = size ;
			// Display each icon
		for ( var i = 0 ; i < manas.length ; i++ ) {
			var color = manas[i] ;
			if ( ! game.manaicons[color] )
				game.image_cache.load(theme_image('ManaIcons/'+color+'.png'), function(img, data) {
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
}
// === [ IMAGE ] ===============================================================
function card_load_image(callback, param) {
	this.img_loading = game.image_cache.load(this.imgurl(), function(img, card) {
		if ( img == card.img_loading ) { // Load image only if it's last asked
			card.img = img ;
			card.refresh('load_image') ;
			if ( isf(callback) )
				callback(img, param) ;
			/** /
			if ( ! card.zone.selzone ) { // In unselzones, refresh entire zone cache
				if ( card.zone.type == 'library' ) { // For library, only refresh top card
					if ( card.IndexInZone() == card.zone.cards.length-1 )
						card.zone.refresh() ;
				} else
					card.zone.refresh() ;
			}
			/**/
		}
	}, function(card, zone) {
		log('Image not found for '+card.name+', creating text') ;
	}, this) ;
}
