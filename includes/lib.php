<?php
// Params management
function param_or_die($arr, $param, $name=null) {
	if ( $name == null )
		$name = $param ;
	if ( array_key_exists($param, $arr) )
		return $arr[$param] ;
	else
		die('A '.$name.' should have been passed in param') ;
}
function param($arr, $param, $defaultvalue='') {
	if ( array_key_exists($param, $arr) )
		return $arr[$param] ;
	else
		return $defaultvalue ;
}
function message($txt='no text to send') {
	$_SESSION['messages'][] = $txt ;
}
// Object
function object() {
	return new simple_object() ;
}
class simple_object {
	function __construct() {
	}
}
function obj_compare($a, $b) { // Returns if $a has exactly the same structure and values as $b
	if ( gettype($a) != gettype($b) )
		return false ;
	switch ( gettype($a) ) {
		case 'object' :
			foreach ( $a as $k => $v )
				if ( ! isset($b->$k) || ( $b->$k != $v ) )
					return false ;
			foreach ( $b as $k => $v )
				if ( ! isset($a->$k) )
					return false ;
			return true ;
			break ;
		default :
			return ( $a == $b ) ;
	}
	die('obj_compare') ;
}
function arr_diff($new, $old) { // Returns $new without values that didn't changed from $old
	$result = array() ;
	foreach ( $new as $key => $value ) { // Each value to search
		foreach ( $old as $k => $v ) { // Search in old
			if ( obj_compare($v, $value) )
				continue 2 ; // Not added to result
		}
		$result[$key] = $value ;
	}
	return $result ;
}
function obj_diff($new, $old) { // Returns only properties that changed between new and old value
	$result = new simple_object() ;	
	foreach ( $new as $key => $value ) { // Each value in new
		if ( isset($old->$key) ) {  // Key is present in old
			$oldvalue = $old->$key ;
			switch ( gettype($value) ) {
				case 'object' :
					$result->$key = obj_diff($value, $oldvalue) ;
					break ;
				case 'array' :
					$a = arr_diff($value, $oldvalue) ;
					if ( count($a) > 0 )
						$result->$key = $a ;
					break ;
				default :
					if ( $value != $old->$key )
						$result->$key = $value ;
			}
		} else // Key not present in old
			$result->$key = $new->$key ; // Set as new value
	}
	return $result ;
}
// Numbers
function addOrdinalNumberSuffix($num) {
	if ( ! in_array(($num % 100), array(11,12,13)) ) {
		switch ($num % 10) {
			// Handle 1st, 2nd, 3rd
			case 1:  return $num.'st';
			case 2:  return $num.'nd';
			case 3:  return $num.'rd';
		}
	}
	return $num.'th';
}
function human_filesize($bytes, $decimals = 2) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
// HTML
function add_css($args) {
	if ( count($args) > 0 ) {
		global $theme ;
		echo '  <!-- CSS -->'."\n" ;
		//echo '  <link type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.min.css">'."\n" ;
		foreach ( $args as $arg ) { /*func_get_args()*/
			if ( substr($arg, 0, 4) != 'http' )
				$prefix = '/themes/'.$theme.'/css/' ;
			else
				$prefix = '' ;
			echo '  <link type="text/css" rel="stylesheet" href="'.$prefix.$arg.'">'."\n" ;
		}
		echo '  <!-- /CSS -->'."\n" ;
	}
}
function add_js($args) {
	if ( count($args) > 0 ) {
		echo '  <!-- JS -->'."\n" ;
		//echo '  <script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>'."\n" ;
		foreach ( $args as $arg ) {
			if ( substr($arg, 0, 4) != 'http' )
				$prefix = '/js/' ;
			else
				$prefix = '' ;
			echo '  <script type="text/javascript" src="'.$prefix.$arg.'"></script>'."\n" ;
		}
		echo '  <!-- /JS -->'."\n" ;
	}
}
function add_rss($args) {
	if ( count($args) > 0 ) {
		echo '  <!-- RSS -->'."\n" ;
		foreach ( $args as $title => $feed )
			echo '  <link type="application/rss+xml" rel="alternate" title="'.$title.'" href="'.$feed.'">'."\n" ;
		echo '  <!-- /RSS -->'."\n" ;
	}
}
function html_head($title='No title', $css=array(), $js=array(), $rss=array()) {
	global $appname, $theme ;
	//if ( ! in_array('style.css', $css) )
	//	array_unshift($css, 'style.css') ;
	echo '<!DOCTYPE html>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title>'.$appname.' : '.$title.'</title>
  <link type="image/jpg" rel="icon" href="/themes/'.$theme.'/favicon.jpg">'."\n" ;
	add_css($css) ;
	add_js($js) ;
	add_rss($rss) ;
	echo ' </head>'."\n" ;
}
class menu_entry {
	function __construct($name, $url, $title='') {
		$this->name = $name ;
		$this->url = $url ;
		$this->title = $title ;
	}
	function bootstrap_render($offset='       ') {
		echo $offset.'<li ' ;
		if ( $_SERVER['PHP_SELF'] == $this->url )
			echo ' class="active"' ;
		echo 'title="'.$this->title.'"><a href="'.$this->url.'">'.$this->name.'</a></li>'."\n" ;

	}
}
function menu_add($name, $url, $title='') {
	global $menu_entries ;
	$menu_entries[] = new menu_entry($name, $url, $title) ;
	return $menu_entries ;
}
function bootstrap_menu($additionnal_entries=null) {
	global $menu_entries, $url, $appname ;
	$home = ( $_SERVER['PHP_SELF'] == '/index_alt.php' ) ;
	echo '   <!-- Navbar -->
   <div class="navbar navbar-inverse navbar-fixed-top">
    <div class="navbar-inner">
     <div class="container">
      <ul class="nav">
       <li' ;
	if ( $home )
		echo ' class="active"' ;
	echo ' title="'.$appname.'\'s home"><a href="'.$url.'/index_alt.php">Home</a></li>'."\n" ;
	foreach ( $menu_entries as $i => $entry )
		$entry->bootstrap_render() ;
	echo '      </ul>'."\n" ;
	if ( $home )
		echo '      <ul class="nav pull-right">
       <li><a id="identity_shower" title="Change nickname and avatar" class="pull-right">Nickname</a></li>
      </ul>'."\n" ;
	echo '     </div>
    </div>
   </div>
   <!-- /Navbar -->'."\n" ;
}
function html_menu($additionnal_entries=null) {
	global $menu_entries, $url ;
	echo '   <div id="header" class="section">'."\n" ;
	echo '    <a title="Main page" href="'.$url.'">Main page</a> - '."\n" ;
	foreach ( $menu_entries as $i => $entry ) {
		if ( $i == count($menu_entries)-1 )
			$separator = '' ;
		else
			$separator = ' - ' ;
		echo '    <a title="'.$entry->title.'" target="_blank" href="'.$entry->url.'">'.$entry->name.'</a>'.$separator."\n" ;
	}
	echo '    <a id="identity_shower" title="Change nickname and avatar">Nickname</a>'."\n" ;
	echo '   </div>'."\n\n" ;
}
function html_options() { // Displays options window
	echo '    <h2>Options</h2>
    <fieldset><legend>Appearence</legend>
     <label title="Search images on another location than default one, another server or your own hard drive for example">Card images : <select id="cardimages_choice">' ;
     global $cardimages_choice, $cardimages_default ;
     foreach ( $cardimages_choice as $choice_name => $choice_url ) 
	echo '       <option value="'.$choice_url.'" selected="selected">'.$choice_name.'</option>'."\n" ;
     echo '</select></label>
     <input id="cardimages" type="hidden" name="cardimages" value="'.$cardimages_default.'" accesskey="i" title="Fill in with an URL or a path on your own computer">
     <span id="cardimages_link">(<a href="http://forum.mogg.fr/viewtopic.php?pid=25#p25" target="_blank">Read that !</a>)</span>
     <label title="Display card upside-down when in an opponent\'s zone, looking more like real MTG playing"><input id="invert_bf" type="checkbox">Invert opponent\'s cards</label>
     <label title="Display card names on top of picture for cards on battlefield, and their costs for cards in hand"><input id="display_card_names" type="checkbox" checked="checked">Card names / mana costs</label>
     <label title="Activate transparency, nicer but slower"><input id="transparency" type="checkbox" checked="checked">Transparency</label>
     <label title="Display right click\'s drag\'n\'drop helper"><input id="helpers" type="checkbox" checked="checked">Helpers</label>
    </fieldset>

    <fieldset><legend>Behaviour</legend>
     <label title="Choose what happend when you doubleclick on library">Library double-click action : 
      <select id="library_doubleclick_action">
       <option value="look_top_n" title="Ask how many cards to look, then look that many cards from top of library. Default behaviour">Look top N cards</option>
       <option value="edit" title="Look in whole library, without asking anything">Search in library</option>
       <option value="draw" title="Draw a card, without asking anything">Draw a card</option>
      </select>
     </label>
     <label title="Draw your starting hand after toss and sides"><input id="auto_draw" type="checkbox" checked="checked">Auto draw</label>
     <label title="Play sounds on events"><input id="sounds" type="checkbox" checked="checked">Sound</label>
     <label title="Display a message when a triggered ability may be triggered. Beware, not every trigger is managed, and most of them just display a message"><input id="remind_triggers" type="checkbox" checked="checked">Remind triggers</label>
     <label title="Where to place creature cards by default (when double clicked) on battlefield">Place creature : 
      <select id="place_creatures">
       <option value="top">Top</option>
       <option value="middle" selected="selected">Middle</option>
       <option value="bottom">Bottom</option>
      </select>
     </label>
     <label title="Where to place non-creature cards by default (when double clicked) on battlefield">Place non-creature : 
      <select id="place_noncreatures">
       <option value="top" selected="selected">Top</option>
       <option value="middle">Middle</option>
       <option value="bottom">Bottom</option>
      </select>
     </label>
     <label title="Where to place land cards by default (when double clicked) on battlefield">Place land : 
      <select id="place_lands">
       <option value="top">Top</option>
       <option value="middle">Middle</option>
       <option value="bottom" selected="selected">Bottom</option>
      </select>
     </label>
     <label title="If checked, you will automatically be marked as ready after picking your card in drafts"><input id="draft_auto_ready" type="checkbox" checked="checked">Auto-mark as ready after picking</label>
     <label title="If checked, every card image will be preloaded at the begining of the game instead of waiting its first display"><input id="check_preload_image" type="checkbox" checked="checked">Preload images</label>
    </fieldset>

    <fieldset><legend>Debug</legend>
     <label title="If checked, logs message (non blocking errors, debug informations) will be displayed as chat messages instead of being sent to a hidden console (Ctrl+L), and debug options are added to menus"><input id="debug" type="checkbox">Debug mode</label>
    </fieldset>

' ;
}
// JSON
function json_verbose_error($i) {
	switch ( $i ) {
		case JSON_ERROR_NONE:
			return 'Aucune erreur' ;
		case JSON_ERROR_DEPTH:
			return 'Profondeur maximale atteinte' ;
		case JSON_ERROR_STATE_MISMATCH:
			return 'Inadéquation des modes ou underflow' ;
		case JSON_ERROR_CTRL_CHAR:
			return 'Erreur lors du contrôle des caractères' ;
		case JSON_ERROR_SYNTAX:
			return 'Erreur de syntaxe ; JSON malformé' ;
		case JSON_ERROR_UTF8:
			return 'Caractères UTF-8 malformés, probablement une erreur d\'encodage' ;
		default:
			return 'Erreur inconnue' ;
	}
	return 'Hu ?' ;
}
// http://stackoverflow.com/questions/1048487/phps-json-encode-does-not-escape-all-json-control-characters
function escapeJsonString($value) { # list from www.json.org: (\b backspace, \f formfeed)
	$escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c") ;
	$replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b") ;
	$result = str_replace($escapers, $replacements, $value) ;
	return $result ;
}
?>
