// events.js : Keyboard events management
// Data
deadKeys = [ // Keys which default action should be canceled
	'control_d', 'control_i', 'control_n', 'control_o', 'control_p', 'control_s', 'control_u' // Basics
	, 'f9', 'f10', 'f11', 'f12', 'alt_a', 'alt_d'
	, 'control_+', 'control_-' // Zoom
	, 'control_pageup', 'control_pagedown' // Tabs navigation
//	, '+', '-' // Opera zoom
] ; // ctrl+n can't be canceled in chromium
keyActions = { // Key <=> action association
	// Delegated modifiers
	'+' : change_powthou(1),
	'=' : change_powthou(1),
	'-' : change_powthou(-1),
	'*' : change_powthou(1, true),
	'/' : change_powthou(-1, true),
	// Without modifiers
	'f3' : function(ev) { card_list_edit(game.player.library) ; },
	'f9' : function(ev) { game.opponent.life.changelife(-1) ; draw() ; },
	'f10' : function(ev) { game.opponent.life.changelife(1) ; draw() ; },
	'f11' : function(ev) { game.player.life.changelife(-1) ; draw() ; },
	'f12' : function(ev) { game.player.life.changelife(1) ; draw() ; },
	'up' : function(ev) {
		if ( chat_pointer == null )
			chat_pending = sendbox.value ;
		if ( ( chat_pointer == null ) || ( chat_pointer > chat_messages.length ) )
			chat_pointer = chat_messages.length ;
		if ( chat_pointer > 0 ) {
			chat_pointer-- ;
			sendbox.value = chat_messages[chat_pointer] ;
		} else
			sendbox.value = '' ;
		sendbox.focus() ;
	},
	'down' : function(ev) {
		if ( chat_pointer != null ) { // Only if already called with up key
			chat_pointer++
			if ( chat_pointer >= chat_messages.length ) {
				sendbox.value = chat_pending ;
				chat_pointer = null ;
				chat_pending = '' ;
			}else
				sendbox.value = chat_messages[chat_pointer] ;
		} else
			sendbox.value = '' ;
		sendbox.focus() ;
	},
	// With modifiers
	'control_ ' : function(ev) { game.turn.incstep() ; draw() ; },
	'control_shift_ ' : function(ev) { game.turn.trigger_step() ; draw() ; },
	'control_d' : function(ev) { game.player.hand.draw_card() ; },
	'control_i' : rolldice,
	'control_k' : function(ev) { alert(game.image_cache.info()) ; },
	'control_l' : function(ev) { log_clear() ; },
	'control_shift_l' : function(ev) { 
		if ( iso(logtext) )
			while ( logtext.length > 0 )
				logtext.pop() ;
	},
	'control_m' : function(ev) { game.player.hand.mulligan() ; },
	'control_n' : function(ev) {
		var cards = game.selected.get_cards() ;
		for ( var i in cards )
			cards[i].setnote() ;
	},
	'control_o' : function(ev) {
		var cards = game.selected.get_cards() ;
		for ( var i in cards )
			cards[i].setcounter() ;
	},
	'control_p' : function(ev) {
		var cards = game.selected.get_cards() ;
		for ( var i in cards )
			cards[i].ask_powthou() ;
	},
	'control_r' : function(ev) { resize_window(ev) ; },
	'control_s' : function(ev) { game.player.library.shuffle() ; },
	'control_u' : function(ev) { game.player.battlefield.untapall() ; },
	'control_z' : function(ev) { game.player.hand.undo() ; },
	'control_backspace' : function(ev) { game.turn.decstep() ; },
	'control_enter' : function(ev) { game.turn.setstep(12) ; },
	'control_del' : function(ev) { game.selected.changezone(game.player.graveyard) ; draw() ; },
	'control_up' : move_bf(0, -1),
	'control_down' : move_bf(0, 1),
	'control_left' : move_bf(-1, 0),
	'control_right' : move_bf(1, 0),
	'control_alt_pageup' : change_powthou(1, false, true),
	'control_alt_pagedown' : change_powthou(-1, false, true),
	'alt_pageup' : function(ev) { game.selected.add_counter(1) ; draw() ; },
	'alt_pagedown' : function(ev) { game.selected.add_counter(-1) ; draw() ; },
	'alt_a' : function(ev) { game.player.battlefield.selectall() ; draw() ; },
	'alt_d' : function(ev) {
		var cards = game.selected.get_cards() ;
		for ( var i in cards )
			cards[i].duplicate() ;
	}
}
// Main lib
function events() { // Handlers for events for all connexion type (player, spectator)
	window.addEventListener('focus',	onFocus,	false) ;
}
function player_events() {
	window.addEventListener('beforeunload',	onBeforeUnload,	false) ; // Confirm page closure
	window.addEventListener('keydown', onKeyDown, false) ;
	window.addEventListener('keyup', onKeyUp, false) ;
	//window.addEventListener('keypress', onKeyPress, false) ;
}
// Event methods
function onFocus(ev) { // On focus, clean window title
	unseen_actions = 0 ;
	document.title = init_title ;
}
function onBeforeUnload(ev) { // Confirm closure
	var text = 'Sure you want to quit this page ?'
	ev.returnValue = text ;
	return text ;
}
function onKeyDown(ev) { // Key Down : cancel dead keys
	var key = eventKey(ev) ;
	var mkey = eventMKey(ev) ;
	ctrlWhileDND(ev, key) ;
	if ( inarray(mkey, deadKeys) )
		return eventStop(ev) ;
}
function onKeyUp(ev) { // Key Up : trigger bound keys
	var key = eventKey(ev) ;
	var mkey = eventMKey(ev) ;
	ctrlWhileDND(ev, key) ;
	if ( isf(keyActions[mkey]) ) // Key is bind as multi-keys
		keyActions[mkey](ev) ;
	else if ( isf(keyActions[key]) ) // Key is bind without modifiers, for delegated modifiers
		keyActions[key](ev) ;
	// Focus to chat if not already - even if bound for +-*/
	if (
		( ! ev.ctrlKey ) && ( ! ev.altKey ) && // Not modified
		( key.length == 1 ) && // Printable character
		( document.activeElement != sendbox ) && // Not already focused
		( document.activeElement.id != 'autotext_area' ) // Would interact with autotext
	) {
		sendbox.focus() ;
		sendbox.value += key ;
	}
}
// Lib
	// Multiple keys doing similar action modularisation
