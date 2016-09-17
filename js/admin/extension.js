$(function() { // On page load
	ajax_error_management() ;
	// Extension update
	document.getElementById('update_ext').addEventListener('submit', function(ev) {
		ev.target.classList.add('updating') ;
		$.getJSON(ev.target.action, form2param(ev.target), function(data) {
			ev.target.classList.remove('updating') ;
			if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
				ev.target.submit.value = data.msg ;
			else {
				if ( data.nb != 1 )
					ev.target.submit.value = data.nb+' rows updated' ;
				else
					ev.target.submit.value = 'Updated' ;
			}
		}) ;
		return eventStop(ev) ;
	}, false) ;
	document.getElementById('filter').addEventListener('submit', function(ev) {
		var sortobj = {} ;
		if ( ev.target.rarity.value != '' )
			sortobj.rarity = ev.target.rarity.value ;
		if ( ev.target.text.value != '' )
			sortobj.text = ev.target.text.value ;
		update_list(sortobj) ;
		return eventStop(ev) ;
	}, false) ;
	// Cards management
	var ext_h = document.getElementById('ext') ;
	ext = ext_h.value ;
	cardnb = document.getElementById('cardnb') ;
	cards = [] ;
	// Stats
	stats_multi = document.getElementById('stats_multi') ;
	stats_multi.addEventListener('click', function(ev) {
		update_stats() ;
	}, false) ;
	// Display cards
	update_list() ;
}) ;
function update_list(obj, callback) {
	if ( !iso(obj) )
		obj = {} ;
	obj['ext'] = ext ;
	$.getJSON('json/cards.php', obj, function(data) {
		cardnb.value = data.length ;
		var tbody = document.getElementById('cards')
		node_empty(tbody) ;
		var rarities = [] ;
		for ( var i = 0 ; i < data.length ; i++ ) {
			var card = data[i] ;
			card.attrs = JSON.parse(card.attrs) ;
			var row = create_tr(tbody) ;
			row.id = card.id ;
			row.title = card.text ;
			create_td(row, create_a(card.name, 'card.php?id='+card.id)) ;
			create_td(row, card.cost) ;
			create_td(row, card.multiverseid) ;
			create_td(row, create_button('Remove', remove_from_ext)) ;
			var form = create_form('/admin/cards/json/ext_update_card.php') ;
			var j = create_input('nbpics', card.nbpics) ;
			j.id = 'p'+i;
			j.size = 2 ;
			form.appendChild(j) ;
			j = create_input('rarity', card.rarity) ;
			j.id = 'r'+i;
			j.size = 2 ;
			form.appendChild(j) ;
			form.appendChild(create_submit('', 'Set')) ;
			form.addEventListener('submit', card_ext_update, false) ;
			create_td(row, form) ;
			// Rarities count
			if ( ! rarities[card.rarity] )
				rarities[card.rarity] = 0 ;
			rarities[card.rarity]++ ;
		}
		var r = document.getElementById('rarities') ;
		node_empty(r) ;
		for ( var i in rarities )
			r.appendChild(create_li(i+' : '+rarities[i])) ;
		cards = data ;
		update_stats() ;
		if ( isf(callback) ) {
			callback() ;
		}
	}) ;
}
function update_stats() {
	if ( stats_multi.checked ) {
		var mycards = [] ;
		for ( var i = 0 ; i < cards.length ; i++ ) {
			var card = cards[i] ;
			if ( card.attrs.color.length <2 )
				mycards.push(card) ;
		}
	} else
		mycards = cards ;
	deck_stats_cc(mycards) ;
}
function remove_from_ext(ev) {
	var cell = ev.target.parentNode ;
	var row = cell.parentNode ;
	cell.classList.add('updating') ;
	$.getJSON('json/ext_remove_card.php', {'ext': ext, 'card': row.id}, function(data) {
		cell.classList.remove('updating') ;
		if ( ( data.nb != 0 ) && ( data.nb != 1 ) )
			alert(data.nb) ;
		update_list() ;
	}) ;
}
function card_ext_update(ev) {
	var currfocus = document.activeElement.id ;
	var form = ev.target ;
	var row = ev.target.parentNode.parentNode ;
	form.classList.add('updating') ;
	$.getJSON(form.action, {'ext': ext, 'card': row.id, 'nbpics': form.nbpics.value, 'rarity': form.rarity.value}, function(data) {
		form.classList.remove('updating') ;
		if ( ( data.nb != 0 ) && ( data.nb != 1 ) )
			alert(data.nb) ;
		update_list(null, function() {
			console.log(currfocus) ;
			if ( iss(currfocus) ) {
				var col = currfocus.substr(0, 1) ;
				var nb = parseInt(currfocus.substr(1));
				currfocus = col + (nb+1) ;
				var el = document.getElementById(currfocus) ;
				if ( el === null ) {
					currfocus = col + nb ;
					var el = document.getElementById(currfocus) ;
				}
				el.select() ;
			}
		}) ;
	}) ;
	return eventStop(ev) ;
}
