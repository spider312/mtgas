// Manage 1 option
function Option(name, desc, longdesc, def, choices, onChange) {
	// Methods
		// Accessors
	this.set = function(value) {
		if ( !isset(value) )
			value = null ;
		if ( ( this.choices != null ) && ! this.choices.hasOwnProperty(value) ) {
			alert(value+' not in choices :') ;
			log2(this.choices) ;
			return false ;
		}
		if ( value == null )
			localStorage.removeItem(this.name) ;
		else
			localStorage[this.name] = value ;
		// Logged-in : send new value
		if ( $.cookie && ( $.cookie('login') != null ) )  {
			if ( this.label != null )
				this.label.classList.add('updating') ;
			var json = {} ;
			json[this.name] = value ;
			var option = this ;
			$.post(url+'/json/account/profile_update.php', {'json': JSON.stringify(json)}, function(data) {
				if ( iss(data.msg) && ( data.msg != '' ) )
					alert(data.msg) ;
				else if ( ( data.affected != 0 ) && ( data.affected != 1 ) ) {
					alert('Something went wrong : '+data.affected) ;
					log2(data) ;
					$.cookie('login', null) ;
				} else if ( option.label != null ) {
					option.label.classList.remove('updating') ;
					option.label.classList.add('updated') ;
					window.setTimeout(function(ol) {
						ol.classList.remove('updated') ;
					}, 500, option.label) ;
				}
			}, 'json') ;
		}
	}
	this.get = function() {
		if ( localStorage.hasOwnProperty(this.name) ) {
			if ( isb(this.def) )
				return ( localStorage[this.name] == 'true' ) ; // Return boolean options as bool
			return localStorage[this.name] ;
		} else
			return null ;
	}
		// Rendering
	this.render = function() { // Returns a form element adequate for option type
		var myoption = this ; // for triggers
		// Create input depending on option value type
		if ( this.choices != null ) { // Choices : select
			this.input = create_select(name) ;
			for ( var i in choices ) {
				var txt = choices[i] ;
				var opt = create_option(txt, i) ;
				if ( i == this.get() )
					opt.selected = true ;
				this.input.add(opt) ;
			}
			this.input.addEventListener('change', function (ev) {
				ev.target.option.set(ev.target.value) ;
				if ( isf(ev.target.option.onChange) )
					ev.target.option.onChange(myoption) ;
			}, false) ;
		} else {
			if ( isb(this.def) ) {
				// Boolean: checkbox input
				this.input = create_checkbox(name, this.get()) ;
				this.input.addEventListener('click', function (ev) {
					ev.target.option.set(ev.target.checked) ;
					if ( isf(ev.target.option.onChange) )
						ev.target.option.onChange(myoption) ;
				}, false) ;
			} else {

				this.input = create_input(name, this.get()) ;
				this.input.placeholder = desc ;
				this.input.addEventListener('change', function (ev) {
					ev.target.option.set(ev.target.value) ;
					if ( isf(ev.target.option.onChange) )
						ev.target.option.onChange(myoption) ;
				}, false) ;
				this.input.addEventListener('keypress', function(ev) {
					ev.stopPropagation() ; // Overrides play.js keypress interception
				}, false) ;
			}
		}
		this.input.id = 'option_'+this.name ;
		this.input.option = this ;
		if ( ( this.choices != null ) || !isb(this.def) )
			this.label = create_label(this.input, this.desc+' : ', this.input) ;
		else
			this.label = create_label(this.input, this.input, this.desc) ;
		this.label.title = this.longdesc ;
		return this.label ;
	}
	// Init
	this.name = name ;
	this.desc = desc ;
	this.longdesc = longdesc ;
	this.def = def ;
	this.label = null ;
	this.input = null ;
	if ( ! iso(choices) )
		choices = null ;
	this.choices = choices ;
	if ( ! isf(onChange) )
		onChange = null ;
	this.onChange = onChange ;
	if ( this.get() == null ) {
		this.set(def) ;
		if ( isf(this.onChange) )
			this.onChange(this) ;
	}
}
// Manage all options
function Options(check_id) {
	// Init
	this.options = {} ;
	this.groups = {} ;
	// Accessors
	this.add = function(group, name, desc, longdesc, def, choices, onChange) {
		var option = new Option(name, desc, longdesc, def, choices, onChange) ;
		if ( ! iso(this.groups[group]) )
			this.groups[group] = {} ;
		this.groups[group][name] = option ;
		this.options[name] = option ;
		return option ;
	}
	this.add_trigger = function(name, trigger) {
		if ( ! this.options.hasOwnProperty(name) )
			return false ;
		this.options[name].onChange = trigger ;
		return true ;
	} 
	this.get = function(name) {
		if ( this.options.hasOwnProperty(name) )
			return this.options[name].get() ;
		else
			return null ;
	}
	this.set = function(name, value) {
		if ( this.options.hasOwnProperty(name) )
			return this.options[name].set(value) ;
		else
			return null ;
	}
	// Rendering
		// Lib
	this.hide = function(win) {
		if ( !iso(win) )
			win = document.getElementById('options_hider') ;
		if ( win != null )
			document.body.removeChild(win) ;
	}
	this.close = function() { // Checks if everything OK then hide option window
		// Nick checking
		var nick = this.get('profile_nick') ;
		if ( nick == '' ) {
			var nickfield = document.getElementById('profile_nick') ;
			if ( nickfield == null ) { // Options opened on another tab
				this.select_tab('Identity') ;
				nickfield = document.getElementById('profile_nick') ;
			}
			nickfield.classList.add('errored') ;
			nickfield.select() ;
			return false ;
		}
		this.identity_apply() ;
		this.hide() ;
	}
	this.show = function(tab) {
		// Container
		var container = create_div() ;
		container.id = 'options' ;
		container.classList.add('section') ;
		// Hider
		var hider = create_div(container) ;
		hider.id = 'options_hider' ;
		hider.classList.add('hider') ;
		document.body.appendChild(hider) ; // Needed by this.select_tab, have to do it early
		// Tabs
		var ul = create_ul('options_tabs') ;
		for ( var i in this.tabs ) {
			var li = create_li(i) ;
			li.addEventListener('click', function(ev) {
				var tab = ev.target.textContent ;
				window.game.options.select_tab(tab) ;
			}, false) ;
			ul.appendChild(li) ;
		}
		container.appendChild(ul) ;
		// Center tabs in window = center in screen, should redo on resize() but only usefull if ul height changes ...
		ul.style.marginTop = '-'+(Math.ceil(ul.offsetHeight/2)+5)+'px' ;
		// Content
		var content = create_div() ;
		content.id = 'options_content' ;
		container.appendChild(content) ;
		this.select_tab(tab) ;
		// Close button
		var btnimg = create_img(theme_image('deckbuilder/button_ok.png')[0]) ;
		btnimg.addEventListener('load', function (ev) {
			game.options.resize() ;
		}, false) ;
		var button = create_button(btnimg, function(ev) {
			game.options.close() ;
		}, 'Close') ;
		button.id = 'options_close' ;
		container.appendChild(button) ;
		this.resize() ;
	}
	this.resize = function() { // Set option window size to match its content
		var container = document.getElementById('options') ;
		var content = document.getElementById('options_content') ;
		if ( ( container == null ) || ( content == null ) ) {
			alert('Trying to resize an unexisting option window') ;
			return false ;
		}
		var width = 400 ;
		var style = container.style ;
		style.width = width+'px' ;
		style.marginLeft = '-'+Math.ceil(width/2)+'px' ;
		var height = 0 ; // Sum heights of all elements in content
		for ( var i = 0 ; i < content.childNodes.length ; i++ ) {
			var el = content.childNodes[i] ;
			if ( el.id == 'buttons' )
				continue ;
			var bcr = el.getBoundingClientRect() ;
			var cs = window.getComputedStyle(el)
			var add = bcr.height + parseInt(cs.marginTop) + parseInt(cs.marginBottom);
			height += add ;
		}
		height = max(190, height) ; // At least N px height in order to correctly display tabs
		style.height = height+'px' ;
		style.marginTop = '-'+Math.ceil(height/2)+'px' ;
	}

	this.select_tab = function(tab) {
		if ( ! iss(tab) || ! this.tabs[tab] )
			tab = 'Options' ;
		var content = document.getElementById('options_content') ;
		if ( content == null ) {
			alert('options not opened ('+tab+')') ;
			return false ;
		}
		node_empty(content) ;
		content.appendChild(create_h(1, tab)) ;
		this.tabs[tab].call(this, content) ;
		// Refresh tabs (unselected/selected)
		var lis = document.getElementById('options').firstElementChild.children ;
		for ( var i = 0 ; i < lis.length ; i++ )
			if ( lis[i].textContent == tab )
				lis[i].classList.add('option_selected') ;
			else
				lis[i].classList.remove('option_selected') ;
		// Resize option window
		this.resize() ;
	}
		// Identity
	this.tab_identity = function(container) {
		var fieldset = create_fieldset('Identity') ;
		container.appendChild(fieldset) ;
		// Nick
		var nick = this.options['profile_nick'].render() ;
		var inick = nick.childNodes[1] ; // Input inside renderer
		nick.childNodes[1].focus() ;
		nick = create_form('', '', nick) ;
		nick.addEventListener('submit', function(ev) {
			game.options.close() ;
			return eventStop(ev) ;
		}, false) ;
		fieldset.appendChild(nick) ;
		inick.select() ;
		// Avatar
			// Input + link to demo
		var avatar = this.options['profile_avatar'].render() ;
		avatar.childNodes[1].addEventListener('change', function (ev) {
			document.getElementById('avatar_demo').src = ev.target.value ;
		}, false) ;
		fieldset.appendChild(avatar) ;
			// Demo
		var last_working_avatar = '' ;
		var txt = 'Current avatar, click to choose one from a gallery' ;
		var avatar_demo = create_img(localize_image(this.get('profile_avatar')), txt, txt) ;
		avatar_demo.id = 'avatar_demo' ;
		avatar_demo.addEventListener('load', function (ev) {
			if ( last_working_avatar != ev.target.src  ) {
				ev.target.classList.remove('errored') ;
				last_working_avatar = ev.target.src ; // Backup as last working avatar, for future errors
			}
			game.options.resize() ;
		}, false) ;
		avatar_demo.addEventListener('error', function (ev) {
			ev.target.classList.add('errored') ;
			if ( ev.target.src == last_working_avatar ) {
				game.options.resize() ; // Last working
				alert('err') ;
			} else
				ev.target.src = last_working_avatar ;
		}, false) ;
		avatar.appendChild(create_a(avatar_demo, 'javascript:gallery()', null, 'Choose an avatar from a gallery')) ;
		fieldset.appendChild(create_a('Unexpectedly reset and using CCleaner ?',
			'http://img.mogg.fr/scrot/ccleaner.png', null, 
			'Screenshot showing how to configure CCleaner to never erase mogg data')) ;
	}
		// Options
	this.tab_options = function(container) { // Base options, render all fields grouped inside fieldset
		for ( var i in {'Appearence': true, 'Behaviour': true, 'Debug': true} ) {
			var group = this.groups[i] ;
			var fieldset = create_fieldset(i) ;
			for ( var j in group )
				fieldset.appendChild(group[j].render()) ;
			container.appendChild(fieldset) ;
		}
	}
		// Spectators
	this.tab_spectators = function(container) {
		var fieldset = create_fieldset('Allowed forever') ;
		if ( spectactor_allowed_forever().length == 0 )
			fieldset.appendChild(create_text('No spectators allowed forever')) ;
		else
			fieldset.appendChild(spectator_select()) ;
		container.appendChild(fieldset) ;
	}
		// Profile
	this.tab_profile = function(container) {
		// Server side
		/*
		if ( $.cookie && ( $.cookie('login') != null ) )  // Already logged-in
			// Log out button
			container.appendChild(
				create_fieldset(
					'Logged in as '+$.cookie('login'),
					create_button('Logout',profile_logout, 'Logout')
				)
			) ;
		else { // Not logged in
			// Login form
			var fieldset = profile_form('Login', 'json/account/login.php', function(data, ev) {
				// If server sent data, store them
				if ( ( typeof data.recieve == 'string' ) && ( data.recieve != '' ) )
					profile_downloaded(JSON_parse(data.recieve)) ;
				else
					alert('Your online profile was empty') ;
			}) ;
			container.appendChild(fieldset) ;
			// Register form
			var fieldset = profile_form('Register', 'json/account/register.php', function(data, ev) {
				alert('Account '+ev.target.email.value+' created, an e-mail has been sent to this adress, you can simply trash it.') ;
				profile_upload() ;
			}) ;
			container.appendChild(fieldset) ;
		}*/
		// Local
		var fieldset = create_fieldset('Local') ;
			// Backup
		fieldset.appendChild(create_button('Backup', profile_backup, 'Download a profile file, that can be restored on another mtgas (nick, avatar, decks, options)')) ;
			// Restore
		var file = create_file('profile_file', 'Restore a profile file previously saved') ;
		file.addEventListener('change', function(ev) {
			game.options.resize() ;
			if ( ev.target.files.length > 0 ) {
				var reader = new FileReader() ; // https://developer.mozilla.org/en-US/docs/Using_files_from_web_applications
				reader.addEventListener('load', function(ev) {
					profile_restore(JSON_parse(ev.target.result)) ;
				}, false) ;
				reader.readAsText(ev.target.files.item(0)) ;
			}
		}, false) ;
		fieldset.appendChild(create_element('div', create_text('Restore : '), file)) ;
			// Clear
		var button = create_button('Clear', profile_clear, 'Erase all mtgas-related informations from your browser') ;
		fieldset.appendChild(button) ;
		container.appendChild(fieldset) ;
	}
	// Tabs definition
	this.tabs = {
		'Identity': this.tab_identity,
		'Options': this.tab_options,
		'Spectators': this.tab_spectators,
		'Profile': this.tab_profile
	} ;
	// Hook
	this.identity_apply = function() {
		// Find hook
		var is = document.getElementById('identity_shower') ;
		if ( is == null )
			return false ;
		// Replace its content
		node_empty(is) ;
		/*
		if ( $.cookie && ( $.cookie('login') != null ) ) {
			var img = create_img(theme_image('greenled.png')[0]) ;
			img.title = 'Logged in as '+$.cookie('login')
		} else {
			var img = create_img(theme_image('redled.png')[0]) ;
			img.title = 'Logged out from server-side profile' ;
		}
		is.appendChild(img) ;
		*/
		var img = create_img(localize_image(this.get('profile_avatar'))) ;
		img.id = 'avatar' ;
		is.appendChild(img) ;
		is.appendChild(create_text(this.get('profile_nick'))) ;
		// Check nickname validity or open identity window
		var nick = this.get('profile_nick')
		if ( ( nick == null ) || ( nick == '' ) )
			this.show('Identity') ;
	}
	// Data
		// Identity
	this.add('Identity', 'profile_nick', 'Nickname', 'A nickname identifying you in game interface and chat', '') ; 
	this.add('Identity', 'profile_avatar', 'Avatar', 'Image displayed near your life counter. Can be any image hosted anywhere on the web, or simply chosen in a local gallery', default_avatar) ;
		// Options
			// Appearence
//function save_restore_options() { // Previous options management had a way for user to define card images URL (not choosing in a list) :
//	save_restore('cardimages', function(field) {$.cookie('cardimages', field.value) ; }) ; // Write value in cookies in order PHP to get it
//	var cardimages = document.getElementById('cardimages') ;
//	var cardimages_choice = document.getElementById('cardimages_choice') ;
//	cardimages_choice.addEventListener('change', function(ev) {
//		cardimages.value = ev.target.value ;
//		save(cardimages) ;
//		$.cookie('cardimages', ev.target.value) ;
//	}, false) ;
//}
	this.add('Appearence', 'lang',			'Language',			'Language used for every message printed on this site, does not include card images',			applang, applangs, function(option) {
		$.cookie(option.name, option.get()) ;
	}) ;
	this.add('Appearence', 'cardimages',		'Card images',			'A theme of card images',										cardimages_default_lang, cardimages_choice) ;
	this.add('Appearence', 'invert_bf',		'Invert opponent\'s cards',	'Display card upside-down when in an opponent\'s zone, looking more like real MTG playing',		false) ;
	this.add('Appearence', 'display_card_names',	'Card names / mana costs',	'Display card names on top of picture for cards on battlefield, and their costs for cards in hand',	true) ;
	this.add('Appearence', 'transparency',		'Transparency',			'Activate transparency, nicer but slower',								true) ;
	this.add('Appearence', 'helpers',		'Helpers',			'Display right click\'s drag\'n\'drop helper',								true) ;
	this.add('Appearence', 'smallres', 'Small resolution', 'Display card images in small size (builder)', false)
			// Behaviour
	var positions = {'top':'Top', 'middle':'Middle', 'bottom':'Bottom'} // Positions for placing
	this.add('Behaviour', 'library_doubleclick_action', 'Library double-click', 'Choose what happend when you doubleclick on library', 'look_top_n', {'look_top_n': 'Look top N cards', 'edit': 'Search in library', 'draw': 'Draw a card'}) ;
	this.add('Behaviour', 'auto_draw', 'Auto draw', 'Draw your starting hand after toss and sides', true) ;
	this.add('Behaviour', 'sounds', 'Sound', 'Play sounds on events', true) ;
	this.add('Behaviour', 'remind_triggers', 'Remind triggers', 'Display a message when a triggered ability may be triggered. Beware, not every trigger is managed, and most of them just display a message', true) ;
	this.add('Behaviour', 'place_creatures', 'Place creature', 'Where to place creature cards by default (when double clicked) on battlefield', 'middle', positions) ; 
	this.add('Behaviour', 'place_noncreatures', 'Place non-creature', 'Where to place non-creature cards by default (when double clicked) on battlefield', 'top', positions) ; 
	this.add('Behaviour', 'place_lands', 'Place land', 'Where to place land cards by default (when double clicked) on battlefield', 'bottom', positions) ;
	//this.add('Behaviour', 'draft_auto_ready', 'Auto-mark as ready after picking', 'You will automatically be marked as ready after picking a card. If unchecked, you\'ll have to double click the card you want or check the "ready" box to mark as ready.', true) ;
	this.add('Behaviour', 'check_preload_image', 'Preload images', 'Every card image will be preloaded at the begining of the game instead of waiting its first display', true) ;
			// Debug
	this.add('Debug', 'debug', 'Debug mode', 'Logs message (non blocking errors, debug informations) will be displayed as chat messages instead of being sent to a hidden console (Ctrl+L), and debug options are added to menus', false) ;
		// Hidden (Only retrieved, or set by other means)
		//player_id
	this.add('Hidden', 'autotext', '', '', 'Ok\nOk?\nWait!\nKeep\nThinking\nEnd my turn\nEOT') ;
	this.add('Hidden', 'deck', '', '', '') ;
	this.add('Hidden', 'allowed', '', '', '{}') ;
		// Tournament hidden
	this.add('Tournament', 'draft_boosters', '', '', 'CUB*3') ;
	this.add('Tournament', 'sealed_boosters', '', '', 'CUB*6') ;
	// Init
	var is = document.getElementById('identity_shower')
	if ( is != null ) {
		is.addEventListener('click', function(ev) {
			game.options.show('Identity') ;
		}, false) ;
		this.identity_apply() ;
	}
	// Synchronize PHPSESSID cookie with stored player ID
	player_id = $.cookie(session_id) ;
	if ( ( localStorage['player_id'] == null ) || ( localStorage['player_id'] == '' ) )// No player ID
		store('player_id', player_id) ; // Store actual PHPSESSID
	else if ( player_id != localStorage['player_id'] ) { // Player ID different from PHPSESSID
		if ( confirm('Restore previous player_id ? (yes if you don\'t know)') ) {
			$.cookie(session_id, localStorage['player_id']) ; // Overwrite PHPSESSID
			window.location = window.location ; // Curent web page isn't informed ID changed
		}
	}
	// Debug info
	if ( this.get('debug') ) {
		var footer = document.getElementById('footer') ;
		if ( footer != null ) {
			footer.appendChild(create_div(create_text('session id : '+player_id))) ;
			footer.appendChild(create_div(create_text('cookie login : '+$.cookie('login')))) ;
			footer.appendChild(create_div(
				create_text('cookie password : '+$.cookie('password')))) ;
		}
	}
}
function gallery() {
	window.open('/avatars.php') ;
}
// Code copied from index.js, TODO : include it in new management
// Other saved fields (not displayed in options, mostly index fields save)
function save_restore(field, onsave, onrestore) {
	var myfield = document.getElementById(field) ;
	if ( myfield != null ) {
		// Prepare save trigger
		switch ( myfield.type ) { // Depending on type
			case 'hidden': // Hidden
				break ; // No save trigger, must be triggered by code
			case 'select-one' : // Selects
			case 'text' : // Texts
				myfield.addEventListener('change', function (ev) {
					save(ev.target) ;
				}, false) ;
				break ;
			case 'checkbox' : // Checkboxes
				myfield.addEventListener('click', function (ev) {
					save(ev.target) ;
				}, false) ;
				break ;
			default :
				alert("Can't save/restore an input of type "+myfield.type) ;
		}
		// Restore
		if ( localStorage[field] == null ) // Var has never been set
			save(myfield) ;
		else { // Var has been set
			if ( ( myfield.value != localStorage[field] ) ) // And have change since then
				if ( myfield.type == 'checkbox' )
					myfield.checked = ( localStorage[field] == 'true' ) ;
				else
					myfield.value = localStorage[field] ;
		}
		if ( typeof onrestore == 'function' )
			onrestore(myfield) ;
		// In order it only triggers on user action save, not with call in this method
		if ( typeof onsave == 'function' )
			myfield.onsave = onsave ;
	}
}
function store(key, value) {
	if ( typeof value == 'undefined' )
		value = null ;
	if ( value == null )
		localStorage.removeItem(key) ;
	else
		localStorage[key] = value ;
	if ( $.cookie && ( $.cookie('login') != null ) )  { // Logged-in : send new value
		var e = document.getElementById(key) ;
		if ( e != null )
			e.parentNode.classList.add('updating') ;
		var json = {} ;
		json[key] = value ;
		$.post(url+'/json/account/profile_update.php', {'json': JSON.stringify(json)}, function(data) {
			if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
				alert(data.msg) ;
			else if ( ( data.affected != 0 ) && ( data.affected != 1 ) ) {
				alert('Something went wrong : '+data.affected) ;
				$.cookie('login', null) ;
			} else if ( e != null )
				e.parentNode.classList.remove('updating') ;
		}, 'json') ;
	}
}
function save(myfield) {
	var field = myfield.id ;
	if ( myfield.type == 'checkbox' )
		var value = myfield.checked ;
	else
		var value = myfield.value ;
	if ( value != localStorage[field] ) {
		store(field, value) ;
		if ( typeof myfield.onsave == 'function' )
			myfield.onsave(myfield) ;
		return true ;
	}
	return false ;
}
// Profile management
	// Local