function ctrlWhileDND(ev, key) { // TODO : install own handler in DND management
	if ( // Press or release control while dragging : set pointer
		( key == 'control' )
		&& ( game.draginit != null )
		&& ( game.widget_under_mouse.type == 'battlefield' )
	)
		game.canvas.style.cursor = ev.type=='keydown'?'copy':'pointer' ;

}
function change_powthou(val, eot, counter) {
	return function(ev) {
		var pow = ev.ctrlKey ? 1 : 0 ;
		var tou = ev.altKey  ? 1 : 0 ;
		if ( pow + tou > 0 ) {
			if ( eot )
				game.selected.add_powthou_eot(pow*val, tou*val) ;
			else
				game.selected.add_powthou(pow*val, tou*val) ;
			if ( counter )
				game.selected.add_counter(val) ;
			draw() ;
		}
	}
}
function move_bf(right, down) {
	return function(ev) {
		var cards = game.selected.get_cards() ;
		if ( cards.length > 0 ) {
			// Inverted opponent BF
			var step = 1 ;
			if ( ( game.selected.zone.player != game.player ) && game.options.get('invert_bf') )
				step = -1 ;
			for ( var i in cards )
				cards[i].place(cards[i].grid_x + right, cards[i].grid_y + step * down) ;
			draw() ;
		}
	}
}
	// Data
