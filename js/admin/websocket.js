function start(ev) { // On page load
	var connected_users = document.getElementById('connected_users') ;
	var bans = document.getElementById('bans') ;
	var pending_duels = document.getElementById('pending_duels') ;
	var joined_duels = document.getElementById('joined_duels') ;
	var pending_tournaments = document.getElementById('pending_tournaments') ;
	var running_tournaments = document.getElementById('running_tournaments') ;
	var mtg_data = document.getElementById('mtg_data') ;
	var refresh_mtg_data = document.getElementById('refresh_mtg_data') ;
	refresh_mtg_data.addEventListener('click', function(ev) {
		game.connection.send({'type': 'refresh_mtg_data'}) ;
	}, false) ;
	game = {} ;
	game.options = new Options(true) ;
	// Websockets
	game.connection = new Connexion('admin', function(data, ev) { // OnMessage
		switch ( data.type  ) {
			case 'overall' :
				clear() ;
				for ( var i in data.handlers )
					handler_li(i, data.handlers[i], connected_users) ;
				for ( var i = 0 ; i < data.bans.length ; i++ )
					bans.appendChild(ban_li(data.bans[i])) ;
				//fill_duel(pending_duels, data.pending_duels) ;
				//fill_duel(joined_duels, data.joined_duels) ;
				//fill_tournament(pending_tournaments, data.pending_tournaments) ;
				//fill_tournament(running_tournaments, data.running_tournaments) ;
				player_number(data, 'pending_duels') ;
				player_number(data, 'joined_duels') ;
				player_number(data, 'pending_tournaments') ;
				player_number(data, 'running_tournaments') ;
				mtg_data.appendChild(create_li('Extensions : '+data.extensions)) ;
				mtg_data.appendChild(create_li('Cards : '+data.cards)) ;
				break ;
			default : 
				debug('Unknown type '+data.type) ;
				debug(data) ;
		}
	}, clear);// OnClose/OnConnect
}
function clear() {
	node_empty(
		connected_users, bans,
		pending_duels, joined_duels,
		pending_tournaments, running_tournaments,
		mtg_data) ;
}
function ban_li(data) {
	var li = create_li(data.id+' : '+data.player_id+' : '+data.reason) ;
	var button = create_button('Unban', function(ev) {
		game.connection.send('{"type": "unban", "id": "'+data.id+'"}') ;
	}, 'Unban player') ;
	li.appendChild(button) ;
	return li ;
}
function handler_li(name, handler, node) {
	// User list
	var ul = create_ul() ;
	for ( var i = 0 ; i < handler.users.length ; i++ )
		ul.appendChild(player_li(handler.users[i], name)) ;
	// Handler LI
	var li = create_li(name) ;
	li.appendChild(ul) ;
	node.appendChild(li) ;
}
function player_li(user, handler) {
	var li = create_li(user.nick) ;
	li.title = user.player_id ;
	var button = create_button('Kick', function(ev) {
		var data = '{"type": "kick", "handler": "'+handler+'", "id": "'+user.player_id+'"}';
		game.connection.send(data) ;
	}, 'Disconnect user') ;
	li.appendChild(button) ;
	var input_reason = create_input('reason', '', null) ;
	li.appendChild(input_reason) ;
	var button = create_button('Ban ID', function(ev) {
		var reason = input_reason.value ;
		if ( reason == '' ) 
			alert('Type a reason') ;
		else {
			var data = '{"type": "ban", "id": "'+user.player_id+'", "reason": "'+reason+'"}';
			game.connection.send(data) ;
		}
	}, 'Ban player by its ID ('+user.player_id+')') ;
	li.appendChild(button) ;
	return li ;
}
function player_number(data, fieldname) {
	var field = document.getElementById(fieldname+'_input') ;
	field.value = data[fieldname] ;
}
function fill_duel(node, datas) {
	if ( datas.length < 1 )
		return node.appendChild(create_li('none')) ;
	var goldfish = 0 ;
	for ( var i = 0 ; i < datas.length ; i++ ) {
		var data = datas[i] ;
		if ( data.creator_id == data.joiner_id ) { // Goldfish
			goldfish++
			continue ;
		}
		if ( data.tournament > 0 ) // Tournament
			continue ;
		var li = create_li(data.id+' : '+data.name) ;
		var ul = create_ul() ;
		if ( data.creator_status > 0 )
		ul.appendChild(player_li({'player_id': data.creator_id, 'nick': data.creator_nick}, 'game')) ;
		else
			ul.appendChild(create_li(data.creator_nick)) ;
		if ( data.joiner_status > 0 )
		ul.appendChild(player_li({'player_id': data.joiner_id, 'nick': data.joiner_nick}, 'game')) ;
		else
			ul.appendChild(create_li(data.joiner_nick)) ;
		li.appendChild(ul) ;
		node.appendChild(li) ;
	}
	if ( goldfish > 0 )
		node.appendChild(create_li('+ '+goldfish+' goldfish'))
}
function fill_tournament(node, datas) {
	if ( datas.length < 1 )
		node.appendChild(create_li('none')) ;
	else
		for ( var i = 0 ; i < datas.length ; i++ ) {
			var data = datas[i] ;
			var name = data.id+' : '+data.name
			if ( iso(data.data.boosters) )
				name += ' ('+data.data.boosters.join('-')+')' ;
			name += ' '+data.min_players+'p' ;
			var li = create_li(name) ;
			var ul = create_ul() ;
			for ( var i = 0 ; i < data.players.length ; i++)
				ul.appendChild(player_li(data.players[i], data.players[i].connected[0])) ;
			li.appendChild(ul) ;
			if ( data.type == 'running_tournament' ) {
				var input = create_input('due_time', data.due_time) ;
				var form = create_form() ;
				form.id = data.id
				form.addEventListener('submit', function(ev) {
					game.connection.send('{"type": "tournament_set", "id": '+ev.target.id+
						', "due_time": "'+ev.target.due_time.value+'"}') ;
					return eventStop(ev) ;
				}, false) ;
				form.appendChild(input) ;
				li.appendChild(form) ;
			}
			node.appendChild(li)
		}
}
