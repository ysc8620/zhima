<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zjh\Controller;
use Think\Controller;
require_once ROOT_PATH .'/Inc/Library/Wxpay/weixin.php';
class TopController extends BaseController {
    public function index(){
        $this->title = "充值中心";

        $this->user = M('user')->find($this->user_id);
    	//首页幻灯片获取
    	$this->display();
    }

    public function pay(){
        $credit = I('get.credit',0, 'intval');

        $json = array(
            'error'=>0,
            'message'=>'',
            'data'=>''
        );

        do{
            if($credit < 1 || $credit > 999999){
                $user = M('user')->find($this->user_id);
                $data['body'] = "用户充值";
                $data['attach'] = "用户充值";
                $data['order_sn'] = get_order_sn('TP') ;
                $data['total_fee'] = $credit * 10;
                $data['time_start'] = date('YmdHis');
                $data['time_expire'] =  date("YmdHis", time() + 600);
                $data['goods_tag'] = "WXG";
                // $openid = ;//session('openid')?session('openid'):cookie('openid');
                $data['openid'] = $user['openid'];
                $this->jsApiParameters = jsapipay($data, false);

                $json['data'] = json_decode($this->jsApiParameters);
                break;



            }
            #$this->error("红包状态不能支付", U('/notes'));
            $json['error'] = 1;
            $json['message'] = "红包状态不能支付";
            break;
        }while(false);
        echo json_encode($json);
        die();
    }

    function notes(){
        $this->title = '充值记录';

        $this->display();
    }
}