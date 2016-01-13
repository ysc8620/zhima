<?php
namespace Wxpay;
ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ERROR);

require_once dirname(__FILE__) . "/lib/WxPay.Api.php";
require_once dirname(__FILE__) . "/example/WxPay.JsApiPay.php";
require_once dirname(__FILE__) . '/example/log.php';

//初始化日志
$logHandler= new CLogFileHandler(dirname(__FILE__) . "/logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);


class Weixinpay{

    /**
     * 生成支付信息
     * @param $data
     * @return mixed
     */
    public function pay($data){
        //①、获取用户openid
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();

        //②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($data['body']);
        $input->SetAttach($data['attach']);
        $input->SetOut_trade_no($data['order_sn']);
        $input->SetTotal_fee($data['total_fee']);
        $input->SetTime_start($data['time_start']); // data('YmdHis')
        $input->SetTime_expire($data['time_expire']);// date("YmdHis", time() + 600)
        $input->SetGoods_tag($data['goods_tag']);
        $input->SetNotify_url("http://{$_SERVER[HTTP_HOST]}/weixin/notify.html");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        return $jsApiParameters;
    }

    public function notify(){

    }
}
?>

