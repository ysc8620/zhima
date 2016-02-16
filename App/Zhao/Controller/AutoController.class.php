<?php

// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zhao\Controller;
use Think\Controller;
set_time_limit(0);
require_once ROOT_PATH .'/Inc/Library/Wxpay/weixin.php';
class AutoController extends Controller {
    // 自动发送红包
    function sendhongbao(){
        $order_list = M('zhaopian_order')->where(array('state'=>2, 'is_send_zhaopian'=>0))->select();

        foreach($order_list as $order){
            $hongbao_send = M('zhaopian_send')->where(array('id'=>$order['send_id']))->find();

            if(!$hongbao_send){
                $bao = array(
                    'mch_billno' =>get_order_sn(),
                    'send_name' => '红包照片',
                    're_openid' =>$order['zhaopian_openid'],
                    'total_amount' => floor($order['amount'] * 0.98 * 100),
                    'wishing' => '恭喜您！你发布的照片有朋友购买了。',
                    'act_name'=> '红包照片',
                    'remark' => '红包照片',
                );
                $send = $bao;
                $send['user_id'] = $order['zhaopian_user_id'];
                $send['addtime'] = time();
                $send['zhaopian_order_id'] = $order['id'];

                $hongbao_id = M('zhaopian_send')->add($send);
                if($hongbao_id){
                    M('zhaopian_order')->where(array("id='{$order['id']}'"))->save(array('send_id'=>$hongbao_id, 'send_sn'=>$bao['mch_billno'], 'send_time'=>time()));
                    $hongbao_send = M('zhaopian_send')->find($hongbao_id);
                }
            }

            // 发送红包
            if($hongbao_send){
                if($hongbao_send['state'] == 2){
                    continue;
                }
                $bao = array(
                    'mch_billno' =>$hongbao_send['mch_billno'],
                    'send_name' => '红包照片',
                    're_openid' =>$order['zhaopian_openid'],
                    'total_amount' => floor($order['amount'] * 0.98 * 100),
                    'wishing' => '恭喜您！你发布的照片有朋友购买了。',
                    'act_name'=> '红包照片',
                    'remark' => '红包照片',
                );

                $data = sendHongBao($bao);

                if($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS'){
                    M('zhaopian_order')->where(array("id='{$order['id']}'"))->save(array('is_send_zhaopian'=>1));
                    M('zhaopian_send')->where(array("id='{$hongbao_send['id']}'"))->save(array('state'=>2, 'send_listid'=>$data['send_listid']));

                  $log = "发送红包成功, 红包编号：{$order['id']},发送编号：{$hongbao_send['id']}";
                    f_log($log, ROOT_PATH.'Runtime/Logs/zhaopian.log');
                    echo $log."<br/>";
                }else{

                    $log = "发送红包失败, 红包编号：{$order['id']},发送编号：{$hongbao_send['id']}";
                    f_log($log, ROOT_PATH.'Runtime/Logs/zhaopian.log');
                    echo $log."<br/>";
                }

                sleep(5);
            }
        }
        die('ok');
    }

    public function img(){
        set_time_limit(0);
        ini_set('memory_limit', '1000M');
        $img = new \Think\Image(2);
        $img->open(ROOT_PATH.'1.jpg')->thumb(300,1000)->save(ROOT_PATH.'2.jpg');
        header('Content-type: image/jpeg');


        $image = new \Imagick(ROOT_PATH.'2.jpg');
        $image->gaussianBlurImage(80,8);

        echo $image->getImageBlob();
    }

    function test(){
        print_r($_COOKIE);
        print_r($_SESSION);
        echo time();
    }

    function create(){
        set_time_limit(0);
        $img_list = M('zhaopian')->select();
        $rootPath = C('UPLOAD_PATH');
        foreach($img_list as $img){
            $pic_url = $rootPath . $img['pic_url'];
            if(file_exists($pic_url) ){
                $img = new \Think\Image(\Think\Image::IMAGE_IMAGICK);
                $img->open($pic_url);
//                $width = $img->width();
//                $height = $img->height();
//                $x = $y = 0;
//                if($width > $height){
//                    $x = floor(($width - $height)/2);
//                    $width = $height;
//                }elseif($height> $width){
//                    $y = floor(($height - $width)/2);
//                    $height = $width;
//                }
//                $img->crop($width, $height,$x,$y, 300, 300)->save($pic_url . '_thumb.jpg');
                $img->thumb(500, 1000)->save($pic_url . '_thumb1.jpg');
                $img2 = new \Think\Image(\Think\Image::IMAGE_IMAGICK);

                $img2->open($pic_url . '_thumb1.jpg')->gaussianBlurImage(90,9)->save($pic_url . '_thumb2.jpg');
            }

        }

    }
}