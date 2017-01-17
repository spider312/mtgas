// evaluation.js : manages evaluation of players
function evaluate(rating, callback) {
	var rating_descs = [
		'Cheater / insulting',
		'Not fair play',
		'No comment',
		'Fairplay, gentle, or a little bit of each',
		'Fairplay and gentle'
	] ;
	// Evaluation window
	var container = create_div() ;
	container.id = 'evaluation' ;
		// Title
	var title = create_element('h1') ;
	title.appendChild(create_text('Evaluate your opponent')) ;
	container.appendChild(title) ;
		// Avatar
	var img = create_img(game.opponent.avatar) ;
	var div = create_div(img) ;
	div.id = 'evaluation_avatar'
	container.appendChild(div) ;
		// Explaination
	container.appendChild(create_div(create_text('You may now evaluate '+game.opponent.get_name()))) ;
		// Rating
	var default_rating = rating + 2 ; // Defaults to score 0 = 3 stars = index 2
	var rating_ul = create_ul('evaluation_rating') ;
	rating_ul.setAttribute('rating', default_rating) ;
	for ( var i = 0 ; i < rating_descs.length ; i++ ) {
		var rating_li = create_li('', 'evaluation_rating') ;
		rating_li.setAttribute('rating', i)
		rating_li.title = rating_descs[i] ;
		rating_li.addEventListener('mouseover', evaluate_hover_rating, false) ;
		rating_li.addEventListener('mouseout', evaluate_leave_rating, false) ;
		rating_li.addEventListener('click', evaluate_click_rating, false) ;
		if ( i <= default_rating ) { // Default display ranking
			rating_li.classList.add('enabled') ;
		}
		rating_li.classList.toggle('selected', ( i === default_rating ) )
		rating_ul.appendChild(rating_li) ;
	}
	container.appendChild(rating_ul) ;
		// Rating description
	var rating_desc = create_div(rating_descs[default_rating]) ;
	rating_desc.id = 'rating_desc' ;
	container.appendChild(rating_desc) ;
		// Buttons
	var buttons = create_div() ;
	buttons.id = 'evaluation_buttons' ;
	var but_ok = create_button(create_img(theme_image('deckbuilder/button_ok.png')[0], 'OK'), function(ev) {
		document.body.removeChild(hider) ;
		var rating = parseInt(rating_ul.getAttribute('rating'), 10) - 2 ;
		game.connection.send(JSON.stringify({"type": "evaluation", "opponent_id": game.opponent.id, "rating": rating})) ;
		callback(true) ;
	}, 'Validate evaluation') ;
	but_ok.id = 'button_ok' ;
	buttons.appendChild(but_ok);
	var but_can = create_button(create_img(theme_image('deckbuilder/button_cancel.png')[0], 'Cancel'), function(ev) {
		document.body.removeChild(hider) ;
		callback(false) ;
	}, 'Cancel evaluation') ;
	but_can.id = 'button_cancel' ;
	buttons.appendChild(but_can);
	container.appendChild(buttons) ;
	// Hider
	var hider = create_div() ;
	hider.appendChild(container) ;
	hider.id = 'evaluation_hider' ;
	hider.classList.add('hider') ;
	document.body.appendChild(hider) ;
	but_can.focus() ;
}

function evaluate_hover_rating() {
	var li = this ;
	var ul = li.parentNode ;
	var rating = parseInt(li.getAttribute('rating'), 10) ; // LI -> rating of hovered
	// Update enabled to hovered
	for ( var i = 0 ; i < ul.childNodes.length ; i++ ) {
		var myli = ul.childNodes[i] ;
		myli.classList.toggle('enabled', ( i <= rating ) ) ; // Enable stars depending on hovered one
	}
	// Update desc to hovered
	var rating_desc = document.getElementById('rating_desc') ;
	if ( rating_desc === null ) { return false ; }
	node_empty(rating_desc) ;
	rating_desc.appendChild(create_text(li.title)) ;
}
function evaluate_leave_rating() {
	var li = this ;
	var ul = li.parentNode ;
	var rating = parseInt(ul.getAttribute('rating'), 10) ; // UL -> previously selected rating
	rating = parseInt(rating, 10) ;
	// Update enabled to selected
	for ( var i = 0 ; i < ul.childNodes.length ; i++ ) {
		var myli = ul.childNodes[i] ;
		myli.classList.toggle('enabled', ( i <= rating ) ) ; // Enable stars depending on selected one
	}
	// Update desc to selected
	var rating_desc = document.getElementById('rating_desc') ;
	if ( rating_desc === null ) { return false ; }
	node_empty(rating_desc) ;
	rating_desc.appendChild(create_text(ul.childNodes[rating].title)) ;
}

function evaluate_click_rating() {
	var li = this ;
	var ul = li.parentNode ;
	var rating = parseInt(li.getAttribute('rating'), 10) ;
	ul.setAttribute('rating', rating); // Consider clicked li's rating as selected rating
	for ( var i = 0 ; i < ul.childNodes.length ; i++ ) {
		var myli = ul.childNodes[i] ;
		myli.classList.toggle('selected', ( i === rating ) )
	}
}
