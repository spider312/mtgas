function start(id, pid) {
	table_side = document.getElementById('table_side') ;
	table_main = document.getElementById('table_main') ;
	land = document.getElementById('land') ;
	zoom = document.getElementById('zoom') ;
	ready = document.getElementById('ready') ;
	TournamentBuild.prototype = new TournamentLimited() ;
	Tournament.prototype = new TournamentBuild() ;
	PlayerBuild.prototype = new PlayerLimited() ;
	Player.prototype = new PlayerBuild() ;
	LogBuild.prototype = new LogLimited() ;
	Log.prototype = new LogBuild() ;
	game = {} ;
	game.image_cache = new image_cache() ;
	game.options = new Options() ;
	game.tournament = new Tournament(id) ;
	game.spectators = new Spectators(function(msg, span) { // Message display
		// Messages are managed via the "log" mechanism which triggers after spectator recieving
		//debug(msg) ;
	}, function(id) { // I'm a player allowing a spectator
		game.connection.send('{"type": "allow", "value": "'+id+'"}') ;
	}, function(player) { // I'm allowed spectator
		// Nothing appends on a build page when another player allows you
	}) ;
	var wsregistration = {'tournament': id} ;
	if ( pid != '' ) {
		game.tournament.follow = pid ;
		wsregistration.follow = pid ;
	}
	game.connection = new Connexion('build', function(data, ev) { // OnMessage
		switch ( data.type ) {
			case 'msg' :
				alert(data.msg) ;
				break ;
			case 'redirect' :
				window.location.replace('index.php?id='+id) ;
				break ;
			case 'running_tournament' :
			case 'ended_tournament' :
				game.tournament.recieve(data) ;
				break ;
			case 'deck' :
				if ( pid != '' )
					game.tournament.me = game.tournament.get_player(pid) ;
				if ( game.tournament.me != null ) {
					game.tournament.me.me = true ; // Spectator overrides "me"
					game.tournament.me.recieve({'deck_obj': data}) ;
				} else
					debug('player is null') ;
				break ;
			default : 
				debug('Unknown type '+data.type) ;
				debug(data) ;
		}
	}, function(ev) { // OnClose/OnConnect
	}, wsregistration) ;
	if ( pid == '' ) {
		var landbase = function(id, name, mana) {
			return {
				'id': id, 'name': name,
				'ext': 'UNH', 'rarity': 'L', 'ext_img': 'UNH',
				'attrs': {
					'manas': [], 'converted_cost': 0, 'color': 'X', 'color_index': 1,
					'types': ['land'], 'supertypes': ['basic'],
					'provide': [mana]
				},
				toString: function() { return this.name+"\n" ; } } ;
		}
		arr_base_land = [
			landbase(6871, 'Plains', 'W'),
			landbase(4621, 'Island', 'U'),
			landbase(9266, 'Swamp', 'B'),
			landbase(6020, 'Mountain', 'R'),
			landbase(3332, 'Forest', 'G')
		] ;
		// GUI events
		ready.addEventListener('change', function(ev) { // On click
			game.connection.send({"type": "ready", "ready": ev.target.checked}) ;
		}, false) ;
		generate_base_land() ;
		clear_button = document.getElementById('clear_button') ;
		if ( clear_button != null ) {
			clear_button.disabled = false ;
			clear_button.addEventListener('click', function(ev) {
				if ( ! confirm('Are you sure you want to clear your deck ?') )
					return true ;
				var cards = game.tournament.me.pool.main.cards ;
				for ( var i = cards.length-1 ; i >= 0 ; i-- )
					game.tournament.me.pool.toggle(cards[i], true) ;
				game.tournament.me.pool.display() ;
			}, false) ;
		}
	}
	// Recompute columns number on resize
	window.addEventListener('resize', function(ev) {
		pool = game.tournament.me.pool ;
		var cols = pool.maxcols
		pool.calc_maxcols()
		if ( cols != pool.maxcols )
			pool.display() ;
	},	false) ;
	// Checkbox to get stats about side instead of maindeck
	stats_side = document.getElementById('stats_side')
	stats_side.addEventListener('change', function(ev) {
		game.tournament.me.pool.stats() ;
	}, false) ;
	zoom.addEventListener('mouseenter', function(ev) {
		zoom.classList.add('hidden') ;
	}, false) ;
}
function TournamentBuild() {}
function PlayerBuild() {
	this.recieved = function(fields) {
		if ( this.me ) {
			if ( inarray('deck_obj', fields) ) {
				if ( this.pool == null )
					this.pool = new Pool(this) ;
				this.pool.recieve(this.deck_obj) ;
				this.pool.display() ;
			}
			if ( this.pool != null ) {
				if ( this.pool.main.cards.length < 40 )
					ready.disabled = true ;
				else
					ready.disabled = false ;
			}
		}
	}
	this.remove = function(card) {
		for ( var i = 0 ; i < this.deck_obj.main.length ; i++ )
			if ( this.deck_obj.main[i].name == card.name )
				this.deck_obj.main.splice(i, 1) ;
	}
	this.toggle = function(card, strfrom) {
		var from = this.deck_obj[strfrom] ;
		for ( var i = 0 ; i < from.length ; i++ )
			if ( from[i].occurence == card.card.occurence ) {
				var to = (from==this.deck_obj.side) ? this.deck_obj.main : this.deck_obj.side ;
				var spl = from.splice(i, 1) ;
				to.push(spl[0]) ;
				return true ;
			}
		debug(card.name+' not found in '+strfrom) ;
	}
	this.pool = null ;
}
function LogBuild() {}
// Build specific object ;
function Pool(player) {
	this.recieve = function(pool) {
		this.main.recieve(pool.main) ;
		this.side.recieve(pool.side) ;
	}
	this.display = function() {
		if ( this.smallres )
			document.body.classList.add('smallres') ;
		else
			document.body.classList.remove('smallres') ;
		this.main.display() ;
		this.side.display() ;
		this.stats() ;
	}
	this.stats = function() {
		var cards = this.main.cards ;
		if ( stats_side.checked )
			cards = this.side.filtered() ;
		this.stats_results = deck_stats_cc(cards) ; // [color, mana, cost, type, provide]
		var cards_number = document.getElementById('cards_number') ;
		node_empty(cards_number) ;
		if ( ! stats_side.checked ) {
			cards_number.appendChild(create_text(cards.length+' cards')) ;
			if ( !iso(this.stats_results[3]) )
				return false ;
			var lands = this.stats_results[3]['land'] ;
			if ( !isn(lands) )
				return false ;
			cards_number.appendChild(create_text(' ('+(cards.length-lands)+' active, '+lands+' land)')) ;
		}
	}
	// Basic lands in main
	this.add = function(card, nb) {
		game.connection.send({"type": "add", "cardname": card.name, "nb": nb}) ;
		for ( var j = 0 ; j < nb ; j++ ) {
			this.player.deck_obj.main.push(card) ;
			this.main.cards.push(new Card(card)) ;
		}
	}
	this.remove = function(card) {
		for ( var i = 0 ; i < this.main.cards.length ; i++ )
			if ( this.main.cards[i].name == card.name ) {
				this.toggle(this.main.cards[i]) ;
				return true ;
			}
		debug(card.name+' not found for removal') ;
		return false ;
	}
	this.toggle = function(card, multiple) {
		var i = this.main.cards.indexOf(card) ;
		if ( i < 0 ) {
			var i = this.side.cards.indexOf(card) ;
			if ( i < 0 ) {
				debug('Card '+card.name+' not in main nor side') ;
				return false ;
			} else
				var from = this.side ;
		} else
			var from = this.main ;
		var to = (from==this.side) ? this.main : this.side ;
		from.cards.splice(i, 1) ;
		if ( inarray('basic', card.card.attrs.supertypes) ) {
			game.connection.send({"type": "remove", "cardname": card.name}) ;
			this.player.deck_obj.main.splice(i, 1) ;
			this.player.remove(card) ;
		} else {
			to.cards.push(card) ;
			var strfrom = (from==this.side) ? 'side' : 'main' ;
			this.player.toggle(card, strfrom) ;
			// Send
			game.connection.send({"type": "toggle", "cardname": card.name, "from": strfrom}) ;
		}
		if ( ! multiple )
			this.display() ;
	}
	this.calc_maxcols = function() {
		// colwidth = imgwidth + 2 * borderwidth
		if ( this.smallres )
			var colwidth = 192 ;
		else
			var colwidth = 239 ;
		this.maxcols = Math.floor(table_main.parentNode.clientWidth/colwidth) ;
	}
	this.set_smallres = function(val) {
		this.smallres = val ;
		this.calc_maxcols() ;
	}
	this.player = player ;
	this.main = new Zone(this, table_main, 'Deck', 'converted_cost') ;
	this.side = new Zone(this, table_side, 'Sideboard', 'color_index') ;
	this.set_smallres(game.options.get('smallres')) ;
	this.limitcols = ! this.smallres ;
	this.stats_results = null ;
	// Selectors
	var selectors = document.getElementById('selectors') ;
	var func = function(zone) {
		return function(ev) {
			if ( zone.reset_color_filter() )
				zone.sort('color_index') ;
			else
				zone.sort('converted_cost') ;
		}
	}
	var input = create_select('sort', 'sort_side') ;
	for ( var i in this.side.fields )
		input.add(create_option(this.side.fields[i], i)) ;
	input.value = 'color_index' ;
	input.addEventListener('change', function(ev) {
		game.tournament.me.pool.side.sort(ev.target.value) ;
	}, false) ;
	selectors.appendChild(input) ;
	var input = create_button('All', func(this.side)) ;
	selectors.appendChild(input) ;
	for ( var i in this.side.color_filter ) {
		var input = create_checkbox(i, this.side.color_filter[i], 'check_'+i) ;
		var func = function(zone, input) {
			return function(ev) {
				zone.toggle_color_filter(input.name) ;
			}
		}
		input.addEventListener('click', func(this.side, input), false) ;
		var label = create_label(input.id, input) ;
		label.classList.add('selector') ;
		var manacolor = i ;
		if ( manacolor === 'X' ) manacolor = 'E' ;
		label.style.backgroundImage = 'url('+theme_image('ManaIcons/'+manacolor+'.png')[0]+')' ;
		var func = function(zone, input) {
			return function(ev) {
				zone.field = 'converted_cost' ;
				zone.set_color_filter(input.name) ;
			}
		}
		label.addEventListener('dblclick', func(this.side, input), false) ;
		selectors.appendChild(label) ;
	}
}
function Zone(pool, node, name, sort) {
	this.pool = pool ;
	this.node = node ;
	this.name = name ;
	this.cards = [] ;
	this.color_filter = {'X': true, 'W': true, 'U': true, 'B': true, 'R': true, 'G': true} ;
	this.field = sort ;
	this.fields = {
		'converted_cost': 'Converted cost',
		'color_index': 'Color',
		'types': 'Types',
		'rarity': 'Rarity'
	} ;
	this.fields_func = {
		'color_index': function(cols) {
			var names = ['Purple', 'Colorless', 'White', 'Blue', 'Black', 'Red', 'Green', 'Multicolor'] ;
			var result = [] ;
			for ( var i = 1 ; i < names.length ; i++ ) {
				if ( cols[i] ) {
					result[names[i]] = cols[i] ;
					delete cols[i] ;
				}
			}
			var lastcol = names[names.length-1] ;
			for ( var i in cols )
				if ( ! iso(result[lastcol]) )
					result[lastcol] = cols[i] ;
				else
					result[lastcol] = result[lastcol].concat(cols[i]) ;
			return result ;
		},
		'types': function(cols) {
			var types = ['planeswalker', 'creature', 'land', 'artifact', 'enchantment', 'sorcery', 'instant', 'tribal'] ;
			var result = [] ;
			for ( var i = 0 ; i < types.length ; i++ ) {
				if ( cols[types[i]] )
					result[types[i]] = cols[types[i]] ;
			}
			return result ;
		},
		'converted_cost': null,
		'rarity': function(cols) {
			var rarities = ['L', 'C', 'U', 'R', 'M', 'S'] ;
			var result = [] ;
			for ( var i = 0 ; i < rarities.length ; i++ ) {
				if ( cols[rarities[i]] )
					result[rarities[i]] = cols[rarities[i]] ;
			}
			return result ;
		}
	} ;
	var me = this ;
	// Node events
	this.node.parentNode.addEventListener('mouseover', function(ev) {
		zoom.classList.add('hidden') ; // Managed in zone in order not to trigger when going from a card
		/// to a card
	}, false) ;
	this.node.parentNode.addEventListener('mouseenter', function(ev) {
		if ( me.name == 'Sideboard' )
			div_main.classList.remove('highlight') ;
		else
			div_main.classList.add('highlight') ;
	}, false) ;
	this.node.addEventListener('mousedown', function(ev) { // Image selection on mousedown bug
		ev.preventDefault() ; // eventStop also forbids propagation to menu events
	}, false) ;
	this.node.addEventListener('mouseup', function(ev) {
		switch ( ev.button ) {
			case 1 :
				if ( ev.target.card )
					ev.target.card.info() ;
				break ;
		}
	}, false) ;
	this.node.addEventListener('click', function(ev) {
		if ( ( ! game.tournament.follow ) && ( ev.button == 0 ) && ( ev.target.card ) ) {
			ev.target.card.zoom_out(ev) ;
			me.pool.toggle(ev.target.card) ;
		}
	}, false) ;
	this.node.parentNode.addEventListener('contextmenu', function(ev) {
		me.menu(ev) ;
	}, false) ;
	// Methods
		// Filter
	this.set_filter = function(color, val) {
		this.color_filter[color] = val ;
		document.getElementById('check_'+color).checked = val ;
	}
	this.toggle_color_filter = function(color) {
		this.set_filter(color, ! this.color_filter[color]) ;
		this.display() ;
	}
	this.reset_color_filter = function() {
		var onefalse = false ;
		for ( var i in this.color_filter )
			if ( ! this.color_filter[i] ) {
				onefalse = true ;
				break ;
			}
		if ( onefalse ) {
			for ( var i in this.color_filter )
				this.set_filter(i, true) ;
			this.display() ;
			return true ;
		}
		this.set_color_filter('') ;
		return false ;
	}
	this.set_color_filter = function(color) {
		for ( var i in this.color_filter )
			this.set_filter(i, i == color) ;
		this.display() ;
	}
		// Sort
	this.sort = function(field) {
		this.field = field ;
		this.display() ;
		var sort_side = document.getElementById('sort_side') ;
		if ( sort_side != null )
			sort_side.value = field ;
	}
	this.filtered = function() {
		var result = [] ;
		for ( var i = 0 ; i < this.cards.length ; i++ )
			for ( var j in this.color_filter )
				if ( this.color_filter[j] && inarray(j, this.cards[i].attrs.color) ) {
					result.push(this.cards[i]) ;
					break ;
				}
		return result ;
	}
	this.sorted = function(field) { // Return an array of "columns" sorted by field
		var result = {} ;
		var cards = this.filtered()
		for ( var i = 0 ; i < cards.length ; i++ ) {
			var card = cards[i] ;
			switch ( field ) {
				case 'rarity': 
					var val = card.rarity ;
					break ;
				case 'types': 
					var val = card.attrs.types[0] ;
					break ;
				default :
					var val = card.attrs[field] ;
			}
			if ( ! result[val] )
				result[val] = [] ;
			result[val].push(card) ;
		}
		if ( isf(this.fields_func[field]) )
			result = this.fields_func[field](result) ;
		return result
	}
	// Display
	this.menu = function(ev) {
		var poolmenu = new menu_init(this) ;
		poolmenu.addline(this.name) ;
		poolmenu.addline('Limit columns', function(pool) {
			this.pool.limitcols = ! this.pool.limitcols ;
			this.display() ;
		}).checked = this.pool.limitcols ;
		poolmenu.addline('Small resolution', function(pool) {
			this.pool.set_smallres(! this.pool.smallres) ;
			this.pool.display() ;
		}).checked = this.pool.smallres ;
		var sortmenu = new menu_init(this) ;
		for ( var i in this.fields )
			sortmenu.addline(this.fields[i], this.sort, i).checked = (this.field==i) ;
		poolmenu.addline('Sort', sortmenu) ;
		var filtermenu = new menu_init(this) ;
		filtermenu.addline('Show all', this.reset_color_filter) ;
		filtermenu.addline('Colorless', this.toggle_color_filter, 'X').checked = this.color_filter['X'];
		filtermenu.addline('White', this.toggle_color_filter, 'W').checked = this.color_filter['W'] ;
		filtermenu.addline('Blue', this.toggle_color_filter, 'U').checked = this.color_filter['U'] ;
		filtermenu.addline('Black', this.toggle_color_filter, 'B').checked = this.color_filter['B'] ;
		filtermenu.addline('Red', this.toggle_color_filter, 'R').checked = this.color_filter['R'] ;
		filtermenu.addline('Green', this.toggle_color_filter, 'G').checked = this.color_filter['G'] ;
		poolmenu.addline('Filter', filtermenu) ;
		if ( ! iso(ev.target.card) )
			var menu = poolmenu ;
		else {
			var menu = new menu_init(this) ;
			menu.addline(ev.target.card.name) ;
			menu.addline('Include', function(card){
				this.pool.toggle(card) ;
			}, ev.target.card) ;
			menu.addline('Informations (MagicCards.Info)', function(card){
				card.info() ;
			}, ev.target.card) ;
			menu.addline() ;
			menu.addline(this.name, poolmenu) ;
		}
		menu.start(ev) ;
	}
	this.display = function() {
		node_empty(this.node) ;
		var cards = this.sorted(this.field) ;
		var th = create_tr(this.node) ;
		var tr = create_tr(this.node) ;
		var n = 0 ;
		for ( var i in cards ) {
			if ( ! this.pool.limitcols || ( n < this.pool.maxcols ) ) {
				var h = create_td(th, i) ;
				h.classList.add('th') ;
				var td = create_td(tr, '') ;
			} else
				h.appendChild(create_text('+')) ;
			var col = cards[i] ;
			for ( var j = 0 ; j < col.length ; j++ )
				td.appendChild(cards[i][j].div()) ;
			n++ ;
		}
		if ( stats_side.checked ) // If we stats side, we want stats refresh on each display
			this.pool.stats() ;
	}
		// Network
	this.recieve = function(cards) {
		this.cards = [] ;
		for ( var i = 0 ; i < cards.length ; i++ )
			this.cards.push(new Card(cards[i])) ;
	}
}
function Card(card) {
	this.toString = function() {
		var ext = this.card.ext ;
		if ( ext != this.card.ext_img )
			ext += '/'+this.card.ext_img ;
		var name = '['+ext+']'+this.card.name ;
		if ( isn(this.card.attrs.nb) )
			name += ' ('+this.card.attrs.nb+')' ;
		return name ;
	}
	this.div = function() {
		if ( this.node != null )
			var div = this.node ;
		else {
			var name = this.toString() ;
			var div = create_div(name) ;
			div.draggable = false ;
			div.title = name ;
			div.classList.add('card') ;
			div.classList.add(card.rarity) ;
			div.card = this ;
			div.transformed_url = '' ;
			if ( iso(card.attrs.split) )
				div.classList.add('split') ;
			// Image loading
			var urls = card_images(card_image_url(this.card.ext_img, this.card.name, this.card.attrs.nb)) ;
			game.image_cache.load(urls, function(img, div) {
				node_empty(div) ; // Erase name wrotten while loading
				var img = create_img(img.src) ;
				img.draggable = false ;
				img.card = div.card ;
				div.card.img = img ;
				div.appendChild(img) ;//.replace('\'', '\\\'')// Chromium & Opera don't like apostrophes
			}, function(div, url) {
				debug(url+' not found') ;
			}, div) ;
			// Transformed image loading
			if ( iso(card.attrs.transformed_attrs) ) {
				game.image_cache.load(card_images(card_image_url(card.ext_img, card.attrs.transformed_attrs.name, card.attrs.nb)), function(img, tag) {
					tag.transformed_url = img.src ;
				}, function(tag, url) {
					tag.appendChild(document.createTextNode(tag.card.attrs.transformed_attrs.name)) ;
				}, div) ;
			}
			// Events
			div.addEventListener('mouseover', this.zoom_in, false) ;
			div.addEventListener('mousemove', this.move_zoom, false) ;
			//div.addEventListener('mouseout', this.zoom_out, false) ; // Managed by zone
			this.node = div ;
		}
		return div ;
	}
	this.zoom_in = function(ev) {
		if ( ! iso(ev.target.card) || ! iso(ev.target.card.img) )
			return true ;
		var div = ev.target.card.node ;
		// Place zoom
		div.card.move_zoom(ev) ;
		// Load image
		var zoomed = zoom.firstElementChild ;
		var card = div.card.card ;
		zoomed.src = div.card.img.src ;
		// Prepare special cases
		var alt = zoomed.nextElementSibling ;
		if ( alt.src != '' )
			alt.src = '' ;
		// Split
		if ( iso(card.attrs.split) )
			zoom.classList.add('split') ;
		else
			zoom.classList.remove('split') ;
		// Flip
		if ( iso(card.attrs.flip_attrs) ) {
			alt.src = div.card.img.src ;
			alt.classList.add('flip') ;
		} else
			alt.classList.remove('flip') ;
		// Transformed
		if ( div.transformed_url != '' )
			alt.src = div.transformed_url ;
		// Needs to display a second image
		if ( ( alt.src != '' ) && ( alt.src != document.location.href ) )
			alt.classList.remove('hidden') ;
		else
			alt.classList.add('hidden') ;
		// Show zoom
		zoom.classList.remove('hidden') ;
		//this.target.card.move_zoom(ev) ;
		return eventStop(ev) ; // Zone shouldn't hide zoom
	}
	this.zoom_out = function(ev) {
		zoom.classList.add('hidden') ;
	}
	this.move_zoom = function(ev) {
		// Image is displayed on cursor's bottom right by default
		// If it would make it appear outside "inner" displayed window, display it on cursor's
		// left and/or top
		// never display zoom on top of cursor (it would interact with mouse* events) and make cards 
		// always readable
		var xoffset = 10 ;
		var x = ev.clientX + xoffset ; // cursor's right
		if ( x + zoom.clientWidth > window.innerWidth ) // Outside inner screen
			x = ev.clientX - zoom.clientWidth - xoffset ; // cursor's left
		zoom.style.left = max(x, 0)+'px' ;
		var yoffset = 10 ;
		var y = ev.clientY + yoffset ; // cursor's bottom
		if ( y + zoom.clientHeight > window.innerHeight ) { // Outside inner screen
			y = ev.clientY - zoom.clientHeight - yoffset ; // cursor's top
			zoom.classList.add('displaytop') ;
		} else
			zoom.classList.remove('displaytop') ;
		zoom.style.top = max(y, 0)+'px' ;
	}
	this.info = function() {
		window.open('http://magiccards.info/query?q=!'+this.name) ;
	}
	this.node = null ;
	this.card = card ;
	this.name = card.name ;
	this.attrs = card.attrs ;
	this.rarity = card.rarity ;
}
function generate_base_land() {
	base_lands = document.getElementById('base_lands') ;
	if ( base_lands == null )
		return false ;
	base_lands.disabled = false ;
	base_lands.addEventListener('click', function(ev) {
		base_lands.disabled = true ;
		stats.classList.add('highlight') ;
		div_main.classList.add('highlight') ;
		// Container
		var container = create_div() ;
		container.id = 'basic_lands' ;
		container.classList.add('section') ;
		// Hider
		var hider = create_div(container) ;
		hider.classList.add('hider') ;
		document.body.appendChild(hider) ;
		// Title
		container.appendChild(create_h(1,'Basic lands'))
		// Table
		var table = create_element('table') ;
		// Count lands and basic lands in deck
		var blindeck = {}
		for ( var i = 0 ; i < arr_base_land.length ; i++ )
			blindeck[arr_base_land[i].name] = 0 ;
		for ( var i = 0 ; i < game.tournament.me.pool.main.cards.length ; i++ ) {
			var card = game.tournament.me.pool.main.cards[i] ;
			if ( inarray('land', card.attrs.types) && inarray('basic', card.attrs.supertypes) )
				blindeck[card.name]++
		}
		container.appendChild(table) ;
		for ( var i = 0 ; i < arr_base_land.length ; i++ ) {
			var land = arr_base_land[i] ;
			var tr = table.insertRow(-1) ;
			// Image
			var td = tr.insertCell(-1) ;
			var land_div = create_div(land.name) ;
			td.appendChild(land_div)
			land_div.classList.add('card') ;
			land_div.classList.add('L') ;
			var urls = card_images(card_image_url(land.ext_img, land.name, land.attrs.nb)) ;
			game.image_cache.load(urls, function(img, div) {
				node_empty(div) ; // Erase name wrotten while loading
				img.draggable = false ;
				div.appendChild(img) ;
			}, function(div, url) {}, land_div) ;
			// Buttons & input
			var add = function(land, input) {
				return function(ev) {
					game.tournament.me.pool.add(land, 1) ;
					input.value = parseInt(input.value)+1 ;
					game.tournament.me.pool.display() ;
				}
			}
			var del = function(land, input) {
				return function(ev) {
					var val = parseInt(input.value) ;
					if ( val > 0 ) {
						game.tournament.me.pool.remove(land) ;
						input.value = val-1 ;
					}
				}
			}
			var input = create_input() ;
			land.input = input ;
			input.size = 2 ;
			input.value = blindeck[land.name] ;
			input.tabIndex = i+1 ;
			var td = tr.insertCell(-1) ;
			td.appendChild(create_button('-', del(land, input))) ;
			td.appendChild(input) ;
			td.appendChild(create_button('+', add(land, input))) ;
			if ( i == 0 )
				input.select() ;
			// Events on image
			land_div.addEventListener('contextmenu', del(land, input), false) ;
			land_div.addEventListener('click', add(land, input), false) ;
			land_div.addEventListener('mousedown', eventStop, false) ;
		}
		// Close button
		var button = create_button(create_img(theme_image('deckbuilder/button_cancel.png')[0]), function(ev) {
			var hider = document.getElementById('basic_lands').parentNode ;
			hider.parentNode.removeChild(hider) ;
			stats.classList.remove('highlight') ;
			base_lands.disabled = false ;
		}, 'Close') ;
		container.appendChild(button) ;
		// Center
		var style = container.style ;
		style.marginLeft = '-'+Math.ceil(container.clientWidth/2)+'px' ;
		style.marginTop = '-'+Math.ceil(container.clientHeight)+'px' ;
	}, false) ;
}
