// network.js : functions for communicating with server, let's try to well modularize choice between AJAJ and WebSocket
// + chat utils
function network_loop() { // Things to do regulary
	active_player = game.player ; // Used to distinguish actions initiated by current player and those initiated by its opponent and transmitted by net
	if ( ! game.websockets ) { // Ajax
		toid = null ;
		querydate = new Date() ;
		$.getJSON('json/actions.php', {'game': game.id, 'from': last_recieved_action}, manage_actions) ; // Get all new actions from server and send them to manage
	} else { // Websockets
		//window.WebSocket = window.WebSocket || window.MozWebSocket;
		if ( ! window["WebSocket"] ) {
			log('No support for websockets in browser') ;
			return false ;
		}
		// Init websocket and connect
		game.connection = new WebSocket('ws://dev.mogg.fr:1337/server');
		// Add event handlers
		game.connection.onopen = function (ev) {
			log('Connection opened') ;
			game.socket_registration.from = last_recieved_action ;
			game.connection.send(JSON.stringify(game.socket_registration)) ; // Register to server
		};
		game.connection.onerror = function (ev) { // Just get an event error containing no info, but triggers onclose
			log('Error, reconnecting in 10 sec') ;
			setTimeout(retry_connection, 10000) ;
			ev.preventDefault() ;
		};
		game.connection.onclose = function (ev) {
			log('Connection closed : ' + ev.reason + ' ('+ev.code+')') ;
			ev.preventDefault() ;
		};
		game.connection.onmessage = function (message) {
			try {
				var json = JSON.parse(message.data);
			} catch (e) {
				log('NOK : '+message.data) ;
			}
			if ( json.type == 'recieve' ) { // Server informs us it recieved last sent stacked action
				if ( json.val > last_recieved_action ) // Consider as last recieved action (for not reasking it if server restarts)
					last_recieved_action = json.val ;
				// Management result
				if ( ( iss(json.msg) ) && ( json.msg != '' ) )
					alert(data.msg) ;
				if ( json.newround ) {
					new_round() ;
					return false ;
				}
				// Callback
				if ( game.action_stack.length > 0 ) { // We recieve a response ton sending of action game.action_stack[0]
					var action = game.action_stack.shift() ;
					var param = JSON_parse(action[0]) ;
					param.param = JSON_parse(param.param) ;
					for ( var i in param ) // Merging sent object with results from server
						json[i] = param[i] ;
					var callback = action[1] ;
					if ( callback != null )
						callback(json) ;
					// Manage next stacked action
					if ( game.action_stack.length > 0 )
						action_unstack() ;
				} else
					log('No action to unstack') ;
			} else
				manage_action(json) ;
		};
	}
}
function manage_actions(round) {
	ping = new Date() - querydate ;
	// Display message transmitted by server
	if ( ( iss(round.msg) ) && ( round.msg != '' ) )
		alert(round.msg) ;
	// Redirect to next round if in tournament
	if ( round.status == 7 ) {
		new_round()
		return false ;
	}
	// Display opponent's lagometter
	if ( round.opponent_lag > 5 )
		game.infobulle.set('Opponent\'s inactivity : '+time_disp(round.opponent_lag)) ;
	// Display round's time left / game's elapsed time
	if ( round.timeleft )
		var time = time_disp(round.timeleft)+ ' left in round' ;
	else
		var time = time_disp(round.age) ;
	timeleft.firstChild.nodeValue = time ;
	// Recieving actions while tab has not focus : inform user
	var hasfocus = false ;
	if ( document.hasFocus )
		hasfocus = document.hasFocus() ;
	if ( ( round.actions.length > 0 ) && ! hasfocus ) {
		if ( unseen_actions == 0 ) { // Display last seen line
			var rows = document.getElementById('chathisto').rows
			for ( var i = 0 ; i < rows.length ; i++ )
				rows[i].classList.remove('lastseen') ; // Remove previously marked
			if ( rows.length > 0 )
				rows[rows.length-1].classList.add('lastseen') ; // Mark last line
		}
		unseen_actions += round.actions.length ;
		document.title = '('+unseen_actions+') '+init_title ;
		//window.getAttention() ; // Doesn't work
		//window.focus() ; // Neither
	}
	// For each action recieved : manage it
	for ( var i in round.actions ) {
		try {
			manage_action(round.actions[i]) ;
		} catch (e) {
			log(e) ;
		}
	}
	draw() ; // All recieved actions managed, refresh ping
	active_player = game.player ; // Reinitialize, all actions intented outside this function was intented by current player
	recieve_time = null ;
	sent_time = null ;
	toid = window.setTimeout(network_loop, ajax_interval) ; // Don't try to get new actions before all recieved are managed (to avoid multi-managing)
}
function retry_connection() {
	log('Reconnecting') ;
	network_loop() ;
}
/* Actions sending management */
function action_send(type, obj, callback) {
	var mydate = new Date() ;
	var params = { 'game': game.id, 'type': type, 'local_index': Math.floor(mydate.getTime()/1000)} ; // Initial params with timestamp
	if ( obj ) {
		try {
			param = JSON.stringify(obj) ;
		} catch (e) {
			var param = clone(obj) ; // obj will be stringified, do not modify original copy
			for ( var i in param )
				if ( ( typeof param[i] == 'object' ) && (param[i] != null ) )
					for ( var j in param[i] )
						if ( ( typeof param[i][j] == 'object' ) && (param[i][j] != null ) )
							param[i][j] = param[i][j].toString() ; // Avoid deep stringification
			var param = JSON.stringify(param) ;
		}
		params.param = param.replace(/\'/g,"\\\'") ;
	}
	if ( ! isf(callback) )
		callback = null ;
	var l = game.action_stack.length ;
	game.action_stack.push(new Array(params, callback)) ;
	if ( l == 0 ) // Stack was empty, unstacking was disabled
		return action_unstack() ;
		//return $.getJSON('json/action_send.php', params, callback) ;
}
function action_unstack() {
	var action = game.action_stack[0] ;
	var params = action[0] ;
	var callback = action[1] ;
	if ( ! game.websockets ) {
		return $.post('json/action_send.php', params, function(data) {
			game.action_stack.shift() ;
			if ( ( iss(data.msg) ) && ( data.msg != '' ) )
				log(data.msg) ;
			if ( data.newround ) {
				new_round() ;
				return false ;
			}
			if ( callback != null )
				callback(data) ;
			if ( game.action_stack.length > 0 )
				action_unstack() ;
		}, 'json') ;
	} else
		game.connection.send(JSON.stringify(params)) ;
}
/* Chat management */
function txt_recieve(text) {
	message('<'+active_player.name+'> '+text) ;
}
function txt_send(text) {
	action_send('text', {'text': text}) ;
	if ( spectactor ) {
		var p = active_player ;
		active_player = game.spectactors[$.cookie(session_id)] ;
	}
	txt_recieve(text) ;
	if ( spectactor )
		active_player = p ;
}
function message_filter_contextmenu(ev) {
	var menu = new menu_init(this) ;
	menu.addline('Messages') ;
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
	ev.preventDefault() ;
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
function message(text, meaning, other_meaning) {
	if ( typeof meaning != 'string' )
		meaning = 'text'
	chatbox = $('#chatbox')[0] ;
	var scrbot = chatbox.scrollHeight - ( chatbox.scrollTop + chatbox.clientHeight ) ; // Scroll from bottom, if 0, will scroll to see added line
	var table = document.getElementById('chathisto') ;
	var row = table.insertRow(-1) ; // At the end
	var cell = row.insertCell(-1) ;
	var title = '' ;
	if ( sent_time == null ) {
		var r = new Date() ;
		title += 'Sent '+r.getHours()+':'+r.getMinutes()+':'+r.getSeconds() ;
	} else
		title += 'Sent '+sent_time.getHours()+':'+sent_time.getMinutes()+':'+sent_time.getSeconds() ;
	if ( recieve_time != null ) {
		var r = recieve_time ;
		title += ', Recieved '+recieve_time.getHours()+':'+recieve_time.getMinutes()+':'+recieve_time.getSeconds() ;
	}
	cell.title = title ;
	if ( iss(text) )
		cell.appendChild(document.createTextNode(text)) ;
	else
		cell.appendChild(text) ;
	cell.classList.add(meaning) ;
	if ( iss(other_meaning) )
		cell.classList.add(other_meaning) ;
	if ( ! message_filter[meaning] )
		cell.classList.add('filtered') ;
	if ( scrbot == 0 )
		chatbox.scrollTop = chatbox.scrollHeight ;
}
function init_chat() {
	logtext = new Array() ;
	// Chat, must be declared before using of "log"
		// Filtering
	message_filter = {
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
		bug: ( localStorage['debug'] == 'true' )
	}
	message_filter_name = {
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
	document.getElementById('timeleft').addEventListener('contextmenu', message_filter_contextmenu, false) ;
	document.getElementById('info').addEventListener('contextmenu', message_filter_contextmenu, false) ;
	document.getElementById('chathisto').addEventListener('contextmenu', message_filter_contextmenu, false) ;
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
	if ( ! iss(localStorage.autotext) || ( localStorage.autotext == '' ) )
		store('autotext', 'Ok\nOk?\nWait!\nThinking\nEnd my turn\nEOT') ; // Default values for autotext
	var autotexts = localStorage.autotext.split('\n') ;
	autotexts = autotexts.filter(function(param) { return ( param != '' ) ; })
	var autotext_div = $('#autotext')[0] ;
	node_empty(autotext_div) ;
	for ( i in autotexts )
		autotext_div.appendChild(create_button(autotexts[i], function(ev){ txt_send(this.firstChild.nodeValue) ; })) ;
	var but = create_button('...', function(ev) {
		document.getElementById('autotext_area').value = localStorage.autotext ;
		document.getElementById('autotext_window').classList.toggle('disp') ;
	},'Edit buttons') ;
	autotext_div.appendChild(but) ;
}
function autotext_init() {
	var b_ok = document.getElementById('autotext_ok') ;
	b_ok.addEventListener('click', function(ev) {
		store('autotext', document.getElementById('autotext_area').value) ;
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
