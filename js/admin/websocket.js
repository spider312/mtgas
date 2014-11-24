$(function() { // On page load
	var pending_duels = document.getElementById('pending_duels') ;
	var joined_duels = document.getElementById('joined_duels') ;
	var pending_tournaments = document.getElementById('pending_tournaments') ;
	var running_tournaments = document.getElementById('running_tournaments') ;
	var connected_users = document.getElementById('connected_users') ;
	var mtg_data = document.getElementById('mtg_data') ;
	game = {} ;
	game.options = new Options(true) ;
	// Websockets
	game.connection = new Connexion('admin', function(data, ev) { // OnMessage
		switch ( data.type  ) {
			case 'overall' :
				for ( var i = 0 ; i < data.handlers.index.users.length ; i++ )
					connected_users.appendChild(player_li(data.handlers.index.users[i], 'index')) ;
				fill_duel(pending_duels, data.pending_duels) ;
				fill_duel(joined_duels, data.joined_duels) ;
				fill_tournament(pending_tournaments, data.pending_tournaments) ;
				fill_tournament(running_tournaments, data.running_tournaments) ;
				mtg_data.appendChild(create_li('Extensions : '+data.extensions)) ;
				mtg_data.appendChild(create_li('Cards : '+data.cards)) ;
				debug(data) ;
				break ;
			default : 
				debug('Unknown type '+data.type) ;
				debug(data) ;
		}
	}, function(ev) { // OnClose/OnConnect
		node_empty(mtg_data, pending_duels, joined_duels, pending_tournaments, running_tournaments) ;
	}) ;
})
function player_li(user, handler) {
	var li = create_li(user.nick) ;
	li.title = user.player_id ;
	var button = create_button('Kick', function(ev) {
		var data = '{"type": "kick", "handler": "'+handler+'", "id": "'+user.player_id+'"}';
		game.connection.send(data) ;
	}, 'Disconnect user') ;
	li.appendChild(button) ;
	return li ;
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
		debug(data) ;
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
			var li = create_li('#'+data.id+' : '+data.name) ;
			var input = create_input('due_time', data.due_time) ;
			var form = create_form() ;
			form.id = data.id
			form.addEventListener('submit', function(ev) {
				game.connection.send('{"type": "tournament_set", "id": '+ev.target.id+', "due_time": "'+ev.target.due_time.value+'"}') ;
				return eventStop(ev) ;
			}, false) ;
			form.appendChild(input) ;
			li.appendChild(form) ;
			node.appendChild(li)
		}
}
