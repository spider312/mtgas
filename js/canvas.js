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
	obj.get_coords_center = function() {
		return {'x': this.x + Math.round(this.w/2), 'y': this.y + Math.round(this.h/2)} ;
	}
	// Drawing cache
	obj.cache = document.createElement('canvas') ;
	obj.context = obj.cache.getContext('2d') ;
	// Init
	obj.set_coords(0, 0, 0, 0) ;
	return obj ;
}
function resize_window(ev) {
	var chatbox = document.getElementById('chatbox') ; // ? Should be cached
	var sendbox = document.getElementById('sendbox') ;
	var autotext = document.getElementById('autotext') ;
	var scrbot = chatbox.scrollHeight - ( chatbox.scrollTop + chatbox.clientHeight ) ; // Scroll from bottom, if 0, will scroll to se added line
// --- [ Globals used by canvas ] ----------------------------------------------
	//draw_counter = 0 ;
	ping = 0 ;
	bench = 0 ;
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
	paperwidth = elementwidth + handwidth ;
	paperheight = 2 * bfheight + 2 * handheight + turnsheight ;
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
	draw() ;
// --- [ Right column ] --------------------------------------------------------
	// Refresh autotext buttons in order to know size of their container
	if ( ! spectactor )
		autotext_buttons() ;
	// Resize right frame depending on paper width
	var rightframe = document.getElementById('rightframe') ;
	var width = window.innerWidth - paperwidth - 1 - 1 ; /* -1 for 'right' CSS attribute */
	if ( width < 0 ) {
		alert('Window not large enough : '+width)
		width = 0 ;
	}
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
function infobulle(message, x, y) {
	var bulle = create_div(message) ;
	document.body.appendChild(bulle) ;
	bulle.classList.add('infobulle') ; // After sizing, 'cuz it contains visibility = none
	bulle.style.display = 'inline' ; // Div are blocks, bloc width is browser's inside width
	if ( isn(x) )
		bulle.style.left = x + 'px' ;
	else
		bulle.style.left = Math.round((window.innerWidth-bulle.scrollWidth)/2) + 'px' ;
	if ( isn(y) )
		bulle.style.top = y + 'px' ;
	else
		bulle.style.top = Math.round((window.innerHeight-bulle.scrollHeight)/2) + 'px' ;
	bulle.disapear = function() {
		this.to = window.setTimeout(function(param) {
			$(param).fadeOut(500, function() {
				bulle.del() ;
			}) ;
		}, 3000, this) ;
	}
	bulle.del = function() {
		window.clearTimeout(this.to) ;
		document.body.removeChild(this) ;
	}
	bulle.set = function(message) {
		// Change message
		this.textContent = message
		// Reinit fadeout
		window.clearTimeout(this.to) ;
		this.disapear() ;
	}
	bulle.style.display = 'none' ; // For fadein
	$(bulle).fadeIn(500, function() {
		this.disapear() ;
		bulle.addEventListener('click', function() {
			this.del() ;
		}, false) ;
	}) ;
	return bulle ;
}
// === [ MAIN DRAW ] ===========================================================
function draw() {
	//draw_counter++ ;
	game.drawing = true ;
	if ( game.drawing ) {
		var begin = new Date()
		var begin = begin.getMilliseconds() + begin.getSeconds()*1000 ;
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
		// Selection rectangle
		if ( ( game.selection_rectangle != null ) && ( game.mouseX != game.selection_rectangle.x ) ) { // Only show if mouse moved on X axis, for hand selection not being drawn on click
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
			if ( localStorage['transparency'] == 'true' ) {
				canvas_set_alpha(.1) ; // .5
				game.context.fillStyle = 'white' ;
				game.context.fillRect(xb+.5, yb+.5, w, h) ;
				canvas_reset_alpha() ;
			}
		}
		// Targets
		game.target.draw(game.context) ;
		// DND
		if ( game.drag != null ) {
			var zone = game.widget_under_mouse ;
			if ( zone != null ) { // Dragging over canvas, not over en HTML element in front
				game.context.save() ;
				game.context.strokeStyle = 'white' ;
				game.context.fillStyle = 'white' ;
				canvas_set_alpha(bgopacity) ; // .5
				var cards = game.selected.cards ;
				for ( var i = 0 ; i < cards.length ; i++ ) {
					var card = cards[i] ;
					// Card's top left (reference for positionning)
					var tl_x = game.mouseX - game.dragxoffset ; // Mouse position - mouse click position
					var tl_y = game.mouseY - game.dragyoffset ;
					if ( zone.type == 'battlefield' ) {
						if ( game.dragover != null ) { // Dragover a card on BF : show attach
							var offset = 10 * ( i + 1 ) ;
							card.draw(game.context, game.dragover.x+offset, game.dragover.y-offset) ;
						} else { // Draw destination on BF
							tl_x += card.xoffset * gridswidth ; // If moving multiple cards, add relative position of that one
							tl_y += card.yoffset * gridsheight ;
							var c = zone.grid_at(tl_x, tl_y) ;
							var p = zone.grid_coords(c.x, c.y) ;
							card.draw(game.context, p.x, p.y) ;
						}
					} else { // Draw cards
						tl_x += ( i - game.selected.cards.indexOf(game.drag) ) * cardwidth ; // Draw cards side on side, center on clicked one
						card.draw(game.context, tl_x, tl_y) ;
					}
				}
				game.context.restore() ;
			}
		}
		/* Detected mouse position
		game.context.strokeStyle = 'red' ;
		game.context.strokeRect(game.mouseX-.5, game.mouseY-.5, 3, 3) ;
		/**/
		// Title
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
		var end = new Date()
		var end = end.getMilliseconds() + end.getSeconds()*1000 ;
		var time = end - begin ;
		var txt = 'Display : '+time+'ms' ;
		txt += ' Ping : '+ping+'ms' ;
		//txt += ' Frames : '+draw_counter ;
		//txt += ' Message : '+bench ;
		//txt += ' Selection : '+game.selected.zone ;
		canvas_text_tr(game.context, txt, game.turn.x + game.turn.w-5, game.turn.y+5, 'white', paperwidth) ;
		game.drawing = false ;
	} else 
		log('framedrop') ;
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
	draw() ; // Canvas mouseDown
}
function canvasMouseMove(ev) {
	game.movedate = new Date() ; // For "title" mechanism
	// Mouse position cache (to get mouse position during draw, for selection rectangle, targets and DND)
	game.mouseX = ev.clientX ; 		
	game.mouseY = ev.clientY ;
	// Finding widget under mouse
	var widget = widget_cache_update(ev) ;
	var needdraw = false ;
	// During selection : trigger select in rectangle
	if ( game.selection_rectangle != null ) {
		game.selection_rectangle.zone.selectin(ev.clientX, ev.clientY, game.selection_rectangle.x, game.selection_rectangle.y) ;
		needdraw = true ;
	}
	// During Targeting : Move helper
	if ( game.target.update_helper(ev) )
		needdraw = true ;
	// Trigger event on widget
	if ( ( widget != null ) && isf(widget.mousemove) )
		widget.mousemove(ev) ;
	// Redraw while DNDing
	if ( game.draginit != null )
		needdraw = true ;
	if ( needdraw )
		draw() ; // Canvas mouseMove
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
	draw() ; // Canvas mouseUp
	// Pointer management
	if ( 
		( isf(widget.card_under_mouse) && ( widget.card_under_mouse(ev) != null ) )
		|| ( isf(widget.step_under_mouse) && ( widget.step_under_mouse(ev) != null ) )
	)
		game.canvas.style.cursor = 'pointer' ; 
	else
		game.canvas.style.cursor = '' ;
}
function canvasDblClick(ev) {
	var widget = widget_under_mouse(ev) ;
	if ( ( widget != null ) && isf(widget.dblclick) )
		widget.dblclick(ev) ;
	draw() ; // Canvas dblClick
}
function canvasEventStop(ev) { // Under windows, canvas doesn't seem to trigger events such as contextmenu or click, we must trigger event on window and cancel it only if canvas is targeted
	if ( ev.target.id == 'paper' )
		return eventStop(ev) ;
}
function canvas_add_events(canvas) {
	game.drawing = false ;
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
	canvas.addEventListener('mousedown', canvasMouseDown, false) ;
	//canvas.addEventListener('mouseout', canvasMouseDown, false) ;
	canvas.addEventListener('mousemove', canvasMouseMove, false) ;
	canvas.addEventListener('mouseup', canvasMouseUp, false) ;
	canvas.addEventListener('mouseout', function(ev) {
		// Mouseout previous item
		if ( ( game.widget_under_mouse != null ) &&  isf(game.widget_under_mouse.mouseout) )
			game.widget_under_mouse.mouseout(ev) ;
		// Update widget cache
		game.widget_under_mouse = null ;
		draw() ;// Canvas mouseOut
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
	window.addEventListener('contextmenu', canvasEventStop, false) ;
	window.addEventListener('resize', 	resize_window,	false) ; // Resize
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
	//var widget = game.widget_under_mouse ; // Use cache
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
	//log('No widget under '+ev.clientX+', '+ev.clientY) ;
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
	if ( context && ( localStorage['transparency'] == 'true' ) ) 
		context.globalAlpha = alpha ;
}
function canvas_reset_alpha(context) {
	if ( ! iso(context) )
		context = game.context ;
	//if ( localStorage['transparency'] == 'true' ) 
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
// === [ Basic shapes ] ===
function canvas_rounded_rect(ctx, x, y, width, height, radius) {
	ctx.beginPath() ;
	ctx.moveTo(x,y+radius) ;
	ctx.lineTo(x,y+height-radius) ;
	ctx.quadraticCurveTo(x,y+height,x+radius,y+height) ;
	ctx.lineTo(x+width-radius,y+height) ;
	ctx.quadraticCurveTo(x+width,y+height,x+width,y+height-radius) ;
	ctx.lineTo(x+width,y+radius) ;
	ctx.quadraticCurveTo(x+width,y,x+width-radius,y) ;
	ctx.lineTo(x+radius,y) ;
	ctx.quadraticCurveTo(x,y,x,y+radius) ;
	ctx.stroke();  
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
function canvas_frame(ctx, x, y, w, h, xp, yp, margin) {
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
