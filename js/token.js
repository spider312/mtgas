// token.js
// Classes for tokens and card duplicates, extends classes 'card' defined in card.js
function Token(id, extension, name, zone, attrs, register) {
	this.type = 'token' ; // Used in right DND
	if ( ! iso(attrs.types) ) {
		log('No types found for token '+name) ;
		attrs.types = ['creature'] ;
	}
	if ( ! iso(attrs.subtypes) )
		attrs.subtypes = name.toLowerCase().split(' ') ;
	this.init('t_' + id, extension, name, zone.player.life, attrs, [extension]) ;
	//this.image_url = img_url ;
	this.bordercolor = 'SlateGray' ;
	this.setzone(zone) ; // Don't know where to place, but it's in battlefield !
	this.zone.refresh_pt(iso(this.attrs.boost_bf)) ; // Boost_bf is applied on changezone, apply it there
	if ( ! isb(register) )
		register = true ;
	if ( register ) // ATM : only MoJoSto avatars
		game.tokens.push(this) ; // Register it in order to delete on game end
	this.get_name = function() {
		return this.name+' (token)' ; // Defaults to token, overridden by duplicate
	}
}
function token_multi(ext, name, attrs) { // Add random number to tokens having multiple images
	if (
		( name == 'Eldrazi Spawn' ) ||
		( ( name == 'Zombie' ) && ( ( ext == 'ISD' ) || ( ext == 'DKA' ) ) )
	)
		attrs.nb = ( rand(3) + 1 ) ;
}
function create_token(ext, name, zone, attrs, nb, oncreate, oncreateparam) { // Creation from menu + living weapon
	if ( ! isn(nb) )
		nb = 1 ;
	for ( var i = 0 ; i < nb ; i++ ) {
		token_multi(ext, name, attrs) ;
		action_send('token',
			{'zone': zone.toString(), 'ext': ext, 'name': name, 'attrs': attrs},
			function(data) {
				var tk = create_token_recieve(data.id, data.param.ext, data.param.name,
					eval(data.param.zone), JSON_parse(data.param.attrs)) ;
				if ( typeof oncreate == 'function' )
					oncreate(tk, oncreateparam) ; // ATM : Living weapon
			}
		) ;
	}
}
function create_token_recieve(id, ext, name, zone, attrs) {
	attrs.token = true ; // For image URL modularisation
	tk = new Token(id, ext, name, zone, attrs) ;
	message(active_player.name+' creates '+tk.get_name(), 'zone') ;
	return tk ;
}
function token_extention(img, ext, exts) { // Search token in extensions (card menu)
	if ( ! iso(game.tokens_catalog) ) // No catalog ?
		return ext ;
	// Search card selected extension in catalog
	if ( iso(game.tokens_catalog[ext]) && iss(game.tokens_catalog[ext][img]) )
		return ext ;
	// Search other extensions in catalog (essentially promo reprint)
	for ( var i = 0 ; i < exts.length ; i++ )
		if ( iso(game.tokens_catalog[exts[i]]) && iss(game.tokens_catalog[exts[i]][img]) )
			return exts[i] ;
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
