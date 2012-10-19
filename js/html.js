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
	//ev.stopPropagation() ;
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
// Basic document management
function document_add_css(doc, url) {
	if ( typeof doc.createElement != 'function' ) {
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
function node_empty(node) {
	if ( isf(node.hasChildNodes) ) {
		while ( node.hasChildNodes() )
			node.removeChild(node.firstChild) ;
	} else
		log2(node) ;
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
function create_span(content) {
	return create_element('span', content) ;
}
function create_img(src, alt, title) {
	var img = document.createElement('img') ;
	if ( typeof src == 'string' )
		img.src = src
	if ( typeof alt == 'string' )
		img.alt = alt ;
	if ( typeof title == 'string' )
		img.title = title ;
	return img ;
}
function create_a(text, href, onclick, title) {
	var a = document.createElement('a') ;
	if ( typeof text == 'object' )
		a.appendChild(text) ;
	else
		a.appendChild(document.createTextNode(text)) ;
	if ( typeof href == 'string' )
		a.href = href ;
	if ( typeof onclick == 'function' )
		a.addEventListener('click', onclick, false) ;
	if ( typeof title == 'string' )
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
	li.appendChild(document.createTextNode(text)) ;
	if ( classname )
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
	if ( typeof id == 'string' )
		submit.id = id ;
	if ( typeof name == 'string' )
		submit.name = name ;
	if ( typeof value == 'string' )
		submit.value = value ;
	if ( typeof classname == 'string' )
		submit.className = classname ;
	return submit ;
}
function create_checkbox(name, checked, id, value) {
	var checkbox = document.createElement('input') ;
	checkbox.type = 'checkbox' ;
	if ( typeof name == 'string' )
		checkbox.name = name ;
	checkbox.checked = checked ;
	if ( typeof id == 'string' )
		checkbox.id = id ;
	if ( typeof value == 'string' )
		checkbox.value = value ;
	if ( typeof id == 'string' )
		checkbox.id = id ;
	return checkbox ;

}
function create_input(name, value, id) {
	var text = document.createElement('input') ;
	text.type = 'text' ;
	if ( typeof name == 'string' )
		text.name = name ;
	text.value = ''+value ;
	if ( typeof id == 'string' )
		text.id = id ;
	return text ;
}
function create_hidden(name, value) {
	var hidden = document.createElement('input') ;
	hidden.type = 'hidden' ;
	if ( typeof name == 'string' )
		hidden.name = name ;
	if ( typeof value == 'string' )
		hidden.value = value ;
	return hidden ;
}
function create_radio(name, value, checked, text, classname) {
	var radio = document.createElement('input') ;
	radio.type = 'radio' ;
	radio.name = name ;
	radio.value = value
	radio.checked = checked ;
	if ( typeof text == 'string' ) {
		var label = document.createElement('label') ;
		label.appendChild(radio) ;
		label.appendChild(document.createTextNode(text)) ;
		if ( typeof classname == 'string' )
			label.className = classname ;
		return label ;
	}
	if ( typeof classname == 'string' )
		radio.className = classname ;
	return radio ;
}
	// Form
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
				alert(typeof arguments[i]) ;
		}
		mylabel.appendChild(el) ;
	}
	mylabel.htmlFor = target ;
	return mylabel ;
}
function create_button(content, onclick, title, classname) {
	var button = document.createElement('button') ;
	if ( iss(content) || isn(content) )
		button.appendChild(document.createTextNode(content)) ;
	else
		button.appendChild(content) ;
	if ( typeof onclick == 'function' )
		button.addEventListener('click', onclick, false) ;
	if ( typeof title == 'string' )
		button.title = title ;
	if ( typeof classname == 'string' )
		button.className = classname ;
	return button ;
}
function create_option(text, value) {
	var option = document.createElement('option') ;
	option.text = text ;
	option.value = value ;
	return option ;
}
	// Table
