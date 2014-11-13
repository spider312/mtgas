if ( typeof log != 'function' )
	function log(obj) {
		return debug(obj) ;
	}
function log2(obj) {
	return debug(obj) ;
}
function debug(obj) { // Main function : launch debug on an object
	var div = debug_div() ;
	div.classList.remove('hidden') ;
	var cache = [] ; // Displayed objects cache in order to only display once each object
	debug_print_r(div.firstElementChild, obj, cache) ;
	div.scrollTop = div.scrollHeight ;
}
function debug_print_r(container, obj, cache, prefix) { // Display recursively an object
	switch ( typeof obj ) {
		case 'undefined' :
			debug_li(container, 'Undefined', prefix) ;
			break ;
		case 'boolean' : // Display basic types "as-is"
		case 'number' :
		case 'string' :
			debug_li(container, obj+' ('+typeof obj+')', prefix) ;
			break ;
		case 'function' : // Just function name for functions
			debug_li(container, functionname(obj), prefix) ;
			break ;
		case 'object' : // Recursive object display
			if ( inarray(obj, cache) )
				debug_li(container, obj+' ->', prefix) ;
			else {
				var objname = 'null' ;
				if ( obj != null ) {
					cache.push(obj) ;
					objname = obj.toString() ;
				}
				if ( obj instanceof Array )
					objname = '[Array('+obj.length+')]' ;
				var li = debug_li(container, objname, prefix) ;
				// Don't detail HTML Elements, Window nor Document unless directly asked
				if ( ! (
					( prefix != undefined ) && (
						( obj instanceof Element )
						|| ( obj instanceof Window )
						|| ( obj instanceof HTMLDocument )
						//|| ( obj instanceof CSS2Properties )
					)
				) ) {
					var ul = create_ul() ;
					li.appendChild(ul) ;
					for ( var i in obj )
						debug_print_r(ul, obj[i], cache, i) ;
				}
				li.addEventListener('click', function(ev) {
					this.classList.toggle('shrink') ;
					return eventStop(ev) ;
				}, false)

			}
			break ;
		default :
			alert(typeof obj) ;
	}
}
function debug_li(container, text, prefix) { // Display one debug line
	if ( text == '' )
		text = '[empty string]' ;
	if ( iss(prefix) )
		text = prefix+' : '+text ;
	var li = create_li(text) ;
	li.title = new Date() ;
	container.appendChild(li) ;
	return li ;
}
function debug_close(ev) { // "Closes" (hides) debug div
	var div = debug_div() ;
	div.classList.add('hidden') ;
}
function debug_div() { // Finds debug div or create if needed
	var div = document.getElementById('debug') ;
	if ( div == null ) {
		var ul = create_ul() ;
		ul.id = 'debug_ul' ;
		var clear = create_button('Clear', function(ev) {
			node_empty(div.firstElementChild) ;
			debug_close(ev) ;
		}) ;
		var close = create_button('Close', debug_close) ;
		var form = create_form() ;
		var input = create_input('code', '') ;
		form.appendChild(input) ;
		form.addEventListener('submit', function(ev) {
			eventStop(ev) ;
			try {
				var val = eval(ev.target.code.value) ;
				debug(' -> '+ev.target.code.value+' : ') ;
				debug(val) ;
				ev.target.code.value = '' ;
			} catch(e) {
				debug(e) ;
			}
			return false ;
		}, false) ;
		div = create_span(ul, form, clear, close) ;
		div.id = 'debug' ;
		document.body.appendChild(div) ;
	}
	return div ;
}
