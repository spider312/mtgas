function start(ev) { // On page load
	game = {} ;
	game.options = new Options(true) ;
	game.import_submit = document.getElementById('import_submit') ;
	game.import_form = document.getElementById('import_form') ;
	for ( var i = 0 ; i < game.import_form.elements.length ; i++ ) {
		var el = game.import_form.elements[i] ;
		if ( el.type !== 'submit' ) {
			save_restore(el) ;
		}
	}
	game.import_form.addEventListener('submit', function(ev) {
		if ( this.noValidate )
			return eventStop(ev) ;
		this.noValidate = true ;
		game.import_submit.disabled = true ;
		var obj = { "type": "import" } ;
		for ( var i = 0 ; i < this.elements.length ; i++ ) {
			var el = this.elements[i] ;
			if ( el.type === 'submit' ) {
				continue ;
			}
			if ( el.value ) {
				obj[el.name] = el.value ;
			} else {
				el.focus() ;
				return eventStop(ev) ;
			}
		}
		node_empty(game.download_table) ;
		game.connection.send(JSON.stringify(obj)) ;
		return eventStop(ev) ;
	}, false) ;
	game.download_table = document.getElementById('download_table') ;
	// Websockets
	game.connection = new Connexion('import', function(data, ev) { // OnMessage
		switch ( data.type  ) {
			case 'msg' :
				alert(data.value) ;
				break ;
			case 'downloaded_extension' :
				console.log(data) ;
				node_empty(game.download_table) ;
				var cards = data.cards ;
				for ( var i = 0 ; i < cards.length ; i++ ) {
					downloaded_card(cards[i]) ;
				}
				update_card_nb(game.download_table) ;
				game.import_form.noValidate = false ;
				game.import_submit.disabled = false ;
				break ;
			case 'downloaded_card' :
				downloaded_card(data) ;
				update_card_nb(game.download_table) ;
				break;
			default : 
				console.log('Unknown type '+data.type) ;
				console.log(data) ;
		}
	}, eventStop);// OnClose/OnConnect
}

function downloaded_card(data) {
	var tb = game.download_table ;
	var text = create_element('pre', data.text) ;
	text.addEventListener('click', function(ev) {
		this.classList.toggle('expanded') ;
	}, false) ;
	var imgs = create_span() ;
	append_images(data.images, imgs) ;
	var langs = create_span() ;
	for ( var code in data.langs ) {
		var lang = data.langs[code] ;
		langs.appendChild(create_text(code)) ;
		langs.title = lang.name ;
		append_images(lang.images, langs) ;
	}
	var tr = create_tr(tb, 
		data.rarity
		, data.name
		, data.cost
		, data.types
		, text
		, imgs
		, langs
	) ;
	return tr ;
}
function append_images(data, td) {
	for ( var i = 0 ; i < data.length ; i++ ) {
		td.appendChild(create_a('['+(i+1)+']', data[i], zoom, data[i])) ;
	}
}
function update_card_nb(tb) {
	var cap = tb.parentElement.caption ;
	node_empty(cap) ;
	cap.appendChild(create_text(tb.rows.length+' cards')) ;
	return cap ;
}
function zoom(ev) {
	var but = this ;
	but.disabled = true ;
	var img = create_img(this.href) ;
	img.classList.add('zoom') ;
	var clickX = ev.pageX ;
	var clickY = ev.pageY ;
	img.addEventListener('load', function(ev) {
		var left = clickX - Math.round(img.width/2) ;
		img.style.left = left+'px' ;
		var top = clickY - Math.round(img.height/2) ;
		img.style.top = top+'px' ;
		document.body.appendChild(this) ;
		this.addEventListener('click', function(ev) {
			this.parentNode.removeChild(this) ;
			but.disabled = false ;
			return eventStop(ev) ;
		}, false) ;
	}, false) ;
	return eventStop(ev) ;
}
