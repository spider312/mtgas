// Bool
function XOR(a, b) {
	return ( a || b ) && !( a && b ) ;
}
// math
function min() {
	var tmp = arguments[0] ;
	for ( var i = 1 ; i < arguments.length ; i++ )
		if ( arguments[i] < tmp ) 
			tmp = arguments[i] ;
	return tmp ;
}
function max() {
	var tmp = arguments[0] ;
	for ( var i = 1 ; i < arguments.length ; i++ )
		if ( arguments[i] > tmp ) 
			tmp = arguments[i] ;
	return tmp ;
}
function between(val, fl, ce, exc) {
	if ( fl > ce ) { // Swap borns
		var swap = fl ;
		fl = ce ;
		ce = swap ;
	}
	if ( exc ) // Exclusive
		return ( ( val > fl ) && ( val < ce) ) ;
	else
		return ( ( val >= fl ) && ( val <= ce) ) ;
}
function rand(n) {
	return Math.floor( Math.random() * n ) ;
}
function pad(n){return n<10 ? '0'+n : n}
	// round alias with decimality
function round(nb, exp) {
	if ( ! isn(exp) )
		exp = 1 ;
	var precision = Math.pow(10, exp) ;
	return Math.round(precision * nb) / precision ;
}
function floor(num, exp) {
	if ( ! isn(exp) )
		exp = 1 ;
	var precision = Math.pow(10, exp) ;
	return Math.floor(precision * nb) / precision ;
}
function ceil(num, exp) {
	if ( ! isn(exp) )
		exp = 1 ;
	var precision = Math.pow(10, exp) ;
	return Math.ceil(precision * nb) / precision ;
}
// Geometry
function dot_in_rect(dot, rect) {
	result = ( dot.x > rect.x ) ;
	result &= ( dot.x < rect.xe ) ;
	result &= ( dot.y > rect.y ) ;
	result &= ( dot.y < rect.ye ) ;
	return result ;
}
function dot(x, y) {
	this.x = x ;
	this.y = y ;
	return this ;
}
function rectwh(x, y, w, h) {
	this.x = x ;
	this.y = y ;
	this.w = w ;
	this.h = h ;
	this.xe = x + w ;
	this.ye = y + h ;
	rect_swap(this) ;
	return this ;
}
function rectbe(xb, yb, xe, ye) {
	this.x = xb ;
	this.y = yb ;
	this.xe = xe ;
	this.ye = ye ;
	this.w = xe - xb ;
	this.h = ye - yb ;
	rect_swap(this) ;
	return this ;
}
function rect_swap(rect) {
	// Verifying if begin < end, else swapping
	if ( rect.x > rect.xe ) {
		var xt = rect.xe ;
		rect.xe = rect.x ;
		rect.x = xt ;
		rect.w = - rect.w ;
	}
	if ( rect.y > rect.ye ) {
		var yt = rect.ye ;
		rect.ye = rect.y ;
		rect.y = yt ;
		rect.h = - rect.h ;
	}
}
function collision(object1, object2) {
	left1 = object1.x ;
	left2 = object2.x ;
	right1 = object1.x + object1.w ;
	right2 = object2.x + object2.w ;
	top1 = object1.y ;
	top2 = object2.y ;
	bottom1 = object1.y + object1.h ;
	bottom2 = object2.y + object2.h ;
	if (bottom1 < top2) return false ;
	if (top1 > bottom2) return false ;
	if (right1 < left2) return false ;
	if (left1 > right2) return false ;
	return true ;
}
// String
function nounize(str) {
	return str[0].toUpperCase() + str.toLowerCase().substr(1) ;
}
function getGetOrdinal(n) {
	var s=["th","st","nd","rd"],
	v=n%100;
	return n+(s[(v-20)%10]||s[v]||s[0]);
}
function disp_percent(nb) {
	return Math.round(nb*100, 2)+'%' ;
}
function string_inspector(str) {
	var result = '' ;
	for ( var i = 0 ; i < str.length ; i++ ) 
		result += i+' : '+str[i]+' ('+str.charCodeAt(i)+')\n' ;
	alert(result) ;
}
function prepend_s(nb) {
	if ( nb < 2 )
		return '' ;
	else
		return 's' ;
}
function string_limit(txt, limit, prepend) {
	if ( ! isn(limit) )
		limit = 10 ;
	if ( ! iss(prepend) )
		prepend = '...' ;
	if ( txt.length > limit )
		return txt.substr(0, limit-prepend.length)+prepend ;
	else
		return txt ;
}
// Array
function inarray(value, arr) {
	for ( var i in arr )
		if ( arr[i] == value )
			return true ;
	return false
}
function shuffle(ary) {
	var s = [];
	while (ary.length) {
		var e = ary.splice(Math.random() * ary.length, 1) ;
		s.push(e[0]) ;
	}
	while (s.length)
		ary.push(s.pop());
}
// Time
function time_disp(duration) { // In seconds
	var prefix = '' ;
	if ( duration < 0 ) {
		prefix = '-' ;
		duration = - duration ;
	}
	var seconds = duration % 60 ;
	disp = seconds + 's' ;
	duration -= seconds ;
	duration /= 60 ;
	if ( duration > 0 ) {
		var minutes = duration % 60 ;
		disp = minutes + 'm ' + disp ;
		duration -= minutes ;
		duration /= 60 ;
		if ( duration > 0 ) {
			var hours = duration % 24 ;
			disp = hours + 'h ' + disp ;
			duration -= hours ;
			duration /= 24 ;
			if ( duration > 0 ) {
				var days = duration ;
				disp = days + 'd ' + disp ;
			}
		}
	}
	return prefix + disp ;
}
function bench() {
	var date = new Date()
	return date.getMilliseconds() + date.getSeconds()*1000 ;
}
function mysql2date(mysqldate) {
	return new Date(mysqldate.replace(' ', 'T')) ;
}
// Types ( https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Operators/typeof )
function isset(val) {
	return ( typeof val != 'undefined' ) ;
}
function isb(val) {
	return ( typeof val == 'boolean' ) ;
}
function isn(val) {
	return ( ( typeof val == 'number' ) && ( ! isNaN(val) ) ) ;
}
function iss(val) {
	return ( typeof val == 'string' ) ;
}
function isf(val) {
	return ( typeof val == 'function' ) ;
}
function iso(val, notnull) {
	var res = ( typeof val == 'object' ) ;
	if ( isb(notnull) && notnull )
		return ( res && ( val != null ) )
	return res ;
}
function issn(val) { // Within JSON, sometimes, values are typed as integers
	return iss(val) || isn(val) ;
}
function disp_int(n) {
	if ( n >= 0 )
		n = '+'+n ;
	else
		n += '' ; // Transtyping
	return n ;
}
// UI
function prompt_int(txt, n) {
	if ( ! txt )
		var txt = 'Type in the number' ;
	if ( isn(n) )
		var t = n.toString() ;
	else
		var t = '0' ;
	do {
		t = prompt(txt, t) ;
		if ( t == null )
			return null ;
		n = parseInt(t) ;
	} while ( ! isn(n) )
	return n ;
}
// Debug
function display2darray(arr) {
	var colsize = 10 ;
	var result = "\n"+fixlength('arr',7)+'|' ;
	for ( var j in arr[0] )
		result += fixlength(j,7) + '|' ;
	result += "\n" ;
	result += displayline(arr[0].length, colsize)  ;
	for ( var i in arr ) {
		result += fixlength(i, colsize)+'|' ;
		for ( var j in arr[i] ) 
			if ( arr[i][j] != null )
				result += fixlength(arr[i][j], colsize) + '|' ;
			else
				result += fixlength('', colsize) + '|' ;
		result += "\n" ;
		result += displayline(arr[j].length, colsize) ;
	}
	return result ;
}
function displayline(colnb,colsize,n) {
	var result = '' ;
	for ( var i = 0 ; i < colnb+1 ; i++ ) {
		for ( var j = 0 ; j < colsize ; j++ )
			result += '-' ;
		result += '+' ;
	}
	result += "\n" ;
	return result ;
}
function fixlength(str,length) {
	str += '' ;
	if ( str.length > length )
		str = str.substring(0,length) ;
	else
		for ( var i = str.length ; i < length ; i++ )
			str += ' ' ;
	return str ;
}
// Objects
/*
 * Fonction de clonage
 * @author Keith Devens
 * @see http://keithdevens.com/weblog/archive/2007/Jun/07/javascript.clone
 */
