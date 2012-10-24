// side.js
// Siding / Game chaining
function side_close(win) { // Common run between buttons ok and cancel in side window
	win.player.side_stop() ;
	win.parentNode.removeChild(win) ;
	side_next(win.player, win.winner) ;
}
function side_start(player, winner) {
	player.side_start() ;
	// Fake window
	var side_window = create_div() ;
	side_window.id = 'side_window' ;
	side_window.player = player ;
	side_window.winner = winner ;
	// Lists + switch button
	var deck_ul = create_ul('deck')
	side_window.appendChild(deck_ul) ;
	var but_switch = create_button(create_img(theme_image('deckbuilder/1leftarrow.png')[0], '=&gt;'), function(ev) {
		var doc = ev.target.parentNode ;
		if ( ! doc.selected['deck'] ) {
			game.infobulle.set('Please select a card in deck before swapping') ;
			return null ;
		}
		if ( ! doc.selected['side'] ) {
			game.infobulle.set('Please select a card in side before swapping') ;
			return null ;
		}
		side_swap(doc.selected['deck'], doc.selected['side'], doc) ;
	}, 'Switch selected maindeck card with selected side card') ;
	but_switch.id = 'button_switch' ;
	side_window.appendChild(but_switch) ;
	var side_ul = create_ul('side') ;
	side_window.appendChild(side_ul) ;
	// Buttons
	var div_but = create_div() ;
	div_but.id = 'buttons' ;
	var but_reload = create_button(create_img(theme_image('deckbuilder/reload.png')[0], 'Reload'), function(ev) {
		var toswapout = [] ;
		var toswapin = [] ;
		for ( var i = 0 ; i < deck_ul.children.length ; i++ )
			for ( var j = 0 ; j < deck_ul.children[i].cards.length ; j++ )
				if ( deck_ul.children[i].cards[j].init_zone.type != 'library' )
					toswapout.push(deck_ul.children[i]) ;
		for ( var i = 0 ; i < side_ul.children.length ; i++ )
			for ( var j = 0 ; j < side_ul.children[i].cards.length ; j++ )
				if ( side_ul.children[i].cards[j].init_zone.type != 'sideboard' )
					toswapin.push(side_ul.children[i]) ;
		if ( toswapout.length != toswapin.length ) 
			alert('Not the same number of cards to swap') ;
		else
			while ( ( toswapout.length > 0 ) && ( toswapin.length > 0 ) )
				side_swap(toswapout.pop(), toswapin.pop(), null) ;
	}, 'Reinitialize your deck as described in decklist') ;
	but_reload.id = 'button_reload' ;
	div_but.appendChild(but_reload) ;
	var but_ok = create_button(create_img(theme_image('deckbuilder/button_ok.png')[0], 'OK'), function(ev) {
		side_in_out(deck_ul, player.library) ; // Side in
		side_in_out(side_ul, player.sideboard) ; // Side out
		side_close(ev.target.parentNode.parentNode) ;
	}, 'Validate side') ;
	but_ok.id = 'button_ok' ;
	div_but.appendChild(but_ok) ;
	var but_can = create_button(create_img(theme_image('deckbuilder/button_cancel.png')[0], 'Cancel'), function(ev) {
		side_undo(player) ;
		side_close(ev.target.parentNode.parentNode) ;
	}, 'Cancel side')
	but_can.id = 'button_cancel' ;
	div_but.appendChild(but_can) ;
	side_window.appendChild(div_but) ;
	// Lists filling
	side_window.selected = new Array() ;
	var deckcards = new Array() ;
	var sidecards = new Array() ;
	for ( var i in game.cards ) { // Create arrays of cards in order to group cards with the same name
		var card = game.cards[i] ;
		switch ( card.orig_zone ) {
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
	// When arrays are filled, create ULs
	for ( var i in deckcards )
		deck_ul.appendChild(side_create_li(deckcards[i])) ;
	for ( var i in sidecards )
		side_ul.appendChild(side_create_li(sidecards[i])) ;	
	document.body.appendChild(side_window) ;
	but_can.focus() ;
}
function side_in_out(ul, zone) { // Side in or side out depending on params (send all cards in ul to zone)
	var zones = {} ; // Set of card selections, one per zone where a card can be
	for ( var i = 0 ; i < ul.childNodes.length ; i++ ) {
		var cards = ul.childNodes[i].cards ;
		for ( var j in cards ) {
			var card = cards[j] ;
			if ( card.zone != zone )  {
				//card.orig_zone = zone ; // Managed in changezone in order to work on recieve
				if ( ! zones[card.zone.toString()] ) // First card for this zone
					zones[card.zone.toString()] = new Selection(card) ; // Create selection
				else // Not first card for this zone
					zones[card.zone.toString()].add(card) ; // Add to previously created
			}
		}
	}
	for ( var i in zones )
		zones[i].changezone(zone) ;
}
function side_undo(player) { // Send all player's cards where it should be at begining of a game
	var zones = {} ; // Set of card selections, one per zone where a card can be
	for ( var i = 0 ; i < game.cards.length ; i++ ) {
		var card = game.cards[i] ; // We use global cards here in order to get back cards in an opponent's zone (stolen creature on its BF for example)
		if ( ( card.owner == player ) && ( card.zone != player.library ) && ( card.zone != player.sideboard ) ) // My card, elsewhere than my library and my sideboard
			if ( ! zones[card.zone.toString()] ) // First card for this zone
				zones[card.zone.toString()] = new Selection(card) ; // Create selection
			else // Not first card for this zone
				zones[card.zone.toString()].add(card) ; // Add to previously created
	}
	for ( var i in zones )
		zones[i].changezone(player.library) ;
}
function side_next(player, winner) {
	if ( goldfish && ( player != game.joiner ) ) { // For one of goldfish player's, only side, the other player will start the game
		side_start(game.joiner, winner) ;
		return true ;
	}
	side_newgame(winner) ; // Start a new game
	return false ;
}
function side_newgame(winner) { // Exec by both players after siding (or not siding) or 1 time during goldfish
	// Reinit game data (cards visible in library)
	for ( var i in game.cards ) {
		game.cards[i].watching = false ; // For cards made locally visible for side
		game.cards[i].attrs.visible = null ; // For any card with forced visibility
		game.cards[i].refresh() ;
	}
	game.player.library.refresh() ; // If no change in library top card and it was revealed
	// Ask player for start if he should
	if ( ask_for_start(winner) ) { // Then winner reinitializes all, loser will recieve the sync
		game.player.init()
		game.opponent.init() ;
	}
}
function ask_for_start(winner) { // If player did lose, ask him if he wants to start or draw
	if ( goldfish ) {
		if ( confirm('Do you want '+winner.opponent.name+' to start ?') )
			var player = winner.opponent ;
		else
			var player = winner ;
		if ( localStorage['auto_draw'] == 'true' )
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
			if ( localStorage['auto_draw'] == 'true' )
				game.player.hand.mulligan() ; // First draw
			return false ; // Don't reinit game data, winner will do
		}
	}
	action_send('choose', {'player': player.toString()}) ;
	game.turn.setturn(0, player) ; // First turn
	game.turn.setstep(3) ; // Main phase
	if ( localStorage['auto_draw'] == 'true' )
		game.player.hand.mulligan() ; // First draw
	return true ;
}
// Lib
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
function side_click_card(ev) {
	var doc = ev.target.parentNode.parentNode ;
	var zone = ev.target.parentNode.id ;
	if ( doc.selected[zone] )
		doc.selected[zone].className = ''
	doc.selected[zone] = this ;
	doc.selected[zone].className = 'selected' ;
}
function side_swap(deckcard, sidecard, doc) {
	var deck = deckcard.parentNode ;
	var side = sidecard.parentNode ;
	// Add side card to deck
	var card = sidecard.cards.pop() ;
	sidecard.firstChild.nodeValue = sidecard.cards.length ;
	if ( sidecard.firstChild.nodeValue < 1 ) { // If 0 occurences of card in side
		side.removeChild(sidecard) ; // Remove visual from side
		if ( doc != null ) 
			delete doc.selected['side'] ; // Consider that no side cards are selected
	}
	var exist = side_name_in_pack(card.name, deck) ; // Is it already in ?
	if ( exist > -1 ) { // It is
		deck.childNodes[exist].cards.push(card) ;
		deck.childNodes[exist].firstChild.nodeValue = deck.childNodes[exist].cards.length ;
	} else // It isn't
		deck.appendChild(side_create_li(Array(card), deck.ownerDocument)) ; // Add it
	// Add deck card to side
	var card = deckcard.cards.pop() ;
	deckcard.firstChild.nodeValue = deckcard.cards.length ;
	if ( deckcard.firstChild.nodeValue < 1 ) { // If 0 occurences of card in deck
		deck.removeChild(deckcard) ; // Remove visual from deck
		if ( doc != null ) 
			delete doc.selected['deck'] ; // Consider that no side cards are selected
	}
	var exist = side_name_in_pack(card.name, side) ; // Is it already in ?
	if ( exist > -1 ) { // It is
		side.childNodes[exist].cards.push(card) ;
		side.childNodes[exist].firstChild.nodeValue = side.childNodes[exist].cards.length ;
	} else // It isn't
		side.appendChild(side_create_li(Array(card), side.ownerDocument)) ; // Add it
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
