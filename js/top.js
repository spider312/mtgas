// Init
function init(ev) {
	game = {} ;
	game.options = new Options() ;
	game.image_cache = new image_cache() ;
	tabs = new Tabs('week', 'month', 'year', 'all') ; // Initialises everything
}
// ========== Tabs ==========
function Tabs() {
	// Create tabs
	var tabs_div = document.getElementById('tabs') ; // Tabs view container
	this.selected = null ; // Currently selected tab
	this.data = {} ; // Dictionnary of tabs
	var args = Tabs.arguments ;
	for ( var i = 0 ; i < args.length ; i++ ) {
		var name = args[i] ;
		var tab = new Tab(this, name) ;
		this.data[name] = tab ;
		tabs_div.appendChild(tab.build()) ; // Build view
		if ( this.selected === null ) {
			this.select(tab) ; // Select first added tab
		}
	}
	// Init sort
	this.players = document.getElementById('table') ; // Cache players tbody for players adding
	this.table = this.players.parentNode ; // Cache full table for "loading" display
	this.head = this.table.tHead ; // Cache sort buttons thead
	this.head.addEventListener('click', function(ev) { // Event on table head
		if ( ev.target.classList.contains('sortable') ) {
			tabs.sort(ev.target.id) ;
			tabs.display() ;
		}
	}, false) ;
	this.sort('ratio') ;
	// First display
	this.display() ;
}
Tabs.prototype.select = function(tab) { // Selects a tab, unselecting others
	this.selected = tab ;
	for ( var i in this.data ) {
		var currtab = this.data[i]
		if ( currtab === this.selected ) {
			currtab.select() ;
		} else {
			currtab.unselect() ;
		}
	}
}
Tabs.prototype.sort = function(sort) { // Change current sorting and updates graphical sorters
	// Defint sorting field and reverse
	if ( this.sortField === sort ) { // Sorting already sorted field
		this.reverse = ! this.reverse ; // Just change order
	} else { // New sort
		this.sortField = sort ;
		this.reverse = false ; // Reinit order
	}
	// Apply classes on table columns head
	var cells = this.head.rows[0].cells ;
	for ( var i = 0 ; i < cells.length ; i++ ) {
		if ( cells[i].classList.contains('sortable') && ( cells[i].id == this.sortField ) ) {
			cells[i].classList.add('sorted') ;
		} else {
			cells[i].classList.remove('sorted') ;
		}
	}
	// Apply order class
	if ( this.reverse ) {
		this.head.classList.add('desc') ;
	} else {
		this.head.classList.remove('desc') ;
	}
}
Tabs.prototype.display = function() { // Security wrapper
	if ( this.selected !== null ) {
		this.selected.display() ;
	}
}
// ========== Tab ==========
function Tab(container, name) {
	this.container = container ;
	this.name = name ;
	this.players = null ;
	this.span = null ;
	//this.nocache = true ; // re-DL data on each tab select
}
// Build view
Tab.prototype.build = function() {
	var str = this.name[0].toUpperCase() ;
	str += this.name.substr(1) ;
	this.span = create_span(str) ;
	this.span.id = this.name ;
	var tab = this ; // Closure
	this.span.addEventListener('click', function(ev) {
		tab.container.select(tab) ;
		tab.display() ;
	}, false) ;
	return this.span ;
}
// Select/unselect
Tab.prototype.select = function() {
	if ( this.span !== null ) {
		this.span.classList.add('selected') ;
	}
}
Tab.prototype.unselect = function() {
	if ( this.span !== null ) {
		this.span.classList.remove('selected') ;
	}
}
// Refresh cache if needed then display
Tab.prototype.display = function() {
	if ( this.nocache || ( this.players === null ) ) {
		this.container.table.classList.add('loading');
		var tab = this ; // Closure
		$.getJSON('ranking/'+this.name+'.json', null, function(content) {
			var players = [] ;
			for ( var i in content ) {
				players.push(new Player(i, content[i])) ;
			}
			tab.players = players ;
			tab.display_recieve() ;
			tab.container.table.classList.remove('loading');
		}) ;
	} else {
		this.display_recieve() ;
	}
}
// Displays player list
Tab.prototype.display_recieve = function() {
	this.sort() ;
	node_empty(tabs.players) ;
	for ( var i = 0 ; i < this.players.length ; i++ )
		this.players[i].display(this.container.players) ;
}
// Sorts players according to master
Tab.prototype.sort = function() {
	this.players.sort(function(a, b) {
		if ( tabs.reverse )
			return a[tabs.sortField] -  b[tabs.sortField] ;
		else
			return b[tabs.sortField] -  a[tabs.sortField] ;
	}) ;
}
// ========== Player ==========
function Player(id, data) {
	this.player_id = id ;
	this.alias = data.alias || [];
	this.nick = data.nick ;
	this.avatar = data.avatar ;
	this.matches = data.matches ;
	this.score = data.score ;
	this.ratio = this.score / this.matches ;
}
// Displays a player line
Player.prototype.display = function player(table) {
	var tr = table.insertRow() ;
	tr.insertCell().appendChild(create_text(''+table.rows.length)) ;
	tr.insertCell().appendChild(player_avatar(this.avatar)) ;
	tr.insertCell().appendChild(create_a(this.nick, 'player.php?id='+this.player_id)) ;
	tr.insertCell().appendChild(create_text(this.matches)) ;
	tr.insertCell().appendChild(create_text(this.score)) ;
	tr.insertCell().appendChild(create_text(round(this.ratio, 2))) ;
	if ( // Mark line as self
		( this.player_id == player_id ) // Client is current line's player
		|| ( this.alias.indexOf(player_id) > -1 ) // Client is an alias of current line's player
	) {
		tr.classList.add('self') ;
	}
}
