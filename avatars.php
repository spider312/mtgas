<?php
if ( array_key_exists('path', $_GET) )
	$path = $_GET['path'] ;
else
	$path = 'img/avatar/_mtg/' ;
?>
<!DOCTYPE html>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <title>MTGAS : Avatars gallery</title>
<script type="text/javascript">
function select(file) {
	var field = window.opener.document.getElementById('profile_avatar') ;
	field.value = file ;
	var e = document.createEvent('HTMLEvents');
	e.initEvent('change', true, true);
	field.dispatchEvent(e);
	window.close() ;
}
function openfolder(folder) {
	if ( folder.className == 'folder' )
		folder.className = 'openfolder' ;
	else
		folder.className = 'folder' ;
}
</script>
  <style>
div.avatar {
	float: left ;
	border: 1px solid black ;
	margin: 1px ;
	width: 100px ;
	height: 100px ;
}
div.avatar:hover {
	background-color: lightblue ;
}
div.avatar img {
	max-width: 100px ;
	max-height: 100px ;
	display: block ;
	margin: auto ;
}
#bottomlink	{
	text-align: center ;
	clear: both ;
}
  </style>
 </head>
 <body>
<?php
function dir_list($path) {
	foreach ( scandir($path) as $file )
		if ( ( $file != '.' ) && ( $file != '..' ) )
			if ( is_dir($path.$file) )
				dir_list($path.$file.'/') ;
			else
				echo '<div class="avatar" onclick="select(\''.addslashes($path.$file).'\')"><img title="'.$path.$file.'" src="'.$path.$file.'"></div>'."\n" ;
}
dir_list($path) ;
?>
  <div id="bottomlink">
<?php
if ( array_key_exists('path', $_GET) ) {
?>
   <a href="?">MTG Gallery</a>
<?php
} else {
?>
   <a href="?path=img/avatar/">Full gallery</a>
<?php
}
?>
  </div>
 </body>
</html>
