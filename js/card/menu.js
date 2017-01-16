function card_menu(ev) {
	var card = this ;
	switch ( card.zone.type ) {
		case 'battlefield' :
			if ( card.zone.player.access() )
				var selected = game.selected.get_cards() ;
			else // Current player has no access on card (spectactor), he can't have a selection.
				//Let's create one with only current card
				var selected = [card] ;
			var menu = new menu_init(selected) ;
			// First item : selection specific
			if ( selected.length > 1 ) // Multiple cards, just show the number
				menu.addline(selected.length+' cards') ;
			else { 
				// Card specific submenu
				var cardmenu = new menu_init(selected) ;
				if ( card.zone.player.access() ) {
					// MoJoSto
					var avatar = card.attrs.get('avatar') ;
					if ( iss(avatar) ) {
						def = this.zone.untaped_lands() ; // ceil((game.turn.num+1)/2, 0)
						switch ( avatar ) {
							case 'momir' :
								cardmenu.addline('Creature ...', function() {
									cc = prompt_int('Converted cost', def) ;
									if ( cc != null ) {
										this.mojosto('momir', cc) ;
										if ( game.nokiou )
											this.mojosto('nokiou', cc) ;
									}
								}) ;
								cardmenu.addline('Nonland, noncreature permanent ...',    function() {
									cc = prompt_int('Converted cost', def) ;
									if ( cc != null )
										this.mojosto('nokiou', cc) ;
								}) ;
								cardmenu.addline('Momir also puts a noncreature permanent',    function() {
									game.nokiou = ! game.nokiou ;
								}).checked = game.nokiou ;
								break ;
							case 'jhoira' :
								cardmenu.addline('Instants', function() {
									this.mojosto('jhoira-instant', cc) ;
								}) ;
								cardmenu.addline('Sorceries', function() {
									this.mojosto('jhoira-sorcery', cc) ;
								}) ;
								break ;
							case 'stonehewer' :
								cardmenu.addline('Activated', function() {
									game.stonehewer = ! game.stonehewer ;
								}).checked = game.stonehewer ;
								break ;
							default :
								log('Avatar '+avatar+' no exist') ;
						}
						menu_merge(menu, card.get_name(), cardmenu) ;
						break ;
					}
					// Morph
					if ( card.owner.me && card.attrs.base_has('morph') ) {
						if ( card.attrs.base_current() != 'initial' ) {
							var txt = 'Turn face up as '+card.attrs.get('name', null, 'initial') ;
							txt += ' ('+card.attrs.get('morph', null, 'initial')+')' ;
							cardmenu.addline(txt, card.base_set, 'initial').moimg = card.imgurl('initial') ;
						}
						if ( card.attrs.base_current() != 'morph' ) {
							var txt = 'Turn face down as morph'
							cardmenu.addline(txt, card.base_set, 'morph').moimg = card.imgurl('morph') ;
						}
					}
					// Manifest
						// Manifester
					if ( this.attrs.get('manifester') )
						cardmenu.addline('Manifest library top card', card.manifest) ;
						// Manifested
					if ( card.owner.me && ( this.attrs.base_current() == 'manifest' ) ) {
						var manas = card.attrs.get('manas', null, 'initial') ;
						var txt = 'Turn face up as '+card.attrs.get('name', null, 'initial') ;
						txt += ' ('+manas.join('')+')' ;
						cardmenu.addline(txt, card.base_set, 'initial').moimg = card.imgurl('initial') ;
					}
					// Flip and transform is all the same : toggle state between on and off
					var func = function(base) {
						var already = ( this.attrs.base_current() == base ) ; // Is on ?
						var to = already ? 'initial' : base ; // Set destination base to param / initial
						var msg = base+' into '+this.get_name(to) ;
						var l = cardmenu.addline(msg, card.base_set, to) ; // Menu sets base to destination
						l.checked = already ;
						l.moimg = card.imgurl(to) ;
						return l ;
					}
					// Flip
					if ( this.attrs.base_has('flip') )
						func.call(this, 'flip') ;
					// Transform
					if ( this.attrs.base_has('transform') )
						func.call(this, 'transform') ;
					// Tokens
					var tokens = card.attrs.get('tokens', []) ;
					for ( var i = 0 ; i < tokens.length ; i++ ) {
						var ext = card.ext ;
						var name = tokens[i].name ;
						var attrs = tokens[i].attrs ;
						token_multi(ext, name, attrs) ;
						var img = token_image(name, attrs.nb, attrs.pow, attrs.thou) ;
						ext = token_extention(img, ext, this.exts) ;
						if ( isn(attrs.pow) && isn(attrs.thou) )
							var txt = 'Token '+name+' '+attrs.pow+'/'+attrs.thou ;
						else
							var txt = name ;
						if ( tokens[i].nb > 1 )
							txt += ' x '+tokens[i].nb
						var zone = game.player.battlefield ; // By default, create token on asking player's battlefield
						if ( goldfish ) // Goldfish players want it on card's controler's battlefield
							zone = card.zone ;
						var l = cardmenu.addline(txt, create_token, ext, name, zone, attrs, tokens[i].nb) ;
						l.moimg = card_images('/TK/'+ext+'/'+img) ;
					}
					// Animate
					var animate = card.attrs.get('animate') ;
					if ( iso(animate) ) {
						for ( var i = 0 ; i < card.attrs.get('animate').length ; i++ ) {
							var anim = animate[i] ;
							var name = 'Animate as '+anim.pow+'/'+anim.tou
							if ( iso(anim.subtypes) )
								name += ' '+anim.subtypes.join(' ') ;
							if ( iss(anim.cost) )
								name += ' ('+anim.cost+')' ;
							cardmenu.addline(name, card.animate, anim) ;
						}
					}
					// Cascade
					if ( this.attrs.get('cascade') )
						cardmenu.addline('Cascade', this.cascade) ;
					// Boost BF
					var boost_bf = this.boost_bf() ;
					if ( boost_bf.length > 0 ) {
						for ( var i = 0 ; i < boost_bf.length ; i++ ) {
							var str = '' ;
							if ( ! boost_bf[i].self )
								str += 'Other ' ;
							if ( iss(boost_bf[i].cond) )
								str += boost_bf[i].cond+' ' ;
							else
								str += 'Creat ' ;
							if ( boost_bf[i].control == -1 )
								str += 'opponent control ' ;
							else if ( boost_bf[i].control == 1 )
								str += 'you control ' ;
							str += 'get '+disp_int(boost_bf[i].pow)+'/'+disp_int(boost_bf[i].tou) ;
							if ( boost_bf[i].eot )
								str += ' until end of turn' ;
							var l = cardmenu.addline(str, this.boost_bf_enable, boost_bf[i]) ;
							l.checked = boost_bf[i].enabled ;
						}
					}
					menu_merge(menu, card.get_name(), cardmenu) ;
				} else { // if ( card.zone.player.access() )
					var line = menu.addline(card.get_name())
					if ( card.attrs.base_has('transform') )
						line.moimg = card.imgurl('transform') ;
				}
			} // else of if ( selected.length > 1 )
			if ( card.zone.player.access() ) {
				menu.addline() ;
				// Remove targets
				if ( game.target.alltargets(card).length > 0 ) 
					menu.addline('Remove targets',		function() {
						var targets = game.target.alltargets(card) ;
						for ( var i = 0 ; i < targets.length ; i++ )
							targets[i].del() ;
					}) ;
				// Tap
				var entry = menu.addline('Tap',  game.selected.tap, ! card.attrs.get('tapped')) ;
				entry.override_target = game.selected ;
				entry.checked = card.attrs.get('tapped') ;
				// Attacking status
				if ( card.attrs.get('attacking') ) // Any moment, if attacking
					menu.addline('Cancel attack', game.selected.attack, this).override_target = game.selected ;
				else { // Card's controler is declaring attackers
					if ( card.is_creature() && ( card.zone.player == game.turn.current_player )
						&& ( game.turn.step == 5 ) ) { // During attackers declaration step
						var l = menu.addline('Attack without tapping', game.selected.attack_notap, this)
						l.override_target = game.selected ;
					}
				}
				var submenu = new menu_init(selected) ;
				this.changezone_menu(submenu) ;
				menu.addline('Move', submenu) ;
				menu.addline() ;
				// P/T
				var pt = menu.addline('Set Power/Toughness', card.ask_powthou) ;
				pt.buttons.push({'text': '+1', 'callback': function(ev, cards) {
					(new Selection(cards)).add_powthou(1, 1) ;
				}}) ;
				pt.buttons.push({'text': '-1', 'callback': function(ev, cards) {
					(new Selection(cards)).add_powthou(-1, -1) ;
				}}) ;
				var pteot = menu.addline('Change P/T until EOT', card.ask_powthou_eot) ;	
				pteot.buttons.push({'text': '+1', 'callback': function(ev, cards) {
					(new Selection(cards)).add_powthou_eot(1, 1) ;
				}}) ;
				pteot.buttons.push({'text': '-1', 'callback': function(ev, cards) {
					(new Selection(cards)).add_powthou_eot(-1, -1) ;
				}}) ;
				// Counters
				var c = menu.addline('Set counters', card.setcounter) ;
				var steps = clone(this.attrs.get('steps', [])) ;
				if ( ! inarray('+1', steps) ) steps.push('+1') ;
				if ( ! inarray('-1', steps) ) steps.push('-1') ;
				var counter = this.attrs.get('counter', 0) ;
				if ( this.is_planeswalker() && ! inarray('-X', steps) )
					steps.push('+X') ;
				for ( i in steps ) {
					var step = parseInt(steps[i]) ;
					if ( step != 0 ) {
						var menubut = {'text': steps[i]} ;
						if ( counter + step >= 0 ) {
							menubut.callback = function(ev, cards, param) {
								(new Selection(cards)).add_counter(param) ;
							}
							menubut.param = step ;
						}
						c.buttons.push(menubut) ;
					}
				}
				// Note
				menu.addline('Set a note', card.setnote) ;
				menu.addline() ;
				// Face down
				if ( this.type == 'card' ) { //  token can't face down
					var back = ! card.is_visible() ;
					if ( back )
						var base = 'initial' ;
					else
						var base = 'back' ;
					var line = menu.addline('Face down', this.base_set, base) ;
					line.checked = back ;
					if ( line.checked )
						line.buttons.push({'text': 'Look', 'callback': function(ev, cards) {
							for ( var i = 0 ; i < cards.length ; i++ ) {
								var card = cards[i] ;
								message_send(game.player.get_name()+' looked facedown card') ;
								message(card.name) ;
								card.zoom('initial') ;
							}
						}}) ;
				}
				// Duplicate
				menu.addline('Duplicate',		card.duplicate) ;
				// Copy
				var tby = game.target.targetedby(card) ;
				var copy = this.attrs.get('copy') ;
				if ( ( tby.length == 1 ) && ( ( tby[0].type == 'card' ) || ( tby[0].type == 'token' ) ) ) { 
					if ( ( iso(copy) ) && ( tby[0] == copy ) ) {
					} else
						menu.addline('Copy '+tby[0].get_name(),	card.copy) ;
				}
				if ( iso(copy) )
					menu.addline('Uncopy '+copy.get_name(),	card.uncopy) ;
				// No untap
				menu.addline() ;
				var no_untap = card.attrs.get('no_untap', false) ;
				menu.addline('Won\'t untap',		card.untap_toggle).checked = no_untap ;
				var no_untap_once = card.attrs.get('no_untap_once', false) ;
				menu.addline('Won\'t untap once',	card.untap_once_toggle).checked = no_untap_once ;
			}
			break ;
		case 'hand' :
			if ( card.zone.player.access() )
				var selected = game.selected.get_cards() ;
			else
				// Current player has no access on card (spectactor), he can't have a selection
				// Let's create one with only current card
				var selected = [card] ;
			var menu = new menu_init(selected) ;
			// First item : selection specific
			if ( selected.length > 1 ) // Multiple cards, just show the number
				menu.addline(selected.length+' cards') ;
			else { 
				// Card specific submenu
				if ( card.zone.player.access() ) {
					var cardmenu = new menu_init(selected) ;
					if ( iss(card.attrs.get('morph')) )
						cardmenu.addline('Morph', card.changezone, card.owner.battlefield, 'morph') ;
					if ( isn(card.attrs.get('suspend')) )
						cardmenu.addline('Suspend ('+card.attrs.get('suspend_cost')+')', card.suspend) ;
					if ( iss(card.attrs.get('cycling')) )
						cardmenu.addline('Cycle ('+card.attrs.get('cycling')+')',	card.cycle) ;
					// Create living weapon token
					if ( card.attrs.get('living_weapon') )
						cardmenu.addline('Living weapon ', function() {
							this.changezone(this.owner.battlefield) ;
							this.living_weapon() ;
						}) ;
					var line = menu_merge(menu, selected[0].get_name(), cardmenu) ;
				} else
					var line = menu_merge(menu, selected[0].get_name()) ;
				if ( card.attrs.base_has('transform') )
					line.moimg = card.imgurl('transform') ;
			}
			if ( card.zone.player.access() ) {
				menu.addline() ;
				this.changezone_menu(menu) ;
				menu.addline() ;
				var line = menu.addline('Reveal', 	game.selected.toggle_reveal, card)
				line.checked = ( card.attrs.get('visible') == true ) ;
				line.override_target = game.selected ;
			}
			break ;
		case 'graveyard' :
		case 'exile' :
		case 'sideboard' :
			var menu = new menu_init(this) ;
			this.changezone_menu(menu) ;
			break ;
		case 'library' :
			var menu = new menu_init(this) ;
			this.changezone_menu(menu) ;
			menu.addline() ;
			menu.addline('Shuffle and put on top', function(card) {
				card.zone.editor_window.close() ;
				card.moveinzone(card.zone.cards.length) ;
			}, this) ;
			break ;
		default : 
			var menu = new menu_init() ;
			menu.addline('No menu on a card from zone ' + card.zone.type) ;
	}
	menu.addline() ;
	if ( card.is_visible() ) {
		menu.addline('Informations (MCI)', card.info) ;
	} else
		menu.addline('No information aviable from hidden card') ;
	if ( game.options.get('debug') )
		menu.addline('Debug internals', function(card) {
			console.log(this) ;
			console.log(this.attrs) ;
		}) ;
	menu.start(ev) ;
	return menu ;
}
function card_changezone_menu(menu) { // Generate a menu depending on current zone, to send card to each other zone
	var card = this ;
	var player = card.owner ;
	var sel = game.selected ;
	if ( this.zone.type != 'battlefield' ) {
		menu.addline('To battlefield',			sel.changezone, player.battlefield).override_target = sel ;
		menu.addline('Play face down',			sel.changezone, player.battlefield, 'back').override_target = sel ;
	}
	if ( this.zone.type != 'hand' )
		menu.addline('To hand',					sel.changezone, player.hand).override_target = sel ;
	if ( this.zone.type != 'library' ) {
		menu.addline('To library top',			sel.changezone, player.library).override_target = sel ;
		menu.addline('To library beneath cards ...', function(zone) {
			var i = prompt_int('Beneath how many cards ?', 2);
			if ( i !== null ) {
				if ( i > 0 ) { // Given positive, compute from top
					i = zone.cards.length - i ;
					if ( i < 0 ) {
						i = 0 ;
					}
				} else if ( i < 0 ) { // Given negative, compute from bottom
					i = -i ;
				} else { // 0 = top - default for changezone
					i = null ;
				}
				sel.changezone(zone, null, i) ;
			}
		}, player.library).override_target = sel ;
		menu.addline('To library bottom',		sel.changezone, player.library, null, 0).override_target = sel ;
	} else {
		var i = card.IndexInZone() ;
		var j = card.zone.cards.length - 1 ;
		if ( i != j )
			menu.addline('To library top',		sel.moveinzone, j).override_target = sel ;
		if ( i != 0 )
			menu.addline('To library bottom',	sel.moveinzone, 0).override_target = sel ;
	}
	if ( this.zone.type != 'graveyard' )
		menu.addline('To graveyard',			sel.changezone, player.graveyard).override_target = sel ;
	if ( this.zone.type != 'exile' )
		menu.addline('To exile',				sel.changezone, player.exile).override_target = sel ;
}
function card_info() {
	var res = this.is_visible() ;
	if ( res )
		window.open('http://magiccards.info/query?q=!'+this.attrs.get('name')+'&v=card&s=cname') ;
	else
		log('You can\'t ask info for hidden card') ;
	return res ;
}
function card_mojosto(avatar, cc, target) {
	var obj = {'avatar': avatar}
	if ( isn(cc) )
		obj.cc = cc ;
	if ( iso(target) ) // Stonehewer giant
		obj.target = target.toString() ;
	action_send('mojosto', obj) ;
	log(obj) ;
}
