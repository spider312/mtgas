// this.js
// Manages various right-click-style's contextual menu and their common lib
function menu_init(target) {
	var menu = this ; // For events, not having the same "this" as this object
	if ( target == undefined )
		target == null ;
	this.target = target ;
	this.items = new Array() ;
	this.toString = function() {
		var result = 'Menu ('+this.target+') : ' ;
		for ( var i in this.items )
			result += this.items[i]+', ' ;
		return result
	}
	this.addCol = function() {
		this.items.push(new Array()) ;
		return this.items.length ;
	}
	this.addline = function(name, action) { //, args[1], args[2], etc.
		if ( this.items.length < 1 ) // Initial column
			this.addCol() ;
		var item = new Object() ;
		item.toString = function() {
			return this.text ;
		}
		item.text = name ;
		item.action = action ;
		item.checked = null ;
		item.args = new Array() ;
		for ( var i = 2 ; i < arguments.length ; i++ )
			item.args.push(arguments[i]) ;
		item.buttons = [] ;
		this.items[this.items.length-1].push(item) ;
		return item ;
	}
	this.start = function(ev, parentMenu) {
		/* global vars 'document' and 'window' refer to main doc/win, if we're working
		in another window (in listeditor for example), we need to know that window's 
		win/doc */
		var doc = ev.target.ownerDocument ; // Document we're working in
		var win = doc.defaultView ; // Window we're working in
		this.table = doc.createElement('table') ;
		this.opened = null ;
		if ( ! parentMenu ) // Root menu
			parentMenu = null ;
		// Determine total line number (highest line number in columns)
		var linenb = 0 ;
		for ( var i = 0 ; i < this.items.length ; i++ ) {
			if ( this.items[i].length > linenb )
				linenb = this.items[i].length ;
		}
		// Menu columns -> rows
		for ( var i = 0 ; i < linenb ; i++ ) {
			var row = this.table.insertRow(this.table.rows.length) ;
			// Menu items -> cells
			for ( var j = 0 ; j < this.items.length ; j++ ) {
				var cell = row.insertCell(row.cells.length) ;
				if ( j < this.items[j].length ) { // For columns not having max number of lines
					var item = this.items[j][i] ;
					// Cell content
					if ( typeof item.text != 'string' ) { // No content : display an horizontal rule
						cell.appendChild(doc.createElement('hr')) ;
						cell.classList.add('line') ;
					} else {
						// Checkbox
						var check = document.createElement('input') ;
						check.type = 'checkbox' ;
						if ( item.checked == null )
							check.style.visibility = 'hidden' ;
						else
							check.checked = item.checked ;
						cell.appendChild(check) ;
						// Text
						cell.appendChild(doc.createTextNode(item.text)) ;
						cell.classList.add('text') ;
					}
					if ( item.title )
						cell.title = item.title ;
					// Links
					cell.item = item ;
					cell.menu = menu ;
					menu.parentMenu = parentMenu ;
					// Click / hover actions depending on item action
					cell.addEventListener('mouseover', function(ev) { // When mouse overs any element of a menu, close all its submenus
						if ( ! this.menu )
							log(''+this) ;
						else {
							var menu = this.menu ;
							while ( menu.opened != null ) {
								menu = menu.opened ;
								menu.stop() ;
							}
						}
					}, false) ;
					for ( var k = 0 ; k < item.buttons.length ; k++ ) {
						var but = create_button(item.buttons[k].text) ;
						but.item = item.buttons[k] ;
						if ( ! isf(but.item.callback) )
							but.disabled = true ;
						else {
							but.addEventListener('mousedown', function(ev) {
								ev.stopPropagation() ;
								ev.target.item.callback(ev, menu.target, ev.target.item.param) ;
								draw() ; // Not called by general click manager like for any other event because of stoppropagation
							}, false) ;
						}
						cell.appendChild(create_text(' ')) ;
						cell.appendChild(but)
					}
					switch ( typeof item.action ) {
						case 'function' :
							cell.classList.add('active') ;
							cell.args = item.args ;
							cell.addEventListener('mousedown', menu.activate, false) ;
							check.addEventListener('mousedown', menu.activate, false) ;
							break ;
						case 'object' : // Submenu
							if ( ( item.action != null ) && ( item.action.items.length > 0 ) ) { // http://brucejohnson.ca/SpecialCharacters.html
								cell.classList.add('active') ;
								cell.classList.add('submenu') ;
								// Don't propagate this mousedown to document, it's binded on close menu
								cell.addEventListener('mousedown', function(ev){ev.stopPropagation();}, false) ;
								cell.addEventListener('mouseover', function(ev) { // Menu containing a submenu hover
									this.menu.opened = this.item.action.start(ev, this.menu) ;
									return eventStop(ev) ;
								}, false) ;
								break ;
							}
						default : 
							cell.classList.add('inactive') ;
							// Continue ?
					}
					// Mouseover image zoom
					if ( item.moimg ) {
						cell.mouimg = document.getElementById('zoom').src ; // Preparing mouseOut
						cell.moimg = item.moimg ;
						cell.addEventListener('mouseover', function(ev) {
							var t = ev.target ;
							while ( ! iso(t.moimg) && ( t.parentNode != null ) )
								t = t.parentNode ;
							game.image_cache.load(clone(t.moimg), function(img, card) {
								var zoom = document.getElementById('zoom') ;
								ev.target.mouimg = zoom.src
								zoom.src = img.src ;
							}, function(card, url) {
							}, this) ;
						}, false) ;
						cell.addEventListener('mouseout', function(ev) {
							document.getElementById('zoom').src = ev.target.mouimg ;
						}, false) ;
					}
				}
			}
		}
		this.table.classList.add('menu') ;
		this.table.style.left = ev.pageX + 'px' ;
		this.table.style.top = ev.pageY + 'px' ;
		// Under FF-win, on right mouseup on a card, contextmenu appears later and raise from this.ul launched by a mouseup event
		// this.ul should have the same behaviour than card that should raise this menu
		if ( ev.target.thing )
			if ( ( ev.target.thing.type == 'card' ) || ( ev.target.thing.type == 'token' ) )
				this.table.addEventListener('contextmenu', eventStop, false) ;
		// We have to add ul document to know its width & height, then compare them to window, and decrease top &| left to make menu enter in window
		var winwidth = win.innerWidth - 25 ; // Scrollbar is counted in innerWidth ...
		var winheight = win.innerHeight - 5 ;
		if ( win.scrollMaxX ) // Opera doesn't know that, nor am i
			winwidth += win.scrollMaxX ;
		if ( win.scrollMaxY )
			winheight += win.scrollMaxY ;
		doc.body.appendChild(this.table) ;
		// Place on parent's menu right
		if ( parentMenu != null ) {
			this.table.style.left = ( ev.target.menu.table.offsetLeft + ev.target.menu.table.clientWidth ) + 'px' ;
			this.table.style.top = ( ev.target.menu.table.offsetTop + ev.target.offsetTop ) + 'px' ;
		}
		// Correct position if table isn't totaly in screen
		if ( this.table.offsetLeft + this.table.offsetWidth > winwidth )
			this.table.style.left = ( winwidth - this.table.offsetWidth ) + 'px' ;
		if ( this.table.offsetTop + this.table.offsetHeight > winheight )
			this.table.style.top = ( winheight - this.table.offsetHeight ) + 'px' ;
		// Manage submenu auto-close
		doc.addEventListener('mousedown', menu.clean, false) ;
		eventStop(ev) ;
		return this ;
	}
	this.activate = function(ev) {
		ev.stopPropagation() ;
		var target = menu.target ;
		var item = null ;
		var t = ev.target ;
		while ( iso(t.parentNode) && ( item == null ) ) {
			if ( iso(t.item) )
				item = t.item ;
			else
				t = t.parentNode ;
		} // Here 't' contains the table cell
		if ( item == null ) {
			log('Unable to find item in clicked element ('+ev.target+')\'s ancestors') ;
			return false ;
		}
		if ( item.override_target )
			target = item.override_target ;
		// Apply action
		if ( ! isn(target.length) )
			item.action.apply(target, t.args) ;
		else // Used for menu actions on selection that are not managed by "Selection", TODO remove when all will be managed
			for ( var i = 0 ; i < target.length ; i++ )
				item.action.apply(target[i], t.args) ;
		// Close current menu and all of its parents
		var m = t.menu ;
		while ( m != null ) {
			m.stop() ;
			m = m.parentMenu ;
		}
		//return eventStop(ev) ;
	}
	this.stop = function() {
		if ( this.table.parentNode != null )
			this.table.parentNode.removeChild(this.table) ;
	}
	this.clean = function(ev) {
		ev.target.ownerDocument.removeEventListener('mousedown', menu.clean, false) ;
		menu.stop() ;
		menu_clean(this) ;
	}
}
function menu_clean(menu) {
	delete menu ;
}
function menu_merge(menu, name, submenu) { // Adds to 'menu' a 'submenu' if it has multiple item, otherwise, just add the only item
	if ( ( submenu.items.length > 0 ) && ( submenu.items[0].length == 1 ) ) {
		var mi = submenu.items[0][0] ;
		var l = menu.addline(name+' : '+mi.text, mi.action) ;
		l.args = mi.args ;
		if ( isb(mi.checked) )
			l.checked = mi.checked ;
		if ( iso(mi.moimg) )
			l.moimg = mi.moimg ;
		if ( iso(mi.buttons) )
			l.buttons = mi.buttons ;
	} else
		var l = menu.addline(name, submenu) ;
	return l ;
}
