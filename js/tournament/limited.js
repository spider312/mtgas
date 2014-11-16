// Common code between Draft and Build pages
window.addEventListener('load', function(ev) {
	document.getElementById('tournament').addEventListener('mouseover', function(ev) {
		document.getElementById('tournament').classList.remove('highlight') ;
	}, false) ;
}, false) ;
window.addEventListener('resize', limited_resize, false) ;
function limited_resize(ev) {
	var chat = document.getElementById('chat') ;
	tournament_log.style.height = (chat.offsetTop - tournament_log.offsetTop - 2)+'px' ;
}
// Top indicator
function TournamentLimited() {
	this.display = function(fields) {
		limited_resize() ; // "onload" with players list drawn
		if ( inarray('due_time', fields) )
			start_timer(timeleft, this.due_time, true) ;
		if ( inarray('name', fields) ) {
			var al = document.getElementById('aditional_link') ;
			if ( al == null )
				debug('No additionnal link span') ;
			else {
				node_empty(al) ;
				al.appendChild(create_text(' > ')) ;
				var name = this.name ;
				if ( iso(this.data.boosters) )
					name += ' ('+this.data.boosters.join('-')+')' ;
				var a = create_a(name, '/tournament/?id='+this.id) ;
				a.title = 'Open me in a new tab, or you will be redirected here' ;
				al.appendChild(a) ;
			}
		}
	}
}
// Right column
function PlayerLimited() {
	this.display = function(fields) { // Display in right column
		if ( this.status > 6 ) {
			if ( this.node != null ) {
				this.node.parentNode.removeChild(this.node) ;
				this.node = null ;
			}
			return false ;
		}
		var player = this ;
		var cb = create_checkbox('', this.ready != '0') ;
		cb.disabled = true ;
		var li = create_li(cb) ;
		var txt = this.nick ;
		if ( isn(this.deck_cards) )
			txt += ' : '+this.deck_cards+' / '+this.side_cards ;
		li.appendChild(document.createTextNode(txt)) ;
		if ( 
			( this.player_id == player_id ) // Self
			|| ( this.player_id == game.tournament.follow )
		) {
			li.classList.add('self') ;
			ready.checked = this.ready ;
		}
		if ( this.node == null )
			players_ul.appendChild(li) ;
		else
			this.node.parentNode.replaceChild(li, this.node) ;
		this.node = li ;
		return this.node ;
	}
}
function LogLimited() {
	this.display = function(fields) {
		if ( fields.length > 0 ) {
			var scrbot = tournament_log.scrollHeight - ( tournament_log.scrollTop + tournament_log.clientHeight ) ; // Scroll from bottom, if 0, will scroll to see added line
			this.update_node(this.generate(), tournament_log) ;
			if ( scrbot == 0 )
				tournament_log.scrollTop = tournament_log.scrollHeight
			document.getElementById('tournament').classList.add('highlight') ;
		}
	}
}
