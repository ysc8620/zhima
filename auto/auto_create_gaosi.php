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
echo '1';
include($root_path . '/auto/config.php');
echo '1';
$db = new db();
echo '1';
do{
    $time = time() - 600;
    $item = $db->get_row("SELECT * FROM zml_zhaopian WHERE is_create = 0 and create_time > $time LIMIT 1");
    echo '1';
    if(empty($item)){
        break;
    }echo '1';
    $db->query("UPDATE zml_zhaopian SET create_time='$time' WHERE id='{$item['id']}'");
    $path = $root_path . "/uploads/".$item['pic_url'];
    echo $path."\r\n";
    if(file_exists($path)){
        if(!file_exists($path . '_thumb.jpg')){
            $img = new Imagick($path);

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
            $img->crop($width, $height,$x,$y, 300, 300);
            $img->save($path . '_thumb.jpg', null, 80,true);
        }

        if(!file_exists($path . '_thumb2.jpg')){
            $img = new Imagick($path);
            $img->thumb(500, 1000);
            $img->save($path . '_thumb1.jpg', null, 80,true);

            $img = new Imagick($path . '_thumb1.jpg');
            $img->gaussianBlurImage(40,36);
            $img->save($path . '_thumb2.jpg', null, 80,true);
        }
    }
    $db->query("UPDATE zml_zhaopian SET is_create=1 WHERE id='{$item['id']}'");
}while(true);