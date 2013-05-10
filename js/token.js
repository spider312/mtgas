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
	for ( var i = 0 ; i < nb ; i++ ) {
		action_send('token', {'zone': zone.toString(), 'ext': ext, 'name': token_name(name, ext), 'attrs': attrs}, function(data) {
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
function token_extention(img, ext) { // Search token in extensions
	if ( ! iso(game.tokens_catalog) ) // No catalog ?
		return ext ;
	if ( iso(game.tokens_catalog[ext]) && iss(game.tokens_catalog[ext][img]) ) // Ext existing in catalog, and token existing in ext
		return ext ;
	if ( iso(game.tokens_catalog['EXT']) && iss(game.tokens_catalog['EXT'][img]) ) // Token existing in special extension
		return 'EXT' ;
	var tokens_catalog = clone(game.tokens_catalog) ;
	delete tokens_catalog['EXT'] ; // Don't search special extension
	// Sort extension to rewind list from ext, then forward list from ext
	var found = false ;
	var exts_b = [] ; // Exts that are before 'ext'
	var exts_a = [] ; // Exts that are after 'ext'
	for ( var i in tokens_catalog ) { // Each extension, sorted by release date, "ext" at the end
		if ( i == ext ) { // Switch between first and second pass
			found = true ;
			continue ; // Searched token isn't in current extension, it was the first check in this function
		}
		if ( ! iss(tokens_catalog[i][img]) ) // Token isn't in extension
			continue ;
		if ( ! found ) // First 'pass' : extensions before 'ext'
			exts_b.unshift(i) ;
		else // Second pass : extensions after 'ext'
			exts_a.push(i) ;
	}
	var exts = exts_b.concat(exts_a) ;
	for ( var i in exts ) // Now 
		if ( iss(tokens_catalog[exts[i]][img]) )
			return exts[i] ;
	log(img+' found in no extension') ;
	return '' ;
}
function token_name(name, ext) { // Modify name depending on extension if needed (adds random numbers to tokens having multiple images)
	if ( name == 'Eldrazi Spawn' )
		name += ( rand(3) + 1 ) ;
	if ( ( name == 'Zombie' ) && ( ( ext == 'ISD' ) || ( ext == 'DKA' ) ) )
		name += ( rand(3) + 1 ) ;
	return name ;
}
function token_image_name(name, ext, attrs) { // Returns an image name from a token
	name = token_name(name, ext) ;
	if ( isn(attrs.pow) && isn(attrs.thou) ) // Emblems doesn't have them
		name += '.'+attrs.pow+'.'+attrs.thou ;
	name += '.jpg' ;
	return name ;
}
function token_image_url(ext, name, attrs) { // Returns an image's full URL
	return '/TK/'+ext+'/'+token_image_name(name, ext, attrs) ;
}
