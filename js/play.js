// play.js
// Main file for MTGAS, loading and initializing everything defined in other JS scripts

// http://www.webreference.com/programming/javascript/mk/column2/
// https://developer.mozilla.org/en/DOM/element

// Event KeyDown
	// Under FF : triggers before any browser default action occurs
// Event KeyUp
	// Under FF : triggers AFTER default browser action occurs
	// Under chromium : catches every key, only if no default action is assigned by the browser
// Both
	// Under FF+ch : manages printable chars as keypress, not charpress
	// Under FF+ch : ctrl keys triggers an event instead of being a modifier (modifiers can't be used :/ )
// === [ INIT ] ================================================================
function game_start() { // When page is loaded : initialize everything
	//refresh_card_count = 0 ;
	workarounds() ; // Workarounds
		// Cards
	// Prototypes
	var pt = new card_prototype() ;
	Card.prototype = pt ;
	Token.prototype = pt ;
	Attrs.prototype = new Attrs_prototype() ;
	// caching getElementById
	zoom = document.getElementById('zoom') ; // Redraw right column when zoom size changes
	zoom.addEventListener('load', resize_right_column, false) ;
	timeleft = document.getElementById('timeleft') ;
	// AJAX Communication
	tokens = null ;
	last_recieved_action = -1 ; // Server created actions are 0
	// Update timer
	if ( tournament_data != null ) {
		game.due = mysql2date(tournament_data.due_time) ;
	}
	window.setInterval(function() {
		var node = timeleft.firstChild.nextSibling ;
		node_empty(node) ;
		if ( iso(game.due) ) { // Tournament
			var post = 'left' ;
			var time = game.due-new Date() ;
			if ( time <= timer_alert_time * 60 * 1000 ) // Blink on alert time
				node.parentNode.classList.toggle('timer_notice') ;
			else if ( time <= timer_notice_time * 60 * 1000 ) // Redify on notice time
				node.parentNode.classList.add('timer_notice') ;
		} else {
			var post = 'elapsed' ;
			var time = (new Date()-game.start_date) ;
		}	
		node.appendChild(create_text(time_disp(Math.round(time/1000))+' '+post)) ;
	}, 1000) ;
	if ( replay == 1 ) // Replay mode : only load action list and prepare GUI, then no timer is launched, button "next" will do the rest
		log('Replay not managed') ;
		/*
		$.getJSON('json/actions.php', {game: game.id}, function(tournament) {
			game.replay_tournament = tournament ; // Store
			game.replay_delay = 100 ;
			var cards = false ;
			while ( game.replay_tournament.actions.length > 0 ) {
				// Play all cards
				var action = game.replay_tournament.actions.shift() ;
				if ( action.type == 'card' )
					cards = true ;
				else // Not a card
					if ( cards ) { // First action after playing all cards
						game.replay_tournament.actions.unshift(action) ; // Re-inject action
						break ; // Stop loop
					}
				manage_action(action) ;
			}
			var sendbox = document.getElementById('sendbox') ;
			var dady = sendbox.parentNode.parentNode ;
			// Play button
			var play = create_button('Play', null, 'Play actions until released') ;
			game.replay_interval = 0 ;
			var autoplay_start = function(button) {
				if ( game.replay_tournament.actions.length > 0 ) {
					play.textContent = 'Pause' ;
					manage_action(game.replay_tournament.actions.shift()) ;
					draw() ; // Equivalent of 'draw()' done in manage_actions during regular games
					document.getElementById('replay_actions_left').value = game.replay_tournament.actions.length+' / '+game.replay_actions_nb ;
				} else {
					play.textContent = 'No more' ;
					window.clearInterval(game.replay_interval) ;
					document.getElementById('replay_autoplay').checked = false ;
				}
			}
			play.addEventListener('mousedown', function(ev) {
				window.clearInterval(game.replay_interval) ;
				game.replay_interval = window.setInterval(autoplay_start, game.replay_delay, ev.target) ;
			}, false) ;
			var autoplay_stop = function(check) {
				if ( typeof check != 'boolean' ) // Called by play.addEventListener
					check = true ;
				play.textContent = 'Play' ;
				window.clearInterval(game.replay_interval) ;
				if ( check )
					document.getElementById('replay_autoplay').checked = false ;
			}
			play.addEventListener('mouseup', autoplay_stop, false) ;
			play.addEventListener('blur', autoplay_stop, false) ;
			sendbox.parentNode.parentNode.replaceChild(play, sendbox.parentNode) ;
			// Autoplay checkbox
			var autoplay = create_checkbox('replay_autoplay', false, 'replay_autoplay') ;
			autoplay.title = "Automatically plays actions when checked" ;
			autoplay.addEventListener('change', function(ev){
				autoplay_stop(false) ; // Don't change checkbox status
				if ( ev.target.checked ) // Called by checkbox change, with checkbox checked
					game.replay_interval = window.setInterval(autoplay_start, game.replay_delay, play) ;
			}, false) ;
			play.parentNode.insertBefore(autoplay, play.nextSibling) ;
			// Delay input
			var delay = create_input('replay_delay', game.replay_delay, 'replay_delay') ;
			delay.title = 'Delay (in ms) between actions' ;
			delay.size = 2 ;
			delay.maxLength = 5 ;
			delay.addEventListener('keyup', function(ev){
				var val = parseInt(ev.target.value) ;
				if ( game.replay_delay != val )
					game.replay_delay = val ;
			}, false) ;
			autoplay.parentNode.insertBefore(delay, autoplay.nextSibling) ;
			// Actions left input
			game.replay_actions_nb = game.replay_tournament.actions.length ;
			var left = create_input('replay_actions_left', game.replay_actions_nb+' / '+game.replay_actions_nb, 'replay_actions_left') ;
			left.title = "Actions left"
			left.size = 10 ;
			left.maxLength = 20 ;
			left.readOnly = true ;
			delay.parentNode.insertBefore(left, delay.nextSibling) ;
		}) ;
		*/
	else { // Not replay mode, prepare chat, keyboard events, past actions and ajax loop
		game.websockets = true ;
		network_loop() ; // Recieve previous actions before sending spectactor / join
		chat_start() ;
		if ( spectactor ) { // Declare itself as a spectactor
			game.me = game.spectators.add(player_id, game.options.get('profile_nick')) ;
		} else {
			game.me = game.player ;
			autotext_init() ;
			player_events() ;
		}
		events() ;
	}
}
function manage_action(action, active_player) {
	var id = parseInt(action.id) ;
	param = JSON_parse(action.param) ;
	if ( ! iso(param) || ( param == null ) ) {
		if ( action.type == 'text' ) { // If action is a text, display raw JSON as a workaround
			param = {} ;
			param.text = action.param ;
		} else {
			log('Unable to parse : '+action.param) ;
			return false ; // Don't manage this action
		}
	}
	sent_time = new Date(parseInt(action.local_index)*1000) ;
	// Eval objects passed by name
	if ( param.zone )
		param.zone = eval(param.zone) ;
	if ( param.player )
		param.player = eval(param.player) ;
	if ( param.card ) {
		var mycard = get_card(param.card) ;
		if ( mycard == null ) {
			log('Can\'t find card '+param.card+' to apply action '+action.type) ;
			return false ; // Don't manage this action
		} else
			param.card = mycard ;
	}
	if ( param.cards ) {
		var cards_str = param.cards ;
		param.cards = new Selection(param.cards) ; // ?
	}
	// Set last recieved action
	if ( 
		! isn(game.connection.registration_data.from)
		|| ( id > game.connection.registration_data.from )
	)
		game.connection.registration_data.from = id ;
	// Self sent callback
	if ( ( active_player == game.player ) && ( issn(action.callback) ) ) {
		action.param = param ;
		var cid = parseInt(action.callback) ;
		if ( isf(game.callbacks[cid]) ) {
			game.callbacks[cid](action) ;
			game.callbacks[cid] = null ;
			return false ; // Don't manage, it's not really a recieved action
		}
	}
	// Action depending on param "type"
	switch ( action.type ) {
		// Init
		case 'tokens' :
			game.tokens_catalog[action.ext] = param ;
			break ;
		// Spectators
		case 'spectactor' :
			game.spectators.add(param.player_id, param.nick) ;
			break ;
		case 'allow' :
			var s = game.spectators.get(param.spectactor) ;
			if ( s != null )
				s.allow(active_player) ;
			break ;
		// Communication
		case 'text' :
			txt_recieve(param.text) ;
			break ;
		case 'msg' :
			message(active_player.name+' '+param.text) ;
			break ;
		// Begin
		case 'toss' :
			if ( game.stonehewer ) {
				var avatars = {
					'momir': 'Momir Vig, Simic Visionary',
					'jhoira': 'Jhoira of the Ghitu',
					'stonehewer': 'Stonehewer Giant'
				}
				var my = 0
				for ( var i in avatars ) {
					var avatar = avatars[i] ;
					var tk = new Token(my, '../VOA', avatar, game.player.battlefield,
						{'types':['avatar'], 'avatar': i}, false) ;
					tk.place_recieve(22+my++, 0) ;
				}
			}
			message(param.player.name+' won the toss', 'win') ;
			if ( action.recieved == '0' ) { // Only ask if not previously managed
				if ( ( ! spectactor ) && ask_for_start(param.player.opponent) )
					// Only player who won the toss will execute this
					game.connection.send({'type': 'recieve', 'id': action.id}) ;
				else // Concerned player hasn't answered, warn opponent
					message('Waiting for answer ...', 'win') ;
			}
			break ;
		case 'choose' :
			if ( param.player == active_player )
				message(active_player.name+' choosed to play', 'win') ;
			else
				message(active_player.name+' choosed to draw', 'win') ;
			break ;
		// Turns
		case 'turn' :
			game.turn.setturn_recieve(param.turn, param.player) ;
			break ;
		case 'step' :
			game.turn.setstep_recieve(param.step) ;
			break ;
		case 'stop' :
			game.turn.steps[param.step].stop_recieve(param.value, ( active_player == game.player )) ;
			break ;
		// Player actions
		case 'psync' : // Player sync (life, poison, library revealed)
			param.player.sync_recieve(eval(param.attrs)) ;
			break ;
		case 'join' :
			message(active_player.name+' has join', 'join') ;
			break ;
		case 'quit' :
			message(active_player.name+' has quit', 'join') ;
			break ;
		// Zone actions
		case 'zsync': // Zone sync (shuffle, zone reordering)
			if ( param.cards.cards.length > 0 )
				param.zone.sync_recieve(param.cards, param.shuffle) ;
			else
				log(param.cards.cards.length+' cards in zsync recieve') ;
			break
		case 'mana' :
			var pool = eval(param.pool) ;
			pool.set_recieve(param.value) ;
			break ;
		// Arrow actions
		case 'arrow' :
			if ( param.from.indexOf('game') == 0 ) // if my string param contains 'game' : it's a zone
				var from = eval(param.from) ;
			else // Otherwise, it's a card
				var from = get_card(param.from) ;
			if ( param.to.indexOf('game') == 0 ) // if my string param contains 'game' : it's a zone
				var to = eval(param.to) ;
			else // Otherwise, it's a card
				var to = get_card(param.to) ;
			game.target.add(from, to, param.reach) ;
			break ;
		case 'delarrow' :
			game.target.del_by_orig_dest(param.card, param.target) ;
			break ;
		// Selection actions
		case 'zone' :
			if ( iss(param.type) && ( param.type != '' ) )
				param.cards.settype(param.type) ;
			for ( var i = 0 ; i < param.cards.cards.length ; i++ ) // If opponent moved cards in selection, remove those cards from selection
				if ( inarray(param.cards.cards[i], game.selected.cards) )
					game.selected.remove(param.cards.cards[i]) ;
			param.cards.changezone_recieve(param.zone, param.base, param.index, param.x, param.y, param.offset) ;
			break ;
		case 'sattrs' :
			param.cards.setattrs(param.attrs) ;
			break ;
		// Card actions
			// Creation
		case 'card' :
			var card = new Card(id, param.ext_img, param.name, param.zone, param.attrs, param.exts) ;
			if ( param.zone.player == game.player ) {
				if ( game.stonehewer && ! card.is_land('initial') ) {
					game.stonehewer = false ;
				}
				if ( document.getElementById('side_window') != null ) {
					card.watching = true ; // Force visible locally
					side_add_card(card) ;
				}
			}
			break ;
		case 'mojosto' :
			message(active_player.name+'\'s avatar '+param.avatar+' casts '+param.name) ;
			var tk = new Token(id, param.ext, param.name, param.zone, param.attrs) ;
			if ( ( action.recieved == '0' ) && ( action.sender == player_id ) ) { // Unmanaged
				game.connection.send({'type': 'recieve', 'id': action.id}) ;
				if ( game.stonehewer && tk.is_creature() )
					tk.mojosto('stonehewer', tk.attrs.get('converted_cost'), tk) ;
				if ( iss(param.target) )
					get_card(param.target).attach(tk) ;
			}
			break ;
		case 'token' :
			create_token_recieve(id, param.ext, param.name, param.zone, JSON_parse(param.attrs)) ;
			break ;
		case 'duplicate' :
			param.card.duplicate_recieve(id) ;
			break ;
			// Other
		case 'place' :
			param.card.place_recieve(param.x,param.y) ;
			break ;
		case 'base' :
			param.card.base_set_recieve(param.base) ;
			break ;
		case 'attrs' :
			param.card.setattrs(param.attrs) ;
			break ;
		case 'animate' :
			param.card.animate_recieve(param.attrs) ;
			break ;
		case 'attach' :
			var attachedto = get_card(param.to) ;
			if ( param.card.get_attachedto() != attachedto )
				if ( attachedto != null )
					attachedto.attach_recieve(param.card) ;
			break ;
		default : 
			log('Unknown action type : '+action.type) ;
	}
	return true ;
}
/* Lib */
function get_card(id) {
	var cards = game.cards.concat(game.tokens) ;
	for ( var j = 0 ; j < cards.length ; j++ )
		if ( cards[j].id == id )
			return cards[j] ;
	return null ;
}
function rolldice(faces) {
	if ( ! isn(faces) )
		faces = prompt_int('Number of faces',20) ;
	if ( isn(faces) )
		message_send('rolled a '+(rand(faces)+1)+' on a '+faces+' sided dice')
}
function flip_coin() {
	switch ( rand(2) ) {
		case 0 :
			message_send('got head on a coin flip') ;
			break ;
		case 1 :
			message_send('got tail on a coin flip') ;
			break ;
		default :
			message_send('got nothing on a coin flip') ;
	}
}
// Log
function log_clear() {
	if ( typeof logtext != 'object') {
		game.infobulle.set('Log doesn\'t exist ') ;
		return false ;
	}
	if ( logtext.length == 0 ) {
		game.infobulle.set('Log is empty') ;
		return false ;
	}
	var txt = '' ;
	for ( var i = 0 ; i < logtext.length ; i++ )
		txt += logtext[i]+'\n' ;
	document.getElementById('log_area').value = txt ;
	document.getElementById('log_window').classList.add('disp') ;
	return true ;
}
function log() {
	for ( var i = 0 ; i < arguments.length ; i++) {
		var arg = arguments[i] ;
		//debug(arg) ;
		//continue ;
		var text = new Array() ;
		var txt = '' ;
		switch ( typeof arg ) {
			case 'boolean' : // Display numbers "as-is"
			case 'number' : // Display numbers "as-is"
			case 'string' : // Display strings "as-is"
				text.push(arg) ;
				break ;
			case 'function' :
				text.push(functionname(arg)) ;
				break ;
			case 'object' : // Detailed object display
				if ( arg == null )
					text.push('null') ;
				else {
					txt += 'Properties of object : '
					txt += arg.toString() ;
					text.push(txt) ;
					for ( var i in arg ) {
						var t = '' ;
						var v = '' ;
						try {
							v = arg[i] ;
							t = typeof v ;
						} catch (e) {
							v = 'exc : '+e ;
							t = '' ;
						}
						txt = " - " + t + '\t' + i ;
						switch ( t ) {
							case 'function' :
								txt += '()' ;
								if ( ( v.name != '') && ( i != v.name ) )
									txt += '('+v.name+')' ;
								break ;
							case 'object' :
								if ( v == null )
									txt += ' = null' ;
								else
									txt += ' = '+v.toString() ;
								break ;
							case 'string' :
								txt += ' = "' + v + '"' ;
								break ;
							default :
								txt += ' = ' + v ;
						}
						text.push(txt) ;
					}
				}
				break ;
			default:
				text.push('[' + functionname(log.caller) + '] Type unrecognized by loging engine :  '+typeof arg) ;
		}
		message(text.join("\n"), 'bug') ;
		logtext = logtext.concat(text) ;
	}
}
