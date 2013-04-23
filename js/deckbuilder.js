function load(body, deckname) {
	options = new Options(true) ;
	selected = null ;
	selected_deck = null ;
	game = new Object() ;
	game.image_cache = new image_cache() ;
	if ( deckname != '' ) { // If a deck was passed in param, parse it and fill card list with its data
		var initial_deck_content = deck_get(deckname) ;
		if ( initial_deck_content == '' )
			initial_deck_content = '// NAME : '+deckname ;
		var lines = deck_parse(initial_deck_content) ;
		var to = 'maindeck' ;
		var noext = true ;
		for ( var i = 0 ; i < lines.length ; i++ ) {
			var line = lines[i] ;
			switch ( typeof line ) {
				case 'string' : 
					var j = i + 1 ;
					while ( typeof lines[j] == 'string' ) // Search for next 'card' line
						j++ ;
					to = 'maindeck' ;
					if ( lines[j] && lines[j][4] ) // When found, use it as "sideboard" information
						to = 'sideboard' ;
					comment(document.getElementById(to), line) ;
					break ;
				case 'object' :
					if ( lines[i][4] )
						to = 'sideboard' ;
					else
						to = 'maindeck' ;
					if ( lines[i][1] != '' ) // One extension is found in deckfile
						noext = false ; // Save with extensions
					var row = add_card(lines[i][1], lines[i][2], lines[i][3], lines[i][0], to) ;
					break ;
				default : 
					alert(typeof line) ;
			}
		}
		changed = false ;
		document.getElementById('noextensions').checked = noext ;
	} else
		deck_stats() ;
	// Bind events to HTML
	window.addEventListener('beforeunload', function(ev) {
		if ( changed ) {
			ev.returnValue = 'Modifications have been to your deck since last save, are you sure you want to quit without saving ?' ;
			return ev.returnValue ;
		}
	}, false) ;
	document.getElementById('cardname').addEventListener('keyup', function(ev) {
		var val = ev.target.value ;
		if ( ( val.length > 2 ) && ( ev.target.previous_value != ev.target.value )) {
			var params = {} ;
			var form = document.getElementById('search_cards') ;
			for ( var i = 0 ; i < form.elements.length ; i++ ) {
				var item = form.elements.item(i) ;
				params[item.name] = item.value
				if ( item == ev.target ) {
					params[item.name] = val ;
				}
			}
			search(params) ;
		}	
		ev.target.previous_value = ev.target.value ;
	}, false) ;
	document.getElementById('search_cards').addEventListener('submit', function(ev) { // Search form
		var params = {} ;
		for ( var i = 0 ; i < ev.target.elements.length ; i++ ) {
			var item = ev.target.elements.item(i) ;
			params[item.name] = item.value ;
		}
		for ( var i = 0 ; i < ev.target.page.length ; i++ )
			log2(ev.target.page[i]) ;
		search(params) ;
		ev.preventDefault() ;
	}, false) ;
	document.getElementById('advanced_search').addEventListener('click', function(ev) { // Search form
		var t = ev.currentTarget ;
		t.classList.toggle('checked') ;  
		var h = document.getElementById('hidden_form') ;
		if ( t.classList.contains('checked') )
			h.classList.remove('hidden') ;
		else
			h.classList.add('hidden') ;
	}, false) ;
	document.getElementById('add_md').addEventListener('click', function(ev) { // Buttons
		var node = null ;
		var sel = selected_deck ; // Curently selected
		if ( selected != null )
			node = select_deck(add_card(selected.ext(), selected.card.name, selected.attrs.nb, 1)) ;
		else 
			if ( selected_deck != null )
				node = select_deck(add_card(selected_deck.ext, selected_deck.card, null, 1)) ;
			else
				alert('Please select a card') ;
		if ( ( node != null ) && ( sel != null ) )
			node.parentNode.insertBefore(node, sel) ;
	}, false) ;
	document.getElementById('add_sb').addEventListener('click', function(ev) {
		var node = null ;
		var sel = selected_deck ; // Curently selected
		if ( selected != null )
			node = select_deck(add_card(selected.ext(), selected.card.name, selected.attrs.nb, 1, 'sideboard')) ;
		else 
			if ( selected_deck != null )
				node = select_deck(add_card(selected_deck.ext, selected_deck.card, null, 1, 'sideboard')) ;
			else
				alert('Please select a card') ;
		if ( ( node != null ) && ( sel != null ) )
			node.parentNode.insertBefore(node, sel) ;
	}, false) ;
	document.getElementById('del').addEventListener('click', function(ev) { remove_card(selected_deck, 1) ; }, false) ;
	document.getElementById('up').addEventListener('click', function(ev) {
		if ( selected_deck != null )
			selected_deck.parentNode.insertBefore(selected_deck, selected_deck.previousSibling) ;
		else
			alert('Please select a card') ;
	}, false) ;
	document.getElementById('down').addEventListener('click', function(ev) {
		if ( selected_deck != null ) {
			if ( selected_deck.nextSibling == null )
				selected_deck.parentNode.insertBefore(selected_deck, selected_deck.parentNode.firstChild) ;
			else
				selected_deck.parentNode.insertBefore(selected_deck, selected_deck.nextSibling.nextSibling) ;
		} else
			alert('Please select a card') ;
	}, false) ;
	document.getElementById('comment').addEventListener('click', function(ev) {
		var txt = prompt('Comment') ;
		if ( ( txt != null ) && ( txt != '' ) ) {
			if ( selected_deck != null )
				var to = selected_deck.parentNode ;
			else
				var to = document.getElementById('maindeck') ;
			var tr = comment(to, txt) ;
			if ( selected_deck != null )
				tr.parentNode.insertBefore(tr, selected_deck) ;
		}
	}, false) ;
	document.getElementById('save').addEventListener('click', function(ev) {
		if ( deckname == '' )
			alert('Please save this deck under a name before') ;
		else {
			if ( deck_set(deckname, deck_content()) ) {
				changed = false ;
				alert('Deck saved as "'+deckname+'"') ;
			} else
				alert('Deck not saved') ;
		}
	}, false) ;
	document.getElementById('saveas').addEventListener('click', function(ev) {
		var name = prompt('Name of this deck', deckname) ;
		if( ( name != null ) && ( name != '' ) ) {
			if ( deck_set(name, deck_content()) ) {
				deckname = name ;
				changed = false ;
				alert('Deck saved as "'+name+'"') ;
			} else
				alert('Deck not saved') ;
		} else
			alert('Deck not saved because of its name') ;
	}, false) ;
	document.getElementById('sort').addEventListener('click', function(ev) {
		sort_deck(document.getElementById('maindeck')) ;
		sort_deck(document.getElementById('sideboard')) ;
	}, false) ;
	// Ajax events
	jQuery('#log').ajaxError(function(ev, req, options, error){
		ev.target.value += req.responseText + '\n' ;
	}) ;
}
function sort_deck(deck) {
	var rows = [] ; // Work on a copy containing only cards
	for ( var i = 0 ; i < deck.rows.length ; i++ ) {
		var row = deck.rows[i] ;
		if ( iso(row.rattrs) )
			rows.push(row) ;
	}
	// Sort copy
	rows.sort(deck_sort) ;
	// Remove copied elements from source
	for ( var i = 0 ; i < rows.length ; i++ )
		rows[i].parentNode.removeChild(rows[i]) ;
	// Append sorted copy
	var pts = -1 ; // Previous typescore
	for ( var i = 0 ; i < rows.length ; i++ ) {
		if ( document.getElementById('sort_comments').checked ) {
			if ( deck == document.getElementById('maindeck') ) {
				var ts = typescore(rows[i].rattrs) ; 
				if ( ts != pts ) { // Typescore changing, add a comment
					switch ( ts ) {
						case 0 :
							comment(deck, 'Lands') ;
							break ;
						case 1 :
							comment(deck, 'Creatures') ;
							break ;
						case 2 :
							comment(deck, 'Permanents') ;
							break ;
						case 3 :
							comment(deck, 'Spells') ;
							break ;
						default :
							comment(deck, 'Unknown typescore : '+ts) ;
					}
					pts = ts ;
				}
			} else
				if ( pts == -1 ) {
					comment(deck, 'Sideboard') ;
					pts++ ;
				}
		}
		deck.appendChild(rows[i]) ;
	}
}
function deck_sort(a, b) {
	var score = typescore(a.rattrs) - typescore(b.rattrs) ; 
	if ( score == 0 ) {
		score = a.rattrs.converted_cost - b.rattrs.converted_cost ;
		if ( score == 0 ) {
			for ( var i = 0 ; i < a.parentNode.rows.length ; i++ ) {
				if ( a.parentNode.rows[i] == a )
					break ;
			}
			for ( var j = 0 ; j < b.parentNode.rows.length ; j++ ) {
				if ( a.parentNode.rows[j] == b )
					break ;
			}
			return i - j ;
			if ( a.card > b.card )
				score = 1 ;
			else if ( a.card < b.card )
				score = -1 ;
			else
				score = 0 ;
		}
	}
	return score ;
}
function typescore(attrs) {
	var result = 3 ; // Defaults to "other"
	if ( attrs.types.indexOf('land') > -1 )
		result = 0 ;
	else if ( attrs.types.indexOf('creature') > -1 )
		result = 1 ;
	else if ( ( attrs.types.indexOf('artifact') > -1 ) || ( attrs.types.indexOf('enchantment') > -1 ) || ( attrs.types.indexOf('planeswalker') > -1 ) ) 
		result = 2 ;
	return result ;
}
function log(msg) {
	var el = document.getElementById('log')
	el.value += msg+'\n' ;
	el.scrollTop = el.scrollHeight ;
}
function deck_content() {
	var content = '' ;
	var deck = document.getElementById('maindeck') ;
	var noext = document.getElementById('noextensions').checked ;
	for ( var i = 0 ; i < deck.rows.length ; i++ ) {
		var row = deck.rows[i] ;
		if ( row.className == 'comment' )
			content += '// '+row.cells[0].textContent+'\n' ;
		else {
			content += '    '+row.cells[0].textContent+' ' ; // Number of cards
			if ( ! noext )
				content += '['+row.ext()+']' ; // Extension
			content += row.card ; // Name
			if ( isn(row.attrs.nb) )
				content += ' ('+row.attrs.nb+')' ;
			content += '\n' ;
		}
	}
	var deck = document.getElementById('sideboard') ;
	for ( var i = 0 ; i < deck.rows.length ; i++ ) {
		var row = deck.rows[i] ;
		if ( row.className == 'comment' )
			content += '//'+row.cells[0].textContent+'\n' ;
		else {
			content += 'SB: '+row.cells[0].textContent+' ' ; // Number of cards
			if ( ! noext )
				content += '['+row.ext()+']' ; // Extension
			content += row.cells[2].textContent ; // Name
			if ( isn(row.attrs.nb) )
				content += ' ('+row.attrs.nb+')' ;
			content += '\n' ;
		}
	}
	return content ;
}
function zoom(ext, card, attrs) {
	var url = card_image_url(ext, card, attrs) ;
	var zoom = document.getElementById('zoom') ;
	zoom.card = card ; // Only display card if last asked
	return game.image_cache.load(card_images(url), function(img, card) {
		if ( zoom.card == card )
			zoom.src = img.src ;
		// Otherwise zoom has "timeout"
	}, function(card, url) {
		log(card+' : '+url) ;
	}, card) ;
}
function select(element) {
	if ( selected != null ) // Try to unselect previous selected if any
		selected.className = '' ;
	selected = element ;
	selected.className = 'selected' ;
}
function remove_card(row, nb) {
	if ( row != null ) {
		if ( row.parentNode != null ) {
			changed = true ;
			var n = parseInt(row.cells[0].textContent) ;
			if ( typeof nb != 'number' )
				nb = n ;
			n -= nb ;
			if ( n < 1 ) {
				if ( row == selected_deck )
					selected_deck = null ;
				row.parentNode.removeChild(row) ;
				row = null ;
			} else
				row.cells[0].textContent = n ;
			deck_stats() ;
		}
	} else
		alert('Please select a card') ;
}
function count_cards(id) {
	var decklist = document.getElementById(id) ;
	var nbcards = 0 ;
	for ( var i = 0 ; i < decklist.rows.length ; i++ ) {
		var row = decklist.rows[i] ;
		if ( row.className != 'comment' ) {
			var nbadd = parseInt(row.cells[0].textContent) ;
			if ( ! isNaN(nbadd) )
				nbcards += nbadd ;
		}
	}
	return nbcards
}
function deck_stats() {
	if ( ! deck_loaded('maindeck') )
		return false ;
	// Right column
	var decklist = document.getElementById('maindeck') ;
	var cards = [] ;
	for ( var i = 0 ; i < decklist.rows.length ; i++ ) {
		var row = decklist.rows[i] ;
		if ( row.className != 'comment' ) {
			if ( ! iso(row.rattrs) ) {
				alert('deck not loaded') ;
				return false ;
			}
			var nbadd = parseInt(row.cells[0].textContent) ;
			for ( var j = 0 ; j < nbadd ; j++ )
				cards.push({'attrs' : row.rattrs}) ;
		}
	}
	var tl = document.getElementById('stats_typelist') ;
	node_empty(tl) ;
	tl.appendChild(create_text(cards.length+' cards')) ;
	deck_stats_cc(cards) ;
}
function deck_loaded(table) {
	var decklist = document.getElementById(table) ;
	for ( var i = 0 ; i < decklist.rows.length ; i++ ) {
		var row = decklist.rows[i] ;
		if ( ( row.className != 'comment' ) && ! iso(row.rattrs) )
			return false ;
	}
	return true ;
}
function deck_types(table) {
	var result = {'cards':0} ;
	var decklist = document.getElementById(table) ;
	for ( var i = 0 ; i < decklist.rows.length ; i++ ) {
		var row = decklist.rows[i] ;
		if ( row.className != 'comment' ) {
			var nbadd = parseInt(row.cells[0].textContent) ;
			if ( row.rattrs ) {
				result.cards += nbadd ; // Total
				for ( var j in row.rattrs.types ) { // Subdivision by type
					var type = row.rattrs.types[j] ;
					if ( result[type] )
						result[type] += nbadd ;
					else
						result[type] = nbadd ;
				}
			}
		}
	}
	return result ;
}
function select_deck(node) {
	node = node_parent_search(node, 'TR') ;
	if ( selected_deck != null )
		selected_deck.className = '' ;
	selected_deck = node ;
	selected_deck.className = 'selected' ;
	return node ;
}
function extension_select(card, orig_ext, orig_attrs) {
	var pics = [] ; // Build a list of pics
	for ( var j = 0 ; j < card.ext.length ; j++ ) { // For each extension
		for ( var l = 0 ; l < card.ext[j].nbpics ; l++ ) { // For each pic in that extension
			var obj = {}
			if ( card.ext[j].nbpics > 1 )
				obj.nb = l+1 ;
			pics.push([j, obj]) ;
		}
	}
	switch ( pics.length ) { // Finding ext to display
		case 0 :
			log('Got 0 pics on ext request') ;
			break ;
		case 1 : // 1 ext, just display it
			var ext = card.ext[0].se ;
			break ;
		default : // More than 1 ext, display a select to choose
			var ext = document.createElement('select') ;
			ext.addEventListener('change', function(ev) {
				var node = node_parent_search(ev.target, 'TR') ;
				node.attrs = ev.target.item(ev.target.selectedIndex).attrs ;
			}, false) ;
			for ( var k = 0 ; k < pics.length ; k++ ) { 
				var opt = document.createElement('option');
				opt.value = pics[k][0] ;
				opt.text = card.ext[opt.value].se ;
				opt.attrs = pics[k][1] ; ;
				opt.title = card.ext[opt.value].name ;
				if ( opt.attrs.nb )
					opt.title += ' (Pic #'+opt.attrs.nb+')' ;
				if ( iss(orig_ext) && ( orig_ext == card.ext[opt.value].se ) ) {
					if ( !isn(orig_attrs.nb) )
						opt.selected = true ;
					else {
						if ( orig_attrs.nb == opt.attrs.nb )
							opt.selected = true ;
					}
				}
				opt.addEventListener('mouseover', function(ev) {
					var val = ev.target.value ;
					var node = node_parent_search(ev.target, 'TR') ;
					zoom(card.ext[val].se, card.name, ev.target.attrs) ;
					ev.stopPropagation() ;
				}, false) ;
				ext.add(opt, null);
			}
	}
	return ext ;
}
function search(params) {
	// Send search request
	$.getJSON('json/cards.php', params, found) ;
	var cardlist = document.getElementById('search_result') ;
	selected = null ;
	node_empty(cardlist) ;
	create_tr(cardlist, 'Searching ...') ;
}
function page_submit(form, num, txt) {
	num += '' ; // Transtyping
	var but = create_button(num, function(ev) { ev.target.form.page.value = num ; }) ;
	but.title = 'Page '+num ;
	if ( iss(txt) && ( txt != '' ) )
		but.title += ' ('+txt+')' ;
	form.appendChild(but) ;
}
function found(data) {
	var cards = data.cards ;
	// Recieve search results
	var cardlist = document.getElementById('search_result') ;
	node_empty(cardlist) ;
	// Pagination
	var pagination = document.getElementById('pagination') ;
	node_empty(pagination) ;
	if ( cards.length < data.num_rows ) {
		var nbpages = Math.ceil(data.num_rows / data.limit) ;
		var from = data.page - 2 ;
		var to = data.page + 2 ;
		// Keep 5 pages displayed
		if ( from < 1 ) {
			to -= from - 1 ;
			from = 1 ;
		}
		if ( to > nbpages ) {
			from -= ( to - nbpages ) - 1 ; // Keep 5 pages displayed
			if ( from < 1 )
				from = 1 ;
			to = nbpages ;
		}
		// Button to first page if needed
		if ( from > 1 ) {
			page_submit(pagination, 1, 'first') ;
			if ( from > 2 )
				pagination.appendChild(create_text('...')) ;
		}
		// 5 Buttons around current page
		for ( var i = from ; i <= to ; i++ ) {
			if ( i == data.page ) {
				var but = create_button(i) ;
				but.title = 'Current page : '+i ;
				but.disabled = true ;
				pagination.appendChild(but) ;
			} else {
				page_submit(pagination, i)
			}
		}
		// Button to last page if needed
		if ( to < nbpages ) {
			if ( to < nbpages - 1 )
				pagination.appendChild(create_text('...')) ;
			page_submit(pagination, nbpages, 'last') ;
		}
	}
	document.getElementById('search_cards').page.value = 1 ; // Reinit paginator in case we launch another search
	// Fill with param
	var k = 0 ;
	var row = null
	for ( var i in cards ) {
		var card = cards[i] ;
		var ext = extension_select(card) ; // Returns a string for one ext or a select for multiple exts
		// Create line
		row = create_tr(cardlist, ext, card.name) ;
		if ( iss(ext) )
			row.cells[0].classList.remove('nopadding') ;
		else
			row.cells[0].classList.add('nopadding') ;
		k++ ;
		// Link card
		row.card = cards[i] ;
		row.extlist = ext ;
		row.ext = function() {
			var result = null ;
			if ( typeof this.extlist == 'string' )
				result = this.extlist ;
			else
				result = this.card.ext[this.extlist.value].se ;
			return result ;
		}
		row.attrs = {} ;
		if ( typeof ext == 'string' ) {
			if ( parseInt(card.ext[0].nbpics) > 1 )
				row.attrs.nb = 1 ;
		} else {
			if ( parseInt(card.ext[row.extlist.value].nbpics) > 1 )
				row.attrs.nb = 1 ;
		}
		// Events
		row.addEventListener('mouseover', function(ev) {
			var node = node_parent_search(ev.target, 'TR') ;
			this.img = zoom(node.ext(), node.card.name.replace(/"/g, ''), node.attrs) ; // "Ach! Hans, Run!"
		}, false) ;
		row.addEventListener('mousedown', function(ev) {
			if ( ev.button == 1 )
				window.open('http://magiccards.info/query?q=!'+ev.currentTarget.card.name+'&v=card&s=cname') ;
			else
				select(node_parent_search(ev.target, 'TR')) ;
		}, false) ;
		row.addEventListener('dblclick', function(ev) {
			var node = node_parent_search(ev.target, 'TR') ;
			var sel = selected_deck ; // Curently selected
			var selected = select_deck(add_card(node.ext(), node.card.name, node.attrs.nb)) ;
			if ( ( selected != null ) && ( sel != null ) )
				selected.parentNode.insertBefore(selected, sel) ;
		}, false) ;
		row.addEventListener('contextmenu', function(ev) {
			var row = node_parent_search(ev.target, 'TR') ;
			var menu = new menu_init(row) ;
			menu.addline('Add (dblclick)', function(node) {
				var sel = selected_deck ; // Curently selected
				var selected = select_deck(add_card(node.ext(), node.card.name, node.attrs.nb)) ;
				if ( ( selected != null ) && ( sel != null ) )
					selected.parentNode.insertBefore(selected, sel) ;
			}, row) ;
			menu.addline('Informations (middle click)', function(target)  {
				window.open('http://magiccards.info/query?q=!'+target.card.name+'&v=card&s=cname') ;
			}, row) ;
			return menu.start(ev) ;
		}, false)
		row.addEventListener('dragstart', function(ev) {
			var row = node_parent_search(ev.target, 'TR') ;
			ev.dataTransfer.setData('newcard', row.card.name) ;
			ev.dataTransfer.setData('ext', row.ext()) ;
			ev.dataTransfer.effectAllowed = 'move' ;
			if ( ev.target.img ) {
				var canvas = create_canvas(cardwidth, cardheight) ;
				var ctx = canvas.getContext("2d");
				ctx.drawImage(ev.target.img, 0, 0, cardwidth, cardheight) ;
				ev.dataTransfer.setDragImage(canvas, cardwidth/2, cardheight/2) ;
			}
		}, false) ;
		row.draggable = true ;
	}
	var to = data.page * data.limit ;
	var from = to - data.limit + 1 ;
	if ( to > data.num_rows )
		to = data.num_rows ;
	create_tr(cardlist, txt = from+' - '+to+' / '+data.num_rows+' ('+data.mode+')') ;
}
function img_button(imgname, title, onclick) {
	var img = create_img(theme_image('deckbuilder/'+imgname+'.png')[0], 'imgname', title) ;
	img.addEventListener('click', onclick, false) ;
	return img ;
}
function add_card(ext, name, num, nb, to) {
	changed = true ;
	if ( ! nb )
		nb = 1 ;
	if ( ! to )
		to = 'maindeck' ;
	var decklist = document.getElementById(to) ;
	// Search for a card with same name & ext
	for ( var i = 0 ; i < decklist.rows.length ; i++ ) { 
		var row = decklist.rows[i] ;
		if ( row.cells.length > 2 ) { // Only rows with columns (no comment row)
			if ( ( row.card == name ) && ( row.ext() == ext ) && ( row.attrs.nb == num ) ) {
				var n = parseInt(row.cells[0].textContent) ;
				n += nb ;
				row.cells[0].textContent = n ;
				deck_stats() ;
				return row ;
			}
		}
	}
	// Card nor found, creating one
	var buttons = create_span() ;
	var row = create_tr(decklist, nb, ext, string_limit(name, 25), buttons) ;
	row.extlist = ext ;
	row.ext = function() {
		var result = null ;
		if ( typeof this.extlist == 'string' )
			result = this.extlist ;
		else {
			result = this.exts[this.extlist.value].se ;
		}
		return result ;
	}
	row.card = name ;
	row.attrs = new Object() ;
	if ( ( typeof num == 'number' ) && ( num > 0 ) )
		row.attrs.nb = num ;
	// Buttons
	buttons.appendChild(img_button('edit_add', 'Add one', function(ev) { remove_card(row, -1) ; })) ;
	buttons.appendChild(img_button('edit_remove', 'Remove one', function(ev) {remove_card(row, 1) ; })) ;
	buttons.appendChild(img_button('button_cancel', 'Remove all', function(ev) { remove_card(row) ; })) ;
	buttons.parentNode.classList.add('buttonlist') ;
	// Ask server info about it
	jQuery.getJSON('json/card.php', {'name': name}, function(data) {
		// Search given extension in list returned by server
		var i = 0 ; // By default, take first extension in list if given extension doesn't exists for this card
		if ( ! data.name )
			log(name+' not found') ;
		else {
			if ( ! data.ext )
				log(data.name+' has no ext') ;
			else {
				// Recieved a card, manage ext
				var ext = extension_select(data, row.ext(), row.attrs) ;
				row.extlist = ext ;
				row.exts = data.ext ;
				node_empty(row.cells[1]) ; 
				if ( iss(ext) ) {
					row.cells[1].appendChild(document.createTextNode(ext)) ;
					row.cells[1].classList.remove('nopadding') ;
				} else {
					row.cells[1].appendChild(ext) ;
					row.cells[1].classList.add('nopadding') ;
				}
				// Manage name (in case it's not exactly the same in deck file and in DB)
				node_empty(row.cells[2]) ;
				row.card = data.name ;
				row.cells[2].appendChild(document.createTextNode(row.card)) ;
			}
		}
		if ( data.attrs ) {
			row.rattrs = JSON.parse(data.attrs) ; // Store attrs recieved from server
			deck_stats() ;
		}
	}) ;
	row.search = function() {
		search({'name': this.card}) ;
		document.getElementById('cardname').value = this.card ;
	}
	// Events
	row.addEventListener('mouseover', function(ev) {
		var node = node_parent_search(ev.target, 'TR') ;
		if ( node.ext() != '' )
			node.img = zoom(node.ext(), node.card.replace(/"/g, ''), node.attrs) ; // "Ach! Hans, Run!"
	}, false) ;
	row.addEventListener('mousedown', function(ev) {
		if ( ev.button == 1 )
			window.open('http://magiccards.info/query?q=!'+ev.currentTarget.card+'&v=card&s=cname') ;
		else
			select_deck(ev.target) ;
	}, false ) ;
	row.addEventListener('dblclick', function(ev) {
		node_parent_search(ev.target, 'TR').search() ;
	}, false ) ;
	row.addEventListener('contextmenu', function(ev) {
		var menu = new menu_init(row) ;
		menu.addline('Search (dblclick)', row.search) ;
		menu.addline('Remove one', remove_card, row, 1) ;
		menu.addline('Remove all', remove_card, row) ;
		if ( row.parentNode == document.getElementById('maindeck') )
			menu.addline('To sideboard', function(target)  {
				document.getElementById('sideboard').appendChild(target) ;
				deck_stats() ;
			}, row) ;
		else if ( row.parentNode == document.getElementById('sideboard') )
			menu.addline('To maindeck', function(target)  {
				document.getElementById('maindeck').appendChild(target) ;
				deck_stats() ;
			}, row) ;
		else
			alert('Don\'t know where card is') ;
		menu.addline('Informations (middle click)', function(target)  {
			window.open('http://magiccards.info/query?q=!'+target.card+'&v=card&s=cname') ;
		}, row) ;
		return menu.start(ev) ;
	}, false)
	// DND
	row.addEventListener('dragstart', function(ev) {
		ev.dataTransfer.setData('card', ev.target.card) ;
		ev.dataTransfer.effectAllowed = 'move' ;
		if ( ev.target.img ) {
			var canvas = create_canvas(cardwidth, cardheight) ;
			var ctx = canvas.getContext("2d");
			ctx.drawImage(ev.target.img, 0, 0, cardwidth, cardheight) ;
			ev.dataTransfer.setDragImage(canvas, cardwidth/2, cardheight/2) ;
		}
	}, false) ;
	row_dnd(row)
	return row ;
}
function comment(table, txt) {
	if ( txt[0] == ' ' ) // Remove starting space due to writing adding it after //
		txt = txt.substr(1) ;
	// No adding twice the same comment : if a comment already exist with this content, remove it
	for ( var i = 0 ; i < table.rows.length ; i++ )
		if ( table.rows[i].firstChild.firstChild.data == txt )
			table.rows[i].parentNode.removeChild(table.rows[i]) ;
	var row = create_tr(table) ; //, txt, buttons) ;
	create_td(row, string_limit(txt, 40)).colSpan = 3 ;
	row.className = 'comment' ;
	// Methods
	row.editcomment = function(ev) {
		var row = node_parent_search(ev.target, 'TR') ;
		var ret = prompt('Comment text, empty to remove', row.firstChild.firstChild.data) ;
		if ( ret != null ) {
			if ( ret == '' )
				row.parentNode.removeChild(row) ;
			else
				row.firstChild.firstChild.data = ret ;
		}
	}
	// Buttons
	var buttons = create_span() ;
	buttons.appendChild(img_button('edit', 'Edit comment', row.editcomment)) ;
	buttons.appendChild(img_button('button_cancel', 'Remove comment', function(ev) { var row = ev.target.parentNode.parentNode.parentNode ; row.parentNode.removeChild(row) ; })) ;
	create_td(row, buttons) ;
	buttons.parentNode.classList.add('buttonlist') ;
	// Events
	row.addEventListener('dblclick', row.editcomment, false) ;
	row.addEventListener('contextmenu', function(ev) {
		var menu = new menu_init(row) ;
		menu.addline('Edit (dblclick)', row.editcomment, ev) ;
		menu.addline('Remove', function(ev) {
			var row = node_parent_search(ev.target, 'TR') ;
			row.parentNode.removeChild(row) ;
		}, ev) ;
		return menu.start(ev) ;
	}, false)

	// DND
	row.addEventListener('dragstart', function(ev) {
		var row = node_parent_search(ev.target, 'TR') ;
		ev.dataTransfer.setData('comment', row.firstChild.firstChild.data) ;
		ev.dataTransfer.effectAllowed = 'move' ;
	}, false) ;
	row_dnd(row)
	return row ;
}
function row_dnd(row) {
	row.addEventListener('dragenter', function(ev) { // On entering a row, immediately move 
		var card = ev.dataTransfer.getData('card') ;
		var comment = ev.dataTransfer.getData('comment') ;
		var p = ev.currentTarget.parentNode ;
		var b = false ;
		for ( var i = 0 ; i < p.rows.length ; i++ ) { // Search dragged
			var row = p.rows[i] ;
			if ( row == ev.currentTarget ) // dragovered is under dragged
				b = true ;
			if ( row != ev.currentTarget ) { // Not when entering on itself
				if ( 
					( ( card != '' ) && ( row.card == card ) ) // A card found
					|| ( ( comment != '' ) && ( row.firstChild.firstChild.data == comment ) ) // A comment found
				) { // move it
					if ( b )
						p.insertBefore(row, ev.currentTarget) ;
					else
						p.insertBefore(row, ev.currentTarget.nextSibling) ;
					ev.preventDefault() ;
					deck_stats() ;
					return false ; // Searched item found, stop search
				}
			}
		}
		var newcard = ev.dataTransfer.getData('newcard') ;
		if ( newcard != '' ) {
				ev.preventDefault() ;
				return false ; // Searched item found, stop search
		}
	}, false) ;
	row.addEventListener('dragover', function(ev) { // Allow "drop"
		if ( ev.dataTransfer == null )
			return true ;
		var card = ev.dataTransfer.getData('card') ;
		var comment = ev.dataTransfer.getData('comment') ;
		var p = ev.currentTarget.parentNode ;
		for ( var i = 0 ; i < p.rows.length ; i++ ) { // Search dragged
			var row = p.rows[i] ;
			if ( ( row.card == card ) || ( row.firstChild.firstChild.data == comment ) ) { // dragged found, move it
				ev.preventDefault() ;
				return false ; // Searched item found, stop search
			}
		}
		var newcard = ev.dataTransfer.getData('newcard') ;
		if ( newcard != '' ) {
				ev.preventDefault() ;
				return false ; // Searched item found, stop search
		}
	}, false) ;
	row.addEventListener('drop', function(ev) { // Required to "validate" drop and skip "not dropped" effect
		var newcard = ev.dataTransfer.getData('newcard') ;
		var ext = ev.dataTransfer.getData('ext') ;
		if ( newcard != '' ) {
			var row = add_card(ext, newcard) ;
			row.parentNode.insertBefore(row, ev.currentTarget) ;
		}
		ev.preventDefault() ;
		return false ;
	}, false) ;
	row.draggable = true ;
}
