// token.js
// Classes for tokens and card duplicates, extends classes 'card' defined in card.js
function Token(id, extension, name, zone, attrs, img_url) {
	Widget(this) ;
	this.type = 'token' ; // Used in right DND
	if ( ! iso(attrs.types) ) {
		log('No types found for token '+name) ;
		attrs.types = ['creature'] ;
	}
	if ( ! iso(attrs.subtypes) )
		attrs.subtypes = name.toLowerCase().split(' ') ;
	this.init('t_' + id, extension, name, zone.player.life, attrs) ; // Token will give impression it comes from zone 'life'
	this.image_url = img_url ;
	this.bordercolor = 'SlateGray' ;
	this.setzone(zone) ;
	this.zone.refresh_pt(iso(this.attrs.boost_bf)) ; // Boost_bf is applied on changezone, apply it there
	game.tokens.push(this) ; // Register it in order to delete on game end
	this.get_name = function() {
		return 'token '+this.name ; // Defaults to token, overridden by duplicate
	}
}
function create_token(ext, name, zone, attrs, nb, oncreate, oncreateparam) { // Modularisation between custom token creation and previous tokens recalling menu
	if ( ! isn(nb) )
		nb = 1 ;
	var tmpname ;
	for ( var i = 0 ; i < nb ; i++ ) {
		tmpname = name ;
		if ( tmpname == 'Eldrazi Spawn' ) // No number specified, add one at random
			tmpname += ( rand(3) + 1 ) ;
		if ( ( tmpname == 'Zombie' ) && ( ( ext == 'ISD' ) || ( ext == 'DKA' ) ) )
			tmpname += ( rand(3) + 1 ) ;
		action_send('token', {'zone': zone.toString(), 'ext': ext, 'name': tmpname, 'attrs': attrs}, function(data) {
			var tk = create_token_recieve(data.id, data.param.ext, data.param.name, eval(data.param.zone), JSON_parse(data.param.attrs)) ;
			if ( typeof oncreate == 'function' )
				oncreate(tk, oncreateparam) ; // ATM : Living weapon
			tk.place(0, tk.place_row()) ; // Place after equipping for living weapon
		}) ;
	}
}
function create_token_recieve(id, ext, name, zone, attrs) {
	tk = new Token(id, ext, name, zone, attrs, token_image_url(ext, name, attrs)) ;
	message(active_player.name+' creates '+tk.get_name(), 'zone') ;
	return tk ;
}
