function Target_tmp(cards, ev) {
	// Events
	this.onKey = function(ev) { // Update a parallel table of modifiers
		var val = ( ev.type == 'keydown' ) ;
		if ( ev.shiftKey )
			game.target.tmp.modifiers.shiftKey = val ;
		if ( ev.ctrlKey )
			game.target.tmp.modifiers.ctrlKey = val ;
		if ( ev.altKey )
			game.target.tmp.modifiers.altKey = val ;
		draw() ;
	}
	// Methods
	this.over = function(el) {
		this.targeted = el ;
	}
	this.out = function(el) {
		this.targeted = null ;
	}
	this.stop = function(targeted) { // On mouseup, targetable elements return themselves, then if none, default return false
		if ( targeted != null ) {
			var reach = game.target.reach(this.modifiers) ;
			for ( var i in this.cards ) {
				var target = game.target.add(this.cards[i], targeted, reach) ;
				if ( target != null )
					action_send('arrow',{'from': this.cards[i].toString(), 'to': targeted.toString(), 'reach': reach}) ;
			}
		}
		document.removeEventListener('keydown', this.onKey, false) ;
		document.removeEventListener('keyup', this.onKey, false) ;
		delete game.target.tmp ;
		game.target.tmp = null ;
		game.target.helper.classList.remove('disp') ;
		return targeted ;
	}
	// Init
	if ( isn(cards.length) )
		this.cards = cards ; // Cards
	else
		this.cards = [cards] ; // It's a zone
	this.targeted = null ;
	this.modifiers = {
		'shiftKey' : false, 
		'ctrlKey' : false, 
		'altKey' : false
	}
	document.addEventListener('keydown', this.onKey, false) ;
	document.addEventListener('keyup', this.onKey, false) ;
	if ( game.options.get('helpers') ) {
		game.target.update_helper(ev) ;
		game.target.helper.classList.add('disp') ;
	}
	return this.tmp ;
}
function closetarget(ev) {
	ev.preventDefault() ;
	ev.target.thing.del() ;
}
function Target(orig, dest, reach) {
	// Methods
	this.del = function() {
		for ( var i = 0 ; i < game.target.targets.length ; i++ )
			if ( this == game.target.targets[i] ) {
				game.target.del(i) ;
				action_send('delarrow',{'card': this.orig.toString(), 'target': this.dest.toString()}) ;
				break ;
			}
	}
	// Init
	this.orig = orig ; 
	this.dest = dest ;
	this.reach = reach ;
}
function Targets() {
	// Init
	this.start = function(cards, ev) {
		game.canvas.style.cursor = 'crosshair' ;
		this.tmp = new Target_tmp(cards, ev) ;
		return this.tmp ;
	}
	this.reach = function(ev) { // Returns reach depending on modifiers from the event
		var reach = 2 ; // Phase
		if ( ev.shiftKey )
			reach = 1 ; // Step
		if ( ev.ctrlKey )
			reach = 3 ;  // Turn, not working under linux (alt+right click = WM menu)
		if ( ev.altKey )
			reach = 5 ; // defnitive
		return reach ;
	}
	this.color = function(reach) {
		switch ( reach ) {
			case 1 : // Step
				return 'darkred' ;
			case 2 : // Phase
				return 'gold' ;
			case 3 : // Turn
				return 'green' ;
			case 5 : // Definitive
				return 'RoyalBlue' ;
			default : 
				return 'black' ;
		}
		return 'white' ;
	}
	// Design
	// http://dbp-consulting.com/tutorials/canvas/CanvasArrow.html
	this.draw = function(context) {
		if ( ( this.targets.length > 0 ) || ( this.tmp != null ) ) { // Something to draw
			context.save() ;
			// Init context
			canvas_set_alpha(1)
			context.lineWidth = 8 ;
			context.lineCap = 'round' ;
				// Definitive
			for ( var i = 0 ; i < this.targets.length ; i++  ) {
				var target = this.targets[i] ;
				var from = target.orig.get_coords_center() ;
				var to = target.dest.get_coords_center() ;
				context.strokeStyle = this.color(target.reach) ;
				context.fillStyle = context.strokeStyle ;
				drawArrow(context, from.x, from.y, to.x, to.y, 3, 1, undefined, 35) ; // 
			}
				// Temp (after definitive in order they appear over definitives, as they will be over by being pushed)
			if ( this.tmp != null ) {
				context.strokeStyle = this.color(this.reach(this.tmp.modifiers)) ;
				context.fillStyle = context.strokeStyle ;
				for ( var i = 0 ; i < this.tmp.cards.length ; i++ ) {
					var c = this.tmp.cards[i].get_coords_center() ;
					drawArrow(context, c.x, c.y, game.mouseX, game.mouseY, 3, 1, undefined, 35) ; 
				}
			}
			context.restore() ;
		}
	}
	this.update_helper = function(ev) {
		var result = ( this.tmp != null ) ;
		if ( result && game.options.get('helpers') ) {
			var margin = 5 ; // helper's left corner placed margin px on cursor's bottom right
			// Helper's left
			var x = ev.clientX + margin ; 
			var xmax = window.innerWidth - this.helper.clientWidth ;
			this.helper.style.left = min(max(x, 0), xmax)+'px' ; // inside 0, xmax
			// Helper's right
			var y = ev.clientY + margin ;
			var ymax = window.innerHeight - this.helper.clientHeight ;
			this.helper.style.top = min(max(y, 0), ymax)+'px' ; // inside 0, ymax
		}
		return result ;
	}
	// Targets management
	this.add = function(orig, dest, reach) {
		if ( ( orig == dest ) || ( orig == null ) || ( dest == null ) ) {
			log('Target canceled : '+orig+' -> '+dest) ;
			return null ;
		}
		if ( !isf(dest.get_name) )
			log(dest) ;
		message(orig.get_name()+' targets '+dest.get_name(), 'target') ;
		for ( var i = 0 ; i < this.targets.length ; i++ ) { // Browse older arows, if one is the same, delete the old, as this new one will replace it
			var line = this.targets[i] ;
			if (
				( ( line.orig == orig ) && (line.dest == dest ) ) || // One line with the same orig and dest
				( ( line.orig == dest ) && (line.dest == orig ) ) // Or inversed
			) {
				this.del(i) ;
				break ; // Theorically, because of this loop, there can't be another line corresponding, so we should stop there
			}
		}
		var line = new Target(orig, dest, reach) ;
		this.targets.push(line) ;
		return line ;
	}
	this.del = function(i) { // Delete target i from targets
		return this.targets.splice(i, 1)[0] ; // Remove from list
	}
	this.del_by_orig_dest = function(orig, dest) {
		var result = false ;
		for ( var i = 0 ; i < this.targets.length ; i++ ) {
			var target = this.targets[i] ;
			if ( ( orig == target.orig ) && ( dest == target.dest ) ) {
				this.del(i--) ; // Splice updated arrows indexes, same index will be reused for next element
				result = true ;
			}
		}
		return result ;
	}
		// On a step, phase or turn change : clean arrows by reach
	this.clean = function(reach) {
		var result = false ;
		for ( var i = 0 ; i < this.targets.length ; i++ ) {
			var line = this.targets[i] ;
			if ( line.reach <= reach ) {
				this.del(i--) ; // Splice updated arrows indexes, same index will be reused for next element
				result = true ;
			}
		}
		return result ;
	}
		// On a card deletion, delete arrows sourced or destinated to that card (don't send)
	this.del_by_target = function(thing) {
		var result = false ;
		for ( var i = 0 ; i < this.targets.length ; i++ ) {
			var target = this.targets[i] ;
			if ( ( thing == target.orig ) || ( thing == target.dest ) ) {
				this.del(i--) ; // Splice updated arrows indexes, same index will be reused for next element
				result = true ;
			}
		}
		return result ;
	}
		// On a card move, move arrows sourced or destinated to that card
	this.update = function(thing) {
		var result = false ; // Was this call usefull ?
		var targets = this.alltargets(thing) ;
		for ( var i = 0 ; i < targets.length ; i++ ) {
			targets[i].draw() ;
			result = true ;
		}
		return result ;
	}
		// Returns an array of card/zones targeted by parameter
	this.targetedby = function(thing) {
		var arr = [] ;
		for ( var i = 0 ; i < this.targets.length ; i++ ) {
			var target = this.targets[i] ;
			if ( thing == target.orig )
				arr.push(target.dest) ;
		}
		return arr ;
	}
	this.targeting = function(thing) {
		var arr = [] ;
		for ( var i = 0 ; i < this.targets.length ; i++ ) {
			var target = this.targets[i] ;
			if ( thing == target.dest )
				arr.push(target.orig) ;
		}
		return arr ;
	}
	this.alltargets = function(thing) {
		var arr = [] ;
		for ( var i = 0 ; i < this.targets.length ; i++ ) {
			var target = this.targets[i] ;
			if ( ( thing == target.dest ) || ( thing == target.orig ) )
				arr.push(target) ;
		}
		return arr ;
	}
	this.targets = [] ;
	this.tmp = null ;
	this.helper = document.getElementById('target_helper') ;
}
