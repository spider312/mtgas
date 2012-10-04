// dnd.js : utils for draging and droping
function drag_init(card, ev) {
	if ( game.draginit != null )
		log('DND already inited') ;
	else if ( ! spectactor ) {
		if ( iso(ev) ) { // With an event (from canvas) : store where user clicked on card representation
			game.dragxoffset = ev.clientX - card.x ;
			game.dragyoffset = ev.clientY - card.y ;
		} else { // (from list) Store fake data, as the card representation isn't the same size or even proportions (canvas card image Vs li)
			game.dragxoffset = cardwidth / 2 ;
			game.dragyoffset = cardheight / 2 ; 
		}
		game.draginit = card ;
		if ( ! card.selected() ) { // In order to move multiple cards, we must keep selection there, will be modified on mouseup 
			game.selected.set(card) ; // Select only that one
			card.refresh() ;
		}
	}
}
function drag_start() {
	if ( game.draginit == null ) {
		//log('DND not inited')
	} else {
		if ( game.drag != null )
			log('DND already started') ;
		else {
			game.drag = game.draginit ;
			game.drag.dragstart() ; // Store offsets in cards
		}
	}
}
function drag_stop() {
	game.draginit = null ;
	game.drag = null ;
	game.dragover = null ;
}
