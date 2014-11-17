// tournament/lib.js
function tournament_status(stat) {
	var result = 'Initializing' ;
	switch ( parseInt(stat) ) {
		case 0 :
			result = 'Canceled' ;
			break;
		case 1 :
			result = 'Pending' ;
			break;
		case 2 :
			result = 'Waiting players' ;
			break;
		case 3 :
			result = 'Drafting' ;
			break;
		case 4 :
			result = 'Building' ;
			break;
		case 5 :
			result = 'Playing' ;
			break;
		case 6 :
			result = 'Ended' ;
			break;
		default : 
			result = 'Unknown status : '+stat ;
	}
	return result ;
}
function tournament_constructed(type) {
	switch ( type ) {
		case 'draft' :
		case 'sealed' :
			return false ;
		case 'vintage' :
		case 'legacy' :
		case 'modern' :
		case 'extend' :
		case 'standard' :
		case 'edh' :
			return true ;
		default :
			return null ;
	}
}
// Class
function Tournament(id) {
	this.id = id ;
	this.fields = ['id', 'creation_date', 'format', 'name', 'min_players', 'status', 'round', 'update_date', 'due_time', 'data', 'games'] ;
	this.players = [] ;
	this.me = null ;
	this.logs = [] ;
	this.hook_recieve = [] ;
	if ( isf(this.recieved) )
		this.hook_recieve.push(this.recieved) ;
	if ( isf(this.display) )
		this.hook_recieve.push(this.display) ;
	this.recieve = function(data) {
		// Players
		for ( var i = 0 ; i < data.players.length ; i++ ) {
			var player = this.get_player(data.players[i].player_id) ;
			if ( player == null ) {
				player = new Player(data.players[i])
				this.players.push(player) ;
			} else
				player.recieve(data.players[i]) ;
			if ( player.me ) {
				this.me = player ;
				spectactor = false ;
				game.player = player ;
			}
		}
		// Spectators
		for ( var i = 0 ; i < data.spectators.spectators.length ; i++ ) {
			var spectator = data.spectators.spectators[i] ;
			game.spectators.add(spectator.player_id, spectator.nick) ;
		}
		var button = document.getElementById('spectators') ;
		if ( button != null )
			button.disabled = ( i == 0 ) ;
		// Logs
		for ( var i = 0 ; i < data.logs.length ; i++ ) {
			var lid = data.logs[i].id ;
			if ( this.logs[lid] )
				this.logs[lid].recieve(data.logs[i]) ;
			else
				this.logs[lid] = new Log(data.logs[i]) ;
		}
		// Fields + display
		recieve.call(this, data) ; // After the rest as it will access to players
		if ( isf(this.display) )
			this.display(['players', 'logs']) ;
	}
	this.get_player = function(id) {
		for ( var i = 0 ; i < this.players.length ; i++ )
			if ( id == this.players[i].player_id )
				return this.players[i] ;
		return null ;
	}
	this.toString = function() { return 'Tournament('+this.id+')' ; }
	// Initialise chat
	var chat = document.getElementById('chat')
	chat.addEventListener('submit', function(ev) {
		ev.preventDefault() ;
		ev.target.msg.focus() ;
		if ( ev.target.msg.value == '' )
			return false ;
		game.connection.send('{"type": "msg", "msg": '+JSON.stringify(ev.target.msg.value)+'}') ;
		ev.target.msg.value = '' ;
	}, false) ;
	chat.focus() ;
}
function Player(data) {
	// Methods
	this.recieve = recieve ;
	this.avatar_url = function() {
		if ( this.avatar.substr(0, 4) == 'http' )
			return this.avatar ;
		else
			return '../'+this.avatar ;
	}
	this.verbose_status = function() {
		var statuses = ['Waiting', 'Redirecting', 'Drafting', 'Building', 'Playing',
			'Ended', 'BYE', 'Dropped'] ;
		var idx = parseInt(this.status) ;
		if ( statuses[idx] ) {
			var result = statuses[idx] ;
			if ( this.ready == 1 )
				result = 'Finished '+result ;
		} else
			return  'Unknown status : '+this.status ;
		return result ;
	}
	this.toString = function() { return 'Player('+this.nick+')' ; }
	this.get_name = function() { return this.nick ; }
	// Init
	this.fields = ['player_id', 'nick', 'avatar', 'deck', 'deck_obj', 'deck_cards', 'side_cards',
		'order', 'type', 'status', 'ready'] ;
	this.node = null ;
	this.me = ( data.player_id == player_id ) ;
	this.hook_recieve = [] ;
	if ( isf(this.recieved) )
		this.hook_recieve.push(this.recieved) ;
	if ( isf(this.display) )
		this.hook_recieve.push(this.display) ;
	this.recieve(data) ;
	this.id = this.player_id ;
}
function Log(data) {
	this.fields = ['id', 'sender', 'type', 'value', 'timestamp'] ;
	this.node = null ;
	this.recieve = recieve ;
	this.toString = function() { return 'Log('+this.id+')' ; }
	this.update_node = function(node, parentNode) {
		if ( this.node == null )
			parentNode.appendChild(node) ;
		else
			this.node.parentNode.replaceChild(node, this.node) ;
		this.node = node ;
	}
	this.generate = function() {
		var nick = this.sender ;
		if ( nick == '' )
			nick = 'Server' ;
		else {
			var p = game.tournament.get_player(this.sender) ;
			if ( p )
				nick = p.nick ;
			else {
				var s = game.spectators.get(this.sender) ;
				if ( s != null )
					nick = s.name ;
			}
		}
		var span = create_span() ;
		var li = create_li(span) ;
		li.title = mysql2date(this.timestamp).toLocaleTimeString() ;
		switch ( this.type ) {
			case 'create' :
				if ( iss(this.value) && ( this.value != '' ) )
					nick = this.value ;
				else
					nick = '['+nick+']' ;
				msg = 'Tournament created by '+nick ;
				break ;
			case 'register' :
				if ( iss(this.value) && ( this.value != '' ) )
					nick = this.value ;
				else
					nick = '['+nick+']' ;
				msg = nick+' registered' ;
				break ;
			case 'unregister' :
				if ( iss(this.value) && ( this.value != '' ) )
					nick = this.value ;
				else
					nick = '['+nick+']' ;
				msg = nick+' unregistered' ;
				break ;
			case 'players' :
				msg = 'Tournament has enough players' ;
				break ;
			case 'pending' :
				msg = 'All players not redirected, back to pending' ;
				break ;
			case 'drop' :
				msg = nick+' droped tournament' ;
				if ( iss(this.value) && ( this.value != '' ) )
					msg += ' ('+this.value+')' ;
				break
			case 'spectactor' :
				msg = nick+' joined as spectactor' ;
				if ( iso(s) && ( game.tournament.get_player(player_id) != null ) )
					li.insertBefore(s.allow_span('Allow'), span.nextSibling);
				break ;
			case 'allow' :
				var s = game.spectators.get(this.value) ;
				if ( s != null )
					s.allow(p) ;
				msg = nick+' allowed '+s.name ;
				break ;
			case 'draft' :
				msg = 'Draft started' ;
				break ;
			case 'build' :
				msg = 'Build started' ;
				startdate = mysql2date(this.timestamp) ;
				break ;
			case 'save' :
				msg = nick+' saved a deck' ;
				break ;
			case 'ready' :
				if ( this.value == '1' )
					msg = nick+' is ready' ;
					// ('+time_disp(Math.round((mysql2date(this.timestamp)-startdate)/1000))+')' ;
				else
					msg = nick+' isn\'t ready anymore' ;
				break ;
			case 'start' :
				msg = 'Tournament started' ;
				break ;
			case 'win' :
				msg = nick+' won its match' ;
				break ;
			case 'round' :
				msg = 'Round '+this.value+' started' ;
				break ;
			case 'end' :
				msg = 'Tournament ended' ;
				if ( this.sender != '' ) // Old fashion winner
					msg += ', congratulations to '+nick ;
				else
					msg += ', can\'t fnid a winner' ;
				break ;
			case 'cancel' :
				msg = 'Tournament canceled : '+this.value ;
				break ;
			case 'msg' :
				msg = nick+': '+this.value ;
				li.classList.add('chat') ;
				break ;
			default :
				msg = this.type+' : '+this.value+' (raw)' ;
		}
		span.appendChild(create_text(msg)) ;
		return li ;
	}
	this.hook_recieve = [] ;
	if ( isf(this.recieved) )
		this.hook_recieve.push(this.recieved) ;
	if ( isf(this.display) )
		this.hook_recieve.push(this.display) ;
	this.recieve(data) ;
}
function recieve(data) { // Recieved updated object
	var changed = [] ;
	for ( var i = 0 ; i < this.fields.length ; i++ ) {
		var field = this.fields[i] ;
		if ( ! isset(data[field]) ) // Didn't recieved wanted field (recieved only deck)
			continue ;
		if ( iso(this[field]) && iso(data[field]) ) { // Compare JSON way
			var lf = JSON.stringify(this[field]) ;
			var df = JSON.stringify(data[field]) ;
		} else {
			var lf = this[field] ;
			var df = data[field] ;
		}
		if ( lf != df ) {
			this[field] = data[field] ; // Update field
			changed.push(field) ;
		}
	}
	for ( var i = 0 ; i < this.hook_recieve.length ; i++ ) {
		var func = this.hook_recieve[i] ;
		if ( isf(func) )
			func.call(this, changed) ;
		else
			debug(func+' is not a function') ;
	}
	return changed ;
}
