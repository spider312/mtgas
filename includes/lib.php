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
// Debug
function l($obj) {
	echo '<pre>'.print_r($obj, true).'</pre>' ;
}
function d($obj) {
	die('<pre>'.print_r($obj, true).'</pre>') ;
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
//File size
function human_filesize($bytes, $decimals = 2) {
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}
function return_bytes($val) {
	$val = trim($val);
	$last = strtolower($val[strlen($val)-1]);
	switch($last) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}
	return $val;
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
	echo '<!DOCTYPE html>
<html lang="en">
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
	echo '    <a title="'.__('menu.main.title').'" href="'.$url.'">'.__('menu.main').'</a> - '."\n" ;
	foreach ( $menu_entries as $i => $entry ) {
		if ( $i == count($menu_entries)-1 )
			$separator = '' ;
		else
			$separator = ' - ' ;
		echo '    <a title="'.$entry->title.'" target="_blank" href="'.$entry->url.'">'.$entry->name.'</a>'.$separator."\n" ;
	}
	echo '    <a id="identity_shower" title="'.__('menu.identity_shower.title').'">Nickname</a>'."\n" ;
	echo '   </div>'."\n\n" ;
}
function html_options() { // Displays options window
	echo '    <h2>Options</h2>
    <fieldset><legend>Appearence</legend>
     <label title="Search images on another location than default one, another server or your own hard drive for example">Card images : <select id="cardimages_choice">' ;
     global $cardimages_choice, $cardimages_default ;
     foreach ( $cardimages_choice as $choice_name => $choice_url ) 
	echo '       <option value="'.$choice_url.'" selected="selected">'.$choice_name.'</option>'."\n" ;

}
// HTML Generation
function html_option($value, $disp, $selected) {
	$return  = '<option value="'.$value.'"' ;
	if ( $value == $selected )
		$return .= ' selected="selected"' ;
	$return .= '>'.$disp.'</option>' ;
	return $return ;
}
function html_checkbox($name, $checked) {
	$return = '<input type="checkbox" name="'.$name.'"' ;
	if ( $checked )
		$return .= ' checked="checked"' ;
	$return .= '>' ;
	return $return ;
}
function html_pre($str) {
	return '<pre>'.$str.'</pre>' ;
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
// Theme
function theme_image($name) {
	global $theme ;
	return '/themes/'.$theme.'/'.$name ;
}
?>
