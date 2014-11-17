$(function() { // On page load
	var pending_duels = document.getElementById('pending_duels') ;
	var joined_duels = document.getElementById('joined_duels') ;
	var pending_tournaments = document.getElementById('pending_tournaments') ;
	var running_tournaments = document.getElementById('running_tournaments') ;
	var connected_users = document.getElementById('connected_users') ;
	game = {} ;
	game.options = new Options(true) ;
	// Websockets
	game.connection = new Connexion('admin', function(data, ev) { // OnMessage
		switch ( data.type  ) {
			case 'overall' :
				fill_duel(pending_duels, data.pending_duels) ;
				fill_duel(joined_duels, data.joined_duels) ;
				fill_tournament(pending_tournaments, data.pending_tournaments) ;
				fill_tournament(running_tournaments, data.running_tournaments) ;
				for ( var i in data.handlers ) {
					var handler = data.handlers[i] ;
					var ul = create_ul() ;
					for ( var j = 0 ; j < handler.users.length ; j++ ) {
						var user = handler.users[j] ;
						var li = create_li(user.nick) ;
						li.title = user.player_id ;
						var button = create_button('Kick', function(ev) {
							var data = '{"type": "kick", "handler": "'+i+'", "id": "'+user.player_id+'"}';
							game.connection.send(data) ;
						}, 'Disconnect user') ;
						for ( var k in user )
							if ( ( k != 'nick' ) && ( k != 'player_id' ) )
								li.appendChild(create_text(', '+k+' = '+user[k])) ;
						li.appendChild(button) ;
						ul.appendChild(li) ;
					}
					var li = create_li(i) ;
					li.appendChild(ul) ;
					connected_users.appendChild(li) ;
				}
				break ;
			default : 
				debug('Unknown type '+data.type) ;
				debug(data) ;
		}
	}, function(ev) { // OnClose/OnConnect
		node_empty(pending_duels, joined_duels, pending_tournaments, running_tournaments) ;
	}) ;
})
function fill_duel(node, datas) {
	if ( datas.length < 1 )
		node.appendChild(create_li('none')) ;
	else
		for ( var i = 0 ; i < datas.length ; i++ ) {
			var data = datas[i] ;
			var li = create_li(data.name+' : '+data.creator_nick+' / '+data.joiner_nick) ;
			node.appendChild(li)
		}
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
