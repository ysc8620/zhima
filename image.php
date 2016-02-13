<?php
ini_set('display_errors',1);
if(file_exists('1.jpg')){
    header('Content-type: image/jpeg');
    $image = new Imagick('1.jpg');
    $image->gaussianBlurImage(30,3);

    echo $image->getImageBlob();
}else{
    echo 'file no exit';
}
