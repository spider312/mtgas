// index.js : Management of MTGAS index (games list, preloaded decks ...)
$(function() { // On page load
	workarounds() ;
	document.getElementById('shout')[0].focus() ;
	ajax_error_management() ;
	notification_request() ;
	game = {}
	game.options = new Options(true) ;
	game.options.add_trigger('profile_nick', function(option) {
		game.connection.close(1000, 'reconnecting for nick change') ;
		game.connection.connect() ;
	}) ;
	// Non-options fields to save
	save_restore('game_name') ;
	save_restore('tournament_name') ;
	save_restore('tournament_players') ;
		// Before "type" because it will look at saved value
	save_restore('tournament_boosters', save_tournament_boosters) ;
	save_restore('tournament_type', null, function(field) {
		tournament_boosters(field.selectedIndex) ;
	}) ;
	save_restore('draft_boosters') ; // hidden for saving
	save_restore('sealed_boosters') ;
	// DOM Elements cache
	shout = document.getElementById('shout') ;
	shouts = document.getElementById('shouts') ;
	shouters = document.getElementById('shouters') ;
	pending_games = document.getElementById('pending_games') ;
	running_games = document.getElementById('running_games') ;
	duel_view = document.getElementById('duel_view') ;
	duel_join = document.getElementById('duel_join') ;
	pending_tournaments = document.getElementById('pending_tournaments') ;
	running_tournaments = document.getElementById('running_tournaments') ;
	tournament_join = document.getElementById('tournament_join') ;
	tournament_view = document.getElementById('tournament_view') ;
	// Websockets
	game.connection = new Connexion('index', function(data, ev) { // OnMessage
		switch ( data.type  ) {
			// Base
			case 'msg' :
				alert(data.msg) ;
				break ;
			case 'userlist' :
				while( shouters.options.length > 0 )
					shouters.remove(0) ;
				for ( var i = 0 ; i < data.users.length ; i++ ) {
					var user = data.users[i] ;
					var option = create_option(user.nick, user.player_id) ;
					option.addEventListener('dblclick', function(ev) {
						var input = shout[0]
						input.value += ev.target.label+', ' ;
						input.focus() ;
					}, false) ;
					if ( user.inactive )
						option.classList.add('inactive') ;
					if ( user.typing )
						option.classList.add('typing') ;
					shouters.appendChild(option) ;
				}
				break ;
			// Shoutbox
			case 'shout' :
				var li = create_li(create_a(data.player_nick, '/player.php?id='+data.player_id))
				li.appendChild(create_text(': '+data.message)) ;
				li.appendChild(create_span(' '+timeWithDays(mysql2date(data.time)))) ;
				shouts.appendChild(li) ;
				shouts.scrollTop = shouts.scrollHeight ;
				if ( ! document.hasFocus() )
					notification_send('Mogg Shout', data.player_nick+' : '+data.message, 'shout') ;
				break ;
			// Duels
			case 'pendingduel' :
				if ( ! document.hasFocus() )
					notification_send('Mogg Duel', 'New duel : '+data.name, 'duel') ;
				pending_duel_add(data) ;
				break ;
			case 'joineduel' :
				if ( data.redirect && ( ( player_id == data.creator_id ) || ( player_id == data.joiner_id ) ) ) {
					window.focus() ;
					document.location = 'play.php?id='+data.id ;
				} else {
					pending_duel_remove(data.id) ;
					running_duel_add(data) ;
				}
				break ;
			case 'duelcancel' :
				if ( ! pending_duel_remove(data.id) )
					running_duel_remove(data.id) ;
				break ;
			// Tournaments
			case 'pending_tournament' :
				if ( pending_tournament_add(data) && ( data.min_players > 1 ) && ! document.hasFocus() )
					notification_send('Mogg Tournament', 'New tournament : '+data.format+' '+data.name, 'tournament');
				break ;
			case 'running_tournament' :
				var tr = pending_tournament_remove(data.id) ;
				if ( tr != null ) { // Found in pending : tournament starting, redirect
					for ( var i = 0 ; i < data.players.length ; i++ )
						if ( data.players[i].player_id == player_id ) {
							if (! document.hasFocus() )
								notification_send('Mogg Tournament starting',
									'Starting : '+data.format+' '+data.name, 'start') ;
							window.focus() ;
							document.location = 'tournament/?id='+data.id ;
						}
				}
				running_tournament_add(data) ;
				break ;
			case 'ended_tournament' :
				if ( pending_tournament_remove(data.id) == null )
					running_tournament_remove(data.id)
					//if ( running_tournament_remove(data.id) == null )
						//debug('ended tournament not found '+data.id) ;
				break ;
			default : 
				debug('Unknown type '+data.type) ;
				debug(data) ;
		}
	}, function(ev) { // OnClose/OnConnect
		// Clear everything
			// Shout
		node_empty(shouters) ;
		node_empty(shouts) ;
			// Duels
		duel_join.classList.add('hidden') ;
		node_empty(pending_games) ;
		duel_view.classList.add('hidden') ;
		node_empty(running_games) ;
			// Tournaments
		node_empty(pending_tournaments) ;
		tournament_join.classList.add('hidden') ; // for disconection
		node_empty(running_tournaments) ;
		tournament_view.classList.add('hidden') ;
	}) ;
	game.connection.events() ;
	//window.addEventListener('blur', function(ev) {connection.send({'type': 'blur'}) ;}, false) ;
	//window.addEventListener('focus', function(ev) {connection.send({'type': 'focus'}) ;}, false) ;
// === [ EVENTS ] ==============================================================
	// Shoutbox
	shout.addEventListener('submit', function(ev) {
		var field = this[0] ;
		if ( field.value != '' ) {
			if ( game.connection != null )
				game.connection.send(JSON.stringify({'type': 'shout', 'message': field.value})) ;
			field.value = '' ;
		}
		field.focus() ;
		return eventStop(ev) ;
	}, false) ;
	keypress_timer = null ;
	shout[0].prev_val = shout[0].value ;
	shout[0].addEventListener('keyup', function(ev) {
		if ( this.prev_val == this.value )
			return false ;
		this.prev_val = this.value ;
		if ( keypress_timer != null ) // A timer is already running, overwrite it
			clearTimeout(keypress_timer) ;
		else // No timer : notify
			if ( game.connection != null )
				game.connection.send(JSON.stringify({'type': 'keypress'})) ;
		keypress_timer = setTimeout(function() {
			keypress_timer = null ;
			game.connection.send(JSON.stringify({'type': 'keyup'})) ;
		}, 1000) ;
	}, false) ;
	// Form adapting to user selections
		// Boosters
	document.getElementById('tournament_type').addEventListener('change', function(ev) {
		tournament_boosters(ev.target.selectedIndex) ;
		get_extensions() ; // Refresh boosters nb
		document.getElementById('tournament_name').focus() ;
	}, false) ;
	document.getElementById('tournament_suggestions').addEventListener('change', function(ev) {
		var boosters = document.getElementById('tournament_boosters') ;
		boosters.value = ev.target.value ;
		save_tournament_boosters(boosters) ;
		boosters.focus() ;
	}, false) ;
	document.getElementById('booster_add').addEventListener('click', function(ev) {
		var boosters = document.getElementById('tournament_boosters') ;
		var booster_suggestions = document.getElementById('booster_suggestions') ;
		if ( boosters.value != '' )
			boosters.value += '-' ;
		boosters.value += booster_suggestions.value ;
		save(boosters) ;
	}, false) ;
	document.getElementById('boosters_reset').addEventListener('click', function(ev) {
		document.getElementById('tournament_boosters').value = '' ;
		return eventStop(ev) ;
	}, false) ;
	document.getElementById('tournament_options_toggle').addEventListener('click', function(ev) {
		var fs = ev.target.parentNode.parentNode ;
		if ( fs.classList.toggle('shrinked') )
			ev.target.textContent = '+' ;
		else
			ev.target.textContent = '-' ;
		return eventStop(ev) ;
	}, false) ;
	// Form submission (load data on creation/join)
		// Duel creation
	document.getElementById('game_create').addEventListener('submit', function(ev) {
		var deckname = deck_checked() ;
		if ( deckname == null )
			alert('You must select a deck in order to create a duel') ;
		else {
			var name = ev.target.name.value ;
			if ( name == '' )
				name = "I'm a noob ! " ;
			game.connection.send({
				'type': 'pendingduel',
				'name': name,
				'creator_nick': game.options.get('profile_nick'),
				'creator_avatar': game.options.get('profile_avatar'),
				'creator_deck': deck_get(deckname)
			}) ;
		}
		return eventStop(ev) ;
	}, false) ;
		// Tournament creation
	document.getElementById('tournament_create').addEventListener('submit', function(ev) {
		var deckname = deck_checked() ;
		var type = ev.target.type.value ;
		if ( tournament_constructed(type) && ( deckname == null ) )
			alert('You must select a deck in order to create a constructed tournament') ;
		else
			game.connection.send({
				'type' : 'pending_tournament',
				'format' : type,
				'name' : ev.target.name.value,
				'min_players' : ev.target.players.value,
				'boosters' : ev.target.boosters.value,
				'nick' : game.options.get('profile_nick'), 
				'avatar' : game.options.get('profile_avatar'), 
				'deck' : deck_get(deckname), 
				'rounds_number' : ev.target.rounds_number.value,
				'rounds_duration' : ev.target.rounds_duration.value,
				'clone_sealed' : ev.target.clone_sealed.checked
			}) ;
		return eventStop(ev) ;
	}, false) ;
	// Decks list
	document.getElementById('deck_edit').addEventListener('click', function(ev) {
		var deck = deck_checked() ;
		if ( deck == null ) {
			alert('Please select a deck') ;
			return false ;
		}
		var form = create_form('deckbuilder.php', 'get', create_hidden('deck', deck)) ;
		document.body.appendChild(form) ;
		form.submit() ;
		document.body.removeChild(form) ;
	}, false) ;
	document.getElementById('deck_delete').addEventListener('click', function(ev) {
		var deck = deck_checked() ;
		if ( deck == null ) {
			alert('Please select a deck') ;
			return false ;
		}
		deck_del(deck) ;
		decks_list() ; // Refresh list
	}, false) ;
	document.getElementById('deck_export').addEventListener('click', function(ev) {
		var deck = deck_checked() ;
		if ( deck == null ) {
			alert('Please select a deck') ;
			return false ;
		}
		var form = create_form('download_file.php', 'post',
			create_hidden('name', deck+'.mwDeck'),
			create_hidden('content', deck_get(deck))
		)
		document.body.appendChild(form) ;
		form.submit() ;
		document.body.removeChild(form) ;
	}, false) ;
	// Deck footer
	document.getElementById('deck_create').addEventListener('submit', function(ev) {
		if ( ev.target.deck.value == '' ) {
			alert('Please enter a name for your deck') ;
			ev.target.deck.focus() ;
			return eventStop(ev) ;
		}
	}, false) ;
	document.getElementById('download').addEventListener('submit', function(ev) { // Use proxy to DL user-url (as AJAX can't DL from another DNS)
		var url = ev.target.deck_url.value ;
		if ( url == '' ) {
			alert('Please enter an URL') ;
			ev.target.deck_url.focus() ;
		} else {
			var result = $.get('proxy.php', {'url': url}, function(content, response) {
				var name = deck_guessname(content) ;
				if ( name == '' ) {
					url = decodeURIComponent(url) ;
					name = url.substring(url.lastIndexOf('/')+1) ;
					name = name.replace(/\.mwdeck$/gi, '') ;
				}
				deck_set(name, content) ;
				decks_list() ;
				document.getElementById('deck_url').value = '' ;
			}, 'html') ;
			if ( result.addEventListener ) // Under opera, this method does not exist
				result.addEventListener("error", function(ev) {
					alert('NOK') ;
				}, false) ;
		}
		return eventStop(ev) ;
	}, false) ;
	document.getElementById('deckfile').addEventListener('change', function(ev) {
		deck_file_load(ev.target.files) ;
		ev.target.value = '' ;
	}, false) ;
	document.getElementById('upload').addEventListener('submit', function(ev) { // Workaround for browsers not triggering 'change' event on file input
		ev.preventDefault() ; // Don't submit
		deck_file_load(ev.target.deckfile.files) ;
		ev.target.deckfile.value = '' ;
	}, false) ;
	// Display decks list
	decks_list() ;
	get_extensions() ;
}) ;
// === [ Duels ] ==============================================================
function player_cell(cell, nick, avatar) {
	node_empty(cell) ;
	var img = create_img(avatar, nick+'\'s avatar', nick+'\'s avatar')
	img.style.maxWidth = '25px' ;
	img.style.maxHeight = '25px' ;
	cell.appendChild(img) ;
	cell.appendChild(create_text(nick)) ;
}
function pending_duel_add(round) {
	duel_join.classList.remove('hidden') ;
	var tr = create_tr(pending_games, 
		round.name,
		'',
		''
	) ;
	player_cell(tr.cells[1], round.creator_nick, round.creator_avatar) ;
	tr.timer = start_timer(tr.cells[2], round.creation_date) ;
	tr.round = round ;
	tr.addEventListener('click', function(ev) {
		var deckname = deck_checked() ;
		if ( deckname == null )
			alert('You must select a deck in order to join a duel') ;
		else
			game.connection.send({
				'type': 'joineduel',
				'id': this.round.id,
				'joiner_nick': game.options.get('profile_nick'),
				'joiner_avatar': game.options.get('profile_avatar'),
				'joiner_deck': deck_get(deckname)
			}) ;
	}, false) ;
	if ( round.creator_id == player_id )
		tr.classList.add('registered') ;
}
function running_duel_add(round) {
	duel_view.classList.remove('hidden') ;
	running_games.classList.remove('hidden') ;
	var url = 'play.php?id='+round.id ;
	var tr = create_tr(running_games,
		create_a(round.name, url),
		create_a('', url),
		create_a(round.creator_score, url),
		create_a(round.joiner_score, url),
		create_a('', url),
		create_a(time_disp(round.age), url)
	) ;
	player_cell(tr.cells[1].firstElementChild, round.creator_nick, round.creator_avatar) ;
	player_cell(tr.cells[4].firstElementChild, round.joiner_nick, round.joiner_avatar) ;
	tr.timer = start_timer(tr.cells[5].firstElementChild, round.creation_date) ;
	tr.round = round ;
	tr.title = 'View '+round.name+' between '+round.creator_nick+' and '+round.joiner_nick ;
	if ( ( round.creator_id == player_id ) || ( round.joiner_id == player_id ) )
		tr.classList.add('registered') ;
}
function duel_remove(tbody, div, id) {
	for ( var i = 0 ; i < tbody.rows.length ; i++ )
		if ( tbody.rows[i].round.id == id ) {
			clearInterval(tbody.rows[i].timer) ;
			tbody.removeChild(tbody.rows[i]) ;
			if ( tbody.rows.length == 0 )
				div.classList.add('hidden') ;
			return true ;
		}
	return false ;
}
function pending_duel_remove(id) {
	return duel_remove(pending_games, duel_join, id) ;
}
function running_duel_remove(id) {
	return duel_remove(running_games, duel_view, id) ;
}
// === [ Tournaments ] ==============================================================
function pending_tournament_add(tournament) {
	tournament_join.classList.remove('hidden') ;
	var current_line = null ;
	for ( var i = 0 ; i < pending_tournaments.rows.length ; i++ )
		if ( pending_tournaments.rows[i].tournament.id == tournament.id ) {
			current_line = pending_tournaments.rows[i] ;
			break ;
		}
	var name = tournament.name ;
	if ( ! tournament_constructed(tournament.format) && iso(tournament.data.boosters) )
		name += ' ('+tournament.data.boosters.join('-')+')' ;
	var tr = create_tr(pending_tournaments, 
		tournament.format,
		name,
		'', '', '',
		create_a('View', 'tournament/?id='+tournament.id, null,
			'Go to tournament page without register for playing')
	) ;
	if ( current_line != null )
		pending_tournaments.replaceChild(tr, current_line) ;
	tr.tournament = tournament ;
	tr.timer = start_timer(tr.cells[2], tournament.creation_date) ;
	tr.cells[3].classList.add('nowrap') ;
	tr.cells[4].classList.add('nowrap') ;
	tr.cells[5].addEventListener('click', function(ev) {ev.stopPropagation() ;}, false) ;
	update_tournament_players(tr) ;
	tr.addEventListener('click', function(ev) {
		game.connection.send({
			'type': 'tournament_register',
			'id' : tournament.id, 
			'nick' : game.options.get('profile_nick'), 
			'avatar' : game.options.get('profile_avatar'), 
			'deck' : deck_get(deck_checked())
		}) ;
	}, false) ;
	return ( current_line == null ) ;
}
function running_tournament_add(t) {
	tournament_view.classList.remove('hidden') ;
	// Search if it already has a line
	var table = running_tournaments ;
	for ( var i = 0 ; i < running_tournaments.rows.length ; i++ )
		if ( running_tournaments.rows[i].tournament.id == t.id ) {
			table = null ; // Create it off parent, will be added after
			var current_line = running_tournaments.rows[i] ; // Store for replaceChild
		}
	var url = 'tournament/?id='+t.id ;
	var title = 'View tournament '+t.type+' : '+t.name ;
	var name = t.name ;
	if ( ! tournament_constructed(t.format) && iso(t.data.boosters) )
		name += ' ('+t.data.boosters.join('-')+')' ;
	var tr = create_tr(table, 
		create_a(t.format, url, null, title), 
		create_a(name, url, null, title), 
		create_a(tournament_status(t.status), url, null, title), 
		create_a('', url, null, title), 
		create_a(list_players(t), url, null, title)
	) ;
	if ( table == null )
		running_tournaments.replaceChild(tr, current_line) ;
	tr.tournament = t ;
	tr.timer = start_timer(tr.cells[3].firstElementChild, t.due_time, true) ;
	tr.cells[3].classList.add('nowrap') ;
	tr.cells[4].classList.add('nowrap') ;
	for ( var j in t.players )
		if ( t.players[j].player_id == player_id )
			tr.classList.add('registered') ;
}
function update_tournament_players(tr) {
	// Slots
	var t = tr.tournament ;
	node_empty(tr.cells[3]) ;
	tr.cells[3].appendChild(create_text((t.min_players-t.players.length)+' / '+t.min_players)) ;
	// Player list
	node_empty(tr.cells[4]) ;
	tr.cells[4].appendChild(list_players(t)) ;
	// Registered
	var word = 'register to' ;
	tr.classList.remove('registered') ;
	for ( var j in t.players )
		if ( t.players[j].player_id == player_id ) {
			tr.classList.add('registered') ;
			word = 'unregister from' ;
		}
	tr.title = 'Click to '+word+' tournament : '+tr.tournament.name+' #'+tr.tournament.id ;
}
function list_players(tournament) {
	var ul = document.createElement('ol') ;
	for ( var j in tournament.players )
		ul.appendChild(create_li(tournament.players[j].nick)) ;
	return ul ;
}
function tournament_remove(id, table, container) {
	for ( var i = 0 ; i < table.rows.length ; i++ ) {
		var tr = table.rows[i] ;
		if ( tr.tournament.id == id ) {
			tr.parentNode.removeChild(tr) ;
			if ( table.rows.length == 0 )
				container.classList.add('hidden') ;
			return tr ;
		}
	}
	return null ;
}
function pending_tournament_remove(id) {
	return tournament_remove(id, pending_tournaments, tournament_join) ;
}
function running_tournament_remove(id) {
	return tournament_remove(id, running_tournaments, tournament_view) ;
}
// === [ FIXED LISTS ] =========================================================
function get_extensions() {
	$.getJSON('json/extensions.php', null, function(data) {
		var booster_suggestions = document.getElementById('booster_suggestions') ;
		var si = booster_suggestions.selectedIndex ;
		// Empty list
		node_empty(booster_suggestions) ;
		// Base editions
		group = create_element('optgroup') ;
		group.label = 'Base editions' ;
		for ( var i = 0 ; i < data.base.length ; i++ )
			group.appendChild(create_option(data.base[i].name, data.base[i].se)) ;
		booster_suggestions.appendChild(group) ;
		// Blocs
		var bloc = -1 ;
		var blocs = [] ;
		for ( var i = 0 ; i < data.bloc.length ; i++ ) {
			if ( typeof blocs[parseInt(data.bloc[i].bloc)] == 'undefined' ) { // First time bloc is encountered
				var group = create_element('optgroup') ; // Create bloc's group
				blocs[data.bloc[i].bloc] = group ;
				booster_suggestions.appendChild(group) ;
				bloc = data.bloc[i].bloc ;
			}
			group = blocs[data.bloc[i].bloc] ; // Get current bloc's group
			group.appendChild(create_option(data.bloc[i].name, data.bloc[i].se)) ;
			if ( data.bloc[i].bloc == data.bloc[i].id ) { // Main extension
				group.label = data.bloc[i].name ;
				var tt = document.getElementById('tournament_type') ;
				var boostnb = 0 ;
				if ( tt.value == 'draft' )
					boostnb = 3 ;
				else
					if ( tt.value == 'sealed' )
						boostnb = 6 ;
				var nb = Math.round(boostnb/group.children.length) ; // Nb of each boosters
				var b = '' ;
				for ( var k = 0 ; k < group.children.length ; k++ ) {
					//for ( var j = 0 ; j < nb ; j++ ) {
					if ( b != '' )
						b += '-' ;
					b += group.children[k].value
					if ( nb > 1 )
						b += '*'+nb ;
				}
				group.appendChild(create_option('Bloc '+data.bloc[i].name, b)) ;
			}
		}
		// Special
		/*
		group = create_element('optgroup') ;
		group.label = 'Special' ;
		for ( var i = 0 ; i < data.special.length ; i++ )
			group.appendChild(create_option(data.special[i].name, data.special[i].se)) ;
		booster_suggestions.appendChild(group) ;
		*/
		// Restore selected index
		booster_suggestions.selectedIndex = si;
	}) ;
}
// === [ FORMS ] ===============================================================
function save_tournament_boosters(boosters) { // When saving tournament boosters, also save it as draft/sealed boosters
	switch ( document.getElementById('tournament_type').selectedIndex ) {
		case 0 : // Draft
			var field = document.getElementById('draft_boosters')
			break ;
		case 1 : // Sealed
			var field = document.getElementById('sealed_boosters')
			break ;
		default : // Constructed
			return false ;
	}
	field.value = boosters.value ;
	save(field) ;
	return true ;
}
function tournament_boosters(type) {
	var boosters = document.getElementById('tournament_boosters') ;
	var suggestions = document.getElementById('tournament_suggestions') ;
	var limited_div = document.getElementById('limited') ;
	switch ( type ) {
		case 0 : // Draft
			limited_div.classList.remove('hidden') ;
			boosters.value = game.options.get('draft_boosters') ;
			boosters.size = 25 ;
			content = draft_formats ;
			break ;
		case 1 : // Sealed
			limited_div.classList.remove('hidden') ;
			boosters.value = game.options.get('sealed_boosters') ;
			boosters.size = 50 ;
			content = sealed_formats
			break ;
		default : // Constructed
			limited_div.classList.add('hidden') ;
			return null ; // Next code is only executed for limited (boosters management)
	}
	// Fill boosters suggestions list
	var set = false ;
		// Empty
	while ( suggestions.options.length > 0 )
		suggestions.options.remove(0) ;
		// Fill with hard schemes
	for ( var i in content ) {
		var option = create_option(i, content[i]);
		suggestions.options.add(option) ;
		if ( boosters.value == content[i] ) {
			option.selected = true ;
			set = true ;
		}
	}
		// Add empty "Custom" booster scheme
	var option = create_option('Custom', '')
	suggestions.options.add(option) ;
		// If no scheme was set as selected in filling, select "Custom"
	if ( ! set )
		option.selected = true ;
}
// Decks management
function deck_checked() { // Returns selected deck's name
	var elts = document.getElementsByName('deck') ;
	for ( var i = 0 ; i < elts.length ; i++ ) {
		var elt = elts.item(i) ;
		if ( elt.checked )
			return elt.value ;
	}
	return null
}
function decks_list() {
	var table = document.getElementById('decks_list') ;
	if ( table == null )
		return false ;
	while ( table.hasChildNodes() )
		table.removeChild(table.firstChild) ;
	if ( localStorage.decks ) {
		var decks = decks_get() ;
		for ( var i in decks ) {
			var deck_name = decks[i] ;
			var deck_content = deck_get(deck_name) ;
			var row = table.insertRow(-1) ;
			//row.id = deck_name ;
			row.title = deck_name ;
			row.classList.add('deck') ;
			/*
			row.toString = function() {
				return 'Row Card '+this.id ;
			}
			row.draggable = true ;
			row.addEventListener('dragstart', function(ev) {
				ev.dataTransfer.setData('text/plain', ev.target.id) ;
			}, false) ;
			row.addEventListener('dragenter', function(ev) {
				//alert(ev.originalTarget.parentNode.parentNode.parentNode.tagName) ;
				//alert(node_parent_search(ev.originalTarget, 'TR')) ;
				var from = ev.currentTarget ;
				var to = node_parent_search(ev.target, 'TR') ;
				alert(from.id + ' -> ' +to.id) ;
				log2(ev) ;
				return eventStop(ev) ;
			}, false) ;*/
			// Main cell : radio + name
			var cell = row.insertCell(-1) ;
			cell.colSpan = 2 ;
			// Radio
			var radio = create_radio('deck', deck_name, (deck_name == game.options.get('deck'))) ;
			radio.addEventListener('change', function(ev) {
				store(ev.target.name, ev.target.value) ;
			}, false) ;
			// Deck name
			deck_name_s = deck_name ;
			if ( deck_name_s.length > deckname_maxlength )
				deck_name_s = deck_name_s.substr(0, deckname_maxlength-3) + '...' ;
			var label = create_label(null, radio, deck_name_s) ;
			label.addEventListener('dblclick', function(ev) {
				document.getElementById('deck_edit').click() ;
			}, false) ;
			label.title = 'Click to select '+deck_name+' before creating or joining a duel or constructed tournament, double click to edit' ;
			cell.appendChild(label) ;
			// View
			var cell = row.insertCell(-1) ;
			cell.classList.add('rightalign') ;
			var img = create_img(theme_image('deckbuilder/layer_visible.png')[0]) ;
			if ( deck_content == '' )
				img.title += 'Deck list is empty' ;
			else
				img.title += deck_content ; 
			img.deck_name = deck_name
			img.addEventListener('click', function(ev) {
				alert(deck_get(ev.target.deck_name)) ;
			}, false) ;
			img.addEventListener('mouseover', function(ev) {
				ev.target.src = theme_image('deckbuilder/layer_novisible.png')[0] ;
				
			}, false) ;
			img.addEventListener('mouseout', function(ev) {
				ev.target.src = theme_image('deckbuilder/layer_visible.png')[0] ;
			}, false) ;
			cell.appendChild(img) ;
			var img = create_img('themes/'+theme+'/goldfish.png') ;
			img.height = 18 ;
			img.style.cursor = 'pointer' ;
			cell.appendChild(img) ;
			// Goldfish
			img.addEventListener('click', function(ev) {
				var deck = deck_checked() ;
				if ( deck == null ) {
					alert('Please select a deck') ;
					return false ;
				}
				game.connection.send({
					'type': 'goldfish',
					'creator_nick': game.options.get('profile_nick'),
					'creator_avatar': game.options.get('profile_avatar'),
					'creator_deck': deck_get(deck),
					'joiner_nick': ev.target.deck_name,
					'joiner_avatar': 'themes/'+theme+'/goldfish.png',
					'joiner_deck': deck_get(ev.target.deck_name)
				}) ;
			}, false) ;
			img.title = 'Play with your selected deck against '+deck_name ;
			img.deck_name = deck_name
			cell.appendChild(img) ;
		}
	} else
		$.getJSON('json/default_decks.php', { }, function(data) {
			for ( var i in data )
				store(i, data[i]) ;
			if ( localStorage.decks )
				decks_list() ;
		}) ;
}
