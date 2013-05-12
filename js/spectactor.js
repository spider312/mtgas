function Spectactor(id, name) {
	this.toString = function() {
		return 'Spectactor('+this.id+', '+this.name+')' ;
	}
	this.allow_recieve = function(player) {
		if ( this.is_allowed(player) )
			message(player.get_name()+' already allowed '+this.name, this.msgtype) ;
		else {
			this.allowed.push(player.id) ;
			if ( $.cookie(session_id) == this.id )  {
				var zone = player.hand ;
				zone.default_visibility = true ;
				for ( var i = 0 ; i < zone.cards.length ; i++ )
					zone.cards[i].load_image() ;
				message(player.get_name()+' allowed you', this.msgtype) ;
			} else
				message(player.get_name()+' allowed '+this.name, this.msgtype) ;
		}
	}
	this.allow = function() {
		action_send('allow', {'spectactor': this.id}) ;
		this.allow_recieve(game.player) ;
	}
	this.is_allowed = function(player) {
		return inarray(player.id, this.allowed) ;
	}
	// Self init
	this.id = id ;
	this.name = name ;
	this.msgtype = 'join' ;
	this.allowed = [] ; // Spectactors current player has allowed
	// Global init
	var spec = this ; // Local copy on which work, to modularize between spectactor creation and reutilisation
	if ( ! game.spectactors[id] ) { // New spectactor
		game.spectactors[id] = this ;
		msg = name+' has join as spectactor'
	} else { // Re-join
		spec = game.spectactors[id] ;
		msg = name+' has re-join as spectactor' ;
		if ( name != game.spectactors[id].name ) { // Under another name
			msg += ' under name '+name ;
			game.spectactors[id].name = name ;
		}
	}
	// Allow button
	var span = null ;
	var allowed_str = game.options.get('allowed')
	if ( allowed_str == '' ) // Nobody allowed
		var allowed = [] ;
	else
		var allowed = allowed_str.split(',') ;
	if ( allowed.indexOf(id) > -1 ) // Spectactor allowed forever
		msg += ', allowed forever' ;
	else if ( ! spectactor ) {
		var cb = create_checkbox('allow') ;
		span = create_span(
			create_button('Show hand', function(ev) {
				spec.allow() ;
				span.parentNode.removeChild(span) ;
				if ( cb.checked ) {
					allowed.push(id) ;
					game.options.set('allowed', allowed.join(',')) ;
				}
			}), 
			create_label(null, cb, 'Forever')
		) ;
	}
	game.infobulle.set(msg) ;
	message(msg, 'join', span) ;
	if ( ( allowed.indexOf(id) > -1 ) && ! spec.is_allowed(game.player) )
		spec.allow() ;
}
