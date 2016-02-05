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
                print_r($data);

                if($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS'){
                    M('zhaopian_order')->where(array("id='{$order['id']}'"))->save(array('is_send_zhaopian'=>1));
                    M('zhaopian_send')->where(array("id='{$hongbao_send['id']}'"))->save(array('state'=>2, 'send_listid'=>$data['send_listid']));

                  $log = "发送红包成功, 红包编号：{$order['id']},发送编号：{$hongbao_send['id']}";
                    f_log($log, ROOT_PATH.'Runtime/Logs/zhaopian.log');
                    echo $log."<br/>";
                }else{
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

    function test(){
        print_r($_COOKIE);
        print_r($_SESSION);
        echo time();
    }
}