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
        header("Content-type:text/html;charset=utf-8");
        $order_list = M('zhaopian_order')->where(array('state'=>2, 'is_send_zhaopian'=>0))->select();

        foreach($order_list as $order){
            $hongbao_send = M('zhaopian_pay')->where(array('id'=>$order['send_id']))->find();
            $hongbao_user = M('user')->find($order['zhaopian_user_id']);
            $order_user = M('user')->find($order['user_id']);
            if(!$hongbao_send){

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
                    'desc'=>'恭喜您！您的照片刚刚"'.$order_user['name'].'"购买了'
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
                    echo "amount:".$order['amount'];
                    print_r($data);
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

    function pay(){
        $data['body'] = "红包照片";
        $data['attach'] = "红包照片";
        $data['order_sn'] = '2016102102458654121';
        $data['total_fee'] = 100;
        $data['time_start'] = date('YmdHis');
        $data['time_expire'] =  date("YmdHis", time() + 600);
        $data['goods_tag'] = "WXG";
        // $openid = ;//session('openid')?session('openid'):cookie('openid');
        $data['openid'] = cookie('openid');

        $jsApiParameters = jsapipay($data, false);
        // print_r($jsApiParameters);
        $this->jsApiParameters = $jsApiParameters;


        $this->display();
    }

    function pay1(){
        $json = array(
            'error'=>0,
            'message'=>'',
            'data'=>''
        );

        $data['body'] = "红包照片";
        $data['attach'] = "红包照片";
        $data['order_sn'] = '2016102102458654121';
        $data['total_fee'] = 100;
        $data['time_start'] = date('YmdHis');
        $data['time_expire'] =  date("YmdHis", time() + 600);
        $data['goods_tag'] = "WXG";
        // $openid = ;//session('openid')?session('openid'):cookie('openid');
        $data['openid'] = cookie('openid');

        $jsApiParameters = jsapipay($data, false);
        // print_r($jsApiParameters);
        $this->jsApiParameters = $jsApiParameters;
        $data['message'] = $jsApiParameters;


        $json['data'] = json_decode($jsApiParameters);
        echo json_encode($json);
    }

    public function zhaopian(){
        $json = array(
            'error'=>0,
            'message'=>'',
            'data'=>''
        );

        do{
            $id = I('id','', 'strval');
            if($id){
                $order = M('zhaopian_order')->where(array('id'=>$id))->find();

                if($order){
                    $zhaopian = M('zhaopian')->where(array('number_no'=>$order['number_no']))->find();

                    if($zhaopian['state'] != 1){
                        #$this->error("红包不能支付", U('/hongbao/detail', array('id'=>$hongbao['number_no'])));
                        $json['error'] = 1;
                        $json['message'] = "照片不能支付";
                        break;
                    }

                    if($zhaopian['state'] != 1){
                        #$this->error("红包已经凑齐", U('/hongbao/detail', array('id'=>$hongbao['number_no'])));
                        $json['error'] = 1;
                        $json['message'] = "照片已关闭";
                        break;
                    }

                    if($order['state'] == 1){
                        $amount = ceil($order['amount'] *100);
                        if($amount < 1 || $amount > 20000){
                            #$this->error("红包金额不对能支付", U('/hongbao/detail', array('id'=>$order['number_no'])));
                            $json['error'] = 1;
                            $json['message'] = "支付金额超过限制.{$amount}";
                            break;
                        }
                        $data = array();
                        $data['body'] = "红包照片";
                        $data['attach'] = "红包照片";
                        $data['order_sn'] = $order['order_sn'];
                        $data['total_fee'] = $amount;
                        $data['time_start'] = date('YmdHis');
                        $data['time_expire'] =  date("YmdHis", time() + 600);
                        $data['goods_tag'] = "WXG";
                        $openid = cookie('openid')?cookie('openid'):$order['openid'];
                        $data['openid'] = $openid;
                        $str = '';
                        foreach($data as $k=>$v){
                            $str .="$k=$v,";
                        }
//
//                        $this->user = M('user')->find($zhaopian['user_id']);
//
//                        $this->title = "{$this->user['name']}凑红包";
//                        $this->zhaopian = $zhaopian;
//                        $this->order = $order;
//                        $this->id = $id;



                        try{
                            $jsApiParameters = jsapipay($data, false);
                        }catch (\Exception $e){
                            $json['error'] = 1;
                            $json['message'] = "签名失败".$e->getMessage().$str;
                            break;
                        }
                        $json['data'] = json_decode($jsApiParameters);
                        break;
                    }else{
                        //$this->error("红包状态不能支付", U('/hongbao/detail', array('id'=>$order['number_no'])));
                        $json['error'] = 1;
                        $json['message'] = "照片状态不能支付";
                        break;
                    }
                }

            }
            #$this->error("红包状态不能支付", U('/notes'));
            $json['error'] = 1;
            $json['message'] = "照片状态不能支付";
            break;
        }while(false);
        echo json_encode($json);
        die();
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

}