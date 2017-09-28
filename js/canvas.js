// canvas.js : library related to canvas drawing
//context.mozImageSmoothingEnabled = false;
//  Class for widget (rectangular representation of a zone, manapool, turn or phase)
function Widget(obj) {
	// Accessors
	obj.set_coords = function(x, y, w, h) {
		// Set each setable coords
		if ( isn(x) ) 
			this.x = x ;
		if ( isn(y) ) 
			this.y = y ;
		if ( isn(w) ) 
			this.w = w ;
		if ( isn(h) ) 
			this.h = h ;
		// Reflect dimensions change on canvas cache
		if ( obj.cache.width != w ) 
			obj.cache.width = w ;
		if ( obj.cache.height != h )
			obj.cache.height = h ;
	}
	if ( !isf(obj.rect) )
		obj.rect = function() { // Coordinates of rectangle representation of widget (for "under mouse")
			return new rectwh(this.x, this.y, this.w, this.h) ;
		}
	obj.get_coords_center = function() {
		var result = {'x': this.x + Math.round(this.w/2), 'y': this.y} ;
		if ( isf(this.get_attachedto) && ( this.get_attachedto() != null ) ) {
			if ( game.options.get('invert_bf') && ( this.zone.player == game.opponent ) )
				result.y += this.h-5 ;
			else
				result.y += 5 ;
		} else
			result.y += Math.round(this.h/2) ;
		return result ;
	}
	// Drawing cache
	obj.cache = document.createElement('canvas') ;
	obj.context = obj.cache.getContext('2d') ;
	// Init
	obj.set_coords(0, 0, 0, 0) ;
	return obj ;
}
// Class for infobulle
function InfoBulle() {
	this.txt = '' ;
	this.timer = null ;
	this.date = null ;
	this.timeout = 5000 ;
	this.fadeout_duration = 1000 ;
	this.fadeout = this.timeout - this.fadeout_duration ;
	this.display = function() {
		if ( this.date == null )
			return false ;
		var date = new Date() ;
		if ( date - this.date > this.timeout )
			return false ;
		return true ;
	}
	this.canvas = function() {
		var canvas = document.createElement('canvas') ;
		var context = canvas.getContext('2d') ;
		var date = new Date() ;
		// Data
		var margin = 8 ;
		var b_h = 12 ;
		// Dimensions
		context.font = b_h+'pt Arial' ;
		var w = context.measureText(this.txt).width + 2 * margin ;
		var h = b_h + 2 * margin ;
		canvas.width = w ;
		canvas.height = h ;
		// Fade
		var elapsed_time = ( date - this.date ) ;
			// Out
		if ( elapsed_time > this.fadeout ) {
			var time_left = this.timeout - elapsed_time ;
			var alpha = time_left / this.fadeout_duration ;
			canvas_set_alpha(alpha, context) ;
		}
		// Border / Background
		context.fillStyle = 'black' ;
		context.strokeStyle = 'white' ;
		context.roundedRect(.5, .5, w-1, h-1, margin, true, false) ;
		// Text
		context.fillStyle = 'red' ;
		context.font = b_h+'pt Arial' ;
		var mx = ( w - context.measureText(this.txt).width ) / 2 ;
		var my = ( h - b_h ) / 2
		context.fillText(this.txt, mx, b_h + my, w) ;
		return canvas ;
	}
	this.draw = function(context) {
		var obj = null ;
		if ( iso(game.turn.button) )
			obj = game.turn.button
		else // Spectactor doesn't have a next step button, place on phases right
			obj = game.turn.phases[game.turn.phases.length-1] ;
		var x = 0 ;
		if ( obj != null )
			x = obj.x + obj.w + 5 ;
		var ib = this.canvas() ;
		var y = 4 * elementheight + ( turnsheight - ib.height ) / 2 ;
		context.drawImage(ib, x, y+.5) ;
	}
	this.set = function(txt) {
		this.txt = txt ;
		this.date = new Date() ;
		// Refresh timeout
		if ( game.infobulle.timer != null ) // Remove previous
			window.clearInterval(game.infobulle.timer) ;
		game.infobulle.timer = window.setTimeout(function() { // Set new
			game.infobulle.date = null ;
		}, this.timeout) ;
	}
}
function resize_window(ev) {
// --- [ Globals used by canvas ] ----------------------------------------------
// --- [ Compute dimensions ] --------------------------------------------------
	if ( window.innerHeight > 800 ) {
		cardimagewidth = 250 ; // Width of card images in zoom, draft and build
		cardimageheight = 354 ;
	} else {
		cardimagewidth = 180 ;
		cardimageheight = 255 ;
	}
	// Base dimensions depending on window size and global params
	paperwidth = window.innerWidth - cardimagewidth ; // Right frame
	paperheight = window.innerHeight ;
	turnsheight = minturnsheight ; // Reinitialize it or it never shorten
	// Compute other from that ones
	bfwidth = paperwidth - elementwidth - manapoolswidth ;
	bfheight = ( paperheight - turnsheight ) / 2 - handheight ;
	// Try different sizes until optimal is found
		// Width
	var gridsmargin = 5 ;
	gridswidth = 10 ;
	do {
		gridswidth++ ;
		gridsmarginh = Math.floor( gridsmargin + 1 + ( cardheight + 2 - gridswidth ) / 2 ) ;
		if ( gridsmarginh < 0 )
			gridsmarginh = 0 ;
	} while ( ( 2 * gridsmarginh + bfcols * gridswidth ) < bfwidth ) ;
	gridswidth-- ;
		// Height
	gridsmarginh = Math.floor( gridsmargin + 1 + ( cardheight + 2 - gridswidth ) / 2 ) ;
	if ( gridsmarginh < 0 )
		gridsmarginh = 0 ;
	gridsheight = 10 ;
	do {
		gridsheight++ ;
		gridsmarginv = Math.floor( gridsmargin + 1 + ( cardheight + 2 - gridsheight ) / 2 ) ;
		if ( gridsmarginv < 0 )
			gridsmarginv = 0 ;
	} while ( ( 2 * gridsmarginv + bfrows * gridsheight ) < bfheight ) ;
	gridsheight-- ;
	gridsmarginv = Math.floor( gridsmargin + 1 + ( cardheight + 2 - gridsheight ) / 2 ) ;
	// Recompose base dimensions from those computed ones
	bfwidth = 2 * gridsmarginh + bfcols * gridswidth ;
	bfheight = 2 * gridsmarginv + bfrows * gridsheight
	handwidth = bfwidth + manapoolswidth ;
	paperwidth -= 2 ; // Fake margin around right column
	//paperheight = 2 * bfheight + 2 * handheight + turnsheight ;
	turnsheight = paperheight - 2 * ( bfheight + handheight ) ;
	elementheight = Math.floor( ( paperheight - turnsheight ) / 8 ) ;
	// Compute other related data
	cardxoffset = Math.floor( ( gridswidth - cardwidth ) / 2 ) ;
	cardyoffset = Math.floor( ( gridsheight - cardheight ) / 2 ) ;
// --- [ Canvas apply ] --------------------------------------------------------
	game.canvas.width = paperwidth ;
	game.canvas.height = paperheight ;
// --- [ Widgets in canvas ] ---------------------------------------------------
	resize_player_zone(game.player) ;
	resize_player_zone(game.opponent) ;
	game.turn.set_coords(0, handheight + bfheight, paperwidth, turnsheight) ;
	game.turn.coords_compute() ;
// --- [ Right column ] --------------------------------------------------------
	resize_right_column() ;
// --- [ Side ] ----------------------------------------------------------------
	side_resize() ;
}
function resize_right_column() {
	var chatbox = document.getElementById('chatbox') ; // ? Should be cached
	var sendbox = document.getElementById('sendbox') ; // Globally cached by network.js
	var autotext = document.getElementById('autotext') ;
	var scrbot = chatbox.scrollHeight - ( chatbox.scrollTop + chatbox.clientHeight ) ; // Scroll from bottom, if 0, will scroll to se added line
	// Refresh autotext buttons in order to know size of their container
	if ( ! spectactor )
		autotext_buttons() ;
	// Resize right frame depending on paper width
	var rightframe = document.getElementById('rightframe') ;
	/*var width = window.innerWidth - paperwidth - 1 - 1 ; // -1 for 'right' CSS attribute
	if ( width < 0 ) {
		alert('Window not large enough : '+width)
		width = 0 ;
	}*/
	var width = cardimagewidth ;
	rightframe.style.width = width + 'px' ;
	var zoom = document.getElementById('zoom') ;
	zoom.style.width = cardimagewidth + 'px' ;
	zoom.style.height = cardimageheight + 'px' ;
	// Place sendbox on top of autotext now it's been filled
	if ( sendbox != null ) {
		sendbox.style.bottom = autotext.offsetHeight+'px' ;
		var ot = sendbox.offsetTop ;
	} else
		ot = 0 ;
	// Resize chatbox depending on size and position of each element in rightframe
	var height = ot - chatbox.offsetTop - 4 ;
	if ( height < 0 ) {
		log('Window not high enough : '+height) ;
		height = 0 ;
	}
	chatbox.style.height = height + 'px' ;
	// Restore full scrolldown
	if ( scrbot == 0 )
		chatbox.scrollTop = chatbox.scrollHeight ;
}
function resize_player_zone(player) { // Resize all zones for a player
	// Library
	if ( player.is_top ) 
		var y = 0 ;
	else
		var y = paperheight - elementheight ;
	player.library.set_coords(0, y, elementwidth, elementheight) ;
	player.library.refresh() ;
	// Graveyard
	if ( player.is_top )
		var y = elementheight ;
	else
		var y = paperheight - ( 2 * elementheight ) ; 
	player.graveyard.set_coords(0, y, elementwidth, elementheight) ;
	player.graveyard.refresh() ;
	// Exile
	if ( player.is_top ) 
		var y = 2 * elementheight ;
	else
		var y = paperheight - ( 3 * elementheight ) ; 
	player.exile.set_coords(0, y, elementwidth, elementheight) ;
	player.exile.refresh() ;
	// Hand
	if ( player.is_top ) 
		var y = 0 ;
	else
		var y = paperheight - handheight ; 
	player.hand.set_coords(elementwidth, y, handwidth, handheight) ;
	player.hand.refresh() ;
	// Battlefield
	if ( player.is_top ) 
		var y = handheight ;
	else
		var y = paperheight - handheight - bfheight ;
	player.battlefield.set_coords(elementwidth + manapoolswidth, y, bfwidth, bfheight) ;
	player.battlefield.refresh() ;
	// Life
	if ( player.is_top ) 
		var y = 3 * elementheight ;
	else
		var y = paperheight - ( 4 * elementheight ) ;
	var offset = handheight + bfheight - ( 4 * elementheight ) ; 
	if ( player.is_top ) 
		var y = 3 * elementheight ;
	else
		var y = paperheight - ( 4 * elementheight ) - offset ;
	player.life.set_coords(0, y, elementwidth, elementheight + offset) ;
	player.life.refresh() ;
	// Manapool
	if ( player.is_top ) 
		var y = handheight ;
	else
		var y = paperheight - handheight - bfheight ;
	player.manapool.set_coords(elementwidth, y, manapoolswidth, bfheight) ;
	player.manapool.coords_compute() ;
	player.manapool.refreshall() ;
}
// === [ MAIN DRAW ] ===========================================================
function draw() {
	// Old fashion draw, called on each event (mouse, network ...)
}
function display_start() {
	game.display.count = 0 ;
	window.setInterval(draw_timer, 40) ;
	game.display.time = 0 ;
	game.display.bench = 'Uninitialized' ;
	window.setInterval(debug_timer, 1000) ;
}
function debug_timer() {
	var txt = 'Display : '+Math.round(game.display.time / game.display.count)+'ms' ;
	//txt += ' '+game.card_under_mouse ;
	game.display.bench = txt ;
	game.display.count = 0 ;
	game.display.time = 0 ;
}
function draw_timer() {
	var begin = bench() ;
	// Background
	game.context.clearRect(0, 0, game.canvas.width, game.canvas.height) ;
	// Widgets (zones, manapools, phases ...)
	for ( var i in game.widgets )
		if ( isf(game.widgets[i].draw ) ) {
			game.context.save() ;
			game.widgets[i].draw(game.context) ;
			game.context.restore() ;
		} else
			log(game.widgets[i]+' has no draw method') ;
	// Cards number
	if ( game.options.get('zone_card_number') == 'follow' ) {
		var zone = game.widget_under_mouse ;
		if ( iso(zone) && isf(zone.disp_card_number) )
			zone.disp_card_number(game.context, true) ;
	}
	// Selection rectangle
		// Only show if mouse moved on X axis, for hand selection not being drawn on click
	if ( ( game.selection_rectangle != null ) && ( game.mouseX != game.selection_rectangle.x ) ) {
		// xb, yb is upper left corner, xe, ye is bottom right (necessary for limitation)
		var xb = min(game.mouseX, game.selection_rectangle.x) ;
		var yb = min(game.mouseY, game.selection_rectangle.y) ;
		var xe = max(game.mouseX, game.selection_rectangle.x) ;
		var ye = max(game.mouseY, game.selection_rectangle.y) ;
		// Limit to zone
		var zone = game.selection_rectangle.zone ;
		var xb = max(xb, zone.x + 1) ;
		var yb = max(yb, zone.y + 1 ) ;
		var xe = min(xe, zone.x + zone.w - 1 ) ;
		var ye = min(ye, zone.y + zone.h - 1 ) ;
		if ( zone.type == 'hand' ) // Selection in hand is full heightened
			ye = zone.y + zone.h - 1 ;
		// Draw
		var w = xe - xb ;
		var h = ye - yb ;
		game.context.strokeStyle = 'white' ;
		game.context.strokeRect(xb+.5, yb+.5, w, h) ;
		if ( game.options.get('transparency') ) {
			canvas_set_alpha(.1) ; // .5
			game.context.fillStyle = 'white' ;
			game.context.fillRect(xb+.5, yb+.5, w, h) ;
			canvas_reset_alpha() ;
		}
	}
	// DND
	if ( game.drag != null ) {
		var zone = game.widget_under_mouse ;
		if ( zone != null ) { // Dragging over canvas, not over an HTML element in front
			game.context.save() ;
			game.context.strokeStyle = 'white' ;
			game.context.fillStyle = 'white' ;
			canvas_set_alpha(bgopacity) ; // .5
			var cards = game.selected.cards ;
			var dragover = game.dragover ;
			for ( var i = 0 ; i < cards.length ; i++ ) {
				var card = cards[i] ;
				// Card's top left (reference for positionning)
				var tl_x = game.mouseX - game.dragxoffset ; // Mouse position - mouse click position
				var tl_y = game.mouseY - game.dragyoffset ;
				if ( zone.type == 'battlefield' ) {
					if ( dragover != null ) { // Dragover a card on BF : show attach
						var ato = dragover.get_attachedto() ; // Drop to root attached card
						if ( ato != null )
							dragover = ato ;
						var alreadyattached = dragover.get_attached() ;
						var offset = 10 * ( (cards.length-i) + alreadyattached.length) ;
						if ( zone.player.is_top && game.options.get('invert_bf') )
							card.draw(game.context, dragover.x-offset, dragover.y+offset, zone) ;
						else
							card.draw(game.context, dragover.x+offset, dragover.y-offset, zone) ;
					} else { // Draw destination on BF
						tl_x += card.xoffset * gridswidth ; // If moving multiple cards, add relative position of that one
						tl_y += card.yoffset * gridsheight ;
						var c = zone.grid_at(tl_x, tl_y) ;
						var p = zone.grid_coords(c.x, c.y) ;
						card.draw(game.context, p.x, p.y, zone) ;
					}
				} else { // Draw cards
					// Draw cards side on side, center on clicked one
					tl_x += ( i - game.selected.cards.indexOf(game.drag) ) * cardwidth ;
					card.draw(game.context, tl_x, tl_y, zone) ;
				}
			}
			game.context.restore() ;
			if ( dragover != null ) // Redraw card and attached over DNDed
				dragover.draw(game.context) ;
		}
	}
	// Targets
	game.target.draw(game.context) ;
	/* Detected mouse position * /
	game.context.strokeStyle = 'red' ;
	game.context.strokeRect(game.mouseX-.5, game.mouseY-.5, 3, 3) ;
	/**/
	// Title
	/*
	var nomove = new Date() - game.movedate ; // ms since last mouse move
	if ( ( game.title != '' ) && ( nomove > 20 ) ) {
		var mw = 200 ;
		var xo = 0 ;
		var yo = 20
		var x = game.mouseX + xo ;
		var y = game.mouseY + yo ;
		var xd = 'l' ;
		var yd = 't' ;
		if ( x + mw > game.canvas.width ) { // would draw out right of canvas
			var xd = 'r' ;
			x -= 2 * xo ;
		}
		if ( y + 20 > game.canvas.height ) { // would draw out right of canvas
			var yd = 'b' ;
			y -= yo  ;
		}
		if ( ( xd == 'l' ) && ( yd == 'b' ) )
			canvas_framed_text_bl(game.context, game.title, x, y, 'black', mw, 'lightgray') ;
		else if ( ( xd == 'l' ) && ( yd == 't' ) )
			canvas_framed_text_tl(game.context, game.title, x, y, 'black', mw, 'lightgray') ;
		else if ( ( xd == 'r' ) && ( yd == 'b' ) )
			canvas_framed_text_br(game.context, game.title, x, y, 'black', mw, 'lightgray') ;
		else if ( ( xd == 'r' ) && ( yd == 't' ) )
			canvas_framed_text_tr(game.context, game.title, x, y, 'black', mw, 'lightgray') ;
		else
			log('Unkwnown direction : '+xd+', '+yd) ; 			
	}
	*/
	// Infobulle
	if ( iso(game.infobulle ) && game.infobulle.display() )
		game.infobulle.draw(game.context) ;
	// Number of permanents
	if ( ( game.widget_under_mouse ) && ( game.widget_under_mouse.type == 'battlefield' ) ) {
		var coords = game.widget_under_mouse.grid_at(game.mouseX, game.mouseY) ;
		var nb = 0 ;
		var pow = 0 ;
		var untap = 0 ;
		for ( var j = -1 ; j < 2 ; j++ )
			for ( var i = 0 ; i < bfcols ; i++ ) {
				var card = game.widget_under_mouse.grid[i][coords.y+j] ;
				if ( card != null ) {
					nb++ ;
					pow += card.get_pow_total() ;
					//if ( card.is_land() && ! card.attrs.get('tapped') )
					if ( iso(card.attrs.provide) && ! card.attrs.get('tapped') )
						untap++ ;
				}
			}
		var pos = game.widget_under_mouse.grid_coords(bfcols-1, coords.y) ;
		game.context.save()
		/** /
		canvas_set_alpha(.1) ;
		var posl = game.widget_under_mouse.grid_coords(0, coords.y) ;
		var post = game.widget_under_mouse.grid_coords(coords.x, 0) ;
		game.context.fillStyle = 'white' ;
		game.context.fillRect(posl.x, posl.y, bfcols*gridswidth, gridsheight) ;
		//game.context.fillRect(post.x, post.y, gridswidth, bfrows*gridsheight) ;
		/**/
		canvas_set_alpha(.2) ;
		if ( nb > 0 ) {
			game.context.font = gridsheight+"pt Arial";
			var txt = nb ;
			if ( pow > 0 )
				txt += ' : '+pow ;
			if ( untap > 0 )
				txt = untap+' / '+txt ;
			canvas_text_tr(game.context, txt, pos.x+gridswidth, pos.y, 'white') ;
		}
		canvas_reset_alpha() ;
		game.context.restore() ;
	}
	// Additionnal information
	if ( game.options.get('debug') ) {
		game.display.count++ ;
		game.display.time += bench() - begin ;
		canvas_text_tr(game.context, game.display.bench, game.turn.x + game.turn.w-5, game.turn.y+5, 'white', paperwidth) ;
	}
}
// === [ MAIN EVENT DISPATCHER ] ===============================================
function canvasMouseDown(ev) {
	widget_cache_update(ev) ;
	var widget = game.widget_under_mouse ;
	// Prepare custom click
	game.mousedown_widget = widget ;
	// Trigger event on widget
	if ( ( widget != null ) && isf(widget.mousedown) )
		widget.mousedown(ev) ;
}
function canvasMouseMove(ev) {
	game.movedate = new Date() ; // For "title" mechanism
	// Mouse position cache (to get mouse position during draw, for selection rectangle, targets and DND)
	game.mouseX = ev.clientX ; 		
	game.mouseY = ev.clientY ;
	// Finding widget under mouse
	var widget = widget_cache_update(ev) ;
	// During selection : trigger select in rectangle
	if ( game.selection_rectangle != null )
		game.selection_rectangle.zone.selectin(ev.clientX, ev.clientY,
			game.selection_rectangle.x, game.selection_rectangle.y) ;
	// Target helper
	game.target.update_helper(ev) ;
	// Trigger event on widget
	if ( ( widget != null ) && isf(widget.mousemove) )
		widget.mousemove(ev) ;
}
function canvasMouseUp(ev) {
	widget_cache_update(ev) ;
	var widget = game.widget_under_mouse ;
	if ( widget != null ) {
		// Trigger event on widget
		if ( isf(widget.mouseup) )
			widget.mouseup(ev) ;
		// Trigger custom click
		if ( ( widget == game.mousedown_widget ) && isf(widget.click) )
			widget.click(ev) ;
	}
	// Mouseup = end of all complex mouse operations (AFTER triggers, etc.)
	drag_stop() ;
	game.selection_rectangle = null ;
	game.current_targeting = null ;
	if ( game.target.tmp != null )
		game.target.tmp.stop(null) ;
	// Pointer management
	if ( ( widget != null ) && (
			( isf(widget.card_under_mouse) && ( widget.card_under_mouse(ev) != null ) )
			|| ( isf(widget.step_under_mouse) && ( widget.step_under_mouse(ev) != null ) )
		)
	)
		game.canvas.style.cursor = 'pointer' ; 
	else
		game.canvas.style.cursor = '' ;
}
function canvasDblClick(ev) {
	var widget = widget_under_mouse(ev) ;
	if ( ( widget != null ) && isf(widget.dblclick) )
		widget.dblclick(ev) ;
	/* Can't stop doubleclick selecting zoom in chromium
	ev.preventDefault() ;
	ev.stopPropagation() ;
	ev.stopImmediatePropagation() ;
	*/
	return eventStop(ev) ;
}
function canvasEventStop(ev) { // Under windows, canvas doesn't seem to trigger events such as contextmenu or click, we must trigger event on window and cancel it only if canvas is targeted
	if ( ev.target.id == 'paper' )
		return eventStop(ev) ;
}
function canvas_add_events(canvas) {
	//draw_timer() ;
	canvas_loading() ;
	// Custom mouse management (uses only down, move, up, cancels all other)
	game.widget_under_mouse = null ;
	game.card_under_mouse = null ;
	game.selection_rectangle = null ;
	game.draginit = null ;
	game.dragxoffset = 0 ;
	game.dragyoffset = 0 ;
	game.drag = null ;
	game.dragover = null ;
	game.current_targeting = null ;
	canvas.addEventListener('selectstart', canvasEventStop, false) ;
	canvas.addEventListener('mousedown', canvasMouseDown, false) ;
	//canvas.addEventListener('mouseout', canvasMouseDown, false) ;
	window.addEventListener('mousemove', canvasMouseMove, false) ;
	window.addEventListener('mouseup', canvasMouseUp, false) ;
	canvas.addEventListener('mouseout', function(ev) {
		// Mouseout previous item
		if ( ( game.widget_under_mouse != null ) &&  isf(game.widget_under_mouse.mouseout) )
			game.widget_under_mouse.mouseout(ev) ;
		// Update widget cache
		game.widget_under_mouse = null ;
	}, false) ;
	// Exception for dblclick, FAR MUCH simpler to trigger this way
	canvas.addEventListener('dblclick', canvasDblClick, false) ;
	// Mouse events over target helper must have the same behaviour than over canvas
	game.target.helper.addEventListener('mousedown', canvasMouseDown, false) ;
	game.target.helper.addEventListener('mousemove', canvasMouseMove, false) ;
	game.target.helper.addEventListener('mouseup', canvasMouseUp, false) ;
	/*
	A click is a mousedown + a mouseup ON THE SAME ELEMENT. Here we have only 1 element (canvas), 
	let simulate click by detecting widget under mousedown and mouseup and generate a click if the same
	*/
	window.addEventListener('click', canvasEventStop, false) ; // Right click under windows
	//window.addEventListener('contextmenu', canvasEventStop, false) ;
	window.addEventListener('contextmenu', eventStop, false) ;
	window.addEventListener('resize', 	resize_window,	false) ; // Resize
}
function canvas_loading() {
	var font = game.context.font ;
	game.context.font = 50+"pt Arial";
	canvas_framed_text_c(game.context, 'Loading ...', paper.width/2, paper.height/2, 'white', paper.width, 'transparent', 40) ;
	game.context.font = font
}
// === [ MAIN LIB ] ============================================================
function widget_cache_update(ev) {
	var widget = widget_under_mouse(ev) ;
	// Trigger mousein/out
	if ( widget != game.widget_under_mouse ) { // Hovering a new item
		if ( widget != null ) { // If hovering outside canvas, don't update cache
			// Mouseout previous item
			if ( ( game.widget_under_mouse != null ) &&  isf(game.widget_under_mouse.mouseout) )
				game.widget_under_mouse.mouseout(ev) ;
			// Update widget cache
			game.widget_under_mouse = widget ;
			// Mouseover new item
			if ( ( game.widget_under_mouse != null ) && isf(game.widget_under_mouse.mouseover) )
				game.widget_under_mouse.mouseover(ev) ;
			// Cursor
			if ( game.draginit != null ) {
				if ( ( game.widget_under_mouse.type == 'battlefield' ) && ( ev.ctrlKey ) )
					game.canvas.style.cursor = 'copy' ;
				else if ( game.widget_under_mouse.type != 'manapool' )
					game.canvas.style.cursor = 'pointer' ;
			}
		}
	}
	return widget ;
}
function widget_under_mouse(ev) {
	for ( var i in game.widgets ) {
		var widget = game.widgets[i] ;
		var d = new dot(ev.clientX, ev.clientY) ;
		var r = new rectwh(widget.x-1, widget.y-1, widget.w+1, widget.h+1) ;
		if ( dot_in_rect(d, r) )
			return widget ;
	}
	return null ;
}
// === [ LIBRARY ] =============================================================
// Basic shapes
function canvas_dot(context, x, y) {
	context.lineWidth = 1 ;
	context.strokeStyle = 'red' ;
	context.strokeRect(x-.5, y-.5, 3, 3) ;
}
// === [ Effects ] ===
function canvas_set_alpha(alpha, context) {
	if ( ! iso(context) )
		context = game.context ;
	if ( ! isn(alpha) )
		alpha = bgopacity ;
	if ( context && game.options.get('transparency') ) 
		context.globalAlpha = alpha ;
}
function canvas_reset_alpha(context) {
	if ( ! iso(context) )
		context = game.context ;
	context.globalAlpha = 1 ;
}
// === [ Image ] ===
function canvas_stretch_img(context, img, x, y, w, h, margin) { // Stretch an image at specified coords
	context.save() ;
	if ( isn(margin) ) { // Apply margin
		x = x + margin ;
		y = y + margin ;
		w = w - 2*margin ;
		h = h - 2*margin ;
	}
	// Compute factors and chose minimal one
	var factor = min(h / img.height, w / img.width) ;
	// Apply minimal factor to both dimensions, to resize image keeping proportions
	new_w = Math.floor(img.width * factor) ;
	new_h = Math.floor(img.height * factor) ;
	// Add a margin to center image
	x += Math.floor((w - new_w) /2) ;
	y += Math.floor((h - new_h) /2) ;
	// Apply all those computed data
	context.drawImage(img, x, y, new_w, new_h) ;
	context.restore() ;
}
// === [ Text ] ===
function canvas_text(ctx, text, x, y, color, mw) {
	if ( iss(color) ) {
		ctx.fillStyle = color ;
		//ctx.strokeStyle = color ;
	} else {
		ctx.fillStyle = 'white' ;
		//ctx.strokeStyle = 'white' ;
	}
	if ( isn(mw) )
		ctx.fillText(text, x, y, mw) ;
	else
		ctx.fillText(text, x, y) ;
}
// Following functions draw text with given corner (t = top, l = left, b = bottom, r = right, c = center) at x, y
	// With frame