// https://www.inkling.com/read/javascript-definitive-guide-david-flanagan-6th/chapter-17/a-keymap-class-for-keyboard
keyCodeToKeyName = {
	// Keys with words or arrows on them
	8:"Backspace", 9:"Tab", 13:"Enter", 16:"Shift", 17:"Control", 18:"Alt",
	19:"Pause", 20:"CapsLock", 27:"Esc", 32:"Spacebar", 33:"PageUp",  
	34:"PageDown", 35:"End", 36:"Home", 37:"Left", 38:"Up", 39:"Right",
	40:"Down", 45:"Insert", 46:"Del",
    // Number keys on main keyboard (not keypad)
	48:"0",49:"1",50:"2",51:"3",52:"4",53:"5",54:"6",55:"7",56:"8",57:"9",
	// Letter keys. Note that we don't distinguish upper and lower case
	65:"A", 66:"B", 67:"C", 68:"D", 69:"E", 70:"F", 71:"G", 72:"H", 73:"I",
	74:"J", 75:"K", 76:"L", 77:"M", 78:"N", 79:"O", 80:"P", 81:"Q", 82:"R",
	83:"S", 84:"T", 85:"U", 86:"V", 87:"W", 88:"X", 89:"Y", 90:"Z",
	// Keypad numbers and punctuation keys. (Opera does not support these.)
	96:"0",97:"1",98:"2",99:"3",100:"4",101:"5",102:"6",103:"7",104:"8",105:"9",
	106:"Multiply", 107:"Add", 109:"Subtract", 110:"Decimal", 111:"Divide",
	// Function keys
	112:"F1", 113:"F2", 114:"F3", 115:"F4", 116:"F5", 117:"F6",
	118:"F7", 119:"F8", 120:"F9", 121:"F10", 122:"F11", 123:"F12",
	124:"F13", 125:"F14", 126:"F15", 127:"F16", 128:"F17", 129:"F18",
	130:"F19", 131:"F20", 132:"F21", 133:"F22", 134:"F23", 135:"F24",
	// Punctuation keys that don't require holding down Shift
	// Hyphen is nonportable: FF returns same code as Subtract
	59:";", 61:"=", 186:";", 187:"=", // Firefox and Opera return 59,61 
	188:",", 189:"-", 190:".", 191:"/", 192:"`", 219:"[", 220:"\\", 221:"]", 222:"'"
} ;
KeyNameToKey = { // Webkit uses names, Gecko uses char, which is simpler
	"Multiply": "*", "Add": "+", "Subtract": "-", "Decimal" : ".", "Divide" : "/"
}
	// DOM Lib
function eventKey(ev) { // Return a cross browser/system identifier for pressed key
	var key = keyCodeToKeyName[ev.keyCode] ; // Fallback (chrome)
	if ( iss(ev.key) ) // W3C, Firefox, Opera (to correct)
		key = ev.key ;
	else if (
		iss(ev.keyIdentifier)
		&& ( ev.keyIdentifier != '' )
		&& ( ev.keyIdentifier.substring(0,2) !== "U+" )
	) // Chrome
		key = ev.keyIdentifier ;
	else if ( isn(ev.charCode) && ( ev.charCode > 0 ) )
		key = String.fromCharCode(ev.keyCode) ;
	// Map webkit names to Gecko ones
	if ( iss(KeyNameToKey[key]) )
		key = KeyNameToKey[key] ;
	key = key.toLowerCase() ;
	//log(ev.type+' '+ev.key+' '+ev.keyIdentifier+' '+ev.keyCode+' : '+key) ;
	return key ;
}
function eventMKey(ev) { // Return an identifier for pressed key + modifiers
	var key = eventKey(ev) ;
	key = ( ev.metaKey  && ( key != 'meta' )    ? 'meta_' : '' ) + key ;
	key = ( ev.shiftKey && ( key != 'shift' )   ? 'shift_' : '' ) + key ;
	key = ( ev.altKey   && ( key != 'alt' )     ? 'alt_' : '' ) + key ;
	key = ( ev.ctrlKey  && ( key != 'control' ) ? 'control_' : '' ) + key ;
	return key ;
}