function profile_backup() {
	var clone = {} ;
	for ( var i = 0 ; i < localStorage.length ; i++ ) {
		var key = localStorage.key(i) ;
		clone[key] = localStorage[key] ; 
	}
	var d = new Date() ;
	var form = create_form('/download_file.php', 'post', 
		create_hidden('name', 'mtgas_profile_'+clone.profile_nick+'_'+d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'.mtgas'), 
		create_hidden('content', JSON.stringify(clone))
	) ;
	document.body.appendChild(form) ;
	form.submit() ;
	document.body.removeChild(form) ;
}
function profile_restore(profile) {
	if ( ! iso(profile) || ( profile == null ) ) {
		alert('File is not a profile') ;
		return false ;
	}
	var nick = '[not found in profile]' ;
	if ( iss(profile.profile_nick) )
		nick = profile.profile_nick
	if ( ! confirm('Are you sure you want to overwrite all your personnal data (nick, avatar, decks, options) with ones stored in profile file with nick '+nick+' ?') ) {
		alert('Profile restoration aborted') ;
		return false ;
	}
	localStorage.clear() ;
	for ( var i in profile )
		//store(i, profile[i]) ;
		localStorage[i] = profile[i] ;
	$.cookie(session_id, localStorage['player_id']) ; // Overwrite PHPSESSID with Player ID
	game.options.identity_apply() ;
	decks_list() ;
	game.options.select_tab('Identity') ;
	//alert('All data were overwritten, reloading page') ;
	//document.location = document.location ;
}
function profile_clear() {
	if ( confirm('Are you sure you want to clear all your personnal data on this website ? (nick, avatar, decks, games, tournaments)') ) {
		localStorage.clear() ;
		$.cookie(session_id, null) ;
		alert('All data were cleared, reloading page') ;
		document.location = document.location ;
	}
}
	// Server side
