function start() {
	// Link between mana colors array and mana color checks
	manacolors = ['X', 'W', 'U', 'B', 'R', 'G'] ;
	active_color = {} ;
	for ( var i in manacolors ) {
		var color = manacolors[i] ;
		var check = document.getElementById('check_c_'+color) ;
		active_color[color] = check.checked ;
		label_check(check) ;
		check.addEventListener('change', function(ev) {
			active_color[ev.target.value] = ev.target.checked ;
			label_check(ev.target) ;
			disp_side() ;
			check_all_c() ;
		}, false) ;
		check.previousElementSibling.addEventListener('dblclick', function(ev) { // Double click : select only that one
			for ( var i in manacolors ) {
				var color = manacolors[i] ;
				var check = document.getElementById('check_c_'+color) ;
				check.checked = ( check == ev.target.nextElementSibling ) ;
				active_color[check.value] = check.checked ;
				label_check(check) ;
			}
			disp_side() ;
			ev.preventDefault() ;
		}, false) ;
		check.previousElementSibling.addEventListener('contextmenu', function(ev) { // Right click : select all but that one
			//alert(ev.target) ;
			for ( var i in manacolors ) {
				var color = manacolors[i] ;
				var check = document.getElementById('check_c_'+color) ;
				check.checked = ( check != ev.target.nextElementSibling ) ;
				active_color[check.value] = check.checked ;
				label_check(check) ;
			}
			disp_side() ;
			ev.preventDefault() ;
		}, false) ;
	}
		// Link between mana colors checks and "all" check
	check_all_c() ;
	label_check(document.getElementById('check_c_all')) ;
	document.getElementById('check_c_all').addEventListener('change', function(ev) {
		label_check(ev.target) ;
		for ( var i in manacolors ) {
			var color = manacolors[i] ;
			var check = document.getElementById('check_c_'+color) ;
			check.checked = ev.target.checked ;
			label_check(check) ;
			active_color[color] = check.checked ;
		}
		disp_side() ;
	}, false) ;
		// Link between rarities array and rarity check
	rarities = ['C', 'U', 'R'] ;
	active_rarity = {} ;
	for ( var i in rarities ) {
		var rarity = rarities[i] ;
		var check = document.getElementById('check_r_'+rarity) ;
		active_rarity[rarity] = check.checked ;
		label_check(check) ;
		check.addEventListener('change', function(ev) {
			active_rarity[ev.target.value] = ev.target.checked ;
			label_check(ev.target) ;
			disp_side() ;
			check_all_r() ;
		}, false) ;
	}
		// Link between rarity checks and "all" check
	check_all_r() ;
	label_check(document.getElementById('check_r_all')) ;
	document.getElementById('check_r_all').addEventListener('change', function(ev) {
		label_check(ev.target) ;
		for ( var i in rarities ) {
			var rarity = rarities[i] ;
			var check = document.getElementById('check_r_'+rarity) ;
			check.checked = ev.target.checked ;
			label_check(check) ;
			active_rarity[rarity] = check.checked ;
		}
		disp_side(poolcards.side, pool) ;
	}, false) ;
		// Message when you unload page with a modified deck
	modified = false ;
	window.addEventListener('beforeunload', function(ev) {
		if ( modified ) {
			ev.returnValue = 'Your deck was modified since last save\nYou may want to save those modifications before leaving page' ;
			alert(ev.returnValue) ;
			return ev.returnValue ;
		}
	}, false) ; // Page closure
		// Basic lands
	node_empty(lands_div) ;
	lands = [] ;
	lands.push(new Land(6871, 'Plains')) ;
	lands.push(new Land(4621, 'Island')) ;
	lands.push(new Land(9266, 'Swamp')) ;
	lands.push(new Land(6020, 'Mountain')) ;
	lands.push(new Land(3332, 'Forest')) ;
}
/*
function color_sort(card1, card2) {
	if ( card1.attrs.color == card2.attrs.color )
		return 0 ;
	if ( card1.attrs.color > card2.attrs.color )
		return -1 ;
	return 1 ;
}
*/
function label_check(target) {
	if ( target.checked )
		target.parentNode.classList.add('checked') ;
	else
		target.parentNode.classList.remove('checked') ;
}
function check_all_c() { // If all manacolor check are checked, check the "all", idem if they are unchecked
	var do_check = true ;
	var do_uncheck = true ;
	for ( var i in manacolors )
		if ( active_color[manacolors[i]] )
			do_uncheck = false ;
		else
			do_check = false ;
	if ( do_check )
		document.getElementById('check_c_all').checked = true ;
	if ( do_uncheck )
		document.getElementById('check_c_all').checked = false ;
	if ( do_check || do_uncheck )
		label_check(document.getElementById('check_c_all')) ;
}
function check_all_r() { // Idem for rarity
	var do_check = true ;
	var do_uncheck = true ;
	for ( var i in rarities ) {
		var rarity = rarities[i] ;
		if ( active_rarity[rarity] )
			do_uncheck = false ;
		if ( ! active_rarity[rarity] )
			do_check = false ;
	}
	if ( do_check )
		document.getElementById('check_r_all').checked = true ;
	if ( do_uncheck )
		document.getElementById('check_r_all').checked = false ;
	if ( do_check || do_uncheck )
		label_check(document.getElementById('check_r_all')) ;
}
