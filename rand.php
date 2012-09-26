<?php

function getRandom($min, $max) { // http://caveofchaos.com/blogs/view/generate-random-number-with-php-using-dev-urandom
    $bits = '';
    $diff = $max-$min;
    $bytes = ceil($diff/256);
    $fp = @fopen('/dev/urandom','rb');
    if ($fp !== FALSE) {
        $bits .= @fread($fp,$bytes);
        @fclose($fp);
    }
    $bitlength = strlen($bits);
    for ($i = 0; $i < $bitlength; $i++) {
        $int =  1+(ord($bits[$i]) % (($max-$min)+1));
    }
    return $int;
}

//die(''.getRandom(1,100)) ;

header("Content-type: image/png");
$sizex=800;
$sizey=400;

$img = imagecreatetruecolor($sizex,$sizey);
$ink = imagecolorallocate($img,255,255,255);

for($i=0;$i<$sizex/2;$i++) {
  for($j=0;$j<$sizey;$j++) {
  imagesetpixel($img, rand(1,$sizex/2), rand(1,$sizey), $ink);
  }
}

for($i=$sizex/2;$i<$sizex;$i++) {
  for($j=0;$j<$sizey;$j++) {
  imagesetpixel($img, mt_rand($sizex/2,$sizex), mt_rand(1,$sizey), $ink);
  }
}

imagepng($img);
imagedestroy($img);
?>
