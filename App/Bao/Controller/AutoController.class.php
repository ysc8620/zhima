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
        $list = M('bao')->where(array('state'=>2, 'addtime'=>array('lt', time() - 259200), 'is_refund'=>0))->select();
        if($list){
            foreach($list as $hongbao){
                if(!$hongbao['refund_sn']){
                    $order_sn = get_order_sn('BR');
                    M('bao')->where(array('id'=>$hongbao['id']))->save(array('refund_sn'=>$order_sn));
                }else{
                    $order_sn = $hongbao['refund_sn'];
                }

                $total_amount = M('bao_order')->where(array('bao_id'=>$hongbao['id'],'user_id'=>0))->sum('amount');
                if($total_amount >0 ){
                    $refund_amount = 0;
                    if($total_amount == $hongbao['total_amount'] * 0.98){
                        $refund_amount = $hongbao['total_amount'];
                    }else{
                        $refund_amount = $total_amount;
                    }

                }
                $rs = (array('out_trade_no'=>$order_sn, 'total_fee'=>$hongbao['total_amount']*100, 'refund_fee'=>$refund_amount*100));
                print_r($rs);
                if($rs['return_code'] == 'SUCCESS' && $rs['result_code'] == 'SUCCESS'){
                    M('bao')->where(array("id"=>$hongbao['id']))->save(array('is_refund'=>1,'refund_time'=>time(),'refund_sn'=>$order_sn));
                    M('hongbao')->where(array('id'=>$hongbao['id']))->save(array('is_refund'=>1));
                }else{
                    $log = "订单退款失败, 红包编号：{$hongbao['id']},退款订单编号：{$order_sn}";
                    f_log($log, ROOT_PATH.'Runtime/Logs/refund.log');
                    echo $log."<br/>";
                }
                //sleep(5);

                //

            }
        }
        die('ok');
    }
}