function create_tr(table) {
	// Search the table node (in case 'table' is a thead/foot/body)
	var node = table ;
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
	var cell = row.insertCell(-1) ;
	var child = null ;
	switch ( typeof text ) {
		case 'undefined' :
			text = 'undef' ;
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
	cell.appendChild(child) ;
	if ( colspan )
		cell.colSpan = colspan ;
	return cell ;
}
// Advanced form functionnalities
function hide_menu() {
	$('#header').animate({
		opacity: 0,
	}, 1000, function() {
		$('#search,#decksection,#infos').animate({
			'top': 0,
			'height': '95%'
		}) ;
		// Animation complete.
	});
}
// User vars
function store(key, value) {
	if ( typeof value == 'undefined' )
		value = null ;
	if ( value == null )
		localStorage.removeItem(key) ;
	else
		localStorage[key] = value ;
	if ( $.cookie && ( $.cookie('login') != null ) )  { // Logged-in : send new value
		var e = document.getElementById(key) ;
		if ( e != null )
			e.parentNode.classList.add('updating') ;
		var json = {} ;
		json[key] = value ;
		$.post(url+'/json/profile_udate.php', {'json': JSON.stringify(json)}, function(data) {
			if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
				alert(data.msg) ;
			else if ( ( data.affected != 0 ) && ( data.affected != 1 ) ) {
				alert('Something went wrong : '+data.affected) ;
				$.cookie('login', null) ;
			} else if ( e != null )
				e.parentNode.classList.remove('updating') ;
		}, 'json') ;
	}
}
function save(myfield) {
	var field = myfield.id ;
	if ( myfield.type == 'checkbox' )
		var value = myfield.checked ;
	else
		var value = myfield.value ;
	if ( value != localStorage[field] ) {
		store(field, value) ;
		if ( typeof myfield.onsave == 'function' )
			myfield.onsave(myfield) ;
		return true ;
	}
	return false ;
}
function save_restore(field, onsave, onrestore) {
	var myfield = document.getElementById(field) ;
	if ( myfield != null ) {
		// Prepare save trigger
		switch ( myfield.type ) { // Depending on type
			case 'hidden': // Hidden
				break ; // No save trigger, must be triggered by code
			case 'select-one' : // Selects
			case 'text' : // Texts
				myfield.addEventListener('change', function (ev) {
					save(ev.target) ;
				}, false) ;
				break ;
			case 'checkbox' : // Checkboxes
				myfield.addEventListener('click', function (ev) {
					save(ev.target) ;
				}, false) ;
				break ;
			default :
				alert("Can't save/restore an input of type "+myfield.type) ;
		}
		// Restore
		if ( localStorage[field] == null ) // Var has never been set
			save(myfield) ;
		else { // Var has been set
			if ( ( myfield.value != localStorage[field] ) ) // And have change since then
				if ( myfield.type == 'checkbox' )
					myfield.checked = ( localStorage[field] == 'true' ) ;
				else
					myfield.value = localStorage[field] ;
		}
		if ( typeof onrestore == 'function' )
			onrestore(myfield) ;
		// In order it only triggers on user action save, not with call in this method
		if ( typeof onsave == 'function' )
			myfield.onsave = onsave ;
	}
}

function cardimages_apply(cardimages, cardimages_choice) {
	for ( var i = 0 ; i < cardimages_choice.options.length ; i++ )
		if ( localStorage['cardimages'] == cardimages_choice.options[i].value )
			cardimages_choice.selectedIndex = i ;
	var cardimages_link = document.getElementById('cardimages_link') ;
	if ( cardimages_choice.value == '' ) {
		cardimages.type = 'text' ;
		cardimages_link.style.display = '' ;
	} else {
		cardimages.type = 'hidden' ;
		cardimages_link.style.display = 'none' ;
	}
}
function save_restore_options() {
	save_restore('sounds', function(input) {
		if ( ( input.checked ) && ( typeof game != 'undefined' ) ) // Enabling sound
			game.sound.loadall() ; // Load them in case they're not
	}) ;
	save_restore('remind_triggers') ;
	save_restore('place_creatures') ;
	save_restore('place_noncreatures') ;
	save_restore('place_lands') ;
	save_restore('cardimages', function(field) {$.cookie('cardimages', field.value) ; }) ; // Write value in cookies in order PHP to get it
	var cardimages = document.getElementById('cardimages') ;
	var cardimages_choice = document.getElementById('cardimages_choice') ;
	cardimages_apply(cardimages, cardimages_choice) ;
	cardimages_choice.addEventListener('change', function(ev) {
		cardimages.value = ev.target.value ;
		save(cardimages) ;
		//localStorage['cardimages'] = ev.target.value ;
		$.cookie('cardimages', ev.target.value) ;
		cardimages_apply(cardimages, cardimages_choice) ;
	}, false) ;
	save_restore('check_preload_image') ;
	save_restore('library_doubleclick_action') ;
	save_restore('auto_draw') ;
	save_restore('draft_auto_ready') ;
	save_restore('invert_bf') ;
	save_restore('helpers') ;
	save_restore('debug') ;
	save_restore('transparency') ;
	save_restore('display_card_names') ;
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
// Log within other pages than main
function log2(arg) {
	var text = '' ;
	switch ( typeof arg ) {
		case 'boolean' : // Display numbers "as-is"
		case 'number' : // Display numbers "as-is"
		case 'string' : // Display strings "as-is"
			text += arg ;
			break ;
		case 'function' :
			//text += functionname(arg) ;
			break ;
		case 'object' : // Detailed object display
			if ( arg == null )
				text += 'null' ;
			else {
				text += 'Properties of object : '
				try {
					text += arg.toString() ;
				} catch ( e ) {
					text += e ;
				}
				for ( var i in arg ) {
					try {
						text += "\n - " + typeof arg[i] + '\t' + i ;
						switch ( typeof arg[i] ) {
							case 'function' :
								text += '()' ;
								if ( ( arg[i].name != '') && ( i != arg[i].name ) )
									text += '('+arg[i].name+')' ;
								break ;
							case 'object' :
								if ( arg[i] == null )
									text += ' = null' ;
								else
									text += ' = '+arg[i].toString() ;
								break ;
							case 'string' :
								text += ' = "' + arg[i] + '"' ;
								break ;
							default :
								text += ' = ' + arg[i] ;
						}
					} catch ( e ) {
						alert('Exception : '+e) ;
					}
				}
			}
			break ;
		default:
			//text += '[' + functionname(log.caller) + '] ' ;
			text += "Type unrecognized by loging engine :  "+typeof arg ;
	}
	alert(text);
}
