// Metagame.js : Form dynamism in metagame card analysis (former sealed_top.php)
function start() { // On page load
	game = {} ;
	game.options = new Options(true) ;
}
function select(ev) { // Double click on a select : selects only clicked option
	var option = ev.target ;
	var select = option.parentElement ;
	for ( var i = 0 ; i < select.options.length ; i++ ) {
		var myOption = select.options[i] ;
		myOption.selected = ( myOption === option ) ;
	}
}
function filter(ev) { // HTML Filter mousedown or mouseover : selects or unselects option
	var value = ! ev.target.selected ; // Value to apply until drop : opposite to clicked element's
	switch ( ev.type ) { // Store or get
		case 'mousedown' : 
			this.lastFilterValue = value ;
			break ;
		case 'mouseover' :
			value = this.lastFilterValue ;
			break ;
		default :
			console.log('Unknown event type '+ev.type) ;
	}
	switch ( ev.buttons ) { // Apply
		case 0 :
			break ;
		case 1 : // Left click
		case 2 : // Right click
			ev.target.selected = value ;
			break ;
		default :
			console.log('Event '+ev.type+' with unknown button '+ev.buttons) ;
	}
	return eventStop(ev) ;
}
function filter_reset(ev) { // Reset button : Reset's all multiple selects' options in target's form to true
	var form = ev.target.form ;
	for ( var i = 0 ; i < form.elements.length ; i++ ) {
		var el = form.elements[i] ;
		if ( ( el.nodeName === 'SELECT' ) && el.multiple ) {
			for ( var j = 0 ; j < el.options.length ; j++ ) {
				el.options[j].selected = true ;
			}
		}
	}
	form.submit() ;
}
function form_submit(ev) { // On form submit, adds anchor to action depending on clicked button
	switch (ev.target.value) {
		case 'Load' :
			ev.target.form.action += '#stats';
			break ;
		case 'Apply' :
			ev.target.form.action += '#list' ;
			break ;
	}
}
