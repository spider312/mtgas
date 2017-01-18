// network.js : functions for communicating with server, let's try to well modularize choice between AJAJ and WebSocket
// + chat utils
function network_loop() { // Things to do regulary
	//active_player = game.player ; // Used to distinguish actions initiated by current player and those initiated by its opponent and transmitted by net
	if ( ! game.websockets ) // Ajax
		log('Websockets should be enabled') ;
	else { // Websockets
		var registration = {'id': game.id} ;
		game.connection = new Connexion('game', function(data, ev) { // OnMessage
			// Guess active player
			switch ( data.sender ) {
				case '' : // Server (cards, first turn/phase)
					active_player = game.server ;
					break ;
				case game.player.id : // Current player
					active_player = game.player ;
					break ;
				case game.opponent.id : // Opponnent
					active_player = game.opponent ;
					break ;
				default : // Spectator
					var s = game.spectators.get(data.sender)
					if ( s != null ) // Known
						active_player = s ;
					else {
						if ( ( data.type != 'spectactor' ) ) {
							log('Action "'+data.type+'" from unknown spectator') ;
							return false ;
						}
					}
			}
			// Manage action depending on type
			switch ( data.type ) {
				// Players/spectators
				case 'register' :
					message(active_player.name+' has join', 'join') ;
					active_player.connect(true) ;
					if ( active_player == game.me ) { // My own registration, recieved after prev actions
						display_start() ;
						if ( isset(active_player.attrs) && active_player.attrs.siding && ( game.lastwinner != null ) ) // End of "initial actions", if side should be opened, open it
							side_start_recieve(active_player, game.lastwinner) ;
					}
					break ;
				case 'unregister' :
					message(active_player.name+' has quit', 'join') ;
					active_player.connect(false) ;
					break ;
				case 'blur' :
					active_player.focus(false) ;
					break ;
				case 'focus' :
					active_player.focus(true) ;
					break ;
				case 'roundend' :
					if ( tournament > 0 ) {
						game.ended = true ; // Game ended by tournament, won't start siding
						var param = JSON.parse(data.param) ;
						var func_redirect = function() {
							window.removeEventListener('beforeunload', onBeforeUnload, false) ;
							document.location = 'tournament/?id='+tournament ;
						}
						if ( isn(param[player_id]) ) {
							evaluate(param[player_id], func_redirect) ;
						} else {
							func_redirect();
						}
					} else {
						log('roundend on a game not in a tournament')
					}
					break ;
				default :
					// Recieving actions while tab has not focus : inform user
					var hasfocus = false ;
					if ( document.hasFocus )
						hasfocus = document.hasFocus() ;
					if ( ! hasfocus ) {
						if ( unseen_actions == 0 ) { // Display last seen line
							var rows = document.getElementById('chathisto').rows
							for ( var i = 0 ; i < rows.length ; i++ )
								rows[i].classList.remove('lastseen') ; // Remove previously marked
							if ( rows.length > 0 )
								rows[rows.length-1].classList.add('lastseen') ; // Mark last line
						}
						unseen_actions++
						document.title = '('+unseen_actions+') '+init_title ;
						//window.getAttention() ; // Doesn't work
						//window.focus() ; // Neither
					}
					try {
						manage_action(data, active_player) ;
					} catch (e) {
						log('Exception in manage_action') ;
						console.log(e) ;
					}
			}
			active_player = game.player ; // End of "recieve", all actions in context are from player
		}, function(ev) { // OnClose/OnConnect
			switch ( ev.type ) {
				case 'open' :
					message('Connected', 'join') ;
					break ;
				case 'close' :
					var reason = ev.reason ;
					if ( reason == '' )
						reason = 'unknown reason' ;
					message('Disconnected : ' + reason + ' ('+ev.code+')', 'join') ;
					break ;
				default :
					log('Unknown open/close type : '+ev.type) ;
					log(ev) ;
			}
		}, registration) ;
		game.connection.events() ;
	}
}
/* Actions sending management */
function action_send(type, obj, callback) {
	var mydate = new Date() ;
	var params = { 'game': game.id, 'type': type, 'local_index': Math.floor(mydate.getTime()/1000)} ; // Initial params with timestamp
	if ( obj ) {
		try {
			param = JSON.stringify(obj) ;
		} catch (e) {
			log('exception in JSON.stringify in action sending') ;
			var param = clone(obj) ; // obj will be stringified, do not modify original copy
			for ( var i in param )
				if ( ( typeof param[i] == 'object' ) && (param[i] != null ) )
					for ( var j in param[i] )
						if ( ( typeof param[i][j] == 'object' ) && (param[i][j] != null ) )
							param[i][j] = param[i][j].toString() ; // Avoid deep stringification
			param = JSON.stringify(param) ;
			log(param) ;
		}
		//params.param = param.replace(/\'/g,"\\\'") ;
		params.param = param
	}
	if ( ! isf(callback) )
		callback = null ;
	if ( ! game.websockets )
		log('Websockets should be enabled') ;
	else {
		if ( isf(callback) ) {
			game.callback_id++ ;
			params.callback = game.callback_id ;
			game.callbacks[game.callback_id] = callback ;
		}
		game.connection.send(JSON.stringify(params)) ;
	}
}
/* Chat management */
function txt_recieve(text) {
	message('<'+active_player.name+'> '+text) ;
}
function txt_send(text) {
	action_send('text', {'text': text}) ;
	if ( spectactor ) {
		var p = active_player ;
		active_player = game.spectators.get(player_id) ;
	}
	txt_recieve(text) ;
	if ( spectactor )
		active_player = p ;
}
function message_filter_contextmenu(ev) {
	var menu = new menu_init(this) ;
	menu.addline('Messages filters') ;
	menu.addline() ;
	menu.addline('All', function() {
		all_message_filter = ! all_message_filter ;
		for ( var i in message_filter )
			if ( message_filter[i] != all_message_filter )
				message_filter_toggle(i) ;
	}).checked = all_message_filter ;
	for ( var i in message_filter )
		menu.addline(message_filter_name[i],  message_filter_toggle, i).checked = message_filter[i] ;
	menu.start(ev) ;
	return eventStop(ev) ;
}
function message_filter_toggle(type) {
	message_filter[type] = ! message_filter[type] ;
	var rows = document.getElementById('chathisto').rows
	for ( var i = 0 ; i < rows.length ; i++) {
		var cell = rows[i].cells[0] ;
		if ( cell.classList.contains(type) ) // Only change concerned lines
			if ( message_filter[type] ) // Set to true
				cell.classList.remove('filtered') ;
			else // Set to false
				cell.classList.add('filtered') ;
	}
	chatbox.scrollTop = chatbox.scrollHeight ;
}
function message_send(text, meaning) {
	action_send('msg', {'text': text}) ;
	message(game.player.name+' '+text, meaning) ;
}
function message(text, meaning, dom) {
	chatbox = document.getElementById('chatbox') ;
	var scrbot = chatbox.scrollHeight - ( chatbox.scrollTop + chatbox.clientHeight ) ; // Scroll from bottom, if 0, will scroll to see added line
	var table = document.getElementById('chathisto') ;
	var row = table.insertRow(-1) ; // At the end
	var cell = row.insertCell(-1) ;
	// Title
	var title = '' ;
	if ( sent_time == null ) {
		var r = new Date() ;
		title += 'Sent '+r.getHours()+':'+r.getMinutes()+':'+r.getSeconds() ;
	} else
		title += 'Sent '+sent_time.getHours()+':'+sent_time.getMinutes()+':'+sent_time.getSeconds() ;
	var recieve_time = new Date() ;
	if ( recieve_time != null ) {
		var r = recieve_time ;
		title += ', Recieved '+recieve_time.getHours()+':'+recieve_time.getMinutes()+':'+recieve_time.getSeconds() ;
	}
	cell.title = title ;
	// Text
	if ( iss(text) )
		cell.appendChild(document.createTextNode(text)) ;
	else
		cell.appendChild(text) ;
	// Meaning
	var displayed = false ;
	if ( iso(meaning) ) { // Multiple
		for ( var i = 0 ; i < meaning.length ; i++ ) {
			cell.classList.add(meaning[i]) ;
			if ( message_filter[meaning[i]] )
				displayed = true ;	
		}
	} else if ( iss(meaning) ) { // Single
		cell.classList.add(meaning) ;
		if ( message_filter[meaning] )
			displayed = true ;	
	} else { // None
		//log('Unknown type for meaning : '+typeof meaning+' : '+text) ;
		var m = 'text' ;
		cell.classList.add(m) ; // Defaults
		if ( message_filter[m] )
			displayed = true ;	
	}
	// DOM
	if ( iso(dom) && ( dom != null ) )
		cell.appendChild(dom) ;
	// Filter
	if ( ! displayed )
		cell.classList.add('filtered') ;
	// Scroll
	if ( scrbot == 0 )
		chatbox.scrollTop = chatbox.scrollHeight ;
}
function init_chat(options) {
	logtext = new Array() ;
	// Chat, must be declared before using of "log"
		// Filtering
	message_filter = {
		join: true,
		text: true,
		win: true,
		turn: true,
		step: true,
		life: true,
		poison: true,
		zone: true,
		tap: true,
		attack: true,
		note: true,
		counter: true,
		pow_thou: true,
		side: true,
		target: true,
		bug: options.get('debug')
	}
	message_filter_name = {
		join: 'Joins / parts',
		text: 'Texts',
		win: 'Wins / Loses',
		turn: 'New turns',
		step: 'Step enter',
		life: 'Life changes',
		poison: 'Poison counters',
		zone: 'Zone changes, tokens, duplicates',
		tap: 'Tap / untap',
		attack: 'Attack / remove from attackers',
		note: 'Notes and other card attributes (reveal, face down ...)',
		counter: 'Counters change',
		pow_thou: 'Power / thoughness changes',
		side: 'Side',
		target: 'Targeting',
		bug: 'Debug'
	}
	all_message_filter = true ;
	document.getElementById('log_close').addEventListener('click', function(ev){
		document.getElementById('log_window').classList.remove('disp')
	}, false) ;
	document.getElementById('log_clear').addEventListener('click', function(ev){
		while ( logtext.length > 0 ) 
			logtext.shift() ;
		document.getElementById('log_window').classList.remove('disp')
	}, false) ;
	document.getElementById('chatbox').addEventListener('contextmenu', message_filter_contextmenu, false) ;
}
function chat_start() {
	chatbox = document.getElementById('chatbox') ; // Caching those requests' result
	sendbox = document.getElementById('sendbox') ;
	chat_messages = new Array() ;
	chat_pointer = null ; // For up/down-keys chat history navigation
	chat_pending = '' ;
	document.getElementById('chat').addEventListener('submit', function(ev) { // Pressing enter when focused on sendbox
		ev.preventDefault() ; // Don't let browser manage submission (would refresh page)
		if ( sendbox.value != '' ) { // Don't send empty string
			txt_send(sendbox.value) ;
			chat_messages.push(sendbox.value) ; // Remember all messages for up/down keys
			chat_pointer = null ;
			sendbox.value = '' ;
		}
		return false ;
	}, false) ;
}
// Autotext (buttons under chatbox with automatic replies)
function autotext_buttons() {
	var autotexts = game.options.get('autotext').split('\n') ;
	autotexts = autotexts.filter(function(param) { return ( param != '' ) ; })
	var autotext_div = document.getElementById('autotext') ;
	node_empty(autotext_div) ;
	for ( i in autotexts )
		autotext_div.appendChild(create_button(autotexts[i], function(ev){ txt_send(this.firstChild.nodeValue) ; })) ;
	var but = create_button('...', function(ev) {
		document.getElementById('autotext_area').value = game.options.get('autotext') ;
		document.getElementById('autotext_window').classList.toggle('disp') ;
	},'Edit buttons') ;
	autotext_div.appendChild(but) ;
}
function autotext_init() {
	var b_ok = document.getElementById('autotext_ok') ;
	b_ok.addEventListener('click', function(ev) {
		game.options.set('autotext', document.getElementById('autotext_area').value)
		document.getElementById('autotext_window').classList.remove('disp') ;
		autotext_buttons() ; // Refresh buttons with new value
		resize_window() ; // Recompute dimensions, as autotext buttons may 
	}, false) ;
	b_ok.style.left = '0px' ;
	var b_cancel = document.getElementById('autotext_cancel') ;
	b_cancel.addEventListener('click', function(ev) {
		document.getElementById('autotext_window').classList.remove('disp') ;
	}, 'Discard changes to buttons') ;
	b_cancel.style.right = '0px' ;
}
