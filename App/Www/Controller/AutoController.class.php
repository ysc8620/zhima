<?php

// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Www\Controller;
use Think\Controller;
set_time_limit(0);
require_once ROOT_PATH .'/Inc/Library/Wxpay/weixin.php';
class AutoController extends Controller {
    // 自动发送红包
    function sendhongbao(){
        // 设置过期
        $list = M('hongbao')->where(array('state'=>1, 'addtime'=>array('lt', time()-86400)))->select();
       foreach($list as $item){
           // 设置过期
           M('hongbao')->where(array('id'=>$item['id']))->save(array('state'=>3));
       }

        $hongbao_list = M('hongbao')->where(array('state'=>2, 'is_send_hongbao'=>0))->select();
        foreach($hongbao_list as $hongbao){
            $hongbao_send = M('hongbao_send')->where(array('id'=>$hongbao['hongbao_id']))->find();
            if(!$hongbao_send){
                $bao = array(
                    'mch_billno' =>get_order_sn(),
                    'send_name' => '凑红包',
                    're_openid' =>$hongbao['openid'],
                    'total_amount' => floor($hongbao['total_amount'] * 0.98 * 100),
                    'wishing' => '恭喜您！您在凑红包发起的凑红包已经完成。',
                    'act_name'=> '凑红包',
                    'remark' => '凑红包',
                );
                $send = $bao;
                $send['user_id'] = $hongbao['user_id'];
                $send['addtime'] = time();
                $hongbao_id = M('hongbao_send')->add($send);
                if($hongbao_id){
                    M('hongbao')->where(array("id='{$hongbao['id']}'"))->save(array('hongbao_id'=>$hongbao_id, 'hongbao_sn'=>$bao['mch_billno'], 'hongbao_time'=>time()));
                    $hongbao_send = M('hongbao_send')->find($hongbao_id);
                }
            }

            // 发送红包
            if($hongbao_send){
                $bao = array(
                    'mch_billno' =>$hongbao_send['mch_billno'],
                    'send_name' => '凑红包',
                    're_openid' =>$hongbao['openid'],
                    'total_amount' => floor($hongbao['total_amount'] * 0.98 * 100),
                    'wishing' => '恭喜您！您在凑红包发起的凑红包已经完成。',
                    'act_name'=> '凑红包',
                    'remark' => '凑红包',
                );

                $data = sendHongBao($bao);
                if($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS'){
                    M('hongbao')->where(array("id='{$hongbao['id']}'"))->save(array('is_send_hongbao'=>1));
                    M('hongbao_send')->where(array("id='{$hongbao_send['id']}'"))->save(array('state'=>2, 'send_listid'=>$data['send_listid']));

                    // 幸运星
                    $star = M('hongbao_order')->where(array('hongbao_id'=>$hongbao['id'], 'is_star'=>1))->find();
                    if(!$star){
                        $list = M('hongbao_order')->where("hongbao_id='{$hongbao['id']}' AND state=2")->select();
                        if($list){
                            $ids = array();
                            foreach($list as $r){
                                $ids[] = $r['id'];
                            }
                            shuffle($ids);
                            $k = array_rand($ids);
                            $id = $ids[$k];
                            if($id){
                                $order_info = M('hongbao_order')->find($id);
                                $user_info = M('user')->find($order_info['user_id']);
                                M('hongbao_order')->where("id='$id'")->save(array('is_star'=>1));
                            }
                        }
                    }else{
                        $user_info = M('user')->find($star['user_id']);
                    }

                    $user_amount = number_format($hongbao['total_amount'] * 0.98,2);
                    $msg =  "你发起的凑红包成功啦！

众筹标题：{$hongbao['remark']}

众筹进度：￥{$hongbao['total_amount']}已成功！

幸运星：{$user_info['name']}

红包已经通过微信红包打给你，其中已扣除2%微信支付手续费，扣除后金额为{$user_amount}元";
                    \Wechat\Wxapi::send_wxmsg($hongbao['openid'],'众筹状态提醒',U('/hongbao/detail',array('id'=>$hongbao['number_no']),true,true),$msg );
                    $msg = "幸运星就是你！没想到吧

众筹标题：{$hongbao['remark']}

众筹进度：￥{$hongbao['total_amount']}已成功！

快找发起人要福利吧 :D";
                    \Wechat\Wxapi::send_wxmsg($user_info['openid'],'众筹状态提醒',U('/hongbao/detail',array('id'=>$hongbao['number_no']),true,true),$msg );
                    $log = "发送红包成功, 红包编号：{$hongbao['id']},发送编号：{$hongbao_send['id']}";
                    f_log($log, ROOT_PATH.'Runtime/Logs/hongbao.log');
                    echo $log."<br/>";
                }else{
                    $log = "发送红包失败, 红包编号：{$hongbao['id']},发送编号：{$hongbao_send['id']}";
                    f_log($log, ROOT_PATH.'Runtime/Logs/hongbao.log');
                    echo $log."<br/>";
                }

                sleep(5);
            }
        }
        die('ok');
    }

    /**
     * 订单退款
     */
    function autorefund(){
        //
        $list = M('hongbao')->where(array('state'=>3, ' 	is_refund'=>0))->select();
        if($list){
            foreach($list as $hongbao){
                $order_list = M('hongbao_order')->where(array('hongbao_id'=>$hongbao['id'], 'state'=>2, 'is_refund'=>0))->select();
                if($order_list){
                    foreach($order_list as $order){
                        $rs = refund(array('out_trade_no'=>$order['order_sn'], 'total_fee'=>$order['total_amount']*100, 'refund_fee'=>$order['total_amount']*100));
                        if($rs['return_code'] == 'SUCCESS' && $rs['result_code'] == 'SUCCESS'){
                            M('hongbao_order')->where(array("id"=>$order['id']))->save(array('is_refund'=>1,'refund_time'=>time()));
                            $log = "订单退款成功, 红包编号：{$hongbao['id']},订单编号：{$order['id']}";
                            f_log($log, ROOT_PATH.'Runtime/Logs/refund.log');
                            echo $log."<br/>";
                        }else{
                            $log = "订单退款失败, 红包编号：{$hongbao['id']},订单编号：{$order['id']}";
                            f_log($log, ROOT_PATH.'Runtime/Logs/refund.log');
                            echo $log."<br/>";
                        }
                        sleep(5);
                    }
                }
                M('hongbao')->where(array('id'=>$hongbao['id']))->save(array('is_refund'=>1));

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