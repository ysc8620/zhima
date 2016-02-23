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
        $pic_url = "/uploads/".$item['user_id'].'/'.date("Ymd").'/zp_'.time().rand(111,999).'.jpg';
        if(!file_exists(dirname($root_path . $pic_url))){
            mkdir(dirname($root_path . $pic_url), 0777, true);
        }
        $data['pic_url'] =  $pic_url;
    }else{
        $pic_url = $item['pic_url'];
    }
    M('zhaopian_pic')->where(array('id'=>$item['id']))->save($data);

    echo $pic_url."\r\n";
    if(!file_exists($pic_url)){
        $ds = \Wechat\Wxapi::downloadWeixinFile($item['media_id']);
        \Wechat\Wxapi::saveWeixinFile($root_path . $pic_url,$ds['body']);
    }

    if($item['is_default']){
        M('zhaopian')->where(array('id'=>$item['zhaopian_id']))->save(array('pic_url'=>$pic_url));

    }
    M('zhaopian_pic')->where(array('id'=>$item['id']))->save(array('state'=>1));
}while(true);