// Create deckfile from object deck
function obj2deck(obj) {
	var deck = "// Main deck\n" ;
	deck += arr2deck(obj.main) ;
	deck += "\n// Sideboard\n" ;
	deck += arr2deck(obj.side, 'SB:') ;
	return deck ;
}
function arr2deck(arr, prefix) {
	if ( ! iss (prefix) )
		prefix = '   '
	deck = '' ;
	while ( arr.length > 0 ) {
		curval = arr.pop() //array_splice($arr, 0, 1) ; // Extract a card
		nb = 1 ;
		i = 0 ;
		while ( i < arr.length ) { // Search for other exemplaries
			value = arr[i] ;
			if ( ( value.ext == curval.ext ) && ( value.name == curval.name ) ) {
				arr.splice(i, 1) ; // Extract them
				nb++ ; // And count
			} else
				i++ ;
		}
		deck += prefix+' '+nb+' ['+curval.ext+'] '+curval.name+"\n" ;
	}
	return deck ;
}
function deck_parse(deck) {
	var reg_comment = /\/\/(.*)/ ;
	var reg_empty = /^$/ ;
	var reg_card_mwd = /(\d+)\s*\[(.*)\]\s*\b(.+)\b/ ;
	var reg_card_apr = /(\d+)\s*\b(.+)\b/ ;
	var str_side = 'SB:' ;
	if ( typeof deck == 'string' )
		var lines = deck.split('\n') ;
	else
		var lines = []
	var result = new Array() ;
	for ( var i = 0 ; i < lines.length ; i++ ) {
		var line = lines[i]
		var matches = line.match(reg_comment) ;
		if ( matches != null ) {
			result.push(matches[1]) ;
		} else if ( line.match(reg_empty) != null ) {
		} else {
			var nb = 0 ;
			var ext = '' ;
			var name = '' ;
			var num = undefined ; // SpideR 2011-01-25 : 0 -> undefined, for deckbuilder to compare parsed lines with existing ones
			// Check if line is a sideboard line
			var sbidx = line.indexOf(str_side) ;
			var side = ( sbidx == 0 ) ;
			if ( side )
				line = line.substr(sbidx+str_side.length) ;
					matches = line.match(reg_card_mwd) ;
			if ( matches != null ) {
				nb = parseInt(matches[1]) ;
				ext = matches[2] ;
				name = matches[3] ;
			} else {
				matches = line.match(reg_card_apr) ;
				if ( matches != null ) {
					nb = parseInt(matches[1]) ;
					name = matches[2] ;
				} else {
					if ( ( line != '\n' ) && ( line != '\r' ) )
						alert('Unparsable line : ['+line+']'+line.charCodeAt(0)) ;
					continue ;
				}
			}
			var tmp = get_img_nb(name) ;
			name = tmp[0] ;
			if ( tmp.length > 1 )
				num = tmp[1] ;
			result.push(new Array(nb, ext, name, num, side)) ;
		}
	}
	return result ;
}
function get_img_nb(name) {
	var matches = name.match(/(.*) \((\d)/) ;
	if ( matches != null )
		return [matches[1], parseInt(matches[2])] ;
	return [name] ;
}
function get_transform(name) {
	var matches = name.match(/(.*)\/(.*)/) ;
	if ( matches != null )
		return [matches[1], matches[2]] ;
	return [name] ;
}
function decks_get() {
	if ( localStorage.decks )
		return localStorage.decks.split(',') ;
	else
		return [] ;
}
function deck_guessname(content) {
	var result = '' ;
	var match = content.match(/\/\/\s*NAME\s*:\s*(.*)$/m) ;
	if ( match != null )
		var result = match[1] ;
	return result ;
}
function deck_set(name, content) {
	// Name getting/parsing/asking
	if ( ( typeof name != 'string' ) || ( name == '' ) )
		name = prompt('What is this deck\'s name ?') ;
	if ( name == null )
		return false ;
	name = name.replace(/(\s+)/g, '_') ;
	// Content checking
	content = deck_xml_to_mw(content) ;
	// Deck creation
	var decks = decks_get() ;
	if ( decks.indexOf(name) == -1 ) {
		decks.push(name) ;
		store('decks', decks.join(',')) ;
	} else
		if ( ! confirm('Are you sure you want to overwrite deck '+name+' ?') )
			return false ;
	store('deck_'+name, content) ; // Save deck
	store('deck', name) ; // Set this deck as selected
	return true ;
}
function deck_del(name) {
	var decks = decks_get() ;
	var index = decks.indexOf(name) ;
	if ( index != -1 ) {
		if ( ! confirm('Are you sure you want to remove deck '+name+' ?') )
			return null ;
		decks.splice(index, 1) ;
		store('decks', decks.join(',')) ;
		store('deck_'+name) ;
		return true ;
	}
	alert('Impossible to remove deck '+name+' : it doesn\'t seem to exist') ;
	return false ;
}
function deck_get(name) {
	var decks = decks_get() ;
	if ( decks.indexOf(name) > -1 )
		return localStorage['deck_'+name] ;
	else
		return null ;
}
function deck_file_load(files) {
	for ( var i = 0 ; i < files.length ; i++ ) {
		var file = files.item(i) ;
		var name = file.name ;
		name = name.replace(/\.mwdeck$/gi, '') ;
		var reader = new FileReader();
		reader.addEventListener('load', function(ev) {deck_set(name, ev.target.result) ; decks_list() ; }, false) ;
		reader.readAsText(file) ;
	}
	decks_list() ;
}
function deck_xml_to_mw(str) {
	// Parse XML
	var parser = new DOMParser();  
	var xml = parser.parseFromString(str, "application/xml") ;
	with ( xml.documentElement ) // Test weel-formedness
		if (
			( tagName == "parseerror" )
			|| ( namespaceURI == "http://www.mozilla.org/newlayout/xml/parsererror.xml" )
		)
			return str ; // Not well-formed, return original string
	// Apply XSL
	var xhttp = new XMLHttpRequest();
	xhttp.open('GET', '/xml_to_mw.xsl', false) ;
	xhttp.send('') ;
	xsltProcessor = new XSLTProcessor();
	xsltProcessor.importStylesheet(xhttp.responseXML);
	resultDocument = xsltProcessor.transformToFragment(xml, document) ;
	var div = create_div(resultDocument) ; // Add to document to get content
	return div.textContent ; // Return parsed string
}
