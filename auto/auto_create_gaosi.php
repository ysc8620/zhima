<?php
/**
 * Created by JetBrains PhpStorm.
 * User: root
 * Date: 16-2-19
 * Time: 下午2:18
 * To change this template use File | Settings | File Templates.
 */
set_time_limit(0);
$root_path = realpath(dirname(dirname(__FILE__)));

include($root_path . '/auto/config.php');


do{
    $time = time() - 600;
    $item = M('zhaopian')->where("is_create= 0 and create_time < $time")->find();

    if(empty($item)){
        break;
    }
    M('zhaopian')->where(array('id'=>$item['id']))->save(array('create_time'=>$time));
    $path = $root_path . "/uploads/".$item['pic_url'];
    echo $path."\r\n";
    if(file_exists($path)){
        if(!file_exists($path . '_thumb.jpg')){
            $img = new \Think\Image(2);
            $img->open($path);

            $width = $img->width();
            $height = $img->height();
            $x = $y = 0;
            if($width > $height){
                $x = floor(($width - $height)/2);
                $width = $height;
            }elseif($height> $width){
                $y = floor(($height - $width)/2);
                $height = $width;
            }
            $img->crop($width, $height,$x,$y, 300, 300)->save($path . '_thumb.jpg','jpg');
        }


        if(!file_exists($path . '_thumb2.jpg')){
            $img = new \Think\Image(2);
            $img->open($path );
            $img->thumb(500, 1000)->save($path . '_thumb1.jpg');

            if(file_exists($path . '_thumb1.jpg')){
                $img = new \Think\Image(2);
                $img->open($path . '_thumb1.jpg' )->gaussianBlurImage(40,36)->save($path . '_thumb2.jpg','jpg');
            }

        }
    }

    M('zhaopian')->where(array('id'=>$item['id']))->save(array('is_create'=>1));
}while(true);