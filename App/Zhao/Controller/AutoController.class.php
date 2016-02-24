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
require_once ROOT_PATH .'/Inc/Library/Wxpay/weizhao.php';
class AutoController extends Controller {
    // 自动发送红包
    function sendhongbao(){
        $order_list = M('zhaopian_order')->where(array('state'=>2, 'is_send_zhaopian'=>0))->select();

        foreach($order_list as $order){
            $hongbao_send = M('zhaopian_pay')->where(array('id'=>$order['send_id']))->find();
            $hongbao_user = M('user')->find($order['zhaopian_user_id']);
            if(!$hongbao_send){
                $order_user = M('user')->find($order['user_id']);
//                $bao = array(
//                    'mch_billno' =>get_order_sn(),
//                    'send_name' => '红包照片',
//                    're_openid' =>$order['zhaopian_openid'],
//                    'total_amount' => floor($order['amount'] * 0.98 * 100),
//                    'wishing' => '恭喜您！你发布的照片有朋友购买了。',
//                    'act_name'=> '红包照片',
//                    'remark' => '红包照片',
//                );

                $bao = array(
                    'partner_trade_no' => get_order_sn(),
                    're_user_name'=>$hongbao_user['name'],
                    'openid' => $order['zhaopian_openid'],
                    'amount' => floor($order['amount'] * 0.98 * 100),
                    'desc'=>'恭喜您！您在照片刚刚"'.$order_user['name'].'"购买了'
                );
                $send = $bao;
                $send['user_id'] = $order['zhaopian_user_id'];
                $send['addtime'] = time();
                $send['order_id'] = $order['id'];
                $send['zhaopian_id'] = $order['zhaopian_id'];

                $hongbao_id = M('zhaopian_pay')->add($send);
                if($hongbao_id){
                    M('zhaopian_order')->where(array("id='{$order['id']}'"))->save(array('send_id'=>$hongbao_id, 'send_sn'=>$bao['partner_trade_no'], 'send_time'=>time()));
                    $hongbao_send = M('zhaopian_pay')->find($hongbao_id);
                }
            }

            // 发送红包
            if($hongbao_send){
                if($hongbao_send['state'] == 2){
                    continue;
                }
//                $bao = array(
//                    'mch_billno' =>$hongbao_send['mch_billno'],
//                    'send_name' => '红包照片',
//                    're_openid' =>$order['zhaopian_openid'],
//                    'total_amount' => floor($order['amount'] * 0.98 * 100),
//                    'wishing' => '恭喜您！你发布的照片有朋友购买了。',
//                    'act_name'=> '红包照片',
//                    'remark' => '红包照片',
//                );

                $bao = array(
                    'partner_trade_no' => $hongbao_send['partner_trade_no'],
                    're_user_name'=>$hongbao_send['re_user_name'],
                    'openid' => $order['zhaopian_openid'],
                    'amount' => floor($order['amount'] * 0.98 * 100),
                    'desc'=>'恭喜您！您在照片刚刚"'.$order_user['name'].'"购买了'
                );

                $data = sendPay($bao);

                if($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS'){
                    M('zhaopian_order')->where(array("id='{$order['id']}'"))->save(array('is_send_zhaopian'=>1));
                    M('zhaopian_pay')->where(array("id='{$hongbao_send['id']}'"))->save(array('state'=>2, 'payment_no'=>$data['payment_no']));

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
        $radius = I('request.r', 80);
        $sigma = I('request.t', 8);
//        echo $radius, $sigma;
//        die();
        ini_set('memory_limit', '1000M');
//
        header('Content-type: image/jpeg');
        $path = ROOT_PATH . '/uploads/10010/20160219/zp_1455874253897.gif';
        $img = new \Think\Image(2);
        $img->open($path)->save(ROOT_PATH.'gif.jpg','jpg');

    }
    function t(){
        \Wechat\Wxapi::authorize();
        //print_r($d);
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
                $img->thumb(500, 1000)->save($pic_url . '_thumb1.jpg','jpg');
                $img2 = new \Think\Image(\Think\Image::IMAGE_IMAGICK);

                $img2->open($pic_url . '_thumb1.jpg')->gaussianBlurImage(90,9)->save($pic_url . '_thumb2.jpg');
            }

        }
    }

    function send(){

        $data = array(
            'mch_billno' =>get_order_sn(),
            'send_name' => '红包照片',
            're_openid' =>'oV3oMxFJRKo8LxX-WGfbHc-wmdE8',
            'total_amount' => 100,
            'wishing' => '测试红包。',
            'act_name'=> '红包照片',
            'remark' => '红包照片',
        );
        $rs = sendPay($data);
    }

    function pay(){
        $data = array(
            'partner_trade_no' => get_order_sn(),
            're_user_name'=>'王苏蕴',
            'openid' => 'oV3oMxP5wdTR8BpptzNq2tDdGtLk',
            'amount' => 100,
            'desc'=>'测试企业付款接口'
        );
         $rs = sendPay($data);
        var_dump($rs);
        /*{
            $input = new WxSendPay();
            $input->SetPartner_Trade_No($data['partner_trade_no']); // 红包编号
            $input->SetRe_user_name($data['re_user_name']);   // 发送人
            $input->SetOpenid($data['openid']);   // 接收人
            $input->SetAmount($data['amount']);  // 发送金额
            $input->SetDesc($data['desc']);  // 备注
        }*/
    }
}