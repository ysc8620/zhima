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
        set_time_limit(0);

        $hongbao_list = M('hongbao')->where(array('state'=>2, 'is_send_hongbao'=>0))->select();
        foreach($hongbao_list as $hongbao){
            $bao = array(
                'mch_billno' =>get_order_sn(),
                'send_name' => '凑红包',
                're_openid' =>$hongbao['openid'],
                'total_amount' => $hongbao['total_amount'] * 0.99 * 100,
                'wishing' => '恭喜您！您在凑红包发起的凑红包已经完成。',
                'act_name'=> '凑红包',
                'remark' => '凑红包',
            );
            $send = $bao;
            $send['user_id'] = $hongbao['user_id'];
            $send['addtime'] = time();
            $hongbao_id = M('hongbao_send')->add($send);
            if($hongbao_id){
                $data = sendHongBao($bao);
                if($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS'){
                    M('hongbao')->where(array('id'=>$hongbao['id']))->save(array('is_send_hongbao'=>1, 'hongbao_id'=>$hongbao_id, 'hongbao_sn'=>$bao['mch_billno'], 'hongbao_time'=>time()));
                    M('hongbao_send')->where(array("id"=>$hongbao_id))->save(array('state'=>2, 'send_listid'=>$data['send_listid']));
                }else{
                    M('hongbao_send')->where(array("id"=>$hongbao_id))->save(array('state'=>3));
                }
            }
        }
        die('ok');
    }

    function autoclose(){

    }
}