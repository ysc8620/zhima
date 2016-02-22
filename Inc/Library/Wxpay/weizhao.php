<?php
ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);

require_once dirname(__FILE__) . "/lib/WxPay.Api.php";
require_once dirname(__FILE__) . "/example/WxPay.JsApiPay.php";
require_once dirname(__FILE__) . '/lib/WxPay.Notify.php';
require_once dirname(__FILE__) . '/example/log.php';
//初始化日志
$logHandler= new CLogFileHandler(dirname(__FILE__) . "/logs/wz".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

//打印输出数组信息
function printf_info($data)
{
    foreach($data as $key=>$value){
        echo "<font color='#00ff55;'>$key</font> : $value <br/>";
    }
}

/**
 * 生成支付接口内容
 * @param $data
 * @param bool $debug
 * @return json
 */
function jsapipay($data, $debug = false){
    // C('weixin.weixin_')
    //①、获取用户openid
    $tools = new JsApiPay();
//    $openId = $tools->GetOpenid();
    if(!empty($data['openid'])){
        $openId = $data['openid'];
    }else{
        echo "empty openid";
        die();
    }

    //②、统一下单
    $input = new WxPayUnifiedOrder();
    $input->SetBody($data['body']);
    $input->SetAttach($data['attach']);
    $input->SetOut_trade_no($data['order_sn']);
    $input->SetTotal_fee($data['total_fee']);
    $input->SetTime_start(date("YmdHis"));
    $input->SetTime_expire(date("YmdHis", time() + 600));
    $input->SetGoods_tag($data['goods_tag']);
    $input->SetNotify_url("http://{$_SERVER[HTTP_HOST]}/weixin/notify.html");
    $input->SetTrade_type("JSAPI");
    $input->SetOpenid($openId);

    $order = WxPayApi::unifiedOrder($input);
    if($debug){
        echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        printf_info($order);
    }

    $jsApiParameters = $tools->GetJsApiParameters($order);
    return $jsApiParameters;
}

/**
 * 系统回调
 */
function notify(){
    Log::DEBUG("begin notify");
    $notify = new PayNotifyCallBack();
    $notify->Handle(false);
}

/**
 * 退单
 * $transaction_id 微信订单号
 * $out_trade_no 系统订单号
 * $total_fee 订单金额
 * $refund_fee 退款金额
 *
 */
function refund($data = array()){
    $input = new WxPayRefund();
    if($data['transaction_id']){
        $input->SetTransaction_id($data['transaction_id']);
    }

    if($data['out_trade_no']){
        $input->SetOut_trade_no($data['out_trade_no']);
    }

    $input->SetTotal_fee($data['total_fee']);
    $input->SetRefund_fee($data['refund_fee']);
    $input->SetOut_refund_no(get_order_sn('zr'));
    $input->SetOp_user_id(WxPayConfig::MCHID);

    return WxPayApi::refund($input);

}

/**
 * 退单
 * $transaction_id 微信订单号
 * $out_trade_no 系统订单号
 * $total_fee 订单金额
 * $refund_fee 退款金额
 *
 */
function sendHongBao($data = array()){
    $input = new WxSendHongBao();
    $input->SetMch_billno($data['mch_billno']); // 红包编号
    $input->SetSend_name($data['send_name']);   // 发送人
    $input->SetRe_openid($data['re_openid']);   // 接收人
    $input->SetTotal_amount($data['total_amount']);  // 发送金额
    $input->SetWishing($data['wishing']);  // 祝福语
    $input->SetAct_name($data['act_name']); // 活动名称
    $input->SetRemark($data['remark']);  // 备注
    //print_r($input);die();
    return WxPayApi::sendHongbao($input);
}

/**
 * 退单
 * $partner_trade_no 微信订单号
 * $openid 收款id
 * $re_user_name 收款人
 * $amount 打款金额
 * $desc 说明
 *

<partner_trade_no>100000982014120919616</partner_trade_no>
<openid>ohO4Gt7wVPxIT1A9GjFaMYMiZY1s</openid>
<check_name>OPTION_CHECK</check_name>
<re_user_name>张三</re_user_name>
<amount>100</amount>
<desc>节日快乐!</desc>
<spbill_create_ip>10.2.3.10</spbill_create_ip>
<sign>C97BDBACF37622775366F38B629F45E3</sign>
 *
 */
function sendPay($data = array()){
    $input = new WxSendPay();
    $input->SetPartner_Trade_No($data['partner_trade_no']); // 红包编号
    $input->SetRe_user_name($data['re_user_name']);   // 发送人
    $input->SetOpenid($data['openid']);   // 接收人
    $input->SetAmount($data['amount']);  // 发送金额
    $input->SetDesc($data['desc']);  // 备注
    //print_r($input);die();
    return WxPayApi::SendPay($input);
}

class PayNotifyCallBack extends WxPayNotify
{
    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        Log::DEBUG("query:" . json_encode($result));
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            f_log(http_build_query($result),dirname(__FILE__).'/wzlog.log');

            $result['addtime'] = time();
            $id = M('pay_log')->add($result);

            // 更改order 状态
            // 更改 hongbao状态
            $result['out_trade_no'];
            $order = M('zhaopian_order')->where("order_sn='{$result['out_trade_no']}'")->find();
            $user = M('user')->find($order['user_id']);
            if($order){
                // 重复操作
                if($order['state'] > 1){
                    return true;
                }
                $order_data = array(
                    'pay_id' => $id,
                    'pay_time' => time(),
                    'transaction_id'=>$result['transaction_id'],
                    'state' => 2
                );
                M('zhaopian_order')->where("id='{$order['id']}'")->save($order_data);

                $zhaopian = M('zhaopian')->where("id='{$order['hongbao_id']}'")->find();
                if($zhaopian){
                    $data = array(
                        'update_time' => time(),
                        'total_num' => $zhaopian['total_num'] + 1,
                        'total_amount' =>$zhaopian['total_amount'] + $order['amount']
                    );
                    $zhaopian = M('zhaopian')->where("id='{$order['hongbao_id']}'")->save($data);
                    $zhaopian_user = M('user')->find($zhaopian['user_id']);
                    // 自动发送红包
                    if(true){
//                        $bao = array(
//                            'mch_billno' =>get_order_sn('wz'),
//                            'send_name' => '红包照片',
//                            're_openid' =>$zhaopian['openid'],
//                            'total_amount' => floor($order['amount'] * 0.98 * 100),
//                            'wishing' => '恭喜您！您在照片刚刚"'.$user['name'].'"购买了',
//                            'act_name'=> '红包照片',
//                            'remark' => '红包照片',
//                        );

                        $bao = array(
                            'partner_trade_no' => get_order_sn(),
                            're_user_name'=>$zhaopian_user['name'],
                            'openid' => $zhaopian['openid'],
                            'amount' => floor($order['amount'] * 0.98 * 100),
                            'desc'=>'恭喜您！您在照片刚刚"'.$user['name'].'"购买了'
                        );

                        $send = $bao;
                        $send['user_id'] = $zhaopian['user_id'];
                        $send['addtime'] = time();
                        $send['order_id'] = $order['id'];
                        $send['zhaopian_id'] = $order['zhaopian_id'];
                        $hongbao_id = M('zhaopian_pay')->add($send);
                        if($hongbao_id){
                            M('zhaopian_order')->where(array("id='{$order['id']}'"))->save(array('send_id'=>$hongbao_id, 'send_sn'=>$bao['partner_trade_no'], 'send_time'=>time()));
                            $data = sendPay($bao);
                            if($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS'){
                                M('zhaopian_order')->where(array("id='{$order['id']}'"))->save(array('is_send_zhaopian'=>1));
                                M('zhaopian_pay')->where(array("id='$hongbao_id'"))->save(array('state'=>2, 'payment_no'=>$data['payment_no']));

                                $user_amount = number_format($order['amount'] * 0.98,2);
$msg =  "你发布的照片有朋友购买了！

照片标题：{$zhaopian['remark']}

支付金额：￥{$order['amount']}元

好友购买照片钱已经通过微信支付打给你，其中已扣除2%微信支付手续费，扣除后金额为{$user_amount}元";
\Wechat\Wxapi::send_wxmsg($zhaopian['openid'],'红包照片状态提醒',U('/zhaopian/detail',array('id'=>$zhaopian['number_no']),true,true),$msg );
                            }else{
                                M('hongbao_send')->where(array("id='$hongbao_id'"))->save(array('state'=>3));
                                $user_amount = number_format($order['amount'] * 0.98,2);
$msg = "你发布的照片有朋友购买了！

照片标题：{$zhaopian['remark']}

支付金额：￥{$order['amount']}元

红包将会在1~3个工作内，通过微信红包打给你，
其中已扣除2%的微信支付手续费，扣除后金额为{$user_amount}元。
因为微信支付到我们的账户需要1~3个工作日，我们
的账户预存垫付的现金不足，暂时不能实时转账，希望
理解。资金安全请你放心，如果有疑问请联系客服。";
                                \Wechat\Wxapi::send_wxmsg($zhaopian['openid'],'红包照片状态提醒',U('/zhaopian/detail',array('id'=>$zhaopian['number_no']),true,true),$msg );
                                $sys_openid = "oV3oMxP5wdTR8BpptzNq2tDdGtLk";
                                $msg = "重要提示! 红包发送异常!!! 可能余额不足,或支付金额异常,支付金额:{$user_amount},请及时处理.";
                                \Wechat\Wxapi::send_wxmsg($sys_openid,'红包照片状态提醒',"http://{$_SERVER['HTTP_HOST']}",$msg);
                            }
                        }
                    }
                }

            }
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();

        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }
        return true;
    }
}

?>
