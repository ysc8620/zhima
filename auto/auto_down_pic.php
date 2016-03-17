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
    $item = M('zhaopian_pic')->where("state= 0 and down_time < $time")->find();

    if(empty($item)){
        break;
    }
    $data = array(
        'down_time'=>$time
    );

    if(!$item['pic_url']){
        $pic_url = $item['user_id'].'/'.date("Ymd").'/zp_'.time().rand(111,999).'.jpg';
        $data['pic_url'] =  $pic_url;
    }else{
        $pic_url = $item['pic_url'];
    }
    M('zhaopian_pic')->where(array('id'=>$item['id']))->save($data);

    $pic_path = $root_path ."/uploads/". $pic_url;
    if(!file_exists(dirname($pic_path))){
        mkdir(dirname($pic_path), 0777, true);
    }
    echo $pic_url."\r\n";
    if(!file_exists($pic_path) && filesize($pic_path) > 4028){
        echo $item['media_id']."\r\n";
        $ds = \Wechat\Wxapi::downloadWeixinFile($item['media_id']);

        \Wechat\Wxapi::saveWeixinFile($pic_path,$ds['body']);
    }

    if($item['is_default']){
        M('zhaopian')->where(array('id'=>$item['zhaopian_id']))->save(array('pic_url'=>$pic_url));
        if(file_exists($pic_path)){
            $img = new \Think\Image(\Think\Image::IMAGE_IMAGICK);
            $img->open($pic_path);
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
            $img->crop($width, $height,$x,$y, 300, 300)->save($pic_path . '_thumb.jpg','jpg');
        }
    }
    M('zhaopian_pic')->where(array('id'=>$item['id']))->save(array('state'=>1));
}while(true);