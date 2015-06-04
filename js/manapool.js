// manapool.js : Classes and library to manage virtual zone "manapool"
function color_most(mana) {
	var result = '' ; // Color (array index) with most mana in pool
	var val = 0 ; // Reference value for comparison
	for ( var i in mana ) // Search color which has the more manas left
		if ( mana[i] > val ) { // That color has more than previous more
			result = i ;
			val = mana[i] ;
		}
	return result ;
}
function pay(cost, manaleft) { // If manaleft can pay cost, return mana left after paying cost, otherwise, return null
	// First separate manas into types
	var mana_colored = [] ; // Mana that can only be paid by 1 color
	var mana_hybrid = [] ; // Hybrid colored / colored
	var mana_unhybrid = [] ; // Hybrid colored / colorless
	var mana_colorless = 0 ; // Colorless
	var mana_phy = [] ; // Phyrexian
	for ( var i = 0 ; i < cost.length ; i++ ) {
		var mana = cost[i] ;
		if ( mana == 'X' )
			continue ;
		else if ( ''+parseInt(mana) == mana ) // Colorless mana, represented by numbers
			mana_colorless += parseInt(mana) ;
		else if ( mana.length == 1 ) // Monocolored mana
			mana_colored.push(mana) ;
		else if ( mana[0] == '2' ) // // Hybrid colored / colorless
			mana_unhybrid.push(mana[1]) ;
		else if ( mana[1] == 'P' ) // Phyrexian
			mana_phy.push(mana[0]) ;
		else // Hybrid colored / colored
			mana_hybrid.push(mana) ;
	}
	// Pay colored mana (at the begining, because those ones offer no choice in the way to pay it)
	for ( var i = 0 ; i < mana_colored.length ; i++ )
		if ( manaleft[mana_colored[i]] > 0 )
			manaleft[mana_colored[i]]-- ;
		else
			return null ;
	// Pay colored hybrid mana (after the begining because they offer a choice, but limited to colored)
	for ( var i = 0 ; i < mana_hybrid.length ; i++ ) {
		var color1 = mana_hybrid[i][0] ;
		var color2 = mana_hybrid[i][1] ;
		if ( manaleft[color1] == 0 ) { // Can't be paid with first color
			if ( manaleft[color2] == 0 ) // Nor with second
				return null ;
			else
				manaleft[color2]-- ; // Pay with second
		} else { // Can be paid with first color
			if ( manaleft[color2] == 0 ) // But not by second
				manaleft[color1]-- ; // Pay with first
			else { // Can be paid with both
				if ( manaleft[color2] > manaleft[color1] ) // Pay with the most present in pool
					manaleft[color2]-- ;
				else
					manaleft[color1]-- ;
			}
		}
	}
	// Pay colorless hybrid mana (after all colored, to avoid not paying a colored because of an hybrid colored/colorless)
	for ( var i = 0 ; i < mana_unhybrid.length ; i++ ) {
		var color = mana_unhybrid[i] ;
		if ( manaleft[color] > 0 )
			manaleft[color]-- ;
		else
			mana_colorless += 2 ;
	}
	// Pay colorless mana (at the end, to spend unused colored mana to pay colorless)
	if ( mana_colorless > 0 ) {
		// With colorless mana
		var aviable = parseInt(manaleft['X']) ;
		if ( aviable > mana_colorless ) { // Colorless mana in pool can pay all colorless mana in cost
			manaleft['X'] = ''+(mana_colorless - aviable) ;
			mana_colorless = 0 ;
		} else { // Colorless mana in pool can't pay all colorless mana in pool
			mana_colorless -= aviable ; // Pay the max we can
			manaleft['X'] = '0' ;
			if ( mana_colorless > 0 ) {
				log(mana_colorless+' colorless mana left to pay with colored') ;
				while ( mana_colorless > 0 ) { // Then pay the rest with the most present colors
					var color = color_most(manaleft) ;
					if ( color == '' ) // There is colorless mana left to pay, but no mana in pool
						return null ;
					log('Colorless paid with '+color) ;
					manaleft[color]-- ;
					mana_colorless-- ;
				}
			}
		}
	}
	// Pay phyrexian mana (after the end, as they can be paid with life if needed)
	for ( var i = 0 ; i < mana_phy.length ; i++ ) {
		var color = mana_phy[i] ;
		if ( manaleft[color] > 0 )
			manaleft[color]--
		else
			message('You have to pay 2 life for phyrexian '+color) ;
	}
	return manaleft ;
}
function Manapool(game, player) {
	// Heritage
	Widget(this) ;
	// Accessors
	this.toString = function () {
		return this.player.toString()+'.manapool' ;
	}
	this.mana = function() { // Returns an array containing mana currently in pool
		var mana = new Array() ;
		for ( var i in game.manacolors ) {
			var color = game.manacolors[i] ;
			mana[color] = this[color].value ;
		}
		return mana ;
	}
	this.empty = function() {
		var result = '' ;
		for ( var i in game.manacolors ) {
			var color = game.manacolors[i] ;
			if ( this[color].value > 0 ) {
				result += this[color].value+color+' ' ;
				this[color].set(0) ;
			}
		}
		if ( result != '' )
			message(active_player.name+'\'s manapool emptied ('+result+')')
		return result ;
	}
	// Design
	this.draw = function(context) {
		context.drawImage(this.cache, this.x, this.y) ;
	}
	this.refreshall = function() {
		for ( var i in game.manacolors )
			this[game.manacolors[i]].refresh() ;
		this.refresh() ;
	}
	this.refresh = function() {
		var context = this.context ;
		context.clearRect(0, 0, this.w, this.h) ;
		// Border / background
		canvas_set_alpha(.5, context) ;
		context.fillStyle = 'black' ;
		context.fillRect(0, 0, this.w, this.h) ;
		canvas_reset_alpha(context) ;
		if ( drawborder ) {
			context.strokeStyle = bordercolor ;
			context.strokeRect(.5, .5, this.w, this.h) ;
		}
		// ManaIcons
		for ( var i in game.manacolors )
			this[game.manacolors[i]].draw(context) ;
	}
	this.coords_compute = function() {
		if ( this.player.is_top && game.options.get('invert_bf') ) {
			var y = this.y + this.h - 1 ; // "margin" under white
			for ( var i in game.manacolors ) {
				y -= this.w - 1 ;
				this[game.manacolors[i]].set_coords(this.x, y, this.w, this.w) ;
			}

		} else {
			var y = this.y + 1 ; // "margin" over white
			for ( var i in game.manacolors ) {
				this[game.manacolors[i]].set_coords(this.x, y, this.w, this.w) ;
				y += this.w - 1 ;
			}
		}
	}
	// Events
	this.mana_under_mouse = function(ev) {
		for ( var i in game.manacolors ) {
			var mana = this[game.manacolors[i]] ;
			if ( dot_in_rect(new dot(ev.clientX, ev.clientY), mana.rect()) )
				return mana ;
		}
		return null ;
	}
	this.mouseout = function(ev) {
		game.settittle('') ;
		game.canvas.style.cursor = this.previous_cursor ;
	}
	this.mousemove = function(ev) {
		var under_mouse = this.mana_under_mouse(ev) ;
		if ( this.under_mouse != under_mouse ) {
			if ( ( this.under_mouse != null ) && ( isf(this.under_mouse.mouseout) ) )
				this.under_mouse.mouseout() ;
			this.under_mouse = under_mouse ;
			if ( ( under_mouse != null ) && ( isf(under_mouse.mouseover) ) )
				under_mouse.mouseover() ;
			if ( under_mouse == null )
				this.mouseover() ;
		}
	}
	this.click = function(ev) {
		if ( this.under_mouse != null )
			this.under_mouse.click(ev) ;
		else
			if ( ev.button == 2 ) {
				var menu = new menu_init(this) ;
				menu.addline('Mana Pool') ;
				var func = null ;
				if ( ! spectactor )
					menu.addline('Empty on each step', function(plop) { plop.empty_phase = ! plop.empty_phase ; }, this).checked = this.empty_phase ;
				return menu.start(ev) ;
			}
	}
		// DND cards over manapool to pay them
	this.mouseover = function(ev) {
		game.settittle('Manapool') ;
		// Backup cursor for mouseout
		this.previous_cursor = game.canvas.style.cursor ;
		// No effects
		if ( game.drag == null )
			return false ;
		// Only cast cards from hand
		if ( game.selected.zone.type != 'hand' )
			return false ;
		// Only cast if at least 1 card is DND
		var cards_split = game.selected.get_cards() ;
		if ( cards_split.length < 1 )
			return false ;
		// Start trying pay
		game.canvas.style.cursor = 'wait' ;
		// Sum cards cost
		var cost = [] ;
		for ( var i in cards_split )
			cost = cost.concat(cards_split[i].attrs.get('manas')) ;
		// Try to spend mana
		var manaleft = pay(cost, this.mana()) ;
		if ( manaleft == null ) {
			game.canvas.style.cursor = 'no-drop' ;
			return false ;
		}
		game.canvas.style.cursor = 'alias' ;
		return true ;
	}
	this.mouseup = function(ev) { // Drop
		if ( game.drag != null ) {
			var cost = [] ;
			var cards_split = game.selected.get_cards() ;
			for ( var i in cards_split )
				cost = cost.concat(cards_split[i].attrs.get('manas')) ;
			var manaleft = pay(cost, this.mana()) ; // Real pay with asking X
			if ( manaleft == null )
				return false ;
			for ( var i in manaleft )
				this[i].set(manaleft[i]) ;
			game.selected.changezone(this.player.battlefield) ;
		}
	}
	// Init
	game.widgets.push(this) ;
	this.type = 'manapool' ;
	this.player = player ;
	this.empty_phase = true ;
	this.under_mouse = null ;
	// Manas in manapool
	for ( var i in game.manacolors ) {
		var color = game.manacolors[i] ;
		this[color] = new Mana(this, color) ;
	}
}
function Mana(zone, color, x, y, w) {
	Widget(this) ;
	// Methods
		// Object
	this.toString = function() {
		return this.zone.toString()+'.'+color ;
	}
		// Accessors
	this.set = function(val) {
		if ( this.set_recieve(val) ) {
			action_send('mana', {'pool': this.toString(), 'value': this.value}) ;
		}
	}
	this.set_recieve = function(val) {
		if ( val < 0 ) 
			this.value = 0 ;
		else
			this.value = val ;
		this.refresh() ;
		return true ;
	}
	// Design
	this.draw = function(context) {
		context.drawImage(this.cache, this.x - this.zone.x, this.y - this.zone.y) ;
	}
	this.refresh = function() {
		var context = this.context ;
		context.clearRect(0, 0, this.w, this.h) ;
		if ( this.img != null )
			canvas_stretch_img(context, this.img, 1, 1, this.w-2, this.w-2) ;
		if ( this.value != 0 ) {
			canvas_set_alpha(zopacity, context)
			context.globalCompositeOperation = 'source-atop' ; // Only cover image where it's been drawn
			context.fillStyle = 'black' ;
			context.fillRect(
				1 ,
				1 ,
				this.w-1,
				this.h-1
			) ;
			context.globalCompositeOperation = 'source-over' ;
			canvas_reset_alpha(context) ;
			context.font = '12pt Arial' ;
			canvas_text_c(context, this.value, this.w/2, this.h/2, this.w) ;
		}
		this.zone.refresh() ;
	}
	// Events
	this.mouseover = function(ev) {
		game.settittle(game.mananames[this.color]+' mana') ;
	}
	this.mouseout = function(ev) {
		game.settittle('') ;
	}
	this.click = function(ev) {
		switch ( ev.button ) {
			case 0 : 
				this.set(this.value+1) ;
				break ;
			case 1 : 
				this.set(0) ;
				break ;
			case 2 : 
				this.set(this.value-1) ;
				break ;
			default : 
				log('Mouse button #'+ev.button+' isn\'t managed in mana') ;
		}
		return false ;
	}
	// Init
	this.zone = zone ;
	this.color = color ;
	this.value = 0 ;
	this.x = x ;
	this.y = y ;
	this.w = w ;
	this.h = w ; // Square
	this.img = null ;
	game.image_cache.load(theme_image('ManaIcons/'+color+'.png'), function(img, widget) {
		widget.img = img ;
		widget.refresh() ;
	}, function(widget) {
		log('Unable to load image for '+widget) ;
	}, this) ;
}
