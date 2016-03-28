<?php

// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Bao\Controller;
use Think\Controller;
set_time_limit(0);
header("Content-type:text/html;charset=utf-8");
require_once ROOT_PATH .'/Inc/Library/Wxpay/weixin.php';
class AutoController extends Controller {
    // 自动发送红包
    function sendhongbao(){

        die('ok');
    }

    /**
     * 订单退款
     */
    function autorefund(){
        //
        //$_GET['show_sql']
        $list = M('bao')->where(array('state'=>2, 'addtime'=>array('lt', time() - 259200), 'is_refund'=>0))->select();
        if($list){
            foreach($list as $hongbao){
                if(!$hongbao['refund_sn']){
                    $order_sn = get_order_sn('BR');
                    M('bao')->where(array('id'=>$hongbao['id']))->save(array('refund_sn'=>$order_sn));
                }else{
                    $order_sn = $hongbao['refund_sn'];
                }

                $total_amount = M('bao_order')->where(array('bao_id'=>$hongbao['id'],'user_id'=>'0','state'=>1))->sum('amount');

                $refund_amount = 0;
                if($total_amount > 0 ){
                    $total_num =  M('bao_order')->where(array('bao_id'=>$hongbao['id'],'user_id'=>'0','state'=>1))->count();
                    if($total_num == $hongbao['total_num']){
                        $refund_amount = $hongbao['total_amount'];
                    }else{
                        $refund_amount = $total_amount;
                    }

                }
                if($refund_amount > 0){
                    $rs = refund(array('out_trade_no'=>$order_sn, 'total_fee'=>$hongbao['total_amount']*100, 'refund_fee'=>$refund_amount*100));

                    if($rs['return_code'] == 'SUCCESS' && $rs['result_code'] == 'SUCCESS'){
                        M('bao')->where(array("id"=>$hongbao['id']))->save(array('is_refund'=>1,'state'=>3, 'refund_time'=>time()));
                        $log = "订单退款成功, 红包编号：{$hongbao['id']},订单编号：{$order_sn}";
                        f_log($log, ROOT_PATH.'Runtime/Logs/refund.log');
                        echo $log."<br/>";
                    }else{
                        $log = "订单退款失败, 红包编号：{$hongbao['id']},退款订单编号：{$order_sn}";
                        f_log($log, ROOT_PATH.'Runtime/Logs/refund.log');
                        echo $log."<br/>";
                    }
                }

                sleep(5);

                //

            }
        }
        die('ok');
    }
}