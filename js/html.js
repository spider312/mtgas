//HTML
function replaceURLWithHTMLLinks(text, external) { // https://stackoverflow.com/questions/19547008/how-to-replace-plain-urls-with-links-with-example + external
	var re = /(\(.*?)?\b((?:https?|ftp|file):\/\/[-a-z0-9+&@#\/%?=~_()|!:,.;]*[-a-z0-9+&@#\/%=~_()|])/ig;
	return text.replace(re, function(match, lParens, url) {
		var rParens = '';
		lParens = lParens || '';
		// Try to strip the same number of right parens from url
		// as there are left parens.  Here, lParenCounter must be
		// a RegExp object.  You cannot use a literal
		//     while (/\(/g.exec(lParens)) { ... }
		// because an object is needed to store the lastIndex state.
		var lParenCounter = /\(/g;
		while (lParenCounter.exec(lParens)) {
			var m;
			// We want m[1] to be greedy, unless a period precedes the
			// right parenthesis.  These tests cannot be simplified as
			//     /(.*)(\.?\).*)/.exec(url)
			// because if (.*) is greedy then \.? never gets a chance.
			if (m = /(.*)(\.\).*)/.exec(url) ||
					/(.*)(\).*)/.exec(url)) {
				url = m[1];
				rParens = m[2] + rParens;
			}
		}
		let target = external ? ' target="_blank"' : '' ;
		return lParens + '<a href="' + url + '"' + target + '>' + url + "</a>" + rParens;
	});
}
// Cookies
function cookie_set(name, value, days) {
	if (!isn(days)) 
		var days = 365 ; // Default expire 1 year
	var date = new Date();
	if ( !iss(value) ) {
		days = -1 ;
		value = '' ;
	}
	date.setTime(date.getTime()+(days*24*60*60*1000));
	var expire = date.toGMTString();
	var cookie = name+"="+value+"; Expires="+expire+"; Path=/";
	document.cookie = cookie ;
}
// Notifications
function notification_send(title, txt, tag) {
	new Promise((resolve, reject) => {
		if ( game.options.get('notification_sound') ) {
			let src = url + '/themes/' + theme + '/Sounds/welcome.wav' ;
			let snd = new Audio(src) ;
			snd.play() ;
		}
		Notification.requestPermission((resp) => {
			resolve(resp) ;
		}) ;
	}).then((resp) => {
		switch ( resp ) {
			case 'granted' : {
				options = { "body": txt, "requireInteraction": ! game.options.get('notification_autoclose') } ;
				if ( game.options.get('notification_icon') ) {
					options.icon = 'themes/jay_kay/Mogg Maniac.crop.png' ;
				}
				if ( iss(tag) ) { options.tag = tag ; }
				return new Notification(title, options) ;
			} break ;
			case 'denied' : {
				console.info('Notification : ['+tag+'] '+title+' - '+txt) ;
			} break ;
			default: 
				console.error('notification_send : Unknown response '+resp) ;
		}
	}).catch((msg) => {
		console.error('notification_send : '+msg)
	}) ;
}
// Ajaj
function ajax_error_management() {
	$.ajaxSetup({'error': function(XMLHttpRequest, textStatus, errorThrown) {
		if ( ( errorThrown != undefined ) && ( errorThrown != '' ) && ( XMLHttpRequest.responseText != '' ) )
			alert('Ajax error : '+textStatus+', '+errorThrown+', ['+XMLHttpRequest.responseText+']') ;
	}});
}
// Events
function eventStop(ev) { // Cancel event's management by browser, to avoid unwanted drag'n'drop for example
	ev.preventDefault() ;
	ev.stopPropagation() ;
	return false ;
}
function eventLog(ev, from) {
	//return false ;
	var res = ev.type+' on '+ev.target ;
	if ( iss(from) )
		res = from+' : '+res
	if ( iso(ev.target) && iso(ev.target.thing) )
		res += ' ('+ev.target.thing+')' ;
	log(res) ;
}
// Timer
function start_timer(node, date, countdown) {
	if ( node.timer )
		window.clearInterval(node.timer) ;
	update_timer(node, date, countdown) ;
	node.timer = window.setInterval(update_timer, 1000, node, date, countdown) ;
	return node.timer ;
}
function update_timer(node, date, countdown) {
	now = new Date() ;
	if ( countdown )
		var duration = mysql2date(date, game.connection.offset) - now ;
	else
		var duration = now - mysql2date(date, game.connection.offset) ;
	var disp = time_disp(Math.round(duration/1000)) ;
	if ( node.nodeName == 'INPUT' ) {
		node.value = disp ;
	} else {
		node_empty(node) ;
		node.appendChild(create_text(disp)) ;
	}
}
// Form
function form2param(form) { // Returns an object of 'name -> value' for all elements of 'form' (without submit and unchanged values)
	var result = {} ;
	for ( var i = 0 ; i < form.elements.length ; i++) {
		var el = form.elements[i] ;
		if ( el.type == 'submit' )
		       continue ;
		result[el.name] = el.value ;
	}
	return result ;
}
// Basic document management
function document_add_css(doc, url) {
	if ( ! isf(doc.createElement) ) {
		alert(doc) ;
		return null ;
	}
	var mycss = doc.createElement('link') ;
	mycss.rel = 'stylesheet' ;
	mycss.type = 'text/css' ;
	mycss.href = url ;
	doc.documentElement.firstChild.appendChild(mycss)
}
// Basic node management
function addAndScroll(container, toAdd) { // Adds a node to a container, then scroll bottom if it was scrolled
	var scrbot = container.scrollHeight - ( container.scrollTop + container.clientHeight ) ;
	container.appendChild(toAdd) ;
	if ( scrbot === 0 ) {
		container.scrollTop = container.scrollHeight ;
	}
}
function node_empty() {
	for ( var i = 0 ; i < arguments.length ; i++) {
		var node = arguments[i] ;
		if ( isf(node.hasChildNodes) ) {
			while ( node.hasChildNodes() )
				node.removeChild(node.firstChild) ;
		} else
			log2(node) ;
	}
	return node ;
}
function node_parent_search(node, tagname) {
	var result = node ;
	do {
		if ( 'tagName' in result )
			if ( result.tagName == tagname )
				break ;
		if ( result.parentNode )
			result = result.parentNode ;
	} while ( result.parentNode ) ;
	return result ;
}
function nodelist_search(nodelist, element) {
	for ( var i = 0 ; i < nodelist.length ; i++ )
		if ( nodelist.item(i) == element)
			return i ;
	return -1 ;
}
// Basic HTML elements creation
	// Lib
function create_element(tagname) {
	var el = document.createElement(tagname) ;
	for ( var i = 1 ; i < arguments.length ; i++) {
		switch ( typeof arguments[i] ) {
			case 'undefined':
				break ;
			case 'number' :
				arguments[i] += '' ; // Transtyping
			case 'string' :
				el.appendChild(document.createTextNode(arguments[i])) ;
				break ;
			case 'object' :
				el.appendChild(arguments[i]) ;
				break ;
			default :
				alert('Error in param #'+i+'type for "create_element" : '+typeof arguments[i]) ;
		}
	}
	return el ;
}
	// Basic HTML
function create_text(text) {
	return document.createTextNode(text) ;
}
function create_div(content) {
	return create_element('div', content) ;
}
function create_span() {
	var span = create_element('span', arguments[0]) ;
	for ( var i = 1 ; i < arguments.length ; i++)
		if ( iso(arguments[i]) )
			span.appendChild(arguments[i]) ;
		else
			span.appendChild(create_text(arguments[i])) ;
	return span
}
function create_img(src, alt, title) {
	var img = document.createElement('img') ;
	if ( issn(src) )
		img.src = src
	if ( issn(alt) )
		img.alt = alt ;
	if ( issn(title) )
		img.title = title ;
	return img ;
}
function create_a(text, href, onclick, title) {
	var a = document.createElement('a') ;
	if ( text == null )
		text = 'null' ;
	if ( issn(text) )
		a.appendChild(document.createTextNode(text)) ;
	else
		a.appendChild(text) ;
	if ( issn(href) )
		a.href = href ;
	if ( isf(onclick) )
		a.addEventListener('click', onclick, false) ;
	if ( issn(title) )
		a.title = title ;
	return a ;
}
function create_ul(id) {
	var ul = document.createElement('ul') ;
	if ( iss(id) )
		ul.id = id ;
	return ul ;
}
function create_li(text, classname) {
	var li = document.createElement('li') ;
	if ( issn(text) )
		li.appendChild(document.createTextNode(text)) ;
	else if ( text )
		li.appendChild(text) ;
	if ( iss(classname) )
		li.className = classname ;
	return li ;
}
function create_h(n, text) {
	return create_element('h'+n, text) ;
}
function create_canvas(width, height) {
	var canvas = document.createElementNS("http://www.w3.org/1999/xhtml","canvas") ;
	if ( isn(width) )
	canvas.width = width ;
	if ( isn(height) )
		canvas.height = height ;
	return canvas ;
}
	// Strictly form
function create_form(action, method) {
	var form = document.createElement('form') ;
	if ( iss(action) )
		form.action = action ;
	if ( ( method == 'get' ) || ( method == 'post' ) )
		form.method = method ;
	else
		form.method = 'get' ;
	for ( var i = 2 ; i < arguments.length ; i++)
		form.appendChild(arguments[i]) ;
	return form ;
}
function create_submit(name, value, id, classname) {
	var submit = document.createElement('input') ;
	submit.type = 'submit' ;
	if ( iss(id) ) {
		submit.id = id ;
	}
	if ( iss(name) ) {
		submit.name = name ;
	}
	if ( iss(value) ) {
		submit.value = value ;
	}
	if ( iss(classname) ) {
		submit.className = classname ;
	}
	return submit ;
}
function create_checkbox(name, checked, id, value) {
	var checkbox = document.createElement('input') ;
	checkbox.type = 'checkbox' ;
	if ( issn(name) )
		checkbox.name = name ;
	checkbox.checked = checked ;
	if ( issn(id) )
		checkbox.id = id ;
	if ( issn(value) )
		checkbox.value = value ;
	return checkbox ;

}
function create_input(name, value, id, placeholder) {
	var text = document.createElement('input') ;
	text.type = 'text' ;
	if ( iss(name) ) {
		text.name = name ;
	}
	if ( iss(value) ) {
		text.value = value ;
	}
	if ( iss(id) ) {
		text.id = id ;
	}
	if ( iss(placeholder) ) {
		text.placeholder = placeholder ;
	}
	return text ;
}
function create_password(name, value, id) {
	var text = document.createElement('input') ;
	text.type = 'password' ;
	if ( issn(name) )
		text.name = name ;
	text.value = ''+value ;
	if ( issn(id) )
		text.id = id ;
	return text ;
}
function create_hidden(name, value) {
	var hidden = document.createElement('input') ;
	hidden.type = 'hidden' ;
	if ( issn(name) )
		hidden.name = name ;
	if ( issn(value) )
		hidden.value = value ;
	return hidden ;
}
function create_radio(name, value, checked, text, classname) {
	var radio = document.createElement('input') ;
	radio.type = 'radio' ;
	radio.name = name ;
	radio.value = value
	radio.checked = checked ;
	if ( issn(text) ) {
		var label = document.createElement('label') ;
		label.appendChild(radio) ;
		label.appendChild(document.createTextNode(text)) ;
		if ( issn(classname) )
			label.className = classname ;
		return label ;
	}
	if ( issn(classname) )
		radio.className = classname ;
	return radio ;
}
function create_file(name, title) {
	var file = document.createElement('input') ;
	file.type = 'file' ;
	if ( issn(name) )
		file.name = name ;
	if ( issn(title) )
		file.title = title ;
	return file ;
}
	// Form
function create_fieldset(legend) {
	var fieldset = document.createElement('fieldset') ;	
	fieldset.appendChild(create_element('legend', legend)) ;
	for ( var i = 1 ; i < arguments.length ; i++) {
		switch ( typeof arguments[i] ) {
			case 'undefined':
				break ;
			case 'number' :
			case 'string' :
				fieldset.appendChild(document.createTextNode(arguments[i])) ;
				break ;
			case 'object' :
				fieldset.appendChild(arguments[i]) ;
				break ;
			default :
				alert('Error in param #'+i+'type for "create_element" : '+typeof arguments[i]) ;
		}
	}
	return fieldset ;
}
function create_label(target) {
	var mylabel = document.createElement('label') ;
	for ( var i = 1 ; i < arguments.length ; i++) {
		switch ( typeof arguments[i] ) {
			case 'string' :
				var el = document.createTextNode(arguments[i]) ;
				break ;
			case 'object' :
				var el = arguments[i] ;
				break ;
			default :
				//alert(i+'/'+arguments.length+' : '+typeof arguments[i]) ;
				continue ;
		}
		mylabel.appendChild(el) ;
	}
	if ( !iss(target) ) { // htmlFor should be a string
		if ( ( target != null ) && issn(target.id) )
			target = target.id ;
		else
			target = null ;
	}
	if ( target != null )
		mylabel.htmlFor = target ;
	return mylabel ;
}
function create_button(content, onclick, title, classname) {
	var button = document.createElement('button') ;
	button.type = 'button' ;
	if ( issn(content) )
		button.appendChild(document.createTextNode(content)) ;
	else
		button.appendChild(content) ;
	if ( isf(onclick) )
		button.addEventListener('click', onclick, false) ;
	if ( issn(title) )
		button.title = title ;
	if ( issn(classname) )
		button.className = classname ;
	return button ;
}
function create_select(name, id) {
	var select = document.createElement('select') ;
	if ( issn(name) )
		select.name = name ;
	if ( issn(id) )
		select.id = id ;
	return select ;
}
function create_option(text, value, title) {
	var option = document.createElement('option') ;
	if ( iss(text) )
		option.text = text ;
	if ( iss(value) )
		option.value = value ;
	if ( iss(title) )
		option.title = title ;
	else
		option.title = value ;
	return option ;
}
	// Table
function create_tr(table) {
	// Search the table node (in case 'table' is a thead/foot/body)
	var node = table ;
	if ( node != null ) {
		while ( node.tagName != 'TABLE' )
			node = node.parentNode ;
		// Search the columns number
		var cols = 1 ;
		if ( node.rows.length > 0 )
			for ( var i = 0 ; i < node.rows.length ; i++ )
				if ( node.rows[i].cells.length > cols )
					cols = node.rows[0].cells.length ;
		// Add wanted row to table
		var row = table.insertRow(-1) ;
	} else
		var row = create_element('tr') ;
	// Add cells to this row
	for ( var i = 1 ; i < arguments.length ; i++ ) {
		var colspan = false ;
		if ( i == arguments.length - 1 ) // Last column
			if ( arguments.length - 1 < cols ) // This row has less columns than table
				colspan = cols - ( arguments.length - 1 ) + 1 ; // colspan to complete
		create_td(row, arguments[i], colspan) ;
	}
	return row ;
}
function create_td(row, text, colspan) {
	if ( row ) {
		var cell = row.insertCell(-1) ;
	} else {
		var cell = document.createElement('td') ;
	}
	var child = null ;
	switch ( typeof text ) {
		case 'undefined' :
			text = 'undef' ;
			break ;
		case 'number' :
		case 'string' :
			child = document.createTextNode(text)
			child.draggable = false ;
			break ;
		case 'object' :
			child = text
			break ;
		default : 
			alert(typeof text) ;
	}
	if ( child !== null ) {
		cell.appendChild(child) ;
	}
	if ( colspan )
		cell.colSpan = colspan ;
	return cell ;
}
// Popup
function popup(title, ok_func, ok_title, cancel_func, cancel_title) {
	// Fake window
	var win = create_div() ;
	win.id = 'choicewin' ;
	// Title
	var title_div = create_div(title) ;
	title_div.classList.add('title') ;
	title_div.title = title ;
	// Buttons
	var div_but = create_div() ;
	div_but.classList.add('buttons') ;
	var but_ok = create_button(create_img(theme_image('deckbuilder/button_ok.png')[0], 'OK'), ok_func, ok_title) ;
	but_ok.id = 'button_ok' ;
	div_but.appendChild(but_ok) ;
	var but_can = create_button(create_img(theme_image('deckbuilder/button_cancel.png')[0], 'Cancel'), cancel_func, cancel_title) ;
	but_can.id = 'button_cancel' ;
	div_but.appendChild(but_can) ;
	// Childs
	win.appendChild(title_div) ;
	win.appendChild(div_but) ;
	document.body.appendChild(win) ; // Must be done in order to get offsetHeights
	but_ok.focus() ;
	return win ;
}
function popup_resize(win, w, h) {
	if ( !isn(h) ) {
		var h = 5 ; // ?
		for ( var i = 0 ; i < win.children.length ; i++ )
			h += win.children[i].offsetHeight ;
	}
	win.style.height = h + 'px' ;
	win.style.left = ( paperwidth - w ) / 2 + 'px' ;
	win.style.top = ( paperheight - h ) / 2 + 'px' ;
}
function popup_li(card, ul) {
	var li = create_li(card.name) ;
	li.card = card ;
	game.image_cache.load(card.imgurl(),function(img, li) { // Load image
		node_empty(li) ;
		li.style.backgroundImage = 'url(\"'+img.src+'\")' ;
	}, function(li) {}, li) ;
	// Events
	li.addEventListener('mouseover', function(ev) { // Hover zoom
		if ( ev.target.card )
			ev.target.card.zoom() ;
		else
			log(ev.target) ;
	}, false) ;
	ul.appendChild(li) ;
	return li ;
}
// Color
function random_color() {
	var rint = Math.round(0xffffff * Math.random());
	return (/*'0x' +*/'#'+ rint.toString(16)).replace(/^#0([0-9a-f]{6})$/i, '#$1');
}
// JSON
function JSON_parse(text) { /* Wrapper to parse JSON with exception management */
        if ( ! iss(text) ) // We only can parse a string
		return text ;
	if ( text == '' )
		return null ;
	var res = text ;
	try {
		res = JSON.parse(text) ;
	} catch (e) {
		//log2('Crash when parsing JSON : ['+text+']') ;
		//log2(e) ;
		//log(stack_trace(JSON_parse)) ;
		res = null ; // An object is expected, return one empty
	}
	return res ;
}
