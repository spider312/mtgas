<?php
$langs = array( // Langs for cards ( => images)
	'en' => 'English',
	'de' => 'German',
	'fr' => 'French',
	'it' => 'Italian',
	'es' => 'Spanish',
	'pt' => 'Portuguese',
	'jp' => 'Japanese',
	'cn' => 'Simplified Chinese',
	'ru' => 'Russian',
	'tw' => 'Traditional Chinese',
	'ko' => 'Korean'
) ;
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
// Debug
function l($obj) {
	return '<pre>'.print_r($obj, true).'</pre>'."\n" ;
}
function p($obj) {
	echo l($obj);
}
function d($obj) {
	die(l($obj));
}
function debug($object) {
	$result = gettype($object)." : \n" ;
	switch ( true ) {
		case is_object($object) :
			$reflect = new ReflectionClass($object);
			$props = $reflect->getProperties();
			foreach ( $props as $i => $val )
				$result .= "\t->".$val->name.' : '.$val->class."\n" ;
			break ;
		case is_array($object) :
			foreach ( $object as $i => $val ) {
				$result .= "\t[".$i.'] : ' ;
				if ( is_object($val) )
					$result .= gettype($val) ;
				else
					$result .= $val ;
				$result .= "\n" ;
			}
			break ;
		default : 
			$result.= print_r($object, true) ;
	}
	return $result ;
}
function now($offset = 0) {
	return date('Y-m-d H:i:s', time() + $offset) ;
}
// Object
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
	$result = new stdClass() ;	
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
	if ( count(get_object_vars($result)) == 0 )
		$result = null ;
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
// File
function sorted_scandir($dir, $field='mtime') {
	$files = scandir($dir) ;
	$data = array() ;
	foreach ( $files as $file )
		if ( ( $file != '.' ) && ( $file != '..' ) ) {
			$stat = stat($dir.'/'.$file) ;
			$data[$file] = $stat[$field] ;
		}
	asort($data) ;
	return array_keys($data) ;
}
function scan($dir) {
	if ( is_dir($dir) ) {
		$result = array() ;
		foreach ( scandir($dir) as $file ) 
			if ( ( $file != '..' ) && ( $file != '.' ) )
				$result[$file] = scan($dir.'/'.$file) ;
	} else
		$result = '' ;
	return $result ;
}
// File size
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
		global $theme, $url ;
		echo '  <!-- CSS -->'."\n" ;
		foreach ( $args as $arg ) { /*func_get_args()*/
			if ( substr($arg, 0, 4) != 'http' )
				$prefix = $url.'/themes/'.$theme.'/css/' ;
			else
				$prefix = '' ;
			echo '  <link type="text/css" rel="stylesheet" href="'.$prefix.$arg.'">'."\n" ;
		}
		echo '  <!-- /CSS -->'."\n" ;
	}
}
function add_js($args) {
	if ( count($args) > 0 ) {
		global $url ;
		echo '  <!-- JS -->'."\n" ;
		foreach ( $args as $arg ) {
			if ( substr($arg, 0, 4) != 'http' )
				$prefix = $url.'/js/' ;
			else
				$prefix = '' ;
			echo '  <script type="text/javascript" src="'.$prefix.$arg.'"></script>'."\n" ;
		}
		echo '  <!-- /JS -->'."\n" ;
	}
}
function add_rss($args) {
	if ( count($args) > 0 ) {
		global $url ;
		echo '  <!-- RSS -->'."\n" ;
		foreach ( $args as $title => $feed )
			echo '  <link type="application/rss+xml" rel="alternate" title="'.$title.'" href="'.$url.$feed.'">'."\n" ;
		echo '  <!-- /RSS -->'."\n" ;
	}
}
function html_head($title='No title', $css=array(), $js=array(), $rss=array()) {
	global $appname, $index_image ;
	echo '<!DOCTYPE html>
<html lang="en">
 <head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title>'.$appname.' : '.$title.'</title>
  <link type="image/png" rel="icon" href="'.theme_image($index_image).'">'."\n" ;
  	$css[] = 'debug.css' ;
	add_css($css) ;
	$js[] = 'debug.js' ;
	add_js($js) ;
	add_rss($rss) ;
	echo ' </head>'."\n" ;
}
function html_foot() {
	global $dir ;
	if ( is_file($dir.'/footer.php') )
		include $dir.'/footer.php' ;
	else
		echo "  <!-- No 'footer.php' file found, you may create one if you want to include something on each page of your site -->\n" ;
	echo ' </body>
</html>' ;
}
class menu_entry {
	function __construct($name, $url, $title='') {
		$this->name = $name ;
		$this->url = $url ;
		$this->title = $title ;
	}
}
function menu_add($name, $url, $title='') {
	global $menu_entries ;
	$menu_entries[] = new menu_entry($name, $url, $title) ;
	return $menu_entries ;
}
function html_menu($additionnal_entries=null) {
	global $menu_entries, $url, $index_image ;
	echo '   <header class="section">'."\n" ;
	echo '    <a id="mainpage" title="'.__('menu.main.title').'" href="'.$url.'">
	<img src="'.theme_image($index_image).'" alt="'.__('menu.main.title').'">
	</a> - '."\n" ;
	foreach ( $menu_entries as $i => $entry ) {
		if ( $i == count($menu_entries)-1 )
			$separator = '' ;
		else
			$separator = ' - ' ;
		echo '    <a title="'.$entry->title.'" href="'.$entry->url.'">'.$entry->name.'</a>'.$separator."\n" ;
	}
	echo '    <a id="identity_shower" title="'.__('menu.identity_shower.title').'">Nickname</a>'."\n" ;
	echo '   </header>'."\n\n" ;
}
function ws_indicator() {
	echo '<img id="wsci" title="Connexion : not initialized" alt="Connexion : not initialized" src="'.theme_image('sphere/black.png').'">' ;
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
function json_verbose_error($i=-1) {
	if ( $i == -1 )
		$i = json_last_error() ;
	switch ( $i ) {
		case JSON_ERROR_NONE:
			return 'Aucune erreur' ;
		case JSON_ERROR_DEPTH:
			return 'Profondeur maximale atteinte' ;
		case JSON_ERROR_STATE_MISMATCH:
			return 'JSON invalide ou mal formé' ;
		case JSON_ERROR_CTRL_CHAR:
			return 'Erreur lors du contrôle des caractères' ;
		case JSON_ERROR_SYNTAX:
			return 'Erreur de syntaxe' ;
		case JSON_ERROR_UTF8:
			return 'Caractères UTF-8 malformés, probablement une erreur d\'encodage' ;
		case JSON_ERROR_RECURSION:
			return 'Une ou plusieurs références récursives sont présentes dans la valeur à encoder' ;
		case JSON_ERROR_INF_OR_NAN:
			return 'Une ou plusieurs valeurs NAN ou INF sont présentes dans la valeurs à encoder' ;
		case JSON_ERROR_UNSUPPORTED_TYPE:
			return 'Une valeur d\'un type qui ne peut être encodée a été fournie' ;
		default:
			return 'Erreur inconnue : '.$i ;
	}
}
// Theme
function theme_image($name) {
	global $theme, $url ;
	return $url.'/themes/'.$theme.'/'.$name ;
}
function manas2html($manas) { // Returns HTML code for icons representing array 'manas'
	$colors = '' ;
	foreach ( $manas as $mana )
		$colors .= '<img src="'.theme_image('ManaIcons/'.$mana.'.png').'" width="16" height="16">' ;
	return $colors ;
}
function manacost2html($cost) { // Returns HTML code for icons representing string 'cost'
	if ($cost == '' )  // Manage '' => No casting cost (land)
		return '' ;
	else if ( is_numeric($cost) ) // Manage '10'
		$manas = array($cost) ;
	else
		$manas = str_split($cost) ;
	return manas2html($manas) ;
}
function manas2color($manas) { // Returns HTML code for icons representing card color for manas in param
	$mymanas = array() ;
	foreach ( $manas as $mana ) {
		if ( is_numeric($mana) || (  $mana == 'X' ))
			continue ;
		if ( array_search($mana, $mymanas) === false )
			$mymanas[] = $mana ;
	}
	return manas2html($mymanas) ;
}
?>
