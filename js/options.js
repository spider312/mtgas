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
			$.post(url+'/json/profile_udate.php', {'json': JSON.stringify(json)}, function(data) {
				if ( iss(data.msg) && ( data.msg != '' ) )
					alert(data.msg) ;
				else if ( ( data.affected != 0 ) && ( data.affected != 1 ) ) {
					alert('Something went wrong : '+data.affected) ;
					$.cookie('login', null) ;
				} else if ( option.label != null )
					option.label.classList.remove('updating') ;
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
	this.render = function() {
		var myoption = this ; // for triggers
		if ( this.choices == null ) {
			if ( isb(this.def) ) {
				this.input = create_checkbox(name, this.get(), name) ;
				this.input.addEventListener('click', function (ev) {
					ev.target.option.set(ev.target.checked) ;
					if ( isf(ev.target.option.onChange) )
						ev.target.option.onChange(myoption) ;
				}, false) ;
			} else {
				this.input = create_input(name, this.get(), name) ;
				this.input.addEventListener('change', function (ev) {
					ev.target.option.set(ev.target.value) ;
					if ( isf(ev.target.option.onChange) )
						ev.target.option.onChange(myoption) ;
				}, false) ;
			}
		} else {
			this.input = create_select(name, name) ;
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
		}
		this.input.id = this.name ;
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
	// Methods
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
	this.container = function(title, onsubmit) {
		this.onsubmit = onsubmit ;
		var myoptions = this ; // Local name
		var container = create_form() ;
		container.addEventListener('submit', function(ev) {
			ev.preventDefault() ;
			if ( isf(onsubmit) && ! onsubmit(myoptions) ) // Trigger
				return false ; // No hide if trigger returns false
			myoptions.hide() ;
		}, false) ;
		container.id = 'choicewin' ;
		container.classList.add('section') ;
		container.appendChild(create_h(1, title)) ;
		var btnimg = create_img(theme_image('deckbuilder/button_ok.png')[0]) ;
		myoptions = this ;
		btnimg.addEventListener('load', function (ev) {
			myoptions.resize(container) ;
		}, false) ;
		var button = create_button(btnimg, 'Close', 'Close') ;
		button.id = 'options_close' ;
		container.appendChild(button) ;
		var result = create_div(container) ;
		result.id = 'options' ;
		result.classList.add('hider') ;
		document.body.appendChild(result) ;
		return container ;
	}
	this.resize = function(container, width) {
		var width = 400 ;
		var style = container.style ;
		style.width = width+'px' ;
		style.marginLeft = '-'+Math.ceil(width/2)+'px' ;
		var height = 10 ; // Margin around H (not sure)
		for ( var i = 0 ; i < container.childNodes.length ; i++ ) {
			var el = container.childNodes[i] ;
			if ( el.id == 'buttons' )
				continue ;
			var bcr = el.getBoundingClientRect() ;
			var cs = window.getComputedStyle(el)
			var add =bcr.height + parseInt(cs.marginTop) + parseInt(cs.marginBottom);
			height += add ;
		}
		style.height = height+'px' ;
		style.marginTop = '-'+Math.ceil(height/2)+'px' ;
	}
	this.hide = function(win) {
		if ( !iso(win) )
			win = document.getElementById('options') ;
		if ( win != null )
			document.body.removeChild(win) ;
	}
			// Options
	this.show = function() {
		var container = this.container('Options') ;
		for ( var i in {'Appearence': true, 'Behaviour': true, 'Debug': true} ) {
			var group = this.groups[i] ;
			var fieldset = create_fieldset(i) ;
			for ( var j in group )
				fieldset.appendChild(group[j].render()) ;
			container.appendChild(fieldset) ;
		}
		this.add_buttons(container, 'options') ;
		this.resize(container) ;
	}
			// Identity
	this.identity_show = function() {
		var container = this.container('Identity', function(options) {
			var nickfield = document.getElementById('profile_nick') ;
			if ( nickfield == null )
				return false ;
			if (  ( nickfield.value != '' ) && ( nickfield.value != 'Nickname' ) ) {
				nickfield.classList.remove('errored') ;
				options.identity_apply() ;
				return true ;
			} else {
				nickfield.classList.add('errored') ;
				nickfield.focus() ;
				nickfield.select() ;
				return false ;
			}
		}, this) ;
		var fieldset = create_div() ;
		// Nick
		var nick = this.options['profile_nick'].render() ;
		fieldset.appendChild(nick) ;
		nick = nick.childNodes[1] ;
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
		var myoptions = this ;
		avatar_demo.addEventListener('load', function (ev) {
			if ( last_working_avatar != ev.target.src  ) {
				ev.target.classList.remove('errored') ;
				last_working_avatar = ev.target.src ; // Backup as last working avatar, for future errors
			}
			myoptions.resize(container) ;
		}, false) ;
		avatar_demo.addEventListener('error', function (ev) {
			ev.target.classList.add('errored') ;
			if ( ev.target.src == last_working_avatar ) {
				myoptions.resize(container) ; // Last working
				alert('err') ;
			} else
				ev.target.src = last_working_avatar ;
		}, false) ;
		avatar.appendChild(create_a(avatar_demo, 'javascript:gallery()', null, 'Choose an avatar from a gallery')) ;
		container.appendChild(fieldset) ;
		nick.select() ;
		this.resize(container) ; // Done on image error/load
		// Buttons
		this.add_buttons(container, 'identity') ;
	}
		// Profile
	this.profile_show = function() {
		var container = this.container('Profile') ;
		// Local
		var fieldset = create_fieldset('Local') ;
			// Backup
		var clone = {} ;
		for ( var i = 0 ; i < localStorage.length ; i++ ) {
			var key = localStorage.key(i) ;
			clone[key] = localStorage[key] ; 
		}
		var d = new Date() ;
		var form = create_form('/download_file.php', 'post', 
     		create_hidden('name', 'mtgas_profile_'+clone.profile_nick+'_'+d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'.mtgas'), 
     		create_hidden('content', JSON.stringify(clone)),
     		create_submit('', 'Backup profile')
		) ;
		form.title = 'Downloads a profile file, that can be restored on another mtgas (nick, avatars, decks, tokens ...)' ;
		fieldset.appendChild(form) ;
			// Restore
		var file = create_file('profile_file', 'Path of the profile file (in .mtgas (json) file format)') ;
		file.size = 2 ;
		var submit = create_submit('', 'Restore profile') ;
		submit.disabled = true ;
		file.addEventListener('change', function(ev) {
			if ( ev.target.files.length > 0 )
				submit.disabled = false ;
		}, false) ;
		var form = create_form('', 'get', 
			file ,
     		submit
		) ;
		form.title = 'Restore a profile file previously saved or from another mtgas' ;
		form.addEventListener('submit', function(ev) {
			ev.preventDefault() ;
			if ( ev.target.profile_file.files.length == 0 )
				alert('Please browse to the saved profile file you want to restore') ;
			else {
				var form = ev.target ; // Used in reader.onload, where ev is another event
				var reader = new FileReader() ; // https://developer.mozilla.org/en-US/docs/Using_files_from_web_applications
				reader.onload = function(ev) {
					var text = ev.target.result ;
					try {
						var profile = JSON.parse(text) ;
					} catch (e) {
						alert('The file '+file.fileName+' ('+file.type+') doesn\'t appears to be a valid MTGAS profile file (json)') ;
						return null ;
					}
					if ( confirm('Are you sure you want to overwrite all your personnal data (nick, avatar, decks, options) with ones stored in profile file with nick '+profile.profile_nick) ) {
						form.profile_file.value = '' ;
						localStorage.clear() ;
						for ( var i in profile )
							store(i, profile[i]) ;
						alert('All data were overwritten, reloading page') ;
						document.location = document.location ;
					}

				} ;
				reader.readAsText(form.profile_file.files.item(0)) ;
			}
		}, false) ;
		fieldset.appendChild(form) ;
			// Clear
		var button = create_button('Clear profile', function(ev) {
			if ( confirm('Are you sure you want to clear all your personnal data on this website ? (nick, avatar, decks, tokens, games, tournaments)') ) {
				localStorage.clear() ;
				$.cookie(session_id, null);
				alert('All data were cleared, reloading page') ;
				document.location = document.location ;
			}
		}, 'Erase all mtgas-related informations from your browser') ;
		fieldset.appendChild(button) ;
		container.appendChild(fieldset) ;
		// Server side
		var myoptions = this ;
		if ( $.cookie && ( $.cookie('login') != null ) )  {
			var fieldset = create_fieldset(
				'Logged in as '+$.cookie('login'),
				create_button('Logout', function(ev) {
					$.getJSON('json/logout.php', {}, function(data) {
						if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
							alert(data.msg) ;
						//document.location = document.location ;
						myoptions.hide() ;
						myoptions.profile_show() ;
						decks_list() ;
					}) ;
					return eventStop(ev) ;
				}, 'Logout')
			) ;
		} else {
			var fieldset = create_fieldset('Login') ;
			var email = create_input('email', '', 'email') ;
			var password = create_password('password', '', 'password') ;
			var overwrite = create_checkbox('overwrite', false, 'overwrite') ;
			var overwrite_l = create_label(overwrite, 'Overwrite : ', overwrite) ;
			overwrite_l.title = 'Overwrite your server profile with your local profile instead of fetching your server profile' ;
			var form = create_form('json/login.php', 'get',
				create_label(email, 'E-mail : ', email), 
				create_label(password, 'Password : ', password),
				overwrite_l,
		 		create_submit('', 'Login/register')
			) ;
			form.addEventListener('submit', function(ev) {
				$.getJSON(ev.target.action, {
					'email': ev.target.email.value,
					'password': ev.target.password.value,
				}, function(data) {
					if ( ( typeof data.msg == 'string' ) && ( data.msg != '' ) )
						alert(data.msg) ;
					else {
						// If asked by server (profile creation) or client (overwrite checkbox), send all stored data
						if ( ( data.send ) || ev.target.overwrite.checked ) {
							$.post('json/profile_udate.php', {'json': JSON.stringify(localStorage)}, function(d) {
								//document.location = document.location ; // Refresh login form
								myoptions.hide() ;
								myoptions.profile_show() ;
								decks_list() ;
							}, 'json') ;
						// If server sent data, store them
						} else if ( ( ! ev.target.overwrite.checked ) && ( typeof data.recieve == 'string' ) && ( data.recieve != '' ) ) {
							var profile = JSON.parse(data.recieve) ;
							localStorage.clear() ;
							for ( var i in profile )
								localStorage[i] = profile[i] ; // As we're DLing data, don't store() them, it would upload back
							//document.location = document.location ; // Refresh all data
							myoptions.hide() ;
							myoptions.profile_show() ;
							decks_list() ;
						} else
							alert('Nothing happend') ;
					}
				}) ;
				return eventStop(ev) ;
			}, false) ;
			fieldset.appendChild(form) ;
/*<div>Please be sure <a href="http://forum.mogg.fr/viewtopic.php?pid=65#p65">you really need it</a> before create a server side profile (and you probably don't if you always connect here from the same computer)</div>*/
		}
		// End
		container.appendChild(fieldset) ;
		this.resize(container) ;
		this.add_buttons(container, 'profile') ;
	}
	this.add_buttons = function(container, disabled) {
		var buttons = create_div() ;
		buttons.id = 'buttons' ;
		this.button_identity.disabled = (disabled == 'identity') ;
		this.button_options.disabled = (disabled == 'options') ;
		this.button_profile.disabled = (disabled == 'profile') ;
		buttons.appendChild(this.button_identity) ;
		buttons.appendChild(this.button_options) ;
		buttons.appendChild(this.button_profile) ;
		container.appendChild(buttons) ;
	}
	// Buttons cache
	var myoptions = this ;
	this.button_identity = create_button('Identity', function(ev) { myoptions.identity_show()  }, 'Manage your nickname and avatar') ;
	this.button_options = create_button('Options', function(ev) { // Verify nickname before leaving 'identity' window
		if ( isf(myoptions.onsubmit) && ! myoptions.onsubmit(myoptions) )
			return false ; // No hide if trigger returns false
		myoptions.show() ;
	}, 'Change various options')
	this.button_profile = create_button('Profile', function(ev) { // Verify nickname before leaving 'identity' window
		if ( isf(myoptions.onsubmit) && ! myoptions.onsubmit(myoptions) )
			return false ; // No hide if trigger returns false
		myoptions.profile_show() ;
	}, 'Manage local and server-side profiles')
	// Hook
	this.identity_apply = function() {
		// Find hook
		var is = document.getElementById('identity_shower') ;
		if ( is == null )
			return false ;
		// Replace its content
		node_empty(is) ;
		var img = create_img(localize_image(this.get('profile_avatar'))) ;
		is.appendChild(img) ;
		is.appendChild(create_text(this.get('profile_nick'))) ;
		// Check nickname validity or open identity window
		var nick = this.get('profile_nick')
		if ( ( nick == null ) || ( nick == '' ) || ( nick == 'Nickname' ) )
			this.identity_show() ;
	}
	// Data
		// Identity
	this.add('Identity', 'profile_nick', 'Nickname', 'Will appear near your life counter/avatar and in all messages displayed in chatbox', 'Nickname') ; 
	this.add('Identity', 'profile_avatar', 'Avatar', 'Image displayed near your life counter. Can be any image hosted anywhere on the web, or simply chosen in a local gallery', 'img/avatar/kuser.png') ;
		// Hidden (Only retrieved, or set by other means)
		//player_id
	this.add('Hidden', 'autotext', '', '', 'Ok\nOk?\nWait!\nKeep\nThinking\nEnd my turn\nEOT') ;
	this.add('Hidden', 'deck', '', '', '') ;
		// Tournament hidden
	this.add('Tournament', 'draft_boosters', '', '', 'CUB*3') ;
	this.add('Tournament', 'sealed_boosters', '', '', 'CUB*6') ;
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
			// Behaviour
	var positions = {'top':'Top', 'middle':'Middle', 'bottom':'Bottom'} // Positions for placing
	this.add('Behaviour', 'library_doubleclick_action', 'Library double-click action', 'Choose what happend when you doubleclick on library', 'look_top_n', {'look_top_n': 'Look top N cards', 'edit': 'Search in library', 'draw': 'Draw a card'}) ;
	this.add('Behaviour', 'auto_draw', 'Auto draw', 'Draw your starting hand after toss and sides', true) ;
	this.add('Behaviour', 'sounds', 'Sound', 'Play sounds on events', true) ;
	this.add('Behaviour', 'remind_triggers', 'Remind triggers', 'Display a message when a triggered ability may be triggered. Beware, not every trigger is managed, and most of them just display a message', true) ;
	this.add('Behaviour', 'place_creatures', 'Place creature', 'Where to place creature cards by default (when double clicked) on battlefield', 'middle', positions) ; 
	this.add('Behaviour', 'place_noncreatures', 'Place non-creature', 'Where to place non-creature cards by default (when double clicked) on battlefield', 'top', positions) ; 
	this.add('Behaviour', 'place_lands', 'Place land', 'Where to place land cards by default (when double clicked) on battlefield', 'bottom', positions) ;
	this.add('Behaviour', 'draft_auto_ready', 'Auto-mark as ready after picking', 'You will automatically be marked as ready after picking a card', true) ;
	this.add('Behaviour', 'check_preload_image', 'Preload images', 'Every card image will be preloaded at the begining of the game instead of waiting its first display', true) ;
			// Debug
	this.add('Debug', 'debug', 'Debug mode', 'Logs message (non blocking errors, debug informations) will be displayed as chat messages instead of being sent to a hidden console (Ctrl+L), and debug options are added to menus', false) ;
	// Init
	if ( check_id ) // Object was created with checking
		this.identity_apply() ;
	var is = document.getElementById('identity_shower')
	if ( is != null )
		is.addEventListener('click', function(ev) {
			options.identity_show() ;
		}, false) ;
}
function gallery() {
	window.open('/avatars.php') ;
}
// Code copied from index.js, TODO : include it in new management

$(function() { // On page load
	// Synchronize PHPSESSID cookie with stored player ID (in case player ID comes from profile importing)
	player_id = $.cookie(session_id) ;
	if ( ( localStorage['player_id'] == null ) || ( localStorage['player_id'] == '' ) ) // No player ID
		store('player_id', player_id) ; // Store actual PHPSESSID
	else if ( player_id != localStorage['player_id'] ) { // Player ID different from PHPSESSID
		$.cookie(session_id, localStorage['player_id']) ; // Overwrite PHPSESSID with Player ID
		window.location = window.location ; // Curent web page isn't informed ID changed, reload
	}
}) ;
// Other saved fields
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
		$.post(url+'/json/profile_udate.php', {'json': JSON.stringify(json)}, function(data) {
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
