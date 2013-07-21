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
	if ( spectactor_is_allowed_forever(id) )
		msg += ', allowed forever' ;
	else if ( ! spectactor ) {
		var cb = create_checkbox('allow') ;
		span = create_span(
			create_button('Show hand', function(ev) {
				spec.allow() ;
				span.parentNode.removeChild(span) ;
				if ( cb.checked )
					spectactor_allow_forever(id, name) ;
			}), 
			create_label(null, cb, 'Forever')
		) ;
	}
	game.infobulle.set(msg) ;
	message(msg, 'join', span) ;
	if ( spectactor_is_allowed_forever(id) && ! spec.is_allowed(game.player) )
		spec.allow() ;
}
function spectactor_allowed_forever() {
	var allowed_str = game.options.get('allowed')
	if ( allowed_str == '' ) // Nobody allowed
		return [] ;
	else
		return allowed_str.split(',') ;
}
function spectactor_is_allowed_forever(id) {
	return ( spectactor_allowed_forever(id).indexOf(id) > -1 ) ;
}
function spectactor_allow_forever(id, name) {
	var allowed = spectactor_allowed_forever() ;
	allowed.push(id) ;
	game.options.set('allowed', allowed.join(',')) ;
	// Nick cache
	var anicks_str = game.options.get('allowed_nicks') ;
	var anicks = JSON_parse(anicks_str) ;
	if ( anicks == null )
		anicks = {} ;
	anicks[id] = name ;
	anicks_str = JSON.stringify(anicks) ;
	game.options.set('allowed_nicks', anicks_str) ;
}
function spectactor_unallow_forever(id) {
	var allowed = spectactor_allowed_forever() ;
	var i = allowed.indexOf(id) ;
	if ( i != -1 ) // Spectator found
		allowed.splice(i, 1) ;
	else
		alert('Spectator '+id+' not found') ;
	game.options.set('allowed', allowed.join(',')) ;
}
function spectator_select() {
	var select = create_select() ;
	select.title = 'Double click a spectator to un-allow' ;
	var spectactors = spectactor_allowed_forever() ;
	select.size = max(2, min(10, spectactors.length)) ; // At least 2 in order to display a full list, max 10 to stay inside screen
	var anicks_str = game.options.get('allowed_nicks') ;
	var anicks = JSON_parse(anicks_str) ;
	for ( var i = 0 ; i < spectactors.length ; i++ ) {
		var name = 'Not found' ;
		if ( anicks[spectactors[i]] )
			name = anicks[spectactors[i]] ;
		select.add(create_option(name, spectactors[i]))
	}
	select.addEventListener('dblclick', function(ev) {
		if ( ev.target.localName != 'option' )
			alert('Please double click a line') ;
		else {
			spectactor_unallow_forever(ev.target.value) ;
			ev.target.parentNode.parentNode.replaceChild(spectator_select(), ev.target.parentNode) ;
		}
	}, false) ;
	return select ;
}
