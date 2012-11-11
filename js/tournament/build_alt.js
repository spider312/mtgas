// === [ Initialisations ] =====================================================
function init() {
	// Initialisations
	game = new Object() ;
	game.image_cache = new image_cache() ;
	zoom = document.getElementById('zoom') ;
	zoomed = document.getElementById('zoomed') ;
	transformed = document.getElementById('transformed') ;
	cardstats = document.getElementById('cardstats') ;
	ready = document.getElementById('ready') ;
	build_canvas = document.getElementById('build_canvas') ;
	build_div = document.getElementById('build_div') ;
	build_canvas.width = build_div.clientWidth + 1  ;
	build_canvas.height = build_div.clientHeight - 25 ;
	build_canvas.addEventListener('mousemove', mousemove, false) ;
	build_canvas.addEventListener('mousedown', mousedown, false) ;
	build_canvas.addEventListener('mouseup', mouseup, false) ;
	//build_canvas.addEventListener('mouseout', mouseup, false) ; // Avoid "unclosed DND" by simulate a mouseup on leave
	build_canvas.addEventListener('contextmenu', contextmenu, false) ;
	lands_div = document.getElementById('lands') ;
	context = build_canvas.getContext('2d') ;
	saved = false ;
	tournament = null ;
	ajax_error_management() ;
	var separation = {'position': Math.floor(build_canvas.height/2), 'height': 5, 'hovered': false, 'moving': false}
	separation.offset = ( separation.height + 1 ) / 2 + 1 ;
	poolcards = {'main': new Pool(), 'side': new Pool(), 'separation': separation} ;
}
function start_standalone(deckname, deckcontent) {
	init() ;
	start() ;
	document.getElementById('save').addEventListener('click', function(ev) {
		deckname = prompt('Deck name', deckname) ;
		if ( deckname != null )
			deck_set(deckname, '// Deck file for Magic Workstation created with mogg.fr\n// NAME : '+deckname+'\n'+obj2deck(clone_deck(poolcards))) ;
	}, false) ;
	$.post('json/deck.php', {'deck': deckcontent}, recieve, 'json') ;
}
function clone_deck(poolcards) { // Returns a copy of a deck
	var localpool = {} ; // Local copy of the pool that will be added lands
	// If we clone poolcards, then localpool.main is the same object instance than poolcards.main, so it's not local
	localpool.main = clone(poolcards.main) ;
	localpool.side = clone(poolcards.side) ;
	for ( var i in lands ) {
		var card = lands[i] ;
		for ( var j = 0 ; j < land_nb('md'+card.name) ; j++ )
			localpool.main.push(card) ;
		for ( var j = 0 ; j < land_nb('sb'+card.name) ; j++ )
			localpool.side.push(card) ;
	}
	return localpool ;
}
function start_tournament(id) { // Start all that is only related to current tournament
	init() ;
	player_id = $.cookie(session_id) ;
	// Events
	ready.addEventListener('change', function(ev) { // On click
		if ( ( ev.target.checked ) && ( ! saved || modified ) ) { // If checking, verify deck hasn't been modified since last save
			ev.target.checked = false ;
			alert('You have to save a valid deck before beeing marked as ready') ;
			return eventStop(ev) ;
		}
		$.getJSON('json/ready.php', {'id': id, 'ready': ev.target.checked+0}) ;
	}, false) ;
	start() ;
		// Button "save"
	document.getElementById('save').addEventListener('click', function(ev) {
		localpool = {'main': [], 'side': []} ; // Work on copies
		for ( var i = 0 ; i < poolcards.main.cards.length ; i++)
			localpool.main.push(poolcards.main.cards[i].data) ;
		for ( var i = 0 ; i < poolcards.side.cards.length ; i++)
			localpool.side.push(poolcards.side.cards[i].data) ;
		for ( var i in lands ) // Add lands
			for ( var j = 0 ; j < parseInt(lands[i].input.value) ; j++ )
				localpool.main.push(lands[i].data) ;
		localpool.side.sort(color_sort) ; // In order side to be sorted by color in limited
		if ( localpool.main.length < 40 ) {
			if ( ! confirm('You have only '+localpool.main.length+' cards in your deck, you need at least 40, save anyway ?') )
				return eventStop(ev) ;
		}
		ev.target.setAttribute('disabled', 'true') ; // Don't send save while previous sent saved isn't recieved
		saved = false ;
		$.post('json/deck_update.php', {'id': id, 'deck': JSON.stringify(localpool)}, function(res) { // Deck content is too heavy for GET
			document.getElementById('save').removeAttribute('disabled') ; // Allow back to save
			switch ( typeof res ) { // Dunno why, sometimes result is JSON parsed, sometimes not
				case 'object' :
					var data = res ;
					break ;
				case 'string' :
					try {
						var data = JSON.parse(res) ;
					} catch (e) {
						alert(e+' : \n'+res);
						return null ;
					}
					break ;
				default :
					alert('Unrecognized type for result of deck update : '+typeof res) ;
					return false ;
			}
			if ( data.msg ) {
				alert(data.msg) ;
				return false ;
			}
			var msg = 'unitialized' ;
			switch ( data.nb ) {
				case 0 : 
					alert('Deck NOT saved for current event (maybe unmodified ?)') ;
					saved = true ;
					modified = false ;
					break ;
				case 1 : 
					alert('Deck saved for current event') ;
					saved = true ;
					modified = false ;
					break ;
				default :
					alert('Unmanaged modifications count : '+data.nb) ;
			}
		}, 'json') ;
	}, false) ;
	// Deck mw -> json (after creating lands because they will be filtered)
	$.getJSON('json/deck.php', {'id': id}, function(obj) {
		recieve(obj) ;
		timer(id) ;
	}) ;
	tournament_log = document.getElementById('log_ul') ;
	players_ul = document.getElementById('players_ul') ;
	document.getElementById('chat').addEventListener('submit', function(ev) {
		$.getJSON(ev.target.action, {'id': id, 'msg': ev.target.msg.value}, function(data) {
			if ( data.nb != 1 )
				alert(data.nb+' affected rows') ;
			else
				ev.target.msg.value = '' ;
		}) ;
		ev.preventDefault() ;
	}, false)
	document.getElementById('tournament').addEventListener('mouseover', function(ev) {
		ev.target.classList.remove('highlight') ;
	}, false) ;
	loglength = 0 ;
}
function recieve(obj) { // Recieving a deck from server (curent player's one during tournament, parsed one during off-tournament editing) 
	poolcards.side.import(obj.side) ;
	poolcards.main.import(obj.main) ;
	deck_stats_cc(poolcards.main.cards) ;
	disp_side() ;
}
function color_sort(card1, card2) {
	var i1 = manacolors.indexOf(card1.attrs.color) ;
	var i2 = manacolors.indexOf(card2.attrs.color) ;
	if ( i1 < 0 ) i1 = 6 ; // Multicolor at the end
	if ( i2 < 0 ) i2 = 6 ;
	return i1 - i2 ;
}
function alpha_sort(card1, card2) {
	if ( card1.name == card2.name )
		return 0 ;
	if ( card1.name > card2.name )
		return 1 ;
	return -1 ;
}
function score_sort(card1, card2) {
	if ( card1.stats.rank == card2.stats.rank )
		return 0 ;
	if ( card1.stats.rank > card2.stats.rank )
		return 1 ;
	return -1 ;
}

