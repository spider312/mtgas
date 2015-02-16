function ranking_import(period, content, update) {
	var arr = [] ;
	for ( var i in content ) {
		content[i].player_id = i ;
		content[i].ratio = content[i].score / content[i].matches ;
		arr.push(content[i]) ;
	}
	tabs[period] = {'players': arr, 'update': update} ;
}
tabs = {} ;
selected = 'week' ;
sort = 'ratio' ;
reverse = false ;
player_id = $.cookie(session_id) ;
function init() {
	game = {} ;
	game.options = new Options() ;
	var tabs_div = document.getElementById('tabs') ;
	tabs_div.id = 'tabs_div' ;
	for ( var i in tabs ) {
		str = i[0].toUpperCase() ;
		str += i.substr(1) ;
		var span = create_span(' '+str+' ') ;
		span.id = i ;
		if ( i == selected )
			span.classList.add('selected') ;
		tabs_div.appendChild(span) ;
	}
	tabs_div.addEventListener('click', function(ev) {
		var clicked = ev.target ;
		if ( clicked.tagName == 'SPAN' ) {
			var div = clicked.parentNode ;
			for ( var i = 0 ; i < div.childNodes.length ; i++ ) {
				var span = div.childNodes[i] ;
				if ( span == clicked ) {
					span.classList.add('selected') ;
					if ( selected != span.id ) {
						selected = span.id ;
						refresh() ;
					}
				} else
					span.classList.remove('selected') ;
			}
		}
	}, false) ;
	var table = document.getElementById('table') ;
	table.parentNode.tHead.addEventListener('click', function(ev) {
		if ( ev.target.classList.contains('sortable') ) {
			if ( ev.target.id != sort ) {
				sort = ev.target.id ;
				reverse = false ;
			} else
				reverse = ! reverse ;
			if ( reverse )
				ev.target.parentNode.classList.add('desc') ;
			else
				ev.target.parentNode.classList.remove('desc') ;
			refresh()
		}
	}, false) ;
	refresh() ;
}
function refresh() {
	// Sort data
	var data = tabs[selected].players ;
	data.sort(function(a, b) {
		if ( a[sort] == b[sort] )
			return 0 ;
		else 
			return ( a[sort] < b[sort] ) ;
	})
	if ( reverse )
		data.reverse() ;
	var table = document.getElementById('table') ;
	// Apply classes on table columns head
	var cells = table.parentNode.tHead.rows[0].cells ;
	for ( var i = 0 ; i < cells.length ; i++ )
		if ( cells[i].classList.contains('sortable') && ( cells[i].id == sort ) )
			cells[i].classList.add('sorted') ;
		else
			cells[i].classList.remove('sorted') ;
	// Fill players list
	node_empty(table) ;
	for ( var i = 0 ; i < data.length ; i++ )
		player(table, data[i], i) ;
	/*
	for ( var i = 0 ; ( i < data.length ) && ( i < 10 )  ; i++ )
		player(table, data[i], i) ;
	if ( data.length > 10 ) {
		var foot = table.parentNode.createTFoot() ;
		node_empty(foot) ;
		foot = foot.insertRow().insertCell() ;
		foot.colSpan = 6 ;
		foot.appendChild(create_text('Top 10 of '+data.length+' players')) ;
		foot.appendChild(create_button('Show', function(ev) {
			table.parentNode.deleteTFoot() ;
			for ( var i = 10 ; i < data.length ; i++ )
				player(table, data[i], i) ;
		})) ;
	} else
		table.parentNode.deleteTFoot() ;
	*/
	// Caption : update date
	var cap = table.parentNode.createCaption() ;
	node_empty(cap) ;
	var update = new Date(tabs[selected].update*1000) ;
	var date = update.toLocaleString() ;
	//date = date.substr(0, date.length-5) ;
	cap.appendChild(create_text(date)) ;
}
function player(table, data, n) {
	var tr = table.insertRow() ;
	tr.insertCell().appendChild(create_text(''+(n+1))) ;
	tr.insertCell().appendChild(create_img(data.avatar)) ;
	tr.insertCell().appendChild(create_a(data.nick, 'player.php?id='+data.player_id)) ;
	tr.insertCell().appendChild(create_text(data.matches)) ;
	tr.insertCell().appendChild(create_text(data.score)) ;
	tr.insertCell().appendChild(create_text(round(data.ratio, 2))) ;
	if ( data.player_id == player_id )
		tr.classList.add('self') ;
	if ( iso(data.alias) && ( data.alias.indexOf(player_id) > -1 ) )
		tr.classList.add('self') ;
}
