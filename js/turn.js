// Turns and phases management (display and events)
/* Phases / steps : 
	1	begin
		1	untap
		2	upkeep
		3	draw
	2	main1
		4	main1
	3	combat
		5	begin
		6	attackers
		7	blockers
		8	damage
		9	end
	4	main2
		10	main2
	5	end
		11	eot
		12	cleanup */
function Step(name, icon, func, menu) { // Steps, essentially evenmential behaviour, and stop points management
	Widget(this) ;
	// Events
	this.mouseover = function(ev) {
		if ( this.name == this.phase.name ) {
			game.settittle('Unknown phase '+this.name) ;
			if ( this.name == 'main1' )
				game.settittle('first main phase') ;
			if ( this.name == 'main2' )
				game.settittle('second main phase') ;
		} else {
			if ( this.name == 'eot' )
				var title = 'end of turn step' ;
			else
				var title = this.name+' step' ;
			if ( this.phase.name == 'combat')
				title = 'combat '+title ;
			game.settittle(title) ;
		}
		game.canvas.style.cursor = 'pointer' ;
	}
	this.mouseout = function(ev) {
		game.settittle('') ;
		game.canvas.style.cursor = '' ;
	}
	this.click = function(ev) {
		switch ( ev.button ) {
			case 0 : // Left click
				if ( game.player.access() ) {
					if ( ev.ctrlKey )
						this.stop_send() ;
					else
						game.turn.setstep(this.nb) ;
				}
				break ;
			case 1 : // Middle button click
				break ;
			case 2 : // Right click
				this.contextmenu(ev) ;
				break ;
		}
	}
	this.dblclick = function(ev) {
		if ( game.player.access() )
			this.phase.turn.trigger_step() ;
	}
	this.contextmenu = function(ev) {
		if ( game.player.access() ) {
			if ( spectactor || goldfish )
				var player = game.turn.current_player ;
			else
				var player = game.player ;
			var menu = new menu_init(this) ;
			menu.addline(this.phase.name+' : '+this.name) ;
			menu.addline() ;
			var func = null ;
			if ( ! spectactor )
				func = function(plop) {
					this.stop_send() ;
				}
			menu.addline('Stop (ctrl+click)', func, this.thing).checked = this.stop ;
			if ( this.menu ) {
				menu.addline() ;
				this.menu(menu) ;
			}
			menu.start(ev) ;
			ev.preventDefault() ;
		}
	}
	this.rect = function() { // Coordinates of rectangle representation of step (for "under mouse")
		return new rectwh(this.x, this.y, this.w, this.h) ;
	}
	// Design
	this.draw = function(context) {
		context.drawImage(this.cache, this.x, this.y) ;
	}
	this.refresh = function() {
		var context = this.context ;
		context.clearRect(0, 0, this.w, this.h) ;
		// Icon		
		if ( this.img != null )
			context.drawImage(this.img, 1, 1, this.w-2, this.h-2) ;
		// Border / background
		context.strokeStyle = bordercolor ;
		context.fillStyle = bgcolor ;
		var alpha = .5 ;
		if ( this.stop )
			context.fillStyle = 'lime'
		if ( this.phase.turn.step == this.nb ) { // Current step
			context.strokeStyle = 'red' ; // Emphase on curencity by border
			if ( this.stop | this.stoped ) // Stoping or stopped
				alpha = .5 ; // Emphase on status
			else
				alpha = 0 ; // Emphase on img
				//context.fillStyle = 'yellow' ;
			if ( this.stoped )
				context.fillStyle = 'red' ;
		}
		if ( drawborder )
			context.strokeRect(.5, .5, this.w-1, this.h-1) ;
		if ( alpha > 0 ) {
			if ( game.options.get('transparency') )
				canvas_set_alpha(alpha, context) ;
			context.globalCompositeOperation = 'source-atop' ; // Only cover image where it's been drawn
			context.fillRect(.5, .5, this.w, this.h) ;
			context.globalCompositeOperation = 'source-over' ;
			if ( game.options.get('transparency') )
				canvas_reset_alpha(context) ;
		}
	}
	// Accessors
	this.toString = function() {
		return 'Step '+this.name ;
	}
	this.stop_send = function() {
		this.stop_recieve(!this.stop, true) ;
		action_send('stop', {'step': this.nb, 'value': this.stop}) ;
	}
	this.stop_recieve = function(value, me) {
		if ( me ) // Recieve self on refresh
			this.stop = value ;
		else
			this.stoped = value ;
		this.refresh() ;
	}
	// Init
	this.name = name ;
	this.icon = icon ;
	if ( isf(func) )
		this.func = func ;
	if ( isf(menu) )
		this.menu = menu ;
	// Stop
	this.stop = false ;
	this.stoped = false ;
	// Image
	this.img = null ;
	game.image_cache.load(theme_image('/TurnIcons/'+icon), function(img, widget) {
		widget.img = img ;
		widget.refresh() ;
	}, function(widget) {
		log('Unable to load image for '+widget) ;
	}, this) ;
}
function Phase(turn, name) { // Phases and their link to steps, essentially drawing functions
	Widget(this) ;
	// Methods
	this.toString = function() {
		return 'Phase '+this.name ;
	}
	this.draw = function(context) {
		if ( drawborder ) {
			context.strokeStyle = bordercolor ;
			context.roundedRect(this.x+.5, this.y+.5, this.w, this.h, 10) ;
		}
	}
	// Referencing
	this.turn = turn ;
	this.nb = turn.phases.length ;
	turn.phases.push(this) ;
	this.steps = [] ;
	// Init
	this.name = name ;
	// Steps
	for ( var i = 2 ; i < arguments.length ; i++ ) {
		// Referencing
		var step = arguments[i] ;
		step.nb = turn.steps.length ;
		turn.steps.push(step) ;
		this.steps.push(step) ;
		step.phase = this ;
		var name = this.name ;
		if ( step.name != this.name )
			name += ' : '+step.name ;
	}
}
function Turn(game) {
	Widget(this) ;
	this.under_mouse = null ;
	// Events
	this.mousemove = function(ev) { // Get "under mouse" and triggers if it changed
		var under_mouse = this.step_under_mouse(ev) ;
		if ( this.under_mouse != under_mouse ) {
			if ( ( this.under_mouse != null ) && ( isf(this.under_mouse.mouseout) ) )
				this.under_mouse.mouseout() ;
			this.under_mouse = under_mouse ;
			if ( ( under_mouse != null ) && ( isf(under_mouse.mouseover) ) )
				under_mouse.mouseover() ;
			if ( under_mouse == null )
				this.mouseover() ;
		}
	}
	this.mouseover = function(ev) {
		game.settittle('Main menu, right click !') ;
	}
	this.mouseout = function(ev) {
		game.settittle('') ;
	}
	this.click = function(ev) {
		var step = this.step_under_mouse(ev) ;
		if ( step != null )
			step.click(ev) ;
		else
			switch ( ev.button ) {
				case 0 : // Left click
					break ;
				case 1 : // Middle button click
					break ;
				case 2 : // Right click
					var menu = new menu_init(this) ;
					menu.addline('Main menu') ;
					menu.addline() ;
					menu.addline('Options', function(turn) {
						game.options.show() ;
						document.getElementById('options').classList.add('disp') ;
					}) ;
					menu.addline() ;
					menu.addline('Roll a dice ...',	rolldice) ; // Can't add player as "message_send" mechanism is used
					menu.addline('Flip a coin',	flip_coin) ;
					menu.addline() ;
					menu.addline('Watch sideboard',	card_list_edit,	game.player.sideboard) ;
					menu.addline() ;
					if ( tournament > 0 )
						menu.addline('Tournament', function() { window.open('tournament/?id='+tournament) ; }) ;
					menu.addline('Main page (new tab)', function() { window.open('./') ; }) ;
					menu.addline('Quit', function() { onUnload() ; window.location.replace('./') ; }) ; // onUnload because menu has focus at this moment, window don't trigger unload event
					if ( game.options.get('debug') ) {
						menu.addline() ;
						if ( iso(logtext) && ( logtext.length > 0 ) )
							menu.addline('Watch logs', 	log_clear) ;
						menu.addline('Tail', function() { window.open('tail.php?game='+game.id) ; }) ;
					}
					menu.start(ev) ;
					break ;
			}
	}
	this.dblclick = function(ev) {
		var step = this.step_under_mouse(ev) ;
		if ( ( step != null ) && isf(step.dblclick) )
			step.dblclick(ev) ;
	}
	this.step_under_mouse = function(ev) {
		for ( var i = 0 ; i < this.steps.length ; i++ ) {
			var step = this.steps[i] ;
			if ( dot_in_rect(new dot(ev.clientX, ev.clientY), step.rect()) )
				return step ;
		}
		if ( ( this.button != null ) && dot_in_rect(new dot(ev.clientX, ev.clientY), this.button.rect()) )
			return this.button ;
		return null ;
	}
	// Design
	this.coords_compute = function() {
		var phases_x = elementwidth + manapoolswidth ;
		var phases_y = 4 * elementheight ;
		var phase_x = 5 ;
		var phase_y = 5 ;
		var phase_w = 40 ;
		var phase_h = 40 ;
		var buttons_w = 30 ;
		var buttons_h = 30 ;
		var buttons_x = 10 ;
		var buttons_y = Math.round((turnsheight-buttons_h)/2) ;
		for ( var i = 0 ; i < this.phases.length ; i++ ) {
			var phase = this.phases[i] ;
			for ( var j = 0 ; j < phase.steps.length ; j++ ) {
				var step = phase.steps[j] ;
				step.set_coords(phases_x + buttons_x, phases_y + buttons_y, buttons_w, buttons_h)
				buttons_x += buttons_w + 5 ;
			}
			phase_w = buttons_x - phase_x ;
			phase.set_coords(phases_x + phase_x, phases_y + phase_y, phase_w, phase_h) ;
			phase_x += phase_w + 5 ;
			buttons_x = phase_x + 5 ;
		}
		if ( this.button != null )
			this.button.set_coords(phases_x + phase_x + 5, phases_y + buttons_y, 150, buttons_h) ;
	}
	this.draw = function(context) {
		// Border / background
		var w = elementwidth + manapoolswidth ; // Widget width is paper width, but let just draw "turn" part
		canvas_set_alpha(.5) ;
		context.fillStyle = 'black' ;
		context.fillRect(this.x, this.y, w, this.h) ;
		canvas_reset_alpha() ;
		if ( drawborder ) {
			context.strokeStyle = bordercolor ;
			context.strokeRect(this.x+.5, this.y+.5, w, this.h) ;
		}
		// Text
		canvas_text_c(context, 'Turn '+(this.num+1), this.x+w/2, this.y+this.h/2, 'white') ;
		// Phases
		for ( var i = 0 ; i < this.phases.length ; i++ )
			this.phases[i].draw(context) ;
		// Steps
		for ( var i = 0 ; i < this.steps.length ; i++ )
			this.steps[i].draw(context) ;
		// Button
		if ( this.button != null )
			this.button.draw(context) ;
		// Arrow
		var rect = this.button.rect() ;
		var x = this.x + rect.x + rect.w + 10 ;
		var x = 0 ;
		var t = Math.ceil(turnsheight / 2) ;
		canvas_set_alpha(zopacity, context) ;
		context.fillStyle = bordercolor ;
		var tblx = x ;
		var tbrx = x + 2 * t ;
		var ttx = x + t ;
		if ( this.current_player == game.player ) {
			var rt = this.y - 1 ; // Rectangle top
			var tbly = this.y + t - 1 ; // Triangle bottom left
			var tbry = this.y + t - 1 ; // Triangle bottom right
			var tty = this.y + 2 * t - 1 ; // Triangle top
		} else {
			var rt = this.y + t - 1 ;
			var tbly = this.y + t - 1 ;
			var tbry = this.y + t - 1 ;
			var tty = this.y + 1 ;
		}
		// Rectangle
		context.fillRect(x + Math.ceil(t/2), rt, t, t) ;
		// Triangle
		context.beginPath() ;
		context.moveTo(tblx, tbly) ;
		context.lineTo(tbrx, tbry) ;
		context.lineTo(ttx, tty) ;
		context.lineTo(tblx, tbly) ;
		context.fill() ;
		context.closePath() ;
		canvas_reset_alpha(context) ;
	}
	// Accessors
	this.toString = function() {
		return 'game.turn' ;
	}
	this.mine = function() {
		return goldfish || this.current_player == game.player ;
	}
	this.incturn = function() {
		this.setturn(this.num+1) ;
		return 0 ; // Returns step wanted
	}
	this.decturn = function() {
		this.setturn(this.num-1) ;
		return 11 ; // Returns step wanted
	}
	this.setturn = function(n, player) {
		n = this.setturn_recieve(n, player) ;
		action_send('turn', {'turn': n, 'player': this.current_player.toString()}) ;
	}
	this.setturn_recieve = function(n, player) {
		// Define active player (just toggle, except when a players plays another turn)
		if ( player )
			this.current_player = player ;
		else {
			if ( this.current_player == game.player )
				this.current_player = game.opponent ;
			else
				this.current_player = game.player ;
		}
		// End all "until EOT" effects
		var cards = game.player.battlefield.cards.concat(game.opponent.battlefield.cards) ;
		for ( var i in cards ) {
			var card = cards[i] ;
			if ( card.attrs ) {
				// Pow / tou
				var chp = isn(card.attrs.pow_eot) ;
				var cht = isn(card.attrs.thou_eot)
				var refresh = false ;
				if ( chp || cht ) {
					if ( chp )
						delete card.attrs.pow_eot ;
					if ( cht )
						delete card.attrs.thou_eot ;
					refresh = true ;
				}
				if ( card.attrs.switch_pt_eot ) {
					card.attrs.switch_pt_eot = false ;
					refresh = true ;
				}
				// "Manualy" in order to be silent
				if ( refresh )
					card.refreshpowthou() ;
				// Damages
				if ( card.get_damages() > 0 )
					card.set_damages(0) ;
				// Animate
				if ( iso(card.animated_attrs) && card.animated_attrs.eot ) {
					delete card.animated_attrs ;
					card.refreshpowthou() ;
				}
			}
		}
		// Targets
		game.target.clean(3) ;
		// Set turn
		if ( ! isn(n) )
			n = this.num + 1 ; 
		this.num = n ;
		if ( this.num < 0 )
			this.num = 0 ;
		game.sound.play('endturn') ;
		message(active_player.name+' declares turn '+this.num+' ('+this.current_player.name+')', 'turn') ;
		return this.num ;
	}
	// Step triggers
	this.trigger_step = function() { // Triggers step specific action then next step (or delegate to step action the step nexting)
		this.triggering = false ;
		if ( isf(this.steps[this.step].func) ) { // Current step is triggerable
			this.button.disabled = true ;
			this.triggering = this.steps[this.step].func(this) ; // Trigger it
			if ( ! this.triggering ) // If it didn't block, next step
				this.incstep() ;
		} else // Not triggerable
			this.incstep() ; // Just go next step
		return this.triggering ;
	}
	// Step change sortcuts
	this.incstep = function() {
		return this.setstep(this.step+1) ;
	}
	this.decstep = function() {
		return this.setstep(this.step-1) ;
	}
	// Change Step
	this.setstep = function(n) {
		var result = false ;
		if ( game.player.access() )
			this.button.disabled = false ;
		if ( this.step != n ) {
			this.triggering = false ;
			// Check if lists are open before changing step
			var fl = this.current_player.focuslists() ;
			fl += this.current_player.opponent.focuslists() ;
			if ( fl != '' )
				game.infobulle.set('Please close lists : '+fl) ;
			else  {
				// Check for stopped steps between
				if ( n > this.step )
					for ( var i = this.step ; i < n ; i++ )
						if ( this.steps[i].stoped ) {
							message('Opponent required a stop at step '+this.steps[i].name) ;
							if ( i == this.step )
								return i ;
							else
								n = i ;
						}
				// Go to desired step
				result = this.setstep_recieve(n) ;
				action_send('step', {'step': result}) ;
			}
		}
		return result ;
	}
	this.setstep_recieve = function(n) {
		var oldturn = this.num ;
		if ( n < 0 ) // Decremented first step
			n = this.decturn() ; // Return last turn
		if ( n >= this.steps.length ) // Incremented last step
			n = this.incturn() ; // Go next turn
		var oldstep = this.step ;
		this.step = n ;
		this.steps[oldstep].refresh() ;
		this.steps[this.step].refresh() ;
		var oldphase = this.phase ;
		this.phase = this.steps[this.step].phase
		// Clean arrows, attackers
		if ( ( oldphase != this.phase ) || ( oldturn != this.num ) ) { // New phase
			game.target.clean(2) ;
			var cards = game.turn.current_player.battlefield.cards ;
			if ( cards.length > 0 ) { // remove attacking status
				var sel = new Selection()
				for ( var i in cards )
					if ( cards[i].attacking )
						sel.add(cards[i]) ;
				sel.attack_recieve(false, true) ; // Silently remove from attackers
				sel.refresh('not attacking anymore') ;
			}
		} else
			game.target.clean(1) ;
		// Empty own manapool (each player manages its own)
		if ( game.player.manapool.empty_phase )
			game.player.manapool.empty() ;
		game.sound.play('tap') ;
		message(active_player.name+' declares phase '+this.phase.name+', Step '+game.turn.steps[this.step].name, 'step') ;
		if ( this.button != null )
			this.button.update() ;
		return this.step ;
	}
	// Initialisation
	game.widgets.push(this) ;
	steps_init(this) ; // Phases / steps
	this.num = 0 ; // Inced onload
	this.step = 0 ;
	this.phase = this.steps[this.step].phase ;
	this.current_player = game.creator ;
	this.triggering = false ;
	// Button
	if ( game.player.access() )
		this.button = new NextStep() ;
}
function NextStep() {
	Widget(this) ;
	this.mouseover = function(ev) {
		game.settittle('Click : Trigger, go next. Right click : Go previous') ;
		game.canvas.style.cursor = 'pointer' ;
	}
	this.mouseout = function(ev) {
		game.settittle('') ;
		game.canvas.style.cursor = '' ;
	}

	this.rect = function() { // Coordinates of rectangle representation of step (for "under mouse")
		return new rectwh(this.x, this.y, this.w, this.h) ;
	}
	this.update = function() {
		this.context.clearRect(0, 0, this.w, this.h) ;
		// Border / Background
		this.context.fillStyle = 'Gainsboro' ;
		this.context.strokeStyle = 'gray' ;
		this.context.roundedRect(0, 0, this.w, this.h, 5, true, false)
		this.context.roundedRect(1.5, 1.5, this.w-3, this.h-3, 3, true)
		// Text
		var step = game.turn.steps[game.turn.step] ;
		if ( step.phase.name == step.name )
			txt = nounize(step.phase.name)
		else
			txt = nounize(step.phase.name) + ' : ' + nounize(step.name) ;
		var b_h = 11 ;
		this.context.fillStyle = 'black' ;
		this.context.font = b_h+'pt Arial' ;
		var mx = ( this.w - this.context.measureText(txt).width ) / 2 ;
		var my = ( this.h - b_h ) / 2
		this.context.fillText(txt, mx, b_h + my, this.w) ;
	}
	this.draw = function(context) {
		context.drawImage(this.cache, this.x, this.y) ;
	}
	this.click = function(ev) {
		if ( game.turn.triggering )
			message('Finish resolving your triggers before going next step') ;
		else
			switch ( ev.which ) {
				case 1 :
					if ( ev.ctrlKey )
						game.turn.setstep(game.turn.steps.length-1) ;
					else {
						if ( ev.shiftKey )
							game.turn.incstep() ;
						else
							game.turn.trigger_step() ;
					}
					break ;
				case 3 :
					if ( ev.ctrlKey )
						game.turn.setstep(0) ;
					else
						game.turn.decstep(ev.shiftKey) ; // this.trigger_step
					break ;
				default :
					log('Unmanaged button : '+ev.which) ;
			}
	}
}
// === [ STEP CODE ] ===========================================================
function steps_init(turn) {
	turn.phases = [] ;
	turn.steps = [] ;
	// Begin
	new Phase(turn, 'begin', 
		// Untap : untap permanents
		//	menu allowing to forbid untaping of one type of permanents	
		new Step('untap', 'Untap.png', function(turn) {
			if ( turn.mine() )
				turn.current_player.battlefield.untapall() ;
		}, function(menu) {
			var func_lands = null ;
			var func_creatures = null ;
			var func_all = null ;
			if ( ! spectactor ) {
				func_all = function(plop) {
					game.player.attrs.untap_all = ! game.player.attrs.untap_all ;
				}
				if ( game.player.attrs.untap_all ) {
					func_lands = function(plop) {
						game.player.attrs.untap_lands = ! game.player.attrs.untap_lands ;
					} ;
					func_creatures = function(plop) {
						game.player.attrs.untap_creatures = ! game.player.attrs.untap_creatures ;
					} ;
				}
			}
			menu.addline('Lands', func_lands, this.thing).checked = ( game.player.attrs.untap_lands && game.player.attrs.untap_all ) ;
			menu.addline('Creatures', func_creatures, this.thing).checked = ( game.player.attrs.untap_creatures && game.player.attrs.untap_all ) ;
			menu.addline('All', func_all, this.thing).checked = game.player.attrs.untap_all ;
		}), 
		// Upkeep : various triggers
		//	no menu
		new Step('upkeep', 'Upkeep.png', function(turn) {
			if ( turn.mine() && game.options.get('remind_triggers') ) {
				var trigger_list = create_ul() ;
				var player = turn.current_player ;
				var cards = player.battlefield.cards ;
				for ( var i = 0 ; i < cards.length ; i++ ) {
					var card = cards[i] ;
					var c = card.getcounter() ;
					if ( card.attrs.vanishing && ( c > 0 ) ) {
						var li = popup_li(card, trigger_list) ;
						li.title = 'Remove a time counter' ;
						li.func = function(card) {
							if ( c > 0 )
								card.addcounter(-1) ;
							if ( c == 1 )
								card.changezone(card.owner.graveyard) ;
						}
					}
					var c = card.getcounter() ;
					if ( card.attrs.fading && ( c >= 0 ) ) {
						var li = popup_li(card, trigger_list) ;
						li.title = 'Remove a fade counter' ;
						li.func = function(card) {
							if ( c == 0 )
								card.changezone(card.owner.graveyard) ;
							if ( c >= 0 )
								card.addcounter(-1) ;
						}
					}
					var c = card.getcounter() ;
					if ( card.attrs.suspend && ( c > 0 ) ) {
						var li = popup_li(card, trigger_list) ;
						li.title = 'Remove a time counter' ;
						li.func = function(card) {
							var c = card.getcounter() ;
							if ( c > 0 )
								card.addcounter(-1) ;
							if ( c == 1 )
								card.place(0, card.grid_y) ; // Suspended card is now cast
						}
					}
					if ( card.attrs.echo ) {
						var li = popup_li(card, trigger_list) ;
						li.title = 'Pay echo' ;
						li.func = function(card) {
							if ( confirm('Pay Echo cost of '+card.attrs.echo+' for '+card.get_name()+' ?') ) { // Echo paid, won't have to pay anymore
								delete card.attrs.echo ;
								card.sync() ;
							} else // Not paid, sacrificed
								card.changezone(card.owner.graveyard) ;
						}
					}
					if ( card.attrs.trigger_upkeep ) {
						var li = popup_li(card, trigger_list) ;
						li.title = card.attrs.trigger_upkeep ;
						li.func = function(card) {
							alert(card.get_name()+' : '+card.attrs.trigger_upkeep) ;
						}
					}
				}
				var cards = player.hand.cards ;
				for ( var i = 0 ; i < cards.length ; i++ ) {
					var card = cards[i] ;
					if ( iss(card.attrs.forecast) ) {
						var li = popup_li(card, trigger_list) ;
						li.func = function(card) {
							alert('Forecast '+card.get_name()+' ('+card.attrs.forecast+')') ;
						}
					}
				}
				if ( trigger_list.children.length == 0 )
					return false ;
				for ( var i = 0 ; i < trigger_list.children.length ; i++ ) {
					var li = trigger_list.children[i] ;
					li.addEventListener('click', function(ev) {
						game.selected.set(ev.target.card) ;
					}, false) ;
					li.addEventListener('dblclick', function(ev) {
						var li = ev.target ;
						if ( isf(li.func) )
							li.func(li.card) ;
						var ul = li.parentNode ;
						ul.removeChild(li) ;
						if ( ul.childNodes.length == 0 ) {
							trigger_win.parentNode.removeChild(trigger_win) ;
							game.turn.incstep() ;
						}
					}, false) ;
					li.addEventListener('contextmenu', function(ev) {
						ev.preventDefault() ;
						var li = ev.target ;
						var ul = li.parentNode ;
						ul.removeChild(li) ;
						if ( ul.childNodes.length == 0 ) {
							trigger_win.parentNode.removeChild(trigger_win) ;
							game.turn.incstep() ;
						}
					}, false) ;
					li.title = 'Double click to trigger ('+li.title+'), right click to ignore' ;
				}
				var trigger_win = popup('Upkeep triggers', function(ev) {
					var but = ev.target ;
					var div = but.parentNode.parentNode ;
					for ( var i = 0 ; i < trigger_list.children.length ; i++ ) {
						var li = trigger_list.children[i] ;
						if ( isf(li.func) )
							li.func(li.card) ;
					}
					trigger_win.parentNode.removeChild(trigger_win) ;
					game.turn.incstep() ;
				}, 'Trigger all', function(ev) {
					trigger_win.parentNode.removeChild(trigger_win) ;
					game.turn.incstep() ;
				}, 'Trigger none') ;
				trigger_win.appendChild(trigger_list) ;
				popup_resize(trigger_win, 265) ; // After adding childs
				return true ;
			}
		}), 
		// Draw : triggers dredge, if not, draw
		//	menu allowing to change number of cards drawn
		new Step('draw', 'Draw.png', function(turn) { // Draw step (dredge)
			if ( turn.mine() ) {
				var player = turn.current_player ;
				// List
				dredge_list = create_ul() ;
				if ( game.options.get('remind_triggers') ) {
					for ( var i in player.graveyard.cards ) { // Search for dredge cards
						var card = player.graveyard.cards[i] ;
						if ( isn(card.attrs.dredge) ) { // Card has dredge, create a li
							var dredge_li = popup_li(card, dredge_list) ;
							// Events
							if ( card.attrs.dredge <= player.library.cards.length ) { // Clicks events if card is dredgeable
								dredge_li.addEventListener('click', function(ev) { // Click to select
									if ( dredge_win.selected != null )
										dredge_win.selected.classList.remove('selected') ;
									dredge_win.selected = ev.target ;
									dredge_win.selected.classList.add('selected') ;
									document.getElementById('button_ok').disabled = false ;
								}, false) ;
								dredge_li.addEventListener('dblclick', function(ev) { // DblClick to dredge
									if ( ev.target.card.dredge() )
										dredge_win.parentNode.removeChild(dredge_win) ;
									game.turn.incstep() ;
								}, false) ;
								dredge_li.title = 'Click to select, double click to dredge, or cancel button to draw' ;
							} else { // Indicate card isn't dredgeable
								dredge_li.classList.add('no') ;
								dredge_li.title = 'Impossible to dredge '+card.name+' (Dredge '+card.attrs.dredge+') with '
								dredge_li.title += player.library.cards.length+' card'+prepend_s(player.library.cards.length)+' in library' ;
							}
						}
					}
				}
				if ( dredge_list.childNodes.length == 0 ) { // No card to dredge, do alternative
					player.hand.draw_card(player.attrs.draw) ;
					return false ;
				}
				var dredge_win = popup('Dredge', function(ev) {
					if ( ( dredge_win.selected != null ) && dredge_win.selected.card.dredge() ) {
						dredge_win.parentNode.removeChild(dredge_win) ;
						game.turn.incstep() ;
					}
				}, 'Dredge selected card', function(ev) {
					player.hand.draw_card(player.attrs.draw) ;
					dredge_win.parentNode.removeChild(dredge_win) ;
					game.turn.incstep() ;
				}, 'Draw '+player.attrs.draw+' card'+prepend_s(player.attrs.draw)) ;
				dredge_win.appendChild(dredge_list) ;
				popup_resize(dredge_win, 265) ; // After adding childs
				// Selection
				dredge_win.selected = null ;
				if ( ( dredge_list.childNodes.length == 1 ) && ( dredge_list.firstChild.card.attrs.dredge <= player.library.cards.length ) ) { // Preselection if only one and able
					dredge_win.selected = dredge_list.firstChild ;
					dredge_win.selected.classList.add('selected') ;
				} else // Force player to select a card
					document.getElementById('button_ok').disabled = true ;
				return true ;
			}
		}, function(menu) {
			var func_draw = null ;
			if ( ! spectactor )
				func_draw = function(plop) {
					nb = prompt_int('Number of cards to draw on draw step', game.player.attrs.draw) ;
					if ( nb >= 0 )
						game.player.attrs.draw = nb ;
				}
			menu.addline('Cards drawn : '+game.player.attrs.draw, func_draw, this.thing) ;
		})
	) ;
	// First main phase : nothing, no menu
	new Phase(turn, 'main1', 
		new Step('main1', 'Precombat.png')
	) ;
	// Combat
	new Phase(turn, 'combat',
		// Begin : nothing
		//	no menu
		new Step('begin', 'BeginningCombat.png', function(turn){
			if ( turn.mine() )
				game.selected.clear() ;
		}), 
		// Attackers : exalt, battlecry
		//	no menu
		new Step('attackers', 'DeclareAtackers.png', function(turn) {
			if ( turn.mine() ) {
				var attacking = new Selection() ;
				var battlecry = new Selection() ;
				var exalted = 0 ;
				for ( var i in game.player.battlefield.cards ) {
					var card = game.player.battlefield.cards[i] ;
					if ( card.attacking ) { // Attacking creatures
						attacking.add(card) ;
						if ( card.attrs.battle_cry ) // Battlecry
							battlecry.add(card) ;
					}
					if ( card.attrs.exalted ) // Exalted
						exalted++ ;
				}
				if ( ( exalted > 0 ) && ( attacking.cards.length == 1 ) ) // Trigger Exalted 
					attacking.add_powthou_eot(exalted, exalted) ;
				if ( ( battlecry.cards.length > 0 ) && ( attacking.cards.length > 1 ) ) { // Trigger BattleCry
					attacking.add_powthou_eot(battlecry.cards.length, 0) ;
					battlecry.add_powthou_eot(-1, 0) ; // No self boost
				}
			}
		}), 
		// Blockers : nothing
		//	no menu
		new Step('blockers', 'DeclareBlockers.png'), 
		//new Step('firststrike', 'FirstStrikeDamage.png'),
		// Damages : apply damages (+life gain & poison) on blocking & blocked creatures & players
		//	no menu
		new Step('damage', 'CombatDamage.png', function(turn) { // Combat damage step
			var a_player = turn.current_player ;
			var d_player = a_player.opponent ;
			var dmg = sum_attackers_powers(d_player) ;
			var life = sum_attackers_powers(a_player) ;
			if ( ! d_player.me ) { // I'm attacking player : set damage and gain life
				if ( dmg[0] != 0 ) {
					var n = prompt_int('How many damages to set',-dmg[0]) ;
					if ( isn(n) && ( n != 0 ) )
						d_player.life.damages_set(d_player.attrs.damages+n)
				}
				if ( life[0] > 0 )
					a_player.life.changelife(life[0]) ;
			} else { // I'm defending player | Goldfish : set life
				if ( dmg[0] < 0 )
					d_player.life.changelife(dmg[0]) ;
				if ( dmg[1] > 0 )
					d_player.life.changepoison(dmg[1]) ;
				if ( life[0] > 0 )
					a_player.life.changelife(life[0]) ;
			}
			creatures_deal_dmg(a_player) ;
		}), 
		// End : nothing
		//	no menu
		new Step('end', 'EndCombat.png')
	) ;
	// Second main phase : nothing, no menu
	new Phase(turn, 'main2', 
		new Step('main2', 'Postcombat.png')
	) ;
	// End phase : nothing, no menu
	new Phase(turn, 'end', 
		new Step('eot', 'Cleanup.png'), 
		new Step('cleanup', 'EndOfTurn.png', null, function(menu) {
			menu.addline('Take another turn', play_turn) ;
		})
	) ;
}
function play_turn() {
	game.turn.setturn(game.turn.num+1, game.turn.current_player) ;
	game.turn.setstep(0) ;
}
