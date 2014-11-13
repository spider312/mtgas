// side.js
// Siding / Game chaining
function side_start(player, winner) {
	side_start_recieve(player, winner) ;
	player.side_start() ;
}
function side_start_recieve(player, winner) {
	// Fake window
	var side_window = create_div() ;
	side_window.id = 'side_window' ;
	side_window.player = player ;
	side_window.winner = winner ;
	// Titles
	var deck_title = create_div('Deck') ;
	deck_title.id = 'deck_title' ;
	side_window.appendChild(deck_title) ;
	var side_title = create_div('Side') ;
	side_title.id = 'side_title' ;
	side_window.appendChild(side_title) ;
	// Lists
	var deck_ul = create_ul('deck')
	side_window.appendChild(deck_ul) ;
	var side_ul = create_ul('side') ;
	side_window.appendChild(side_ul) ;
	// Switch button
	var img_down = create_img(theme_image('deckbuilder/1downarrow.png')[0], 'v') ;
	var but_switch = create_button(img_down, function(ev) {
		//var doc = ev.target.parentNode ;
		if ( side_window.selected == null ) {
			game.infobulle.set('Please select a card before clicking') ;
			return null ;
		}
		side_swap(side_window) ;
	}, 'Select a card first') ;
	but_switch.id = 'button_switch' ;
	but_switch.disabled = true ;
	but_switch.img_down = img_down ;
	but_switch.img_left = create_img(theme_image('deckbuilder/1leftarrow.png')[0], '=&gt;') ;
	but_switch.img_right = create_img(theme_image('deckbuilder/1rightarrow.png')[0], '=&lt;') ;
	side_window.appendChild(but_switch) ;
	// Bottom buttons
		// Lands
	var img_add = create_img(theme_image('deckbuilder/edit_add.png')[0], 'Add basic lands') ;
	var but_land = create_button(img_add, function(ev) {
		var ul = document.getElementById('land') ;
		if ( ul != null ) {
			ul.parentNode.removeChild(ul) ;
			but_land.replaceChild(but_land.img_add, but_land.firstElementChild) ;
		} else {
			ul = create_ul() ;
			ul.id = 'land' ;
			ul.appendChild(side_land_li('Plains')) ;
			ul.appendChild(side_land_li('Island')) ;
			ul.appendChild(side_land_li('Swamp')) ;
			ul.appendChild(side_land_li('Mountain')) ;
			ul.appendChild(side_land_li('Forest')) ;
			side_window.appendChild(ul) ;
			but_land.replaceChild(but_land.img_del, but_land.firstElementChild) ;
		}
	}, 'Add basic lands') ;
	but_land.img_add = img_add ;
	but_land.img_del = create_img(theme_image('deckbuilder/edit_remove.png')[0], 'Add basic lands') ;
	but_land.id = 'button_land' ;
		// Reload
	var but_reload = create_button(create_img(theme_image('deckbuilder/reload.png')[0], 'Reload'), function(ev) {
		side_lists_fill(player, deck_ul, side_ul, 'init_zone') ;
	}, 'Reinitialize your deck as described in decklist') ;
	but_reload.id = 'button_reload' ;
		// OK
	var but_ok = create_button(create_img(theme_image('deckbuilder/button_ok.png')[0], 'OK'), function(ev) {
		side_in_out(deck_ul, player.library) ; // Side in
		side_in_out(side_ul, player.sideboard) ; // Side out
		side_close(side_window) ;
	}, 'Validate side') ;
	but_ok.id = 'button_ok' ;
		// Cancel
	var but_can = create_button(create_img(theme_image('deckbuilder/button_cancel.png')[0], 'Cancel'), function(ev) {
		side_undo(player) ;
		side_close(side_window) ;
	}, 'Cancel side')
	but_can.id = 'button_cancel' ;
		// Container
	var div_but = create_div() ;
	div_but.id = 'side_buttons' ;
	if ( tournament > 0 )
		div_but.appendChild(but_land) ;
	div_but.appendChild(but_reload) ;
	div_but.appendChild(but_ok) ;
	div_but.appendChild(but_can) ;
	side_window.appendChild(div_but) ;
	// End of init
	side_window.selected = null ;
	document.body.appendChild(side_window) ;
	side_resize() ;
	side_lists_fill(player, deck_ul, side_ul, 'orig_zone') ;
	but_can.focus() ;
}
// Refresh lists
function side_resize() {
	var side_window = document.getElementById('side_window') ;
	if ( side_window == null )
		return false ;
	var deck_ul = document.getElementById('deck') ;
	var deck_title = document.getElementById('deck_title') ;
	deck_ul.style.height = (side_window.clientHeight-deck_title.clientHeight-10)+'px' ;
	var side_ul = document.getElementById('side') ;
	var side_title = document.getElementById('side_title') ;
	var buttons = document.getElementById('side_buttons') ;
	side_ul.style.height = (side_window.clientHeight-side_title.clientHeight-buttons.clientHeight-15)+'px' ;
}
function side_lists_fill(player, deck_ul, side_ul, field) {
	var deckcards = new Array() ;
	var sidecards = new Array() ;
	for ( var i in game.cards ) { // Create arrays of cards in order to group cards with the same name
		var card = game.cards[i] ;
		switch ( card[field] ) {
			case player.library :
				card.watching = true ; // Force visible locally
				if ( ! deckcards[card.name] )
					deckcards[card.name] = new Array() ;
				deckcards[card.name].push(card) ;
				break ;
			case player.sideboard :
				card.watching = true ;
				if ( ! sidecards[card.name] )
					sidecards[card.name] = new Array() ;
				sidecards[card.name].push(card) ;
				break ;
		}
	}
	// When arrays are filled, create LIs
	node_empty(deck_ul) ;
	node_empty(side_ul) ;
	for ( var i in deckcards )
		deck_ul.appendChild(side_create_li(deckcards[i])) ;
	for ( var i in sidecards )
		side_ul.appendChild(side_create_li(sidecards[i])) ;	
	// Then titles
	side_card_numbers('deck') ;
	side_card_numbers('side') ;
}
function side_create_li(cards) {
	var card = cards[0] ;
	var myli = create_li(cards.length) ;
	myli.card = card ;
	myli.cards = cards ; // Array of cards, to be poped
	game.image_cache.load(myli.card.imgurl(),function(img, myli) {
		myli.style.backgroundImage = 'url(\"'+img.src+'\")' ;
	}, function(myli) {
		myli.appendChild(document.createTextNode(myli.card.name)) ;
	}, myli) ;
	myli.addEventListener('click', side_click_card, false) ;
	myli.addEventListener('mouseover', function(ev) {
		ev.target.card.zoom(ev.target.defaultView) ;
	}, false) ;
	return myli
}
// Siding actions
function side_click_card(ev) {
	var doc = ev.target.parentNode.parentNode ;
	var prev = doc.selected ;
	if ( prev != null )
		prev.classList.remove('selected') ;
	doc.selected = this ;
	doc.selected.classList.add('selected') ;
	var but_switch = document.getElementById('button_switch') ;
	but_switch.style.top = ( doc.selected.offsetTop - doc.selected.parentNode.scrollTop + 20 )+'px' ;
	var zone = doc.selected.parentNode.id ;
	switch( zone ) {
		case 'deck' :
			but_switch.replaceChild(but_switch.img_right, but_switch.firstElementChild) ;
			but_switch.title = 'Side-out '+doc.selected.card.name ;
			break ;
		case 'side': 
			but_switch.replaceChild(but_switch.img_left, but_switch.firstElementChild) ;
			but_switch.title = 'Side-in '+doc.selected.card.name ;
			break ;
		default:
			debug('Unknown zone : '+zone) ;
	}
	if ( but_switch.disabled )
		but_switch.disabled = false ;
}
function side_swap(doc) {
	switch( doc.selected.parentNode.id ) {
		case 'deck' :
			var zone_to = document.getElementById('side') ;
			break ;
		case 'side': 
			var zone_to = document.getElementById('deck') ;
			break ;
		default:
			debug('Unknown zone : '+doc.selected.parentNode.id) ;
	}
	// Remove card from zone
	var card = doc.selected.cards.pop() ; // Remove one from line
	doc.selected.firstChild.nodeValue = doc.selected.cards.length ; // Update line
	if ( doc.selected.cards.length < 1 ) { // If 0 occurences of card in zone
		doc.selected.parentNode.removeChild(doc.selected) ; // Remove visual from zone
		// No card is selected anymore
		doc.selected = null ;
		var but_switch = document.getElementById('button_switch') ;
		but_switch.disabled = true ;
		but_switch.title = 'Select a card first' ;
		but_switch.replaceChild(but_switch.img_down, but_switch.firstElementChild) ;
	}
	// Add card to zone
	var exist = side_name_in_pack(card.name, zone_to) ; // Is it already in ?
	if ( exist > -1 ) { // It is
		zone_to.childNodes[exist].cards.push(card) ;
		zone_to.childNodes[exist].firstChild.nodeValue = zone_to.childNodes[exist].cards.length ;
	} else // It isn't
		zone_to.appendChild(side_create_li(Array(card), zone_to.ownerDocument)) ; // Add it
	// Update zones card numbers
	side_card_numbers('deck') ;
	side_card_numbers('side') ;
}
function side_card_numbers(zone) {
	var nb = 0 ;
	var deck = document.getElementById(zone) ;
	for ( var i = 0 ; i < deck.childNodes.length ; i++ )
		nb += deck.childNodes[i].cards.length ;
	var deck_title = document.getElementById(zone+'_title') ;
	node_empty(deck_title) ;
	var title = (zone=='deck')?'Deck':'Side' ;
	deck_title.appendChild(create_text(title+' : '+nb)) ;
}
function side_name_in_pack(name, deck) { // Returns index of 'name' in 'deck', -1 if not found
	var exist = -1 ;
	for ( var i = 0 ; i < deck.childNodes.length ; i++ ) {
		var card = deck.childNodes[i].cards[0] ;
		if ( card.name == name ) {
			exist = i ;
			break ;
		}
	}
	return exist ;
}
// Lands
function side_land_li(name) {
	var myli = create_li(null) ;
	myli.name = name ;
	var urls = card_images(card_image_url('UNH', name, {})) ;
	game.image_cache.load(urls, function(img, myli) {
		myli.style.backgroundImage = 'url(\"'+img.src+'\")' ;
	}, function(myli) {
		myli.appendChild(document.createTextNode(name+' err')) ;
	}, myli) ;
	myli.addEventListener('click', side_click_land, false) ;
	return myli ;
}
function side_click_land(ev) {
	action_send('land', {'name': ev.target.name}, function(data) {
		debug(data) ;
	}) ;
}
// End of side actions (siding over network)
function side_undo(player) { // Side should also clean up grave, exile, bf, hands ... if user cancels side, we have to do this anyway
	var zones = {} ; // Set of card selections, one per zone where a card can be
	for ( var i = 0 ; i < game.cards.length ; i++ ) {
		var card = game.cards[i] ;
		if ( ( card.owner == player ) && ( card.zone != player.library ) && ( card.zone != player.sideboard ) ) // My card, elsewhere than my library and my sideboard
			if ( ! zones[card.zone.toString()] ) // Create/add-to selection
				zones[card.zone.toString()] = new Selection(card) ;
			else
				zones[card.zone.toString()].add(card) ;
	}
	for ( var i in zones )
		zones[i].changezone(player.library) ;
}
function side_in_out(ul, zone) { // Send all cards in ul to zone
	var zones = {} ; // Set of card selections, one per zone where a card can be
	for ( var i = 0 ; i < ul.childNodes.length ; i++ ) {
		var cards = ul.childNodes[i].cards ;
		for ( var j in cards ) {
			var card = cards[j] ;
			if ( card.zone != zone )  {
				if ( ! zones[card.zone.toString()] ) // Create/add-to selection
					zones[card.zone.toString()] = new Selection(card) ;
				else
					zones[card.zone.toString()].add(card) ;
			}
		}
	}
	for ( var i in zones )
		zones[i].changezone(zone) ;
}
function side_close(win) { // Common run between buttons ok and cancel in side window
	win.player.side_stop() ;
	win.parentNode.removeChild(win) ;
	side_next(win.player, win.winner) ;
}
// Siding procedure
function side_next(player, winner) {
	if ( goldfish && ( player != game.joiner ) ) { // For one of goldfish player's, only side, the other player will start the game
		side_start(game.joiner, winner) ;
		return true ;
	}
	side_newgame(winner) ; // Start a new game
	return false ;
}
function side_newgame(winner) { // Exec by both players after siding (or not siding) or 1 time during goldfish
	game.player.library.refresh() ; // If no change in library top card and it was revealed
	// Ask player for start if he should
	if ( ask_for_start(winner) ) { // Then winner reinitializes all, loser will recieve the sync
		game.player.init()
		game.opponent.init() ;
	}
	// Reinit game data (cards visible in library)
	for ( var i in game.cards ) {
		game.cards[i].watching = false ; // For cards made locally visible for side
		game.cards[i].attrs.visible = null ; // For any card with forced visibility
		game.cards[i].load_image() ;
		game.cards[i].refresh() ;
	}
}
function ask_for_start(winner) { // If player did lose, ask him if he wants to start or draw
	if ( goldfish ) {
		if ( confirm('Do you want '+winner.opponent.name+' to start ?') )
			var player = winner.opponent ;
		else
			var player = winner ;
		if ( game.options.get('auto_draw') )
			game.opponent.hand.mulligan() ; // First draw
	} else {
		if ( game.player != winner ) { // Loser chooses
			if ( confirm('Do you want to start ?') ) {
				message(winner.opponent.name+' choosed to play', 'win') ; // Having the same display when sending and recieving
				var player = game.player ;
			} else {
				message(winner.opponent.name+' choosed to draw', 'win') ;
				var player = game.opponent ;
			}
		} else { // Winner gets info from loser
			if ( game.options.get('auto_draw') )
				game.player.hand.mulligan() ; // First draw
			return false ; // Don't reinit game data, winner will do
		}
	}
	action_send('choose', {'player': player.toString()}) ;
	game.turn.setturn(0, player) ; // First turn
	game.turn.setstep(3) ; // Main phase
	if ( game.options.get('auto_draw') )
		game.player.hand.mulligan() ; // First draw
	return true ;
}
