// Management of card attrs in game

// Object has 2 sources of data : 
//  - own is a record for each attrs that was set for this card occurrence
//  - bases is a dictionnary of 'base' attrs
// Getting an attr retunrns current base value if own didn't have one

function Attrs(initial) {
	// Init own
	this.clear() ;
	// Init Bases
	this.bases = {} ;
	this.base_add('back', this.base_back) ;
	this.base_add('manifest', this.base_manifest) ; // Each card can be manifested
	// Init own as recieved object
	if ( iso(initial) ) {
		this.base_extract('flip_attrs', initial, 'flip') ;
		this.base_extract('transformed_attrs', initial, 'transform') ;
		//this.base_extract('animated_attrs', initial, 'animate') ;
		if ( iss(initial.morph) )
			this.base_add('morph', this.base_morph) ;
	} else
		var initial = {} ;
	//this.initial = initial ;
	this.base_add('initial', initial, true) ;
}
function Attrs_prototype() {
	this.base_back = {'name': 'A card'} ;
	this.base_morph = twotwo('Morph', 'KTK') ;
	this.base_manifest = twotwo('Manifest', 'FRF') ;
	// Own attrs
	this.clear = function() {
		this.own = {} ;
	}
	this.set = function(name, value) {
		// Force store of null value in case of deleting a base attr (remove pow from creat)
		if ( ! isset(value) )
			value = null ;
		this.own[name] = value ;
	}
	this.get = function(name, def, base) {
		// Own
		if ( isset(this.own[name]) )
			return this.own[name] ;
		// Base
		var curr = this.base_get(base) ;
		if ( isset(curr[name]) && ( curr[name] != null ) )
			return curr[name] ;
		// Default in params
		if ( isset(def) )
			return def ;
		return null ;
	}
	this.own_has = function(name) {
		return isset(this.own[name]) ;
	}
	// Bases
		// Lib
	this.base_has = function (basename) { // Returns if a base is set
		if ( iss(basename) )
			return isset(this.bases[basename]) ;
		return false ;
	}
		// Current
	this.base_set = function(curr) { // Set current base
		if ( this.base_has(curr) )
			this.current = curr ;
		else
			log('can not set current to '+curr) ;
	}
	this.base_current = function() { // Returns current base name
		if ( this.base_has(this.current) )
			return this.current ;
		log('base_curent no current found : '+this.current) ;
		log(this.bases) ;
		return null ;
	}
		// Content
	this.base_get = function(name) { // Returns a base, current by default
		if ( ! this.base_has(name) )
			name = this.base_current()
		return this.bases[name] ;
	}
	this.base_add = function(name, value, active) { // Add a base to list
		this.bases[name] = value ;
		if ( active )
			this.base_set(name) ;
	}
	this.base_extract = function(param, attrs, name) { // Use initial properties as new base
		if ( attrs[param] ) {
			attrs[param].ext = attrs.ext ; // For image URL
			this.base_add(name, attrs[param]) ;
			delete attrs[param] ;
		}
	}
	// Misc
	this.base_transfer = function(to, attr) { // Transfer changed pow and tou from current base to new base
		// Get current own value
		if ( ! this.own_has(attr) )
			return false ;
		var curval = this.get(attr) ;
		if ( ! isn(curval) )
			return false ;
		// Get current base value
		var curbase = this.base_get() ;
		var curbaseval = curbase[attr] ;
		if ( ! isn(curbaseval) || ( curval == curbaseval ) ) { // Unchanged
			this.set(attr, null) ;
			return false ;
		}
		// Get destination base value
		var tobase = this.base_get(to)
		var tobaseval = tobase[attr] ;
		if ( ! isn(tobaseval) )
			return false ;
		// Value change between bases
		if ( curbaseval == tobaseval )
			return false ;
		var newval = curval - curbaseval + tobaseval ;
		this.set(attr, newval) ;
		return true ;
	}
}
function twotwo(name, ext) { // Returns a 2/2 colorless creature
	return { // (Morph and manifest)
		'name': name, 'ext': ext, // Params (morph/manifest)
		'token': true, // For image
		'types': ['creature'], 'pow': 2, 'thou': 2 // Game
	} ;
}

