// https://developer.mozilla.org/en-US/docs/Web/API/WebSocket
function Connexion(path, onmessage, onclose, registration_data) {
	// Accessors
	this.toString = function() {
		return 'websocket connexion to '+this.url ;
	}
	this.indicator_color = function(color, title) {
		if ( this.indicator != null ) {
			var img = theme_image('sphere/'+color+'.png') ;
			if ( this.indicator.src != img )
				this.indicator.src = img ;
			if ( ! iss(title) )
				title = 'not defined' ;
			this.title = title ;
			this.refresh_indicator() ;
		}
	}
	this.refresh_indicator = function() {
		var title = this.title ;
		if ( this.connexion === null ) {
			title += ' (not connected)' ;
		} else {
			title += ' (ping last/min/avg : '+this.ping_avg(1)+'/'+this.ping_best ;
			title += '/'+this.ping_avg(this.ping_times.length)+' ms)' ;
		}
		if ( this.extended ) {
			title += ' Offset : '+this.offset ;
		}
		this.indicator.title = 'Connexion : '+title ;
	}
	// Master Methods
	this.connect = function() {
		if ( this.timer == null )
			this.timer = setInterval.call(this, this.connect, 1000) ;
		if ( this.connexion != null )  {
			switch ( this.connexion.readyState ) {
				case this.connexion.CONNECTING :
					return null ;
				case this.connexion.OPEN :
					return null ;
				case this.connexion.CLOSING :
					return null ; // Create a double connection if retrying to connect during this state
				case this.connexion.CLOSED :
					break ;
				default :
					alert('Unknown ReadyState : '+this.connexion.readyState) ;
			}
		}
		this.indicator_color('orange', 'connecting') ;
		this.connexion = new WebSocket(this.url) ;
		var connexion = this ;
		this.connexion.addEventListener('open', function (ev) {
			connexion.indicator_color('green', 'connected before first ping') ;
			connexion.registration_data.nick = game.options.get('profile_nick') ;
			connexion.send(JSON.stringify(connexion.registration_data)) ;
			setTimeout.call(connexion, connexion.ping, connexion.ping_delay) ;
			if ( isf(connexion.onclose) )
				connexion.onclose(ev) ;
		}, false) ;
		this.connexion.addEventListener('close', function (ev) {
			connexion.indicator_color('red', 'disconnected') ;
			switch ( ev.code ) { // https://developer.mozilla.org/en-US/docs/Web/API/CloseEvent
				case 1000 : //CLOSE_NORMAL;
				case 1001 : //CLOSE_GOING_AWAY;
				case 1002 : //CLOSE_PROTOCOL_ERROR;
				case 1003 : //CLOSE_UNSUPPORTED
				case 1004 : //CLOSE_TOO_LARGE
				case 1005 : //CLOSE_NO_STATUS
				case 1006 : //CLOSE_ABNORMAL
					break ;
				default :
					alert('Unmanaged error code : '+ev.code+' - '+ev.reason) ;
			}
			if ( isf(connexion.onclose) )
				connexion.onclose(ev) ;
			connexion.connexion = null ;
		}, false) ;
		this.connexion.addEventListener('message', function (ev) {
			if ( ev.data === '' ) {
				console.info('Recieved empty data');
				return false ;
			}
			var data = null ;
			try {
				data = JSON.parse(ev.data) ;
			} catch (e) {
				console.error("Websocket can't parse JSON : "+ev.data) ;
			}
			if ( data == null ) {
				console.error('Unparsable data : '+ev.data) ;
				return false ;
			}
			switch ( data.type  ) { // Websocket messages interpreted by this
				case 'ping' : // Server sent ping, answer as soon as possible
					data.type = 'pong' ;
					connexion.send(JSON.stringify(data)) ;
					break ;
				case 'pong' : // Server replied to a ping, update indicator
					if ( connexion.pingtime(new Date() - connexion.ping_sent) ) {
						connexion.offset_update(data.time) ;
					}
					setTimeout.call(connexion, connexion.ping, connexion.ping_delay) ;
					break ;
				case 'ban' : // Server informed us we're ban, inform and stop connexion loop
					alert('You are banned : '+data.reason) ;
					connexion.close(1000, 'Banned : '+data.reason) ;
					break ;
				case 'time' : // Synchronize times sent
					connexion.offset_update(data.time) ;
					break ;
				default : // Each other message is sent to websocket connexion initializer
					if ( isf(connexion.onmessage) )
						connexion.onmessage(data, ev) ;
			}
			if ( connexion.debug ) {
				debug(' <- ') ;
				debug(data) ;
			}
		}, false) ;
		this.connexion.addEventListener('error', function (ev) {
			connexion.indicator_color('violet', 'Error') ;
		}, false) ;
	}
	this.offset_update = function(server_time) { // Recieved a server timestamp, update offset
		var time = parseInt(server_time, 10) ; // Server timestamp
		var now = Math.floor(Date.now()/1000) ; // Local timestamp, set on the same scale
		this.offset = time - now ;
	}
	this.ping = function() {
		this.send('{"type": "ping"}') ;
		this.ping_sent = new Date() ;
	}
	this.ping_avg = function(n) { // Returns an average of the last n measures
		var max = this.ping_times.length ;
		if ( isn(n) ) {
			n = min(n, max) ;
		} else {
			n = max ;
		}
		if ( n === 0 ) {
			return 0 ;
		}
		var result = 0 ;
		for ( var i = 1 ; i <= n ; i++ ) {
			result += this.ping_times[this.ping_times.length-i] ;
		}
		return Math.ceil(result / n) ;
	}
	this.pingtime = function(time) { // Recieved a new ping response
		var is_best = ( ( this.ping_best < 0 ) || ( time < this.ping_best ) ) ; // Returns if it was best
		this.ping_times.push(time) ; // Store
		// Don't store too much of them
		if ( this.ping_times.length > this.ping_max_times ) {
			this.ping_times.shift() ;
		}
		// Best ping
		if ( is_best ) {
			this.ping_best = time ;
		}
		// Update indicator
		if ( time < this.ping_limit ) {
			this.indicator_color('green', 'connected') ;
		} else {
			this.indicator_color('blue', 'laggy') ;
		}
		return is_best ;
	}
	this.send = function(param) {
		if ( iss(param) )
			data = param ;
		else
			data = JSON.stringify(param) ;
		if ( this.connexion != null )
			try {
				this.connexion.send(data) ;
			} catch (e) {
				this.indicator_color('violet', 'Exception ('+this.connexion.readyState+') - '+e.name) ;
			}
			if ( this.debug ) {
				param = JSON.parse(data) ;
				debug(' -> ') ;
				debug(param) ;
			}
	}
	this.close = function(code, reason) {
		if ( ! isn(code) )
			code = 1000 ;
		if ( ! iss(reason) )
			reason = 'no reason given' ;
		if ( this.connexion != null ) {
			this.connexion.close(code, reason) ;
			this.connexion = null ;
		}
		clearInterval(this.timer);
		this.timer = null ;
	}
	this.events = function() {
		window.addEventListener('blur', this.sendevent(this), false) ;
		window.addEventListener('focus', this.sendevent(this), false) ;
	}
	this.sendevent = function(cnx) {
		return function(ev) {
			cnx.send({'type': ev.type}) ;
		}
	}
	// Init
		// properties
	var connex = this ; // Closures
	this.connexion = null ;
	this.indicator = document.getElementById('wsci') ;
	this.indicator.addEventListener('click', function(ev) {
		connex.extended = ! connex.extended ;
	}, false) ;
	this.ping_sent = null ;
	this.ping_times = [] ; // Stored ping measurments
	this.ping_max_times = 100 ; // Max number of stored pings
	this.ping_delay = 5000 ; // Number of ms between reception and sending of a ping
	this.ping_limit = 200 ; // Below this ping (in ms) indicator is green, blue otherwise
	this.ping_best = -1 ;
	this.offset = null ; // Offset in dates with daemon
	this.debug = false ;
	this.extended = false ; // Extended information about ping in WSCI title
		// GUI
	this.indicator_color('violet', 'initializing') ;
		// params
	this.baseurl = 'ws://'+wshost+':'+wsport+'/' ;
	this.url = this.baseurl ;
	if ( iss(path) ) this.url += path ;
	if ( isf(onmessage) ) this.onmessage = onmessage ;
	else this.onmessage = null ;
	if ( isf(onclose) ) this.onclose = onclose ;
	else this.onclose = null ;
	if ( iso(registration_data) && ( registration_data != null ) )
		this.registration_data = registration_data ;
	else
		this.registration_data = {} ;
	this.registration_data.type = 'register' ;
	this.registration_data.player_id = player_id ;
		// actions
	if ( ! window["WebSocket"] )
		alert('No support for websockets in browser') ;
	else {
		window.addEventListener('beforeunload', function(ev) {
			connex.close(1000, 'User closing (or refreshing) window') ;
		}, false) ;
		this.connect() ;
	}
}
// Workaround for 'this' problem : https://developer.mozilla.org/en-US/docs/Web/API/Window.setTimeout
var __nativeST__ = window.setTimeout, __nativeSI__ = window.setInterval;
window.setTimeout = function (vCallback, nDelay /*, argumentToPass1, argumentToPass2, etc. */) {
	var oThis = this, aArgs = Array.prototype.slice.call(arguments, 2);
	return __nativeST__(vCallback instanceof Function ? function () {
		vCallback.apply(oThis, aArgs);
	} : vCallback, nDelay);
};
window.setInterval = function (vCallback, nDelay /*, argumentToPass1, argumentToPass2, etc. */) {
	var oThis = this, aArgs = Array.prototype.slice.call(arguments, 2);
	return __nativeSI__(vCallback instanceof Function ? function () {
		vCallback.apply(oThis, aArgs);
	} : vCallback, nDelay);
};
