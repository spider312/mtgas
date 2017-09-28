function Spectators(msg_func, allow_func, allowed_func) {
	this.spectators = [] ;
	this.msg_func = msg_func ;
	this.allow_func = allow_func ;
	this.allowed_func = allowed_func ;
	this.add = function(id, name) {
		var s = this.get(id) ;
		if ( s == null ) {
			s = new Spectator(this, id, name) ;
			this.spectators.push(s) ;
			var msg = 'New spectator : '+name ;
			var imspectator = ( this.get(player_id) != null ) ;
			if  ( ! imspectator && spectactor_is_allowed_forever(id) ) {
				this.msg_func(msg+' (alowed forever)') ;
				this.allow(id) ;
			} else {
				var span = null ;
				if ( ! imspectator )
					span = s.allow_span('Show hand') ;
				this.msg_func(msg, span) ;
			}
		} else
			this.msg_func('Spectator '+s.name+' already added') ;
		return s ;
	}
	this.get = function(id) {
		for ( var i = 0 ; i < this.spectators.length ; i++ ) 
			if ( this.spectators[i].id == id )
				return this.spectators[i] ;
		return null ;
	}
	this.is_allowed_by = function(spectator_id, player_id) {
		var spec = this.get(spectator_id) ;
		return ( spec === null ) ? false : spec.allowed(player_id) ;
	}
	this.allow = function(spectator_id) { // Send
		var s = this.get(spectator_id) ;
		if ( s == null )
			this.msg_func('Unable to find spectator to allow') ;
		else {
			this.allow_func(s.id) ;
			s.allow(game.player) ;
		}
	}
	this.menu = function() {
		var specmenu = new menu_init(this) ;
		for ( var i in game.spectators.spectators ) {
			var spec = game.spectators.spectators[i] ;
			var name = spec.name ;
			if ( ! spec.connected ) name += ' (off)' ;
			else if ( ! spec.focused ) name += ' (out)' ;
			var a = false ;
			if ( iso(game.player) )
				a = spec.allowed(game.player.id) ;
			if ( a || spectactor )
				var line = specmenu.addline(name) ;
			else
				var line = specmenu.addline(name, this.allow, spec.id) ;
			if ( ! spectactor )
				line.checked = a ;
		}
		return specmenu ;
	}
}
function Spectator(container, id, name) {
	// Init
	this.container = container ;
	this.id = id ;
	this.name = name ;
	this.allowed_players = [] ;
	this.connected = ( this.id == player_id ) ;
	this.focused = true ;
	// Methods
	this.toString = function() { return 'Spectactor('+this.id+', '+this.name+')' ; }
	this.allowed = function(player_id) { return inarray(player_id, this.allowed_players) ; }
	this.allow_span = function(value) {
		var cb = create_checkbox('allow') ;
		var that = this ;
		var span = create_span(
			create_button(value, function(ev) {
				game.spectators.allow(that.id) ;
				if ( cb.checked )
					spectactor_allow_forever(that.id, that.name) ;
			}), 
			create_label(null, cb, 'Forever')
		) ;
		span.classList.add('allow_'+that.id) ;
		return span ;
	}
	this.allow = function(player) { // Recieve
		if ( player == null )
			return false ;
		if ( this.allowed(player.id) )
			this.container.msg_func('already allowed') ;
		else {
			this.allowed_players.push(player.id) ;
			var msg = player.get_name()+' allowed ' ;
			if ( player_id == this.id ) { // I am allowed spectator
				this.container.allowed_func(player) ;
				var msg = msg+'you' ;
			} else
				var msg = msg+this.name ;
			this.container.msg_func(msg) ;
		}
		if ( player_id == player.id ) { // I am allowing player
			var spans = document.getElementsByClassName('allow_'+this.id) ;
			for ( var i = 0 ; i < spans.length ; i++ ) {
				var span = spans[i] ;
				span.parentNode.removeChild(span) ;
			}

		}
	}
	this.connect = function(val) { this.connected = val ; }
	this.focus = function(val) { this.focused = val ; }
}
