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
function start() { // When page is loaded : initialize everything
	//refresh_card_count = 0 ;
	workarounds() ; // Workarounds
		// Cards
	// Prototypes
	Card.prototype = new card_prototype() ;
	Token.prototype = new card_prototype() ;
	// caching getElementById
	zoom = document.getElementById('zoom') ;
	timeleft = document.getElementById('timeleft') ;
	// AJAX Communication
	$.ajaxSetup({'cache': false, 'error': function(XMLHttpRequest, textStatus, errorThrown) {
		//if ( ( errorThrown != undefined ) && ( errorThrown != '' ) && ( XMLHttpRequest.responseText != '' ) )
		log('Ajax error : '+textStatus+', '+errorThrown+', '+XMLHttpRequest.responseText) ;
	}});
	tokens = null ;
	last_recieved_action = -1 ; // Server created actions are 0
	if ( replay == 1 ) // Replay mode : only load action list and prepare GUI, then no timer is launched, button "next" will do the rest
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
	else { // Not replay mode, prepare chat, keyboard events, past actions and ajax loop
		game.websockets = false ;
		network_loop() ; // Recieve previous actions before sending spectactor / join
		chat_start() ;
		if ( spectactor ) {
			new Spectactor($.cookie(session_id), game.options.get('profile_nick')) ; // Declare itself as a spectactor
			action_send('spectactor', {'name': game.options.get('profile_nick')}, function(data){log('Connection successfull')}) ; // And send to other players
			//game.socket_registration = {'type': 'register', 'game': game.id, 'nick': game.options.get('profile_nick'), 'player_id': $.cookie(session_id)} ;
		} else {
			autotext_init() ;
			window.addEventListener('keypress',	onKeyPress,	false) ; // Key press
			window.addEventListener('keydown',	function(ev) { // Key down (for ctrl, while dnding)
				switch ( ev.which ) {
					case 17 :
						if ( ( game.draginit != null ) && ( game.widget_under_mouse.type == 'battlefield' ) )
							game.canvas.style.cursor = 'copy' ;
						break ;
					default:
				}
			},	false) ;
			window.addEventListener('keyup',	function(ev) { // Key up (for ctrl, while dnding)
				switch ( ev.which ) {
					case 17 :
						if ( ( game.draginit != null ) && ( game.widget_under_mouse.type == 'battlefield' ) )
							game.canvas.style.cursor = 'pointer' ;
						break ;
					default:
				}
			},	false) ;
			window.addEventListener('beforeunload',	onBeforeUnload,	false) ; // Confirm page closure
			window.addEventListener('unload',	onUnload,	false) ; // Page closure
			action_send('join', {'player': game.player.toString()}, function(data){log('Connection successfull')}) ;
			//game.socket_registration = {'type': 'register', 'game': game.id, 'nick': game.player.name, 'player_id': game.player.id} ;
		}
		window.addEventListener('focus',	onFocus,	false) ; // On focus, clean unfocused new unseen actions
	}
}
function new_round() {
	window.removeEventListener('beforeunload', onBeforeUnload, false) ; // Remove page closure confirmation
	alert('Game ended, going to tournament page') ;
	document.location = 'tournament/?id='+tournament ;
}
function manage_action(action) {
	recieve_time = new Date() ;
	sent_time = new Date(parseInt(action.local_index)*1000) ;
	switch ( action.sender ) {
		case '' : // Server (cards, first turn/phase)
			active_player = game.server ;
			break ;
		case game.player.id : // Current player
			active_player = game.player ;
			break ;
		case game.opponent.id : // Opponnent
			active_player = game.opponent ;
			break ;
		default :
			if ( game.spectactors[action.sender] )
				active_player = game.spectactors[action.sender] ;
			else
				if ( action.type != 'spectactor' ) // 'spectactor' is never sent by a 'valid' sender
					log(action.sender+' is not a valid sender')
	}
	var id = parseInt(action.id) ;
	param = JSON_parse(action.param) ;
	if ( ! iso(param) || ( param == null ) ) {
		if ( action.type == 'text' ) { // If action is a text, display raw JSON as a workaround
			param = {} ;
			param.text = action.param ;
		} else {
			log('Unable to parse : '+param) ;
			return false ; // Don't manage this action
		}
	}
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
	// Action depending on param "type"
	switch ( action.type ) {
		// External
		case 'spectactor' :
			new Spectactor(action.sender, param.name) ;
			break ;
		case 'allow' :
			game.spectactors[param.spectactor].allow_recieve(active_player) ;
			break ;
		// Game actions
			// Communication
		case 'text' :
			txt_recieve(param.text) ;
			break ;
		case 'msg' :
			message(active_player.name+' '+param.text) ;
			break ;
			// Begin
		case 'toss' :
			message(param.player.name+' won the toss', 'win') ;
			if ( action.recieved == '0' ) { // Only ask if not previously managed
				if ( ( ! spectactor ) && ask_for_start(param.player.opponent) ) // Only player who won the toss will enter that if
					$.getJSON('json/action_recieve.php', {action: action.id}) ; // Mark Toss as managed
				else
					message('Waiting for answer ...', 'win') ; // Concerned player hasn't answered, warn opponent
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
		// Selection actions
		case 'zone' :
			if ( iss(param.type) && ( param.type != '' ) )
				param.cards.settype(param.type) ;
			for ( var i = 0 ; i < param.cards.cards.length ; i++ ) // If opponent moved cards in selection, remove those cards from selection
				if ( inarray(param.cards.cards[i], game.selected.cards) )
					game.selected.remove(param.cards.cards[i]) ;
			param.cards.changezone_recieve(param.zone, param.visible, param.index, param.x, param.y) ;
			break ;
		case 'sattrs' :
			param.cards.setattrs(param.attrs) ;
			break ;
		// Card actions
		case 'card' :
			new Card(id, param.ext, param.name, param.zone, param.attrs) ;
			break ;
		case 'token' :
			create_token_recieve(id, param.ext, param.name, param.zone, JSON_parse(param.attrs)) ;
			break ;
		case 'duplicate' :
			param.card.duplicate_recieve(id) ;
			break ;
		case 'place' :
			param.card.place_recieve(param.x,param.y) ;
			break ;
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
	var id = parseInt(action.id) ;
	if ( id > last_recieved_action )
		last_recieved_action = id ;
	return true ;
}
/* Lib */
function JSON_parse(text) { /* Wrapper to parse JSON with exception management */
        if ( ! iss(text) ) // We only can parse a string
		return text ;
	var res = text ;
	try {
		res = JSON.parse(text) ;
	} catch (e) {
		log('Crash when parsing JSON : ['+text+']') ;
		log(e) ;
		//log(stack_trace(JSON_parse)) ;
		res = null ; // An object is expected, return one empty
	}
	return res ;
}
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
// Events method
function onKeyPress(ev) {
	// Under FF : triggers before any browser default action occurs, allowing to cancel them
	// Under FF : catches events such as F5 => refresh, Ctrl+S => Save dialog, Ctrl+D => Bookmark dialog ...
	// Under Chromium : catches only printable characters, only if no browser action is triggered
	var handled = true ; // Handled by default, unhandled in 'default:' cases
	// https://developer.mozilla.org/en/DOM/Event/UIEvent/KeyEvent
	// ev.which contains a code in both cases : char and key
	// Chars to regroup undepending on modifier
	switch ( ev.charCode ) {
		case '}'.charCodeAt(0) : // ctrl alt + french win
			game.selected.add_powthou(1, 1) ;
			break ;
		case '='.charCodeAt(0) : // lower-case "+" on french keyboards
		case '+'.charCodeAt(0) :
			if ( ev.ctrlKey ) {
				if ( ev.altKey ) // Ctrl + Alt + key
					game.selected.add_powthou(1, 1) ;
				else // Ctrl + key
					game.selected.add_powthou(1, 0) ;
			} else {
				if ( ev.altKey ) // Alt + key
					game.selected.add_powthou(0, 1) ;
				else // Key
					handled = false ;
			}
			break ;
		case '|'.charCodeAt(0) : // ctrl alt - french win
			game.selected.add_powthou(-1, -1) ;
			break ;
		case '-'.charCodeAt(0) :
			if ( ev.ctrlKey ) {
				if ( ev.altKey ) // Ctrl + Alt + key
					game.selected.add_powthou(-1, -1) ;
				else // Ctrl + key
					game.selected.add_powthou(-1, 0) ;
			} else {
				if ( ev.altKey ) // Alt + key
					game.selected.add_powthou(0, -1) ;
				else // Key
					handled = false ;
			}
			break ;
		case '*'.charCodeAt(0) :
			if ( ev.ctrlKey ) {
				if ( ev.altKey ) // Ctrl + Alt + key
					game.selected.add_powthou_eot(1, 1) ;
				else // Ctrl + key
					game.selected.add_powthou_eot(1, 0) ;
			} else {
				if ( ev.altKey ) // Alt + key
					game.selected.add_powthou_eot(0, 1) ;
				else // Key
					handled = false ;
			}
			break ;
		case ':'.charCodeAt(0) : // lower-case "/" on french keyboards
		case '/'.charCodeAt(0) :
			if ( ev.ctrlKey ) {
				if ( ev.altKey ) // Ctrl + Alt + key
					game.selected.add_powthou_eot(-1, -1) ;
				else // Ctrl + key
					game.selected.add_powthou_eot(-1, 0) ;
			} else {
				if ( ev.altKey ) // Alt + key
					game.selected.add_powthou_eot(0, -1) ;
				else // Key
					handled = false ;
			}
			break ;
		default :
			handled = false ;
	}
	if ( handled ) { // If key was considered as handled, don't let browser trigger its defaults actions for this event
		draw() ; // KeyPress
		return eventStop(ev) ;
	}
	// Keys to regroup undepending on modifier 
	switch ( ev.keyCode ) {
		// Ctrl+PgUp/PgDn isn't preventable
		case KeyEvent.DOM_VK_PAGE_UP :
			if ( ev.altKey ) {
				if ( ev.ctrlKey )
					game.selected.add_powthou(1, 1) ;
				game.selected.add_counter(1) ;
				handled = true ;
			}
			break ;
		case KeyEvent.DOM_VK_PAGE_DOWN :
			if ( ev.altKey ) {
				if ( ev.ctrlKey )
					game.selected.add_powthou(-1, -1) ;
				game.selected.add_counter(-1) ;
				handled = true ;
			}
			break ;
	}
	if ( handled ) { // If key was considered as handled, don't let browser trigger its defaults actions for this event
		draw() ; // KeyPress
		return eventStop(ev) ;
	}
	handled = true ;
	// Keys to regroup depending on modifier
	if ( ev.ctrlKey ) {
		if ( ! ev.altKey ) { // Ctrl + key
			switch ( ev.keyCode ) {
				case 0 : // Special management for chars (keypress generating a character in focused field)
					switch ( ev.charCode ) {
						case ' '.charCodeAt(0) :
							if ( ev.shiftKey )
								game.turn.trigger_step() ;
							else
								game.turn.incstep() ;
							break ;
						case 'd'.charCodeAt(0) :
							game.player.hand.draw_card() ;
							break ;
						case 'g'.charCodeAt(0) :
							/*
							log('Widgets : ') ;
							for ( var i in game.widgets )
								log(' - '+game.widgets[i]) ;
							*/
							node_empty(document.getElementById('chathisto'));
							break ;
						case 'h'.charCodeAt(0) : // Relaunch timer
							if ( toid != null ) { // One is already running
								window.clearTimeout(toid) ; // Stop it
								log('AJAJ loop stopped') ;
							}
							timer() ;
							log('AJAJ loop started') ;
							break ;
						case 'H'.charCodeAt(0) : // Stop timer
							if ( toid != null ) { // One is already running
								window.clearTimeout(toid) ; // Stop it
								log('AJAJ loop stopped') ;
							}
							toid = null ; // Mark it as 'not running'
							break ;
						case 'i'.charCodeAt(0) :
							rolldice() ;
							break ;
						case 'j'.charCodeAt(0) : // Displays battlefield
							var txt = '' ;
							var grid = game.player.battlefield.grid ;
							for ( var i = 0 ; i < grid[0].length ; i++ ) {
								for ( var j = 0 ; j < grid.length ; j++ )
									txt += grid[j][i] + '\t' ;
								txt += '\n' ;
							}
							alert(txt) ;
							break ;
						case 'k'.charCodeAt(0) :
							alert(game.image_cache.info()) ;
							break ;
						case 'l'.charCodeAt(0) :
							log_clear() ;
							break ;
						case 'L'.charCodeAt(0) :
							if ( iso(logtext) )
								while ( logtext.length > 0 )
									logtext.pop() ;
							break ;
						case 'm'.charCodeAt(0) :
							game.player.hand.mulligan() ;
							break ;
						case 'n'.charCodeAt(0) :
							var cards = game.selected.get_cards() ;
							for ( var i in cards )
								cards[i].setnote() ;
							break ;
						case 'o'.charCodeAt(0) :
							var cards = game.selected.get_cards() ;
							for ( var i in cards )
								cards[i].setcounter() ;
							break ;
						case 'p'.charCodeAt(0) :
							var cards = game.selected.get_cards() ;
							for ( var i in cards )
								cards[i].ask_powthou() ;
							break ;
						case 'r'.charCodeAt(0) :
							resize_window(ev) ;
							break ;
						case 's'.charCodeAt(0) :
							game.player.library.shuffle() ;
							break ;
						case 'u'.charCodeAt(0) :
							game.player.battlefield.untapall() ;
							break ;
						case 'z'.charCodeAt(0) :
							game.player.hand.undo() ;
							break ;
						default: 
							handled = false ; // Let browser manage this key
					}
					break ;
				// Management for nonchar keypress (non printable keyboard keys)
				case KeyEvent.DOM_VK_BACK_SPACE :
					game.turn.decstep()
					break ;
				case KeyEvent.DOM_VK_RETURN : 
					game.turn.setstep(12) ;
					break ;
				case KeyEvent.DOM_VK_DELETE :
					game.selected.changezone(game.player.graveyard) ;
					break ;
				case KeyEvent.DOM_VK_LEFT :
					var cards = game.selected.get_cards() ;
					for ( var i in cards )
						cards[i].place(cards[i].grid_x - 1, cards[i].grid_y) ;
					break ;
				case KeyEvent.DOM_VK_UP :
					var cards = game.selected.get_cards() ;
					if ( ( game.selected.zone.player != game.player ) && game.options.get('invert_bf') ) // Inverted opponent BF
						var step = 1 ;
					else
						var step = -1 ;
					for ( var i in cards )
						cards[i].place(cards[i].grid_x, cards[i].grid_y + step) ;
					break ;
				case KeyEvent.DOM_VK_RIGHT :
					var cards = game.selected.get_cards() ;
					for ( var i in cards )
						cards[i].place(cards[i].grid_x + 1, cards[i].grid_y) ;
					break ;
				case KeyEvent.DOM_VK_DOWN :
					var cards = game.selected.get_cards() ;
					if ( ( game.selected.zone.player != game.player ) && game.options.get('invert_bf') ) // Inverted opponent BF
						var step = -1 ;
					else
						var step = 1 ;
					for ( var i in cards )
						cards[i].place(cards[i].grid_x, cards[i].grid_y + step) ;
					break ;
				default : 
					handled = false ; // Let browser manage this key
			}
		}
	} else if ( ev.altKey ) // Alt+key
		switch ( ev.keyCode ) {
			case 0 : // Special management for chars (keypress generating a character in focused field
				switch ( ev.charCode ) {
					case 'd'.charCodeAt(0) :
						var cards = game.selected.get_cards() ;
						for ( var i in cards )
							cards[i].duplicate() ;
						break ;
					default :
						handled = false ; // Let browser manage this key
				}
				break ;
			default :
				handled = false ; // Let browser manage this key
		}
	else // No modifier
		switch ( ev.keyCode ) {
			case 0 : // Special management for chars (keypress generating a character in focused field)
				if ( ( document.activeElement != sendbox ) && ( document.activeElement.id != 'autotext_area' ) ) {
					sendbox.focus() ; // Ensure the char will be sent in right field
					sendbox.value += String.fromCharCode(ev.charCode) ;
				} else
					handled = false ; // Nothing else to do more than what the browser wants to do
				break ;
			// Management for nonchar keypress (non printable keyboard keys)
			case KeyEvent.DOM_VK_UP :
				if ( chat_pointer == null )
					chat_pending = sendbox.value ;
				if ( ( chat_pointer == null ) || ( chat_pointer > chat_messages.length ) )
					chat_pointer = chat_messages.length ;
				if ( chat_pointer < 1 )
					chat_pointer = 1 ;
				chat_pointer-- ;
				sendbox.value = chat_messages[chat_pointer] ;
				sendbox.focus() ;
				break ;
			case KeyEvent.DOM_VK_DOWN :
				if ( chat_pointer != null ) { // Only if already called with up key
					chat_pointer++
					if ( chat_pointer >= chat_messages.length ) {
						sendbox.value = chat_pending ;
						chat_pointer = null ;
						chat_pending = '' ;
					}else
						sendbox.value = chat_messages[chat_pointer] ;
				} else
					sendbox.value = '' ;
				sendbox.focus() ;
				break ;
			case KeyEvent.DOM_VK_F9 :
				game.opponent.life.changelife(-1) ;
				break ;
			case KeyEvent.DOM_VK_F10 :
				game.opponent.life.changelife(1) ;
				break ;
			case KeyEvent.DOM_VK_F11 :
				game.player.life.changelife(-1) ;
				break ;
			case KeyEvent.DOM_VK_F12 :
				game.player.life.changelife(1) ;
				break ;
			default:
				handled = false ; // Let browser manage this key
		}
	draw() ; // KeyPress
	if ( handled ) // If key was considered as handled, don't let browser trigger its defaults actions for this event
		return eventStop(ev) ;
}
function onBeforeUnload(ev) {
	var text = 'Sure you want to quit this page ?'
	ev.returnValue = text ;
	return text ;
}
function onUnload(ev) {
	action_send('quit', {'player': game.player.toString()}, function(data){log('Disconnection successfull')}) ;
}
function onFocus(ev) {
	unseen_actions = 0 ;
	document.title = init_title ;
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
		var text = new Array() ;
		var txt = '' ;
		var arg = arguments[i] ;
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
