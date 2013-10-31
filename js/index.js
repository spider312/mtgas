// index.js : Management of MTGAS index (games list, preloaded decks ...)
$(function() { // On page load
	document.getElementById('game_name').select() ; // Give focus on page load
	ajax_error_management() ;
	player_id = $.cookie(session_id) ;
	game = {}
	game.options = new Options(true) ;
	// Non-options fields to save
	save_restore('game_name') ;
	save_restore('tournament_name') ;
	save_restore('tournament_players') ;
	save_restore('tournament_boosters', save_tournament_boosters) ; // Before "type" because it will look at saved value
	save_restore('tournament_type', null, function(field) {
		tournament_boosters(field.selectedIndex) ;
	}) ;
	save_restore('draft_boosters') ; // hidden for saving
	save_restore('sealed_boosters') ;
	last_shout_id = 0 ;
// === [ EVENTS ] ==============================================================
	// Shoutbox
	document.getElementById('shout').addEventListener('submit', function(ev) {
		var field = this[0] ;
		if ( field.value != '' ) {
			$.getJSON(this.action,
				{'nick': game.options.get('profile_nick'),'message': field.value, 'from': last_shout_id}) ;
			field.value = '' ;
		}
		return eventStop(ev) ;
	}, false) ;
	/*
	var shouts = document.getElementById('shouts')
	shoutsheight = shouts.clientHeight ;
	shouts.addEventListener('scroll', function(ev) {
		if ( shouts.clientHeight != shoutsheight ) {
			shoutsheight = shouts.clientHeight ;
			shouts.scrollTop = shouts.scrollHeight ;
		}
	}, false) ;
	*/
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
			$.post(ev.target.action, {
				'name': name,
				'nick': game.options.get('profile_nick'),
				'avatar': game.options.get('profile_avatar'),
				'deck': deck_get(deckname)
			}, function(data) {
				if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
					alert(data.msg) ;
			}, 'json') ;
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
			$.post(ev.target.action, {
				'type' : type,
				'name' : ev.target.name.value,
				'players' : ev.target.players.value,
				'boosters' : ev.target.boosters.value,
				'nick' : game.options.get('profile_nick'), 
				'avatar' : game.options.get('profile_avatar'), 
				'deck' : deck_get(deckname), 
				'rounds_number' : ev.target.rounds_number.value,
				'rounds_duration' : ev.target.rounds_duration.value,
				'clone_sealed' : ev.target.clone_sealed.checked
			 }, function(data) {
				if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
					alert(data.msg) ;
			}, 'json') ;
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
	// Start to display and regulary update games list
	shout_timer(document.getElementById('shouts')) ;
	games_timer(document.getElementById('pending_games'), document.getElementById('cell_no')
		, document.getElementById('running_games'), document.getElementById('running_games_no')) ;
	tournaments_timer(document.getElementById('pending_tournaments'), document.getElementById('tournament_no')
		, document.getElementById('running_tournaments'), document.getElementById('running_tournament_no')) ;
}) ;
// === [ TIMERS ] ==============================================================
function shout_timer(ul) {
	$.getJSON('json/shout.php', {'from': last_shout_id}, function(shouts) {
		if ( shouts.length > 0 ) {
			for ( var i in shouts ) {
				var id = parseInt(shouts[i].id) ;
				if ( id > last_shout_id )
					last_shout_id = id ;
				var li = create_li('<'+shouts[i].sender_nick+'> '+shouts[i].message)
				li.title = shouts[i].time
				ul.appendChild(li) ;
			}
			ul.scrollTop = ul.scrollHeight ;
		}
		window.setTimeout(shout_timer, game_list_timer, ul) ;
	}) ;
}
// Requests from server a list of pending games, and display them in prepared tables
function games_timer(pending_games, cell_no, running_games, running_games_no) {
	$.getJSON('json/pending_games.php', {'player_id': player_id}, function(data) {
		// Redirect to joined game/tournament
		if ( data.game_redirect ) {
			window.focus() ;
			document.location = 'play.php?id='+data.game_redirect ;
		}
		// Displays a list of single games
		var rounds = data.games ; // Get games list
		node_empty(pending_games) ; // Remove old lines
		if ( rounds.length > 0 ) { // Some pending games returned
			document.getElementById('duel_join').classList.remove('hidden') ;
			cell_no.style.display = 'none' ; // Hide table line "no pending games"
			for ( var i = 0 ; i < rounds.length ; i++ ) { // Add new lines
				var round = rounds[i] ;
				var submit = create_submit('id', round.id, 'game_' + round.id) ;
				// Normal form for clients not trigering events
				var form = create_form('join.php', 'post', 
					create_hidden('nick', game.options.get('profile_nick')), 
					create_hidden('avatar', game.options.get('profile_avatar')), 
					create_hidden('deck', deck_get(deck_checked())),
					submit
				) ;
				// Submit override for the form, replacing its sumbission by an AJAJ query
				form.addEventListener('submit', function(ev) {
					var deckname = deck_checked() ;
					if ( deckname == null ) {
						alert('You must select a deck in order to join a duel') ;
						return eventStop(ev) ;
					}
				}, false) ;
				// Table line content (form is the first cell)
				var img = create_img(round.creator_avatar, round.creator_nick+'\'s avatar', round.creator_nick+'\'s avatar')
				img.style.maxWidth = '25px' ;
				img.style.maxHeight = '25px' ;
				var creator = create_label(submit.id, round.creator_nick) ;
				creator.insertBefore(img, creator.firstChild) ;
				var tr = create_tr(pending_games, 
					form, 
					create_label(submit.id, round.name),
					creator,
					create_label(submit.id, time_disp(round.age)),
					create_label(submit.id, time_disp(round.inactivity))
				) ;
				if ( round.creator_id == player_id )
					tr.classList.add('registered') ;
			}
		} else // No pending games returned
			//cell_no.style.display = '' ; // Show table line "no pending games"
			document.getElementById('duel_join').classList.add('hidden') ;
		// Display a list of runing games
		var rounds = data.runing_games ; // Get games list
		node_empty(running_games) ; // Remove old lines
		if ( rounds.length > 0 ) { // Some pending games returned
			document.getElementById('duel_view').classList.remove('hidden') ;
			running_games_no.style.display = 'none' ; // Hide table line "no pending games"
			for ( var i = 0 ; i < rounds.length ; i++ ) { // Add new lines
				var round = rounds[i] ;
				var url = 'play.php?id='+round.id ;
				// Creator
				var creator = create_a(round.creator_nick, url) ;
				var img = create_img(round.creator_avatar, round.creator_nick+'\'s avatar', round.creator_nick+'\'s avatar')
				img.style.maxWidth = '25px' ;
				img.style.maxHeight = '25px' ;
				creator.insertBefore(img, creator.firstChild) ;
				// Joiner
				var joiner = create_a(round.joiner_nick, url) ;
				var img = create_img(round.joiner_avatar, round.joiner_nick+'\'s avatar', round.joiner_nick+'\'s avatar')
				img.style.maxWidth = '25px' ;
				img.style.maxHeight = '25px' ;
				joiner.insertBefore(img, joiner.firstChild) ;
				// Line
				var tr = create_tr(running_games,
					create_a(round.name, url),
					creator,
					create_a(round.creator_score, url),
					create_a(round.joiner_score, url),
					joiner,
					create_a(time_disp(round.age), url),
					create_a(time_disp(round.inactivity), url)
				) ;
				tr.title = 'View '+round.name+' between '+round.creator_nick+' and '+round.joiner_nick ;
				if ( ( round.creator_id == player_id ) || ( round.joiner_id == player_id ) )
					tr.classList.add('registered') ;
			}
		} else // No pending games returned
			//running_games_no.style.display = '' ; // Show table line "no pending games"
			document.getElementById('duel_view').classList.add('hidden') ;
	}) ;
	// Loop's next iteration
	window.setTimeout(games_timer, game_list_timer // Call same function in 'game_list_timer' seconds
		, pending_games, cell_no, running_games, running_games_no) ; // With all same parameters (pointers to result displaying tables)
}
function tournaments_timer(pending_tournaments, tournament_no, running_tournaments, running_tournament_no) {
// Requests from server a list of pending and past tournaments, and display them in prepared tables
	$.getJSON('tournament/json/pending.php', {'player_id': player_id}, function(data) {
		if ( data.tournament_redirect ) {
			window.focus() ;
			document.location = 'tournament/?id='+data.tournament_redirect ;
		}
		// Displays a list of pending tournaments
		var tournaments = data.tournaments ;
		node_empty(pending_tournaments) ; // Remove old lines
		if ( tournaments.length > 0 ) { // Some pending games returned
			document.getElementById('tournament_join').classList.remove('hidden') ;
			tournament_no.style.display = 'none' ; // Hide table line "no pending tournaments"
			for ( var i = 0 ; i < tournaments.length ; i++ ) { // Add new lines
				var tournament = tournaments[i] ;
				var submit = create_submit('id', tournament.id, 'tournament_' + tournament.id) ;
				// Normal form for clients not trigering events
				var form = create_form('tournament/json/join.php', 'post', 
					create_hidden('nick', game.options.get('profile_nick')), 
					create_hidden('avatar', game.options.get('profile_avatar')), 
					create_hidden('deck', deck_get(deck_checked())),
					submit
				) ;
				// Submit override for the form, replacing its sumbission by an AJAJ query
				form.addEventListener('submit', function(ev) {
					var deckname = deck_checked() ;
					if ( tournament_constructed(tournament.type) && ( deckname == null ) )
						alert('You must select a deck in order to join a constructed tournament') ;
					else
						$.post(ev.target.action, {
							'id' : ev.target.id.value, 
							'nick' : game.options.get('profile_nick'), 
							'avatar' : game.options.get('profile_avatar'), 
							'deck' : deck_get(deckname)
						 }, function(data) {
							if ( data.msg != '' )
								alert(data.msg) ;
						}, 'json') ;
					return eventStop(ev) ;
				}, false) ;
				// Table line content (form is the first cell)
				var age = create_label(submit.id, time_disp(tournament.age)) ;
				age.classList.add('nowrap') ;
				var slots = create_label(submit.id, (tournament.min_players-tournament.players.length)+' / '+tournament.min_players) ;
				slots.classList.add('nowrap') ;
				var playerlist = create_label(submit.id, list_players(tournament)) ;
				playerlist.classList.add('nowrap') ;
				var view = create_a('View', 'tournament/?id='+tournament.id, null, 'Just view tournament page, chat with other players, without register for playing') ;
				var tr = create_tr(pending_tournaments, 
					form, 
					create_label(submit.id, tournament.type),
					create_label(submit.id, tournament.name),
					age,
					slots,
					playerlist,
					view
				) ;
				var word = 'register'
				for ( var j in tournament.players )
					if ( tournament.players[j].player_id == player_id ) {
						tr.classList.add('registered') ;
						word = 'unregister' ;
					}
				tr.title = 'Click to '+word+' to tournament '+tournament.type+' : '+tournament.name ;
			}
		} else // No pending tournaments returned
			//tournament_no.style.display = '' ; // Show table line "no pending tournament"
			document.getElementById('tournament_join').classList.add('hidden') ;
		// Running
		node_empty(running_tournaments) ; // Remove old lines
		if ( data.tournaments_running.length > 0 ) {
			document.getElementById('tournament_view').classList.remove('hidden') ;
			running_tournament_no.style.display = 'none' ; // Hide table line "no pending tournaments"
			for ( var i = 0 ; i < data.tournaments_running.length ; i++ ) {
				var t = data.tournaments_running[i] ;
				var url = 'tournament/?id='+t.id ;
				var title = 'View tournament '+t.type+' : '+t.name ;
				var tournament = JSON.parse(t.data) ;
				var age = create_a(time_disp(t.time_left), url, null, title) ;
				age.classList.add('nowrap') ;
				var playerlist = create_a(list_players(tournament), url, null, title) ;
				playerlist.classList.add('nowrap') ;
				var tr = create_tr(running_tournaments, 
					create_a(t.type, url, null, title), 
					create_a(t.name, url, null, title), 
					create_a(tournament_status(t.status), url, null, title), 
					age,
					playerlist
				) ;
				for ( var j in tournament.players )
					if ( tournament.players[j].player_id == player_id )
						tr.classList.add('registered') ;
			}
		} else
			//running_tournament_no.style.display = '' ;
			document.getElementById('tournament_view').classList.add('hidden') ;
	}) ;
	// Loop's next iteration
	if ( true ) // To stop loop for debuging
		window.setTimeout(tournaments_timer, game_list_timer // Call same function in 'game_list_timer' seconds
			, pending_tournaments, tournament_no // With all same parameters (pointers to result displaying tables)
			, running_tournaments, running_tournament_no) ;
}
function list_players(tournament) {
	var ul = document.createElement('ol') ;
	for ( var j in tournament.players )
		ul.appendChild(create_li(tournament.players[j].nick)) ;
	return ul ;
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
				var form = create_form('goldfish.php', 'post',
					create_hidden('nick', game.options.get('profile_nick')),
					create_hidden('avatar', game.options.get('profile_avatar')),
					create_hidden('deck', deck_get(deck)), 
					create_hidden('goldfish_nick', ev.target.deck_name),
					create_hidden('goldfish_avatar', 'themes/'+theme+'/goldfish.png'),
					create_hidden('goldfish_deck', deck_get(ev.target.deck_name))
				) ;
				document.body.appendChild(form) ;
				form.submit() ;
				document.body.removeChild(form) ;
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
