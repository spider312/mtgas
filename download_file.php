<?php
// Used by "ajax" to create a file on client's computer from a javascript content
header('Content-Disposition: attachment; filename='.str_replace(' ', '_', $_POST['name'])) ;
header('Content-type: text/plain');
die($_POST['content']) ; // stripslashes ?
?>
