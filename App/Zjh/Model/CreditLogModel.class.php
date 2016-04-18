<?php 
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.zhimale.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:  火鸡 <834758588@qq.com>
// +----------------------------------------------------------------------
namespace Zjh\Model;
use Think\Model;
class CreditLogModel extends Model {
    /**
     * 添加日志
     * @param $user_id
     * @param $credit
     */
    public function add_log($user_id,$credit,$type='minus',$channel='game',$from_id=0,$remark=''){
        $data = array(
            'user_id' => $user_id,
            'credit_type' => $type,
            'credit' => $credit,
            'channel' => $channel,
            'from_id' => $from_id,
            'remark' => $remark,
            'addtime' => time()
        );
        return M('user_pay as a')->add($data);
    }
}