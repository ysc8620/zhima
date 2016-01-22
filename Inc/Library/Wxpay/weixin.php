<?php
ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);

require_once dirname(__FILE__) . "/lib/WxPay.Api.php";
require_once dirname(__FILE__) . "/example/WxPay.JsApiPay.php";
require_once dirname(__FILE__) . '/lib/WxPay.Notify.php';
require_once dirname(__FILE__) . '/example/log.php';
//初始化日志
$logHandler= new CLogFileHandler(dirname(__FILE__) . "/logs/".date('Y-m-d').'.log');
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
    $input->SetOut_refund_no(get_order_sn());
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
            f_log(http_build_query($result),dirname(__FILE__).'/mylog.log');

            $result['addtime'] = time();
            $id = M('pay_log')->add($result);

            // 更改order 状态
            // 更改 hongbao状态
            $result['out_trade_no'];
            $order = M('hongbao_order')->where("order_sn='{$result['out_trade_no']}'")->find();
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
                M('hongbao_order')->where("id='{$order['id']}'")->save($order_data);

                $hongbao = M('hongbao')->where("id='{$order['hongbao_id']}'")->find();
                if($hongbao){
                    $data = array(
                        'update_time' => time(),
                        'total_num' => $hongbao['total_num'] + $order['part_num'],
                        'total_pay_amount' => $hongbao['total_pay_amount'] + $order['total_amount'],
                        'total_user' => $hongbao['total_user'] + 1
                    );
                    if($data['total_num'] >= $hongbao['total_part'] || $data['total_pay_amount'] >= $hongbao['total_amount']){
                        $data['state'] = 2;
                    }
                    M('hongbao')->where("id='{$order['hongbao_id']}'")->save($data);
                    if($hongbao['state'] > 1){
                        return true;
                    }
                    // 设置幸运星
                    if($data['state'] == 2 ){
                        $list = M('hongbao_order')->where("hongbao_id='{$order['hongbao_id']}' AND state=2")->select();
                        if($list){
                            $ids = array();
                            foreach($list as $r){
                                $ids[] = $r['id'];
                            }
                            $k = array_rand($ids);
                            $id = $ids[$k];
                            if($id){
                                $order_info = M('hongbao_order')->find($id);
                                $user_info = M('user')->find($order_info['user_id']);
                                M('hongbao_order')->where("id='$id'")->save(array('is_star'=>1));
                            }
                        }
                    }
                    // 自动发送红包
                    if($data['state'] == 2){
                        $bao = array(
                            'mch_billno' =>get_order_sn(),
                            'send_name' => '凑红包',
                            're_openid' =>$hongbao['openid'],
                            'total_amount' => $hongbao['total_amount'] * 0.98 * 100,
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
                            $data = sendHongBao($bao);
                            if($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS'){
                                M('hongbao')->where(array("id='{$hongbao['id']}'"))->save(array('is_send_hongbao'=>1));
                                M('hongbao_send')->where(array("id='$hongbao_id'"))->save(array('state'=>2, 'send_listid'=>$data['send_listid']));

                                $user_amount = $hongbao['total_amount'] * 0.98;
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

                            }else{
                                M('hongbao_send')->where(array("id='$hongbao_id'"))->save(array('state'=>3));
                                $user_amount = $hongbao['total_amount'] * 0.98;
$msg = "你发起的凑红包成功啦！

众筹标题：{$hongbao['remark']}

众筹进度：￥{$hongbao['total_amount']}已成功！

幸运星：{$user_info['name']}

红包将会在1~3个工作内，通过微信红包打给你，
其中已扣除2%的微信支付手续费，扣除后金额为{$user_amount}元。
因为微信支付到我们的账户需要1~3个工作日，我们
的账户预存垫付的现金不足，暂时不能实时转账，希望
理解。资金安全请你放心，如果有疑问请联系客服。";
                                \Wechat\Wxapi::send_wxmsg($hongbao['openid'],'众筹状态提醒',U('/hongbao/detail',array('id'=>$hongbao['number_no']),true,true),$msg );
                                $sys_openid = "obb1AuA79tIJ-BGY7HA38FXAJwoc";
                                $msg = "重要提示！ 红包发送异常！！！";
                                \Wechat\Wxapi::send_wxmsg($sys_openid,'众筹状态提醒',"http://{$_SERVER['HTTP_HOST']}",$msg);
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
