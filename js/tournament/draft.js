function start(id, pid) {
	booster_cards = document.getElementById('booster_cards') ;
	drafted_cards = document.getElementById('main') ;
	sided_cards = document.getElementById('side') ;
	ready = document.getElementById('ready') ;
	tournament_info = document.getElementById('tournament_info') ;
	initpooldnd(drafted_cards) ;
	initpooldnd(sided_cards) ;
	TournamentDraft.prototype = new TournamentLimited() ;
	Tournament.prototype = new TournamentDraft() ;
	PlayerDraft.prototype = new PlayerLimited() ;
	Player.prototype = new PlayerDraft() ;
	Log.prototype = new LogLimited() ;
	img_width = 200 ;
	game = {} ;
	game.draging = null ; // Can't use getData/setData under chrome before drop event Oo ugly workaround
	game.image_cache = new image_cache() ;
	game.options = new Options() ;
	game.spectators = new Spectators(function(msg, span) { // Message display
		// Messages are managed via the "log" mechanism which triggers after spectator recieving
	}, function(id) { // I'm a player allowing a spectator
		game.connection.send('{"type": "allow", "value": "'+id+'"}') ;
	}, function(player) { // I'm allowed spectator
		// Nothing appends on a build page when another player allows you
	}) ;
	game.tournament = new Tournament(id) ;
	var wsregistration = {'tournament': id} ;
	if ( pid != '' ) {
		game.tournament.follow = pid ;
		wsregistration.follow = pid ;
	}
	game.connection = new Connexion('draft', function(data, ev) { // OnMessage
		switch ( data.type ) {
			case 'msg' :
				alert(data.msg) ;
				break ;
			case 'running_tournament' :
			case 'ended_tournament' :
				switch ( parseInt(data.status) ) {
					case 3 : // Standard case : Drafting
						game.tournament.recieve(data) ;
						if ( ( game.tournament.me === null ) && ( pid != '' ) ) {
							game.tournament.me = game.tournament.get_player(pid) ;
							game.tournament.me.me = true ; // Spectator overrides "me"
						}
						break ;
					case 4 : // Normal case : Building
						var url = 'build.php?id=' + id ;
						if ( pid !== '' ) { url += '&pid='+pid ; }
						window.location.replace(url) ;
						break ;
					default : // All other cases considered anormal
						// Go to tournament's main page
						window.location.replace('index.php?id='+id) ;
				}
				break ;
			case 'booster' :
				draft_update(data)
				break ;
			case 'deck' : // Initial deck sending
				var scry = window.scrollY ;
				var me = game.tournament.me ;
				me.deck_obj = data ;
				pool_update(drafted_cards, me.deck_obj.main) ;
				pool_update(sided_cards, me.deck_obj.side) ;
				deck_stats_cc(me.deck_obj.main) ;
				window.setTimeout(function(ev) {
					window.scroll(window.scrollX, scry) ; // Scroll back to where user was before recieving pick
				}, 10) ;
				break ;
			case 'pick' : // deck updates
				var scry = window.scrollY ;
				var zone = null ;
				switch ( data.dest ) {
					case 'side' :
						zone = game.tournament.me.deck_obj.side ;
						node = sided_cards ;
						break ;
					case 'main' :
						zone = game.tournament.me.deck_obj.main ;
						node = drafted_cards ;
						break ;
					default :
						debug('Unknown destination for pick : '+data.dest) ;
				}
				if ( zone != null ) {
					zone.push(data.card) ;
					pool_update(node, zone) ;
					if ( data.dest == 'main' )
						deck_stats_cc(game.tournament.me.deck_obj.main) ;
				}
				window.setTimeout(function(ev) {
					window.scroll(window.scrollX, scry) ; // Scroll back to where user was before recieving pick
				}, 10) ;
				break ;
			default : 
				debug('Unknown type '+data.type) ;
				debug(data) ;
		}
	}, function(ev) { // OnClose/OnConnect
		node_empty(tournament_info) ;
		node_empty(booster_cards) ;
	}, wsregistration) ;
	ready.addEventListener('change', function(ev) { // On click
		game.connection.send({"type": "pick", "ready": ev.target.checked}) ;
	}, false) ;
}
function TournamentDraft() {
	this.recieved = function(fileds) {
		node_empty(tournament_info) ;
		// Caption : booster list
		var caption = create_element('caption') ;
		for ( var i = 0 ; i < this.data.boosters.length ; i++ ) {
			var el = create_text(' '+this.data.boosters[i]+' ') ;
			if ( i == this.round-1 ) {
				el = create_element('b', el) ;
			}
			caption.appendChild(el) ;
			if ( i == this.round-1 ) {
				caption.appendChild(create_text('('+booster_cards.childNodes.length+')'));
			}
		}
		tournament_info.appendChild(caption) ;
		// Draft Table
		var cols = round(this.players.length/2, 0) ;
		if ( this.round % 2 == 1 ) // Draft direction
			var sep = {"t": "r", "r": "d", "b": "l", "l": "u"} ;
		else
			var sep = {"t": "l", "r": "u", "b": "r", "l": "d"} ;
		var line1 = create_tr(tournament_info) ;
		create_td(line1, '') ;
		for ( var i = 0 ; i < cols ; i++ ) {
			if ( line1.cells.length > 1 )
				create_td(line1, create_img(theme_image('/arrows/'+sep.t+'.png')[0])) ;
			this.players[i].td(line1) ;
		}
		create_td(line1, '') ;
		if ( cols > 1 ) {
			var line2 = create_tr(tournament_info) ;
			create_td(line2, create_img(theme_image('/arrows/'+sep.l+'.png')[0])) ;
			var td = create_td(line2, '', (2*(cols)-1)) ;
			td.classList.add('table') ;
			create_td(line2, create_img(theme_image('/arrows/'+sep.r+'.png')[0])) ;
		}
		if ( this.players.length > 1 ) {
			var line3 = create_tr(tournament_info) ;
			create_td(line3, '') ;
			if ( this.players.length % 2 == 1)
				create_td(line3, create_img(theme_image('/arrows/'+sep.b+'.png')[0])) ;
			for ( var i = this.players.length-1 ; i >= cols ; i-- ) {
				if ( line3.cells.length > 1 )
					create_td(line3,
						create_img(theme_image('/arrows/'+sep.b+'.png')[0])) ;
				this.players[i].td(line3) ;
			}
			create_td(line3, '') ;
		}
	}
}
function PlayerDraft() {
	this.td = function(tr) { // TD for draft table
		var img = player_avatar(this.avatar_url(), null, null, '../') ;
		var td = create_td(tr, img) ;
		td.classList.add('playerDraft') ;
		var txt = create_text(this.nick) ;
		if ( this.player_id == player_id ) {
			td.classList.add('self') ;
		}
		if ( iss(game.tournament.follow) ) { // Current client is a spectator
			if ( this.player_id === game.tournament.follow ) { // And current player is the one i'm following
				td.classList.add('self') ;
				td.title = "You are following "+this.nick+"'s draft" ;
			} else if ( game.spectators.is_allowed_by(player_id, this.player_id) ) { // And current player allowed me : display a link to view its draft
				txt = create_a(this.nick, 'draft.php?id='+game.tournament.id+'&pid='+this.player_id, null, "View "+this.nick+"'s draft") ;
			}
		}
		td.appendChild(txt) ;
		td.classList.add('player') ;
	}
}
// Generic card image object
function Img(container, ext, name, nb) {
	var alt = '['+ext+']'+name ;
	if ( isn(nb) )
		alt += ' ('+nb+')' ;
	var img = create_img('', '['+ext+']'+name, name) ;
	img.cardname = alt ;
	img.width = img_width ;
	img.classList.add('card') ;
	img.addEventListener('contextmenu', function(ev) {
		window.open('http://magiccards.info/query?q=!'+name+'&v=card&s=cname') ;
		eventStop(ev) ;
	}, false) ;
	container.appendChild(img) ;
	// Loading its URL independently
	img.url = card_image_url(ext, name, nb) ;
	game.image_cache.load(card_images(img.url), function(img, tag) {
		tag.src = img.src ;
		tag.url = img.src ;
	}, function(img, url) {
		debug('Unable to load '+url)
	}, img) ;
	return img ;
}
// Draft update
function draft_update(data) {
	node_empty(booster_cards) ;
	var pick = parseInt(data.pick) ;
	for ( var i = 0 ; i < data.content.length ; i++ ) {
		var card = data.content[i] ;
		// Image in div
		var img = Img(booster_cards, card.ext_img, card.name, card.nb) ;
		img.classList.add('drafting') ;
		if ( i === pick ) {
			img.classList.add('drafting') ;
		}
		var cardId = i + 1 ; // can't use img.id as it's transtyped into string
		img.id = cardId ;
		if ( cardId == Math.abs(parseInt(data.pick)) ) {
			img.classList.add(( data.destination == 'main' ) ? 'pick' : 'side') ;
		}
		// Events
		img.addEventListener('dragstart', eventStop, false) ; // No DND
		if ( game.tournament.follow ) {
			continue ;
		}
		img.addEventListener('click', function(ev) { // On click
			for ( var i = 0 ; i < booster_cards.childNodes.length ; i++ ) {
				var card = booster_cards.childNodes[i] ;
				if ( card != ev.target ) { // Uncheck all other card
					card.classList.remove('pick') ;
					card.classList.remove('side') ;
				}
			}
			// (ctrl) click once to select, second click to check ready
			var cl = ev.target.classList ; // Shortcut for clicked card's class list
			var isset = cl.contains('side') || cl.contains('pick');// Already set before click
			if ( ev.ctrlKey ) { // Asked to put in side
				if ( cl.contains('pick') ) { // Changing from pick
					cl.remove('pick') ;
					isset = false ;
				}
				cl.add('side') ;
			}
			if ( ! cl.contains('side') && ! cl.contains('pick') ) // Not already checked
				cl.add('pick') ;
			var obj = {"type": "pick", "pick": ev.target.id, "main": !cl.contains('side')} ;
			if ( isset )
				obj.ready = true ;
			else
				obj.ready = false ;
			game.connection.send(obj) ;
			eventStop(ev) ;
		}, false) ;
		// Transform
		if ( ! iso(card.attrs) )
			continue ;
		if ( iso(card.attrs.transformed_attrs) && iss(card.attrs.transformed_attrs.name) ) {
			var name = card.attrs.transformed_attrs.name ;
			var url = card_image_url(content.ext_img, name, card.attrs.nb) ;
			game.image_cache.load(card_images(url), function(img, tag) {
				tag.transformed_url = img.src ;
				tag.addEventListener('mouseover', function(ev) {
					ev.target.src = tag.transformed_url ;
				}, false) ;
				tag.addEventListener('mouseout', function(ev) {
					ev.target.src = ev.target.url ;
				}, false) ;
			}, function(card, url) {
			}, img) ;
		}
	}
}
// Pool update
function pool_update(place, zone) {
	node_empty(place) ;
	for ( var i = 0 ; i < zone.length ; i++) {
		var line = zone[i] ;
		switch ( typeof line ) {
			case 'string' : 
				var h2 = document.createElement('h2') ;
				h2.appendChild(document.createTextNode(line)) ;
				place.appendChild(h2) ;
				break ;
			case 'object' :
				var img = Img(place, line.ext_img, line.name, line.attrs.nb) ;
				img.draggable = true ;
				dragimage(img, place, i);
				if ( game.tournament.follow ) {
					img.addEventListener('dragstart', eventStop, false) ; // No DND
				} else {
					img.addEventListener('dragstart', pool_card_dragstart, false) ;
					img.addEventListener('dragenter', pool_card_dragenter, false) ;
					img.addEventListener('dragover', eventStop, false) ; // Confirm drop
					img.addEventListener('drop', pool_drop, false) ;
					img.addEventListener('dragend', pool_card_dragend, false) ;
				}
				break ;
			default : 
				alert(typeof line) ;
		}
	}
}
function initpooldnd(pool) {
	pool.addEventListener('dragenter', function(ev) {
		if ( game.draging == null ) { return true ; }
		var target = ev.target ;
		if ( target.nodeName === 'H1' ) {
			target = target.nextElementSibling ;
		}
		target.appendChild(game.draging) ;
		return eventStop(ev) ;
	}, false) ;
	pool.addEventListener('dragover', eventStop, false) ; // Confirm drop
	pool.addEventListener('drop', pool_drop, false) ;
	if ( pool.previousElementSibling !== null ) {
		initpooldnd(pool.previousElementSibling) ;
	}
}
// Card DND functions
function dragimage(img, place, i) { // Adds/update data of a card image to make it dragable
	img.idxFrom = i ;
	img.zoneFrom = place ;
	img.id = place.id + i ;
}
function pool_drop(ev) { // Drop on pool or card
	var drag = game.draging ;
	if ( drag == null ) { return true ; }
	var zoneTo = drag.parentNode.id ;
	var zoneFrom = drag.zoneFrom.id ;
	var zone = drag.parentNode.childNodes ;
	var idxTo = zone.indexOf(drag) ;
	if ( zoneTo === zoneFrom ) { // Same zone, just change index
		if ( drag.idxFrom != idxTo ) { // If it changed
			game.connection.send({"type": "reorder", "pool": zoneTo, "from": drag.idxFrom, "to": idxTo}) ;
		}
	} else { // Zone change, send toggle
		game.connection.send({"type": "toggle", "card": drag.title, "from": zoneFrom, "to": idxTo}) ;
	}
	for ( var i = 0 ; i < zone.length ; i++) {
		dragimage(zone[i], drag.parentNode, i);
	}
	return eventStop(ev) ; // No redirect to img
}
function pool_card_dragstart(ev) {
	ev.target.classList.add('drag') ;
	game.draging = ev.target ;
}
function pool_card_dragenter(ev) {
	var drag = game.draging ;
	if ( game.draging === null ) { return true ; }
	if ( game.draging === ev.target ) { return eventStop(ev) ; }
	// Dragged image
	var pf = game.draging.parentNode ;
	// Image after which Dragged will be inserted
	var after = ev.target ;
	var pt = after.parentNode ;
	// Correct left to right in same parent
	var from = pf.childNodes.indexOf(game.draging) ;
	var to = pt.childNodes.indexOf(after) ;
	if ( from < to ) {
		after = after.nextElementSibling ;
	}
	pt.insertBefore(game.draging, after) ;
	return eventStop(ev) ;
}
function pool_card_dragend(ev) {
	ev.target.classList.remove('drag') ;
	game.draging = null ;
}
NodeList.prototype['indexOf'] = Array.prototype['indexOf'];