function canvas_framed_text_c(ctx, text, x, y, color, mw, fillcolor, fontsize) {
	var margin = 2 ;
	var w = min(mw, ctx.measureText(text).width) ;
	if ( isn(fontsize) )
		var h = fontsize ;
	else
		var h = 12 ;
	canvas_set_alpha(bgopacity, ctx) ;
	ctx.fillStyle = fillcolor ;
	ctx.fillRect(
		x - margin - w / 2,
		y - margin - h / 2,
		w + 2 * margin+1,
		h + 2 * margin+1
	) ;
	canvas_reset_alpha(ctx) ;
	canvas_text_c(ctx, text, x, y, color, mw) ;
	//canvas_dot(ctx, x, y) ;
}
function canvas_framed_text_tl(ctx, text, x, y, color, mw, fillcolor, fontsize) {
	var margin = 2 ;
	var w = min(mw-1, ctx.measureText(text).width) ;
	if ( isn(fontsize) )
		var h = fontsize ;
	else
		var h = 7 ;
	canvas_set_alpha(bgopacity, ctx) ;
	ctx.fillStyle = fillcolor ;
	ctx.fillRect(
		x ,
		y ,
		w + 2 * margin+1,
		h + 2 * margin+1
	) ;
	canvas_reset_alpha(ctx) ;
	canvas_text_tl(ctx, text, x+margin, y+margin, color, mw) ;
	//canvas_dot(ctx, x, y) ;
}
function canvas_framed_text_tr(ctx, text, x, y, color, mw, fillcolor, fontsize) {
	var margin = 2 ;
	var w = min(mw-1, ctx.measureText(text).width) ;
	if ( isn(fontsize) )
		var h = fontsize ;
	else
		var h = 7 ;
	canvas_set_alpha(bgopacity, ctx) ;
	ctx.fillStyle = fillcolor ;
	ctx.fillRect(
		x - w - 2 * margin+1,
		y ,
		w + 2 * margin+1,
		h + 2 * margin+1
	) ;
	canvas_reset_alpha(ctx) ;
	canvas_text_tr(ctx, text, x+margin, y+margin, color, mw) ;
	//canvas_dot(ctx, x, y) ;
}
function canvas_framed_text_bl(ctx, text, x, y, color, mw, fillcolor, fontsize) {
	var margin = 2 ;
	var w = min(mw, ctx.measureText(text).width) ;
	if ( isn(fontsize) )
		var h = fontsize ;
	else
		var h = 12 ;
	canvas_set_alpha(bgopacity, ctx) ;
	ctx.fillStyle = fillcolor ;
	ctx.fillRect(
		x,
		y - h - 2 * margin+1,
		w + 2 * margin+1,
		h + 2 * margin+1
	) ;
	canvas_reset_alpha(ctx) ;
	canvas_text_bl(ctx, text, x+margin, y+margin, color, mw) ;
	//canvas_dot(ctx, x, y) ;
}
function canvas_framed_text_br(ctx, text, x, y, color, mw, fillcolor, fontsize) {
	var margin = 2 ;
	var w = ctx.measureText(text).width ;
	if ( isn(fontsize) )
		var h = fontsize ;
	else
		var h = 12 ;
	canvas_set_alpha(bgopacity, ctx) ;
	ctx.fillStyle = fillcolor ;
	ctx.fillRect(
		x - w - 2 * margin+1,
		y - h - 2 * margin+1,
		w + 2 * margin+1,
		h + 2 * margin+1
	) ;
	canvas_reset_alpha(ctx) ;
	canvas_text_br(ctx, text, x+margin, y+margin, color, mw) ;
	//canvas_dot(ctx, x, y) ;
}
	// Without frame
function canvas_text_c(ctx, text, x, y, color, mw) {
	ctx.textBaseline = 'middle' ;
	ctx.textAlign = 'center' ;
	canvas_text(ctx, text, x, y, color, mw) ;
}
function canvas_text_tl(ctx, text, x, y, color, mw) {
	ctx.textBaseline = 'top' ;
	ctx.textAlign = 'left' ;
	canvas_text(ctx, text, x, y, color, mw) ;
}
function canvas_text_bl(ctx, text, x, y, color, mw) {
	ctx.textBaseline = 'bottom' ;
	ctx.textAlign = 'left' ;
	canvas_text(ctx, text, x, y, color, mw) ;
}
function canvas_text_tr(ctx, text, x, y, color, mw) {
	ctx.textBaseline = 'top' ;
	ctx.textAlign = 'right' ;
	canvas_text(ctx, text, x, y, color, mw) ;
}
function canvas_text_br(ctx, text, x, y, color, mw) {
	ctx.textBaseline = 'bottom' ;
	ctx.textAlign = 'right' ;
	canvas_text(ctx, text, x, y, color, mw) ;
}