// === [ Timer ] ==============================================================
function timer(id) {
	$.getJSON('json/tournament.php', {'id': id, 'firsttime': true}, function(data) { // Get time left
		tournament = data ;
		if ( data.status != 4 ) // If tournament isn't in "drafting" status, go back to tournament index (that will normally redirect to build)
			window.location.replace('index.php?id='+id) ;
		else {
			document.getElementById('timeleft').value = time_disp(parseInt(data.timeleft)) ;
			window.setTimeout(timer, sealed_timer, id) ; // Refresh in 30 secs
			if ( iso(data.log) && ( data.log.length > loglength ) ) {
				if ( tournament_log.children.length != 0 ) // Some messages already recieved
					document.getElementById('tournament').classList.add('highlight') ;
				loglength = data.log.length ;
				node_empty(tournament_log) ;
				while ( data.log.length > 0 ) {
					line = data.log.shift() ;
					last_id = parseInt(line.id) ;
					pid = line.sender ;
					if ( pid == '' )
						nick = 'Server' ;
					else {
						nick = pid ;
						for ( var j in data.players )
							if ( data.players[j].player_id == pid )
								nick = data.players[j].nick ;
					}
					var msg = tournament_log_message(line, nick) ;
					tournament_log.appendChild(create_li((new Date(line.timestamp.replace(' ', 'T'))).toLocaleTimeString()+' '+msg)) ;
				}
			}
			if ( iso(data.players)) {
				node_empty(players_ul) ;
				for ( var i = 0 ; i < data.players.length ; i++ ) {
					var li = create_li(null) ;
					var cb = create_checkbox('', data.players[i].ready != '0') ;
					cb.disabled = true ;
					li.appendChild(cb) ;
					li.appendChild(document.createTextNode(data.players[i].nick)) ;
					players_ul.appendChild(li) ;
				}
			}
		}
		draw() ;
	}) ;
}
// === [ Drawing ] ============================================================
function draw() {
	context.clearRect(0, 0, build_canvas.width, build_canvas.height) ;
	// Zones
	poolcards.side.draw(context) ;
	poolcards.main.draw(context) ;
	// Separation
	if ( poolcards.separation.moving )
		context.strokeStyle = 'red' ;
	else if ( poolcards.separation.hovered )
		context.strokeStyle = 'white' ;
	else
		context.strokeStyle = 'black' ;
	context.lineWidth = poolcards.separation.height ;
	context.lineCap = 'round' ;
	var margin = 50 ;
	context.beginPath();
	context.moveTo(margin+.5, poolcards.separation.position+.5) ;
	context.lineTo(build_canvas.width+.5 - margin, poolcards.separation.position+.5) ;
	context.stroke() ;
}
// === [ Events ] =============================================================
function mousemove(ev) {
	if ( poolcards.separation.moving ) { // Moving separation
		poolcards.separation.position = ev.layerY ;
		poolcards.main.refresh() ;
		poolcards.side.refresh() ;
		draw() ;
	} else {
		// Hovering separation
		var sh = poolcards.separation.hovered ;
		poolcards.separation.hovered = ( ( ev.layerY > poolcards.separation.position - poolcards.separation.offset ) && ( ev.layerY < poolcards.separation.position + poolcards.separation.offset ) ) ;
		var redraw = ( poolcards.separation.hovered != sh ) ; // its value changed
		if ( redraw ) {
			if ( poolcards.separation.hovered ) { // Hover separation
				build_canvas.style.cursor = 'row-resize' ;
			} else
				build_canvas.style.cursor = '' ;
			draw() ;
		}
		// Moving over a pool, transmit
		if ( ev.layerY > poolcards.separation.position )
			var pool = poolcards.main ;
		else
			var pool = poolcards.side ;
		pool.mousemove(ev) ;
	}
}
function mousedown(ev) {
	if ( poolcards.separation.hovered ) { // Begin separation moving
		poolcards.separation.moving = true ;
		draw() ;
	} else { // Clicking over a pool, transmit
		if ( ev.layerY > poolcards.separation.position )
			var pool = poolcards.main ;
		else
			var pool = poolcards.side ;
		pool.mousedown(ev) ;
	}
}
function mouseup(ev) {
	if ( poolcards.separation.moving ) { // End separation moving
		poolcards.separation.moving = false ;
		draw() ;
	} else { // Clicking over a pool, transmit
		if ( ev.layerY > poolcards.separation.position )
			var pool = poolcards.main ;
		else
			var pool = poolcards.side ;
		pool.mouseup(ev) ;
	}
}
function contextmenu(ev) {
	if ( poolcards.separation.hovered ) {
		var menu = new menu_init(poolcards.separation) ;
		menu.addline('Separation (no action)') ;
		return menu.start(ev) ;
	} else { // Clicking over a pool, transmit
		if ( ev.layerY > poolcards.separation.position )
			var pool = poolcards.main ;
		else
			var pool = poolcards.side ;
		pool.contextmenu(ev) ;
	}
}
// === [ Clases ] =============================================================
function Pool() {
	// Init
	this.cards = [] ;
	this.cols = [] ;
	this.over = null ;
	this.colsort = 'cost' ;
	this.linsort = 'alpha' ;
	// Accessors
	this.toString = function() {
		if ( this == poolcards.side )
			return 'Sideboard' ;
		if ( this == poolcards.main )
			return 'Main deck' ;
		return 'Unknown zone' ;
	}
	this.import = function(obj) {
		for ( var i = 0 ; i < obj.length ; i++ ) {
			var card = obj[i] ;
			// Filter basic lands
			if ( iso(card.attrs.supertypes) && (card.attrs.supertypes.indexOf('basic') > -1 ) ) {
				if ( this == poolcards.main ) // Manage maindeck ones
					for ( var j = 0 ; j < lands.length ; j++ ) // By finding the corresponding land and increase its value
						if ( card.name == lands[j].data.name )
							lands[j].input.value++ ;
			} else
				this.add(new Card(this, obj[i])) ;
		}
		//this.refresh() ;
	}
	this.add = function(card) {
		this.cards.push(card) ;
	}
	// Events
	this.mousemove = function(ev) {
		var over = this.over ; 
		this.over = this.card_under(ev.layerX, ev.layerY-this.zoney) ;
		if ( over != this.over ) { // Changed
			if ( over != null ) // From a card
				over.mouseout(ev) ; 
			if ( this.over != null ) // To a card
				this.over.mouseover(ev) ;
		}
	}
	this.mousedown = function(ev) {
	}
	this.mouseup = function(ev) {
		if ( this.over != null ) // Mouseup on a card
			this.over.mouseup(ev) ;
	}
	this.contextmenu = function(ev) {
		if ( this.over != null )
			return this.over.contextmenu(ev) ;
		else {
			var menu = new menu_init(this) ;
			menu.addline(this.toString()) ;
			menu.addline() ;
			// Columns
			var colmenu = new menu_init(this) ;
			var mfunc = null ;
			if ( this.colsort != 'cost' )
				mfunc = function() { this.setsort('cost') ; } ;
			colmenu.addline('Converted cost', mfunc) ;
			mfunc = null ;			
			if ( this.colsort != 'color' )
				mfunc = function() { this.setsort('color') ; } ;
			colmenu.addline('Color', mfunc) ;
			mfunc = null ;
			if ( this.colsort != 'type' )
				mfunc = function() { this.setsort('type') ; } ;
			colmenu.addline('Type', mfunc) ;
			menu.addline('Columns sorting', colmenu) ;
			// Lines
			var linmenu = new menu_init(this) ;
			var mfunc = null ;
			if ( this.linsort != 'alpha' )
				mfunc = function() { this.setlinsort('alpha') ; } ;
			linmenu.addline('Alphabetically', mfunc) ;
			mfunc = null ;			
			if ( this.linsort != 'rank' )
				mfunc = function() { this.setlinsort('rank') ; } ;
			linmenu.addline('Score', mfunc) ;
			menu.addline('Line sorting', linmenu) ;
			// Clear
			if ( this == poolcards.main )
				menu.addline('Clear', function(pool) {
					for ( var i = 0 ; i < lands.length ; i++ ) 
						lands[i].input.value = 0 ;
					while ( pool.cards.length > 0 )
						pool.cards[0].switch() ;
					deck_stats_cc(poolcards.main.cards) ;
				}, this) ;
			// Start menu
			return menu.start(ev) ;
		}
	}
	this.card_under = function(x, y) {
		var i = Math.floor(x / this.cardw) ;
		if ( i >= this.cols.length )
			return null ;
		if ( typeof this.cols[i] == 'undefined' )
			return null ;
		if ( this.cols[i].length == 0 )
			return null ;
		var j = Math.floor(y / this.cols[i][0].h) ; // First card of that column's height, as each column has its own height
		if ( ( this.cols.length > i ) && ( this.cols[i].length > j ) )
			return this.cols[i][j] ;
		return null ;
	}
	// Displaying
	this.refresh = function() { // Compute all data for drawing
		// Sort cards by line sorting
		var zone = this ; // In order to be accessible in "array.sort"
		this.cards.sort(function(card1, card2) {
			var b = color_sort(card1.data, card2.data) ; // First sort by color
			if ( b != 0 )
				return b ;
			switch ( zone.linsort ) {
				case 'alpha' :
					return alpha_sort(card1.data, card2.data) ;
					break ;
				case 'rank' :
					return score_sort(card1.data, card2.data) ;
					break ;
				default:
					alert('Unknown sorting : '+zone.linsort) ;
			}
		}) ;
		// Divide cards into columns
		this.cols = this.get_cards() ;
		this.colnb = max(poolcards.main.cols.length, poolcards.side.cols.length) ;
		// "zone" (pool) dimensions
		this.zoney = 0 ;
		this.zoneh = poolcards.separation.position ;
		if ( this == poolcards.main ) {
			this.zoney = this.zoneh + poolcards.separation.offset ;
			this.zoneh = build_canvas.height - this.zoneh ;
		}
		this.zoneh -= poolcards.separation.offset ;
		// Percentage of position for finding top frame on original image
		this.xoff = .025 ;
		this.yoff = .025 ;
		this.hoff = .077 ;
		// Card displayer (only top frame) dimensions
		this.cardw = Math.floor(build_canvas.width / this.colnb) ;
		if ( this.cardw > 250 )
			this.cardw = 250 ;
		var cardh = this.cardw / cardwidth * cardheight ;
		this.cardh = Math.floor(cardh*this.hoff)+5 ;
		// Inform cards about their new coordinates
		for ( var i = 0 ; i < this.cols.length ; i++ ) { // Each column
			var col = this.cols[i] ;
			// Limit height to "zone" height
			var h = this.cardh ;
			if ( this.cardh*col.length > this.zoneh )
				h = Math.floor(this.zoneh / col.length) ;
			for ( var j = 0 ; j < col.length ; j++ ) {
				var card = col[j] ; 
				card.x = Math.floor(i*this.cardw) ;
				card.y = Math.floor(this.zoney+j*h) ;
				card.w = Math.floor(this.cardw) ;
				card.h = h ;
			}
		}
	}
	this.draw = function(context) {
		for ( var i = 0 ; i < this.cols.length ; i++ ) { // Each column
			var col = this.cols[i] ;
			for ( var j = 0 ; j < col.length ; j++ ) {
				var card = col[j] ;
				card.draw(context, card.x, card.y, card.w, card.h, this.xoff, this.yoff, this.hoff) ;
			}
		}
	}
	this.get_cards = function() {
		var cards = this.cards.concat() ;
		var result = [] ;
		for ( var i = 0 ; i < cards.length ; i++ ) {
			var card = cards[i] ;
			if ( this == poolcards.side ) { // Side filtered by top document selectors 
				// Filter by rarity
				var r = card.data.rarity ; // modify this rarity will change border color
				if ( r == 'M' ) // Consider mythics as rares for selector
					r = 'R' ;
				if ( ( r != 'S' ) && ( ! active_rarity[r] ) )
					continue ;
				// Filter by color
					// Count checked colors
				var nbc = 0 ;
				var color = '' ;
				for ( var j in manacolors )
					if ( active_color[manacolors[j]] ) { // Checked
						if ( ++nbc == 1 )
							color = manacolors[j] ;
					}
					// Only one : display golds/hybrids if any of its colors is checked one
				if ( nbc == 1 ) {
					if ( card.attrs.color.indexOf(color) == -1 )
						continue ;
				} else {
					// More than one : display golds/hybrids only if all of its colors are checked
					var shall_continue = false ; // Indicate if we should go next card
					for ( var j in manacolors ) { // Foreach colors
						var color = manacolors[j] ;
						if ( ! active_color[color] ) // Unchecked
							if ( card.attrs.color.indexOf(color) > -1 ) { // Current card has current color
								shall_continue = true ; // After the color loop, continue card loop
								break ; // No need to continue this for
							}
					}
					if ( shall_continue )
						continue ;
				}
			}
			switch ( this.colsort ) {
				case 'color' :
					var colsort = card.colorscore()
					break ;
				case 'type' :
					var colsort = card.typescore()
					break ;
				default : 
					var colsort = card.data.attrs.converted_cost
			}
			if ( result[colsort] )
				result[colsort].push(card) ;
			else
				result[colsort] = [card] ;
			// fill array with null values
			while ( ( colsort > 0 ) && ! iso(result[colsort-1]) )
				result[--colsort] = [] ;
		}
		return result ;
	}
	this.setsort = function(sorting) {
		this.colsort = sorting ;
		this.refresh() ;
		poolcards.side.refresh() ;
		poolcards.main.refresh() ;
		draw() ;
	}
	this.setlinsort = function(sorting) {
		this.linsort = sorting ;
		this.refresh() ;
		poolcards.side.refresh() ;
		poolcards.main.refresh() ;
		draw() ;
	}

}
function Card(pool, card) {
	// Init
	this.pool = pool ;
	this.data = card ;
	this.attrs = card.attrs ; // Required for stats
	// Images
	this.img = null ;
	game.image_cache.load(card_images(card_image_url(card.ext, card.name, card.attrs)), function(img, card) {
		card.img = img ;
		draw() ;
	}, function(tag, url) {
		tag.appendChild(document.createTextNode(div.firstChild.name)) ;
	}, this) ;
		// Transformed image
	this.trimg = null ;
	if ( iso(card.attrs.transformed_attrs) )
		game.image_cache.load(card_images(card_image_url(card.ext, card.attrs.transformed_attrs.name, card.attrs)), function(img, card) {
			card.trimg = img ;
		}, function(card, url) {
			alert('Unable to load image '+url) ;
		}, this) ;
	// Methods
		//Accessors
	this.toString = function() {
		return this.data.name ;
	}
	this.colorscore = function() {
		for ( var i = 0 ; i < manacolors.length ; i++ )
			if ( this.data.attrs.color == manacolors[i] )
				return i ; // Returns index of card's color in global array of colors
		return 6 ; // Defaults to "multiple" 
	}
	this.typescore = function() {
		var result = 3 ; // Defaults to "other"
		if ( this.data.attrs.types.indexOf('land') > -1 )
			result = 0 ;
		else if ( this.data.attrs.types.indexOf('creature') > -1 )
			result = 1 ;
		else if ( ( this.data.attrs.types.indexOf('artifact') > -1 ) || ( this.data.attrs.types.indexOf('enchantment') > -1 )  || ( this.data.attrs.types.indexOf('planeswalker') > -1 ) )
			result = 2 ;
		return result ;
	}
		// Actions
	this.info = function() {
		window.open('http://magiccards.info/query?q=!'+this+'&v=card&s=cname') ;
	}
	this.switch = function(ev) {
		var dest = null ;
		if ( this.pool == poolcards.side )
			dest = poolcards.main ;
		if ( this.pool == poolcards.main )
			dest = poolcards.side ;
		if ( dest == null )
			return false ;
		var i = this.pool.cards.indexOf(this) ;
		if ( i < 0 )
			alert('did not find card in its pool '+i) ;
		else {
			var pool = this.pool ; 
			this.pool.cards.splice(i, 1) ;
			dest.cards.push(this) ;
			this.pool = dest ;
			disp_side() ;
			if ( iso(ev) ) { // Won't have to when clearing
				pool.mousemove(ev) ; // Inform previous pool that card under mouse changed
				deck_stats_cc(poolcards.main.cards) ;
			}
			return true ;
		}
		return false ;
	}
	this.draw = function(context, x, y, w, h, xoff, yoff, hoff) {
		switch ( this.data.rarity ) {
			case 'L' :
				context.fillStyle = 'sienna' ;
				break ;
			case 'C' :
				context.fillStyle = 'black' ;
				break ;
			case 'U' :
				context.fillStyle = 'lightgray' ;
				break ;
			case 'R' :
				context.fillStyle = 'GoldenRod' ;
				break ;
			case 'M' :
				context.fillStyle = 'OrangeRed' ;
				break ;
			case 'S' :
				context.fillStyle = 'purple' ;
				break ;
			default :
				context.fillStyle = 'lime' ;
		}
		context.fillRect(x, y, w, h) ;
		if ( ( this.img != null ) && ( w > 4 ) && ( h > 4 ) ) {
			var sx = this.img.width*xoff ;
			var sWidth = this.img.width*(1-2*xoff) ;
			var sy = this.img.height*yoff ;
			var sHeight = this.img.height*hoff ;
			context.drawImage(this.img, sx, sy, sWidth, sHeight, x+2, y+2, w-4, h-4) ;
		}
	}
	// Events
	this.mouseover = function(ev) {
		//build_canvas.title = this+' : '+this.x+', '+this.y+', '+this.w+', '+this.h ;
		build_canvas.style.cursor = 'pointer' ;
		if ( this.img != null ) {
			zoom.classList.add('disp') ;
			// Images (orig and transformed)
			zoomed.src = this.img.src ;
			zoomed.width = cardimagewidth ;
			if ( this.trimg != null ) {
				transformed.src = this.trimg.src ;
				transformed.width = cardimagewidth ;
				transformed.classList.add('disp') ;
				zoom.width = 2*cardimagewidth ;
			} else {
				transformed.classList.remove('disp') ;
				zoom.width = cardimagewidth ;
			}
			// Position (zoom div must stay inside canvas)
			var left = build_canvas.parentNode.offsetLeft+this.x+this.w + 1 ;
			if ( left + zoom.width > build_canvas.width )
				left -= zoom.width + this.w ;
			zoom.style.left = left+'px' ;
			var h = zoomed.offsetHeight ;
			var top = build_canvas.parentNode.offsetTop+Math.floor(this.y) + 1 ;
			if ( top + h > build_canvas.height )
				top -= h - Math.floor(this.h) ;
			zoom.style.top = top+'px' ;
			zoom.addEventListener('contextmenu', this.contextmenu, false) ;
			// Stats
			node_empty(cardstats) ;
			var card = this.data ;
			if ( iso(card.stats) ) {
				var ul = create_ul() ;
				ul.appendChild(create_li('Opened : '+card.stats.sealed_open)) ;
				ul.appendChild(create_li('Played : '+card.stats.sealed_play+' ('+disp_percent(card.stats.sealed_play_ratio)+')')) ;
				ul.appendChild(create_li('Scored : '+card.stats.sealed_score+' ('+disp_percent(card.stats.sealed_score_ratio)+')')) ;
				ul.appendChild(create_li('Scored / Played : '+disp_percent(card.stats.sealed_play_score_ratio))) ;
				ul.appendChild(create_li('Rank : '+card.stats.rank+' / '+card.stats.count)) ;
				ul.appendChild(create_li('Rank by : '+card.stats.order_by)) ;
				cardstats.appendChild(ul) ;
			}

		}
	}
	this.mouseout = function(ev) {
		zoom.classList.remove('disp') ;
		build_canvas.style.cursor = '' ;
	}
	this.mouseup = function(ev) {
		switch ( ev.button ) {
			case 0 : // Middle click
				this.switch(ev) ;
				break ;
			case 1 : // Middle click
				this.info() ;
				break ;
		}
	}
	this.contextmenu = function(ev) {
		var menu = new menu_init(this) ;
		menu.addline(this.toString()) ;
		menu.addline() ;
		if ( this.pool == poolcards.side )
			menu.addline('Add to deck (click)', this.switch, this) ;
		if ( this.pool == poolcards.main )
			menu.addline('Remove from deck (click)', this.switch, this) ;
		menu.addline('Informations (middle click)', this.info, this) ;
		return menu.start(ev) ;
	}
}
function Land(id, name) { // Returns visual representation of a land-card
	this.data = {'id': id, 'name': name, 'ext': 'UNH', 'color': '', 'rarity': 'L', 'attrs': {'color': ''}} ;
	var div = create_div() ;
	this.div = div ;
	this.div.card = this ;
	this.div.title = 'Maindeck '+this.data.name+', click to add, right click to remove one, middle click to remove all' ;
	if ( window.innerHeight < 800 )
		div.classList.add('sr') ; // Small resolutions
	// Events
	div.addEventListener('mouseup', function(ev) {
		switch ( ev.button ) {
			case 0 : 
				ev.target.firstChild.value++ ;
				break ;
			case 1 : 
				ev.target.firstChild.value = 0 ;
				break ;
			case 2 : 
				if ( ev.target.firstChild.value > 0 )
					ev.target.firstChild.value-- ;
				break ;
			default: 
				alert('Button '+ev.button+' unmanaged') ;
				return false ;
		}
		disp_side() ;
	}, false) ;
	div.addEventListener('contextmenu', function(ev) {
		ev.preventDefault() ;
	}, false) ;
	// Image loading
	this.img = null ;
	game.image_cache.load(card_images(card_image_url(this.data.ext, this.data.name, this.data.attrs)), function(img, land) {
		land.img = img ;
		land.div.style.backgroundImage = 'url('+img.src+')' ;
	}, function(land, url) {
		land.div.appendChild(document.createTextNode(div.firstChild.name)) ;
	}, this) ;
	// Input
	this.input = create_input(this.data.name, 0, this.data.name) ;
	this.input.size = 2 ;
	this.input.maxLength = 2 ;
	div.appendChild(this.input) ;
	lands_div.appendChild(this.div) ;
	this.input.addEventListener('change', function(ev) {
		disp_side() ;
	}, false) ;
}
// Compatibility
function log(txt) {}
function disp_side() {
		poolcards.side.refresh() ;
		poolcards.main.refresh() ;
		draw() ;
		resume = document.getElementById('resume') ;
		node_empty(resume) ;
		var nblands = 0 ;
		for ( var i = 0 ; i < lands.length ; i++ ) 
			nblands += parseInt(lands[i].input.value) ;
		resume.appendChild(create_text(poolcards.main.cards.length+' cards + '+nblands+' basic lands = '+(poolcards.main.cards.length+nblands)+' total cards'))
}
pool = null ;
