// https://developer.mozilla.org/en-US/docs/Web/API/WebSocket
function Connexion(onmessage, onclose, url) {
	// Accessors
	this.toString = function() {
		return 'websocket connexion to '+this.url ;
	}
	this.indicator_color = function(color) {
		if ( this.indicator != null )
			this.indicator.src = theme_image(color+'led.png') ;
	}
	// Master Methods
	this.connect = function() {
		if ( this.connexion != null )  {
			switch ( this.connexion.readyState ) {
				case this.connexion.CONNECTING :
					return null ;
				case this.connexion.OPEN :
					/*if ( bench == null ) {
						bench = performance.now() ;
						this.connexion.send(JSON.stringify({'type': 'ping'})) ;
					}*/
					return null ;
				case this.connexion.CLOSING :
				case this.connexion.CLOSED :
					break ;
				default :
					alert('Unknown ReadyState : '+this.connexion.readyState)
			}
		}
		this.indicator_color('yellow') ;
		this.connexion = new WebSocket(this.url) ;
		var connexion = this ;
		this.connexion.addEventListener('open', function (ev) {
			connexion.indicator_color('green') ;
			ev.target.send(JSON.stringify({
				'type': 'register',
				'nick': game.options.get('profile_nick'),
				'player_id': $.cookie(session_id)
			})) ;
		}, false) ;
		this.connexion.addEventListener('close', function (ev) {
			connexion.indicator_color('red') ;
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
			if ( isf(this.onclose) )
				this.onclose(ev) ;
		}, false) ;
		this.connexion.addEventListener('message', function (ev) {
			var data = JSON.parse(ev.data) ;
			if ( data == null ) {
				alert('Unparsable data : '+ev.data) ;
				return false ;
			}
			switch ( data.type  ) {
				case 'ping' :
					/*wsci.title = Math.round(performance.now()-bench)+'ms' ;
					bench = null ;*/
					break ;
				default : 
					if ( isf(connexion.onmessage) )
						connexion.onmessage(data, ev) ;
			}
		}, false) ;
		this.connexion.addEventListener('error', function (ev) {
			shout_add('An error occured') ;
			wsconnect() ;
		}, false) ;
	}
	this.send = function(data) {
		if ( ! iss(data) )
			data = JSON.stringify(data) ;
		if ( this.connexion != null )
			this.connexion.send(data) ;
	}
	this.close = function(code, reason) {
		if ( this.connexion != null )
			this.connexion.close(code, reason) ;
	}
	// Init : params
	if ( iss(url) ) this.url = url ;
	else this.url = 'ws://dev.mogg.fr:1337/index' ;
	if ( isf(onmessage) ) this.onmessage = onmessage ;
	else this.onmessage = null ;
	if ( isf(onclose) ) this.onclose = onclose ;
	else this.onclose = null ;
	// Init : properties
	this.connexion = null ;
	this.indicator = document.getElementById('wsci') ;
	// Init : actions
	this.indicator_color('red') ;
	if ( ! window["WebSocket"] )
		alert('No support for websockets in browser') ;
	else {
		setTimeout.call(this, this.connect, 1000) ;
		setInterval.call(this, this.connect, 10000) ;
		window.addEventListener('beforeunload', function(ev) {
			this.close() ;
		}, false) ;
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
