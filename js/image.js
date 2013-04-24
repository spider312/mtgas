// image.js : Images and cache management
// Lib
function card_image_url(ext, name, attrs) {
	var url = '/'+ext+'/'+card_image_name(name) ;
	if ( attrs )
		if ( isn(attrs.nb) )
			url += attrs.nb ;
	url += '.full.jpg' ;
	return url ;
}
function card_image_name(name) { // Follow card names conventions from slightlymagic
	name = name.replace(' / ', '') ; // "Fire / Ice" -> "FireIce"
	name = name.replace(':', '') ; // "Circle of protection: Black" -> "Circle of protection Black"
	return name ;
}
function card_images(url) {
	// http://www.wizards.com/global/images/magic/general/[card name s/ /_/g].jpg
	var result = [] ;
	if ( url != '' )
		result.push(cardimages + '/' + url) ; // User data
	if ( cardimages != cardimages_default ) // If user data differs from default data
		result.push(cardimages_default + '/' + url) ; // Add default data as fallback
	for ( var i = 0 ; i < result.length ; i++ ) {
		if ( result[i].indexOf('//') > -1 ) {
			//while ( result[i].indexOf('//') > -1 )
				//result[i] = result[i].replace(/\/\//g, '\/') ;
			result[i] = result[i].replace(new RegExp('//*', 'g'), '\/') ;
			result[i] = result[i].replace('http:/', 'http://') ;
			result[i] = result[i].replace('file:/', 'file://') ;
		}
		if ( ( result[i].indexOf('http:') == -1 ) && ( result[i].indexOf('file:') == -1 ) ) // User has enter a URL without http nor file
			result[i] = 'file://'+result[i] ; // Let's presume it's a local folder
	}
	return result ;
}
function theme_image(name) {
	return [ '/themes/'+theme+'/'+name ] ;
}
function localize_image(url) { // Detects if url is absolute or relative, then add an initial / if relative
	if ( ( url.indexOf('://') < 0 ) && ( url.indexOf('/') > 0 ) )
		url = '/'+url ;
	return url ;
}
// Classes
function image_cache() {
/* This class tries to load a list of images and keep track of any success or fail of this result
 * If called back with an image ever successed, failed, or currently loading : 
 *  - If one is successed, will launch directly callback_load on it
 *  - If one is failed (meaning anyone will fail), will launch directly callback_error on it
 *  - If one is loading, will add load anr error success on this loading,
 *  	that will trigger ALL the desired callbacks (every one added while image is loading) when this one finish its loading
*/
	this.debug = false ; // Should i display debug message
	this.cache_loading = new Array() ; // Array of [URL, HTMLImage]
	this.cache_loaded = new Array() ; // Array of [URL, HTMLImage]
	this.cache_error = new Array() ; // Array of URL
	this.cache_skipped = new Array() ; // Array of URL
	this.is_loaded = function(url) {
		return ( this.search(url, this.cache_loaded) > -1 ) ;
	}
	this.load = function(arr_images, callback_load, callback_error, obj) {
	/*
	 * arr_images : an array of URL, will try to load first, if fail will try to load next, etc.
	 * callback_load : function launched in case of success during image loading (first param is the loaded image)
	 * callback_error : function launched in case ALL images fail loading, meaning none of the URL in array will work
	 * obj : an object that will be passed in parameter of both callbacks
	*/
		var myimg = null ;
		// First, check if one of the required URL has already worked
		for ( var i in arr_images ) {
			var url = arr_images[i] ;
			var idx = this.search(url, this.cache_loaded) ;
			if ( idx != -1 ) {
				myimg = this.cache_loaded[idx][1] ;
				if ( this.debug )
					log('IMG CACHE - using loaded cache : '+url) ;
				//callback_load(myimg, obj) ; // Use it to gain time (it's in browser's cache)
				window.setTimeout(callback_load, 0, myimg, obj) ; // There, nothing has been returned, card.load_image doesn't know which image it is loading
				// With any delay, image has been returned and stored as loading, and can be compared by callback
				return myimg ; // And stop process, all is done
			} // Else, continue process
		}
		// Then, check if one has failed, meaning all URLs are wrong
		for ( var i in arr_images ) {
			var url = arr_images[i] ;
			if ( inarray(url, this.cache_error) ) {
				if ( this.debug )
					log('IMG CACHE - using failed cache :'+url) ;
				callback_error(obj, '['+url+']') ; // Trigers fail, [] to mean call back on cached error
				return null ; // And stop process, all is done
			} // Else, continue process
		}
		// Then, check if one is loading
		for ( var i in arr_images ) {
			var url = arr_images[i] ;
			var idx = this.search(url, this.cache_loading) ;
			if ( idx > -1 ) { // One is actually loading
				myimg = this.cache_loading[idx][1] ; // Add triggers to it
				if ( this.debug )
					log('IMG CACHE - using loading cache : '+url) ;
				var cache = myimg ;
			}
		}
		if ( myimg == null ) { // No URL in list is loaded, failed, or loading, create a new image cache for it
			var url = arr_images[0] ;
			var cache = new Image() ;
			cache.init_urls = clone(arr_images) ;
			cache.urls = arr_images ;
			cache.next = function() {
				if ( this.urls.length > 0 ) { // If list isn't empty
					var oldurl = this.src ;
					var oldorigurl = this.src_orig ;
					this.src_orig = this.urls.shift() ; // Save src before being interpreted by the DOM, for further comparison
					this.src = this.src_orig ; // Try next item, will trigger 'load' or 'error' event, depending if file is loadable or not
					if ( oldurl == '' ) { // First "next"
						game.image_cache.cache_loading.push(new Array(this.src_orig, this)) ; // Consider newly created image as "loading"
						if ( this.debug )
							log('IMG CACHE - loading first URL : '+this.src) ;
					} else { // Others nexts
						// As we 'nexted', we must inform loading array about the new src
						var idx = game.image_cache.search(oldorigurl, game.image_cache.cache_loading) ;
						if ( idx == -1 )
							log('IMG CACHE - Nexting oldurl not found : '+oldorigurl) ;
						else {
							game.image_cache.cache_skipped.push(oldorigurl) ;
							game.image_cache.cache_loading[idx][0] = this.src_orig ;
							if ( this.debug )
								log('IMG CACHE - nexted : '+oldorigurl+' -> '+this.src_orig) ;
						}
					}
					return true ;
				}
				return false ;
			}
			cache.addEventListener('load', function(ev) { // Correctly loaded for the first time
				var idx = game.image_cache.not_loading_anymore(this.src_orig) ;
				if ( game.image_cache.debug )
					log('IMG CACHE - loaded : '+this_orig.src+' ('+idx+')') ;
				if ( idx != -1 ) { // If it's considered as not loaded yet
					game.image_cache.cache_loaded.push(new Array(this.src_orig, this)) // Consider it as loaded
					if ( game.image_cache.debug )
						log('IMG CACHE - loading => loaded : '+this.src_orig) ;
				} else 
					if ( game.image_cache.debug )
						log('IMG CACHE - not present in loading : '+this.src_orig) ;
			}, false) ;
			cache.addEventListener('error', function(ev) { // File not loadable
				if ( ! this.next() ) { // List is empty
					game.image_cache.not_loading_anymore(this.src_orig) ;
					game.image_cache.cache_error.push(this.src_orig) ; // Consider it as errored
					if ( this.debug )
						log('IMG CACHE - failed : '+this.src_orig) ;
					callback_error(obj, '*'+this.src_orig+'*') ; // Trigger error callback, * to mean call back on real error
				}
			}, false) ;
			if ( this.debug )
				log('IMG CACHE - created : '+arr_images.join(', ')) ;
		}
		// Add triggers to newly created or previously loading image cache
		cache.addEventListener('load', function(ev) { // Correctly loaded
			if ( isf(callback_load) )
				callback_load(this, obj) ; // Trigger load callback
			if ( this.debug )
				log('IMG CACHE - trigger load : '+this.src) ;
		}, false) ;
		// Finaly, for newly created image cache, launch first loading
		if ( myimg == null ) {
			myimg = cache ; // Return something interesting : HTML image with loading src
			cache.next() ;
		}
		return myimg ;
	}
	this.search = function(match, arr) {
		for ( var i in arr )
			if ( arr[i][0] == match )
				return i ;
		return -1 ;
	}
	this.not_loading_anymore = function(url) {
		var idx = search_in_img_array(this.cache_loading, url) ;
		if ( idx > -1 ) // If it's still considered as loading (normally, only on first 'loaded' trigger)
			this.cache_loading.splice(idx, 1) ; // Consider it as not loading anymore
		return idx ;
	}
	this.info = function() {
		var result = 'Image cache : \n' ;
		result += 'LOADING ('+this.cache_loading.length+')\n' ;
		for ( var i in this.cache_loading )
			result += '   - '+this.cache_loading[i][0]+'\n' ;
		result += '\n' ;
		result += 'LOADED ('+this.cache_loaded.length+')\n' ;
		for ( var i in this.cache_loaded )
			result += '   - '+this.cache_loaded[i][0]+'\n' ;
		result += '\n' ;
		result += 'ERRORED ('+this.cache_error.length+')\n' ;
		for ( var i in this.cache_error )
			result += '   - '+this.cache_error[i]+'\n' ;
		result += '\n' ;
		result += 'SKIPPED ('+this.cache_skipped.length+')\n' ;
		for ( var i in this.cache_skipped )
			result += '   - '+this.cache_skipped[i]+'\n' ;
		return result ;
	}
}
function search_in_img_array(arr, url) { // arr is an array containing array of [url], [HTMLImage]
	for ( var i = 0 ; i < arr.length ; i++ )
		if ( arr[i][0] == url )
			return i ; 
	return -1
}
