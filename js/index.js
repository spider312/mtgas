// index.js : Management of MTGAS index (games list, preloaded decks ...)
/*
function DisplayConnectionState() {
	var form = document.getElementById('unhosted-login') ;
	form.removeEventListener('submit', unhostedlogin, false) ;
	form.removeEventListener('submit', unhostedlogout, false) ;
	if(remoteStorage.isConnected()) {
		document.getElementById('unhosted-submit').value='Disconnect';
		document.getElementById('unhosted-span').innerHTML=remoteStorage.getUserAddress();
		document.getElementById('unhosted-input').style.display='none';
		form.target = '_blank' ;
		form.addEventListener('submit', unhostedlogout, false) ;
	} else {
		document.getElementById('unhosted-submit').value='Sign in';
		document.getElementById('unhosted-span').innerHTML='';
		document.getElementById('unhosted-input').style.display='';
		form.target = '' ;
		form.addEventListener('submit', unhostedlogin, false) ;
	}
}
function unhostedlogin(ev) {
	if ( ev.target.login.value != '' )
		remoteStorage.connect(ev.target.login.value, 'mtgas') ;
	else
		alert('Please type a login') ;
	ev.preventDefault() ;
	DisplayConnectionState() ;
}
function unhostedlogout(ev) {
	remoteStorage.disconnect() ;
	ev.preventDefault() ;
	DisplayConnectionState() ;
}
*/
$(function() { // On page load
	ajax_error_management() ;
	// Synchronize PHPSESSID cookie with stored player ID (in case player ID comes from profile importing)
	player_id = $.cookie(session_id) ;
	if ( ( localStorage['player_id'] == null ) || ( localStorage['player_id'] == '' ) ) // No player ID
		store('player_id', player_id) ; // Store actual PHPSESSID
	else if ( player_id != localStorage['player_id'] ) { // Player ID different from PHPSESSID
		$.cookie(session_id, localStorage['player_id']) ; // Overwrite PHPSESSID with Player ID
		window.location = window.location ; // Curent web page isn't informed ID changed, reload
	}
	// Fields that will be saved on change/blur, and restored on load
		// Profile
	save_restore('profile_nick') ; // Player's nickname
	last_working_avatar = document.getElementById('avatar_demo').src ; // Backup default avatar, for future errors (before save/restore !)
	var avatar_apply = function(field) { document.getElementById('avatar_demo').src = field.value ; } ;
	save_restore('profile_avatar', avatar_apply, avatar_apply) ; // Player's avatar
	save_restore('theme', function(el) {
		var oldtheme = $.cookie('theme') ;
		$.cookie('theme', el.value) ;
		if ( ( oldtheme != null ) && ( oldtheme != '' ) && confirm('Theme changed, need reload to apply') )
			window.location = window.location ;
	}) ; // Player's avatar
		// Options
	save_restore_options() ;
		// Creation
	save_restore('game_name') ;
	save_restore('tournament_name') ;
	save_restore('tournament_players') ;
	save_restore('tournament_boosters', save_tournament_boosters) ; // Before "type" because it will look at saved value
	save_restore('tournament_type', null, function(field) {
		tournament_boosters(field.selectedIndex) ;
	}) ;
	save_restore('draft_boosters') ; // hidden for saving
	save_restore('sealed_boosters') ;
	document.getElementById('game_name').focus() ; // Give focus on page load
// === [ EVENTS ] ==============================================================
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
		if ( fs.classList.toggle('hidden') )
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
		else
			$.post(ev.target.action, {
				'name': ev.target.name.value,
				'nick': document.getElementById('profile_nick').value,
				'avatar': document.getElementById('profile_avatar').value,
				'deck': deck_get(deckname)
			}, function(data) {
				if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
					alert(data.msg) ;
			}, 'json') ;
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
				'nick' : document.getElementById('profile_nick').value, 
				'avatar' : document.getElementById('profile_avatar').value, 
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
	// Profile
		// Update avatar demo on URL change, with error management
	document.getElementById('profile_avatar').addEventListener('change', function (ev) {
		document.getElementById('avatar_demo').src = ev.target.value ;
	}, false) ;
	document.getElementById('avatar_demo').addEventListener('load', function (ev) {
		last_working_avatar = ev.target.src ; // Backup as last working avatar, for future errors
		if ( ev.target.errored ) {
			ev.target.errored = false ;
			ev.target.classList.add('errored') ;
		} else 
			ev.target.classList.remove('errored') ;
	}, false) ;
	document.getElementById('avatar_demo').addEventListener('error', function (ev) {
		if ( ev.target.src == last_working_avatar )
			alert('Can\'t load default avatar') ;
		else {
			alert('Can\'t load '+ev.target.src+', rolling back to '+last_working_avatar) ;
			ev.target.errored = true ; // Will be set as errored on load
			ev.target.src = last_working_avatar ; // Restore last working avatar
		}
	}, false) ;
	// Server-side profile
	var l = document.getElementById('login') ;
	if ( l != null ) // Login form isn't on page (logged in)
		l.addEventListener('submit', function(ev) {
			$.getJSON(ev.target.action, {
				'email': ev.target.email.value,
				'password': ev.target.password.value,
			}, function(data) {
				if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
					alert(data.msg) ;
				if ( ( data.send ) || ( ev.target.overwrite.checked ) ) {
					$.post('json/profile_udate.php', {'json': JSON.stringify(localStorage)}, function(d) {
						if ( d.affected != 1 )
							alert('Something went wrong : '+d.affected) ;
						else
							document.location = document.location ;
					}, 'json') ;
				}
				if ( ( ! ev.target.overwrite.checked ) && ( typeof data.recieve == 'string' ) && ( data.recieve != '' ) ) {
					var profile = JSON.parse(data.recieve) ;
					localStorage.clear() ;
					for ( var i in profile )
						localStorage[i] = profile[i] ; // As we're DLing data, don't store() them, it would upload back
					document.location = document.location ;
				}
			}) ;
			return eventStop(ev) ;
		}, false) ;
	var l = document.getElementById('logout') ;
	if ( l != null ) // Login form isn't on page (logged in)
		l.addEventListener('submit', function(ev) {
			$.getJSON(ev.target.action, {}, function(data) {
				if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
					alert(data.msg) ;
				document.location = document.location ;
			}) ;
			return eventStop(ev) ;
		}, false) ;
	// Decks list
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
	// Profile management
	document.getElementById('backup').addEventListener('submit', function(ev) {
		var clone = {} ;
		for ( var i = 0 ; i < localStorage.length ; i++ ) {
			var key = localStorage.key(i) ;
			clone[key] = localStorage[key] ; 
		}
		var d = new Date() ;
		ev.target.name.value = 'mtgas_profile_'+clone.profile_nick+'_'+d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'.mtgas' ;
		ev.target.content.value = JSON.stringify(clone)
	}, false) ;
	document.getElementById('profile_file').addEventListener('change', function(ev) {
		if ( ev.target.files.length > 0 )
			document.getElementById('restore_submit').disabled = false ;
	}, false) ;
	document.getElementById('restore').addEventListener('submit', function(ev) {
		ev.preventDefault() ;
		if ( ev.target.profile_file.files.length == 0 )
			alert('Please browse to the saved profile file you want to restore') ;
		else {
			var file = ev.target.profile_file.files.item(0) ;
			if ( ! file.getAsText ) {
				alert('Your browsers doesn\'t support profile restoring. Please use firefox') ;
				return null ;
			}
			var text = file.getAsText('utf-8') ;
			try {
				var profile = JSON.parse(text) ;
			} catch (e) {
				alert('The file '+file.fileName+' ('+file.type+') doesn\'t appears to be a valid MTGAS profile file (json)') ;
				return null ;
			}
			if ( confirm('Are you sure you want to overwrite all your personnal data (nick, avatar, decks, tokens) with ones stored in profile file with nick '+profile.profile_nick) ) {
				ev.target.profile_file.value = '' ;
				localStorage.clear() ;
				for ( var i in profile )
					store(i, profile[i]) ;
				alert('All data were overwritten, reloading page') ;
				document.location = document.location ;
			}
		}
	}, false) ;
	document.getElementById('clear').addEventListener('click', function(ev) {
		if ( confirm('Are you sure you want to clear all your personnal data on this website ? (nick, avatar, decks, tokens, games, tournaments)') ) {
			localStorage.clear() ;
			$.cookie(session_id, null);
			alert('All data were cleared, reloading page') ;
			document.location = document.location ;
		}
	}, false) ;
	// Display decks list
	decks_list() ;
	get_extensions() ;
	// Start to display and regulary update games list
	games_timer(document.getElementById('pending_games'), document.getElementById('cell_no')
		, document.getElementById('running_games'), document.getElementById('running_games_no')) ;
	tournaments_timer(document.getElementById('pending_tournaments'), document.getElementById('tournament_no')
		, document.getElementById('running_tournaments'), document.getElementById('running_tournament_no')) ;
}) ;
// === [ TIMERS ] ==============================================================
function games_timer(pending_games, cell_no, running_games, running_games_no) {
// Requests from server a list of pending games, and display them in prepared tables
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
			cell_no.style.display = 'none' ; // Hide table line "no pending games"
			for ( var i = 0 ; i < rounds.length ; i++ ) { // Add new lines
				var round = rounds[i] ;
				var submit = create_submit('id', round.id, 'game_' + round.id) ;
				// Normal form for clients not trigering events
				var form = create_form('join.php', 'post', 
					create_hidden('nick', document.getElementById('profile_nick').value), 
					create_hidden('avatar', document.getElementById('profile_avatar').value), 
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
			cell_no.style.display = '' ; // Show table line "no pending games"
		// Display a list of runing games
		var rounds = data.runing_games ; // Get games list
		node_empty(running_games) ; // Remove old lines
		if ( rounds.length > 0 ) { // Some pending games returned
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
					joiner,
					create_a(time_disp(round.age), url),
					create_a(time_disp(round.inactivity), url)
				) ;
				tr.title = 'View '+round.name+' between '+round.creator_nick+' and '+round.joiner_nick ;
				if ( ( round.creator_id == player_id ) || ( round.joiner_id == player_id ) )
					tr.classList.add('registered') ;
			}
		} else // No pending games returned
			running_games_no.style.display = '' ; // Show table line "no pending games"
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
			tournament_no.style.display = 'none' ; // Hide table line "no pending tournaments"
			for ( var i = 0 ; i < tournaments.length ; i++ ) { // Add new lines
				var tournament = tournaments[i] ;
				var submit = create_submit('id', tournament.id, 'tournament_' + tournament.id) ;
				// Normal form for clients not trigering events
				var form = create_form('tournament/json/join.php', 'post', 
					create_hidden('nick', document.getElementById('profile_nick').value), 
					create_hidden('avatar', document.getElementById('profile_avatar').value), 
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
							'nick' : document.getElementById('profile_nick').value, 
							'avatar' : document.getElementById('profile_avatar').value, 
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
				var tr = create_tr(pending_tournaments, 
					form, 
					create_label(submit.id, tournament.type),
					create_label(submit.id, tournament.name),
					age,
					slots,
					playerlist
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
			tournament_no.style.display = '' ; // Show table line "no pending tournament"
		// Running
		node_empty(running_tournaments) ; // Remove old lines
		if ( data.tournaments_running.length > 0 ) {
			running_tournament_no.style.display = 'none' ; // Hide table line "no pending tournaments"
			for ( var i = 0 ; i < data.tournaments_running.length ; i++ ) {
				var t = data.tournaments_running[i] ;
				var url = 'tournament/?id='+t.id ;
				var title = 'View tournament '+t.type+' : '+t.name ;
				var tournament = JSON.parse(t.data) ;
				var age = create_a(time_disp(t.age), url, null, title) ;
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
			running_tournament_no.style.display = '' ;
	}) ;
	// Loop's next iteration
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
function load_profile(target, ev) {
	// Fill some hidden values
	var deck = deck_checked() ;
	if ( deck != null ) {
		document.getElementById(target+'_nick').value = document.getElementById('profile_nick').value ;
		document.getElementById(target+'_avatar').value = document.getElementById('profile_avatar').value
		document.getElementById(target+'_deck').value = deck_get(deck) ;
		return true ;
	} else
		return false ;
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
function gallery() {
	window.open('avatars.php') ;
}
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
	var boosters_label = document.getElementById('tournament_boosters_label') ;
	var suggestions = document.getElementById('tournament_suggestions') ;
	var suggestions_label = document.getElementById('tournament_suggestions_label') ;
	var booster_label = document.getElementById('booster_suggestions_label') ;
	switch ( type ) {
		case 0 : // Draft
			boosters.value = localStorage.draft_boosters ;
			boosters.size = 25 ;
			content = draft_formats ;
			break ;
		case 1 : // Sealed
			boosters.value = localStorage.sealed_boosters ;
			boosters.size = 50 ;
			content = sealed_formats
			break ;
		default : // Constructed
			boosters_label.style.display = 'none' ;
			suggestions_label.style.display = 'none' ;
			booster_label.style.display = 'none' ;
			boosters.value = '' ;
			return null ; // Next code is only executed for limited (boosters management)
	}
	// Fill boosters suggestions list
	boosters_label.style.display = '' ;
	booster_label.style.display = '' ;
	suggestions_label.style.display = '' ;
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
	while ( table.hasChildNodes() )
		table.removeChild(table.firstChild) ;
	if ( localStorage.decks ) {
		var decks = decks_get() ;
		for ( var i in decks ) {
			var deck_name = decks[i] ;
			var deck_content = deck_get(deck_name) ;
			var row = table.insertRow(-1) ; 
			row.title = deck_content ;
			row.id = deck_name ;
			var radio = create_radio('deck', deck_name, (deck_name == localStorage['deck']), ' ', 'fullwidth') ;
			radio.firstChild.id = 'radio_'+deck_name
			deck_name_s = deck_name ;
			if ( deck_name_s.length > deckname_maxlength )
				deck_name_s = deck_name_s.substr(0, deckname_maxlength-3) + '...' ;
			row.insertCell(-1).appendChild(create_label('radio_'+deck_name, deck_name_s)) ;
			row.insertCell(-1).appendChild(radio).addEventListener('change', function(ev) {
				store(ev.target.name, ev.target.value) ;
			}, false) ;
			row.insertCell(-1).appendChild(create_button('Goldfish', function(ev) {
				var row = node_parent_search(ev.target, 'TR') ;
				if ( load_profile('self', ev) ) { // Load data from profil to goldfish self
					document.getElementById('goldfish_nick').value = row.id ; // Defining deck name as opponent name in goldfish
					document.getElementById('goldfish_deck').value = deck_get(row.id) ;
					document.getElementById('goldfish').submit() ;
				} else
					alert('You have to select a deck in order to goldfish it') ;
			}, 'Play alone with your selected deck against '+deck_name, 'fullwidth')) ;
			row.insertCell(-1).appendChild(create_button('Delete', function(ev) {
				deck_del(ev.target.parentNode.parentNode.id) ;
				decks_list() ; // Refresh list
			}, 'Remove '+deck_name+' from list', 'fullwidth')) ;
			var tmp = row.insertCell(-1).appendChild(
				create_form('deckbuilder.php', 'get', 
					create_hidden('deck', deck_name), 
					create_submit(null, 'Edit', null, 'fullwidth')
				)
			) ;
			tmp.title = 'Edit '+deck_name ;
			tmp = row.insertCell(-1).appendChild(
				create_form('download_file.php', 'post',
					create_hidden('name', row.id+'.mwDeck'),
					create_hidden('content', deck_get(row.id)),
					create_submit(null, 'Export', null, 'fullwidth')
				)
			) ;
			tmp.title = 'Export '+deck_name+' as a .mwdeck file' ;
			row.addEventListener('mousedown', function(ev) {
				if ( ev.button == 1 )
					alert(ev.currentTarget.title) ;
			}, false) ;
		}
	} else
		$.getJSON('json/default_decks.php', { }, function(data) {
			for ( var i in data )
				store(i, data[i]) ;
			if ( localStorage.decks )
				decks_list() ;
		}) ;
}
