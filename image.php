<?php
ini_set('display_errors',1);
header('Content-type: image/jpeg');
$image = new Imagick('1.jpg');
$image->gaussianBlurImage(30,3);
echo $image;