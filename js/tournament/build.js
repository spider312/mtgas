function start_spectactor(id, tournament_name, player_name, deckcontent) {
	init() ;
	document.getElementById('save').addEventListener('click', function(ev) {
		deckname = prompt('Deck name', player_name+'@'+tournament_name) ;
		if ( name != null )
			deck_set(deckname, '// Deck file for Magic Workstation created with mogg.fr\n// NAME : '+deckname+'\n'+obj2deck(clone_deck(poolcards))) ;
	}, false) ;
	$.post('json/deck.php', {'deck': deckcontent}, function(obj) { // Get deck as JS object
		obj.side = obj.side.filter(filter_lands, 'sb') ;
		disp_side(obj.side, pool) ;
		obj.main = obj.main.filter(filter_lands, 'md') ;
		disp_side(obj.main, deck) ;
		poolcards = obj ;
		tournament_init(id) ;
		timer(id, true) ;
	}, 'json') ;
	// Spectactor specific
	ready.disabled = true ;
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
		// Button "save"
	document.getElementById('save').addEventListener('click', function(ev) {
		localpool = clone_deck(poolcards) ;
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
	$.getJSON('json/deck.php', {'id': id}, function(obj) { // Get deck as JS object
		obj.side = obj.side.filter(filter_lands, 'sb') ;
		obj.side.sort(alpha_sort) ; // In limited, sort pool cards alphabetically to regroup them
		obj.main = obj.main.filter(filter_lands, 'md') ;
		disp_side(obj.side, pool) ;
		disp_side(obj.main, deck) ;
		poolcards = obj ;
		tournament_init(id) ;
		timer(id, false) ;
	}) ;
}
function start_standalone(deckname, deckcontent) {
	init() ;
	document.getElementById('save').addEventListener('click', function(ev) {
		deckname = prompt('Deck name', deckname) ;
		if ( name != null )
			deck_set(deckname, '// Deck file for Magic Workstation created with mogg.fr\n// NAME : '+deckname+'\n'+obj2deck(clone_deck(poolcards))) ;
	}, false) ;
	$.post('json/deck.php', {'deck': deckcontent}, function(obj) { // Get deck as JS object
		obj.side = obj.side.filter(filter_lands, 'sb') ;
		disp_side(obj.side, pool) ;
		obj.main = obj.main.filter(filter_lands, 'md') ;
		disp_side(obj.main, deck) ;
		poolcards = obj ;
	}, 'json') ;
}
function init() {
	// Initialisations
	game = new Object() ;
	game.image_cache = new image_cache() ;
	pool = document.getElementById('pool') ;
	deck = document.getElementById('deck') ;
	land = document.getElementById('land') ;
	zoom = document.getElementById('zoom') ;
	zoomed = document.getElementById('zoomed') ;
	transformed = document.getElementById('transformed') ;
	ready = document.getElementById('ready') ;
	saved = false ;
	tournament = null ;
	ajax_error_management() ;
	poolcards = null ;
	// Link between mana colors array and mana color checks
	manacolors = ['X', 'W', 'U', 'B', 'R', 'G'] ;
	active_color = {} ;
	for ( var i in manacolors ) {
		var color = manacolors[i] ;
		var check = document.getElementById('check_c_'+color) ;
		active_color[color] = check.checked ;
		label_check(check) ;
		check.addEventListener('change', function(ev) {
			active_color[ev.target.value] = ev.target.checked ;
			label_check(ev.target) ;
			disp_side(poolcards.side, pool) ;
			check_all_c() ;
		}, false) ;
		check.previousElementSibling.addEventListener('dblclick', function(ev) { // Double click : select only that one
			for ( var i in manacolors ) {
				var color = manacolors[i] ;
				var check = document.getElementById('check_c_'+color) ;
				check.checked = ( check == ev.target.nextElementSibling ) ;
				active_color[check.value] = check.checked ;
				label_check(check) ;
			}
			disp_side(poolcards.side, pool) ;
			ev.preventDefault() ;
		}, false) ;
		check.previousElementSibling.addEventListener('contextmenu', function(ev) { // Right click : select all but that one
			for ( var i in manacolors ) {
				var color = manacolors[i] ;
				var check = document.getElementById('check_c_'+color) ;
				check.checked = ( check != ev.target.nextElementSibling ) ;
				active_color[check.value] = check.checked ;
				label_check(check) ;
			}
			disp_side(poolcards.side, pool) ;
			ev.preventDefault() ;
		}, false) ;
	}
		// Link between mana colors checks and "all" check
	check_all_c() ;
	label_check(document.getElementById('check_c_all')) ;
	document.getElementById('check_c_all').addEventListener('change', function(ev) {
		label_check(ev.target) ;
		for ( var i in manacolors ) {
			var color = manacolors[i] ;
			var check = document.getElementById('check_c_'+color) ;
			check.checked = ev.target.checked ;
			label_check(check) ;
			active_color[color] = check.checked ;
		}
		disp_side(poolcards.side, pool) ;
	}, false) ;
		// Link between rarities array and rarity check
	rarities = ['C', 'U', 'R'] ;
	active_rarity = {} ;
	for ( var i in rarities ) {
		var rarity = rarities[i] ;
		var check = document.getElementById('check_r_'+rarity) ;
		active_rarity[rarity] = check.checked ;
		label_check(check) ;
		check.addEventListener('change', function(ev) {
			active_rarity[ev.target.value] = ev.target.checked ;
			label_check(ev.target) ;
			disp_side(poolcards.side, pool) ;
			check_all_r() ;
		}, false) ;
	}
		// Link between rarity checks and "all" check
	check_all_r() ;
	label_check(document.getElementById('check_r_all')) ;
	document.getElementById('check_r_all').addEventListener('change', function(ev) {
		label_check(ev.target) ;
		for ( var i in rarities ) {
			var rarity = rarities[i] ;
			var check = document.getElementById('check_r_'+rarity) ;
			check.checked = ev.target.checked ;
			label_check(check) ;
			active_rarity[rarity] = check.checked ;
		}
		disp_side(poolcards.side, pool) ;
	}, false) ;
		// DND management for toggling cards
	deck.addEventListener('dragover', dragover, false)
	pool.addEventListener('dragover', dragover, false)
	deck.addEventListener('drop', drop, false) ;
	pool.addEventListener('drop', drop, false) ;
		// Message when you unload page with a modified deck
	modified = false ;
	window.addEventListener('beforeunload', function(ev) {
		if ( modified ) {
			ev.returnValue = 'Your deck was modified since last save\nYou may want to save those modifications before leaving page' ;
			alert(ev.returnValue) ;
			return ev.returnValue ;
		}
	}, false) ; // Page closure
		// Basic lands
	lands = [] ;
	function landbase(id, name) {
		return {'id': id, 'name': name, 'ext': 'UNH', 'color': '', 'rarity': 'L', 'attrs': {'color': ''}} ;
	}
	arr = [
		landbase(3332, 'Forest'),
		landbase(4621, 'Island'),
		landbase(6020, 'Mountain'),
		landbase(6871, 'Plains'),
		landbase(9266, 'Swamp')
	] ;
	var land_main = create_tr(land) ;
	var land_side = create_tr(land) ;
	for ( var i in arr ) {
		var card = arr[i] ;
		lands.push(card) ;
		var td = create_td(land_main, land_div(card, 'md')) ;
		td.title = 'Maindeck '+arr[i].name ;
		td.title += ', click to add, right click to remove one, middle click to remove all' ;
		var td = create_td(land_side, land_div(card, 'sb')) ;
		td.title = 'Sideboard '+arr[i].name ;
		td.title += ', click to add, right click to remove one, middle click to remove all' ;
	}
}
// Timer loop
function timer(id, spectactor) {
	$.getJSON('json/tournament.php', {'id': id, 'firsttime': true}, function(data) { // Get time left
		tournament = data ;
		if ( ( ! spectactor ) && ( data.status != 4 ) ) // If tournament isn't in "drafting" status, go back to tournament index (that will normally redirect to build)
			window.location.replace('index.php?id='+id) ;
		else {
			window.setTimeout(timer, sealed_timer, id, spectactor) ; // Refresh in 30 secs
			document.getElementById('timeleft').value = time_disp(parseInt(data.timeleft)) ;
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
			if ( iso(data.log) && ( data.log.length > loglength ) ) {
				if ( tournament_log.children.length != 0 ) // Some messages already recieved
					document.getElementById('tournament').classList.add('highlight') ;
				loglength = data.log.length ;
				tournament_spectactors(data.log, spectactors) ; // Populate from log
				tournament_log_ul(tournament_log, data.log, data.players, spectactors) ;
			}
		}
	}) ;
}
// Functions
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
function alpha_sort(card1, card2) {
	b = color_sort(card1, card2) ; // First sort by color
	if ( b != 0 )
		return b ;
	// Then inside same color, sort by name
	if ( card1.name == card2.name )
		return 0 ;
	if ( card1.name > card2.name )
		return 1 ;
	return -1 ;
}
function color_sort(card1, card2) {
	if ( card1.attrs.color == card2.attrs.color )
		return 0 ;
	if ( card1.attrs.color > card2.attrs.color )
		return -1 ;
	return 1 ;
}
function dragover(ev) { // Allows drop if dragged object is a card
	if ( ev.dataTransfer.getData('card') != '' ) // Nothing else but a card is dragged
		ev.preventDefault() ;
}
function drop(ev) { // Does drop (search card, toggle card)
	var card = ev.dataTransfer.getData('card') ;
	if ( card != '' ) // A card is dragged
		card_toggle(document.getElementById(card).card) ;
	else // Anything else than a card is dragged
		alert('Not dragging a card') ;
}
function label_check(target) {
	if ( target.checked )
		target.parentNode.classList.add('checked') ;
	else
		target.parentNode.classList.remove('checked') ;
}
function check_all_c() { // If all manacolor check are checked, check the "all", idem if they are unchecked
	var do_check = true ;
	var do_uncheck = true ;
	for ( var i in manacolors )
		if ( active_color[manacolors[i]] )
			do_uncheck = false ;
		else
			do_check = false ;
	if ( do_check )
		document.getElementById('check_c_all').checked = true ;
	if ( do_uncheck )
		document.getElementById('check_c_all').checked = false ;
	if ( do_check || do_uncheck )
		label_check(document.getElementById('check_c_all')) ;
}
function check_all_r() { // Idem for rarity
	var do_check = true ;
	var do_uncheck = true ;
	for ( var i in rarities ) {
		var rarity = rarities[i] ;
		if ( active_rarity[rarity] )
			do_uncheck = false ;
		if ( ! active_rarity[rarity] )
			do_check = false ;
	}
	if ( do_check )
		document.getElementById('check_r_all').checked = true ;
	if ( do_uncheck )
		document.getElementById('check_r_all').checked = false ;
	if ( do_check || do_uncheck )
		label_check(document.getElementById('check_r_all')) ;
}
function land_div(card, prefix) { // Returns visual representation of a land-card
	var div = create_div() ;
	div.classList.add(card.rarity) ;
	if ( window.innerHeight < 800 )
		div.classList.add('sr') ; // Small resolutions
	div.align = 'center' ;
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
		disp_side(poolcards.main, deck) ;
	}, false) ;
	div.addEventListener('contextmenu', function(ev) {
		ev.preventDefault() ;
	}, false)
	div.addEventListener('change', function(ev) {
		disp_side(poolcards.main, deck) ;
	}, false) ;
	// Image loading
	game.image_cache.load(card_images(card_image_url(card.ext, card.name, card.attrs)), function(img, tag) {
		tag.url = img.src ;
		tag.style.backgroundImage = 'url('+img.src+')' ;
	}, function(tag, url) {
		tag.appendChild(document.createTextNode(div.firstChild.name)) ;
	}, div) ;
	var val = 0 ;
	var input = create_input(prefix+card.name, val, prefix+card.name) ;
	input.size = 2 ;
	div.appendChild(input) ;
	return div
}
function land_nb(land) {
	var input = document.getElementById(land) ;
	if ( input == null ) {
		alert('Missing input for '+land) ;
		return -1 ;
	} else
		return parseInt(input.value) ;
}
function filter_lands(card, index, cards) {
	for ( var i in lands) {
		if ( card.name == lands[i].name ) {
			document.getElementById(this+card.name).value++ ;
			return false
		}
	}
	return true ;
}
function card_div(card) { // Returns visual representation of a card
	var div = create_div() ;
	if ( window.innerHeight < 800 )
		div.classList.add('sr') ; // Small resolutions
	div.id = card.id ;
	div.card = card ;
	div.classList.add(card.rarity) ;
	div.title = card.name+' , click to add/remove from deck, right or middle click to get infos' ;
	div.url = '' ;
	div.transformed_url = '' ;
	// Image loading
	game.image_cache.load(card_images(card_image_url(card.ext, card.name, card.attrs)), function(img, tag) {
		tag.url = img.src ;
		tag.style.backgroundImage = 'url('+img.src+')' ;
	}, function(tag, url) {
		tag.appendChild(document.createTextNode('['+tag.card.ext+']'+tag.card.name)) ;
	}, div) ;
	// Transformed image loading
	if ( iso(card.attrs.transformed_attrs) ) {
		game.image_cache.load(card_images(card_image_url(card.ext, card.attrs.transformed_attrs.name, card.attrs)), function(img, tag) {
			tag.transformed_url = img.src ;
		}, function(tag, url) {
			tag.appendChild(document.createTextNode(tag.card.attrs.transformed_attrs.name)) ;
		}, div) ;
	}
	// Events
	div.addEventListener('mouseover', function(ev) { // Initialize zoom
		zoomed.src = ev.target.url ;
		zoomed.width = cardimagewidth ;
		if ( ev.target.transformed_url != '' ) {
			transformed.src = ev.target.transformed_url ;
			transformed.width = cardimagewidth ;
			transformed.classList.add('disp') ;
			zoom.width = 2*cardimagewidth ;
		} else {
			transformed.classList.remove('disp') ;
			zoom.width = cardimagewidth ;
		}
		zoom.classList.add('disp') ;
	}, false) ;
	div.addEventListener('mousemove', function(ev) { // Update zoom
		// Image is displayed on cursor's bottom right by default
		// If it would make it appear outside "inner" displayed window, display it on cursor's left and/or top
		// The goal of this behaviour is to never display zoom on top of cursor (it would interact with mouse* events)
		// and make cards always readable
		var x = ev.clientX + 5 ; // 5px on cursor's right
		if ( x + zoom.width > window.innerWidth ) // Outside inner screen
			x = ev.clientX - zoom.width - 5 ; // 5px on cursor's left
		zoom.style.left = max(x, 0)+'px' ;
		var y = ev.clientY + 5 ;// 5px on cursor's bottom
		if ( y + zoom.clientHeight > window.innerHeight ) // Outside inner screen
			y = ev.clientY - zoom.clientHeight - 5 ; // 5px on cursor's top
		zoom.style.top = max(y, 0)+'px' ;
	}, false) ;
	div.addEventListener('mouseout', function(ev) { // Clear zoom
		zoom.classList.remove('disp') ;
	}, false) ;
	div.addEventListener('mouseup', function(ev) {
		switch ( ev.button ) {
			case 0 :
				card_toggle(ev.target.card) ;
				zoom.classList.remove('disp') ; // toggling card with a dblclick implies mouse is over div
				// but the toggle won't fire mouseout, doing it by hand here
				break ;
			case 1 :
				window.open('http://magiccards.info/query?q=!'+ev.target.card.name+'&v=card&s=cname') ;
				break ;
			case 2 :
				break ;
		}
	}, false) ;
	div.addEventListener('contextmenu', function(ev) {
		window.open('http://magiccards.info/query?q=!'+ev.target.card.name+'&v=card&s=cname') ;
		eventStop(ev) ;
	}, false) ;
	return div ;
}
function card_toggle(card) { // If card is in side, move it in deck, vice versa
	var from = null ;
	var to = poolcards.main ;
	var i = poolcards.side.indexOf(card) ;
	if ( i > -1 ) { // Card found in side
		from = poolcards.side ;
	} else {
		i = poolcards.main.indexOf(card) ;
		if ( i > -1 ) { // Card found maindeck
			from = poolcards.main ;
			to = poolcards.side ;
		}
	}
	var topush = from.splice(i, 1)[0] ;
	if ( to != null )
		to.push(topush) ;
	// Refresh displays
	if ( ( from == poolcards.side ) | ( to == poolcards.side ) )
		disp_side(poolcards.side, pool) ;
	if ( ( from == poolcards.main ) | ( to == poolcards.main ) )
		disp_side(poolcards.main, deck) ;
	modified = true ; // Set as modified for beforeunload check
}
function disp_side(originaldeck, table) {
	var side = clone(originaldeck) ; // Working on a clone of original array
	node_empty(table) ;
	var trb = create_tr(table) ;
	var trc = create_tr(table) ;
	var cc = 0 ; // Current converted cost computing
	var nb = 0 ; // Number of cards displayed
	var types = {'creature': 0} ;
	var total = 0 ;
	var cards = [] ;
	do { // Loop on columns while there are cards left
		var beginnb = nb ;
		var tdb = create_td(trb, '') ;
		for ( var i = 0 ; i < side.length ; i++ ) { // Card loop
			if ( i >= side.length ) // Must be done here because of "continue"
				break ;
			var card = side[i] ;
			if ( table == pool ) { // If displaying in pool, only display checked color's cards
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
					if ( card.attrs.color.indexOf(color) == -1 ) {
						side.splice(i, 1) ; // Remove card from list
						i-- ; // Card n removed, we must go back one step to continue over next card
						continue ;
					}
				} else {
				// More than one : display golds/hybrids only if all of its colors are checked
					var shall_continue = false ; // Indicate if we should go next card
					for ( var j in manacolors ) { // Foreach colors
						var color = manacolors[j] ;
						if ( ! active_color[color] ) // Unchecked
							if ( card.attrs.color.indexOf(color) > -1 ) { // Current card has current color
								side.splice(i, 1) ; // Remove card from list
								i-- ; // Card n removed, we must go back one step to continue over next card
								shall_continue = true ; // After the color loop, continue card loop
								break ; // No need to continue this for
							}
					}
					if ( shall_continue )
						continue ;
				}
				// Filter by rarity
				var r = card.rarity ; // modify this rarity will change border color
				if ( r == 'M' ) // Consider mythics as rares for selector
					r = 'R' ;
				if ( ( r != 'S' ) && ( ! active_rarity[r] ) ) {
					side.splice(i, 1) ;
					i-- ;
					continue ;
				}
			}
			if ( ! card.attrs )
				log2(card) ;
			else {
				if ( card.attrs.converted_cost == cc ) {
					total++ ;
					side.splice(i, 1) ;
					i-- ; // Card n removed, we must go back one step to continue over next card
					nb++ ; // One card displayed
					tdb.appendChild(card_div(card)) ;
					for ( var j = 0 ; j < card.attrs.types.length ; j++ ) { // Count types for stats
						var type = card.attrs.types[j] ;
						if ( ! types[type] )
							types[type] = 1 ;
						else
							types[type]++ ;
					}
					cards.push(card) ;
				}
			}
		}
		create_td(trc, (nb-beginnb)+' cards') ;
		if ( beginnb == nb ) { // No cards were added in current column (for current casting cost), removing column
			var div = create_div('No cards with CC = '+cc) ;
			if ( window.innerHeight < 800 )
				div.classList.add('sr') ; // Small resolutions
			div.classList.add('emptycol') ;
			tdb.appendChild(div) ;
		} else
			beginnb = nb ;
		cc++ ; // Next converted cost
	} while ( ( side.length > 0 ) ) ;
	var trc = create_tr(table) ;
	var nblands = 0 ;
	if ( table == deck ) {
		for ( var i in lands) {
			nblands += parseInt(document.getElementById('md'+lands[i].name).value) ;
		}
	}
	// Stats
	if ( ( table == deck ) )
		deck_stats_cc(cards) ;
	// Text line at bottom of table resuming content
	create_td(trc, (nb+nblands)+' total cards ('+nblands+' basic lands)') ;
	var line = '' ;
	for ( var i in types )
		line += i+' : '+types[i]+', ' ;
	line = line.substr(0, line.length-2)
	create_td(trc, line, cc-1) ;
}
