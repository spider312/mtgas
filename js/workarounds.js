function strip(str) { // Removes all type of spaces from the string (space, tab, carriage return, newline ...)
	return str.replace(/\f/g,'').replace(/\n/g,'').replace(/\r/g,'').replace(/\t/g,'').replace(/\v/g,'').replace(/^\s*/g,'').replace(/\s*$/g,'') ;
}
function debug_xml(node,prefix) {
// Fonction de débug : retourne une chaine indiquant la structure de l'objet XML passé en paramétre
// La fonction est récursive et doit étre appelée avec un préfixe pour l'affichage, de préférence, mettre une chaine vide
	if ( ! prefix )
		prefix = '' ;
	var result ='';
	switch ( node.nodeType ) {
		case 1: // Element
		case 9: // Document
			var childs = node.childNodes ;
			result += prefix + '<' + node.nodeName + '>' ;
			if ( node.nodeValue != null )
				result += '(' + node.nodeValue + ')' ;
			result += '\n' ;
			if ( childs.length > 0 )
				for( var i = 0 ; i < childs.length ; i++) {
					child = childs.item(i) ;
					result += debug_xml(child,prefix+'\t('+i+')') ;
				}
			break ;
		case 3: // Text
			var text = strip(node.nodeValue) ;
			if ( text != '' ) 
			result += prefix + '"' + text + '"\n' ;
			break ;
		case 8: // Comment
			var text = strip(node.nodeValue) ;
			if ( text != '' ) 
			result += prefix + '//' + text + '"\n' ;
			break ;
		default:
			result += prefix + 'unknown node type ' + node.nodeType + '\n' ;
	}
	return result ;
}
function workarounds() {
	if ( ! isf(document.hasFocus) ) {
		log("Your browser doesn't support focus detection, disabling it") ;
		document.hasFocus = function() {
			return true ;
		}
	}
}