function clone(srcInstance) {
	if( typeof(srcInstance) != 'object' || srcInstance == null ) // Only clone non-null objects, other vars are passed by value
		return srcInstance ;
	var newInstance = new srcInstance.constructor() ;
	for( var i in srcInstance )
		if ( srcInstance[i] === srcInstance ) // Property is a self reference, change it for a reference to new instance
			newInstance[i] = newInstance ;
		else // Not a self reference, copy address/value
			newInstance[i] = clone(srcInstance[i]) ; // srcInstance[i]
	return newInstance;
}
// Card sorting (for lists)
function sort_ext_alphabet(a, b) {
	if ( a.ext < b.ext )
		return -1 ;
	else if ( a.ext > b.ext )
		return 1 ;
	else
		return sort_alphabet(a, b) ;
}
function sort_alphabet(a, b) {
	if ( a.name < b.name )
		return 1 ;
	else if ( a.name > b.name )
		return -1 ;
	else
		return 0
}
function sort_deck(a, b) {
	if ( a.id < b.id )
		return 1 ;
	else if ( a.id > b.id )
		return -1 ;
	else
		return 0
}
// Functions
function functionname(func) {
	var code = func.toString() ;
	return code.substr(0,code.indexOf('{')-1) ;
}
/* An old way to detect player initiating an action, kept for legacy and debug reasons */
function stack_trace(func) { // Returns an array representation of call stack
// Last array element is a function passed in parameter
// Previous array element is this function's caller
// Etc. until first element
	var result = [] ;
	if ( typeof func == 'function' )
		while ( func != null ) {
			//result.unshift(functionname(func)) ;
			result.unshift(func) ;
			func = func.caller ;
		}
	return result ;
}
