// tournament/index.js
function start(tournament_id) {
	tournament_info = document.getElementById('tournament_info') ;
	html_status = document.getElementById('status') ;
	timeleft = document.getElementById('timeleft') ;
	players_table = document.getElementById('players_table') ;
	rounds = document.getElementById('rounds') ;
	tournament_log = document.getElementById('tournament_log') ;
	Tournament.prototype = new TournamentIndex() ;
	Player.prototype = new PlayerIndex() ;
	Log.prototype = new LogIndex() ;
	game = {}
	game.options = new Options(true) ;
	game.tournament = new Tournament(tournament_id) ;
	// Spectators
	spectactor = true ;
	game.spectators = new Spectators(function(msg, span) { // Message display
		// Messages are managed via the "log" mechanism which triggers after spectator recieving
		//debug(msg) ;
	}, function(id) { // I'm a player allowing a spectator
		game.connection.send('{"type": "allow", "value": "'+id+'"}') ;
	}, function(player) { // I'm allowed spectator
		var p = game.tournament.get_player(player.player_id) ;
		if ( p != null )
			p.display() ;
	}) ;
	var button = document.getElementById('spectators') ;
	button.addEventListener('click', function(ev) {
		var menu = game.spectators.menu() ;
		menu.start(ev) ;
	}, false) ;
	// Websockets
	game.connection = new Connexion('tournament', function(data, ev) { // OnMessage
		switch ( data.type ) {
			// Base
			case undefined :
			case 'msg' :
				if ( iss(data.msg) )
					debug(data.msg) ;
				else
					debug(data) ;
				break ;
			case 'pending_tournament' :
			case 'running_tournament' :
			case 'ended_tournament' :
				game.tournament.recieve(data) ;
				break ;
			case 'redirect' :
				switch ( parseInt(game.tournament.status) ) {
					case 2 : // Waiting for other players to be redirected
						break ;
					case 3 :
						window.location.replace('draft.php?id='+tournament_id) ;
						break ;
					case 4 :
						window.location.replace('build.php?id='+tournament_id) ;
						break ;
					case 5 :
						window.location.replace('../play.php?id='+data.game) ;
						break ;
					default :
						debug('Unmanaged status '+game.tournament.status) ;
				}
				break ;
			case 'save' :
				var name = data.name ;
				name = prompt('Deck name', name) ;
				deck_set(name, data.deck) ;
				break ;
			default : 
				debug('Unknown type '+data.type+' : '+JSON.stringify(data)) ;
		}
	}, function(ev) { // OnClose/OnConnect
		// Clear everything
		node_empty(html_status) ;
		node_empty(players_table) ;
		node_empty(rounds) ;
		node_empty(tournament_log) ;
	}, {'tournament': tournament_id}) ;
	var drop = document.getElementById('drop')
	drop.addEventListener('click', function (ev) {
		game.connection.send('{"type": "drop"}') ;
	}, false) ;
}
function TournamentIndex() {
	this.display_field = function(fields, field, nodeId) {
		if ( inarray(field, fields) ) {
			var node = document.getElementById(nodeId) ;
			if ( node == null ) {
				debug('node '+nodeId+' not found') ;
				return false ;
			}
			var value = ''+this[field] ;
			node_empty(node) ;
			node.appendChild(create_text(value)) ;
		}
	}
	this.display = function(fields) {
		this.display_field(fields, 'name', 'tournament_name') ;
		this.display_field(fields, 'format', 'tournament_format') ;
		this.display_field(fields, 'min_players', 'tournament_player_nb') ;
		if ( inarray('data', fields) ) {
			var fieldnames = {	'clone_sealed' : 'Clone sealed',
								'boosters' : 'Boosters',
								'rounds_duration' : 'Rounds duration (minutes)',
								'rounds_number' : 'Number of rounds'} ;
			node_empty(tournament_info) ;
			for ( var i in this.data ) {
				if ( inarray(i, ['players', 'results', 'score']) )
					continue ;
				var str = i ;
				if ( iss(fieldnames[i]) )
					str = fieldnames[i] ;
				var div = create_div(str)
				if ( ! isb(this.data[i]) ) {
					div.appendChild(create_text(' : ')) ;
					div.appendChild(create_element('strong', ''+this.data[i])) ;
				} else if ( ! this.data[i] )
					continue ;
				tournament_info.appendChild(div) ;
			}
		}
		if ( inarray('status', fields) ) {
			if ( this.status == 1 )
				window.location.replace('../') ;
			node_empty(html_status) ;
			var str = tournament_status(this.status)
			switch ( this.status ) {
				case 5:
					str += ' (round '+this.round+')' ;
					break ;
				case 6:
					html_status.nextSibling.nodeValue = ' for ' ;
					break ;
				default :
			}
			html_status.appendChild(create_text(str)) ;
		}
		if ( inarray('creation_date', fields) ) {
			var tournament_created = document.getElementById('tournament_created') ;
			var date = mysql2date(this.creation_date) ;
			tournament_created.appendChild(create_text(date.toLocaleString())) ;
		}
		if ( inarray('due_time', fields) )
			start_timer(timeleft, this.due_time, this.status!=6) ;
		if ( inarray('players', fields) )
			for ( var i = 0 ; i < this.players.length ; i++ )
				this.players[i].display() ;
		if ( inarray('games', fields) && ( this.games.length > 0 ) ) {
			rounds.parentNode.classList.remove('hidden') ;
			node_empty(rounds) ;
			for ( var i = this.games.length-1 ; i >= 0 ; i-- ) {
				rounds.appendChild(create_h(3, 'Round '+(i+1)+' : ')) ;
				var table = create_element('table') ;
				var round = this.games[i] ;
				for ( var j = 0 ; j < round.length ; j++ ) {
					var game = round[j] ;
					var tr = table.insertRow() ;
					tr.insertCell(-1).appendChild(create_text(game.creator_nick)) ;
					tr.insertCell(-1).appendChild(create_text(game.creator_score+' - '+game.joiner_score)) ;
					tr.insertCell(-1).appendChild(create_text(game.joiner_nick)) ;
					var action = 'View' ;
					if ( ( player_id == game.creator_id ) || ( player_id == game.joiner_id ) ) {
						tr.classList.add('self') ;
						action = 'Play' ;
					}
					tr.insertCell(-1).appendChild(create_a(action, '/play.php?id='+game.id)) ;
				}
				rounds.appendChild(table) ;
			}
		}
	}
}
function PlayerIndex() {
	this.display = function(fields) {
		if ( this.node == null )
			this.node = create_tr(players_table) ;
		else
			node_empty(this.node) ;
		this.node.player = this ;
		create_td(this.node, this.order) ;
		var td = create_td(this.node,
			create_a(this.nick, '../player.php?id='+this.player_id)) ;
		var img = player_avatar(this.avatar_url(), null, null, '../') ;
		img.classList.add('big') ;
		create_td(this.node, img) ;
		var st = create_td(this.node, this.verbose_status()) ;
		st.appendChild(this.connection()) ;
		var tr = this.node ; // Legacy
		if ( iso(game.tournament.data) && iso(game.tournament.data.score) ) {
			if ( game.tournament.data.score[this.player_id] ) {
				var score = game.tournament.data.score[this.player_id] ;
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
			} else
				create_td(tr, 'Player has no score Oo', 6) ;
		} else
			create_td(tr, 'Not available before first round\'s end', 5) ;
		// Deck
		create_td(this.node, this.deck_cards+' / '+this.side_cards+' cards') ;
		// Actions
		var allowed = ( player_id == this.player_id ) ; // Player can see its own deck
		allowed = allowed || ( game.tournament.status > 5 ) ; // At the end, everybody can see everything
		if ( ! allowed ) {
			var s = game.spectators.get(player_id) ;
			if ( ( s != null ) && ( s.allowed(this.player_id) ) )
				allowed = true ;
		}
		if ( allowed ) {
			var button_view = create_submit('view', 'View') ;
			button_view.title = 'View deck while player builds it' ;
			var view_form = create_form('build.php', 'get'
				, create_hidden('id', game.tournament.id)
				, create_hidden('pid', this.player_id)
				, button_view
			) ;
			var td = create_td(this.node, view_form) ;
			var player = this ;
			var button_save = create_button('Save as ...', function(ev) {
				game.connection.send('{"type": "save", "player": "'+player.player_id+'"}') ;
			}, 'Save deck in decklist') ;
			td.appendChild(button_save) ;
		} else
			create_td(this.node, 'none') ;
		if ( player_id == this.player_id )
			this.node.classList.add('self') ;
	}
}
function LogIndex() {
	this.display = function(fields) {
		if ( fields.length == 0 )
			return false ;
		var li = this.generate() ;
		var span = create_span(timeWithDays(mysql2date(this.timestamp))) ;
		span.classList.add('linetime') ;
		li.insertBefore(span, li.firstChild) ;
		this.update_node(li, tournament_log) ;
	}
}