function profile_upload() { // Server side profile registration || login with overwrite
	$.post('json/account/profile_update.php', {'json': JSON.stringify(localStorage)}, function(d) {
		game.options.select_tab('Profile') ;
	}, 'json') ;
}
function profile_downloaded(profile) { // Server side login without overwrite
	if ( !iso(profile) || ( profile == null ) )
		return false ;
	localStorage.clear() ;
	for ( var i in profile )
		localStorage[i] = profile[i] ; // As we're DLing data, don't store() them, it would upload back
	$.cookie(session_id, localStorage['player_id']) ; // Overwrite PHPSESSID with Player ID
	// Apply data downloaded
	decks_list() ;
	game.options.identity_apply() ;
	// Show modified
	game.options.select_tab('Identity') ;
}
function profile_logout(ev) {
	$.getJSON('json/account/logout.php', {}, function(data) {
		game.options.identity_apply() ;
		if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
			alert(data.msg) ; // Something wrong happened
		else
			game.options.select_tab('Profile') ; // Offer to re-login
	}) ;
	return eventStop(ev) ;
}
function profile_form(name, target, callback) {
	var email = create_input('email', '', 'email_'+name) ;
	var password = create_password('password', '', 'password_'+name) ;
	var remember = create_checkbox('remember', false, 'remember_'+name) ;
	var remember_l = create_label(remember, remember, 'Remember') ;
	remember_l.title = 'Reconnect automatically on next visits' ;
	var form = create_form(target, 'get',
		create_label(email, 'E-mail : ', email), 
		create_label(password, 'Password : ', password),
		remember_l, 
		create_submit('submit_button', name)
	) ;
	form.addEventListener('submit', function(ev) {
		var obj = {} ;
		for ( var i = 0 ; i < ev.target.elements.length ; i++ ) {
			var el = ev.target.elements[i] ;
			if ( el.type == 'checkbox' )
				obj[el.name] = el.checked ;
			else if ( el.type != 'submit' ) 
				obj[el.name] = el.value ;
		}
		$.getJSON(ev.target.action, obj, function(data) {
			if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
				alert(data.msg) ;
			else
				callback(data, ev) ;
			
		}) ;
		return eventStop(ev) ;
	}, false) ;
	var fieldset = create_fieldset(name, form) ;
	return fieldset ;
}
// Spectactor forever allow
function spectactor_allowed_forever() { // Returns a list of allowed players in form of {id: 'Nick', id2: 'Nick2' ... }
	var allowed_str = game.options.get('allowed') ;
	if ( !iss(allowed_str) )
		allowed_str = '' ;
	var allowed = JSON_parse(allowed_str) ;
	if ( allowed != null ) // Parsing OK
		return allowed ;
	else { // Parsing not OK : conversion utility
		if ( allowed_str == '' ) // Nobody allowed
			return {} ;
		// Somebody allowed
		allowed = allowed_str.split(',') ;
		var result = {} ;
		for ( var i = 0 ; i < allowed.length ; i++ ) {
			var id = allowed[i] ;
			result[id] = id ;
		}
		return result ;
	}
}
function spectactor_is_allowed_forever(id) {
	return iss(spectactor_allowed_forever()[id]) ;
}
function spectactor_allow_forever(id, name) {
	var allowed = spectactor_allowed_forever() ;
	allowed[id] = name ;
	game.options.set('allowed', JSON.stringify(allowed)) ;
}
function spectactor_unallow_forever(id) {
	var allowed = spectactor_allowed_forever() ;
	delete allowed[id] ;
	game.options.set('allowed', JSON.stringify(allowed)) ;
	// If while in options window (only way to do it) refresh it
	var div = document.getElementById('allowed_spectators') ;
	if ( div != null )
		div.parentNode.replaceChild(spectator_select(), div) ;
	else
		alert('Unable to find option window, shouldn\'t happen')
}
function spectator_select() { // Returns a HTMLSelect listing spectators allowed forever
	var div = create_div() ;
	div.id = 'allowed_spectators' ;
	var select = create_select() ;
	var allowed = spectactor_allowed_forever() ;
	for ( var i in allowed )
		select.add(create_option(allowed[i], i)) ;
	if ( select.options.length < 1 )
		div.appendChild(create_text('No spectator to unallow')) ;
	else {
		// Select
		select.title = 'Double click a spectator to un-allow' ;
		select.size = max(2, min(10, select.options.length)) ; // At least 2 in order to display a full list, max 10 to stay inside screen
		select.addEventListener('dblclick', function(ev) {
			if ( ev.target.localName != 'option' )
				alert('Please double click a line') ;
			else
				spectactor_unallow_forever(ev.target.value) ;
		}, false) ;
		div.appendChild(select) ;
		div.appendChild(create_element('br')) ;
		// Button
		var button = create_button('Un-allow', function(ev) {
			if ( select.selectedIndex == -1 )
				alert('Please select a spectator to un-allow') ;
			else
				spectactor_unallow_forever(select.value) ;
		}) ;
		button.type = 'button' ; // Don't send form
		div.appendChild(button) ;
	}
	return div ;
}
