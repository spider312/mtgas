function start(tournament_id) {
	var player_id = $.cookie(session_id) ;
	// Error management
	ajax_error_management() ; 
	timeleft = document.getElementById('timeleft') ;
	html_status = document.getElementById('status') ;
	players_table = document.getElementById('players_table') ;
	past_rounds = document.getElementById('past_rounds') ;
	current_round = document.getElementById('current_round') ;
	tournament_log = document.getElementById('tournament_log') ;
	document.getElementById('log_form').addEventListener('submit', function(ev) {
		$.getJSON(ev.target.action, {'id': tournament_id, 'msg': ev.target.msg.value}, function(data) {
			if ( data.nb != 1 )
				alert(data.nb+' affected rows') ;
			else
				ev.target.msg.value = '' ;
				//timer(tournament_id, player_id) ;
		}) ;
		ev.preventDefault() ;
	}, false) ;
	prev_data = '' ;
	me = null ;
	data = {} ;
	last_id = 0 ;
	$.ajaxSetup({ cache: false });
	spectactors = [] ;
	timer(tournament_id, player_id, data, last_id, true) ;
}
function player_get(players, id) {
	for ( var i in players )
		if ( players[i].player_id == id )
			return players[i] ;
	return null ;
}
function update(g, l) { // Update global fields with local values
	for ( var i in l )
		if ( iso(l[i]) && iso(g[i]))
			update(g[i], l[i]) ;
		else
			g[i] = l[i] ;
}
function timer(tournament_id, player_id, data, last_id, firsttime) {
	var param = {'id': tournament_id, 'last_id': last_id, 'firsttime': firsttime} ;
	$.getJSON('json/tournament.php', param, function(rdata) { // Get time left
		if ( iss(rdata.msg) )
			alert(rdata.msg) ;
		if ( iss(rdata.data) )
			try {
				var tdata = JSON.parse(rdata.data) ;
				rdata.data = tdata ;
			} catch (e) {
				//alert(e+' : \n'+res);
				alert(e);
				return null ;
			}
		update(data, rdata) ;
		// Spectactor
		if ( firsttime && ( player_get(data.players, player_id) == null ) )
			$.getJSON('json/spectactor.php', {'id': tournament_id, 'nick': localStorage['profile_nick']}, function(data) {
				if ( data.nb != 1 )
					alert(data.nb+' affected rows') ;
			}) ;
		//
		var t_status = parseInt(data.status) ; // Tournament's status
		// List players and their scores
		if ( iso(data.players) && ( prev_data != JSON.stringify(data.players) ) ) {
			prev_data = JSON.stringify(data.players) ;
			node_empty(players_table) ;
			for ( var i in data.players ) {
				var player = data.players[i] ;
				var tr = create_tr(players_table) ;
				tr.player = player ;
				if ( player_id == player.player_id ) {
					tr.classList.add('self') ;
					me = player ;
				}
				create_td(tr, player.order) ;
				var td = create_td(tr, create_a(player.nick, '../stats.php?id='+data.players[i].player_id)) ;
				if ( player.avatar.substr(0, 4) == 'http' )
					var img = create_img(player.avatar) ;
				else
					var img = create_img('../'+player.avatar) ;
				img.height = 50 ;
				create_td(tr, img) ;
				create_td(tr, player_status(player.status, player.ready)) ;
				if ( data.data.score ) {
					if ( data.data.score[player.player_id] ) {
						var score = data.data.score[player.player_id] ;
						create_td(tr, getGetOrdinal(score.rank)) ;
						create_td(tr, score.matchpoints) ;
						if ( isn(score.opponentmatchwinpct) )
							create_td(tr, Math.round(score.opponentmatchwinpct*100)+'%') ;
						else
							create_td(tr, 'N/A') ;
						if ( isn(score.opponentgamewinpct) )
							create_td(tr, Math.round(score.opponentgamewinpct*100)+'%') ;
						else
							create_td(tr, 'N/A') ;
						create_td(tr, Math.round(score.matchwinpct*100)+'%') ;
						create_td(tr, Math.round(score.gamewinpct*100)+'%') ;
					} else
						create_td(tr, 'Player has no score Oo', 6) ;
				} else
					create_td(tr, 'Not available before first round\'s end', 6) ;
				if ( ( t_status == 6 ) || ( player_id == player.player_id ) ) { // tournament ended or self
					var button_save = create_button('Save as ...', function(ev) {
						var name = data.name ;
						var player = ev.target.parentNode.parentNode.parentNode.player ;
						if ( player_id != player.player_id )
							name += '_'+player.nick ;
						name = prompt('Deck name', name) ;
						if ( name != null )
							deck_set(name, '// Deck file for Magic Workstation created with mogg.fr\n// NAME : '+data.name+'\n// CREATOR : '+player.nick+'\n// FORMAT : '+data.type+'\n'+player.deck) ;
					}, 'Save deck in decklist') ;
					var button_edit = create_submit('edit', 'Edit'/*, id, classname*/) ;
					button_edit.title = 'Enter in deck builder mode with this deck. Will ONLY be saved in your deck list' ;
					var form_edit = create_form('build.php', 'post'
						, create_hidden('deck', player.deck)
						, create_hidden('name', data.type+'_'+data.name+'_'+player.nick)
						, button_edit
					) ;
					var actions = create_div(button_save) ;
					actions.appendChild(form_edit) ;
					if ( t_status < 6 ) { // Not finished (so only on 'self' line)
						var button_drop = create_button('Drop', function(ev) {
							if ( confirm('This action is irremediable, are you sure you want to end your participation in this tournament ?') ) {
								$.getJSON('json/drop.php', {'id': tournament_id}, function(data) {
									alert(data.msg) ;
								}) ;
							}
						}, 'End your participation in this tournament') ;
						actions.appendChild(button_drop) ;
					}
				} else {
					//deck = 'Not viewable during the tournament' ;
					actions = 'No action' ;
				}
				//create_td(tr, deck) ;
				create_td(tr, actions) ;
			}
			var nbpl = parseInt(data.min_players) ;
			if ( data.players.length < nbpl ) {
				var tr = create_tr(players_table) ;
				var td = create_td(tr, (nbpl-data.players.length)+' open slots', 12) ;
				if ( ( t_status == 1 ) && ( me == null ) ) { // Tournament pending : registration form
					// Normal form for clients not trigering events
					var form = create_form('json/join.php', 'post', 
						create_hidden('id', tournament_id),
						create_hidden('nick', localStorage.profile_nick),
						create_hidden('avatar', localStorage.profile_avatar),
						create_hidden('deck', deck_get(localStorage['deck'])),
						create_submit('submit', 'Register')
					) ;
					form[0].value = tournament_id ; // Dunno why it's erased, overwrite ...
					td.appendChild(form) ;
					// Submit override for the form, replacing its sumbission by an AJAJ query
					form.addEventListener('submit', function(ev) {
						$.post(ev.target.action, {
							'id' : ev.target.id.value, 
							'nick' : ev.target.nick.value, 
							'avatar' : ev.target.avatar.value, 
							'deck' : ev.target.deck.value
						}, function(data) {
							if ( data.msg != '' )
								alert(data.msg) ;
							document.location = document.location ;
						}, 'json');
						ev.preventDefault() ;
						return false ;
					}, false) ;
				}
			}
		}
		// Display current round's score
		node_empty(current_round) ;
		var mygame = null ;
		if ( data.current_round ) {
			current_round.appendChild(create_h(2, 'Current round : '+data.round)) ;
			var table = document.createElement('table') ;
			current_round.appendChild(table) ;
			for ( var i in data.current_round ) {
				var game = data.current_round[i] ;
				var creator = player_get(data.players, game.creator_id) ;
				var joiner = player_get(data.players, game.joiner_id) ;
				var tr = game_table(creator, joiner, data, game, table, player_id) ;
				if ( ( joiner != null ) && ( ( player_id == creator.player_id ) || ( player_id == joiner.player_id ) ) ) {
					mygame = game ;
					tr.insertCell(tr.cells.length).appendChild(create_a('Play', '../play.php?id='+game.id)) ;
				} else 
					if ( game.joiner_id != '' ) // Not a BYE
						tr.insertCell(tr.cells.length).appendChild(create_a('View', '../play.php?id='+game.id)) ;
			}
		}
		// Display past round's score
		if ( iso(data.data) && iso(data.data.results) ) {
			node_empty(past_rounds) ;
			if ( data.data.results )
				past_rounds.appendChild(create_h(2, 'Past rounds')) ;
			for ( var i in data.data.results ) { // For each past round
				var round = data.data.results[i] ;
				past_rounds.appendChild(create_h(3, 'Round '+i)) ;
				var table = document.createElement('table') ;
				past_rounds.appendChild(table) ;
				for ( var j in round ) {
					var tr = game_table(
						player_get(data.players, round[j].creator_id)
						, player_get(data.players, round[j].joiner_id)
						, data
						, round[j]
						, table
						, player_id
					) ;
					if ( round[j].joiner_id != '' ) // Not a BYE
						tr.insertCell(tr.cells.length).appendChild(create_a('Replay', '../play.php?id='+round[j].id)) ;
				}
			}
		}
		// Manage top link
		var url = '' ;
		var toplink = document.getElementById('toplink') ;
		toplink.href = '' ;
		var notabene = '' ; ;
		html_status.value = 'Tournament status : '+tournament_status(data.status) ;
		var int_timeleft = parseInt(data.timeleft)
		var int_age = parseInt(data.age)
		var register = document.getElementById('register') ;
		register.textContent = '' ;
		switch ( t_status ) {
			case 1 : // Pending
				register.textContent = 'Registered' ;
				break ;
			case 0 : // Canceled
			case 2 : // Redirecting
				html_status.value += ' for ' + time_disp(int_age) ;
				break ;
			case 3 : // Drafting
				html_status.value += ' for ' + time_disp(int_age) ;
				url = 'draft.php?id='+tournament_id ;
				break ;
			case 4 : // Building
				html_status.value += ', ' + time_disp(int_timeleft) + ' remaining' ;
				url = 'build.php?id='+tournament_id ;
				break ;
			case 5 : // Tournament running
				html_status.value += ', ' + time_disp(int_timeleft) + ' remaining' ;
				if ( mygame != null ) {
					if ( player_id == mygame.creator_id )
						notabene = ' against '+mygame.joiner_nick ;
					else
						notabene = ' against '+mygame.creator_nick ;
					url = '../play.php?id='+mygame.id ;
				} else {
					notabene = ' against nobody. Yes, this is a bug' ;
					url = '' ;
				}
				break ;
			case 6 : // Ended
				html_status.value += ' for ' + time_disp(int_age) ;
				break ;
			default :
				html_status.value += ', ' + time_disp(int_age) + ' remaining' ;
				alert('Unknown status '+data.status+' ('+typeof data.status+')') ;
		}
		if ( me == null ) { // No player corresponding to client's ID : it's a spectactor
			toplink.textContent = 'Just watching' ;
		} else if ( parseInt(me.status) > 6 ) { // Player dropped or banned
			toplink.textContent = player_status(parseInt(me.status)) ;
		} else {
			// Redirection
			if ( url != '' ) { // Client should be on another page
				p_status = parseInt(me.status) ;
				toplink.href = url ;
				if ( p_status != t_status - 1 ) { // And did not already get there : redirect
					toplink.textContent = 'Redirecting' ;
					window.location.replace(url) ;
				} else // And has ever been ther : only show a link
					toplink.textContent = 'Currently, you should be '+tournament_status(p_status+1)+notabene ;
			}
		}
		if ( iso(rdata.log) ) {
			while ( rdata.log.length > 0 ) {
				line = rdata.log.shift() ;
				last_id = parseInt(line.id) ;
				pid = line.sender ;
				if ( line.type == 'spectactor' ) {
					var found = false
					for ( var j = 0 ; j < spectactors.length ; j++ )
						if ( spectactors[j].id == pid )
							found = true ;
					if ( ! found )
						spectactors.push({'id': pid, 'nick': line.value}) ;
				}
				if ( pid == '' )
					nick = 'Server' ;
				else {
					nick = pid ;
					for ( var j in data.players )
						if ( data.players[j].player_id == pid )
							nick = data.players[j].nick ;
					if ( nick == pid )
						for ( var j = 0 ; j < spectactors.length ; j++ )
							if ( spectactors[j].id == pid )
								nick = spectactors[j].nick ;
				}
				var msg = tournament_log_message(line, nick) ;
				tournament_log.appendChild(create_li((new Date(line.timestamp.replace(' ', 'T'))).toLocaleTimeString()+' '+msg)) ;
			}
		}
		window.setTimeout(timer, tournament_timer, tournament_id, player_id, data, last_id, false) ; // Refresh in 1 sec
	}) ;
}
function game_table(creator, joiner, data, game, table, player_id) {
	// Line
	var tr_game = create_tr(table) ;
	if ( creator != null )
		var td_c = create_td(tr_game, creator.nick) ;
	else
		var td_c = create_td(tr_game, '<i>Nick unknown</i>') ;
	if ( game.joiner_id != '' ) { // An opponent is present
		create_td(tr_game, game.creator_score+' - '+game.joiner_score) ;
		create_td(tr_game, joiner.nick) ;
	} else // BYE
		create_td(tr_game, 'BYE', 2) ;
	if ( ( ( creator != null ) && ( player_id == creator.player_id ) ) || ( ( joiner != null ) && ( player_id == joiner.player_id ) ) )
		tr_game.classList.add('self') ;
	if ( joiner != null )
		if ( player_id == joiner.player_id )
			tr_game.classList.add('self') ;
	return tr_game ;
}
