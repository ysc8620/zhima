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
require_once ROOT_PATH .'/Inc/Library/Wxpay/weixin.php';
class AutoController extends Controller {
    // 自动发送红包
    function sendhongbao(){
        $hongbao_list = M('hongbao')->where(array('state'=>2, 'is_send_hongbao'=>0))->select();
        foreach($hongbao_list as $hongbao){
//            $order_list = M('hongbao_order')->where(array('state'=>2))->select();
//            foreach($order_list as $order){
//
//            }
            $data = array(
                'mch_billno' =>get_order_sn(),
                'send_name' => '凑红包',
                're_openid' =>$hongbao['openid'],
                'total_amount' => $hongbao['total_amount'] * 0.99 * 100,
                'wishing' => '恭喜您！您在凑红包发起的凑红包已经完成。',
                'act_name'=> '凑红包',
                'remark' => '凑红包',
            );
            $data = sendHongBao($data);
            M('hongbao')->where(array('id'=>$hongbao['id']))->save(array('is_send_hongbao'=>1));
        }
    }

    function autoclose(){

    }
}